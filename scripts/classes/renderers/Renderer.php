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
abstract class Renderer {
	public $writer;
	public $outputDir;
	public $outputName;

	abstract public function render ();
	abstract public function getSettings ();

	// Looks up a font object from the renderer settings.
	public function font ($key, $optionsSpec = null) {
		$key = 'font.' . $key;
		$settings = $this->getSettings();
		if (!@$settings[$key]) error('Font not found: ' . $key);
		$font = new Font($settings[$key]);
		if ($optionsSpec) $font->setOptions($optionsSpec);
		return $font;
	}

	public function getTextWidth ($text, Font $font) {
		return $this->drawText(null, 0, 0, null, $text, $font);
	}

	// Draws text with embedded symbols, scaled to fit within the specified width (never wrapped).
	public function drawText ($canvas, $left, $baseline, $maxWidth, $text, Font $font) {
		if (!$maxWidth) $maxWidth = 99999;

		$text = str_replace('*', '{*}', $text);
		$text = str_replace('+', '{+}', $text);
		$chunks = $this->getChunks($text);
		// Scale down text to fit, if needed.
		$tempFont = clone $font;
		while (true) {
			$textSize = $this->testChunksWrapped(99999, $chunks, $tempFont);
			if ($textSize["lastLineWidth"] < $maxWidth) break;
			$tempFont->size -= 0.1;
			if ($tempFont->size < 8) {
				warn('Text does not fit: ' . $text);
				break;
			}
		}

		if ($tempFont->centerX) $left -= $textSize["lastLineWidth"] / 2;
		if ($tempFont->alignRight) $left += $maxWidth - $textSize["lastLineWidth"];
		if ($tempFont->centerY)
			$baseline += ($textSize["height"] - $textSize["belowBaseline"]) / 2;
		else {
			// Adjust smaller text upwards slightly.
			if ($tempFont->size < 11) $baseline += $tempFont->size - 11;
		}
		if ($tempFont->shadow) {
			$shadowFont = clone $tempFont;
			$shadowFont->setColor('0,0,0');
			$this->drawChunksWrapped($canvas, $baseline + 2, $left + 2, 99999, $chunks, $shadowFont);
		}
		if($tempFont->glow) {
			$glowFont = clone $tempFont;
			$glowFont->setColor('255,255,255');
			$this->drawChunksWrapped($canvas, $baseline + 2, $left, 99999, $chunks, $glowFont);
			$this->drawChunksWrapped($canvas, $baseline - 2, $left, 99999, $chunks, $glowFont);
			$this->drawChunksWrapped($canvas, $baseline, $left + 2, 99999, $chunks, $glowFont);
			$this->drawChunksWrapped($canvas, $baseline, $left - 2, 99999, $chunks, $glowFont);
		}
		$this->drawChunksWrapped($canvas, $baseline, $left, 99999, $chunks, $tempFont);
		return $textSize["lastLineWidth"];
	}

	// Converts text into smaller chunks for renderering italics, symbols, etc.
	private function getChunks ($text) {
		$text = trim($text);
		//$text = iconv('UTF-8', 'ASCII//TRANSLIT', $text); // Convert to UTF-8 so extended characters are rendered by the font correctly.
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace('# ', ' #', $text); // A non-italic space after an italic character is too large. In this case, always use an italic space.
		$chunks = array();
		// Regex: any chars (ungreedy), {}# or \n not preceded by & (ignores entities, eg &#123;), any chars that are not {}# or \n.
		if (!preg_match_all('/(.*?)((?<!&)[{}#\n])([^{}#\n]*)/', $text, $matches, PREG_SET_ORDER)) {
			// Chunk with no symbols or italics.
			$chunk = new Chunk();
			$chunk->value = $text;
			$chunks[] = $chunk;
		} else {
			$italic = false;
			$chunk = new Chunk();
			foreach ($matches as $match) {
				$chunk->value .= @$match[1];
				if ($chunk->value != null || $chunk->newLine) $chunks[] = $chunk;
				if ($match[2] == '#') $italic = !$italic;
				$chunk = new Chunk();
				$chunk->isItalic = $italic;
				$chunk->isSymbol = $match[2] == '{';
				$chunk->newLine = $match[2] == "\n";
				$chunk->value = @$match[3];
			}
			// When there is some text left unprocessed by the regex, happens when there is an entitle in the last chunk.
			//if(@strpos($text, $chunk->value) !== false)
			//	$chunk->value .= substr($text, strlen($chunk->value)+strpos($text, $chunk->value));
			if ($chunk->value != null) $chunks[] = $chunk;
		}
		return $chunks;
	}

