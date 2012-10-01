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
require_once 'includes/global.php';

$pagedOutput = $argv[1] == 'pagedOutput=true';
$decklistOnlyOutput = $argv[2] == 'decklistOnlyOutput=true';
$fileNames = array_slice($argv, 3);

$mode = 'Generate ';
if ($decklistOnlyOutput) $mode .= 'Decklist ';
$mode .= $pagedOutput ? 'Pages' : 'Cards';
echo "Card Generator v$version - $mode\n\n";

$files = getInputFiles(
	$fileNames,
	'Drag and drop a decklist file or directory into this window and press enter...'
);
configPrompt($decklistOnlyOutput);
cleanOutputDir($pagedOutput);

$writer = new ImageWriter();
$writer->setOutputType($pagedOutput, $decklistOnlyOutput);
foreach ($files as $file)
	$writer->parseDecklist($file);
echo "Generating images...\n";
if ($pagedOutput) {
	$count = $writer->writePages();
	echo "Image generation complete.\n";
	echo $count . " pages written.\n";
} else {
	$count = $writer->writeCards();
	echo "Image generation complete.\n";
	echo $count . " images written.\n";
}

?>
