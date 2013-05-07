<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Address.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Person.php');

if(!$cart->FoundPostage) {
	redirect("Location: cart.php?postage=missing");
}

$session->Secure();

$personIDNo = $cart->Customer->Contact->Person->ID;
$person = new person;
$person->validateContact($personIDNo,'F');

if($cart->TotalLines == 0) {
	redirect("Location: cart.php");
}

if($action == "remove"){
	if(param('contact') && param('contact') && strtolower(param('type', '')) == "contact"){
		$formDetail = new CustomerContact;
		$formDetail->Delete(param('contact'));
	}

	redirect("Location: " . $_SERVER['PHP_SELF'] . "?action=change");
} elseif($action == "edit"){
	$formDetail = new CustomerContact();
	$formDetail->Get(id_param('contact'));
	$formTitle = "Save";
	$formType = "contact";
} elseif($action == "editbilling"){
	$formDetail = new Person;
	if(empty($cart->Customer->Contact->ID)) $cart->Customer->Get();
	if(empty($cart->Customer->Contact->Person->ID)) $cart->Customer->Contact->Get();
	$formDetail = $cart->Customer->Contact->Person;
	$formTitle = "Save";
	$formType = "billing";
} elseif($action == "change"){
	$formDetail = new CustomerContact();
	$formTitle = "Add";
	$formType = "contact";
} else {
	if(param('confirm', '') != true && isset($cart->Customer->Contact->Person->Address->ID) && $cart->Customer->Contact->Person->Address->ID > 0){
		$cart->ShipTo = 'billing';
		$cart->Update();
		redirect("Location: summary.php");
	}

	$formDetail = new CustomerContact();
	$formTitle = "Add";
	$formType = "contact";
}

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'addAddress', 'alpha', 1, 15);
if($action == 'change') $form->SetValue('action', 'addAddress');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('contact', 'Contact ID', 'hidden', $formDetail->ID, 'numeric_unsigned', 1, 11, false);
$form->AddField('type', 'Contact Type', 'hidden', $formType, 'alpha', 1, 11, false);
$form->AddField('title', 'Title', 'select', $formDetail->Title, 'anything', 0, 20, true);
$form->AddOption('title', '', '');

$title = new DataQuery("select * from person_title order by Person_Title");
while($title->Row){
	$form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
	$title->Next();
}
$title->Disconnect();

$form->AddField('fname', 'First Name', 'text', $formDetail->Name, 'name', 1, 60, true);
$form->AddField('iname', 'Initial', 'text', $formDetail->Initial, 'alpha', 1, 1, false, 'size="1"');
$form->AddField('lname', 'Last Name', 'text', $formDetail->LastName, 'name', 1, 60, true);

if($action != "editbilling") {
	$form->AddField('oname', 'Organisation Name', 'text', $formDetail->OrgName, 'address', 1, 60, false);
}

$form->AddField('address1', 'Property Name/Number', 'text', $formDetail->Address->Line1, 'address', 1, 150, true);
$form->AddField('address2', 'Street', 'text',  $formDetail->Address->Line2, 'address', 1, 150, true);
$form->AddField('address3', 'Area', 'text',  $formDetail->Address->Line3, 'address', 1, 150, false);
$form->AddField('city', 'City', 'text',  $formDetail->Address->City, 'address', 1, 150, true);

$form->AddField('country', 'Country', 'select', $formDetail->Address->Country->ID, 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
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
		$form->AddField('region', 'Region', 'select',  $formDetail->Address->Region->ID, 'numeric_unsigned', 1, 11, true);
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
	$formDetail->Address->Region->Name = "";
}
$region->Disconnect();

	$form->AddField('postcode', 'Postcode', 'text',  $formDetail->Address->Zip, 'postcode', 1, 10);


if(param('status')=='update'){
	$form->Validate();
}	

if(strtolower(param('confirm', '')) == "true"){
	$form->Validate();

	if($form->GetValue('country') == 0){
    	$form->AddError('You have yet to select a country.', 'country');
  	}

  	/*if($form->GetValue('region') == 0){
    	$form->AddError('You have yet to select a region.', 'region');
  	}*/



	if($form->Valid){
		if($action != "editbilling") {
			$formDetail->OrgName = $form->GetValue('oname');
		}
		$formDetail->Title = $form->GetValue('title');
		$formDetail->Name = $form->GetValue('fname');
		$formDetail->Initial = $form->GetValue('iname');
		$formDetail->LastName = $form->GetValue('lname');
		$formDetail->Address->Line1 = $form->GetValue('address1');
		$formDetail->Address->Line2 = $form->GetValue('address2');
		$formDetail->Address->Line3 = $form->GetValue('address3');
		$formDetail->Address->City = $form->GetValue('city');
		$formDetail->Address->Region->ID = $form->GetValue('region');
		$formDetail->Address->Country->ID = $form->GetValue('country');
		$formDetail->Address->Zip = $form->GetValue('postcode');

		$formDetail->Customer = $session->Customer->ID;
		if($action == "addaddress"){
			$formDetail->Add();
			$cart->ShipTo = $formDetail->ID;
		} elseif($action == "edit" || $action == "editbilling"){
			$formDetail->Update();
			if(empty($cart->ShipTo) && $action =='editbilling'){
				$cart->ShipTo = 'billing';
			} else if($action == "edit") {
				$cart->ShipTo = $formDetail->ID;
			}
		}
		$cart->Update();
		if(empty($cart->ShipTo)){
			redirect("Location: " . $_SERVER['PHP_SELF']);
		} else {
			redirect("Location: summary.php");
		}
	}
}
//require_once('lib/mobile' . $_SERVER['PHP_SELF']);
require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');