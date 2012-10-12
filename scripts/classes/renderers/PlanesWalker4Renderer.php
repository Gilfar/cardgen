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
class PlanesWalker4Renderer extends CardRenderer {
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
			$bgImage = @imagecreatefrompng('images/planeswalker/regular/cards/Art4.png');
		} else if ($useMulticolorFrame || $card->isDualManaCost()) {
			// Multicolor frame.
			if($settings['card.multicolor.gold.frame'])
				$bgImage = @imagecreatefrompng("images/planeswalker/regular/cards/Gld$costColors4.png");
			else
				$bgImage = @imagecreatefrompng("images/planeswalker/regular/cards/$costColors4.png");
			if (!$bgImage) error("Background image not found for color: $costColors");
		} else {
			// Mono color frame.
			$bgImage = @imagecreatefrompng('images/planeswalker/regular/cards/' . $card->color . '4.png');
			if (!$bgImage) error('Background image not found for color "' . $card->color . '"');
		}

		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 736, 1050);
		imagedestroy($bgImage);

		// Loyalty
		if ($card->pt) {
			$image = @imagecreatefrompng('images/planeswalker/loyalty/LoyaltyBegin.png');
			if (!$image) error("Loyalty image not found");
			imagecopy($canvas, $image, 605, 940, 0, 0, 127, 82);
			imagedestroy($image);
			$card->pt = (string)str_replace('/', '', $card->pt);
			$this->drawText($canvas, $settings['loyalty.starting.center.x'], $settings['loyalty.starting.center.y'], $settings['loyalty.starting.width'], $card->pt, $this->font('loyalty.starting', 'color:'.$white));
		}

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
		if (!preg_match_all('/(\+|-)?([0-9XYZ]+): (.*?)(?=$|[-|\+]?[0-9XYZ]+:)/s', $card->legal, $matches)) error('Missing legality change from legal text: ' . $card->title);
		//print_r($matches);
		$logaltyImage = null;
		$loyalty = array();

		//1
		$this->loyaltyIcon($matches[1][0], 1, $logaltyImage, $loyalty);
		imagecopy($canvas, $logaltyImage, $loyalty['x'], $loyalty['y'], 0, 0, $loyalty['w'], $loyalty['h']);
		imagedestroy($logaltyImage);

		$this->drawText($canvas, $settings['loyalty.1.center.x'], $settings['loyalty.1.center.y'], $settings['loyalty.1.center.width'], preg_replace('/([+|-])/', '{\\1}', $matches[1][0]).$matches[2][0], $this->font('loyalty.change', 'color:'.$white));
		$this->drawLegalAndFlavorText($canvas, $settings['text.1.top'], $settings['text.1.left'], $settings['text.1.bottom'], $settings['text.1.right'], $matches[3][0], null, $this->font('text'), 0);

		//2
		$this->loyaltyIcon($matches[1][1], 2,  $logaltyImage, $loyalty);
		imagecopy($canvas, $logaltyImage, $loyalty['x'], $loyalty['y'], 0, 0, $loyalty['w'], $loyalty['h']);
		imagedestroy($logaltyImage);

		$this->drawText($canvas, $settings['loyalty.2.center.x'], $settings['loyalty.2.center.y'], $settings['loyalty.2.center.width'], preg_replace('/([+|-])/', '{\\1}', $matches[1][1]).$matches[2][1], $this->font('loyalty.change', 'color:'.$white));
		$this->drawLegalAndFlavorText($canvas, $settings['text.2.top'], $settings['text.2.left'], $settings['text.2.bottom'], $settings['text.2.right'], $matches[3][1], null, $this->font('text'), 0);

		//3
		$this->loyaltyIcon($matches[1][2], 3,  $logaltyImage, $loyalty);
		imagecopy($canvas, $logaltyImage, $loyalty['x'], $loyalty['y'], 0, 0, $loyalty['w'], $loyalty['h']);
		imagedestroy($logaltyImage);

		$this->drawText($canvas, $settings['loyalty.3.center.x'], $settings['loyalty.3.center.y'], $settings['loyalty.3.center.width'], preg_replace('/([+|-])/', '{\\1}', $matches[1][2]).$matches[2][2], $this->font('loyalty.change', 'color:'.$white));
		$this->drawLegalAndFlavorText($canvas, $settings['text.3.top'], $settings['text.3.left'], $settings['text.3.bottom'], $settings['text.3.right'], $matches[3][2], null, $this->font('text'), 10);

		//4
		$this->loyaltyIcon($matches[1][3], 4,  $logaltyImage, $loyalty);
		imagecopy($canvas, $logaltyImage, $loyalty['x'], $loyalty['y'], 0, 0, $loyalty['w'], $loyalty['h']);
		imagedestroy($logaltyImage);

		$this->drawText($canvas, $settings['loyalty.4.center.x'], $settings['loyalty.4.center.y'], $settings['loyalty.4.center.width'], preg_replace('/([+|-])/', '{\\1}', $matches[1][3]).$matches[2][3], $this->font('loyalty.change', 'color:'.$white));
		$this->drawLegalAndFlavorText($canvas, $settings['text.4.top'], $settings['text.4.left'], $settings['text.4.bottom'], $settings['text.4.right'], $matches[3][3], null, $this->font('text'), 10);

		// Artist and copyright.
		// The artist color is white if the frame behind it is black.
		$footerColor = '255,255,255';
		if ($card->artist) {
			if ($settings['card.artist.gears']) {
				$artistSymbol = '{gear}';
			} else {
				$artistSymbol = '{brush}';
			}
			$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], $settings['artist.width'], $artistSymbol . $card->artist, $this->font('artist', 'color:' . $footerColor));
		}
		if ($card->copyright) $this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));

		//Art overlay
		$overlayFileName = preg_replace('/(.*)\.(jpg|png)/i','$1-overlay.png', $card->artFileName);
		if(file_exists($overlayFileName))
			$this->drawArt($canvas, $overlayFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);

		echo "\n";
		return $canvas;
	}

	private function loyaltyIcon($sign, $slotNumber, &$logaltyImage, &$loyalty){
		if ($sign == '+'){
			$logaltyImage = @imagecreatefrompng('images/planeswalker/loyalty/LoyaltyUp.png');
			switch($slotNumber){
				case 1:
					$loyalty['y'] = 675;
					break;
				case 2:
					$loyalty['y'] = 745;
					break;
				case 3:
					$loyalty['y'] = 824;
					break;
				case 4:
					$loyalty['y'] = 900;
					break;
			}
			$loyalty['x'] = 13;
			$loyalty['w'] = 91;
			$loyalty['h'] = 76;
		} else if ($sign == '-'){
			$logaltyImage = @imagecreatefrompng('images/planeswalker/loyalty/LoyaltyDown.png');
			switch($slotNumber){
				case 1:
					$loyalty['y'] = 675;
					break;
				case 2:
					$loyalty['y'] = 745;
					break;
				case 3:
					$loyalty['y'] = 824;
					break;
				case 4:
					$loyalty['y'] = 900;
					break;
			}
			$loyalty['x'] = 13;
			$loyalty['w'] = 91;
			$loyalty['h'] = 75;
		} else {
			$logaltyImage = @imagecreatefrompng('images/planeswalker/loyalty/LoyaltyZero.png');
			switch($slotNumber){
				case 1:
					$loyalty['y'] = 675;
					break;
				case 2:
					$loyalty['y'] = 745;
					break;
				case 3:
					$loyalty['y'] = 824;
					break;
				case 4:
					$loyalty['y'] = 900;
					break;
			}
			$loyalty['x'] = 13;
			$loyalty['w'] = 85;
			$loyalty['h'] = 70;
		}

	}

	public function getSettings () {
		global $rendererSettings;
		return $rendererSettings['config/config-planeswalker4.txt'];
	}

}

?>
