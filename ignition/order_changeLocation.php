<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');

$session->Secure(2);

if(!isset($cart)){
	$cart = new Cart($session, true);
	$cart->Calculate();
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'set', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('country', 'Country', 'select', $cart->ShippingCountry->ID, 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
$form->AddOption('country', '0', '');
$form->AddOption('country', '222', 'United Kingdom');

$country = new DataQuery(sprintf("select Country_ID, Country from countries where Allow_Sales='Y' order by Country"));
while($country->Row){
	$form->AddOption('country', $country->Row['Country_ID'], $country->Row['Country']);
	$country->Next();
}
$country->Disconnect();

$form->AddField('region', 'Region', 'select', $cart->ShippingRegion->ID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('region', '0', '');

if($form->GetValue('country') > 0) {
	$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($form->GetValue('country'))));
	while($region->Row){
		$form->AddOption('region', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
	$region->Disconnect();
}

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()){
		$cart->ShippingCountry->ID = $form->GetValue('country');
		$cart->ShippingRegion->ID = $form->GetValue('region');
		$cart->Update();
		
		redirect("Location: order_cart.php");
	}
}

$page = new Page('Create a New Order Manually', '');
$page->LinkScript('js/regions.php');
$page->Display('header');
?>
<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top"><h3 class="blue">Calculate Tax &amp; Shipping</h3>
							<p>Change the delivery location of your shopping cart to calculate Tax & Shipping.
							This facility assumes that your billing and delivery locations are the same. </p>

							<?php
							echo $form->Open();
							echo $form->GetHtml('confirm');
							echo $form->GetHtml('action');
							?>
							<strong><?php echo $form->GetLabel('country'); ?></strong><br/>
							<?php echo $form->GetHtml('country'); ?><br />
							If the country does not appear we do not have shipping information available to complete the order.<br />
							<br />
							<strong><?php echo $form->GetLabel('region'); ?></strong><br />
							<?php echo $form->GetHtml('region'); ?><br />
							<br />
							<input type="submit" name="submit" value="submit" class="submit" />
							<?php echo $form->Close(); ?></td>
  </tr>
</table>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>