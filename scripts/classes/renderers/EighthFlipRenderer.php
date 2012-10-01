<?
////////////////////////////////////////////////////////////////////////
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
////////////////////////////////////////////////////////////////////////
class EighthFlipRenderer extends CardRenderer {
	static private $settingSections;
	static private $titleToGuild;
	static private $titleToFrameDir;

	public function render () {
		global $config;

		echo $this->card . '...';
		$card = $this->card;

		$settings = $this->getSettings();

		$costColors = Card::getCostColors($card->cost);

		$useMulticolorFrame = strlen($costColors) == 2 && strpos($settings['card.multicolor.frames'], strval(strlen($costColors))) !== false;

		$canvas = imagecreatetruecolor(736, 1050);

		// Art image.
		$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);

		echo '.';
		$cards = explode("\n-----\n", $card->legal);
		$legal1 = $cards[0];
		if (preg_match("/(.*?)\n(.*?)\n(.*?)\n(.*)/s", $cards[1], $matches)) {
			$title2 = $matches[1];
			$type2 = $matches[2];
			$pt2 = $matches[3];
			$legal2 = $matches[4];
		} else {
			preg_match("/(.*?)\n(.*?)\n(.*)/s", $cards[1], $matches);
			$title2 = $matches[1];
			$type2 = $matches[2];
			$pt2 = '';
			$legal2 = $matches[3];
		}

