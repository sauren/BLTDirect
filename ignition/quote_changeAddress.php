<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$session->Secure(3);

$quote = new Quote($_REQUEST['quoteid']);
$quote->GetLines();

if(strtolower($_REQUEST['type']) == 'shipping'){
	$contactTitle = &$quote->Shipping->Title;
	$contactName = &$quote->Shipping->Name;
	$contactInitial = &$quote->Shipping->Initial;
	$contactLast = &$quote->Shipping->LastName;
	$contactOrg = &$quote->ShippingOrg;
	$contactAddress1 = &$quote->Shipping->Address->Line1;
	$contactAddress2 = &$quote->Shipping->Address->Line2;
	$contactAddress3 = &$quote->Shipping->Address->Line3;
	$contactCity = &$quote->Shipping->Address->City;
	$contactCountry = &$quote->Shipping->Address->Country->ID;
	$contactRegion = &$quote->Shipping->Address->Region->ID;
	$contactZip = &$quote->Shipping->Address->Zip;
	
} elseif(strtolower($_REQUEST['type']) == 'invoice'){
	$contactTitle = &$quote->Invoice->Title;
	$contactName = &$quote->Invoice->Name;
	$contactInitial = &$quote->Invoice->Initial;
	$contactLast = &$quote->Invoice->LastName;
	$contactOrg = &$quote->InvoiceOrg;
	$contactAddress1 = &$quote->Invoice->Address->Line1;
	$contactAddress2 = &$quote->Invoice->Address->Line2;
	$contactAddress3 = &$quote->Invoice->Address->Line3;
	$contactCity = &$quote->Invoice->Address->City;
	$contactCountry = &$quote->Invoice->Address->Country->ID;
	$contactRegion = &$quote->Invoice->Address->Region->ID;
	$contactZip = &$quote->Invoice->Address->Zip;
} else {
	$contactTitle = &$quote->Billing->Title;
	$contactName = &$quote->Billing->Name;
	$contactInitial = &$quote->Billing->Initial;
	$contactLast = &$quote->Billing->LastName;
	$contactOrg = &$quote->BillingOrg;
	$contactAddress1 = &$quote->Billing->Address->Line1;
	$contactAddress2 = &$quote->Billing->Address->Line2;
	$contactAddress3 = &$quote->Billing->Address->Line3;
	$contactCity = &$quote->Billing->Address->City;
	$contactCountry = &$quote->Billing->Address->Country->ID;
	$contactRegion = &$quote->Billing->Address->Region->ID;
	$contactZip = &$quote->Billing->Address->Zip;
}

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 15);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('quoteid', 'Quote ID', 'hidden', $quote->ID, 'numeric_unsigned', 1, 11, false);
$form->AddField('type', 'Contact Type', 'hidden', $_REQUEST['type'], 'alpha', 1, 11, false);

$form->AddField('title', 'Title', 'select', $contactTitle, 'anything', 0, 20, false);
$form->AddOption('title', '', '');

$title = new DataQuery("select * from person_title order by Person_Title");
while($title->Row){
	$form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
	$title->Next();
}
$title->Disconnect();

$form->AddField('fname', 'First Name', 'text', $contactName, 'anything', 1, 60, false);
$form->AddField('iname', 'Initial', 'text', $contactInitial, 'alpha', 1, 1, false, 'size="1"');
$form->AddField('lname', 'Last Name', 'text', $contactLast, 'anything', 1, 60, false);
$form->AddField('org', 'Organisation Name', 'text', $contactOrg, 'anything', 1, 60, false);
$form->AddField('address1', 'Property Name/Number', 'text', $contactAddress1, 'anything', 1, 150, false);
$form->AddField('address2', 'Street', 'text',  $contactAddress2, 'anything', 1, 150, false);
$form->AddField('address3', 'Area', 'text',  $contactAddress3, 'anything', 1, 150, false);
$form->AddField('city', 'City', 'text',  $contactCity, 'anything', 1, 150, false);

$form->AddField('country', 'Country', 'select', $contactCountry, 'numeric_unsigned', 1, 11, false, 'onChange="propogateRegions(\'region\', this);"');
$form->AddOption('country', '0', '');
$form->AddOption('country', '222', 'United Kingdom');

$data = new DataQuery("select * from countries order by Country asc");
while($data->Row){
	$form->AddOption('country', $data->Row['Country_ID'], $data->Row['Country']);
	$data->Next();
}
$data->Disconnect();

