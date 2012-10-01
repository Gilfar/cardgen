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
class DecklistRenderer extends Renderer {
	public $cards = array();

	public function render () {
		echo $this->outputName . '...';

		$canvas = imagecreatetruecolor(736, 1050);
		$settings = $this->getSettings();

		$black = imagecolorallocate($canvas, 0, 0, 0);
		imagefill($canvas, 0, 0, $black);
		imagefilledrectangle($canvas, 12, 14, 736 - 12, 1050 - 14, imagecolorallocate($canvas, 255, 255, 255));
		imageline($canvas, 20, 62, 716, 62, $black);

		// Compute the number of colored mana symbols.
		$symbolsCount = array("R" => 0, "G" => 0, "U" => 0, "B" => 0, "W" => 0);
		$allCards = isset($this->wholeDeck) ? $this->wholeDeck : $this->cards;
		foreach ($allCards as $card) {
			foreach ($card->getCostSymbols() as $symbols) {
				for ($i = 0, $n = strlen($symbols); $i < $n; $i++) {
					$symbol = $symbols[$i];
					if (isset($symbolsCount[$symbol])) $symbolsCount[$symbol]++;
				}
			}
		}
		$symbolsTotal = 0;
		foreach ($symbolsCount as $count)
			$symbolsTotal += $count;

		// Draw deck colors.
		$symbolsLeft = $settings['colors.right'];
		if ($symbolsTotal > 0) {
			asort($symbolsCount);
			foreach ($symbolsCount as $symbol => $count) {
				// Draw either a large symbol or smaller one, based on the percentage of the color in the deck.
				if ($count / $symbolsTotal >= 0.15) {
					list($width) = $this->drawSymbol(null, 0, 0, 40, $symbol, false, true);
					$symbolsLeft -= $width;
					$this->drawSymbol($canvas, $settings['colors.top'], $symbolsLeft, 40, $symbol, true, true);
				} else if ($count > 0) {
					list($width) = $this->drawSymbol(null, 0, 0, 25, $symbol, false, true);
					$symbolsLeft -= $width;
					$this->drawSymbol($canvas, $settings['colors.top'] + 15, $symbolsLeft, 25, $symbol, true, true);
				}
				$symbolsLeft -= 5;
			}
		}

		// Draw title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $symbolsLeft - $settings['title.x'], str_replace('Decklist - ', '', $this->outputName), $this->font('title'));

		// Draw legality icons.
		list($legality, $maindeckLegality, $illegalSideboardCards) = $this->writer->formatDB->getLegality($this->cards, $settings['decklist.debug.legality']);
		$y = $settings["legality.top"];
		$x = $settings["legality.left"];
		foreach ($legality as $format => $isLegal) {
			if ($isLegal)
				$imageName = "legal";
			else if ($maindeckLegality[$format])
				$imageName = "caution";
			else
				$imageName = "illegal";
			switch ($format) {
			case "Vintage (T1)":
				$format = "t1";
				break;
			case "Legacy (T1.5)":
				$format = "t15";
				break;
			case "Extended (T1.x)":
				$format = "t1x";
				break;
			case "Standard (T2)":
				$format = "t2";
				break;
			}
			$legalityImage = @imagecreatefrompng("images/decklist/$imageName $format.png");
			if (!$legalityImage) error("Legality image not found: $imageName $format.png");
			imagecopy($canvas, $legalityImage, $x, $y, 0, 0, 25, 25);
			imagedestroy($legalityImage);
			$x += 35;
		}

		// Compute card qty in main deck and sideboard.
		$maindeckCards = array();
		$n = count($this->cards);
		if ($n >= 75) $n -= 15;
		for ($i = 0; $i < $n; $i++) {
			$card = $this->cards[$i];
			$title = strtolower($card->title);
			if (!@$maindeckCards[$title]) $maindeckCards[$title] = 0;
			$maindeckCards[$title]++;
		}
		$sideboardCards = array();
		for ($n = count($this->cards); $i < $n; $i++) {
			$card = $this->cards[$i];
			$title = strtolower($card->title);
			if (!@$sideboardCards[$title]) $sideboardCards[$title] = 0;
			$sideboardCards[$title]++;
		}

		echo '.';

		// Draw main deck cards.
		$baselineStart = $settings['cards.top'] + $this->font('cards')->getHeight();
		$left = $settings['cards.column.1.left'];
		$right = $settings['cards.column.1.right'];
		$baseline = $baselineStart;
		foreach ($maindeckCards as $title => $qty) {
			if ($baseline > $settings['cards.bottom']) {
				if ($left == $settings['cards.column.2.left']) break;
				$left = $settings['cards.column.2.left'];
				$right = $settings['cards.column.2.right'];
				$baseline = $baselineStart;
			}
			$baseline = $this->drawTitle($canvas, $left, $baseline, $right, $qty, $title, $this->font('cards'));
		}
		// Sideboard goes on the second column, or after a blank line if already on the second column.
		if ($left == $settings['cards.column.1.left']) {
			$left = $settings['cards.column.2.left'];
			$right = $settings['cards.column.2.right'];
			$baseline = $baselineStart;
		} else
			$baseline += $this->font('cards')->getLineHeight();
		// Draw sideboard cards.
		foreach ($sideboardCards as $title => $qty) {
			if ($baseline > $settings['cards.bottom']) {
				if ($left == $settings['cards.column.2.left']) break;
				$left = $settings['cards.column.2.left'];
				$right = $settings['cards.column.2.right'];
				$baseline = $baselineStart;
			}
			$color = in_array($title, $illegalSideboardCards) ? 'color:130,130,130' : null;
			$baseline = $this->drawTitle($canvas, $left, $baseline, $right, $qty, $title, $this->font('cards', $color));
		}
		if ($baseline > $settings['cards.bottom'] && $left == $settings['cards.column.2.left']) {
			echo "\n";
			warn('Not all cards fit on the decklist card.');
		}

		// Draw card count.
		$count = count($this->cards);
		if ($count >= 75) $count = ($count - 15) . "/15";
		$count = "($count)";
		$width = $this->getTextWidth($count, $this->font('count'));
		$this->drawText($canvas, $settings['count.right'] - $width, $settings['count.y'], null, $count, $this->font('count'));

		echo "\n";
		return $canvas;
	}

	private function drawTitle ($canvas, $left, $baseline, $right, $qty, $title, $font) {
		foreach ($this->cards as $card)
			if (strtolower($card->title) == $title) break;
		// Draw qty.
		$width = $this->getTextWidth($qty . " x", $font);
		$this->drawText($canvas, $left - $width, $baseline, null, $qty . " x", $font);
		// Draw card title.
		$textSize = $this->drawTextWrapped($canvas, $baseline, $left + 10, $right, $card->getDisplayTitle(), $font);
		return $baseline + $textSize['lineCount'] * $font->getLineHeight();
	}

	public function getSettings () {
		global $rendererSettings;
		return $rendererSettings['config/config-decklist.txt'];
	}
}

?>
