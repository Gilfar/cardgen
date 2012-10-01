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

echo "Card Generator v$version - Generate All\n\n";

configPrompt(false);
cleanOutputDir(false);

$config['output.card.set.directories'] = true;

$writer = new ImageWriter();
$writer->setOutputType(false, false);
echo "Generating images...\n";
foreach ($writer->cardDB->getAllCardTitles() as $title) {
	$writer->addCardByTitle($title);
	$writer->writeCards();
}

?>
