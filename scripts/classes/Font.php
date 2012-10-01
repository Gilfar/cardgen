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
class Font {
	public $regular;
	public $italic;
	public $size;
	public $leadingPercent = 1;
	public $centerX;
	public $centerY;
	public $alignRight;
	public $shadow;
	public $glow;

	private $color = '0,0,0';
	private $colorResource;

	public function __construct ($spec) {
		$values = explode(',', $spec, 3);

		if (count($values) < 2 || !is_numeric($values[0])) error('Invalid font spec: ' . $spec);

		$this->size = $values[0];

		if (strpos($values[1], '/') !== false) {
			$fonts = explode('/', $values[1]);
			$this->regular = $this->findFont($fonts[0]);
			$this->italic = $this->findFont($fonts[1]);
		} else {
			$this->regular = $this->findFont($values[1]);
			$this->italic = $this->regular;
		}

		$this->setOptions(@$values[2]);
	}

	public function setOptions ($optionsSpec) {
		foreach (parseNameValues($optionsSpec) as $name => $value) {
			switch ($name) {
			case 'color':
				$this->setColor($value);
				break;
			case 'leading':
				$this->leadingPercent = $value / 100 + 1;
				break;
			case 'centerX':
				$this->centerX = $value == 'true';
				break;
			case 'centerY':
				$this->centerY = $value == 'true';
				break;
			case 'alignRight':
				$this->alignRight = $value == 'true';
				break;
			case 'shadow':
				$this->shadow = $value == 'true';
				break;
			case 'glow':
				$this->glow = $value == 'true';
				break;
			default:
				error("Invalid font options: $optionsSpec");
			}
		}
	}

	private function findFont ($fonts) {
		$fonts = trim($fonts);
		if (strpos($fonts, '>') !== false)
			$fonts = explode('>', $fonts);
		else
			$fonts =  array($fonts);
		foreach ($fonts as $font)
			if (file_exists('fonts/' . $font)) return 'fonts/' . $font;
		error('Unable to find font(s): ' . implode(', ', $fonts));
	}

	public function getColor () {
		return $this->color;
	}

	public function setColor ($color) {
		$this->color = $color;
		$this->colorResource = null;
	}

	public function getHeight () {
		// Height is the distance from the top of the tallest character to the basline.
		$textSize = $this->getSize('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz01234567890');
		return $textSize['yOffset'];
	}

	public function getLineHeight () {
		return $this->getHeight() * $this->leadingPercent;
	}

	public function getSize ($text, $italic = false) {
		$bbox = imagettfbbox($this->size, 0, $italic ? $this->italic : $this->regular, (binary)$text);
		return $this->convertBoundingBox($bbox);
	}

	private function convertBoundingBox ($bbox) {
		// Transform the results of imagettfbbox into usable (and correct!) values.
		if ($bbox[0] >= -1)
			$xOffset = -abs($bbox[0] + 1);
		else
			$xOffset = abs($bbox[0] + 2);
		$width = abs($bbox[2] - $bbox[0]);
		if ($bbox[0] < -1) $width = abs($bbox[2]) + abs($bbox[0]) - 1;
		$yOffset = abs($bbox[5] + 1);
		if ($bbox[5] >= -1) $yOffset = -$yOffset;
		$height = abs($bbox[7]) - abs($bbox[1]);
		if ($bbox[3] > 0) $height = abs($bbox[7] - $bbox[1]) - 1;
		return array(
			'width' => $width,
			'height' => $height,
			'xOffset' => $xOffset, // Using xCoord + xOffset with imagettftext puts the left most pixel of the text at xCoord.
			'yOffset' => $yOffset, // Using yCoord + yOffset with imagettftext puts the top most pixel of the text at yCoord.
			'belowBasepoint' => max(0, $bbox[1])
		);
	}

	public function draw ($canvas, $left, $baseline, $text, $italic = false) {
		if (!$this->colorResource) {
			$rgb = explode(',', $this->color);
			$this->colorResource = imagecolorallocate($canvas, $rgb[0], $rgb[1], $rgb[2]);
		}
		$bbox = imagettftext($canvas, $this->size, 0, $left, $baseline, $this->colorResource, $italic ? $this->italic : $this->regular, (binary)$text);
		return $this->convertBoundingBox($bbox);
	}

	public function hashCode () {

	}
}

?>
