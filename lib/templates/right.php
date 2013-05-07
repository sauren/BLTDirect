<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$groups = array();
$groups[223] = false;
$groups[224] = false;
$groups[221] = false;
$groups[41] = false;

$zeroMatch = 'Sorry, no matches found.';

$specificationGroups = array();
$specificationValues = array();

foreach($groups as $groupId=>$groupData) {
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM product_specification_combine WHERE productSpecificationGroupId=%d", mysql_real_escape_string($groupId)));	
	if($data->Row['count'] > 0) {
		$groups[$groupId] = true;
	}
	$data->Disconnect();
}

$uncombinedGroups = array();

foreach($groups as $groupId=>$groupData) {
	$uncombinedGroups[] = $groupId;
}

$data = new DataQuery(sprintf("SELECT Group_ID, Name, Data_Type FROM product_specification_group WHERE Group_ID IN (%s) ORDER BY Name ASC", implode(', ', $uncombinedGroups)));
while($data->Row) {
	$specificationGroups[$data->Row['Group_ID']] = $data->Row;
	$specificationValues[$data->Row['Group_ID']] = array();
	
	if(!$groups[$data->Row['Group_ID']]) {
		$data2 = new DataQuery(sprintf("SELECT psv.Group_ID, psv.Value_ID, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification_value AS psv INNER JOIN product_specification AS ps ON ps.Value_ID=psv.Value_ID INNER JOIN product_specification_group AS psg ON psg.Group_ID=psv.Group_ID WHERE psv.Group_ID=%d AND psv.Hide='N' GROUP BY psv.Value_ID ORDER BY Value ASC", $data->Row['Group_ID']));
		while($data2->Row) {
			$specificationValues[$data->Row['Group_ID']][] = $data2->Row;
			
			$data2->Next();
		}
		$data2->Disconnect();
	} else {
		$data2 = new DataQuery(sprintf("SELECT id, name FROM product_specification_combine WHERE productSpecificationGroupId=%d ORDER BY name ASC", $data->Row['Group_ID']));
		while($data2->Row) {
			$specificationValues[$data->Row['Group_ID']][] = $data2->Row;
			
			$data2->Next();
		}
		$data2->Disconnect();
	}

	if($data->Row['Data_Type'] == 'numeric') {
		$sortArray = array();
		$cacheArray = $specificationValues[$data->Row['Group_ID']];
		
		for($j=0; $j<count($specificationValues[$data->Row['Group_ID']]); $j++) {
			$sortArray[$specificationValues[$data->Row['Group_ID']][$j]['Value_ID']] = $specificationValues[$data->Row['Group_ID']][$j]['Value'];
		}

		asort($sortArray, SORT_NUMERIC);
		
		$specificationValues[$data->Row['Group_ID']] = array();
		
		foreach($sortArray as $valueId=>$value) {
			for($j=0; $j<count($cacheArray); $j++) {
				if($cacheArray[$j]['Value_ID'] == $valueId) {
					$specificationValues[$data->Row['Group_ID']][] = $cacheArray[$j];
					break;
				}
			}
		}
	}
	
	$data->Next();
}
$data->Disconnect();

$formBulbFinder = new Form('finder.php', 'get', 'formbulbfinder');
$formBulbFinder->AddField('confirm', 'Confirm', 'hidden-basic', 'true', 'alpha', 4, 4);

foreach($specificationGroups as $group) {
	$formBulbFinder->AddField('finder_group_' . $group['Group_ID'], $group['Name'], 'select', '0', 'numeric_unsigned', 1, 11, false, 'onchange="getRightFinderResults(this);" style="width: 100%;"');
	$formBulbFinder->SetValue('finder_group_' . $group['Group_ID'], '0');
	$formBulbFinder->AddOption('finder_group_' . $group['Group_ID'], '0', '');
	
	foreach($specificationValues[$group['Group_ID']] as $value) {
		if(!$groups[$group['Group_ID']]) {
			$formBulbFinder->AddOption('finder_group_' . $group['Group_ID'], $value['Value_ID'], $value['UnitValue']);
		} else {
			$formBulbFinder->AddOption('finder_group_' . $group['Group_ID'], $value['id'], $value['name']);
		}
	}	
}

$sql = '';
$sqlTotalRows = '';

$productPrices = array();
$productOffers = array();

$totalResults = 0;
?>

