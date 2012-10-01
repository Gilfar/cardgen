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
class PreEighthRenderer extends CardRenderer {
	public function render () {
		global $config;

		echo $this->card . '...';
		$card = $this->card;

		$settings = $this->getSettings();
		$costColors = Card::getCostColors($card->cost);
		$canvas = imagecreatetruecolor(736, 1050);

		// Art image.
		$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);

		echo '.';

		// Background image.
		if ($card->isLand()) {
			// Land frame.
			$landColors = @$this->writer->titleToLandColors[strtolower($card->title)];
			$notBasicFrame = '';
			if ($settings['card.multicolor.fetch.land.frames']) {
				switch (strtolower($card->title)) {
				case 'flooded strand': $landColors = 'WU'; break;
				case 'bloodstained mire': $landColors = 'BR'; break;
				case 'wooded foothills': $landColors = 'RG'; break;
				case 'polluted delta': $landColors = 'UB'; break;
				case 'windswept heath': $landColors = 'GW'; break;
				case 'flood plain': $landColors = 'WU'; break;
				case 'rocky tar pit': $landColors = 'BR'; break;
				case 'mountain valley': $landColors = 'RG'; break;
				case 'bad river': $landColors = 'UB'; break;
				case 'grasslands': $landColors = 'GW'; break;
				}
			}
			if (!$landColors) error('Land color missing for card: ' . $card->title);
			if (strlen($landColors) > 1) {
				$useMulticolorLandFrame = strpos($settings['card.multicolor.land.frames'], strval(strlen($landColors))) !== false;
				if (strlen($landColors) > 2 || !$useMulticolorLandFrame) $landColors = 'A';
			}
			else if(!$card->isBasicLand() && $landColors != 'A' && $landColors != 'C')
				$notBasicFrame = 'C';
			$bgImage = @imagecreatefrompng("images/preEighth/land/$notBasicFrame$landColors.png");
			if (!$bgImage) error("Background image not found for land color \"$notBasicFrame$landColors\": " . $card->title);
		} else {
			// Mono color frame.
			$bgImage = @imagecreatefrompng('images/preEighth/cards/' . $card->color . '.png');
			if (!$bgImage) error('Background image not found for mono color: ' . $card->color);
		}
		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 736, 1050);
		imagedestroy($bgImage);

		// Power / toughness.
		if ($card->pt) $this->drawText($canvas, $settings['pt.center.x'], $settings['pt.center.y'], $settings['pt.width'], $card->pt, $this->font('pt'));

		// Casting cost.
		$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $settings['cost.top'], $settings['cost.right'], $settings['cost.size']);

		echo '.';

		// Set and rarity.
		if (!$card->isBasicLand() || $settings['card.basic.land.set.symbols']) {
			$rarityMiddle = $settings['rarity.center.y'];
			if ($card->isLand()) $rarityMiddle += 2; // Rarity on pre8th lands is slightly lower.
			$rarityLeft = $this->drawRarity($canvas, $card->rarity, $card->set, $settings['rarity.right'], $rarityMiddle, $settings['rarity.height'], $settings['rarity.width'], true, $settings['card.rarity.fallback']);
		} else
			$rarityLeft = $settings['rarity.right'];

		// Tombstone sign.
		if ($settings['card.tombstone']) {
			if (strpos($card->legal, 'Flashback') !=false || strpos($card->type, 'Incarnation') !=false || $card->title == 'Riftstone Portal' || $card->title == 'Ichorid') {
				list($image, $width, $height) = getPNG('images/preEighth/tombstone.png', 'Tombstone image not found.');
				imagecopy($canvas, $image, $settings["tombstone.left"], $settings["tombstone.top"], 0, 0, $width, $height);
				imagedestroy($image);
			}
		}

		// Card title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $costLeft - $settings['title.x'], $card->getDisplayTitle(false), $this->font('title'));

		echo '.';

		// Type.
		$typeBaseline = $settings['type.y'];
		if ($card->isLand()) $typeBaseline += 2; // Type on pre8th lands is slightly lower.
		$this->drawText($canvas, $settings['type.x'], $typeBaseline, $rarityLeft - $settings['type.x'], $card->type, $this->font('type'));

		if ($card->isBasicLand()) {
			// Basic land symbol instead of legal text.
			list($image, $width, $height) = getPNG("images/symbols/land/$landColors.png", "Basic land image not found for: images/symbols/land/$landColors.png");
			imagecopy($canvas, $image, 373 - ($width / 2), 640, 0, 0, $width, $height);
			imagedestroy($image);
		} else if ($card->isLand() && strlen($landColors) == 2 && !$card->legal) {
			// Dual land symbol instead of legal text.
			if ($settings['card.dual.land.symbols'] == 1) {
				// Single hybrid symbol.
				list($image, $width, $height) = getPNG("images/symbols/land/$landColors.png", "Dual symbol image not found for: $landColors");
				imagecopy($canvas, $image, 368 - ($width / 2), 667, 0, 0, $width, $height);
				imagedestroy($image);
			} else if ($settings['card.dual.land.symbols'] == 2) {
				// One of each basic symbol.
				$landColor = substr($landColors, 0, 1);
				list($image, $width, $height) = getPNG("images/symbols/land/$landColor.png", 'Basic land image not found for: ' . $card->title);
				imagecopy($canvas, $image, 217 - ($width / 2), 640, 0, 0, $width, $height);
				imagedestroy($image);

				$landColor = substr($landColors, 1, 2);
				list($image, $width, $height) = getPNG("images/symbols/land/$landColor.png", 'Basic land image not found for: ' . $card->title);
				imagecopy($canvas, $image, 519 - ($width / 2), 640, 0, 0, $width, $height);
				imagedestroy($image);
			}
		} else {
			// Legal and flavor text.
			$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font('text'));
		}

		// Artist and copyright.
		if ($card->artist) $this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, 'Illus. ' . $card->artist, $this->font('artist'));
		if ($card->copyright) {
			$copyrightColor = '255,255,255';
			if ($card->color == 'W') $copyrightColor = '0,0,0';
			$this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $copyrightColor));
		}

		echo "\n";
		return $canvas;
	}

	public function getSettings () {
		global $rendererSettings;
		return $rendererSettings['config/config-preEighth.txt'];
	}
}

?>
