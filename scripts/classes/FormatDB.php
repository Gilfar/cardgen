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
class FormatDB {
	private $cardDB;
	private $formats = array();
	private $formatToBanned = array();
	private $formatToRestricted = array();
	private $formatToSets = array();

	public function __construct (SetDB $setDB, CardDB $cardDB) {
		$this->cardDB = $cardDB;

		$file = fopen_utf8('data/formats.txt', 'r');
		while (!feof($file)) {
			$format = trim(fgets($file, 6000));
			if (!$format) continue;

			$this->formats[] = $format;
			$this->formatToBanned[$format] = array();
			$this->formatToRestricted[$format] = array();
			$this->formatToSets[$format] = array();

			$state = null;
			while (!feof($file)) {
				$line = trim(fgets($file, 6000));
				if (!$line) break;
				if ($line == 'BANNED' || $line == 'RESTRICTED' || $line == 'SETS') {
					$state = $line;
					continue;
				}
				$line = strtolower($line);
				switch ($state) {
				case 'BANNED':
					$this->formatToBanned[$format][] = $line;
					break;
				case 'RESTRICTED':
					$this->formatToRestricted[$format][] = $line;
					break;
				case 'SETS':
					$set = $setDB->normalize($line);
					if (!$set) error('Error parsing "data/formats.txt". Unknown set: ' . $set);
					$this->formatToSets[$format][] = $set;
					break;
				default:
					error('Error parsing "data/formats.txt". Invalid section: ' . $line);
				}
			}
		}
		fclose($file);
	}

	public function getLegality ($cards, $debug) {
		global $config;

		$legality = array();
		$maindeckLegality = array();
		$illegalSideboardCards = array();
		foreach ($this->formats as $format) {
			if ($debug) echo "\nFormat: $format\n";

			$legality[$format] = false;
			$maindeckLegality[$format] = false;

			if (count($cards) < 60) {
				if ($debug) {
					echo "Less than 60 cards.\n";
					echo "$format legal: false\n";
				}
				continue;
			}

			$legality[$format] = true;
			$cardCount = array();
			$i = 0;
			foreach ($cards as $card) {
				// If we have moved from main deck to sideboard, record main deck legality.
				if (count($cards) >= 75 && $i == count($cards) - 15) $maindeckLegality[$format] = $legality[$format];
				$i++;

				$cardLegal = true;

				if ($card->isBasicLand()) continue;

				// Check card count.
				$title = strtolower($card->title);
				if (!@$cardCount[$title]) $cardCount[$title] = 0;
				$cardCount[$title]++;
				if ($cardCount[$title] > 4 && strpos($card->legal, 'deck can have any number of cards') === false) {
					$cardLegal = false;
					if ($debug) echo 'More than four: ' . $card->title . "\n";
				}

				// Check that the card was printed in a legal edition.
				$found = false;
				foreach ($this->cardDB->getSets($title) as $set) {
					if (in_array($set, $this->formatToSets[$format])) {
						$found = true;
						break;
					}
				}
				if (!$found) {
					$cardLegal = false;
					if ($debug) echo 'Not in ' . $format . ' sets: ' . $card->title . "\n";
				}

				// Check card is not banned or restricted.
				if (in_array($title, $this->formatToBanned[$format])) {
					$cardLegal = false;
					if ($debug) echo 'Banned in ' . $format . ': ' . $card->title . "\n";
				}
				if (in_array($title, $this->formatToRestricted[$format])) {
					if ($cardCount[$title] > 1) {
						$cardLegal = false;
						if ($debug) echo 'Restricted in ' . $format . ': ' . $card->title . "\n";
					}
				}

				if ($maindeckLegality[$format] && !$cardLegal) $illegalSideboardCards[] = $title;

				if (!$cardLegal) $legality[$format] = false;
			}
			if ($debug) echo "$format legal: " . ($legality[$format] ? "true" : "false") . "\n";
		}
		return array($legality, $maindeckLegality, $illegalSideboardCards);
	}
}

?>
