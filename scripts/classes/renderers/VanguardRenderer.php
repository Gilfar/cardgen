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
class VanguardRenderer extends CardRenderer {
	static private $settingSections;

	public function render () {
		echo $this->card . '...';
		$card = $this->card;

		$settings = $this->getSettings();
		$isNewFrame = $settings['card.new.avatar.frame'];
		$canvas = imagecreatetruecolor(736, 1050);

		// Art image.
		$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right']);

		// Background.
		$bgImage = @imagecreatefrompng('images/vanguard/avatar-' . ($isNewFrame ? 'new' : 'old') . '.png');
		if (!$bgImage) error('Avatar background image not found.');
		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 736, 1050);
		imagedestroy($bgImage);

		// Title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $settings['title.width'], $card->getDisplayTitle(!$isNewFrame), $this->font('title'));

		// Hand and life.
		if (!preg_match('/Hand ([\+\-][0-9]+), Life ([\+\-][0-9]+)/', $card->legal, $matches)) error('Missing hand/life from legal text: ' . $card->title);
		$hand = $matches[1];
		if (substr($hand, 0, 1) == ' ') $hand = '+' . substr($hand, 1);
		$life = $matches[2];
		if (substr($life, 0, 1) == ' ') $life = '+' . substr($life, 1);
		$this->drawText($canvas, $settings['hand.x'], $settings['hand.y'], $settings['hand.width'], $hand, $this->font('hand.and.life'));
		$this->drawText($canvas, $settings['life.x'], $settings['life.y'], $settings['life.width'], $life, $this->font('hand.and.life'));

		// Legal and flavor.
		$legal = substr($card->legal, strpos($card->legal, "\n"));
		if ($isNewFrame)
			$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $legal, $card->flavor, $this->font('text'));
		else {
			// Old frame.
			if ($card->flavor) {
				$this->drawTextWrappedAndScaled($canvas, $settings['legal.top'], $settings['legal.left'], $settings['legal.bottom'], $settings['legal.right'], $legal, $this->font('text'));
				$this->drawTextWrappedAndScaled($canvas, $settings['flavor.top'], $settings['flavor.left'], $settings['flavor.bottom'], $settings['flavor.right'], '#' . $card->flavor . '#', $this->font('text'));
			} else
				$this->drawTextWrappedAndScaled($canvas, $settings['legal.top'], $settings['flavor.left'], $settings['flavor.bottom'], $settings['flavor.right'], $legal, $this->font('text'));
		}

		// Artist and copyright.
		if ($card->artist) $this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, 'Illus. ' . $card->artist, $this->font('artist'));
		if ($card->copyright) $this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright'));

		echo "...\n";
		return $canvas;
	}

	public function getSettings () {
		global $rendererSettings, $rendererSections;

		// Merge the settings with the new or old avatar frame settings.
		$settings = $rendererSettings['config/config-vanguard.txt'];
		$section = $rendererSections['config/config-vanguard.txt'];
		if ($settings['card.new.avatar.frame'])
			$settings = array_merge($settings, $section['layout - new'], $section['fonts - new']);
		else
			$settings = array_merge($settings, $section['layout - old'], $section['fonts - old']);

		return $settings;
	}
}

?>
