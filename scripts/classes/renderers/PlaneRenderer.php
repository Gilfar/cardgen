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
class PlaneRenderer extends CardRenderer {
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

		$canvas = imagecreatetruecolor(1050, 736);

		// Art image.
		$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);

		echo '.';

		// Background image.
		$bgImage = @imagecreatefrompng('images/plane/plane.png');
		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 1050, 736);
		imagedestroy($bgImage);

		echo '.';

		// Set and rarity.
		$rarityLeft = $this->drawRarity($canvas, $card->rarity, $card->set, $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);

		// Card title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], null, $card->getDisplayTitle(), $this->font('title'));

		echo '.';

		// Type.
		$this->drawText($canvas, $settings['type.x'], $settings['type.y'], null, $card->type, $this->font('type'));

		//Chaos Symbol
		$this->drawSymbol($canvas, 632, 115, 50, 'C', null);

		// Legal text.
		if (!preg_match('/(.*\n)(.*?)$/s', $card->legal, $matches)) error('Missing chaos ability from legal text: ' . $card->title);
		$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $matches[1], null, $this->font('text'), 0);
		$this->drawLegalAndFlavorText($canvas, $settings['chaos.top'], $settings['chaos.left'], $settings['chaos.bottom'], $settings['chaos.right'], $matches[2], null, $this->font('text'), 0);

		// Artist and copyright.
		// The artist color is white if the frame behind it is black.
		$footerColor = '255,255,255';
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

		public function getSettings() {
		global $rendererSettings;

		return $rendererSettings['config/config-plane.txt'];
	}
}

?>
