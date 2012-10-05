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
class EighthRenderer extends CardRenderer {
	static private $settingSections;
	static private $titleToGuild;
	static private $titleToPhyrexia;
	static private $titleToFrameDir;
	static private $titleToTransform;

	public function render () {
		global $config;

		echo $this->card . '...';
		$card = $this->card;
		$settings = $this->getSettings();
		$frameDir = $this->getFrameDir($card->title, $card->set, $settings);
		$costColors = Card::getCostColors($card->cost);

		$useMulticolorFrame = (strlen($costColors) > 1 && strpos($settings['card.multicolor.frames'], strval(strlen($costColors))) !== false) || ($card->isDualManaCost() && (strpos($settings['card.multicolor.frames'], strval(strlen($costColors))) !== false || strlen($costColors) == 2));
		switch ($frameDir) {
		case "timeshifted": $useMulticolorFrame = false; break;
		case "transform-day": $useMulticolorFrame = false; $pts = explode("|", $card->pt); $card->pt = $pts[0]; break;
		case "transform-night": $useMulticolorFrame = false; break;
		}

		$canvas = imagecreatetruecolor(736, 1050);

		// Art image.
		if($card->isEldrazi())
			$this->drawArt($canvas, $card->artFileName, 0, 0, 1050, 736, !$config['art.keep.aspect.ratio']);
		else
			$this->drawArt($canvas, $card->artFileName, $settings['art.top'], $settings['art.left'], $settings['art.bottom'], $settings['art.right'], !$config['art.keep.aspect.ratio']);

		echo '.';

		// Background image, border image, and grey title/type image.
		$borderImage = null;
		$greyTitleAndTypeOverlay = null;
		if ($card->isLand()) {
			// Land frame.
			$landColors = @$this->writer->titleToLandColors[strtolower($card->title)];
			if ($settings['card.multicolor.fetch.land.frames']) {
				switch (strtolower($card->title)) {
				case 'flooded strand': $landColors = 'WU'; break;
				case 'bloodstained mire': $landColors = 'BR'; break;
				case 'wooded foothills': $landColors = 'RG'; break;
				case 'polluted delta': $landColors = 'UB'; break;
				case 'windswept heath': $landColors = 'GW'; break;
				case 'flood plain': $landColors = 'WU'; break;
				case 'rocky tar pit': $landColors = 'BR'; break;
				case 'mountain valley': $landColors = 'RG'; break;
				case 'bad river': $landColors = 'UB'; break;
				case 'grasslands': $landColors = 'GW'; break;
				}
			}
			switch (strtolower($card->title)) {
				case 'arid mesa': $landColors = 'RW'; break;
				case 'marsh flats': $landColors = 'WB'; break;
				case 'misty rainforest': $landColors = 'GU'; break;
				case 'scalding tarn': $landColors = 'UR'; break;
				case 'verdant catacombs': $landColors = 'BG'; break;
				}
			if (!$landColors) error('Land color missing for card: ' . $card->title);
			if (strlen($landColors) > 1) {
				$useMulticolorLandFrame = strpos($settings['card.multicolor.land.frames'], strval(strlen($landColors))) !== false;
				if (!$useMulticolorLandFrame) $landColors = 'A';
			}
			$bgImage = @imagecreatefrompng("images/eighth/$frameDir/land/$landColors.png");
			if (!$bgImage) error("Background image not found for land color \"$landColors\": " . $card->title);
			// Grey title/type image.
			if (strlen($landColors) >= 2 && $settings['card.multicolor.grey.title.and.type']) {
				$greyTitleAndTypeOverlay = @imagecreatefrompng("images/eighth/$frameDir/cards/C-overlay.png");
				if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
			}
		} else {
			if($card->isEldrazi()) {
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/Eldrazi.png");
			} else if ($card->isArtefact()) {
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/Art.png");
				if(strpos($settings['card.artifact.color'], strval(strlen($costColors))) !== false){
					if (strlen($costColors) >= 2) {
						$useMulticolorFrame = false;
						$borderImage = @imagecreatefrompng("images/eighth/$frameDir/borders/$costColors.png");
					}
					else {
						$useMulticolorFrame = false;
						$borderImage = @imagecreatefrompng("images/eighth/$frameDir/borders/$card->color.png");
					}
				} else if (strlen($costColors) >= 2) {
					$useMulticolorFrame = false;
					$borderImage = @imagecreatefrompng("images/eighth/$frameDir/borders/Gld.png");
				}
			} else if ($useMulticolorFrame) {
				// Multicolor frame.
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/$costColors.png");
				if (!$bgImage) error("Background image not found for color: $costColors");
				// Grey title/type image.
				if ($settings['card.multicolor.grey.title.and.type']) {
					$greyTitleAndTypeOverlay = @imagecreatefrompng("images/eighth/$frameDir/cards/C-overlay.png");
					if (!$greyTitleAndTypeOverlay) error('Image not found: C-overlay.png');
				}
			} else {
				// Mono color frame.
				$bgImage = @imagecreatefrompng("images/eighth/$frameDir/cards/" . $card->color . '.png');
				if (!$bgImage) error('Background image not found for color "' . $card->color . '" in frame dir: ' . $frameDir);
				// Border image.
				if (strlen($costColors) == 2 && $settings['card.multicolor.dual.borders'])
					$borderImage = @imagecreatefrompng("images/eighth/$frameDir/borders/$costColors.png");
			}
		}
		imagecopy($canvas, $bgImage, 0, 0, 0, 0, 736, 1050);
		imagedestroy($bgImage);
		if ($borderImage) {
			imagecopy($canvas, $borderImage, 0, 0, 0, 0, 736, 1050);
			imagedestroy($borderImage);
		}
		if ($greyTitleAndTypeOverlay) {
			imagecopy($canvas, $greyTitleAndTypeOverlay, 0, 0, 0, 0, 736, 1050);
			imagedestroy($greyTitleAndTypeOverlay);
		}

		// Power / toughness.
		if ($card->pt) {
			if($card->isEldrazi()) {
				$image = @imagecreatefrompng("images/eighth/$frameDir/pt/B.png");
			} else if ($useMulticolorFrame)
				$image = @imagecreatefrompng("images/eighth/$frameDir/pt/" . substr($costColors, -1, 1) . '.png');
			else
				$image = @imagecreatefrompng("images/eighth/$frameDir/pt/" . $card->color . '.png');
			if (!$image) error("Power/toughness image not found for color: $color");
			imagecopy($canvas, $image, 0, 1050 - 162, 0, 0, 736, 162);
			imagedestroy($image);
			$this->drawText($canvas, $settings['pt.center.x'], $settings['pt.center.y'], $settings['pt.width'], $card->pt, $this->font('pt'));
		}

		//Transform P/T
		if($frameDir == "transform-day" && isset($pts[1]))
			$this->drawText($canvas, $settings['pt.transform.center.x'], $settings['pt.transform.center.y'], $settings['pt.transform.width'], $pts[1], $this->font('pt.transform'));

		// Casting cost.
		$costLeft = $this->drawCastingCost($canvas, $card->getCostSymbols(), $card->isDualManaCost() ? $settings['cost.top.dual'] : $settings['cost.top'], $settings['cost.right'], $settings['cost.size'], true);

		echo '.';

		// Set and rarity.
		if (!$card->isBasicLand() || $settings['card.basic.land.set.symbols'])
			$rarityLeft = $this->drawRarity($canvas, $card->rarity, $card->set, $settings['rarity.right'], $settings['rarity.center.y'], $settings['rarity.height'], $settings['rarity.width'], false);
		else
			$rarityLeft = $settings['rarity.right'];

		// Card title.
		$this->drawText($canvas, $settings['title.x'], $settings['title.y'], $costLeft - $settings['title.x'], $card->getDisplayTitle(), $this->font('title'));

		echo '.';

		// Type.
		$typex = ($frameDir == "transform-night" && $card->isArtefact()) ? $settings['type.art.x'] : $settings['type.x'];
		$this->drawText($canvas, $typex, $settings['type.y'], $rarityLeft - $settings['type.x'], $card->type, $this->font('type'));

		// Guild sign.
		if($card->set == "DIS"||$card->set == "GP"||$card->set == "GPT"||$card->set == "RAV"||$card->set == "RV") {
			if ($guild = $this->getGuild($card->title)) {
				list($image, $width, $height) = getPNG("images/eighth/guilds/$guild.png", "Guild image not found for: $guild");
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height);
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height); // Too much alpha, need to apply twice.
				imagedestroy($image);
			}
		}

		// Phyrexia/Mirran sign.
		if($card->set == "SOM"||$card->set == "MBS"||$card->set == "NPH") {
			if ($phyrexia = $this->getPhyrexia($card->title)) {
				list($image, $width, $height) = getPNG("images/eighth/phyrexia/$phyrexia.png", "Phyrexia/Mirran image not found for: $phyrexia");
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height);
				imagecopy($canvas, $image, 373 - ($width / 2), 652, 0, 0, $width, $height); // Too much alpha, need to apply twice.
				imagedestroy($image);
			}
		}

		// Promo overlay
		if ($card->promo && !$card->isBasicLand()) {
			list($image, $width, $height) = getPNG('images/promo/' . $card->promo . '.png', 'Promo overlay image not found for: ' . $card->promo);
			imagecopy($canvas, $image, 359 - ($width / 2), 680, 0, 0, $width, $height);
			imagedestroy($image);
		}

		if ($card->isBasicLand()) {
			// Basic land symbol instead of legal text.
			list($image, $width, $height) = getPNG("images/symbols/land/$landColors.png", "Basic land image not found for: images/symbols/land/$landColors.png");
			imagecopy($canvas, $image, 373 - ($width / 2), 660, 0, 0, $width, $height);
			imagedestroy($image);
		} else if ($card->isLand() && strlen($landColors) == 2 && !$card->legal) {
			// Dual land symbol instead of legal text.
			if ($settings['card.dual.land.symbols'] == 1) {
				// Single hybrid symbol.
				list($image, $width, $height) = getPNG("images/symbols/land/$landColors.png", "Dual symbol image not found for: $landColors");
				imagecopy($canvas, $image, 368 - ($width / 2), 680, 0, 0, $width, $height);
				imagedestroy($image);
			} else if ($settings['card.dual.land.symbols'] == 2) {
				// One of each basic symbol.
				$landColor = substr($landColors, 0, 1);
				list($image, $width, $height) = getPNG("images/symbols/land/$landColor.png", 'Basic land image not found for: ' . $card->title);
				imagecopy($canvas, $image, 217 - ($width / 2), 660, 0, 0, $width, $height);
				imagedestroy($image);

				$landColor = substr($landColors, 1, 2);
				list($image, $width, $height) = getPNG("images/symbols/land/$landColor.png", 'Basic land image not found for: ' . $card->title);
				imagecopy($canvas, $image, 519 - ($width / 2), 660, 0, 0, $width, $height);
				imagedestroy($image);
			}
		} else {
			// Legal and flavor text.
			$heightAdjust = $card->pt ? $settings['text.pt.height.adjust'] : 0;
			$this->drawLegalAndFlavorText($canvas, $settings['text.top'], $settings['text.left'], $settings['text.bottom'], $settings['text.right'], $card->legal, $card->flavor, $this->font('text'), $heightAdjust);
		}

		// Artist and copyright.
		// The artist color is white if the frame behind it is black.
		$footerColor = '0,0,0';
		if ($card->isLand())
			$footerColor = '255,255,255';
		else if (($costColors == 'B' || $card->color == 'B') && !$card->isArtefact())
			$footerColor = '255,255,255';
		else if ($card->isEldrazi())
			$footerColor = '255,255,255';
		else if ($useMulticolorFrame) {
			// Only multicolor frames with a bottom left color of black should use a white footer.
			if ((strlen($costColors) <= 2 && substr($costColors, 0, 1) == 'B') || (strlen($costColors) >= 3 && substr($costColors, 2, 1) == 'B'))
				$footerColor = '255,255,255';
		}
		if ($card->artist) {
			if ($settings['card.artist.gears']) {
				$artistSymbol = '{gear}';
			} else {
				$artistSymbol = '{brush}';
			}
			$this->drawText($canvas, $settings['artist.x'], $settings['artist.y'], null, $artistSymbol . $card->artist, $this->font('artist', 'color:' . $footerColor));
		}
		if ($card->copyright) $this->drawText($canvas, $settings['copyright.x'], $settings['copyright.y'], null, $card->copyright, $this->font('copyright', 'color:' . $footerColor));

		echo "\n";
		return $canvas;
	}

	public function getSettings () {
		global $rendererSettings, $rendererSections;
		$settings = $rendererSettings['config/config-eighth.txt'];
		$frameDir = $this->getFrameDir($this->card->title, $this->card->set, $settings);
		$settings = array_merge($settings, $rendererSections['config/config-eighth.txt']['fonts - ' . $frameDir]);
		$settings = array_merge($settings, $rendererSections['config/config-eighth.txt']['layout - ' . $frameDir]);
		return $settings;
	}

	private function getGuild ($title) {
		if (!EighthRenderer::$titleToGuild) EighthRenderer::$titleToGuild = csvToArray('data/eighth/titleToGuild.csv');
		return @EighthRenderer::$titleToGuild[(string)strtolower($title)];
	}

	private function getPhyrexia ($title) {
		if (!EighthRenderer::$titleToPhyrexia) EighthRenderer::$titleToPhyrexia = csvToArray('data/eighth/titleToPhyrexia.csv');
		return @EighthRenderer::$titleToPhyrexia[(string)strtolower($title)];
	}

	private function getFrameDir ($title, $set, $settings) {
		if (!EighthRenderer::$titleToFrameDir) EighthRenderer::$titleToFrameDir = csvToArray('data/eighth/titleToAlternateFrame.csv');
		if (!EighthRenderer::$titleToTransform) EighthRenderer::$titleToTransform = csvToArray('data/eighth/titleToTransform.csv');
		$frameDir = @EighthRenderer::$titleToFrameDir[(string)strtolower($title)];
		if (!$frameDir) $frameDir = 'transform-' . @EighthRenderer::$titleToTransform[(string)strtolower($title)];
		$timeshifted = explode(',', $settings['card.timeshifted.frames']);
		if (!$frameDir || $frameDir == 'transform-') $frameDir = "regular";
		if ($frameDir == 'timeshifted' && in_array($set, $timeshifted) === FALSE) $frameDir = "regular";
		return $frameDir;
	}
}

?>
