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
require_once 'scripts/includes/global.php';

echo "Card Generator v$version - Create Card\n\n";

configPrompt(false);
cleanOutputDir(false);

$writer = new ImageWriter();
$writer->setOutputType(false, false);

echo "Collecting card data...\n";
echo "Card type (R=Regular, L=Land, V=Vanguard, S=Split, F=Flip, FL=Flip Land):\n";
$cardType = strtolower(trim(fgets(STDIN)));
if ($cardType != 'r' && $cardType != 'l' && $cardType != 'v' && $cardType != 's' && $cardType != 'f' && $cardType != 'fl') error('Invalid card type.');
if ($cardType == 's') {
	$card = promptCardInfo($cardType, false);
	$card2 = promptCardInfo($cardType, true);
	$card->title .= '/' . $card2->title;
	$card->color .= '/' . $card2->color;
	$card->cost .= '/' . $card2->cost;
	$card->type .= '/' . $card2->type;
	$card->legal .= ' // ' . $card2->legal;
	$card->flavor .= ' // ' . $card2->flavor;
	$card->pt .= '//' . $card2->pt;
	$card->set = $card2->set;
	$card->rarity = $card2->rarity;
} else if ($cardType == 'f' || $cardType == 'fl') {
	$card = promptCardInfo($cardType, false);
	$card2 = promptCardInfo($cardType, true);
	$card->legal .= "\n-----\n" .
		$card2->title . "\n" .
		$card2->type . "\n" .
		$card2->pt . "\n" .
		$card2->legal;
} else
	$card = promptCardInfo($cardType);

$writer->addCard($card);
echo "Generating image...\n";
$writer->writeCards();
echo "Image generation complete.\n";

function promptCardInfo ($cardType, $isLastCard = true) {
	global $writer;

	$card = new Card();

	echo "Title:\n";
	$card->title = upperCaseWords(trim(fgets(STDIN)));
	if (!$card->title) error('Invalid title.');

	if ($cardType != 'v' && $cardType != 'l' && $cardType != 'fl' && ($cardType != 'f' || !$isLastCard)) {
		echo "Casting cost (ex: 4R, {4}{R}, 1RWU, {10}{WU}):\n";
		$cost = trim(fgets(STDIN));
		if (strpos($cost, '{') === false) {
			$newCost = '';
			for ($i = 0, $n = strlen($cost); $i < $n; $i++)
				$newCost .= '{' . $cost[$i] . '}';
			$cost = $newCost;
		}
		$card->cost = $cost;

		$colors = Card::getCostColors($cost);
		$colorCount = strlen($colors);
		if ($colorCount == 1)
			$card->color = $colors;
		else if ($colorCount > 1)
			$card->color = 'Gld';
		else if ($card->cost)
			$card->color = 'Art';
	}

	if ($cardType == 'v') {
		$card->color = 'Art';
		$card->set = 'VG';
	}

	if ($cardType == 'l' || ($cardType == 'fl' && !$isLastCard)) {
		$card->color = 'Lnd';
		echo "Mana color(s) land can produce (ex: W, R, C, A, RG, RGW, WUBRG):\n";
		$writer->titleToLandColors[strtolower($card->title)] = Card::getCostColors(trim(fgets(STDIN)));
		if (!$writer->titleToLandColors[strtolower($card->title)]) error('Invalid land mana color(s).');
	}

	if (($cardType != 'f' && $cardType != 'fl') || !$isLastCard) {
		echo "Art (URL or local file path):\n";
		$card->artFileName = trim(fgets(STDIN));
	}

	if ($cardType != 'v') {
		echo "Type (ex: Creature - Human):\n";
		$card->type = str_replace('-', '&#8211;', upperCaseWords(trim(fgets(STDIN))));

		if ($cardType != 'l' && $cardType != 'fl') {
			echo "Power/toughness (ex: 3/4):\n";
			$card->pt = trim(fgets(STDIN));
		}
	}

	echo "Legal text (ex: {T}: Add {G} to your mana pool. #(This is italic.)#):\n";
	$text = '';
	while (true) {
		$input = trim(fgets(STDIN));
		if ($input == '') break;
		if ($text) $text .= "\n";
		$text .= $input;
	}
	$text = str_replace('-', '&#8211;', $text);
	$card->legal = $text;

	if ($cardType != 'f' && $cardType != 'fl') {
		echo "Flavor text:\n";
		$text = '';
		while (true) {
			$input = trim(fgets(STDIN));
			if ($input == '') break;
			if ($text) $text .= "\n";
			$text .= $input;
		}
		$card->flavor = str_replace('-', '&#8212;', $text);
	}

	if ($cardType == 'v') {
		echo "Starting & max hand size (ex: +1, -3, +10):\n";
		$hand = trim(fgets(STDIN));
		if (substr($hand, 0, 1) != '+' && substr($hand, 0, 1) != '-') error('Invalid starting & max hand size.');

		echo "Starting life (ex: +5, -12, +0):\n";
		$life = trim(fgets(STDIN));
		if (substr($life, 0, 1) != '+' && substr($life, 0, 1) != '-') error('Invalid starting life.');

		$card->legal = "Hand $hand, Life $life\n" . $card->legal;
	}

	if (($cardType != 'f' && $cardType != 'fl') || !$isLastCard) {
		echo "Artist:\n";
		$card->artist = trim(fgets(STDIN));

		echo "Copyright (ex: (tm) & (c) 2006 Lizards on a Post, Inc. 42/175):\n";
		$card->copyright = trim(fgets(STDIN));
		$card->copyright = str_replace('(tm)', '&#8482;', $card->copyright);
		$card->copyright = str_replace('(c)', '&#169;', $card->copyright);
		if (!$card->copyright) $card->copyright = ' ';
	}

	if ($cardType != 'v' && (($cardType != 'f' && $cardType != 'fl') || $isLastCard) && ($cardType != 's' || $isLastCard)) {
		echo "Edition (ex: A, B, 7E, 9E, IN, RAV):\n";
		$card->set = strtoupper(trim(fgets(STDIN)));

		echo "Rarity (ex: R, U, C):\n";
		$card->rarity = strtoupper(trim(fgets(STDIN)));

		if ($card->rarity && !$card->set) $card->set = 'X';
	}

	return $card;
}

?>