$regionCount = 0;
$regionFound = false;
$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($form->GetValue('country'))));
$regionCount = $region->TotalRows;
if($regionCount > 0){
	$form->AddField('region', 'Region', 'select',  $contactRegion, 'numeric_unsigned', 1, 11, false);
	$form->AddOption('region', '0', '');

	while($region->Row){
		$form->AddOption('region', $region->Row['Region_ID'], $region->Row['Region_Name']);
		if($region->Row['Region_ID'] == $form->GetValue('region')) $regionFound = true;
		$region->Next();
	}
} else {
	$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
	$form->AddOption('region', '0', '');
}
if(!$regionFound){
	$form->SetValue('region', '');
}
$region->Disconnect();

$form->AddField('postcode', 'Postcode/Zip', 'text',  $contactZip, 'alpha_numeric', 1, 10, false);

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()){
		$contactTitle = $form->GetValue('title');
		$contactName = $form->GetValue('fname');
		$contactInitial = $form->GetValue('iname');
		$contactLast = $form->GetValue('lname');
		$contactOrg = $form->GetValue('org');
		$contactAddress1 = $form->GetValue('address1');
		$contactAddress2 = $form->GetValue('address2');
		$contactAddress3 = $form->GetValue('address3');
		$contactCity = $form->GetValue('city');
		$contactCountry = $form->GetValue('country');
		$contactRegion = $form->GetValue('region');
		$contactZip = $form->GetValue('postcode');

		if(strtolower($_REQUEST['type']) == 'shipping') {
			$quote->Recalculate();
			$quote->Update();
			
			if(!$quote->FoundPostage){
				redirect("Location: quote_details.php?postage=error&quoteid=" . $quote->ID);
			} else {
				redirect("Location: quote_details.php?quoteid=" . $quote->ID);
			}
		} else {
			$quote->Update();
			
			redirect("Location: quote_details.php?quoteid=" . $quote->ID);
		}
	}
}

$page = new Page('Change Address', '');
$page->LinkScript('js/regions.php');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}
echo $form->Open();
echo $form->GetHtml('action');
echo $form->GetHtml('confirm');
echo $form->GetHtml('quoteid');
echo $form->GetHtml('type');
?>
	<table width="100%" cellspacing="0" class="form">
	  <tr>
		<th colspan="2"><?php echo $formTitle; ?> Address</th>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('title'); ?></td>
	  <td>
		<?php echo $form->GetHtml('title'); ?><?php echo $form->GetIcon('title'); ?></td>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('fname'); ?></td>
	  <td>
		<?php echo $form->GetHtml('fname'); ?><?php echo $form->GetIcon('fname'); ?></td>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('iname'); ?></td>
	  <td>
		<?php echo $form->GetHtml('iname'); ?><?php echo $form->GetIcon('iname'); ?></td>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('lname'); ?></td>
	  <td>
		<?php echo $form->GetHtml('lname'); ?><?php echo $form->GetIcon('lname'); ?></td>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('org'); ?></td>
	  <td>
		<?php echo $form->GetHtml('org'); ?><?php echo $form->GetIcon('org'); ?></td>
	  </tr>
	  <tr>
		<td width="28%"><?php echo $form->GetLabel('address1'); ?> </td>
		<td width="72%"><?php echo $form->GetHtml('address1'); ?> <?php echo $form->GetIcon('address1'); ?></td>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('address2'); ?> </td>
		<td><?php echo $form->GetHtml('address2'); ?> <?php echo $form->GetIcon('address2'); ?></td>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('address3'); ?> </td>
		<td><?php echo $form->GetHtml('address3'); ?> <?php echo $form->GetIcon('address3'); ?></td>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('city'); ?> </td>
		<td><?php echo $form->GetHtml('city'); ?> <?php echo $form->GetIcon('city'); ?></td>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('country'); ?> </td>
		<td><?php echo $form->GetHtml('country'); ?> <?php echo $form->GetIcon('country'); ?></td>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('region'); ?> </td>
		<td><?php echo $form->GetHtml('region'); ?> <?php echo $form->GetIcon('region'); ?></td>
	  </tr>
	  <tr>
		<td><?php echo $form->GetLabel('postcode'); ?> </td>
		<td><?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?></td>
	  </tr>
	  <tr>
		<td>&nbsp;</td>
		<td><input name="cancel" type="button" value="cancel" onClick="window.location.href='./quote_details.php?quoteid=<?php echo $quote->ID; ?>';" class="btn" /> <input name="update" type="submit" class="btn" id="update" value="update" /></td>
	  </tr>
	</table>
<?php
echo $form->Close();
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>