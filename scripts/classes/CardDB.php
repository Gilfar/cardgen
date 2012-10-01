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
class CardDB {
	private $setDB;
	private $artDB;
	private $titleToCards = array();
	private $titleToFlavors = array();

	public function __construct (SetDB $setDB, ArtDB $artDB) {
		global $config;

		$this->setDB = $setDB;
		$this->artDB = $artDB;

		// Load english cards.
		echo 'Loading card data';
		$file = fopen_utf8('data/cards.csv', 'r');
		if (!$file) error('Unable to open file: data/cards.csv');
		$i = 0;
		while (($row = fgetcsv($file, 6000, ',')) !== FALSE) {
			if ($i++ % 400 == 0) echo '.';

			$card = CardDB::rowToCard($row);
			// Ignore cards with an unknown set.
			$card->set = $setDB->normalize($card->set);

			if (!$card->set) continue;

			$title = strtolower($card->title);
			if (!@$this->titleToCards[$title]) $this->titleToCards[$title] = array();
			$this->titleToCards[(string)$title][] = $card;
		}
		fclose($file);
		echo "\n";

		// Load foreign card data.
		$language = strtolower($config['output.language']);
		if ($language && $language != 'english') {
			echo "Loading $language card data";
			$file = fopen_utf8("data/cards-$language.csv", 'r');
			if (!$file) error("Unable to open file: data/cards-$language.csv");
			$i = 0;
			while (($row = fgetcsv($file, 6000, ',')) !== FALSE) {
				if ($i++ % 400 == 0) echo '.';
				// Overwrite some of the english card values with the foreign values.
				$englishTitle = strtolower($row[0]);

				$cards = @$this->titleToCards[(string)$englishTitle];
				if (!$cards){
					//print_r($row);
					echo "\nError matching card data for card: $row[0]";
					continue; // Skip errors
					}
				foreach ($cards as $card)
					CardDB::applyLanguageRowToCard($row, $card);
			}
			fclose($file);
			echo "\n";
			if (!$config['output.english.flavor.text']) {
				echo "Loading $language card flavor data";
				$file = fopen_utf8("data/cards-$language-flavor.csv", 'r');
				if (!$file)
					echo "\nNo localized flavor for language: $language";
				else {
					$i = 0;
					while (($row = fgetcsv($file, 6000, ',')) !== FALSE) {
						if ($i++ % 400 == 0) echo '.';
						// Overwrite some of the english card values with the foreign values.
						$englishTitle = strtolower($row[0]);
						$cards = @$this->titleToCards[(string)$englishTitle];
						if (!$cards){
							//print_r($row);
							echo "\nError matching card flavor for card: $row[0]";
							continue; // Skip errors.
							}
						// Find the card from needed edition and apply localized flavor.
						foreach ($cards as $card) {
							if ($card->set == $row[1]) {
								$card->flavor = $row[2];
								break;
							}
						}
					}
					fclose($file);
					echo "\n";
				}
			}
		}
	}

	public function getCard ($title, $set = null, $pic = null) {
		global $config;

		$cards = @$this->titleToCards[(string)strtolower($title)];
		if (!$cards) return null;
		if (!$set) {
			if ($config['card.set.random']) {
				// Pick a random set.
				$sets = array();
				foreach ($cards as $card)
					$sets[$card->set] = true;
				$set = array_rand($sets);
			} else {
				// Find earliest set.
				$lowest = 999999;
				foreach ($cards as $card) {
					$ordinal = $this->setDB->getOrdinal($card->set);
					if ($ordinal < $lowest) {
						$lowest = $ordinal;
						$set = $card->set;
					}
				}
			}
		} else
			$set = $this->setDB->normalize($set);

		$chosenCard = null;
		if ($pic) {
			// Find the specific picture number.
			foreach ($cards as $card) {
				if ($card->set != $set) continue;
				if ($card->pic == $pic) {
					$chosenCard = $card;
					break;
				}
			}
		} else {
			// Randomly pick a card in the set.
			$cardsInSet = array();
			foreach ($cards as $card) {
				if ($card->set != $set) continue;
				$cardsInSet[] = $card;
			}
			if (count($cardsInSet) > 0) $chosenCard = $cardsInSet[array_rand($cardsInSet)];
		}
		if (!$chosenCard) return null;

		// Card was found.
		return $this->configureCard($chosenCard);
	}

