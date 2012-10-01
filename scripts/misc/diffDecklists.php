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

echo "Card Generator v$version - Diff Decklists\n\n";

$files = getInputFiles(
	array_slice($argv, 1),
	'Drag and drop the original decklist file into this window and press enter...',
	'Drag and drop the new decklist file into this window and press enter...'
);

echo 'Ignore sets and picture numbers? (y/n) ';
$ignoreSets = strtolower(trim(fgets(STDIN))) == 'y';
echo "\n";

$setDB = new SetDB();
$artDB = new ArtDB();
$cardDB = new CardDB($setDB, $artDB);
$convertor = new Convertor();

$oldDecklist = new Decklist($setDB, $cardDB, $convertor, $files[0]);
$newDecklist = new Decklist($setDB, $cardDB, $convertor, $files[1]);

echo "Examining differences...";
$addCardsIndex = cardsMinusCards($newDecklist->cards, $oldDecklist->cards);
$removeCardsIndex = cardsMinusCards($oldDecklist->cards, $newDecklist->cards);
echo "\n";
if (count($addCardsIndex) == 0 && count($removeCardsIndex) == 0) {
	echo "Decklists are identical.";
	return;
}

$outputFileDir = substr($files[1], 0, -strlen(strrchr($files[1], '/'))) . '/';

echo "\nCards to add to " . $oldDecklist->name . ":\n";
$addCardsFileName = $outputFileDir . $newDecklist->name . ' (add).csv';
$addCardsFile = fopen_utf8($addCardsFileName, 'w');
foreach ($addCardsIndex as $index => $qty) {
	$card = $newDecklist->cards[$index];
	echo $qty . ', ' . $card . "\n";
	$name = $card->title;
	if (!$ignoreSets && $card->pic) $name .= ' (' . $card->pic . ')';
	fputcsv($addCardsFile, array($qty, $name, $card->set));
}
fclose($addCardsFile);
echo "\nOutput decklist:\n$addCardsFileName\n";

echo "\nCards to remove from " . $oldDecklist->name . ":\n";
$removeCardsFileName = $outputFileDir . $newDecklist->name . ' (remove).csv';
$removeCardsFile = fopen_utf8($removeCardsFileName, 'w');
foreach ($removeCardsIndex as $index => $qty) {
	$card = $oldDecklist->cards[$index];
	echo $qty . ', ' . $card . "\n";
	$name = $card->title;
	if (!$ignoreSets && $card->pic) $name .= ' (' . $card->pic . ')';
	fputcsv($removeCardsFile, array($qty, $name, $card->set));
}
fclose($removeCardsFile);
echo "\nOutput decklist:\n$removeCardsFileName\n";

function cardsMinusCards ($a, $b) {
	global $ignoreSets;

	$result = array();
	foreach ($a as $index => $cardA) {
		foreach ($b as $cardB) {
			if ($cardA->title != $cardB->title) continue;
			if (!$ignoreSets) {
				if ($cardA->set != $cardB->set) continue;
				if ($cardA->pic != $cardB->pic) continue;
			}
			// Remove card from $b that both arrays contain.
			unset($b[array_search($cardB, $b)]);
			continue 2;
		}
		// $cardA was not found in $b.
		if (!@$result[$index]) $result[$index] = 0;
		$result[$index]++;
	}
	return $result;
}

?>
