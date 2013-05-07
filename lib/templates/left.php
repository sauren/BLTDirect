<div class="NavLeft" id="LeftNavArea">
	<div class="NavLeftBar NavLeftBarCatalogue">
		<p class="title"><strong><a href="products.php" title="Light Bulb, Lamp and Tube Products">Product Catalogue</a></strong></p>
	</div>

	<div id="LeftPane1">
		<div id="LeftPane1Mask">
			<div class="NavLeftCatalogue" id="LeftPane1Container">
				<ul id="LeftPane1List">
					<?php
					$holidayPromos = new HolidayPromotion();

					$isChristmas = false;
					if($holidayPromos->IsChristmas()) {
						$isChristmas = true;
					}
					
					$isHalloween = false;
					if($holidayPromos->IsHalloween()) {
						$isHalloween = true;
					}
					
					for($i=0; $i<count($GLOBALS['Cache']['Categories']); $i=$i+2) {
						if($isChristmas) {
							echo sprintf('<li><a href=".%s" title="%s"%s>%s</a> </li>', $GLOBALS['Cache']['Categories'][$i+1], htmlentities($GLOBALS['Cache']['Categories'][$i]), (stristr($GLOBALS['Cache']['Categories'][$i], 'Christmas')) ? ' class="christmas"' : '', htmlentities($GLOBALS['Cache']['Categories'][$i]));
						} elseif($isHalloween) {
							echo sprintf('<li><a href=".%s" title="%s"%s>%s</a> </li>', $GLOBALS['Cache']['Categories'][$i+1], htmlentities($GLOBALS['Cache']['Categories'][$i]), (stristr($GLOBALS['Cache']['Categories'][$i], 'Halloween')) ? ' class="halloween"' : '', htmlentities($GLOBALS['Cache']['Categories'][$i]));

						} else {
							
							echo sprintf('<li><a href=".%s" title="%s">%s</a> </li>', $GLOBALS['Cache']['Categories'][$i+1], htmlentities($GLOBALS['Cache']['Categories'][$i]), htmlentities($GLOBALS['Cache']['Categories'][$i]));
						}
					}
					?>
				</ul>
			</div>
		</div>
	</div>
	
	<?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		
		<div class="NavLeftCap" id="LeftPane1Cap"></div>
		
		<div id="LeftOption1" class="NavLeftOption" onmouseover="optionGroup1.expand({element: this});">
			<div class="NavOption NavLeftOptionRed">
				<a href="./offers.php" title="Special Offers on Light Bulbs">Special Offers</a>
			</div>
		</div>
		<div id="LeftOption1Mask">
			<div class="NavLeftOffers" id="LeftOption1Container">
				<div class="NavLeftOfferItem" id="LeftOfferItem"></div>
				<div class="NavLeftOfferImage" id="LeftOfferImage"></div>
				<div class="NavLeftOfferNav" id="LeftOfferNav"></div>

				<div class="NavLeftOffersLink">
					<a href="./offers.php" title="Special Offers from BLT Direct">View All Offers</a>
				</div>
			</div>
		</div>

		<div id="LeftOption2" class="NavLeftOption" onmouseover="optionGroup1.expand({element: this});">
			<div class="NavOption NavLeftOptionBlue">
				<a href="./lampBaseExamples.php" title="Find the Lamp Base you Require">What Base?</a>
			</div>
		</div>
		<div id="LeftOption2Mask" class="panelMinimise">
			<div class="NavLeftBase" id="LeftOption2Container">
				<div class="NavLeftBaseLink">
					<a href="./lampBaseExamples.php" title="Find the Lamp Base you Require">Bulb Base Examples</a>
				</div>
				<div class="NavLeftBaseLink">
					<a href="./fluorescent_tubes.php" title="Fluorescent Tube Finder">Tube Finder</a>
				</div>
			</div>
		</div>

		<div id="LeftOption3" class="NavLeftOption" onmouseover="optionGroup1.expand({element: this});">
			<div class="NavOption NavLeftOptionPink">
				<a href="./fluorescent_tubes.php" title="Fluorescent Tube Finder">Tube Finder</a>
			</div>
		</div>
		<div id="LeftOption3Mask" class="panelMinimise">
			<div class="NavLeftTube" id="LeftOption3Container">
				<div class="NavLeftTubeLink">
					<a href="./fluorescent_tubes.php" title="Fluorescent Tube Finder">Find The Right Tube</a>
				</div>
				<div class="NavLeftTubeLink">
					<a href="./lampBaseExamples.php" title="Find the Lamp Base you Require">Find The Right Base</a>
				</div>
			</div>
		</div>

		<div id="LeftOption4" class="NavLeftOption" onmouseover="optionGroup1.expand({element: this});">
			<div class="NavOption NavLeftOptionGreen">
				<a href="./energy-saving-bulbs.php" title="Energy Saving Light Bulbs">Energy Saving</a>
			</div>
		</div>
		<div id="LeftOption4Mask" class="panelMinimise">
			<div class="NavLeftEnergy" id="LeftOption4Container">
				<div class="NavLeftEnergyLink">
					<a href=".<?php echo Category::GetCategory(15)->GetUrl(); ?>" title="<?php echo Category::GetCategory(15)->Name; ?>">Energy Saving Bulbs</a>
				</div>
				<div class="NavLeftEnergyLink">
					<a href="./energy-saving-bulbs.php" title="Calculate Your Savings">Calculate Savings</a>
				</div>
				<div class="NavLeftEnergyLink">
					<a href="./energy-saving-bulbs.php" title="Energy Saving Light Bulb Equivalent Wattages">Equivalent Wattages</a>
				</div>
			</div>
		</div>

		<div id="LeftOption5" class="NavLeftOption" onmouseover="optionGroup1.expand({element: this});">
			<div class="NavOption NavLeftOptionTurquoise">
				<a href="./lampColourTemperatures.php" title="Lamp Colour Temperature Guide">Colour Temperatures</a>
			</div>
		</div>
		<div id="LeftOption5Mask" class="panelMinimise">
			<div class="NavLeftTemperature" id="LeftOption5Container">
				<div class="NavLeftTemperatureLink">
					<a href="./lampColourTemperatures.php" title="Light Bulb Colour Temperature Examples">Colour Temperatures</a>
				</div>
				<div class="NavLeftTemperatureLink">
					<a href="./search.php?search=daylight" title="Search for Daylight Light Bulbs">Daylight Lamps</a>
				</div>
			</div>
		</div>

		<div id="LeftOption6" class="NavLeftOption" onmouseover="optionGroup1.expand({element: this});">
			<div class="NavOption NavLeftOptionBrown">
				<a href="./beamangles.php" title="Beam Angles">Beam Angles</a>
			</div>
		</div>
		<div id="LeftOption6Mask" class="panelMinimise">
			<div class="NavLeftBeam" id="LeftOption6Container">
				<div class="NavLeftBeamLink">
					<a href="./beamangles.php" title="Beam Angles">Beam Angles</a>
				</div>
			</div>
		</div>
		
		<?php
	}
	?>
	
</div>