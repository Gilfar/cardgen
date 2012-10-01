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

echo "Card Generator v$version - Prices\n\n";

$files = getInputFiles(
	array_slice($argv, 1),
	'Drag and drop a decklist file or directory here and press enter...'
);

$setDB = new SetDB();
$artDB = new ArtDB();
$cardDB = new CardDB($setDB, $artDB);
$convertor = new Convertor();

echo 'Downloading MagicTraders pricing...';

$prices = array();
$file = @fopen('http://www.magictraders.com/pricelists/current-magic', 'r');
if (!$file) error('Unable to open URL: http://www.magictraders.com/pricelists/current-magic');
$count = 0;
while (!feof($file)) {
	$line = fgets($file, 4096);
	if (substr($line, 0, 6) == 'total:') continue;
	$comma = strpos($line, ',  ');
	if ($comma === false) continue;

	$title = strtolower(substr($line, 0, $comma));
	if (substr($title, -1) == ')') {
		$set = substr($title, strrpos($title, '(') + 1, -1);
		$title = substr($title, 0, strrpos($title, '(') - 1);
	} else
		$set = null;

	$price = trim(substr($line, $comma + 3, strpos($line, ',', $comma + 3)));

	if (!@$prices[$title]) $prices[$title] = array();

	if (!@$prices[$title]['']) $prices[$title][''] = array();
	$prices[$title][''][] = $price;
	asort($prices[$title]['']);

	if ($set) {
		$mainSet = $setDB->normalize($set);
		if ($mainSet) $prices[$title][$mainSet] = $price;
		//if (!$mainSet) echo "\nUnknown set \"$set\" for card: $title";
	}

	$count++;
	if ($count % 200 == 0) echo '.';
}
fclose($file);

echo "\n$count prices downloaded.\nCalculating totals...\n\n";

$grandTotal = 0;
foreach ($files as $file) {
	$decklist = new Decklist($setDB, $cardDB, $convertor, $file, true);
	$total = 0;
	foreach ($decklist->cards as $card) {
		$price = @$prices[strtolower($card->title)][$card->set];
		if (!$price) $price = @$prices[strtolower($card->title)][''][0];
		if (!$price) {
			echo 'Price for card not found: ' . $card->title . "\n";
			continue;
		}
		$total += $price;
	}
	echo $decklist->name . ': ' . $total . "\n";
	$grandTotal += $total;
}
echo "\nGrand total: " . $grandTotal;

?>
