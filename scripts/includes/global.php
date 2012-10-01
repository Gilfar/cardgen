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
require_once 'version.php';

set_time_limit(0);
ini_set('memory_limit', '1024M');
srand((float) microtime() * 10000000);

$config = parse_ini_file('config/config.txt', false);

$rendererSettings = array();
foreach (glob("config/config-*.txt") as $configFile)
	$rendererSettings[$configFile] = parse_ini_file($configFile, false);

$rendererSections = array();
foreach (glob("config/config*.txt") as $configFile)
	$rendererSections[$configFile] = parse_ini_file($configFile, true);

function __autoload($className) {
	$classFile = getClassFile($className . '.php');
	if ($classFile) require_once($classFile);
}

// Searches folders and subfolders for a class php file.
function getClassFile ($fileName, $path = '/') {
	$dirPath = 'scripts' . $path;
	if (file_exists($dirPath . $fileName)) return $dirPath . $fileName;

	$dir = dir($dirPath);
	while (($subdir = $dir->read()) !== false) {
		if ($subdir == '.' || $subdir == '..') continue;
		if (!is_dir($dirPath . $subdir)) continue;
		$classFile = getClassFile($fileName, $path . $subdir . '/');
		if ($classFile) return $classFile;
	}
	$dir->close();
	return false;
}

function warn ($message) {
	echo "WARNING: $message";
}

function error ($message) {
	echo "\n\nERROR: $message";
	exit();
}

// Converts a two column CSV into an associative array.
function csvToArray ($fileName) {
	$array = array();
	$file = fopen_utf8($fileName, 'r');
	if (!$file) error('Unable to open file: ' . $fileName);
	while (($data = fgetcsv($file, 6000, ',')) !== FALSE)
		$array[(string)strtolower($data[0])] = trim($data[1]);
	fclose($file);
	return $array;
}

function getInputFiles ($fileNames) {
	$files = array();

	for ($i = 0, $n = count($fileNames); $i < $n; $i++) {
		$fileName = $fileNames[$i];
		// Need to strip "" otherwise is_dir fails on paths with spaces.
		if (substr($fileName, 0, 1) == '"') $fileName = substr($fileName, 1);
		if (substr($fileName, -1) == '"') $fileName = substr($fileName, 0, -1);
		if (is_dir($fileName)) {
			foreach (glob($fileName . '/*') as $child)
				if (!is_dir($child)) $files[] = validateFileName($child);
		} else
			$files[] = validateFileName($fileName);
	}

	$requiredFileCount = func_num_args();
	for ($i = count($files) + 1; $i < $requiredFileCount; $i++) {
		echo func_get_arg($i) . "\n";
		$input = trim(fgets(STDIN));
		echo "\n";
		// Need to strip "" otherwise is_dir fails on paths with spaces.
		if (substr($input, 0, 1) == '"') $input = substr($input, 1);
		if (substr($input, -1) == '"') $input = substr($input, 0, -1);
		if (is_dir($input)) {
			foreach (glob($input . '/*') as $child)
				if (!is_dir($child)) $files[] = validateFileName($child);
		} else
			$files[] = validateFileName($input);
	}

	return $files;
}

function validateFileName ($fileName) {
	$fileName = str_replace('\\', '/', trim($fileName));
	if (substr($fileName, 0, 1) == '"') $fileName = substr($fileName, 1);
	if (substr($fileName, -1) == '"') $fileName = substr($fileName, 0, -1);
	if (!$fileName) error('Missing input file.');
	if (!file_exists($fileName)) error("File does not exist: $fileName");
	return $fileName;
}

function configPrompt ($decklistOnlyOutput) {
	global $config, $rendererSettings, $rendererSections, $promptOutputClean;

	$configFiles = glob("config*.txt");
	foreach ($configFiles as $configFile) {
		if ($configFile == 'config.txt')
			$currentConfig =& $config;
		else
			$currentConfig =& $rendererSettings[$configFile];
		$prompt = @$rendererSections[$configFile]['prompt'];
		if (!$prompt) continue;
		foreach ($prompt as $name => $value) {
			if (!$value) continue;
			if ($name == 'output.clean') {
				// This is handled as a special case in cleanOutputDir().
				$promptOutputClean = true;
				continue;
			}
			echo $name . '=[' . $currentConfig[$name] . '] ';
			$input = trim(fgets(STDIN));
			if ($input == '') $input = $currentConfig[$name];
			$currentConfig[$name] = $input;
		}
	}
}

