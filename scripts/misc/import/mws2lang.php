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

echo "Card Generator v$version - MWS to cards-<language>.csv\n\n";

$files = getInputFiles(
	array_slice($argv, 1),
	'Drag and drop an MWS masterbase CSV file here and press enter...'
);

echo 'Enter the name of the language: ';
$language = strtolower(trim(fgets(STDIN)));

echo "Creating temporary file: data/cards-$language.csv.temp\n";
$cardsFile = fopen_utf8("data/cards-$language.csv.temp", 'w+');
if (!$cardsFile) error("Unable to write CSV file: data/cards-$language.csv.temp");

// Write masterbase.
$masterBase = new MasterBase($files[0]);
$cards = $masterBase->cards;
foreach ($cards as $card)
	writeCsvRow($cardsFile, CardDB::cardToLanguageRow($card));

fclose($cardsFile);

echo "\n" . count($cards) . " cards processed.\n";
echo "Temporary file complete.\n";

if (file_exists("data/cards-$language.csv")) {
	echo "Backing up file \"data/cards-$language.csv\" to \"data/cards-$language.csv.bak\"...\n";
	@unlink("data/cards-$language.csv.bak");
	@rename("data/cards-$language.csv", "data/cards-$language.csv.bak");
}

echo "Moving temporary file to \"data/cards-$language.csv\"...\n";
rename("data/cards-$language.csv.temp", "data/cards-$language.csv");

echo "Import complete.\n";

?>