	// Draws text with embedded symbols, wrapped to fit within the specified width.
	public function drawTextWrapped ($canvas, $baseline, $left, $right, $text, Font $font) {
		return $this->drawChunksWrapped($canvas, $baseline, $left, $right, $this->getChunks($text), $font);
	}

	public function testTextWrapped ($width, $text, Font $font) {
		return $this->testChunksWrapped($width, $this->getChunks($text), $font);
	}

	private function testChunksWrapped ($width, $chunks, Font $font) {
		return $this->drawChunksWrapped(null, 0, 0, $width, $chunks, $font);
	}

	private function drawChunksWrapped ($canvas, $baseline, $left, $right, $chunks, Font $font) {
		global $config;

		$baselineStart = $baseline;
		$maxWidth = $right - $left;
		$textHeight = $font->getHeight();
		$textLeading = $textHeight * $font->leadingPercent;
		$symbolHeight = $textHeight * ($config['card.text.symbol.height.percentage'] / 100);
		$symbolSpacing = $config['card.text.symbol.spacing'];
		$belowBaseline = 0;
		$xOffset = 0;
		$belowBaseline = 0;
		for ($i=0, $n=count($chunks); $i < $n; $i++) {
			$chunk = $chunks[$i];
			if ($chunk->isSymbol) {
				$xOffset += $symbolSpacing;
				// Wrap if this symbol and any following symbols or non-whitespace text are too long.
				$nonWrappedChunksWidth = 0;
				for ($ii=$i; $ii < $n; $ii++) {
					$nextChunk = $chunks[$ii];
					if ($nextChunk->isSymbol) {
						// Don't wrap in between symbols.
						list($symbolWidth) = $this->drawSymbol(null, 0, 0, $symbolHeight, $nextChunk->value, false, true);
						$nonWrappedChunksWidth += $symbolSpacing + $symbolWidth;
						if ($ii < $n - 1) $nonWrappedChunksWidth += $symbolSpacing; // Don't add spacing if this is the last chunk.
					} else {
						// Don't wrap in between a symbol and text (eg, when a period follows a symbol).
						if (preg_match('/^([^\w]*)\w/', $nextChunk->value, $matches)) {
							// Text starts with non-whitespace characters. Get their width.
							$textSize = $font->getSize($matches[0], $nextChunk->isItalic);
							$nonWrappedChunksWidth += $textSize['width'];
						}
						break;
					}
				}
				if ($xOffset + $nonWrappedChunksWidth > $maxWidth) {
					$xOffset = 0;
					$belowBaseline = 0;
					$baseline += $textLeading;
				}
				// Draw symbol.
				$symbolTop = $baseline - $symbolHeight + 1 + (($symbolHeight - $textHeight) / 2);
				list($symbolWidth) = $this->drawSymbol($canvas, $symbolTop, $left + $xOffset, $symbolHeight, $chunk->value, false, true, $font->getColor());
				$xOffset += $symbolWidth;
				if ($i < $n - 1) $xOffset += $symbolSpacing; // Don't add spacing if this is the last chunk.
			} else {
				if ($chunk->newLine) {
					$xOffset = 0;
					$belowBaseline = 0;
					// If there last chunk was a newline with no text, then this newline is the second one in a row. Reduce the leading on the second one.
					$previousChunk = @$chunks[$i - 1];
					if ($previousChunk && $previousChunk->newLine && $previousChunk->value == null)
						$baseline += $textLeading * ($config['card.text.double.spacing'] / 100);
					else
						$baseline += $textLeading;
					if ($chunk->value == null) continue;
				}
				$spaceSize = $font->getSize(' ', $chunk->isItalic);
				$spaceWidth = $spaceSize['width'];
				if (substr($chunk->value, 0, 1) == ' ') $xOffset += $spaceWidth; // Starts with space.
				// Break text into words and build an array of lines.
				$words = explode(' ', $chunk->value);
				$lines = array();
				$text = '';
				//$wordN = 0;
				foreach ($words as $word) {
					//$wordN++;
					if ($word == null || $word == '') continue;
					$testLine = $text;
					if ($text != '') $testLine .= ' '; // Space between words.
					$testLine .= $word;
					$lineSize = $font->getSize($testLine, $chunk->isItalic);
					if (count($lines) == 0) $lineSize['width'] += $xOffset; // Only the first line takes into account the xOffset.
					if ($lineSize['width'] > $maxWidth) {

						// Check if after a comma only one word was added.
						// If the codition was true then remove the previous word, start a new line and put the previous word plus current one.
						/*$prevWordLen = @strlen($words[$wordN-2]);
						if(substr($text, -$prevWordLen-2, 1) == ','){
							$text = substr($text, 0, -$prevWordLen-1);
							$lineSize = $font->getSize($text, $chunk->isItalic);
							$lineSize['text'] = $text;
							$lines[] = $lineSize;
							$text = $words[$wordN-2] .' '. $word;
							continue;
						}*/

						// Word doesn't fit, start a new line.
						$lineSize['text'] = $text;
						$lines[] = $lineSize;
						$text = $word;
					} else
						$text = $testLine; // Word fits, collect more.
				}
				// Store last line.
				$lineSize = $font->getSize($text, $chunk->isItalic);
				$lineSize['text'] = $text;
				$lines[] = $lineSize;
				$belowBaseline = max($belowBaseline, $lineSize['belowBasepoint']);
				// Write each line.
				$lineCount = count($lines);
				foreach ($lines as $line) {
					if ($canvas) $font->draw($canvas, $left + $xOffset + $line['xOffset'], $baseline, $line['text'], $chunk->isItalic);
					if ($lineCount > 1) {
						$xOffset = 0;
						$baseline += $textLeading;
					}
				}
				if ($lineCount > 1) $baseline -= $textLeading; // Stay on the last line written.
				$xOffset += $lineSize['width']; // Last line width.
				if (substr($chunk->value, -1) == ' ') $xOffset += $spaceWidth; // Ends with space.
			}
		}
		return array(
			"belowBaseline" => $belowBaseline,
			"lastLineWidth" => $xOffset,
			"height" => ($baseline - $baselineStart) + $textHeight + $belowBaseline,
			"lineCount" => (($baseline - $baselineStart) / $textLeading) + 1
		);
	}

