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
class ArtDB {
	private $ext;
	private $root;
	private $directories = array();
	private $titleToUsedArt = array();
	private $picPatterns = array(' %', '%', ' (%)');

	public function __construct (SetDB $setDB) {
		global $config;

		$this->setDB = $setDB;

		$this->ext = '.' . $config['art.extension'];

		$this->root = $config['art.directory'];
		if (!$this->root) error('Missing art.directory from config.txt.');
		$this->root = str_replace('\\', '/', $this->root);
		if (substr($this->root, -1, 1) == '/') $this->root = substr($this->root, 0, -1);

		echo 'Scanning art directories...';
		$this->collectArtDirectories($this->root);
		echo "\n";
	}

	private function collectArtDirectories ($path) {
		global $config;

		$this->directories[] = $path . '/';

		if (count($this->directories) % 3 == 0) echo '.';

		$dir = @opendir($path);
		if (!$dir) {
			if ($config['art.error.when.missing']) error("Unable to locate art directory: $path");
			echo "\n";
			warn("Unable to locate art directory: $path");
			return;
		}

		$dirs = array();
		while (false !== ($file = readdir($dir))) {
			if ($file == '.' || $file == '..') continue;
			if (!is_dir($path . '/' . $file)) continue;
			$dirs[] = $path . '/' . $file;
		}
		closedir($dir);

		foreach ($dirs as $path)
			$this->collectArtDirectories($path);
	}

	public function getArtFileName ($title, $set, $pic) {
		global $config;

		if (strpos($title, '/') !== false) {
			// Split cards art file names are delimited by a pipe ("|").
			$title1 = substr($title, 0, strpos($title, '/'));
			$title2 = substr($title, strpos($title, '/') + 1);
			return $this->getArtFileName($title1, $set, $pic) . '|' . $this->getArtFileName($title2, $set, $pic);
		}

		$name = $title;
		$name = str_replace('Avatar: ', '', $name);
		$name = str_replace(':', '', $name);
		$name = str_replace('"', '', $name);

		$fileName = null;
		if ($config['art.random']) {
			$images = array();
			foreach ($this->directories as $path)
				$this->collectImages($images, $path, $name);
			$images = array_keys($images);
			if (count($images) > 0) {
				// Choose one that hasn't been chosen yet.
				$usedArt = @$this->titleToUsedArt[$title];
				if (!$usedArt) $this->titleToUsedArt[$title] = $usedArt = array();
				while (true) {
					$i = rand(0, count($images) - 1);
					$fileName = $images[$i];
					if (!in_array($fileName, $usedArt)) break;
				}
				$this->titleToUsedArt[$title][] = $fileName;
				// Reset if all images have been chosen.
				if (count($this->titleToUsedArt[$title]) == count($images)) $this->titleToUsedArt[$title] = array();
			}
		} else {
			if ($config['art.debug']) echo "\n";
			foreach ($this->setDB->getAbbrevs($set) as $abbrev) {
				$fileName = $this->findImage($this->root . '/' . $abbrev . '/', $name, $pic);
				if ($fileName)
					break;
			}
		}

		if (!$fileName && $config['art.error.when.missing']) error('No art found for card: ' . $title);

		if ($config['art.debug']) {
			if ($fileName)
				echo "Using art: $fileName\n";
			else
				echo "Art not found for: $title\n";
		}

		return $fileName;
	}

	private function findImage ($path, $name, $pic) {
		global $config;

		if ($pic) {
			foreach ($this->picPatterns as $pattern) {
				$fileName = $path . $name . str_replace('%', $pic, $pattern) . $this->ext;
				$fileNameLowercase = $path . strtolower($name) . str_replace('%', $pic, $pattern) . $this->ext;
				if ($config['art.debug']) {
					echo "Looking for art: $fileName";
					if (file_exists($fileName)) echo ' *found*';
					elseif (file_exists($fileNameLowercase)) echo ' *found*';
					echo "\n";
				}
				if (file_exists($fileName)) return $fileName;
				elseif (file_exists($fileNameLowercase)) return $fileNameLowercase;
			}
		} else {
			$fileName = $path . $name . $this->ext;
			$fileNameLowercase = $path . strtolower($name) . $this->ext;
			if ($config['art.debug']) {
				echo "Looking for art: $fileName";
				if (file_exists($fileName)) echo ' *found*';
				elseif (file_exists($fileNameLowercase)) echo ' *found*';
				echo "\n";
			}
			if (file_exists($fileName)) return $fileName;
			elseif (file_exists($fileNameLowercase)) return $fileNameLowercase;
		}
		return null;
	}

	private function collectImages (&$images, $path, $name) {
		global $config;

		// Look for a file with no picture number.
		$fileName = $path . $name . $this->ext;
		if ($config['art.debug']) {
			echo "\nLooking for art: $fileName";
			if (file_exists($fileName)) echo ' *found*';
			echo "\n";
		}
		if (file_exists($fileName)) $images[$fileName] = true;

		// Find all images with picture numbers.
		$i = 1;
		while (true) {
			$fileName = $this->findImage($path, $name, $i);
			if ($fileName)
				$images[$fileName] = true;
			else
				break;
			$i++;
		}

		return $images;
	}

	public function resetUsedArt () {
		$this->titleToUsedArt = array();
	}
}

?>
