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
abstract class CardRenderer extends Renderer {
	public $card;
	public $setDB;

	public function __construct (SetDB $setDB) {
		$this->setDB = $setDB;
	}

	// Draws the art image within the specified box, cropping the art if necessary to maintain proportions.
	public function drawArt (&$canvas, $artFileName, $artTop, $artLeft, $artBottom, $artRight, $ignoreAspectRatio = false) {
		$artImage = null;
		if ($artFileName) {
			switch (substr($artFileName, -4)) {
			case ".jpg":
			case "jpeg":
				$artImage = @imagecreatefromjpeg($artFileName);
				break;
			case ".png":
				$artImage = @imagecreatefrompng($artFileName);
				break;
			case ".gif":
				$artImage = @imagecreatefromgif($artFileName);
				break;
			default:
				error('Unknown art file extension: ' . $artFileName);
			}
		}
		if (!$artImage) {
			echo '*art not found*';
			return;
		}
		list($srcWidth, $srcHeight) = getimagesize($artFileName);
		$destWidth = $artRight - $artLeft + 1;
		$destHeight = $artBottom - $artTop + 1;
		if ($ignoreAspectRatio) {
			imagecopyresampled($canvas, $artImage, $artLeft, $artTop, 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
			imagedestroy($artImage);
			return;
		}
		if ($srcWidth > $srcHeight) {
			$height = $srcWidth * ($destHeight / $destWidth);
			$width = $srcWidth;
			if ($height > $srcHeight) {
				$width = $srcHeight * ($destWidth / $destHeight);
				$height = $srcHeight;
			}
		} else {
			$width = $srcHeight * ($destWidth / $destHeight);
			$height = $srcHeight;
			if ($width > $srcWidth) {
				$height = $srcWidth * ($destHeight / $destWidth);
				$width = $srcWidth;
			}
		}
		$srcX = ($srcWidth - $width) / 2;
		$srcY = ($srcHeight - $height) / 2;
		imagecopyresampled($canvas, $artImage, $artLeft, $artTop, $srcX, $srcY, $destWidth, $destHeight, $width, $height);
		imagedestroy($artImage);
	}

	public function drawLegalAndFlavorText ($canvas, $top, $left, $bottom, $right, $legal, $flavor, $font, $heightAdjust = 0) {
		$text = str_replace("\n", "\n\n", $legal);
		if ($flavor) $text .= "\n\n#" . $flavor . '#';
		$this->drawTextWrappedAndScaled($canvas, $top, $left, $bottom, $right, $text, $font, true, $heightAdjust);
	}

	public function drawRarity ($canvas, $rarity, $set, $right, $middle, $height, $width, $whiteBorder, $fallback = true) {
		$image = null;
		if ($whiteBorder)
			foreach ($this->setDB->getAbbrevs($set) as $abbrev){
				list($image, $srcWidth, $srcHeight) = getPNG('images/preEighth/rarity/' . $abbrev . '_' . $rarity . '.png');
				if ($image)
					break;
				list($image, $srcWidth, $srcHeight) = getGIF('images/preEighth/rarity/' . $abbrev . '_' . $rarity . '.gif');
				if ($image)
					break;
			}
		if (!$image && $fallback)
			foreach ($this->setDB->getAbbrevs($set) as $abbrev) {
				list($image, $srcWidth, $srcHeight) = getPNG('images/eighth/rarity/' . $abbrev . '_' . $rarity . '.png');
				if ($image)
					break;
				list($image, $srcWidth, $srcHeight) = getGIF('images/eighth/rarity/' . $abbrev . '_' . $rarity . '.gif');
				if ($image)
					break;
			}
		if ($image) {
			$destWidth = $srcWidth;
			$destHeight = $srcHeight;
			// Resize height, keeping width in proportion.
			if ($srcHeight > $height) {
				$destWidth = $height * ($srcWidth / $srcHeight);
				$destHeight = $height;
			}
			if ($width && $destWidth > $width) {
				$destWidth = $width;
				$destHeight = $width * ($srcHeight / $srcWidth);
			}
			imagecopyresampled($canvas, $image, $right - $destWidth, $middle - ($destHeight / 2), 0, 0, $destWidth, $destHeight, $srcWidth, $srcHeight);
			imagedestroy($image);
			$rarityLeft = $right - $destWidth - 5;
		} else
			$rarityLeft = $right;
		return $rarityLeft;
	}

	// Draws multiple symbols.
	public function drawCastingCost ($canvas, $symbols, $top, $right, $symbolSize, $shadow = false) {
		// Figure width of all symbols.
		$symbolsWidth = 0;

		foreach ($symbols as $symbol) {
			list($width) = $this->drawSymbol(null, 0, 0, $symbolSize, $symbol, false);
			$symbolsWidth += $width + 4;
		}
		// Output casting cost symbols from left to right.
		$x = $right - $symbolsWidth;
		$left = $x - 5;
		foreach ($symbols as $symbol) {
			list($width) = $this->drawSymbol($canvas, $top, $x, $symbolSize, $symbol, $shadow);
			$x += $width + 4;
		}
		return $left;
	}

	public function drawTextWrappedAndScaled ($canvas, $top, $left, $bottom, $right, $text, $font, $center = true, $heightAdjust = 0) {
		// Use font size from cache if possible.
		$cachedFontSize = $this->writer->fontSizeDB->getSize($this->card->title, $font, $text, $right - $left, $bottom - $top);
		if ($cachedFontSize) {
			$font = clone $font;
			$font->size = $cachedFontSize;
		}
		$fontSize = parent::drawTextWrappedAndScaled($canvas, $top, $left, $bottom, $right, $text, $font, $center, $heightAdjust);
		// Store font size in cache if needed.
		if (!$cachedFontSize) $this->writer->fontSizeDB->setSize($this->card->title, $font, $text, $right - $left, $bottom - $top, $fontSize);
	}

	public function getCardName() {
		return $this->card->title;
	}
}

?>
