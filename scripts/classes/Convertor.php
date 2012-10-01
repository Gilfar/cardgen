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
class Convertor {
	private $tempFiles = array();
	private $mtgoIdToTitle;

	function __construct() {
		$this->mtgoIdToTitle = csvToArray('data/mtgoIdToTitle.csv');
	}

	function __destruct () {
		foreach ($this->tempFiles as $fileName)
			@unlink($fileName);
	}

	public function toCSV ($inputFileName) {
		$extension = strtolower(strrchr($inputFileName, '.'));
		if ($extension == '.csv' || $extension == '.temp') return $inputFileName;

		$name = $inputFileName;
		$name = strrchr($name, '/');
		$name = substr($name, 0, -strlen(strrchr($name, '.')));
		$name = substr($name, 1);

		$tempFileName = substr($inputFileName, 0, -strlen(strrchr($inputFileName, '/'))) . "/$name.temp";
		$this->tempFiles[] = $tempFileName;

		if ($extension == '.mwdeck') {
			echo "Converting: $name...\n";
			$this->mws2csv($inputFileName, $tempFileName);
			return $tempFileName;
		} else if ($extension == '.dec') {
			echo "Converting: $name...\n";
			$this->dec2csv($inputFileName, $tempFileName);
			return $tempFileName;
		}

		return $inputFileName;
	}

	private function mws2csv ($inputFileName, $outputFileName) {
		$inputFile = fopen_utf8($inputFileName, 'rb');
		if (!$inputFile) error("Unable to read MWS file: $inputFileName");

		$outputFile = fopen_utf8($outputFileName, 'w');
		if (!$outputFile) error("Unable to write CSV file: $outputFileName");

		while (!feof($inputFile)) {
			$line = fgets($inputFile, 6000);
			if (preg_match('/(SB: )?([0-9]+) \[([0-9A-Z]{1,3})\] (.+)/', $line, $regs) ) {
				$qty = $regs[2];
				$title = trim($regs[4]);
				$set = $regs[3];
				fputcsv($outputFile, array($qty, $title, $set));
			}
		}

		fclose($inputFile);
		fclose($outputFile);
	}

	private function dec2csv ($inputFileName, $outputFileName) {
		$inputFile = fopen_utf8($inputFileName, 'rb');
		if (!$inputFile) error("Unable to read DEC file: $inputFileName");

		$outputFile = fopen_utf8($outputFileName, 'w');
		if (!$outputFile) error("Unable to write CSV file: $outputFileName");

		$cards = array();

		// Read header.
		fread($inputFile, 8);
		$cardCount = $this->readInt($inputFile, 2);

		// Read deck cards.
		for ($i = 0; $i < $cardCount; $i++) {
			fread($inputFile, 2);
			$id = $this->readInt($inputFile, 4);
			fread($inputFile, 10);
			if (!@$this->mtgoIdToTitle[$id])
				echo "Unable to find card with ID: $id\n";
			else
				$cards[] = $this->mtgoIdToTitle[$id];
		}

		// Read sideboard header.
		fread($inputFile, 2);
		$cardCount = $this->readInt($inputFile, 1);
		fread($inputFile, 1);

		// Read sideboard cards.
		for ($i = 0; $i < $cardCount; $i++) {
			fread($inputFile, 2);
			$id = $this->readInt($inputFile, 4);
			fread($inputFile, 10);
			if (!@$this->mtgoIdToTitle[$id])
				echo "Unable to find card with ID: $id\n";
			else
				$cards[] = $this->mtgoIdToTitle[$id];
		}

		fclose($inputFile);

		// Write csv entries.
		for ($i = 0, $n = count($cards); $i < $n;) {
			$title = $cards[$i];
			$qty = 0;
			while ($cards[$i] == $title) {
				$qty++;
				$i++;
				if ($i >= $n) break;
			}
			$title = str_replace(' (premium)', '', $title);
			fputcsv($outputFile, array($qty, $title));
		}

		fclose($outputFile);
	}

	private function readInt ($file, $bytes) {
		$hex = bin2hex(fread($file, $bytes));
		$hex = strrev($hex);
		$flipped = '';
		for ($i = 0, $n = strlen($hex); $i < $n; $i += 2)
			$flipped .= substr($hex, $i + 1, 1) . substr($hex, $i, 1);
		return hexdec($flipped);
	}
}

?>