		// Background image, border image, and grey title/type image.
		$borderImage = null;
		$greyTitleAndTypeOverlay = null;
		if ($card->isLand()) {
			// Land frame.
			$landColors = @$this->writer->titleToLandColors[strtolower($card->title)];
			if (!$landColors) error('Land color missing for card: ' . $card->title);
			if (strlen($landColors) > 1) {
				$useMulticolorLandFrame = strpos($settings['card.multicolor.land.frames'], strval(strlen($landColors))) !== false;
				if (!$useMulticolorLandFrame) $landColors = 'A';
			}
			$bgImage = @imagecreatefrompng("images/eighth/flip/land/$landColors.png");
			if (!$bgImage) error('Background image not found for land color "$landColors": ' . $card->title);
			// Grey title/type image.
			if (strlen($landColors) >= 2 && $settings['card.multicolor.grey.title.and.type']) {
				$greyTitleAndTypeOverlay = @imagecreatefrompng('images/eighth/flip/cards/C-overlay.png');
				if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
			}
		} else {
			if ($useMulticolorFrame || $card->isDualManaCost()) {
				// Multicolor frame.
				$bgImage = @imagecreatefrompng("images/eighth/flip/cards/$costColors.png");
				if (!$bgImage) error("Background image not found for color: $costColors");
				// Grey title/type image.
				if ($settings['card.multicolor.grey.title.and.type']) {
					$greyTitleAndTypeOverlay = @imagecreatefrompng('images/eighth/flip/cards/C-overlay.png');
					if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
				}
			} else {
				// Mono color frame.
				$bgImage = @imagecreatefrompng('images/eighth/flip/cards/' . $card->color . '.png');
				if (!$bgImage) error('Background image not found for color "' . $card->color . '" in frame dir: flips');
				// Border image.
				if (strlen($costColors) == 2 && $settings['card.multicolor.dual.borders'])
					$borderImage = @imagecreatefrompng("images/eighth/flips/borders/$costColors.png");
			}
		}
		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 736, 1050);
		imagedestroy($bgImage);
		if ($borderImage) {
			imagecopy($canvas, $borderImage, 0, 0, 0, 0, 736, 1050);
			imagedestroy($borderImage);
		}
		if ($greyTitleAndTypeOverlay) {
			imagecopy($canvas, $greyTitleAndTypeOverlay, 0, 0, 0, 0, 736, 1050);
			imagedestroy($greyTitleAndTypeOverlay);
		}

		// Power / toughness.
		if ($card->pt) {
			if ($useMulticolorFrame || $card->isDualManaCost())
				$image = @imagecreatefrompng('images/eighth/flip/pt/' . substr($costColors, -1, 1) . 'u.png');
			else
				$image = @imagecreatefrompng('images/eighth/flip/pt/' . $card->color . 'u.png');
			if (!$image) error("Power/toughness image not found for color: $card->color");
			imagecopy($canvas, $image, 0, 0, 0, 0, 736, 1050);
			imagedestroy($image);
			$this->drawText($canvas, $settings['pt.upper.center.x'], $settings['pt.upper.center.y'], $settings['pt.upper.width'], $card->pt, $this->font('pt'));
			$ptLeft = $settings['pt.upper.center.x'] - $settings['pt.upper.width'];
		} else
			$ptLeft = $settings['rarity.right'];

		// Casting cost.
		$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $card->isDualManaCost() ? $settings['cost.top.dual'] : $settings['cost.top'], $settings['cost.right'], $settings['cost.size'], true);

		echo '.';
		// Set and rarity.
		if (!$card->isBasicLand() || $settings['card.basic.land.set.symbols'])
			$this->drawRarity($canvas, $card->rarity, $card->set, $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);

		// Card title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $costLeft - $settings['title.x'], $card->getDisplayTitle(), $this->font('title'));

		echo '.';

		// Type.
		$this->drawText($canvas, $settings['type.x'], $settings['type.upper.y'], $ptLeft - $settings['type.x'], $card->type, $this->font('type'));

		$this->drawLegalAndFlavorText($canvas, $settings['text.upper.top'], $settings['text.left'], $settings['text.upper.bottom'], $settings['text.right'], $legal1, '', $this->font('text'), 0/*$heightAdjust*/);

		echo '.';

		// Flip side.
		if ($pt2) {
			if ($useMulticolorFrame || $card->isDualManaCost())
				$image = @imagecreatefrompng('images/eighth/flip/pt/' . substr($costColors, 0, 1) . 'd.png');
			else
				$image = @imagecreatefrompng('images/eighth/flip/pt/' . $card->color . 'd.png');
			if (!$image) error("Power/toughness image not found for color: $color");
			imagecopy($canvas, $image, 0, 0, 0, 0, 736, 1050);
			imagedestroy($image);
			$canvasTmp = imagerotate($canvas, 180, 0, -1);
			imagedestroy($canvas);
			$canvas = $canvasTmp;
			$this->drawText($canvas, $settings['pt.lower.center.x'], $settings['pt.lower.center.y'], $settings['pt.lower.width'], $pt2, $this->font('pt'));
			$ptLeft = $settings['pt.lower.center.x'] - $settings['pt.lower.width'];
		} else {
			$canvasTmp = imagerotate($canvas, 180, 0, -1);
			imagedestroy($canvas);
			$canvas = $canvasTmp;
			$ptLeft = $settings['rarity.right'];
		}

		// Card title.
		$title2 = str_replace('AE', 'Æ', $title2);
		$title2 = str_replace("'", '’', $title2);
		$this->drawText($canvas, $settings['title.x'], $settings['title.lower.y'], $settings['cost.right'] - $settings['title.x'], $title2, $this->font('title'));

		echo '.';

		// Type.
		$this->drawText($canvas, $settings['type.x'], $settings['type.lower.y'], $ptLeft - $settings['type.x'], $type2, $this->font('type'));

		$this->drawLegalAndFlavorText($canvas, $settings['text.lower.top'], $settings['text.left'], $settings['text.lower.bottom'], $settings['text.right'], $legal2, '', $this->font('text'), 0/*$heightAdjust*/);

		// Back to normal orientation.
		$canvas = imagerotate($canvas, 180, 0, -1);

		// Artist and copyright.
		// The artist color is white if the frame behind it is black.
		$footerColor = '0,0,0';
		if ($card->isLand())
			$footerColor = '255,255,255';
		else if ($costColors == 'B')
			$footerColor = '255,255,255';
		else if ($useMulticolorFrame) {
			// Only multicolor frames with a bottom left color of black should use a white footer.
			if ((strlen($costColors) <= 2 && substr($costColors, 0, 1) == 'B') || (strlen($costColors) >= 3 && substr($costColors, 2, 1) == 'B'))
				$footerColor = '255,255,255';
		}
		if ($card->artist) {
			if ($settings['card.artist.gears']) {
				$artistSymbol = '{gear}';
			} else {
				$artistSymbol = '{brush}';
			}
			$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $artistSymbol . $card->artist, $this->font('artist', 'color:' . $footerColor));
		}
		if ($card->copyright) $this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));

		echo "\n";
		return $canvas;
	}

	public function getSettings () {
		global $rendererSettings, $rendererSections;
		$settings = $rendererSettings['config/config-eighth.txt'];
		$settings = array_merge($settings, $rendererSections['config/config-eighth.txt']['fonts - regular']);
		$settings = array_merge($settings, $rendererSections['config/config-eighth.txt']['layout - flip']);
		return $settings;
	}
}

?>
