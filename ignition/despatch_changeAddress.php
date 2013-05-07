<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$session->Secure(3);

$despatch = new Despatch($_REQUEST['despatchid']);
			
$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('despatchid', 'Despatch ID', 'hidden', '', 'numeric_unsigned', 1, 11, false);
$form->AddField('title', 'Title', 'select', $despatch->Person->Title, 'anything', 0, 20, false);
$form->AddOption('title', '', '');

$data = new DataQuery("SELECT * FROM person_title ORDER BY Person_Title ASC");
while($data->Row){
	$form->AddOption('title', $data->Row['Person_Title'], $data->Row['Person_Title']);
	
	$data->Next();
}
$data->Disconnect();

$form->AddField('fname', 'First Name', 'text', $despatch->Person->Name, 'name', 1, 60, true);
$form->AddField('iname', 'Initial', 'text', $despatch->Person->Initial, 'alpha', 1, 1, false, 'size="1"');
$form->AddField('lname', 'Last Name', 'text', $despatch->Person->LastName, 'name', 1, 60, true);
$form->AddField('org', 'Organisation Name', 'text', $despatch->Organisation, 'anything', 1, 60, false);
$form->AddField('address1', 'Property Name/Number', 'text', $despatch->Person->Address->Line1, 'address', 1, 150, true);
$form->AddField('address2', 'Street', 'text',  $despatch->Person->Address->Line2, 'address', 1, 150, true);
$form->AddField('address3', 'Area', 'text',  $despatch->Person->Address->Line3, 'address', 1, 150, false);
$form->AddField('city', 'City', 'text',  $despatch->Person->Address->City, 'address', 1, 150, true);
$form->AddField('country', 'Country', 'select', $despatch->Person->Address->Country->ID, 'numeric_unsigned', 1, 11, false, 'onchange="propogateRegions(\'region\', this);"');
$form->AddOption('country', '0', '');
$form->AddOption('country', '222', 'United Kingdom');

$data = new DataQuery("SELECT * FROM countries ORDER BY Country ASC");
while($data->Row){
	$form->AddOption('country', $data->Row['Country_ID'], $data->Row['Country']);
	
	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Region_ID, Region_Name FROM regions WHERE Country_ID=%d ORDER BY Region_Name ASC", mysql_real_escape_string($form->GetValue('country'))));
if($data->TotalRows > 0){
	$form->AddField('region', 'Region', 'select',  $despatch->Person->Address->Region->ID, 'numeric_unsigned', 1, 11, false);
	$form->AddOption('region', '0', '');

	while($data->Row){
		$form->AddOption('region', $data->Row['Region_ID'], $data->Row['Region_Name']);
		
		$data->Next();
	}
} else {
	$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
	$form->AddOption('region', '0', '');
}
$data->Disconnect();

$form->AddField('postcode', 'Postcode/Zip', 'text', $despatch->Person->Address->Zip, 'postcode', 1, 10, false);

if(isset($_REQUEST['confirm'])) {
	$form->Validate();
	if($form->GetValue('country') == 0){
    	$form->AddError('You have yet to select a country.', 'country');
  	}

	if($form->Valid){
		$despatch->Person->Title = $form->GetValue('title');
		$despatch->Person->Name = $form->GetValue('fname');
		$despatch->Person->Initial = $form->GetValue('iname');
		$despatch->Person->LastName = $form->GetValue('lname');
		$despatch->Organisation = $form->GetValue('org');
		$despatch->Person->Address->Line1 = $form->GetValue('address1');
		$despatch->Person->Address->Line2 = $form->GetValue('address2');
		$despatch->Person->Address->Line3 = $form->GetValue('address3');
		$despatch->Person->Address->City = $form->GetValue('city');
		$despatch->Person->Address->Country->ID = $form->GetValue('country');
		$despatch->Person->Address->Region->ID = $form->GetValue('region');
		$despatch->Person->Address->Zip = $form->GetValue('postcode');
		$despatch->Update();
			
		redirect("Location: despatch.php?despatchid=" . $despatch->ID);
	}
}

$page = new Page('Change Address', '');
$page->LinkScript('js/regions.php');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}
echo $form->Open();
echo $form->GetHtml('action');
echo $form->GetHtml('confirm');
echo $form->GetHtml('despatchid');
?>

<table width="100%" cellspacing="0" class="form">
  <tr>
	<th colspan="2">Despatch Address</th>
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
	<td><input name="back" type="button" value="back" onclick="window.location.href = 'despatch.php?despatchid=<?php echo $despatch->ID; ?>';" class="btn" /> <input name="update" type="submit" class="btn" id="update" value="update" /></td>
  </tr>
</table>

<?php
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');