function cleanOutputDir ($pagedOutput) {
	global $config, $promptOutputClean;

	$outputDir = $config['output.directory'];
	$ext = $config['output.extension'];

	$skipImages = 0;
	if ($files = glob("$outputDir*.$ext")) {
		$append = false;
		echo strtoupper($ext) . ' files in output directory: ' . count($files) . "\n";
		if ($pagedOutput && file_exists($outputDir . 'lastPage.txt') && file_exists($outputDir . "page1.$ext")) {
			echo 'Append to files in output directory? (y/n) ';
			$append = strtolower(trim(fgets(STDIN))) == 'y';
		}
		if (!$append) {
			if (@$promptOutputClean) {
				echo 'output.clean=[' . $config['output.clean'] . '] ';
				$input = trim(fgets(STDIN));
				if ($input != '') $config['output.clean'] = $input;
			}
			if ($config['output.clean']) {
				echo 'Deleting ' . strtoupper($ext) . " files from output directory...\n";
				foreach ($files as $file)
					unlink($file);
				@unlink($outputDir . 'lastPage.txt');
			}
		}
	}

	@mkdir($outputDir);
	if (!file_exists($outputDir)) error("Error locating output directory: $outputDir");
}

function getNameFromPath ($fileName) {
	$fileName = basename($fileName);
	if (strrpos($fileName, '/') !== FALSE) $fileName = strrchr($fileName, '/');
	if (strrpos($fileName, '.') !== FALSE) $fileName = substr($fileName, 0, strrpos($fileName, '.'));
	return splitWords($fileName);
}

function splitWords ($text) {
	// Seperate "[lowercase char][uppercase char]" with a space.
	for ($i = 1, $n = strlen($text); $i < $n; $i++) {
		$char = substr($text, $i, 1);
		if ($char < 'A' || $char > 'Z') continue;
		$prevChar = substr($text, $i - 1, 1);
		if ($prevChar < 'a' || $prevChar > 'z') continue;
		$text = substr($text, 0, $i) . ' ' . substr($text, $i);
		$i++;
	}
	return upperCaseWords($text);
}

function upperCaseWords ($text) {
	$text = ucwords($text);
	$text = str_replace(' Of ', ' of ', $text);
	$text = str_replace(' A ', ' a ', $text);
	$text = str_replace(' The ', ' the ', $text);
	$text = str_replace(' De ', ' de ', $text);
	$text = str_replace(' With ', ' with ', $text);
	return $text;
}

function getPNG ($fileName, $errorMessage = null) {
	$image = @imagecreatefrompng($fileName);
	if (!$image) {
		if ($errorMessage)
			error($errorMessage);
		else
			return null;
	}
	list($width, $height) = getimagesize($fileName);
	return array($image, $width, $height);
}

function getGIF ($fileName, $errorMessage = null) {
	$image = @imagecreatefromgif($fileName);
	if (!$image) {
		if ($errorMessage)
			error($errorMessage);
		else
			return null;
	}
	list($width, $height) = getimagesize($fileName);
	return array($image, $width, $height);
}

// Avoid PHP5's fputcsv because it writes far too many extra quotes (any time the field contains a space!).
function writeCsvRow ($csvFile, $row) {
	$csvString = '';
	$writeComma = false;
	foreach ($row as $value) {
		if ($writeComma) $csvString .= ',';
		$writeQuote = strpos($value, ',') !== false || strpos($value, "\n") !== false;
		if ($writeQuote) {
			$value = str_replace('"', '""', $value);
			$csvString .= '"';
		}
		$csvString .= $value;
		if ($writeQuote) $csvString .= '"';
		$writeComma = true;
	}
	fwrite($csvFile, ($csvString . "\n"));
}

function parseNameValues ($text) {
	$values = array();
	if (preg_match_all('/([^:\s]+):("(?P<value1>[^"]+)"|\'(?P<value2>[^\']+)\'|(?P<value3>[^ ]+))/', $text, $matches, PREG_SET_ORDER))
		foreach ($matches as $match)
			$values[trim($match[1])] = trim(@$match['value1'] . @$match['value2'] . @$match['value3']);
	return $values;
}

// Reads past the UTF-8 bom if it is there.
function fopen_utf8 ($filename, $mode) {
	$file = @fopen($filename, $mode);
	if (!$file) error('Unable to open file: ' . $filename);
	$bom = fread($file, 3);
	if ($bom != "\xEF\xBB\xBF") rewind($file);
	return $file;
}
?>
