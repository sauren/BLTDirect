<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');

if(!isset($cart)){
	$cart = new Cart($session);
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'set', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('country', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
$country = new DataQuery(sprintf("select Country_ID, Country from countries where Allow_Sales='Y' order by Country"));
$form->AddOption('country', '0', 'Select Country');
$form->AddOption('country', '222', 'United Kingdom (inc. Scotland & N. Ireland)');

while($country->Row){
	$form->AddOption('country', $country->Row['Country_ID'], $country->Row['Country']);
	$country->Next();
}
$country->Disconnect();

 
//$disabled = (!param('region'))?'disabled="disabled"': '';

$form->AddField('region', 'Region', 'select', '0', 'numeric_unsigned', 1, 11, false);
$form->AddOption('region', '0', 'Select Region');


//if(param('region') && ($form->GetValue('country') > 0)) {
	$countryId = $form->GetValue('country');
	$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($countryId)));

	while($region->Row){
		$form->AddOption('region', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
	$region->Disconnect();
//}

if(param('confirm')) {
	if($form->Validate()) {
		$cart->Postage = 0;
		$cart->ShipTo = '';
		$cart->ShippingCountry->ID = $form->GetValue('country');
		$cart->ShippingRegion->ID = $form->GetValue('region');
		$cart->Update();

		redirect("Location: cart.php");
	}
}
include("ui/nav.php");
include("ui/search.php");
?>
<script src="../ignition/js/regions.php" type="text/javascript"></script>
<script type="text/javascript">
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
</script>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Tax &amp; Shipping Information</span></div>
    <div class="maincontent">
<div class="maincontent1">
					<p class="breadcrumb"><a href="index.php" title="Light Bulbs, Lamps and Tubes Direct Home Page">Home</a> / <a href="cart.php">Shopping Cart</a></p>

					<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td width="90%">
								<p>We ship products across the UK, Europe and beyond. To get the best deal for our customers we calculate Tax &amp; Shipping costs during the checkout process based on your billing and delivery address.</p>
								<p>If you would like to find out Tax &amp; Shipping costs before checkout please use the &quot;Calculate Tax &amp; Shipping&quot; facility on the right of this page.</p>
								<p>UK customers are liable for VAT.</p>
							</td></tr><tr>
							<td width="90%" valign="top">
								<div id="ShippingCalc">
									<h3 class="blue">Calculate Tax &amp; Shipping</h3>
									<p>Change the delivery location of your shopping cart to calculate Tax &amp; Shipping.
									This facility assumes that your billing and delivery locations are the same. </p>

									<?php
										echo $form->Open();
										echo $form->GetHtml('confirm');
										echo $form->GetHtml('action');
									?>
									<strong><?php echo $form->GetLabel('country'); ?></strong><br/>
									<?php echo $form->GetHtml('country'); ?><br />
									<a href="#" class="smallTxt" onclick="MM_openBrWindow('help_wheres_my_country.php','','status=yes,scrollbars=yes,width=300,height=400')">Where is my Country?</a>						  <br />
									<br />
									<strong><?php echo $form->GetLabel('region'); ?></strong><br />
									<?php echo $form->GetHtml('region'); ?><br />
									<span class="smallTxt">(If your region is not showing please select the nearest region for calculating shipping costs.)</span><br />
									<br />
									<input type="submit" name="submit" value="submit" class="submit" />
									<?php echo $form->Close(); ?>
								</div>
							</td>
						</tr>
					</table>

</div>
</div>
<?php require_once('../lib/common/appFooter.php');