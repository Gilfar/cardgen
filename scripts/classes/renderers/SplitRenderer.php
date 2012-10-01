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
class SplitRenderer extends CardRenderer {
	public function render () {
		global $config;

		$card1 = clone $this->card;
		$card1->setDisplayTitle(substr($card1->getDisplayTitle(), 0, strpos($card1->getDisplayTitle(), '/')));
		if(strpos($card1->englishType, '/')) $card1->englishType = substr($card1->englishType, 0, strpos($card1->englishType, '/'));
		$card1->title = substr($card1->title, 0, strpos($card1->title, '/'));
		$card1->type = substr($card1->type, 0, strpos($card1->type, '/'));
		if (strpos($card1->color, '/') != FALSE) $card1->color = substr($card1->color, 0, strpos($card1->color, '/'));
		$card1->cost = substr($card1->cost, 0, strpos($card1->cost, '/'));
		$card1->legal = substr($card1->legal, 0, strpos($card1->legal, '-----'));
		$card1->flavor = substr($card1->flavor, 0, strpos($card1->flavor, '//'));
		$card1->pt = substr($card1->pt, 0, strpos($card1->pt, '//'));
		$card1->artFileName = substr($card1->artFileName, 0, strpos($card1->artFileName, '|'));
		if ($config['output.english.title.on.translated.card'])	$card1->artist = $card1->title;
		else if(strpos($card1->artist, '//')) $card1->artist = substr($card1->artist, 0, strpos($card1->artist, '//'));


		$card2 = clone $this->card;
		$card2->setDisplayTitle(substr($card2->getDisplayTitle(), strpos($card2->getDisplayTitle(), '/') + 1));
		if(strpos($card2->englishType, '/')) $card2->englishType = substr($card2->englishType, strpos($card2->englishType, '/') + 1);
		$card2->title = substr($card2->title, strpos($card2->title, '/') + 1);
		$card2->type = substr($card2->type, strpos($card2->type, '/') + 1);
		if (strpos($card2->color, '/') != FALSE) $card2->color = substr($card2->color, strpos($card2->color, '/') + 1);
		$card2->cost = substr($card2->cost, strpos($card2->cost, '/') + 1);
		$card2->legal = substr($card2->legal, strpos($card2->legal, '-----') + 5);
		$card2->flavor = substr($card2->flavor, strpos($card2->flavor, '//') + 2);
		$card2->pt = substr($card2->pt, strpos($card2->pt, '//') + 2);
		$card2->artFileName = substr($card2->artFileName, strpos($card2->artFileName, '|') + 1);
		if ($config['output.english.title.on.translated.card'])	$card2->artist = $card2->title;
		else if(strpos($card2->artist, '//')) $card2->artist = substr($card2->artist, strpos($card2->artist, '//') + 2);

		$r1 = $this->writer->getCardRenderer($card1);
		$image1 = $r1[0]->render();
		$image1tmp = imagerotate($image1, 90, 0);
		imagedestroy($image1);
		$image1 = $image1tmp;

		$r2 = $this->writer->getCardRenderer($card2);
		$image2 = $r2[0]->render();
		$image2tmp = imagerotate($image2, 90, 0);
		imagedestroy($image2);
		$image2 = $image2tmp;

		echo $this->card . '...';

		$canvas = imagecreatetruecolor(736, 1050);
		imagecopyresampled($canvas, $image1, 0, 1050 / 2, 0, 0, 736, 525, 1050, 736);
		imagecopyresampled($canvas, $image2, 0, 0, 0, 0, 736, 525, 1050, 736);
		imagedestroy($image1);
		imagedestroy($image2);

		echo "\n";
		return $canvas;
	}

	public function getSettings () {
		throw new Exception('SplitRenderer does not have settings.');
	}
}

?>
