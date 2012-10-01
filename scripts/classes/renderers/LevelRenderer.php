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
class LevelRenderer extends CardRenderer {
	static private $settingSections;
	static private $titleToGuild;
	static private $titleToFrameDir;

	public function render () {
		global $config;

		echo $this->card . '...';
		$card = $this->card;
		$settings = $this->getSettings();
		$costColors = Card::getCostColors($card->cost);
		$white = '255,255,255';

		$useMulticolorFrame = strlen($costColors) == 2;

		$canvas = imagecreatetruecolor(736, 1050);

		// Art image.
		$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);

		echo '.';

		// Background image.
		$borderImage = null;
		$greyTitleAndTypeOverlay = null;
		if ($card->isArtefact()) {
			$bgImage = @imagecreatefrompng('images/eighth/leveler/cards/Art.png');
		} else if ($useMulticolorFrame || $card->isDualManaCost()) {
			// Multicolor frame.
			if($settings['card.multicolor.gold.frame'])
				$bgImage = @imagecreatefrompng("images/eighth/leveler/cards/Gld$costColors".".png");
			else
				$bgImage = @imagecreatefrompng("images/eighth/leveler/cards/$costColors".".png");
			if (!$bgImage) error("Background image not found for color: $costColors");
		} else {
			// Mono color frame.
			$bgImage = @imagecreatefrompng('images/eighth/leveler/cards/' . $card->color . '.png');
			if (!$bgImage) error('Background image not found for color "' . $card->color . '"');
		}

		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 736, 1050);
		imagedestroy($bgImage);

		// Casting cost.
		$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $card->isDualManaCost() ? $settings['cost.top.dual'] : $settings['cost.top'], $settings['cost.right'], $settings['cost.size'], true);

		echo '.';

		// Set and rarity.
		$rarityLeft = $this->drawRarity($canvas, $card->rarity, $card->set, $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);

		// Card title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $costLeft - $settings['title.x'], $card->getDisplayTitle(), $this->font('title'));

		echo '.';

		// Type.
		$this->drawText($canvas, $settings['type.x'], $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font('type'));

		// Legal text.
		if(!preg_match_all('/(.*?)\r?\n(.*?) ([0-9\-\+]+?)\r?\n([0-9\*\-\+\/]{3,})\r?\n(.*?)\r?\n?((?<=\n)[^\s]*?(?=\s)) ([0-9\-\+]+?)\r?\n([0-9\*\-\+\/]{3,})\r?\n?(.*?)$/s', $card->legal, $matches))
			error('Wrong format for legal text: ' . $card->title);

		//1
		$this->drawText($canvas, $settings['pt.1.center.x'], $settings['pt.1.center.y'], $settings['pt.1.width'], $card->pt, $this->font('pt'));
		$this->drawLegalAndFlavorText($canvas, $settings['text.1.top'], $settings['text.1.left'], $settings['text.1.bottom'], $settings['text.1.right'], $matches[1][0], null, $this->font('text'), 0);

		//2
		$this->drawText($canvas, $settings['pt.2.center.x'], $settings['pt.2.center.y'], $settings['pt.2.width'], $matches[4][0], $this->font('pt'));
		$this->drawText($canvas, $settings['name.2.center.x'], $settings['name.2.center.y'], $settings['name.2.width'], $matches[2][0], $this->font('pt', 'glow:true'));
		$this->drawText($canvas, $settings['level.2.center.x'], $settings['level.2.center.y'], $settings['level.2.width'], $matches[3][0], $this->font('pt', 'glow:true'));
		$this->drawLegalAndFlavorText($canvas, $settings['text.2.top'], $settings['text.2.left'], $settings['text.2.bottom'], $settings['text.2.right'], $matches[5][0], null, $this->font('text'), 0);

		//3
		$this->drawText($canvas, $settings['pt.3.center.x'], $settings['pt.3.center.y'], $settings['pt.3.width'], $matches[8][0], $this->font('pt'));
		$this->drawText($canvas, $settings['name.3.center.x'], $settings['name.3.center.y'], $settings['name.3.width'], $matches[6][0], $this->font('pt', 'glow:true'));
		$this->drawText($canvas, $settings['level.3.center.x'], $settings['level.3.center.y'], $settings['level.3.width'], $matches[7][0], $this->font('pt', 'glow:true'));
		$this->drawLegalAndFlavorText($canvas, $settings['text.3.top'], $settings['text.3.left'], $settings['text.3.bottom'], $settings['text.3.right'], $matches[9][0], null, $this->font('text'), 10);


		// Artist and copyright.
		// The artist color is white if the frame behind it is black.
		$footerColor = '0,0,0';
		if ($card->isLand())
			$footerColor = '255,255,255';
		else if ($card->color == 'B' && !$card->isArtefact())
			$footerColor = '255,255,255';
		else if ($costColors == 'B' && !$card->isArtefact())
			$footerColor = '255,255,255';
		else if (stripos($card->englishType, 'Eldrazi') !== false)
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
		global $rendererSettings;
		return $rendererSettings['config/config-level.txt'];
	}

}

?>
