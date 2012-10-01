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
class FontSizeDB {
	private $titleToHashesToSize = array();

	public function __construct ($loadFromFile) {
		if (!$loadFromFile || !file_exists('data/fontSizes.csv')) return;

		$file = fopen_utf8('data/fontSizes.csv', 'r');
		if (!$file) error('Unable to open file: data/fontSizes.csv');

		while (($data = fgetcsv($file, 6000, ',')) !== FALSE) {
			$title = $data[0];
			if (@!$this->titleToHashesToSize[(string)$title]) $this->titleToHashesToSize[(string)$title] = array();
			if (count($data) < 3) continue;
			$this->titleToHashesToSize[(string)$title][(string)$data[1]] = $data[2];
		}
		fclose($file);
	}

	public function getSize ($title, $font, $text, $width, $height) {
		$title = strtolower($title);
		return @$this->titleToHashesToSize[(string)$title][(string)$this->getHash($title, $font, $text, $width, $height)];
	}

	public function setSize ($title, $font, $text, $width, $height, $size) {
		// Don't cache sizes that aren't scaled down.
		if ($font->size == $size) return;

		$title = strtolower($title);
		if (@!$this->titleToHashesToSize[$title]) $this->titleToHashesToSize[$title] = array();
		$this->titleToHashesToSize[$title][$this->getHash($title, $font, $text, $width, $height)] = $size;
	}

	private function getHash ($title, $font, $text, $width, $height) {
		$data = '';
		$data .= '|' . $title;
		$data .= '|' . $font->size;
		$data .= '|' . $font->regular;
		$data .= '|' . $font->italic;
		$data .= '|' . $font->size;
		$data .= '|' . $font->leadingPercent;
		$data .= '|' . $text;
		$data .= '|' . $width;
		$data .= '|' . $height;
		return hash('md5', (binary)$data);
	}

	public function reset () {
		$this->titleToHashesToSize = array();
	}

	public function getSizes ($title) {
		return @$this->titleToHashesToSize[(string)strtolower($title)];
	}

	public function hasCard ($title) {
		return is_array(@$this->titleToHashesToSize[(string)strtolower($title)]);
	}
}

?>
