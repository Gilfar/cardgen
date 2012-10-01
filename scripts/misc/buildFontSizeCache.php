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

echo "Card Generator v$version - Build font size cache\n\n";

$existingFontSizeDB = null;
if (file_exists('data/fontSizes.csv')) {
	echo 'Keep existing cache entries? (y/n) ';
	if (strtolower(trim(fgets(STDIN))) != 'n') $existingFontSizeDB = new FontSizeDB(true);

	if (file_exists("data/fontSizes.csv")) {
		echo "Backing up file \"data/fontSizes.csv\" to \"data/fontSizes.csv.bak\"...\n";
		@unlink("data/fontSizes.csv.bak");
		@copy("data/fontSizes.csv", "data/fontSizes.csv.bak");
	}
}

$fontSizesFile = fopen_utf8("data/fontSizes.csv", $existingFontSizeDB ? 'a' : 'w');
if (!$fontSizesFile) error("Unable to write CSV file: data/fontSizes.csv");

$config['output.card.set.directories'] = true;
$config['card.flavor.random'] = false;

$writer = new ImageWriter();
$writer->setOutputType(false, false);
echo "Building cache...\n";
foreach ($writer->cardDB->getAllCardTitles() as $title) {
	if ($existingFontSizeDB && $existingFontSizeDB->hasCard($title)) continue;
	$writer->fontSizeDB->reset();
	$writer->addCardByTitle($title);
	foreach ($writer->renderers as $renderer)
		$renderer->render();
	$fontSizes = $writer->fontSizeDB->getSizes($title);
	if ($fontSizes) {
		foreach ($fontSizes as $hash => $size)
			writeCsvRow($fontSizesFile, array((string)$title, (string)$hash, (string)$size));
	} else {
		writeCsvRow($fontSizesFile, array((string)$title));
	}
	$writer->reset();
}

fclose($fontSizesFile);

echo "Import complete.\n";

?>
