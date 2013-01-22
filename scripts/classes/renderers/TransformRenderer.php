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
class TransformRenderer extends CardRenderer {
	public $version;
	static private $titleToTransform;

	public function __construct (SetDB $setDB, ArtDB $artDB, $version) {
		$this->setDB = $setDB;
		$this->artDB = $artDB;
		$this->version = $version;
	}

	public function render () {
		global $config;

		$card = $this->card;

		$settings = $this->getSettings();

		echo $card . ' ' . $this-> version . '...';
		echo "\n";

		$cards = explode("\n-----\n", $card->legal);
		$flavor1 = trim(substr($card->flavor, 0, strpos($card->flavor, "-----")));
		$flavor2 = trim(substr($card->flavor, strpos($card->flavor, "-----") + 6));

		$pts = explode("|", $card->pt);

		$card1 = clone $this->card;
		$card1->legal = $cards[0];
		$card1->flavor = $flavor1;

		if (preg_match("/(.*?)\n(.*?)\n(.*?)\n(.*)/s", $cards[1], $matches) && !$card->isPlaneswalker()) {
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

		if($pt2 != "")
			$card1->pt .= '|'.$pt2;

		$title2 = str_replace('AE', 'A', $title2);
		$title2 = str_replace("'", '’', $title2);

		$card2 = clone $this->card;
		$card2->setDisplayTitle($title2);
		$card2->title = $title2;
		$card2->type = $type2;
		$card2->englishType = $type2;
		$card2->pt = $pt2;
		$card2->legal = $legal2;
		$card2->flavor = $flavor2;
		$card2->cost = "";
		$card2->artFileName = $this->artDB->getArtFileName($card2->title, $card2->set, $card2->pic);
		if(TransformRenderer::$titleToTransform[(string)strtolower($card1->title)][1] != '')
			$card1->color = TransformRenderer::$titleToTransform[(string)strtolower($card1->title)][1];
		if(TransformRenderer::$titleToTransform[(string)strtolower($card2->title)][1] != '')
			$card2->color = TransformRenderer::$titleToTransform[(string)strtolower($card2->title)][1];
		if($this->version == "day") {
			$r = $this->writer->getCardRenderer($card1);
		} else
			$r = $this->writer->getCardRenderer($card2);
		$canvas = $r[0]->render();

		return $canvas;
	}

	public function getCardName() {
		if($this->version == "day")
			return $this->card->title;
		else {
			$cards = explode("\n-----\n", $this->card->legal);
			preg_match("/(.*?)\n(.*?)\n(.*)/s", $cards[1], $matches);
			return $matches[1];
		}
	}

	public function getSettings () {
		global $rendererSettings;
		if (!TransformRenderer::$titleToTransform) TransformRenderer::$titleToTransform = $this->csvToArray('data/eighth/titleToTransform.csv');
		return 0;
	}

	private function csvToArray ($fileName) {
		$array = array();
		$file = fopen_utf8($fileName, 'r');
		if (!$file) error('Unable to open file: ' . $fileName);
		while (($data = fgetcsv($file, 6000, ',')) !== FALSE)
			$array[(string)strtolower($data[0])] = array(trim($data[1]), isset($data[2]) ? trim($data[2]) : "");
		fclose($file);
		return $array;
	}
}

?>