	public function getSets ($cardTitle) {
		$cards = @$this->titleToCards[(string)strtolower($cardTitle)];
		if (!$cards) return null;
		$sets = array();
		foreach ($cards as $card)
			$sets[$card->set] = true;
		return array_keys($sets);
	}

	public function getCards ($title) {
		$cards = @$this->titleToCards[(string)strtolower($title)];
		if (!$cards) return null;
		$configuredCards = array();
		foreach ($cards as $card)
			$configuredCards[] = $this->configureCard($card);
		return $configuredCards;
	}

	public function configureCard ($card) {
		global $config;

		$card = clone $card;

		if ($config["card.flavor.random"]) {
			// Pick a flavor text that hasn't been picked yet.
			$flavors = @$this->titleToFlavors[(string)$card->title];
			if (!$flavors || count($flavors) == 0) {
				$cardWithSameTitle = @$this->titleToCards[(string)strtolower($card->title)];
				foreach ($cardWithSameTitle as $cardWithSameTitle)
					if ($cardWithSameTitle->flavor) $flavors[] = $cardWithSameTitle->flavor;
			}
			if (count($flavors) > 0) {
				$index = array_rand($flavors);
				$card->flavor = $flavors[$index];
				array_splice($flavors, $index, 1);
			}
		}

		// Find art image.
		$card->artFileName = $this->artDB->getArtFileName($card->title, $card->set, $card->pic);

		// Artist and copyright.
		if ($config['card.artist.and.copyright'])
			$card->copyright = $config['card.copyright'] . ' ' . $card->collectorNumber;
		else {
			$card->artist = null;
			$card->copyright = null;
		}

		if (!$config['card.reminder.text']) $card->legal = preg_replace('/#\\(.*?\\)#/', '', $card->legal);

		return $card;
	}

	public function getAllCardTitles () {
		$titles = array();
		foreach ($this->titleToCards as $title => $cards)
			$titles[$title] = true;
		return array_keys($titles);
	}

	static public function rowToCard ($row) {
		$card = new Card();
		$card->title = (string)$row[0];
		$card->set = (string)$row[1];
		$card->color = (string)$row[2];
		$card->type = (string)$row[3];
		$card->englishType = $card->type;
		$card->pt = (string)str_replace('\\', '/', $row[4]);
		$card->flavor = (string)$row[5];
		$card->rarity = (string)$row[6];
		$card->cost = (string)$row[7];
		$card->legal = (string)str_replace("\r\n", "\n", $row[8]);
		$card->pic = (string)@$row[9];
		$card->artist = (string)@$row[10];
		$card->collectorNumber = (string)str_replace('\\', '/', @$row[11]);
		return $card;
	}

	static public function cardToRow ($card) {
		$row = array();
		$row[0] = (string)$card->title;
		$row[1] = (string)$card->set;
		$row[2] = (string)$card->color;
		$row[3] = (string)$card->type;
		$row[4] = (string)str_replace('/', '\\', $card->pt);
		$row[5] = (string)$card->flavor;
		$row[6] = (string)$card->rarity;
		$row[7] = (string)$card->cost;
		$row[8] = (string)$card->legal;
		$row[9] = (string)$card->pic;
		$row[10] = (string)$card->artist;
		$row[11] = (string)str_replace('/', '\\', $card->collectorNumber);
		return $row;
	}

	static public function applyLanguageRowToCard ($row, $card) {
		global $config;
		if($row[1]){
			$cardName = preg_replace("/ ?\(\d\)/", "", $row[1]);
			$card->setDisplayTitle($cardName);
		}
		else
			$card->setDisplayTitle($row[0]);
		$card->type = $row[2];
		if(@$row[3])
			$card->legal = str_replace("\r\n", "\n", $row[3]);
		else
			$card->legal = "";
		if (!$config['output.english.flavor.text']) $card->flavor = @$row[4];
		if ($config['output.english.title.on.translated.card']) $card->artist = $card->title;
	}

	static public function cardToLanguageRow ($card) {
		$row = array();
		$row[0] = $card->title;
		$row[1] = $card->getDisplayTitle();
		$row[2] = $card->type;
		$row[3] = $card->legal;
		$row[4] = $card->flavor;
		return $row;
	}

	public function resetUsedFlavors () {
		$this->titleToFlavors = array();
	}
}

?>