<?php /*
<script type="text/javascript">
<script type="text/javascript">
	jQuery(function($){
		$('#formbulbfinder option:selected').removeAttr('selected');
	});
</script>
	jQuery(function($){

		$('#formbulbfinder option:selected').removeAttr('selected');

		var searchId=["223","224","221", "41"];
		
		jQuery.each(searchId, function() {
			
			var select = "#finder_group_" + this;
			
			$(select).live('change',function(e){

				var str = "";

				$("select option:selected").each(function () {
					str += $(this).val() + " ";
					console.log(str);
				});
			});
		});
	});
</script>
*/ ?>

<div class="NavRight" id="RightNavArea">
	<div class="NavRightBar NavRightBarShopping">
		<p class="title"><strong><a href="./cart.php" title="View Your Shopping Cart">Shopping Cart</a></strong></p>
	</div>


	<div id="RightPane1">
		<div id="RightPane1Mask">
			<div class="NavRightCart" id="RightPane1Container">

				<table>

					<?php
					if(count($cart->Line) > 0){
						$subTotal = 0;

						for($i=0; $i < count($cart->Line); $i++){
							$itemTotal = (($cart->Line[$i]->Price-($cart->Line[$i]->Discount/$cart->Line[$i]->Quantity))*$cart->Line[$i]->Quantity);
							$subTotal += $itemTotal;
							?>

							<tr>
								<?php
								if($cart->Line[$i]->Product->ID > 0) {
									?>
									<td class="product" colspan="2"><a href="./product.php?pid=<?php echo $cart->Line[$i]->Product->ID; ?>" title="Click to View Product"><?php echo $cart->Line[$i]->Product->Name; ?></a></td>
									<?php
								} else {
									?>
									<td class="product" colspan="2"><?php echo $cart->Line[$i]->AssociativeProductTitle; ?></td>
									<?php
								}
								?>
							</tr>
							<tr>
								<td class="qty"><a href="./cart.php" title="Click to Edit Cart">Qty <?php echo $cart->Line[$i]->Quantity; ?> x</a></td>
								<td class="price">&pound;<?php echo number_format($itemTotal, 2, '.', ','); ?></td>
							</tr>

							<?php
						}

						$unassociatedProducts = 0;

						for($i=0;$i<count($cart->Line);$i++) {
							if($cart->Line[$i]->Product->ID == 0) {
								$unassociatedProducts++;
							}
						}

						if($unassociatedProducts > 0) {
							$session->Customer->AvailableDiscountReward = 0;
						}

						if($session->Customer->AvailableDiscountReward > 0){
							$discount = $session->Customer->AvailableDiscountReward;
							if(($cart->SubTotal-$cart->Discount) < $discount) {
								$discount = ($cart->SubTotal-$cart->Discount);
							}

							$subTotal = ($cart->SubTotal-$cart->Discount)-$session->Customer->AvailableDiscountReward;
							if($subTotal < 0) {
								$subTotal = 0;
							}

							$remaining = $session->Customer->AvailableDiscountReward-($cart->SubTotal-$cart->Discount);
							if($remaining < 0) {
								$remaining = 0;
							}

							$taxTotal = $cart->CalculateCustomTax($subTotal+$cart->ShippingTotal);
							?>

							<tr>
								<td class="product" colspan="2" style="color: #f00;">Discount Reward</td>
							</tr>
							<tr>
								<td class="qty" style="color: #f00;">&pound;<?php echo number_format($session->Customer->AvailableDiscountReward, 2, '.', ','); ?></td>
								<td class="price" style="color: #f00;">-&pound;<?php echo number_format($discount, 2, '.', ','); ?></td>
							</tr>

							<?php
						}
					} else {
						?>

						<tr>
							<td class="qty">Your Cart is Empty</td>
						</tr>

						<?php
					}

					if(count($cart->Line) > 0) {
						if(!$cart->Error) {
							?>

							<tr>
								<td class="subTotal">Subtotal</td>
								<td class="subTotalPrice">&pound;<?php echo number_format($subTotal, 2, '.', ','); ?></td>
							</tr>
							<tr>
								<td class="shipping">Shipping</td>
								<td class="shippingPrice">&pound;<?php echo number_format($cart->ShippingTotal, 2, '.', ','); ?></td>
							</tr>

							<?php
							if($session->Customer->AvailableDiscountReward > 0){
								?>

								<tr>
									<td class="tax">Pre VAT</td>
									<td class="taxPrice">&pound;<?php echo number_format($subTotal+$cart->ShippingTotal, 2, '.', ','); ?></td>
								</tr>
								<tr>
									<td class="tax">VAT</td>
									<td class="taxPrice">&pound;<?php echo number_format($taxTotal, 2, '.', ','); ?></td>
								</tr>
								<tr>
									<td class="total">Total</td>
									<td class="totalPrice">&pound;<?php echo number_format($subTotal+$cart->ShippingTotal+$taxTotal, 2, '.', ','); ?></td>
								</tr>

								<?php
							} else {
								?>

								<tr>
									<td class="tax">Pre VAT</td>
									<td class="taxPrice">&pound;<?php echo number_format($cart->Total-$cart->TaxTotal, 2, '.', ','); ?></td>
								</tr>
								<tr>
									<td class="tax">VAT</td>
									<td class="taxPrice">&pound;<?php echo number_format($cart->TaxTotal, 2, '.', ','); ?></td>
								</tr>
								<tr>
									<td class="total">Total</td>
									<td class="totalPrice">&pound;<?php echo number_format($cart->Total, 2, '.', ','); ?></td>
								</tr>

								<?php
							}

							if(!empty($cart->Discount)) {
								?>

								<tr>
									<td class="discount"><em>Savings</em></td>
									<td class="discountPrice" align="right"><em>&pound;<?php echo number_format($cart->Discount, 2, '.', ','); ?></em></td>
								</tr>

								<?php
							}
						}
						?>

						<tr>
							<td class="buttons" colspan="2">
								<form method="post" action="./cart.php">
									<input type="submit" name="View" value="View" class="greySubmit" title="View Your Shopping Cart" />
								</form>

								<form method="post" action="./checkout.php">
									<input type="hidden" name="MiniCart" value="true" />
									<input type="submit" name="Checkout" value="Checkout" class="<?php echo (count($cart->Line) > 0)?'submit':'greySubmit'; ?>" title="Checkout Your Shopping Cart" />
								</form>
							</td>
						</tr>

						<?php
					}
					?>

				</table>

			</div>
		</div>
	</div>
	
	<div class="NavRightBar NavRightBarFinder">
		<p class="title"><strong><a href="./finder.php" title="Use Our Simple Bulb Finder">Bulb Finder</a></strong></p>
	</div>
	
	<div id="RightPane2">
		<div class="NavRightFinder bulbFinderForm">
			
			<?php
			echo $formBulbFinder->Open();
			echo $formBulbFinder->GetHTML('confirm');

			foreach($groups as $groupId=>$groupData) {
				echo sprintf('<div class="bulbFinderSelect_%d">%s<br />%s</div>', $groupId, $formBulbFinder->GetLabel('finder_group_' . $groupId), $formBulbFinder->GetHTML('finder_group_' . $groupId));
			}
			?>
				
			<div <?php echo empty($sql) ? 'style="display: none;"' : ''; ?> class="right-results">
				
				<div class="loader"></div>
				<div class="results">
					<div class="spacer">
						<strong>Results</strong><br />
						<span class="right-results-matches"><?php echo ($totalResults > 0) ? sprintf('%d matches', $totalResults) : $zeroMatch; ?></span>
					</div>
					

					<div class="spacer align-right right-results-show" <?php echo ($totalResults > 0) ? '' : 'style="display: none;"'; ?>>
						<input type="submit" class="submit" name="search" value="Show Bulbs" />
					</div>
				</div>
				
				<div class="spacer align-center">
					<small><a href="search.php?show=advanced">Looking for something different?</a></small>
				</div>
			</div>
				
			<?php
				echo $formBulbFinder->Close();
			?>
			
		</div>
	</div>
	
	<div class="NavRightBar NavRightBarBarcode">
		<p class="title"><strong><a href="./searchbarcodes.php" title="Search">Barcode Search</a></strong></p>
	</div>
	
	<div id="RightPane3">
		<div class="NavRightBarcode">
			<a href="./searchbarcodes.php" title="Search Barcodes"><img src="images/template/bg_navRight_barcode_1.jpg" alt="Search Barcodes"/></a>
		</div>
	</div>
	
	<?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		
		<div id="RightOption1" class="NavRightOption" onmouseover="optionGroup2.expand({element: this});">
			<div class="NavOption NavRightOptionOrange">
				<a href="./deliveryRates.php" title="Free Shipping from BLT Direct">Free Shipping</a>
			</div>
		</div>
		<div id="RightOption1Mask" class="panelMinimise">
			<div class="NavRightDelivery" id="RightOption1Container">
				<div class="NavRightDeliveryLink">
					<a href="./deliveryRates.php" title="BLT Direct UK Delivery Rates">UK Deliveries</a>
				</div>
				<div class="NavRightDeliveryLink">
					<a href="./deliveryRates.php#International" title="BLT Direct International Rates">International</a>
				</div>
			</div>
		</div>

		<div id="RightOption2" class="NavRightOption" onmouseover="optionGroup2.expand({element: this});">
			<div class="NavOption NavRightOptionYellow">
				<a href="./security.php" title="Shop Safely with BLT Direct">Shop Safely</a>
			</div>
		</div>
		<div id="RightOption2Mask">
			<div class="NavRightSecurity" id="RightOption2Container">
				<div class="NavRightSecurityMcAfee">
					<a target="_blank" href="https://www.mcafeesecure.com/RatingVerify?ref=www.bltdirect.com" title="McAfee Secured"><img width="94" height="54" src="//images.scanalert.com/meter/www.bltdirect.com/23.gif" alt="McAfee Secure sites help keep you safe from identity theft, credit card fraud, spyware, spam, viruses and online scams" /></a>
				</div>

				<div class="NavRightSecurityLink">
					<a href="https://www.mcafeesecure.com/RatingVerify?ref=www.bltdirect.com" title="McAfee Secured">McAfee Secured</a>
				</div>
				<div class="NavRightSecurityLink">
					<a href="./security.php" title="Security at BLT Direct">Security Policy</a>
				</div>
			</div>
		</div>
		
		<?php
		if(!empty($GLOBALS['Cache']['Brochure']->Image->FileName) && file_exists($GLOBALS['BROCHURE_MENU_IMAGE_DIR_FS'].$GLOBALS['Cache']['Brochure']->Image->FileName)) {
			?>

			<div class="NavRightBrochure">
				<div><img src="<?php echo $GLOBALS['BROCHURE_MENU_IMAGE_DIR_WS'].$GLOBALS['Cache']['Brochure']->Image->FileName; ?>" alt="Download <?php echo $GLOBALS['Cache']['Brochure']->Name; ?>" /></div>
				<div><a href="<?php echo $GLOBALS['BROCHURE_DOWNLOAD_DIR_WS'].$GLOBALS['Cache']['Brochure']->Download->FileName; ?>" title="Download <?php echo $GLOBALS['Cache']['Brochure']->Name; ?>" target="_blank"><img src="./images/template/bg_navRight_download_1.jpg" alt="Download <?php echo $GLOBALS['Cache']['Brochure']->Name; ?>" width="150" height="24" /></a></div>
			</div>

			<?php
		}
		?>

		<div><img src="./images/template/bg_navRight_subscribe_2.jpg" alt="Subscribe to BLT Direct" width="150" height="98" /></div>
		<div><a href="./subscribe.php" title="Subscribe to BLT Direct"><img src="./images/template/bg_navRight_subscribe_1.jpg" alt="Subscribe to BLT Direct" width="150" height="24" /></a></div>
		
		<br />
		
		<div class="align-center">
			<a href="http://twitter.com/bltdirect" target="_blank"><img src="./images/template/logo_twitter_1.png" alt="Visit Us at Twitter" /></a>
		</div>
		
		<?php
	} else {
		?>
		
		<div class="NavRightBar">
			<p class="title"><strong>Information Links</strong></p>
		</div>

		<div class="NavRightCatalogue">
			<ul>
				<li><a href="./lampBaseExamples.php" title="Find the Lamp Base you Require">What Base?</a> </li>
				<li><a href="./fluorescent_tubes.php" title="Fluorescent Tube Finder">Tube Finder</a> </li>
				<li><a href="./energy-saving-bulbs.php" title="Energy Saving Light Bulbs">Energy Saving</a> </li>
				<li><a href="./lampColourTemperatures.php" title="Lamp Colour Temperature Guide">Colour Temperatures</a> </li>
				<li><a href="./beamangles.php" title="Beam Angles">Beam Angles</a> </li>
			</ul>
		</div>
	
		<?php
	}
	?>
	
</div>

<script type="text/javascript">
	using("mootools.XHR");
</script>
<script type="text/javascript">
	var rightFinderGroups = new Array();
	
	<?php
	foreach($specificationGroups as $group) {
		echo sprintf('rightFinderGroups.push(\'bulbFinderSelect_%d\');', $group['Group_ID']);
		echo sprintf('rightFinderGroups.push(%s);', ($groups[$group['Group_ID']]) ? 'true' : 'false');
	}
	?>
</script>
<script type="text/javascript" src="js/right.js"></script>