<?php
	/** Libchart - PHP chart library
	*
	* Copyright (C) 2005-2006 Jean-Marc Trémeaux (jm.tremeaux at gmail.com)
	*
	* This library is free software; you can redistribute it and/or
	* modify it under the terms of the GNU Lesser General Public
	* License as published by the Free Software Foundation; either
	* version 2.1 of the License, or (at your option) any later version.
	*
	* This library is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	* Lesser General Public License for more details.
	*
	* You should have received a copy of the GNU Lesser General Public
	* License along with this library; if not, write to the Free Software
	* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*
	*/

	/**
	* Line chart
	*
	* @author   Jean-Marc Trémeaux (jm.tremeaux at gmail.com)
	*/

	class LineChart extends BarChart
	{
		/**
		* Creates a new line chart
		*
		* @access	public
    		* @param	integer		width of the image
    		* @param	integer		height of the image
		*/

		function LineChart($width = 600, $height = 250, $legend = null)
		{
			parent::BarChart($width, $height);

			$this->Legend = $legend;
			$this->setLabelMarginLeft(50);
			$this->setLabelMarginRight(!is_null($this->Legend) ? 150 : 50);
			$this->setLabelMarginTop(40);
			$this->setLabelMarginBottom(50);
		}

		/**
		* Print the axis
		*
		* @access	private
		*/

		function printAxis()
		{
			// Check if some points were defined

			if($this->sampleCount < 2)
				return;

			$minValue = $this->axis->getLowerBoundary();
			$maxValue = $this->axis->getUpperBoundary();
			$stepValue = $this->axis->getTics();

			// Line axis

			for($value = $minValue; $value <= $maxValue; $value += $stepValue)
			{
				$y = $this->graphBRY - ($value - $minValue) * ($this->graphBRY - $this->graphTLY) / ($this->axis->displayDelta);

				imagerectangle($this->img, $this->graphTLX, $y, $this->graphBRX, $y, $this->horizontalLineColor->getColor($this->img));

				$this->text->printText($this->img, $this->graphTLX - 5, $y, $this->textColor, $value, $this->text->fontCondensed, $this->text->HORIZONTAL_RIGHT_ALIGN | $this->text->VERTICAL_CENTER_ALIGN);
			}


			// Horizontal Axis

			$columnWidth = ($this->graphBRX - $this->graphTLX) / ($this->sampleCount - 1);

			reset($this->point);

			for($i = 0; $i < $this->sampleCount; $i++)
			{
				$x = $this->graphTLX + $i * $columnWidth;

				imagerectangle($this->img, $x - 1, $this->graphBRY + 2, $x, $this->graphBRY + 3, $this->axisColor1->getColor($this->img));
				imagerectangle($this->img, $x - 1, $this->graphBRY, $x, $this->graphBRY + 1, $this->axisColor2->getColor($this->img));

				$point = current($this->point);
				next($this->point);

				$text = $point->getX();

				if($this->ShowLabels && (($i % $this->LabelInterval) == 0)) {
					$this->text->printDiagonal($this->img, $x - 5, $this->graphBRY + 10, $this->textColor, $text);
				}
			}

			for($i = 0; $i < $this->sampleCount; $i++) {
				for($j = 0; $j < count($this->segment); $j++) {
					if(($this->segment[$j]->getAfter() == ($i + 1)) && ($this->segment[$j]->getAfter() < $this->sampleCount)) {
						$x = $this->graphTLX + $i * $columnWidth;
						$x2 = $x + ($columnWidth / 2);

						imagerectangle($this->img, $x2, $this->graphTLY, $x2, $this->graphBRY, $this->axisColor1->getColor($this->img));
					}
				}
			}
		}

		/**
		* Print the lines
		*
		* @access	private
		*/

		function printLine()
		{
			// Check if some points were defined
			if($this->sampleCount < 2)
				return;

			reset($this->point);

			$minValue = $this->axis->getLowerBoundary();
			$maxValue = $this->axis->getUpperBoundary();

			$columnWidth = ($this->graphBRX - $this->graphTLX) / ($this->sampleCount - 1);

			$x1 = null;
			$y1 = array();

			for($i = 0; $i < $this->sampleCount; $i++)
			{
				$x2 = $this->graphTLX + $i * $columnWidth;

				$point = current($this->point);
				next($this->point);
				$values = $point->getY();

				$smallestY2 = $this->graphBRY;

				for($j = 0; $j<count($values);$j++){
					$value = $values[$j];

					$y2 = $this->graphBRY - ($value - $minValue) * ($this->graphBRY - $this->graphTLY) / ($this->axis->displayDelta);

					if($y2 < $smallestY2) {
						$smallestY2 = $y2;
					}
				}

				if(($x2 > $this->graphTLX) && ($x2 < $this->graphBRX) && ($smallestY2 < $this->graphBRY)) {
					imagerectangle($this->img, $x2, $smallestY2, $x2, $this->graphBRY, $this->verticalLineColor->getColor($this->img));
				}

				for($j = 0; $j<count($values);$j++){
					$colorIndex = $j;
					$colorLimit = count($this->barColors);

					if($colorIndex >= $colorLimit) {
						$colorHigh = floor($colorIndex / $colorLimit);
						$colorMulti = $colorLimit * $colorHigh;
						$colorIndex -= $colorMulti;
					}

					$value = $values[$j];

					$y2 = $this->graphBRY - ($value - $minValue) * ($this->graphBRY - $this->graphTLY) / ($this->axis->displayDelta);

					if($this->ShowText) {
						if($value > 0) {
							if($this->ShortenValues) {
								$value = (round($value / 1000) >= 1) ? sprintf('%sK', number_format(round($value / 1000), 0, '.', '')) : $value;
							}

							$this->text->printVerticalText($this->img, $x2, $y2 - 5, $this->textColor, $value, $this->text->fontCondensed, $this->text->HORIZONTAL_CENTER_ALIGN | $this->text->VERTICAL_BOTTOM_ALIGN);
						}
					}

					if($x1)
					{
						$this->primitive->line($x1, $y1[$j], $x2, $y2,$this->barColors[$colorIndex][1], 4);
						$this->primitive->line($x1, $y1[$j] - 1, $x2, $y2 - 1, $this->barColors[$colorIndex][0], 2);
					}


					$y1[$j] = $y2;
				}
				$x1 = $x2;
			}
		}

		function printKey(){
			$startLeg = $this->graphBRX+10;
			$this->text->PrintText($this->img,$startLeg + 150/2,$this->margin+90,$this->textColor,"Legend",$this->text->fontCondensedBold, $this->text->HORIZONTAL_CENTER_ALIGN | $this->text->VERTICAL_BOTTOM_ALIGN);
			$currentLeg = $this->margin + 90;

			for($i = 0; $i<count($this->Legend);$i++){
				$colorIndex = $i;
				$colorLimit = count($this->barColors);

				if($colorIndex >= $colorLimit) {
					$colorHigh = floor($colorIndex / $colorLimit);
					$colorMulti = $colorLimit * $colorHigh;
					$colorIndex -= $colorMulti;
				}

				$textLen = (strlen($this->Legend[$i])*10)*5/8;
				//$textLen = $textLen - (strlen($this->Legend[$i]));
				imagefilledrectangle($this->img, $startLeg, $currentLeg,$startLeg+10 , $currentLeg+10, $this->barColors[$colorIndex][1]->getColor($this->img));
				$this->text->printtext($this->img,$startLeg+15,$currentLeg + 10,$this->textColor,$this->Legend[$i], $this->text->fontCondensed, $this->text->HORIZONTAL_LEFT_ALIGN | $this->text->VERTICAL_BOTTOM_ALIGN);
				$currentLeg += 15;
			}
		}

		function createImage()
		{
			parent::createImage();

			$this->verticalLineColor = new Color(0, 0, 0, 248);
			$this->horizontalLineColor = new Color(0, 0, 0, 224);
		}



		/**
		* Render the chart image
		*
		* @access	public
		* @param	string		name of the file to render the image to (optional)
		*/

		function render($fileName = null)
		{
			$this->computeBound();
			$this->computeLabelMargin();
			$this->createImage();
			$this->printLogo();
			$this->printTitle();
			$this->printAxis();
			$this->printLine();

			if(!is_null($this->Legend)) {
				$this->printKey();
			}

			if(isset($fileName))
				imagepng($this->img, $fileName);
			else
				imagepng($this->img);
		}
	}
?>