	// Draws text with embedded symbols, wrapped to fit within the specified width and scaled to fit within the specified height.
	public function drawTextWrappedAndScaled ($canvas, $top, $left, $bottom, $right, $text, $font, $center = true, $heightAdjust = 0) {
		if (!$text) return;
		$chunks = $this->getChunks($text);
		$maxHeight = $bottom - $top - $heightAdjust; // heightAdjust affects text height scaling but not centering.
		$tempFont = clone $font;
		while (true) {
			$textSize = $this->testChunksWrapped($right - $left, $chunks, $tempFont);
			if ($textSize["height"] < $maxHeight) break;
			$difference = $textSize["height"] - $maxHeight;
			if ($difference < 15)
				$decrement = 0.05;
			else if ($difference < 30)
				$decrement = 0.2;
			else if ($difference < 100)
				$decrement = 0.4;
			else
				$decrement = 0.8;
			$tempFont->size -= $decrement;
			if ($tempFont->size < 8) {
				warn('Text does not fit: ' . $text);
				break;
			}
			echo '.';
		}
		// Distance below baseline is not used for centering.
		$offset = !$center ? 0 : ($bottom - $top - ($textSize["height"] - $textSize["belowBaseline"])) / 2;
		$this->drawChunksWrapped($canvas, $top + $tempFont->getHeight() + $offset, $left, $right, $chunks, $tempFont);
		return $tempFont->size;
	}

