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
	* Vertical bar chart
	*
	* @author   Jean-Marc Trémeaux (jm.tremeaux at gmail.com)
	*/

	class VerticalChart extends BarChart
	{
		/**
		* Creates a new vertical bar chart
		*
		* @access	public
    		* @param	integer		width of the image
    		* @param	integer		height of the image
		*/

		function VerticalChart($width = 600, $height = 250, $legend)
		{
			parent::BarChart($width, $height);
			$this->Legend=$legend;
			$this->setLabelMarginLeft(50);
			$this->setLabelMarginRight(150);
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

			if(!$this->sampleCount)
				return;

			$minValue = $this->axis->getLowerBoundary();
			$maxValue = $this->axis->getUpperBoundary();
			$stepValue = $this->axis->getTics();

			// Vertical axis

			for($value = $minValue; $value <= $maxValue; $value += $stepValue)
			{
				$y = $this->graphBRY - ($value - $minValue) * ($this->graphBRY - $this->graphTLY) / ($this->axis->displayDelta);

				//imagerectangle($this->img, $this->graphTLX - 3, $y, $this->graphTLX - 2, $y + 1, $this->axisColor1->getColor($this->img));
				//imagerectangle($this->img, $this->graphTLX - 1, $y, $this->graphTLX, $y + 1, $this->axisColor2->getColor($this->img));

				imagerectangle($this->img, $this->graphTLX, $y, $this->graphBRX, $y, $this->horizontalLineColor->getColor($this->img));

				$this->text->printText($this->img, $this->graphTLX - 5, $y, $this->textColor, $value, $this->text->fontCondensed, $this->text->HORIZONTAL_RIGHT_ALIGN | $this->text->VERTICAL_CENTER_ALIGN);
			}

			// Horizontal Axis

			$columnWidth = ($this->graphBRX - $this->graphTLX) / $this->sampleCount;

			reset($this->point);

			for($i = 0; $i <= $this->sampleCount; $i++)
			{
				$x = $this->graphTLX + $i * $columnWidth;

				imagerectangle($this->img, $x - 1, $this->graphBRY + 2, $x, $this->graphBRY + 3, $this->axisColor1->getColor($this->img));
				imagerectangle($this->img, $x - 1, $this->graphBRY, $x, $this->graphBRY + 1, $this->axisColor2->getColor($this->img));

				if($i < $this->sampleCount)
				{
					$point = current($this->point);
					next($this->point);

					$text = $point->getX();

					if($this->ShowLabels && (($i % $this->LabelInterval) == 0)) {
						$this->text->printDiagonal($this->img, $x + $columnWidth * 1 / 3, $this->graphBRY + 10, $this->textColor, $text);
					}
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
		* Print the bars
		*
		* @access	private
		*/

		function printBar()
		{
			// Check if some points were defined

			if(!$this->sampleCount)
				return;

			reset($this->point);

			$minValue = $this->axis->getLowerBoundary();
			$maxValue = $this->axis->getUpperBoundary();
			$stepValue = $this->axis->getTics();

			$columnWidth = ($this->graphBRX - $this->graphTLX) / $this->sampleCount;

			for($i = 0; $i < $this->sampleCount; $i++)
			{
				$x = $this->graphTLX + $i * ($this->graphBRX - $this->graphTLX) / $this->sampleCount;

				$point = current($this->point);
				next($this->point);

				$values = $point->getY();
				//echo count ($values);*/

				for($j = 0; $j<count($values);$j++){
					$value = $values[$j];
					$ymin = $this->graphBRY - ($value - $minValue) * ($this->graphBRY - $this->graphTLY) / ($this->axis->displayDelta);
					$newColWidth = (($columnWidth*4/5)-($columnWidth * 1/5)) / count($values);

					if($this->ShowText) {
						if($value > 0) {
							if($this->ShortenValues) {
								$value = (round($value / 1000) >= 1) ? sprintf('%sK', number_format(round($value / 1000), 0, '.', '')) : $value;
							}

							//$this->text->printText($this->img, $x + (($columnWidth*1/5)+(($j+1)*$newColWidth)-($newColWidth/2)), $ymin - 5, $this->textColor, $value, $this->text->fontCondensed, $this->text->HORIZONTAL_CENTER_ALIGN | $this->text->VERTICAL_BOTTOM_ALIGN);
							$this->text->printVerticalText($this->img, $x + (($columnWidth*1/5)+(($j+1)*$newColWidth)-($newColWidth/2)), $ymin - 5, $this->textColor, $value, $this->text->fontCondensed, $this->text->HORIZONTAL_CENTER_ALIGN | $this->text->VERTICAL_BOTTOM_ALIGN);
						}
					}

					// Vertical bar

					$x1 = $x + $columnWidth * 1 / 5;
					$x2 = $x + $columnWidth * 4 / 5;


					$diff = ($x2-$x1)/count($values);

					$x1 += ($j*$diff);
					$x2 = $x1+$diff;

					$col = bcmod($j,15);
					imagefilledrectangle($this->img, $x1, $ymin, $x2, $this->graphBRY - 1, $this->barColors[$col][1]->getColor($this->img));
					imagefilledrectangle($this->img, $x1 + 1, $ymin + 1, $x2 - 4, $this->graphBRY - 1, $this->barColors[$col][0]->getColor($this->img));
				}
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
		function printKey(){
			$startLeg = $this->graphBRX+10;
			$this->text->PrintText($this->img,$startLeg + 150/2,$this->margin+90,$this->textColor,"Legend",$this->text->fontCondensedBold, $this->text->HORIZONTAL_CENTER_ALIGN | $this->text->VERTICAL_BOTTOM_ALIGN);
			$currentLeg = $this->margin + 90;
			for($i = 0; $i<count($this->Legend);$i++){
				$textLen = (strlen($this->Legend[$i])*10)*5/8;
				//$textLen = $textLen - (strlen($this->Legend[$i]));
				imagefilledrectangle($this->img, $startLeg, $currentLeg,$startLeg+10 , $currentLeg+10, $this->barColors[$i][1]->getColor($this->img));
				$this->text->printtext($this->img,$startLeg+15,$currentLeg + 10,$this->textColor,$this->Legend[$i], $this->text->fontCondensed, $this->text->HORIZONTAL_LEFT_ALIGN | $this->text->VERTICAL_BOTTOM_ALIGN);
				$currentLeg += 15;
			}

		}

		function render($fileName = null)
		{
			$this->computeBound();
			$this->computeLabelMargin();
			$this->createImage();
			$this->printLogo();
			$this->printTitle();
			$this->printAxis();
			$this->printBar();
			if(!is_null($this->Legend))
			$this->printKey();
			if(isset($fileName))
				imagepng($this->img, $fileName);
			else
				imagepng($this->img);
		}
	}
?>