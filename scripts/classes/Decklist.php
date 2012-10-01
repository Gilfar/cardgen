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
class Decklist {
	public $name;
	public $cards = array();

	public function __construct ($setDB, $cardDB, $convertor, $inputFileName, $quiet = false) {
		global $config;

		$this->name = getNameFromPath($inputFileName);

		if (!$quiet) echo 'Parsing decklist: ' . $this->name . '...';

		$fileName = $convertor->toCSV($inputFileName);
		$file = fopen_utf8($fileName, 'r');
		if (!$file) error("Error opening decklist file: $inputFileName");

		$hasError = false;
		$lineNumber = 0;
		$delimiter = trim($config['decklist.delimiter']);
		$cards = array();
		while (($row = fgetcsv($file, 6000, $delimiter)) !== FALSE) {
			$lineNumber++;
			if (count($row) == 0 || (count($row) == 1 && $row[0] == '') || (count($row) == 2 && $row[0] == '')) continue;

			$qty = trim($row[0]);
			$name = trim(@$row[1]);
			$set = $config['decklist.ignore.sets'] ? null : trim(@$row[2]);
			$promo = trim(@$row[3]);

			if (strtolower($qty) == 'qty' && strtolower($name) == 'card name') continue;
			if (!is_numeric($qty)) {
				echo("\nLine $lineNumber: Invalid quantity: $qty");
				$hasError = true;
				continue;
			}
			if (!$name) {
				echo("\nLine $lineNumber: Missing card name.");
				$hasError = true;
				continue;
			}

			$pic = null;
			if (!$config['decklist.ignore.sets'] && !$config['decklist.ignore.picture.numbers'] && preg_match('/ \(([1-9])\)/', $name, $matches))
				$pic = $matches[1];

			$name = preg_replace('/ \(([1-9])\)/', '', $name);
			//$name = str_replace('Æ, 'AE', $name);

			for ($i = 0; $i < $qty; $i++) {
				$card = $cardDB->getCard($name, $set, $pic);
				if (!$card) {
					echo("\nLine $lineNumber: Card not found: $name");
					if ($set || $pic) {
						echo ' [';
						if ($set) {
							echo $set;
							if ($pic) echo ', ';
						}
						if ($pic) echo $pic;
						echo ']';
					}
					$hasError = true;
					continue;
				}

				// If there are any errors, don't actually process any more cards beyond looking them up by title.
				if ($hasError) continue;

				$card->promo = $promo;
				if ($promo) {
					$card->rarity = 'R';
					$card->set = $promo;
				}

				$this->cards[] = $card;
			}

			if (!$hasError && count($this->cards) % 300 == 0 && !$quiet) echo '.';
		}
		if (!$quiet) echo "\n";
		if ($hasError) error('Unable to parse decklist: ' . $inputFileName);
	}
}

?>