	// Draws a single symbol.
	public function drawSymbol ($canvas, $top, $left, $height, $symbol, $shadow, $fixedSize = false, $color = '0,0,0') {
		global $config;

		if ($color != '255,255,255') $color = '0,0,0';

		$scale = true;
		$yOffset = 0; //only used when $scale == true

		if ($symbol == '*') {
			$symbol = "star_$color";
			$shadow = null;
		}
		if ($symbol == '+') {
			$symbol = "plus_$color";
			$shadow = null;
		}
		if ($symbol == '-') {
			$symbol = "minus_$color";
			$shadow = null;
		}
		if ($symbol == 'brush') {
			$symbol = "brush_$color";
			$shadow = null;
			$scale = false;
			$yOffset = $height/3;
		}
		if ($symbol == 'gear') {
			$symbol = "gear_$color";
			$shadow = null;
			$scale = false;
		}

		// If a dual symbol or always using larger symbols, use a larger symbol.
		if ((!is_numeric($symbol) && strlen($symbol) == 2) || ($config['card.larger.regular.symbols'] && !$fixedSize)) {
			$top -= $height * 0.25 / 2; // Move symbol up half the height increase.
			$height *= 1.25;
		}

		if(preg_match('/(\d|X|Y|Z)(W|U|R|G|B)/i', $symbol, $matches)) {
			list($sliceImage, $srcWidth, $srcHeight) = getPNG("images/symbols/$matches[1]_.png", "Symbol image not found: $matches[1]_");
			switch($matches[2]) {
				case 'W':
				case 'U':
					$prefix = 'G';
					break;
				case 'R':
				case 'G':
					$prefix = 'B';
					break;
				case 'B':
					$prefix = 'U';
					break;
			}
			list($image, $srcWidth, $srcHeight) = getPNG("images/symbols/$prefix$matches[2].png", "Symbol image not found: $prefix$matches[1]");
			imagecopy($image, $sliceImage, 0, 0, 0, 0, $srcWidth, $srcHeight);
			imagedestroy($sliceImage);
		}
		else {
			if($symbol == 'T' && $this instanceof PreEighthRenderer && $config['card.old.tap.symbol']) {
				$symbol .= "_pre";
			}
			list($image, $srcWidth, $srcHeight) = getPNG("images/symbols/$symbol.png", "Symbol image not found: $symbol");
		}
		$width = $height * ($srcWidth / $srcHeight);
		if ($canvas) {
			if ($shadow) {
				$shadowImage = @imagecreatefrompng('images/symbols/shadow.png');
				if (!$shadowImage) error('Symbol shadow image not found.');
				imagecopyresampled($canvas, $shadowImage, $left - 2, $top + 2, 0, 0, $width + 2, $width + 2, 134, 134);
				imagedestroy($shadowImage);
			}
			if($scale)
				imagecopyresampled($canvas, $image, $left, $top, 0, 0, $width, $height, $srcWidth, $srcHeight);
			else
				imagecopy($canvas, $image, $left, $top+$yOffset, 0, 0, $srcWidth, $srcHeight);

			imagedestroy($image);
		}
		if ($scale)
			return array($width, $left);
		else
			return array($srcWidth, $left);
	}
}

class Chunk {
	public $value;
	public $isSymbol;
	public $newLine;
	public $isItalic;
}

?>
