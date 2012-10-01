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
class Card {
	static private $combos;

	// Persisted properties.
	public $title;
	public $color;
	public $type;
	public $englishType;
	public $pt;
	public $flavor;
	public $rarity;
	public $cost;
	public $legal;
	public $set;
	public $pic;
	public $promo;
	public $artist;
	public $collectorNumber;

	// Computed properties.
	public $artFileName;
	public $copyright;

	private $displayTitle;

	public function isBasicLand () {
		$title = strtolower($this->title);
		$title = str_replace('snow-covered ', '', $title);
		return $title == 'swamp' || $title == 'plains' || $title == 'island' || $title == 'mountain' || $title == 'forest';
	}

	public function isLand () {
		return strtolower($this->color) == 'lnd';
	}

	public function isArtefact () {
		return (strtolower($this->color) == 'art' || stripos($this->englishType, 'Artifact') !== false);
	}

	public function isEldrazi () {
		return (stripos($this->englishType, 'Eldrazi') !== false);
	}

	public function getCostSymbols () {
		$cost = $this->cost;

		$position = strpos($cost, '{|}');
		if ($position) $cost = substr($cost, $position + 1);

		$symbols = array();
		$position = 0;
		while (true) {
			$position = strpos($cost, '{', $position);
			if ($position === FALSE) break;
			$symbol = substr($cost, $position + 1, strpos($cost, '}', $position) - $position - 1);
			$position += 2 + strlen($symbol);
			$symbols[] = $symbol;
		}
		return $symbols;
	}

	public function isDualManaCost () {
		return preg_match('/\{([GRUBW][GRUBW])\}/', $this->cost, $matches);
	}

	public function setDisplayTitle ($title) {
		$this->displayTitle = $title;
	}

	public function getDisplayTitle ($useExtendedCharacters = true) {
		$title = $this->displayTitle;
		if (!$title) $title = $this->title;
		$title = str_replace('Avatar: ', '', $title);
		$title = str_replace('AE', 'Æ', $title);
		//card specific
		$title = str_replace('El-Hajjaj', 'El-Hajjâj', $title);
		$title = str_replace('Junun', 'Junún', $title);
		$title = str_replace('Lim-Dul', 'Lim-Dûl', $title);
		$title = str_replace('Jotun', 'Jötun', $title);
		$title = str_replace('Ghazban', 'Ghazbán', $title);
		$title = str_replace('Ifh-Biff', 'Ifh-Bíff', $title);
		$title = str_replace('Juzam', 'Juzám', $title);
		$title = str_replace('Khabal', 'Khabál', $title);
		$title = str_replace('Marton', 'Márton', $title);
		$title = str_replace("Ma'ruf", "Ma'rûf", $title);
		$title = str_replace('Deja Vu', 'Déjà Vu', $title);
		$title = str_replace('Dandan', 'Dandân', $title);
		$title = str_replace('Bosium', 'Bösium', $title);
		$title = str_replace('Seance', 'Séance', $title);

		if ($useExtendedCharacters) {
			// These characters are not stored for card titles because they are hard to type.
			// Convert them for display, but only if the font for the card title has the glyphs (eg, the pre8th font does not).
			$title = str_replace("'", '’', $title);
			$title = str_replace(' en-', ' #en#-', $title);
			$title = str_replace(' il-', ' #il#-', $title);
		}
		return $title;
	}

	public function __toString () {
		$name = $this->title . ' [' . $this->set;
		if ($this->pic) $name .= ', ' . $this->pic;
		$name .= ']';
		return $name;
	}

	// Returns the ordered colors of a mana cost.
	static function getCostColors ($cost) {
		if (!Card::$combos) {
			Card::$combos = array();
			Card::$combos[] = array('B', 'G');
			Card::$combos[] = array('B', 'R');
			Card::$combos[] = array('G', 'U');
			Card::$combos[] = array('G', 'W');
			Card::$combos[] = array('R', 'G');
			Card::$combos[] = array('R', 'W');
			Card::$combos[] = array('U', 'B');
			Card::$combos[] = array('U', 'R');
			Card::$combos[] = array('W', 'B');
			Card::$combos[] = array('W', 'U');
			Card::$combos[] = array('W', 'U', 'B');
			Card::$combos[] = array('W', 'B', 'R');
			Card::$combos[] = array('U', 'R', 'G');
			Card::$combos[] = array('U', 'B', 'R');
			Card::$combos[] = array('R', 'W', 'U');
			Card::$combos[] = array('R', 'G', 'W');
			Card::$combos[] = array('B', 'G', 'W');
			Card::$combos[] = array('B', 'R', 'G');
			Card::$combos[] = array('G', 'U', 'B');
			Card::$combos[] = array('G', 'W', 'U');
			Card::$combos[] = array('B', 'R', 'G', 'W');
			Card::$combos[] = array('G', 'W', 'U', 'B');
			Card::$combos[] = array('R', 'G', 'W', 'U');
			Card::$combos[] = array('U', 'B', 'R', 'G');
			Card::$combos[] = array('W', 'U', 'B', 'R');
			Card::$combos[] = array('W', 'U', 'B', 'R', 'G');
		}

		// A pipe in the cost means "process left of the pipe as the card color, but display right of the pipe as the cost".
		$position = strpos($cost, '{|}');
		if ($position) $cost = substr($cost, 0, $position);

		// Collect all the colors.
		$colors = array();
		$cost = preg_replace('/[0-9XYZP]/', '', $cost);
		$cost = str_replace('{', '', $cost);
		$cost = str_replace('}', '', $cost);
		for ($i = 0, $n = strlen($cost); $i < $n; $i++) {
			$char = strtoupper(substr($cost, $i, 1));
			if (in_array($char, $colors)) continue;
			$colors[] = $char;
		}

		// Find the matching combo.
		$colorsLength = count($colors);
		foreach (Card::$combos as $combo) {
			if (count($combo) != $colorsLength) continue;
			if (!array_diff($combo, $colors)) {
				$colors = $combo;
				break;
			}
		}

		return implode('', $colors);
	}
}

?>
