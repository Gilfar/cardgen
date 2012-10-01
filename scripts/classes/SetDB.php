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
class SetDB {
	private $setToMainSet = array();
	private $mainSetToOrdinal = array();
	private $pre8thSets = array();

	public function __construct () {
		$file = fopen_utf8('data/sets.txt', 'r');
		$i = 1000;
		while (!feof($file)) {
			$line = trim(fgets($file, 6000));
			$spaceIndex = strpos($line, ' ');
			if ($spaceIndex === false) continue;

			$name = trim(substr($line, $spaceIndex + 1));
			if ($i > 1000 && $name == 'Alpha') $i = 1;  // Everything before alpha starts at 1000.

			$abbreviations = explode(',', substr($line, 0, $spaceIndex));
			$mainSet = "";
			foreach($abbreviations as $set)
				if (strlen($mainSet) < strlen($set) &&  strlen($set)<=3) $mainSet = $set;
			$mainSet = strtoupper($mainSet);

			$this->setToMainSet[(string)strtoupper($name)] = $mainSet;
			foreach ($abbreviations as $abbreviation)
				$this->setToMainSet[(string)strtoupper($abbreviation)] = $mainSet;

			$this->mainSetToOrdinal[(string)$mainSet] = $i;

			$i++;
		}
		fclose($file);

		$file = fopen_utf8('data/sets-pre8th.txt', 'r');
		while (!feof($file)) {
			$line = trim(fgets($file, 6000));
			if (!$line) continue;
			$set = $this->normalize($line);
			if (!$set) error('Error parsing "data/sets-pre8th.txt". Unknown set: ' . $line);
			$this->pre8thSets[(string)$set] = true;
		}
	}

	public function normalize ($set) {
		return @$this->setToMainSet[(string)strtoupper($set)];
	}

	public function getAbbrevs ($set) {
		return array_keys($this->setToMainSet, $set);
	}

	public function getOrdinal ($set) {
		return $this->mainSetToOrdinal[(string)$this->normalize($set)];
	}

	public function isPre8th ($set) {
		return @$this->pre8thSets[(string)$this->normalize($set)];
	}
}

?>
