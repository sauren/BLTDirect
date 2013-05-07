<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
	
if($action == 'add'){
	$session->Secure(3);
	add();
	exit();
} elseif($action == 'makecustomer') {
	$session->Secure(3);
	makeCustomer();
	exit();
} elseif($action == 'addchild'){
	$session->Secure(3);
	addChild();
	exit();
} elseif($action == 'addtoneworg'){
	$session->Secure(3);
	addToNewOrg();
	exit();
} elseif($action == 'remove'){
	$session->Secure(3);
	remove();
	exit();
} elseif($action == 'removechild'){
	$session->Secure(3);
	removeChild();
	exit();
} elseif($action == 'updatechild'){
	$session->Secure(3);
	updateChild();
	exit();
} elseif($action == 'updateind'){
	$session->Secure(3);
	updateInd();
	exit();
} elseif($action == 'requestcatalogue'){
	$session->Secure(3);
	requestCatalogue();
	exit();
} elseif($action == 'cancelrequestcatalogue'){
	$session->Secure(3);
	cancelrequestCatalogue();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function makeCustomer() {
	createCustomerRecord($_REQUEST['cid']);

	redirectTo('?cid=' . $_REQUEST['cid']);
}

function requestCatalogue() {
	$contact = new Contact();
	
	if($contact->Get($_REQUEST['cid'])) {
		$contact->IsCatalogueRequested = 'Y';
		$contact->Update();
	}
	
	redirectTo('?cid=' . $_REQUEST['cid']);
}

function cancelrequestCatalogue() {
	$contact = new Contact();
	
	if($contact->Get($_REQUEST['cid'])) {
		$contact->IsCatalogueRequested = 'N';
		$contact->Update();
	}
	
	redirectTo('?cid=' . $_REQUEST['cid']);
}

function createCustomerRecord($contactId) {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');

	$contact = new Contact($contactId);

	if($contact->IsCustomer == 'N') {
		$password = new Password(PASSWORD_LENGTH_CUSTOMER);

		$customer = new Customer();
		$customer->Contact = $contact;
		$customer->Username = $customer->Contact->Person->Email;
		$customer->SetPassword($password->Value);
		$customer->FoundVia = 0;
		$customer->Add();
		
		$contact->IsCustomer = 'Y';
		$contact->Update();

		return true;
	}

	return false;
}

function updateInd(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$contact = new Contact($_REQUEST['cid']);
	$isUpdate = (isset($_REQUEST['status']) && $_REQUEST['status']=='update')?'true':'false';
	
	if(isset($_REQUEST['remove']) && ($_REQUEST['remove'] == 'tradeimage')) {
    	$contact->TradeImage->Delete();
    	$contact->TradeImage->FileName = '';
    	$contact->Update();
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'updateind', 'alpha', 9, 9);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$form->AddField('updatedetail', 'Update Details', 'hidden', $isUpdate, 'alpha');


	$form->AddField('cid', 'Contact ID', 'hidden', $contact->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('istest', 'Is Test', 'checkbox', $contact->IsTest, 'boolean', 1, 1, false);
	$form->AddField('ishighdiscount', 'Is High Discount', 'checkbox', $contact->IsHighDiscount, 'boolean', 1, 1, false);
	$form->AddField('istradeaccount', 'Is Trade Account', 'checkbox', $contact->IsTradeAccount, 'boolean', 1, 1, false);
	$form->AddField('tradeimage', 'Trade Image', 'file', '', 'file', null, null, false);
	$form->AddField('integrationreference', 'Sage Account Reference', 'text', $contact->IntegrationReference, 'paragraph', 1, 64, false);
	$form->AddField('account', 'Account Manager', 'select', $contact->AccountManager->ID, 'numeric_unsigned', 1, 11, true, (($contact->AccountManager->ID == 0) || ($GLOBALS['SESSION_USER_ID'] == 3) || ($contact->AccountManager->ID == $GLOBALS['SESSION_USER_ID'])) ? '' : 'disabled="disabled"');
	$form->AddOption('account', '0', '');

	$data = new DataQuery('SELECT u.User_ID, p.Name_First, p.Name_Last FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY p.Name_First, p.Name_Last ASC');
	while($data->Row){
		$form->AddOption('account', $data->Row['User_ID'], trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])));

		$data->Next();
	}
	$data->Disconnect();
	
	$form->AddField('generategroups', 'Generate Groups', 'checkbox', $contact->GenerateGroups, 'boolean', 1, 1, false);

	$form->AddField('personTitle', 'Title', 'select', $contact->Person->Title, 'anything', 0, 20, false);
	$form->AddOption('personTitle', '', '');

	$pTitle = new DataQuery('select * from person_title order by Person_Title asc');
	while($pTitle->Row){
		$form->AddOption('personTitle', $pTitle->Row['Person_Title'], $pTitle->Row['Person_Title']);
		$pTitle->Next();
	}
	$pTitle->Disconnect();

	$form->AddField('name', 'First Name', 'text', $contact->Person->Name, 'name', 1, 60, true);
	$form->AddField('initial', 'Initial', 'text', $contact->Person->Initial, 'alpha', 1, 1, false);
	$form->AddField('surname', 'Last Name', 'text', $contact->Person->LastName, 'name', 1, 60, true);
	$form->AddField('phone1', 'Phone 1', 'text', $contact->Person->Phone1, 'telephone', 1, 15, true);
	$form->AddField('phone1Ext', 'Phone 1 Extension', 'text', $contact->Person->Phone1Ext, 'telephone', 1, 5, false);
	$form->AddField('phone2', 'Phone 2', 'text', $contact->Person->Phone2, 'telephone', 1, 15, false);
	$form->AddField('phone2Ext', 'Phone 2 Extension', 'text', $contact->Person->Phone2Ext, 'telephone', 1, 5, false);
	$form->AddField('fax', 'Fax', 'text', $contact->Person->Fax, 'telephone', 1, 15, false);
	$form->AddField('mobile', 'Mobile', 'text', $contact->Person->Mobile, 'telephone', 1, 15, false);
	$form->AddField('email', 'Email', 'text', $contact->Person->Email, 'email', NULL, NULL, true);
	$form->AddField('address1', 'Address Line 1', 'text', $contact->Person->Address->Line1, 'address', 1, 150, true);
	$form->AddField('address2', 'Address Line 2', 'text', $contact->Person->Address->Line2, 'address', 1, 150, true);
	$form->AddField('address3', 'Address Line 3', 'text', $contact->Person->Address->Line3, 'address', 1, 150, false);
	$form->AddField('city', 'City/Town', 'text', $contact->Person->Address->City, 'address', 1, 100, true);
	$form->AddField('region', 'Region', 'select', $contact->Person->Address->Region->ID, 'numeric_unsigned', 1, 11, true);
	$form->AddOption('region', '0', '');

	$regions = new DataQuery(sprintf("select * from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($contact->Person->Address->Country->ID)));
	while($regions->Row){
		$form->AddOption('region', $regions->Row['Region_ID'], $regions->Row['Region_Name']);
		$regions->Next();
	}
	$regions->Disconnect();

	$form->AddField('country', 'Country', 'select', $contact->Person->Address->Country->ID, 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
	$form->AddOption('country', '0', '');
	$form->AddOption('country', '222', 'United Kingdom');

	$country = new DataQuery("select * from countries order by Country asc");
	while($country->Row){
		$form->AddOption('country', $country->Row['Country_ID'], $country->Row['Country']);
		$country->Next();
	}
	$country->Disconnect();

	$form->AddField('zip', 'Postal Code', 'text', $contact->Person->Address->Zip, 'postcode', 1, 10, false);

	$form->AddField('status', 'Status', 'select', $contact->Status->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('status', '0', '');

	$data = new DataQuery('SELECT * FROM contact_status ORDER BY Name ASC');
	while($data->Row){
		$form->AddOption('status', $data->Row['Contact_Status_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(param('status')=='update'){
		$form->Validate();	
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		$form->Validate();

		if($form->GetValue('country') == 0){
	    	$form->AddError('You have yet to select a country.', 'country');
	  	}

	  	/*if($form->GetValue('region') == 0){
	    	$form->AddError('You have yet to select a region.', 'region');
	  	}*/

		if($form->Valid){
			$contact->Type = 'I';
			$contact->Status->ID = $form->GetValue('status');
			$contact->Person->Name = $form->GetValue('name');
			$contact->Person->Title = $form->GetValue('personTitle');
			$contact->Person->LastName = $form->GetValue('surname');
			$contact->Person->Initial = $form->GetValue('initial');
			$contact->Person->Phone1 = $form->GetValue('phone1');
			$contact->Person->Phone1Ext = $form->GetValue('phone1Ext');
			$contact->Person->Phone2 = $form->GetValue('phone2');
			$contact->Person->Phone2Ext = $form->GetValue('phone2Ext');
			$contact->Person->Fax = $form->GetValue('fax');
			$contact->Person->Mobile = $form->GetValue('mobile');
			$contact->Person->Email = $form->GetValue('email');
			$contact->Person->Address->Line1 = $form->GetValue('address1');
			$contact->Person->Address->Line2 = $form->GetValue('address2');
			$contact->Person->Address->Line3 = $form->GetValue('address3');
			$contact->Person->Address->City = $form->GetValue('city');
			$contact->Person->Address->Region->ID = $form->GetValue('region');
			$contact->Person->Address->Country->ID = $form->GetValue('country');
			$contact->Person->Address->Zip = $form->GetValue('zip');
			$contact->IsTest = $form->GetValue('istest');
			$contact->IsHighDiscount = $form->GetValue('ishighdiscount');
			$contact->IsTradeAccount = $form->GetValue('istradeaccount');
			$contact->IntegrationReference = $form->GetValue('integrationreference');

			if(($contact->AccountManager->ID == 0) || ($GLOBALS['SESSION_USER_ID'] == 3) || ($contact->AccountManager->ID == $GLOBALS['SESSION_USER_ID'])) {
				$contact->AccountManager->ID = $form->GetValue('account');
			}
			
			$contact->GenerateGroups = $form->GetValue('generategroups');


			
			if($contact->UpdateTradeImage('tradeimage')) {
				$contact->Update();
				$contact->UpdateAccountManager();
				
				if(isset($_REQUEST['updatedetail']) && strtolower($_REQUEST['updatedetail']) == "true"){
	      			redirect("Location: order_summary.php");
	    		}else{
					redirect(sprintf("Location:contact_profile.php?action=view&cid=%d", $contact->ID));
	    		}
			} else {
				for($i=0; $i<count($contact->TradeImage->Errors); $i++) {
					$form->AddError($contact->TradeImage->Errors[$i], 'tradeimage');
				}
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var foundUser = function(id, firstName, lastName) {
			var e = document.getElementById(\'user\');

			if(e) {
				e.value = id;

				e = document.getElementById(\'userStr\');

				if(e) {
					e.value = firstName + \' \' + lastName;
				}
			}
		}
		</script>');

	$page = new Page(sprintf('<a href="contact_profile.php?action=view&cid=%d">%s %s</a> &gt; Update Contact', $contact->ID, $contact->Person->Name, $contact->Person->LastName),'Contacts are used throughout Ignition for Customers, Suppliers and Users. The more information you supply the better your system will become');

	if(param('status')=='update'){?>
		<div class="detailNotification"> 
	        <h1>Customer Details Missing</h1>
	        <p> You have been redirected back to the contact page as the details that have provided are incomplete or are not valid. Please ammend and save the changes to the correct format / required fields before proceeding with the order.</p>
	    </div>
	    <br/>
	<?php }
	
	$page->LinkScript('js/regions.php');
	$page->LinkScript('js/pcAnywhere.js');
	$page->AddToHead($script);
	$page->AddToHead("
		<script language=\"javascript\" type=\"text/javascript\">
			Address.account = '".$GLOBALS['POSTCODEANYWHERE_ACCOUNT']."';
			Address.licence = '".$GLOBALS['POSTCODEANYWHERE_LICENCE']."';

			Address.add('zip', 'line1', 'address2');
			Address.add('zip', 'line2', 'address3');
			Address.add('zip', 'line3', null);
			Address.add('zip', 'city', 'city');
			Address.add('zip', 'county', 'region');
		</script>");
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Edit Contact");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHtml('updatedetail');
	echo $form->GetHTML('cid');

	echo $window->Open();
	echo $window->AddHeader('Please complete the following fields as accurately as and as many as possible.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('istest'), $form->GetHTML('istest') . $form->GetIcon('istest'));
	echo $webForm->AddRow($form->GetLabel('ishighdiscount'),$form->GetHTML('ishighdiscount').$form->GetIcon('ishighdiscount'));
	echo $webForm->AddRow($form->GetLabel('istradeaccount'),$form->GetHTML('istradeaccount').$form->GetIcon('istradeaccount'));
	echo $webForm->AddRow($form->GetLabel('tradeimage'), $form->GetHTML('tradeimage') . $form->GetIcon('tradeimage'));
	
	if(!empty($contact->TradeImage->FileName)) {
		echo $webForm->AddRow('Current Trade Image', sprintf('<a href="%s%s">%s</a> <a href="?action=%s&cid=%d&remove=tradeimage"><img src="images/button-cross.gif" /></a>', $GLOBALS['TRADE_IMAGES_DIR_WS'], $contact->TradeImage->FileName, $contact->TradeImage->FileName, $form->GetValue('action'), $form->GetValue('cid')));
	}
	
	echo $webForm->AddRow($form->GetLabel('integrationreference'), $form->GetHTML('integrationreference') . $form->GetIcon('integrationreference'));
	echo $webForm->AddRow($form->GetLabel('account'), $form->GetHTML('account') . $form->GetIcon('account'));
	echo $webForm->AddRow($form->GetLabel('generategroups'), $form->GetHTML('generategroups') . $form->GetIcon('generategroups'));
	echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status') . $form->GetIcon('status'));
	echo $webForm->AddRow($form->GetLabel('personTitle'), $form->GetHTML('personTitle') . $form->GetIcon('personTitle'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('initial'), $form->GetHTML('initial') . $form->GetIcon('initial'));
	echo $webForm->AddRow($form->GetLabel('surname'), $form->GetHTML('surname') . $form->GetIcon('surname'));
	echo $webForm->AddRow($form->GetLabel('phone1'), $form->GetHTML('phone1') . $form->GetIcon('phone1'));
	echo $webForm->AddRow($form->GetLabel('phone1Ext'), $form->GetHTML('phone1Ext') . $form->GetIcon('phone1Ext'));
	echo $webForm->AddRow($form->GetLabel('phone2'), $form->GetHTML('phone2') . $form->GetIcon('phone2'));
	echo $webForm->AddRow($form->GetLabel('phone2Ext'), $form->GetHTML('phone2Ext') . $form->GetIcon('phone2Ext'));
	echo $webForm->AddRow($form->GetLabel('fax'), $form->GetHTML('fax') . $form->GetIcon('fax'));
	echo $webForm->AddRow($form->GetLabel('mobile'), $form->GetHTML('mobile') . $form->GetIcon('mobile'));
	echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email') . $form->GetIcon('email'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Send invoices to the below address.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('zip'), $form->GetHTML('zip') . $form->GetIcon('zip') . '<a href="javascript:Address.find(document.getElementById(\'zip\'));"><img src="../images/searchIcon.gif" border="0" align="absmiddle" /> Auto-complete address (UK residents)</a>');
	echo $webForm->AddRow($form->GetLabel('address1'), $form->GetHTML('address1') . $form->GetIcon('address1'));
	echo $webForm->AddRow($form->GetLabel('address2'), $form->GetHTML('address2') . $form->GetIcon('address2'));
	echo $webForm->AddRow($form->GetLabel('address3'), $form->GetHTML('address3') . $form->GetIcon('address3'));
	echo $webForm->AddRow($form->GetLabel('city'), $form->GetHTML('city') . $form->GetIcon('city'));
	echo $webForm->AddRow($form->GetLabel('country'), $form->GetHTML('country') . $form->GetIcon('country'));
	echo $webForm->AddRow($form->GetLabel('region'), $form->GetHTML('region') . $form->GetIcon('region'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function updateChild(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$contact = new Contact($_REQUEST['cid']);
    $customer = new Customer();
    
    if(isset($_REQUEST['remove']) && ($_REQUEST['remove'] == 'tradeimage')) {
    	$contact->TradeImage->Delete();
    	$contact->TradeImage->FileName = '';
    	$contact->Update();
	}

	$isCustomer = false;

	$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
	while($data->Row) {
		$customer->Get($data->Row['Customer_ID']);
		$isCustomer = true;

		$data->Next();
	}
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'updatechild', 'alpha', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Contact ID', 'hidden', $contact->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('istest', 'Is Test', 'checkbox', $contact->IsTest, 'boolean', 1, 1, false);
	$form->AddField('isactive', 'Is Active', 'checkbox', $contact->IsActive, 'boolean', 1, 1, false);
	$form->AddField('iscreditcontact', 'Is Credit Contact', 'checkbox', $contact->IsCreditContact, 'boolean', 1, 1, false);
	$form->AddField('ishighdiscount', 'Is High Discount', 'checkbox', $contact->IsHighDiscount, 'boolean', 1, 1, false);
	$form->AddField('istradeaccount', 'Is Trade Account', 'checkbox', $contact->IsTradeAccount, 'boolean', 1, 1, false);
	$form->AddField('tradeimage', 'Trade Image', 'file', '', 'file', null, null, false);
	$form->AddField('status', 'Status', 'select', $contact->Status->ID, 'numeric_unsigned', 1, 11,false);
	$form->AddOption('status', '0', '');

	$data = new DataQuery('SELECT * FROM contact_status ORDER BY Name ASC');
	while($data->Row){
		$form->AddOption('status', $data->Row['Contact_Status_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('personTitle', 'Title', 'select', $contact->Person->Title, 'anything', 0, 20, false);
	$form->AddOption('personTitle', '', '');

	$pTitle = new DataQuery('select * from person_title order by Person_Title asc');
	while($pTitle->Row){
		$form->AddOption('personTitle', $pTitle->Row['Person_Title'], $pTitle->Row['Person_Title']);
		$pTitle->Next();
	}
	$pTitle->Disconnect();

	$form->AddField('name', 'First Name', 'text', $contact->Person->Name, 'name', 1, 60, true);
	$form->AddField('initial', 'Initial', 'text', $contact->Person->Initial, 'alpha', 1, 1, false);
	$form->AddField('surname', 'Last Name', 'text', $contact->Person->LastName, 'name', 1, 60, true);
	$form->AddField('dept', 'Department', 'text', $contact->Person->Department, 'anything', 1, 40, false);
	$form->AddField('division', 'Division', 'text', $contact->Person->Division, 'anything', 1, 60, false);
	$form->AddField('position', 'Position', 'text', $contact->Person->Position, 'anything', 1, 100, false);
	$form->AddField('phone1', 'Phone 1', 'text', $contact->Person->Phone1, 'telephone', 1, 15, true);
	$form->AddField('phone1Ext', 'Phone 1 Extension', 'text', $contact->Person->Phone1Ext, 'telephone', 1, 5, false);
	$form->AddField('phone2', 'Phone 2', 'text', $contact->Person->Phone2, 'telephone', 1, 15, false);
	$form->AddField('phone2Ext', 'Phone 2 Extension', 'text', $contact->Person->Phone2Ext, 'telephone', 1, 5, false);
	$form->AddField('fax', 'Fax', 'text', $contact->Person->Fax, 'telephone', 1, 15, false);
	$form->AddField('mobile', 'Mobile', 'text', $contact->Person->Mobile, 'telephone', 1, 15, false);
	$form->AddField('email', 'Email', 'text', $contact->Person->Email, 'email', NULL, NULL, true);
	$form->AddField('address1', 'Address Line 1', 'text', $contact->Person->Address->Line1, 'address', 1, 150, true);
	$form->AddField('address2', 'Address Line 2', 'text', $contact->Person->Address->Line2, 'address', 1, 150, true);
	$form->AddField('address3', 'Address Line 3', 'text', $contact->Person->Address->Line3, 'address', 1, 150, false);
	$form->AddField('city', 'City/Town', 'text', $contact->Person->Address->City, 'address', 1, 100, true);
	$form->AddField('region', 'Region', 'select', $contact->Person->Address->Region->ID, 'numeric_unsigned', 1, 11, true);
	$form->AddOption('region', '0', '');

	$regions = new DataQuery(sprintf("select * from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($contact->Person->Address->Country->ID)));
	while($regions->Row){
		$form->AddOption('region', $regions->Row['Region_ID'], $regions->Row['Region_Name']);
		$regions->Next();
	}
	$regions->Disconnect();

	$form->AddField('country', 'Country', 'select', $contact->Person->Address->Country->ID, 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
	$form->AddOption('country', '0', '');
	$form->AddOption('country', '222', 'United Kingdom');

	$country = new DataQuery("select * from countries order by Country asc");
	while($country->Row){
		$form->AddOption('country', $country->Row['Country_ID'], $country->Row['Country']);
		$country->Next();
	}
	$country->Disconnect();

	$form->AddField('zip', 'Postal Code', 'text', $contact->Person->Address->Zip, 'postcode', 1, 10, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$contact->Type = 'I';
			$contact->IsCreditContact = $form->GetValue('iscreditcontact');
			$contact->Person->Name = $form->GetValue('name');
			$contact->Person->Title = $form->GetValue('personTitle');
			$contact->Person->LastName = $form->GetValue('surname');
			$contact->Person->Initial = $form->GetValue('initial');
			$contact->Person->Department = $form->GetValue('dept');
			$contact->Person->Position = $form->GetValue('position');
			$contact->Person->Division = $form->GetValue('division');
			$contact->Person->Phone1 = $form->GetValue('phone1');
			$contact->Person->Phone1Ext = $form->GetValue('phone1Ext');
			$contact->Person->Phone2 = $form->GetValue('phone2');
			$contact->Person->Phone2Ext = $form->GetValue('phone2Ext');
			$contact->Person->Fax = $form->GetValue('fax');
			$contact->Person->Mobile = $form->GetValue('mobile');
			$contact->Person->Email = $form->GetValue('email');
			$contact->Person->Address->Line1 = $form->GetValue('address1');
			$contact->Person->Address->Line2 = $form->GetValue('address2');
			$contact->Person->Address->Line3 = $form->GetValue('address3');
			$contact->Person->Address->City = $form->GetValue('city');
			$contact->Person->Address->Region->ID = $form->GetValue('region');
			$contact->Person->Address->Country->ID = $form->GetValue('country');
			$contact->Person->Address->Zip = $form->GetValue('zip');
			$contact->IsActive = $form->GetValue('isactive');
			$contact->IsTest = $form->GetValue('istest');
			$contact->IsHighDiscount = $form->GetValue('ishighdiscount');
			$contact->IsTradeAccount = $form->GetValue('istradeaccount');
			$contact->Status->ID = $form->GetValue('status');
			
			if($contact->UpdateTradeImage('tradeimage')) {
				$contact->Update();

				$data = new DataQuery(sprintf("SELECT Is_Active FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
				if($data->TotalRows > 0) {
					new DataQuery(sprintf("UPDATE customer SET Is_Active='%s' WHERE Contact_ID=%d", mysql_real_escape_string($form->GetValue('isactive')), mysql_real_escape_string($contact->ID)));
				}
				$data->Disconnect();

				redirect(sprintf("Location:contact_profile.php?action=view&cid=%d", $contact->ID));
			} else {
				for($i=0; $i<count($contact->TradeImage->Errors); $i++) {
					$form->AddError($contact->TradeImage->Errors[$i], 'tradeimage');
				}
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var foundUser = function(id, firstName, lastName) {
			var e = document.getElementById(\'user\');

			if(e) {
				e.value = id;

				e = document.getElementById(\'userStr\');

				if(e) {
					e.value = firstName + \' \' + lastName;
				}
			}
		}
		</script>');

	$page = new Page(sprintf('<a href="contact_profile.php?action=view&cid=%d">%s</a> &gt; Update Contact', $contact->Parent->ID, $contact->Parent->Organisation->Name),'Contacts are used throughout Ignition for Customers, Suppliers and Users. The more information you supply the better your system will become');

	if(param('status')=='update'){?>
		<div class="detailNotification"> 
	        <h1>Customer Details Missing</h1>
	        <p> You have been redirected back to the contact page as the details that have provided are incomplete or are not valid. Please ammend and save the changes to the correct format / required fields before proceeding with the order.</p>
	    </div>
	    <br/>
	<?php }

	$page->LinkScript('js/regions.php');
	$page->LinkScript('js/pcAnywhere.js');
	$page->AddToHead($script);
	$page->AddToHead("
		<script language=\"javascript\" type=\"text/javascript\">
			Address.account = '".$GLOBALS['POSTCODEANYWHERE_ACCOUNT']."';
			Address.licence = '".$GLOBALS['POSTCODEANYWHERE_LICENCE']."';

			Address.add('zip', 'line1', 'address2');
			Address.add('zip', 'line2', 'address3');
			Address.add('zip', 'line3', null);
			Address.add('zip', 'city', 'city');
			Address.add('zip', 'county', 'region');
		</script>");

	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Edit Contact");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('cid');

	echo $window->Open();
	echo $window->AddHeader('Please complete the following fields as accurately as and as many as possible.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('istest'), $form->GetHTML('istest') . $form->GetIcon('istest'));
	echo $webForm->AddRow($form->GetLabel('isactive'), $form->GetHTML('isactive') . $form->GetIcon('isactive'));
	echo $webForm->AddRow($form->GetLabel('iscreditcontact'), $form->GetHTML('iscreditcontact') . $form->GetIcon('iscreditcontact'));
	echo $webForm->AddRow($form->GetLabel('ishighdiscount'),$form->GetHTML('ishighdiscount').$form->GetIcon('ishighdiscount'));
	echo $webForm->AddRow($form->GetLabel('istradeaccount'),$form->GetHTML('istradeaccount').$form->GetIcon('istradeaccount'));
	echo $webForm->AddRow($form->GetLabel('tradeimage'), $form->GetHTML('tradeimage') . $form->GetIcon('tradeimage'));
	
	if(!empty($contact->TradeImage->FileName)) {
		echo $webForm->AddRow('Current Trade Image', sprintf('<a href="%s%s">%s</a> <a href="?action=%s&cid=%d&remove=tradeimage"><img src="images/button-cross.gif" /></a>', $GLOBALS['TRADE_IMAGES_DIR_WS'], $contact->TradeImage->FileName, $contact->TradeImage->FileName, $form->GetValue('action'), $form->GetValue('cid')));
	}
	
	echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status') . $form->GetIcon('status'));
	echo $webForm->AddRow($form->GetLabel('personTitle'), $form->GetHTML('personTitle') . $form->GetIcon('personTitle'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('initial'), $form->GetHTML('initial') . $form->GetIcon('initial'));
	echo $webForm->AddRow($form->GetLabel('surname'), $form->GetHTML('surname') . $form->GetIcon('surname'));
	echo $webForm->AddRow($form->GetLabel('dept'), $form->GetHTML('dept') . $form->GetIcon('dept'));
	echo $webForm->AddRow($form->GetLabel('division'), $form->GetHTML('division') . $form->GetIcon('division'));
	echo $webForm->AddRow($form->GetLabel('position'), $form->GetHTML('position') . $form->GetIcon('position'));
	echo $webForm->AddRow($form->GetLabel('phone1'), $form->GetHTML('phone1') . $form->GetIcon('phone1'));
	echo $webForm->AddRow($form->GetLabel('phone1Ext'), $form->GetHTML('phone1Ext') . $form->GetIcon('phone1Ext'));
	echo $webForm->AddRow($form->GetLabel('phone2'), $form->GetHTML('phone2') . $form->GetIcon('phone2'));
	echo $webForm->AddRow($form->GetLabel('phone2Ext'), $form->GetHTML('phone2Ext') . $form->GetIcon('phone2Ext'));
	echo $webForm->AddRow($form->GetLabel('fax'), $form->GetHTML('fax') . $form->GetIcon('fax'));
	echo $webForm->AddRow($form->GetLabel('mobile'), $form->GetHTML('mobile') . $form->GetIcon('mobile'));
	echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email') . $form->GetIcon('email'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Send invoices to the below address.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('zip'), $form->GetHTML('zip') . $form->GetIcon('zip') . '<a href="javascript:Address.find(document.getElementById(\'zip\'));"><img src="../images/searchIcon.gif" border="0" align="absmiddle" /> Auto-complete address (UK residents)</a>');
	echo $webForm->AddRow($form->GetLabel('address1'), $form->GetHTML('address1') . $form->GetIcon('address1'));
	echo $webForm->AddRow($form->GetLabel('address2'), $form->GetHTML('address2') . $form->GetIcon('address2'));
	echo $webForm->AddRow($form->GetLabel('address3'), $form->GetHTML('address3') . $form->GetIcon('address3'));
	echo $webForm->AddRow($form->GetLabel('city'), $form->GetHTML('city') . $form->GetIcon('city'));
	echo $webForm->AddRow($form->GetLabel('country'), $form->GetHTML('country') . $form->GetIcon('country'));
	echo $webForm->AddRow($form->GetLabel('region'), $form->GetHTML('region') . $form->GetIcon('region'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Organisation.php');

	if(isset($_REQUEST['cid']) && isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == 'true'){
		$data = new DataQuery(sprintf("SELECT Org_ID FROM contact WHERE Contact_ID=%d", mysql_real_escape_string($_REQUEST['cid'])));
		if($data->TotalRows > 0) {
			if($data->Row['Org_ID'] > 0) {
				$org = new Organisation($data->Row['Org_ID']);
				$org->Delete();
			}
		}
		$data->Disconnect();

		$contact = new Contact($_REQUEST['cid']);
		$contact->Delete();

		$data = new DataQuery(sprintf("UPDATE contact SET Parent_Contact_ID=0 WHERE Parent_Contact_ID=%d", mysql_real_escape_string($_REQUEST['cid'])));
		$data->Disconnect();
	}

	if(!isset($_REQUEST['parent'])){
		redirect("Location: contact_search.php");
	} else {
		redirect("Location: contact_profile.php?action=view&cid=" . $_REQUEST['parent']);
	}
}

function removeChild() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');

	if(isset($_REQUEST['cid'])) {
		
		$id = $_REQUEST['cid'];
		$contact = new Contact($_REQUEST['cid']);
		$contact->RemoveContactsOrganistation($id);
		
		redirect(sprintf("Location: contact_search.php?cid=%d", $contact->Parent->ID));
	}
	
	redirect("Location: contact_search1.php");
}

function addToNewOrg() {
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$contact = new Contact($_REQUEST['cid']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addtoneworg', 'alpha', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Contact ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('orgName', 'Organisation Name', 'text', '', 'alpha_numeric', 1, 100, false);
	$form->AddField('orgType', 'Organisation Type', 'select', '', 'numeric_unsigned', 1, 11, false);
	$form->AddOption('orgType', '', 'Select...');

	$orgTypes = new DataQuery('select * from organisation_type order by Org_Type asc');
	while($orgTypes->Row){
		$form->AddOption('orgType', $orgTypes->Row['Org_Type_ID'], $orgTypes->Row['Org_Type']);
		$orgTypes->Next();
	}
	$orgTypes->Disconnect();

	$form->AddField('orgIndustry', 'Organisation Industry', 'select', '', 'numeric_unsigned', 1, 11, false);
	$form->AddOption('orgIndustry', '', 'Select...');
	$orgInd = new DataQuery('select * from organisation_industry order by Industry_Name asc');
	while($orgInd->Row){
		$form->AddOption('orgIndustry', $orgInd->Row['Industry_ID'], $orgInd->Row['Industry_Name']);
		$orgInd->Next();
	}
	$orgInd->Disconnect();

	$form->AddField('url', 'Organisation Website', 'text', '', 'link', NULL, NULL, false);
	$form->AddField('registrationNumber', 'Organisation Registration Number', 'text', '', 'alpha_numeric', 1, 100, false);
	$form->AddField('taxNumber', 'Tax Registration/VAT Number', 'text', '', 'alpha_numeric', 1, 100, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$contact->Parent = new Contact();
			$contact->Parent->Type = 'O';
			$contact->Parent->Organisation->Name = $form->GetValue('orgName');
			$contact->Parent->Organisation->Address->Line1 = $contact->Person->Address->Line1;
			$contact->Parent->Organisation->Address->Line2 = $contact->Person->Address->Line2;
			$contact->Parent->Organisation->Address->Line3 = $contact->Person->Address->Line3;
			$contact->Parent->Organisation->Address->City = $contact->Person->Address->City;
			$contact->Parent->Organisation->Address->Region->ID = $contact->Person->Address->Region->ID;
			$contact->Parent->Organisation->Address->Country->ID = $contact->Person->Address->Country->ID;
			$contact->Parent->Organisation->Address->Zip = $contact->Person->Address->Zip;
			$contact->Parent->Organisation->Type = $form->GetValue('orgType');
			$contact->Parent->Organisation->Industry = $form->GetValue('orgIndustry');
			$contact->Parent->Organisation->Phone1 = $contact->Person->Phone1;
			$contact->Parent->Organisation->Phone1Ext = $contact->Person->Phone1Ext;
			$contact->Parent->Organisation->Phone2 = $contact->Person->Phone2;
			$contact->Parent->Organisation->Phone2Ext = $contact->Person->Phone2Ext;
			$contact->Parent->Organisation->Fax = $contact->Person->Fax;
			$contact->Parent->Organisation->Email = $contact->Person->Email;
			$contact->Parent->Organisation->Url = $form->GetValue('url');
			$contact->Parent->Organisation->CompanyNo = $form->GetValue('registrationNumber');
			$contact->Parent->Organisation->TaxNo = $form->GetValue('taxNumber');
			$contact->Parent->Add();

			$contact->Update();

			redirect(sprintf("Location:contact_profile.php?action=view&cid=%d", $contact->ID));
		}
	}

	$page = new Page(sprintf('<a href="?cid=%d">Individual: %s %s</a> &gt; Add To New Organisation', $contact->ID, $contact->Person->Name, $contact->Person->LastName));
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add a New Contact.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cid');

	echo $window->Open();
	echo $window->AddHeader('Please complete the following fields as accurately as and as many as possible.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('orgName'), $form->GetHTML('orgName') . $form->GetIcon('orgName'));
	echo $webForm->AddRow($form->GetLabel('orgType'), $form->GetHTML('orgType') . $form->GetIcon('orgType'));
	echo $webForm->AddRow($form->GetLabel('orgIndustry'), $form->GetHTML('orgIndustry') . $form->GetIcon('orgIndustry'));
	echo $webForm->AddRow($form->GetLabel('url'), $form->GetHTML('url') . $form->GetIcon('url'));
	echo $webForm->AddRow($form->GetLabel('registrationNumber'), $form->GetHTML('registrationNumber') . $form->GetIcon('registrationNumber'));
	echo $webForm->AddRow($form->GetLabel('taxNumber'), $form->GetHTML('taxNumber') . $form->GetIcon('taxNumber'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addChild() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

	$contact = new Contact($_REQUEST['parent']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addchild', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Parent ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('iscreditcontact', 'Is Credit Contact', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('personTitle', 'Title', 'select', '', 'anything', 0, 20, false);
	$form->AddOption('personTitle', '', '');

	$pTitle = new DataQuery('select * from person_title order by Person_Title asc');
	while($pTitle->Row){
		$form->AddOption('personTitle', $pTitle->Row['Person_Title'], $pTitle->Row['Person_Title']);
		$pTitle->Next();
	}
	$pTitle->Disconnect();

	$form->AddField('useParent', 'Use Parent Address', 'checkbox', 'N', 'boolean', 1, 1, false, 'onclick="insertAddress(this);"');
	$form->AddField('useParentPhone', 'Use Parent Telephone', 'checkbox', 'N', 'boolean', 1, 1, false, 'onclick="insertTelephone(this);"');

	$form->AddField('name', 'First Name', 'text', '', 'name', 1, 60, true);
	$form->AddField('initial', 'Initial', 'text', '', 'alpha', 1, 1, false);
	$form->AddField('surname', 'Last Name', 'text', '', 'name', 1, 60, true);
	$form->AddField('dept', 'Department', 'text', '', 'anything', 1, 40, false);
	$form->AddField('division', 'Division', 'text', '', 'anything', 1, 60, false);
	$form->AddField('position', 'Position', 'text', '', 'anything', 1, 100, false);
	$form->AddField('phone1', 'Phone 1', 'text', '', 'telephone', 1, 15, true);
	$form->AddField('phone1Ext', 'Phone 1 Extension', 'text', '', 'telephone', 1, 5, false);
	$form->AddField('phone2', 'Phone 2', 'text', '', 'telephone', 1, 15, false);
	$form->AddField('phone2Ext', 'Phone 2 Extension', 'text', '', 'telephone', 1, 5, false);
	$form->AddField('fax', 'Fax', 'text', '', 'telephone', 1, 15, false);
	$form->AddField('mobile', 'Mobile', 'text', '', 'telephone', 1, 15, false);
	$form->AddField('email', 'Email', 'text', '', 'email', NULL, NULL, true);
	$form->AddField('address1', 'Address Line 1', 'text', '', 'address', 1, 150, true);
	$form->AddField('address2', 'Address Line 2', 'text', '', 'address', 1, 150, true);
	$form->AddField('address3', 'Address Line 3', 'text', '', 'address', 1, 150, false);
	$form->AddField('city', 'City/Town', 'text', '', 'address', 1, 100, true);
	$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, true);
	$form->AddOption('region', '0', '');
	
	$regions = new DataQuery(sprintf("select * from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($GLOBALS['SYSTEM_COUNTRY'])));
	while($regions->Row){
		$form->AddOption('region', $regions->Row['Region_ID'], $regions->Row['Region_Name']);
		$regions->Next();
	}
	$regions->Disconnect();

	$form->AddField('country', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
	$form->AddOption('country', '0', '');
	$form->AddOption('country', '222', 'United Kingdom');

	$country = new DataQuery("select * from countries order by Country asc");
	while($country->Row){
		$form->AddOption('country', $country->Row['Country_ID'], $country->Row['Country']);
		$country->Next();
	}
	$country->Disconnect();

	$form->AddField('zip', 'Postal Code', 'text', '', 'alpha_numeric', 1, 10, true);
	$form->AddField('status', 'Status', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('status', '0', '');

	$data = new DataQuery('SELECT * FROM contact_status ORDER BY Name ASC');
	while($data->Row){
		$form->AddOption('status', $data->Row['Contact_Status_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$customer = new Customer();
			$customer->Username = $form->GetValue('email');
			$customer->Contact->Parent = $contact;
			$customer->Contact->Type = 'I';
			$customer->Contact->IsCreditContact = $form->GetValue('iscreditcontact');
			$customer->Contact->Status->ID = $form->GetValue('status');
			$customer->Contact->Person->Name = $form->GetValue('name');
			$customer->Contact->Person->Title = $form->GetValue('personTitle');
			$customer->Contact->Person->LastName = $form->GetValue('surname');
			$customer->Contact->Person->Initial = $form->GetValue('initial');
			$customer->Contact->Person->Department = $form->GetValue('dept');
			$customer->Contact->Person->Position = $form->GetValue('position');
			$customer->Contact->Person->Division = $form->GetValue('division');
			$customer->Contact->Person->Mobile = $form->GetValue('mobile');
			$customer->Contact->Person->Email = $form->GetValue('email');

			if($form->GetValue('useParentPhone') == 'Y') {
				$customer->Contact->Person->Phone1 = addslashes($customer->Contact->Parent->Organisation->Phone1);
				$customer->Contact->Person->Phone2 = addslashes($customer->Contact->Parent->Organisation->Phone2);
				$customer->Contact->Person->Fax = addslashes($customer->Contact->Parent->Organisation->Fax);
			} else {
				$customer->Contact->Person->Phone1 = $form->GetValue('phone1');
				$customer->Contact->Person->Phone1Ext = $form->GetValue('phone1Ext');
				$customer->Contact->Person->Phone2 = $form->GetValue('phone2');
				$customer->Contact->Person->Phone2Ext = $form->GetValue('phone2Ext');
				$customer->Contact->Person->Fax = $form->GetValue('fax');
			}

			if($form->GetValue('useParent') == 'Y') {
				$customer->Contact->Person->Address->Line1 = addslashes($customer->Contact->Parent->Organisation->Address->Line1);
				$customer->Contact->Person->Address->Line2 = addslashes($customer->Contact->Parent->Organisation->Address->Line2);
				$customer->Contact->Person->Address->Line3 = addslashes($customer->Contact->Parent->Organisation->Address->Line3);
				$customer->Contact->Person->Address->City = addslashes($customer->Contact->Parent->Organisation->Address->City);
				$customer->Contact->Person->Address->Region->ID = addslashes($customer->Contact->Parent->Organisation->Address->Region->ID);
				$customer->Contact->Person->Address->Country->ID = addslashes($customer->Contact->Parent->Organisation->Address->Country->ID);
				$customer->Contact->Person->Address->Zip = addslashes($customer->Contact->Parent->Organisation->Address->Zip);
			} else {
				$customer->Contact->Person->Address->Line1 = $form->GetValue('address1');
				$customer->Contact->Person->Address->Line2 = $form->GetValue('address2');
				$customer->Contact->Person->Address->Line3 = $form->GetValue('address3');
				$customer->Contact->Person->Address->City = $form->GetValue('city');
				$customer->Contact->Person->Address->Region->ID = $form->GetValue('region');
				$customer->Contact->Person->Address->Country->ID = $form->GetValue('country');
				$customer->Contact->Person->Address->Zip = $form->GetValue('zip');
			}

			$customer->Contact->Add();
			$customer->Add();
			
			$contact->Update();
			
			redirectTo('?cid=' . $customer->Contact->ID);
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var foundUser = function(id, firstName, lastName) {
			var e = document.getElementById(\'user\');

			if(e) {
				e.value = id;

				e = document.getElementById(\'userStr\');

				if(e) {
					e.value = firstName + \' \' + lastName;
				}
			}
		}
		</script>');

	$page = new Page(sprintf('<a href="contact_profile.php?action=view&cid=%d">%s</a> &gt; New Contact', $contact->ID, $contact->Organisation->Name),'Contacts are used throughout Ignition for Customers, Suppliers and Users. The more information you supply the better your system will become');
	$page->LinkScript('js/regions.php');
	$page->LinkScript('js/pcAnywhere.js');
	$page->AddToHead($script);
	$page->AddToHead("
		<script language=\"javascript\" type=\"text/javascript\">
			Address.account = '".$GLOBALS['POSTCODEANYWHERE_ACCOUNT']."';
			Address.licence = '".$GLOBALS['POSTCODEANYWHERE_LICENCE']."';

			Address.add('zip', 'line1', 'address2');
			Address.add('zip', 'line2', 'address3');
			Address.add('zip', 'line3', null);
			Address.add('zip', 'city', 'city');
			Address.add('zip', 'county', 'region');

			function insertAddress(obj) {
				if(obj.checked) {
					document.getElementById('zip').setAttribute('disabled', 'disabled');
					document.getElementById('address1').setAttribute('disabled', 'disabled');
					document.getElementById('address2').setAttribute('disabled', 'disabled');
					document.getElementById('address3').setAttribute('disabled', 'disabled');
					document.getElementById('city').setAttribute('disabled', 'disabled');
					document.getElementById('country').setAttribute('disabled', 'disabled');
					document.getElementById('region').setAttribute('disabled', 'disabled');

					document.getElementById('zip').value = '".addslashes($contact->Organisation->Address->Zip)."';
					document.getElementById('address1').value = '".addslashes($contact->Organisation->Address->Line1)."';
					document.getElementById('address2').value = '".addslashes($contact->Organisation->Address->Line2)."';
					document.getElementById('address3').value = '".addslashes($contact->Organisation->Address->Line3)."';
					document.getElementById('city').value = '".addslashes($contact->Organisation->Address->City)."';
					document.getElementById('country').value = '".addslashes($contact->Organisation->Address->Country->ID)."';
					document.getElementById('region').value = '".addslashes($contact->Organisation->Address->Region->ID)."';
				} else {
					document.getElementById('zip').removeAttribute('disabled');
					document.getElementById('address1').removeAttribute('disabled');
					document.getElementById('address2').removeAttribute('disabled');
					document.getElementById('address3').removeAttribute('disabled');
					document.getElementById('city').removeAttribute('disabled');
					document.getElementById('country').removeAttribute('disabled');
					document.getElementById('region').removeAttribute('disabled');
				}
			}

			function insertTelephone(obj) {
				if(obj.checked) {
					document.getElementById('phone1').setAttribute('disabled', 'disabled');
					document.getElementById('phone2').setAttribute('disabled', 'disabled');
					document.getElementById('fax').setAttribute('disabled', 'disabled');

					document.getElementById('phone1').value = '".addslashes($contact->Organisation->Phone1)."';
					document.getElementById('phone2').value = '".addslashes($contact->Organisation->Phone2)."';
					document.getElementById('fax').value = '".addslashes($contact->Organisation->Fax)."';
				} else {
					document.getElementById('phone1').removeAttribute('disabled');
					document.getElementById('phone2').removeAttribute('disabled');
					document.getElementById('fax').removeAttribute('disabled');
				}
			}

			window.onload = function() {
				if(".(($form->GetValue('useParent') == 'Y') ? 'true' : 'false').") {
					insertAddress(document.getElementById('useParent'));
				}

				if(".(($form->GetValue('useParentPhone') == 'Y') ? 'true' : 'false').") {
					insertTelephone(document.getElementById('useParentPhone'));
				}
			}
		</script>");

	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Add a New Contact.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');
	
	echo $window->Open();
	echo $window->AddHeader('Please complete the following fields as accurately as and as many as possible.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('iscreditcontact'), $form->GetHTML('iscreditcontact') . $form->GetIcon('iscreditcontact'));
	echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status') . $form->GetIcon('status'));
	echo $webForm->AddRow($form->GetLabel('personTitle'), $form->GetHTML('personTitle') . $form->GetIcon('personTitle'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('initial'), $form->GetHTML('initial') . $form->GetIcon('initial'));
	echo $webForm->AddRow($form->GetLabel('surname'), $form->GetHTML('surname') . $form->GetIcon('surname'));
	echo $webForm->AddRow($form->GetLabel('dept'), $form->GetHTML('dept') . $form->GetIcon('dept'));
	echo $webForm->AddRow($form->GetLabel('division'), $form->GetHTML('division') . $form->GetIcon('division'));
	echo $webForm->AddRow($form->GetLabel('position'), $form->GetHTML('position') . $form->GetIcon('position'));
	echo $webForm->AddRow($form->GetLabel('mobile'), $form->GetHTML('mobile') . $form->GetIcon('mobile'));
	echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email') . $form->GetIcon('email'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Please complete the following fields as accurately as and as many as possible.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('useParentPhone'), $form->GetHTML('useParentPhone'));
	echo $webForm->AddRow($form->GetLabel('phone1'), $form->GetHTML('phone1') . $form->GetIcon('phone1'));
	echo $webForm->AddRow($form->GetLabel('phone2'), $form->GetHTML('phone2') . $form->GetIcon('phone2'));
	echo $webForm->AddRow($form->GetLabel('fax'), $form->GetHTML('fax') . $form->GetIcon('fax'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Send invoices to the below address.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('useParent'), $form->GetHTML('useParent'));
	echo $webForm->AddRow($form->GetLabel('zip'), $form->GetHTML('zip') . $form->GetIcon('zip') . '<a href="javascript:Address.find(document.getElementById(\'zip\'));"><img src="../images/searchIcon.gif" border="0" align="absmiddle" /> Auto-complete address (UK residents)</a>');
	echo $webForm->AddRow($form->GetLabel('address1'), $form->GetHTML('address1') . $form->GetIcon('address1'));
	echo $webForm->AddRow($form->GetLabel('address2'), $form->GetHTML('address2') . $form->GetIcon('address2'));
	echo $webForm->AddRow($form->GetLabel('address3'), $form->GetHTML('address3') . $form->GetIcon('address3'));
	echo $webForm->AddRow($form->GetLabel('city'), $form->GetHTML('city') . $form->GetIcon('city'));
	echo $webForm->AddRow($form->GetLabel('country'), $form->GetHTML('country') . $form->GetIcon('country'));
	echo $webForm->AddRow($form->GetLabel('region'), $form->GetHTML('region') . $form->GetIcon('region'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('account', 'Account Manager', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('account', '0', '');

	$data = new DataQuery('SELECT u.User_ID, p.Name_First, p.Name_Last FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY p.Name_First, p.Name_Last ASC');
	while($data->Row){
		$form->AddOption('account', $data->Row['User_ID'], trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])));
		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('contactType', 'Contact Type', 'select', 'O', 'alpha', 1, 1);
	$form->AddOption('contactType', 'O', 'Organisation');
	$form->AddOption('contactType', 'I', 'Individual');
	$form->AddField('orgName', 'Organisation Name', 'text', '', 'alpha_numeric', 1, 100, false);
	$form->AddField('orgType', 'Organisation Type', 'select', '', 'numeric_unsigned', 1, 11, false);
	$form->AddOption('orgType', '', 'Select...');
	$orgTypes = new DataQuery('select * from organisation_type order by Org_Type asc');
	while($orgTypes->Row){
		$form->AddOption('orgType', $orgTypes->Row['Org_Type_ID'], $orgTypes->Row['Org_Type']);
		$orgTypes->Next();
	}
	$orgTypes->Disconnect();
	$form->AddField('orgIndustry', 'Organisation Industry', 'select', '', 'numeric_unsigned', 1, 11, false);
	$form->AddOption('orgIndustry', '', 'Select...');
	$orgInd = new DataQuery('select * from organisation_industry order by Industry_Name asc');
	while($orgInd->Row){
		$form->AddOption('orgIndustry', $orgInd->Row['Industry_ID'], $orgInd->Row['Industry_Name']);
		$orgInd->Next();
	}
	$orgInd->Disconnect();
	$form->AddField('url', 'Organisation Website', 'text', '', 'link', NULL, NULL, false);
	$form->AddField('registrationNumber', 'Organisation Registration Number', 'text', '', 'alpha_numeric', 1, 100, false);
	$form->AddField('taxNumber', 'Tax Registration/VAT Number', 'text', '', 'alpha_numeric', 1, 100, false);
	$form->AddField('personTitle', 'Title', 'select', '', 'alpha_numeric', 1, 11);
	$form->AddOption('personTitle', '', 'Select...');
	$pTitle = new DataQuery('select * from person_title order by Person_Title asc');
	while($pTitle->Row){
		$form->AddOption('personTitle', $pTitle->Row['Person_Title'], $pTitle->Row['Person_Title']);
		$pTitle->Next();
	}
	$pTitle->Disconnect();
	$form->AddField('name', 'First Name', 'text', '', 'name', 1, 60,true);
	$form->AddField('initial', 'Initial', 'text', '', 'alpha', 1, 1, false);
	$form->AddField('surname', 'Last Name', 'text', '', 'name', 1, 60,true);
	$form->AddField('dept', 'Department', 'text', '', 'alpha_numeric', 1, 40, false);
	$form->AddField('division', 'Division', 'text', '', 'alpha_numeric', 1, 60, false);
	$form->AddField('position', 'Position', 'text', '', 'alpha_numeric', 1, 100, false);
	$year = cDatetime(getDatetime(), 'y');
	$form->AddField('dob', 'Date of Birth', 'datetime', '0000-00-00 00:00:00', 'datetime', $year-101, $year-1, false);
	$form->AddField('gender', 'Gender', 'select', 'M', 'alpha', 1, 1, false);
	$form->AddOption('gender', '', 'Select...');
	$form->AddOption('gender', 'M', 'Male');
	$form->AddOption('gender', 'F', 'Female');

	$form->AddField('phone1', 'Phone 1', 'text', '', 'telephone', 1, 15, true);
	$form->AddField('phone1Ext', 'Phone 1 Extension', 'text', '', 'telephone', 1, 5, false);
	$form->AddField('phone2', 'Phone 2', 'text', '', 'telephone', 1, 15, false);
	$form->AddField('phone2Ext', 'Phone 2 Extension', 'text', '', 'telephone', 1, 5, false);
	$form->AddField('fax', 'Fax', 'text', '', 'telephone', 1, 15, false);
	$form->AddField('mobile', 'Mobile', 'text', '', 'telephone', 1, 15, false);
	$form->AddField('email', 'Email', 'text', '', 'email', NULL, NULL, true);
	$form->AddField('address1', 'Address Line 1', 'text', '', 'address', 1, 150, true);
	$form->AddField('address2', 'Address Line 2', 'text', '', 'address', 1, 150, true);
	$form->AddField('address3', 'Address Line 3', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('city', 'City/Town', 'text', '', 'address', 1, 100, true);
	$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, true);
	$form->AddOption('region', '0', '');
	$regions = new DataQuery(sprintf("select * from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($GLOBALS['SYSTEM_COUNTRY'])));
	while($regions->Row){
		$form->AddOption('region', $regions->Row['Region_ID'], $regions->Row['Region_Name']);
		$regions->Next();
	}
	$regions->Disconnect();

	$form->AddField('country', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
	$form->AddOption('country', '0', '');
	$form->AddOption('country', '222', 'United Kingdom');

	$country = new DataQuery("select * from countries order by Country asc");
	while($country->Row){
		$form->AddOption('country', $country->Row['Country_ID'], $country->Row['Country']);
		$country->Next();
	}
	$country->Disconnect();

	$form->AddField('zip', 'Postal Code', 'text', '', 'alpha_numeric', 1, 10, true);

	$form->AddField('status', 'Status', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('status', '0', '');

	$data = new DataQuery('SELECT * FROM contact_status ORDER BY Name ASC');
	while($data->Row){
		$form->AddOption('status', $data->Row['Contact_Status_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$customer = new Customer();
			$customer->Username = $form->GetValue('email');
			$customer->Contact->Type = 'I';
			$customer->Contact->Status->ID = $form->GetValue('status');
			$customer->Contact->Person->Name = $form->GetValue('name');
			$customer->Contact->Person->Title = $form->GetValue('personTitle');
			$customer->Contact->Person->LastName = $form->GetValue('surname');
			$customer->Contact->Person->Initial = $form->GetValue('initial');
			$customer->Contact->Person->DOB = $form->GetValue('dob');
			$customer->Contact->Person->Department = $form->GetValue('dept');
			$customer->Contact->Person->Position = $form->GetValue('position');
			$customer->Contact->Person->Division = $form->GetValue('division');
			$customer->Contact->Person->Gender = $form->GetValue('gender');
			$customer->Contact->Person->Phone1 = $form->GetValue('phone1');
			$customer->Contact->Person->Phone1Ext = $form->GetValue('phone1Ext');
			$customer->Contact->Person->Phone2 = $form->GetValue('phone2');
			$customer->Contact->Person->Phone2Ext = $form->GetValue('phone2Ext');
			$customer->Contact->Person->Fax = $form->GetValue('fax');
			$customer->Contact->Person->Mobile = $form->GetValue('mobile');
			$customer->Contact->Person->Email = $form->GetValue('email');
			$customer->Contact->Person->Address->Line1 = $form->GetValue('address1');
			$customer->Contact->Person->Address->Line2 = $form->GetValue('address2');
			$customer->Contact->Person->Address->Line3 = $form->GetValue('address3');
			$customer->Contact->Person->Address->City = $form->GetValue('city');
			$customer->Contact->Person->Address->Region->ID = $form->GetValue('region');
			$customer->Contact->Person->Address->Country->ID = $form->GetValue('country');
			$customer->Contact->Person->Address->Zip = $form->GetValue('zip');
			$customer->Contact->AccountManager->ID = $form->GetValue('account');

			if($form->GetValue('contactType') == "O"){
				$customer->Contact->Parent = new Contact();
				$customer->Contact->Parent->Type = 'O';
				$customer->Contact->Parent->Organisation->Name = $form->GetValue('orgName');
				$customer->Contact->Parent->Organisation->Address->Line1 = $form->GetValue('address1');
				$customer->Contact->Parent->Organisation->Address->Line2 = $form->GetValue('address2');
				$customer->Contact->Parent->Organisation->Address->Line3 = $form->GetValue('address3');
				$customer->Contact->Parent->Organisation->Address->City = $form->GetValue('city');
				$customer->Contact->Parent->Organisation->Address->Region->ID = $form->GetValue('region');
				$customer->Contact->Parent->Organisation->Address->Country->ID = $form->GetValue('country');
				$customer->Contact->Parent->Organisation->Address->Zip = $form->GetValue('zip');
				$customer->Contact->Parent->Organisation->InvoiceAddress = $customer->Contact->Parent->Organisation->Address;
				$customer->Contact->Parent->Organisation->Type = $form->GetValue('orgType');
				$customer->Contact->Parent->Organisation->Industry = $form->GetValue('orgIndustry');
				$customer->Contact->Parent->Organisation->Phone1 = $form->GetValue('phone1');
				$customer->Contact->Parent->Organisation->Phone1Ext = $form->GetValue('phone1Ext');
				$customer->Contact->Parent->Organisation->Phone2 = $form->GetValue('phone2');
				$customer->Contact->Parent->Organisation->Phone2Ext = $form->GetValue('phone2Ext');
				$customer->Contact->Parent->Organisation->Fax = $form->GetValue('fax');
				$customer->Contact->Parent->Organisation->Email = $form->GetValue('email');
				$customer->Contact->Parent->Organisation->Url = $form->GetValue('url');
				$customer->Contact->Parent->Organisation->CompanyNo = $form->GetValue('registrationNumber');
				$customer->Contact->Parent->Organisation->TaxNo = $form->GetValue('taxNumber');
			}
			
			$customer->Contact->Add();
			$customer->Contact->UpdateAccountManager();
			$customer->Add();

			redirect(sprintf("Location:contact_profile.php?action=view&cid=%d", $customer->Contact->ID));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var foundUser = function(id, firstName, lastName) {
			var e = document.getElementById(\'user\');

			if(e) {
				e.value = id;

				e = document.getElementById(\'userStr\');

				if(e) {
					e.value = firstName + \' \' + lastName;
				}
			}
		}
		</script>');

	$page = new Page('Create a New Contact','Contacts are used throughout Ignition for Customers, Suppliers and Users. The more information you supply the better your system will become');
	$page->LinkScript('js/regions.php');
	$page->AddToHead($script);
	$page->SetFocus('contactType');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add a New Contact.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');

	echo $window->Open();
	echo $window->AddHeader('Please complete the following fields as accurately as and as many as possible.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('account'), $form->GetHTML('account') . $form->GetIcon('account'));
	echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status') . $form->GetIcon('status'));
	echo $webForm->AddRow($form->GetLabel('contactType'), $form->GetHTML('contactType') . $form->GetIcon('contactType'));
	echo $webForm->AddRow($form->GetLabel('orgName'), $form->GetHTML('orgName') . $form->GetIcon('orgName'));
	echo $webForm->AddRow($form->GetLabel('orgType'), $form->GetHTML('orgType') . $form->GetIcon('orgType'));
	echo $webForm->AddRow($form->GetLabel('orgIndustry'), $form->GetHTML('orgIndustry') . $form->GetIcon('orgIndustry'));
	echo $webForm->AddRow($form->GetLabel('url'), $form->GetHTML('url') . $form->GetIcon('url'));
	echo $webForm->AddRow($form->GetLabel('registrationNumber'), $form->GetHTML('registrationNumber') . $form->GetIcon('registrationNumber'));
	echo $webForm->AddRow($form->GetLabel('taxNumber'), $form->GetHTML('taxNumber') . $form->GetIcon('taxNumber'));
	echo $webForm->AddRow($form->GetLabel('personTitle'), $form->GetHTML('personTitle') . $form->GetIcon('personTitle'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('initial'), $form->GetHTML('initial') . $form->GetIcon('initial'));
	echo $webForm->AddRow($form->GetLabel('surname'), $form->GetHTML('surname') . $form->GetIcon('surname'));
	echo $webForm->AddRow($form->GetLabel('dept'), $form->GetHTML('dept') . $form->GetIcon('dept'));
	echo $webForm->AddRow($form->GetLabel('division'), $form->GetHTML('division') . $form->GetIcon('division'));
	echo $webForm->AddRow($form->GetLabel('position'), $form->GetHTML('position') . $form->GetIcon('position'));
	echo $webForm->AddRow($form->GetLabel('dob'), $form->GetHTML('dob') . $form->GetIcon('dob'));
	echo $webForm->AddRow($form->GetLabel('gender'), $form->GetHTML('gender') . $form->GetIcon('gender'));
	echo $webForm->AddRow($form->GetLabel('phone1'), $form->GetHTML('phone1') . $form->GetIcon('phone1'));
	echo $webForm->AddRow($form->GetLabel('phone1Ext'), $form->GetHTML('phone1Ext') . $form->GetIcon('phone1Ext'));
	echo $webForm->AddRow($form->GetLabel('phone2'), $form->GetHTML('phone2') . $form->GetIcon('phone2'));
	echo $webForm->AddRow($form->GetLabel('phone2Ext'), $form->GetHTML('phone2Ext') . $form->GetIcon('phone2Ext'));
	echo $webForm->AddRow($form->GetLabel('fax'), $form->GetHTML('fax') . $form->GetIcon('fax'));
	echo $webForm->AddRow($form->GetLabel('mobile'), $form->GetHTML('mobile') . $form->GetIcon('mobile'));
	echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email') . $form->GetIcon('email'));
	echo $webForm->AddRow($form->GetLabel('address1'), $form->GetHTML('address1') . $form->GetIcon('address1'));
	echo $webForm->AddRow($form->GetLabel('address2'), $form->GetHTML('address2') . $form->GetIcon('address2'));
	echo $webForm->AddRow($form->GetLabel('address3'), $form->GetHTML('address3') . $form->GetIcon('address3'));
	echo $webForm->AddRow($form->GetLabel('city'), $form->GetHTML('city') . $form->GetIcon('city'));
	echo $webForm->AddRow($form->GetLabel('country'), $form->GetHTML('country') . $form->GetIcon('country'));
	echo $webForm->AddRow($form->GetLabel('region'), $form->GetHTML('region') . $form->GetIcon('region'));
	echo $webForm->AddRow($form->GetLabel('zip'), $form->GetHTML('zip') . $form->GetIcon('zip'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$customer->Contact = new Contact($_REQUEST['cid']);
	
	if($customer->Contact->Type == 'O') {
		if($customer->Contact->AccountManager->ID > 0) {
			$customer->Contact->AccountManager->Get();
		}
	
		$page = new Page($customer->Contact->Organisation->Name);
		$page->Display('header');
		
		$orders = new DataQuery(sprintf("SELECT COUNT(DISTINCT Order_ID) AS Count FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$orderCount = $orders->Row['Count'];
		$orders->Disconnect();
		
		$orders = new DataQuery(sprintf("SELECT COUNT(DISTINCT Order_ID) AS Count FROM order_document AS od INNER JOIN orders AS o ON o.Order_ID=od.orderId INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$orderPurchaseCount = $orders->Row['Count'];
		$orders->Disconnect();

		$quotes = new DataQuery(sprintf("SELECT COUNT(DISTINCT Quote_ID) AS Count FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$quoteCount = $quotes->Row['Count'];
		$quotes->Disconnect();

		$invoices = new DataQuery(sprintf("SELECT COUNT(DISTINCT Invoice_ID) AS Count FROM invoice AS i INNER JOIN customer AS c ON c.Customer_ID=i.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$invoiceCount = $invoices->Row['Count'];
		$invoices->Disconnect();

		$returns = new DataQuery(sprintf("SELECT COUNT(DISTINCT Return_ID) AS Count FROM `return` AS r INNER JOIN customer AS c ON c.Customer_ID=r.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$returnCount = $returns->Row['Count'];
		$returns->Disconnect();

		$credits = new DataQuery(sprintf("SELECT COUNT(DISTINCT Credit_Note_ID) AS Count FROM credit_note AS d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$creditCount = $credits->Row['Count'];
		$credits->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM contact_document WHERE contactId=%d", mysql_real_escape_string($customer->Contact->ID)));
		$documentCount = $data->Row['count'];
		$data->Disconnect();
	
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM contact_note WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$noteCount = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM campaign_contact AS cc INNER JOIN contact AS c ON cc.Contact_ID=c.Contact_ID WHERE c.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$campaignCount = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT Enquiry_ID) AS Count FROM enquiry AS e INNER JOIN customer AS c ON c.Customer_ID=e.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$enquiryCount = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_product AS cp INNER JOIN product AS p ON p.Product_ID=cp.Product_ID INNER JOIN customer AS c ON c.Customer_ID=cp.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE n.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$productCount = $data->Row['Count'];
		$data->Disconnect();
		
		$options = array();
		$options[] = sprintf('<li><a href="organisation_orders.php?ocid=%1$d">Orders (%2$d)</a><br /><a href="organisation_order_purchases.php?ocid=%1$d">Order Purchases (%3$d)</a>', $customer->Contact->ID, $orderCount, $orderPurchaseCount);
		$options[] = sprintf('<li><a href="organisation_quotes.php?ocid=%1$d">Quotes (%2$d)</a></li>', $customer->Contact->ID, $quoteCount);
		$options[] = sprintf('<li><a href="organisation_invoices.php?ocid=%1$d">Invoices (%2$d)</a></li>', $customer->Contact->ID, $invoiceCount);
		$options[] = sprintf('<li><a href="organisation_credits.php?ocid=%1$d">Credit Notes (%2$d)</a></li>', $customer->Contact->ID, $creditCount);
		$options[] = sprintf('<li><a href="organisation_returns.php?ocid=%1$d">Returns (%2$d)</a></li>', $customer->Contact->ID, $returnCount);
		$options[] = sprintf('<li><a href="organisation_products.php?ocid=%1$d">Products (%2$d)</a></li>', $customer->Contact->ID, $productCount);
		$options[] = sprintf('<li><a href="contact_notes.php?cid=%1$d">Notes (%2$d)</a></li>', $customer->Contact->ID, $noteCount);
		$options[] = sprintf('<li><a href="contact_documents.php?cid=%1$d">Documents (%2$d)</a></li>', $customer->Contact->ID, $documentCount);
		$options[] = sprintf('<li><a href="organisation_campaigns.php?ocid=%1$d">Campaigns (%2$d)</a></li>', $customer->Contact->ID, $campaignCount);
		$options[] = sprintf('<li><a href="organisation_enquiries.php?ocid=%1$d">Enquiries (%2$d)</a></li>', $customer->Contact->ID, $enquiryCount);

		echo sprintf('<div class="contactOptions"><ul>%s</ul></div>', implode($options));
		?>

		<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td valign="top" width="49.5%">
				
					<h3>Organisation Details</h3>
					<br />
				
					<table width="100%" class="invoiceAddresses" style="background-color: #eee;">
						<tr>
							<td valign="top">
								<strong>Organisation Address</strong><br />
								<?php
								echo $customer->Contact->Organisation->Name;
								echo '<br />';
								echo $customer->Contact->Organisation->Address->GetLongString();
								?>
							</td>
							<td valign="top">
								<strong>Account Address</strong>
								<br />
								<?php
								if($customer->Contact->Organisation->UseInvoiceAddress == 'Y') {
									echo $customer->Contact->Organisation->InvoiceName;
									echo '<br />';
									echo $customer->Contact->Organisation->InvoiceAddress->GetLongString();
								} else {
									echo $customer->Contact->Organisation->Name;
									echo '<br />';
									echo $customer->Contact->Organisation->Address->GetLongString();
								}
								?>
							</td>
						</tr>
						<tr>
							<td>
								<strong>Sage Account Reference</strong><br />
								<?php echo $customer->Contact->IntegrationReference; ?>
							</td>
							<td>
								<strong>Account Manager</strong><br />
								<?php echo trim(sprintf('%s %s', $customer->Contact->AccountManager->Person->Name, $customer->Contact->AccountManager->Person->LastName)); ?>
							</td>
						</tr>
						<tr>
							<td>
								<strong>Phone (#1)</strong><br />
								<?php echo sprintf('%s%s', $customer->Contact->Organisation->Phone1, !empty($customer->Contact->Organisation->Phone1Ext) ? sprintf(' (ext; %s)', $customer->Contact->Organisation->Phone1Ext) : ''); ?>
							</td>
							<td>
								<strong>Phone (#2)</strong><br />
								<?php echo sprintf('%s%s', $customer->Contact->Organisation->Phone2, !empty($customer->Contact->Organisation->Phone2Ext) ? sprintf(' (ext; %s)', $customer->Contact->Organisation->Phone2Ext) : ''); ?>
							</td>
						</tr>
						<tr>
							<td>
								<strong>Invoice Phone</strong><br />
								<?php echo $customer->Contact->Organisation->InvoicePhone; ?>
							</td>
							<td>
								<strong>Invoice Email</strong><br />
								<?php echo $customer->Contact->Organisation->InvoiceEmail; ?>
							</td>
						</tr>
						<tr>
							<td>
								<strong>Fax</strong><br />
								<?php echo $customer->Contact->Organisation->Fax; ?>
							</td>
							<td>
								<strong>Email</strong><br />
								<?php echo $customer->Contact->Organisation->Email; ?>
							</td>
						</tr>
						<tr>
							<td>
								<strong>VAT Number</strong><br />
								<?php echo $customer->Contact->Organisation->TaxNo; ?>
							</td>
							<td>
								<strong>Registration Number</strong><br />
								<?php echo $customer->Contact->Organisation->CompanyNo; ?>
							</td>
						</tr>
					</table>
					
					<br />
					<input name="edit" type="button" value="edit profile" class="btn" onclick="window.location.href = 'organisation_profile.php?cid=<?php echo $customer->Contact->ID; ?>'" />
					
				</td>
				<td valign="top" width="1%"></td>
				<td valign="top" width="49.5%">
				
					<h3>Contacts</h3>
					<br />
					
					<?php
					$table = new DataTable('contacts');
					$table->SetExtractVarsLink(array('action,confirm,cid'));
					$table->SetSQL(sprintf("SELECT c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name, p.Department, IF(cu.Customer_ID IS NULL, 'N', 'Y') AS Is_Customer FROM contact AS c INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID WHERE c.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
					$table->AddField('ID#', 'Contact_ID', 'left');
					$table->AddField('Contact', 'Contact_Name', 'left');
					$table->AddField('Department', 'Department', 'left');
					$table->AddField('Customer', 'Is_Customer', 'center');
					$table->AddLink("?cid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
					$table->AddLink("javascript:confirmRequest('contact_profile.php?action=removechild&cid=%s', 'Are you sure you want to remove this contact?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Contact_ID");
					$table->SetMaxRows(25);
					$table->SetOrderBy('Contact_ID');
					$table->Finalise();
					$table->DisplayTable();
					echo '<br />';
					$table->DisplayNavigation();
					?>
					
					<br />
		    		<input name="add" type="button" value="new contact" class="btn" onclick="window.location.href = '?action=addchild&parent=<?php echo $customer->Contact->ID; ?>';" />
	        
				</td>
			</tr>
		</table>
		
		<?php
	} elseif($customer->Contact->Type == 'I') {
		if($customer->Contact->Status->ID > 0) {
			$customer->Contact->Status->Get();
		}
		
		if($customer->Contact->AccountManager->ID > 0) {
			$customer->Contact->AccountManager->Get();
		}
		
		$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$customerId = $data->Row['Customer_ID'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT Supplier_ID, Is_Comparable FROM supplier WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$supplierId = $data->Row['Supplier_ID'];
		$supplierIsComparable = $data->Row['Is_Comparable'];
		$data->Disconnect();
		
		$options = array();
		
		if(empty($customerId)) {
			$options[] = sprintf('<li><a href="?action=makecustomer&cid=%1$d">Make Customer</a></li>', $customer->Contact->ID);
		} else {
			$data = new DataQuery(sprintf("select count(Order_ID) as Count from orders where Customer_ID=%d AND Status NOT IN ('Incomplete', 'Unauthenticated')", mysql_real_escape_string($customerId)));

			$orderCount = $data->Row['Count'];
			$data->Disconnect();
			
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM order_document AS od INNER JOIN orders AS o ON o.Order_ID=od.orderId WHERE od.type LIKE 'Purchase Order' AND o.Customer_ID=%d", mysql_real_escape_string($customerId)));
			$orderPurchaseCount = $data->Row['Count'];
			$data->Disconnect();
			
			$data = new DataQuery(sprintf("select count(Quote_ID) as Count from quote where Customer_ID=%d", mysql_real_escape_string($customerId)));
			$quoteCount = $data->Row['Count'];
			$data->Disconnect();

			$data = new DataQuery(sprintf("select count(Invoice_ID) as Count from invoice where Customer_ID=%d", mysql_real_escape_string($customerId)));
			$invoiceCount = $data->Row['Count'];
			$data->Disconnect();

			$data = new DataQuery(sprintf("select count(Return_ID) as Count from `return` where Customer_ID=%d", mysql_real_escape_string($customerId)));
			$returnCount = $data->Row['Count'];
			$data->Disconnect();

			$data = new DataQuery(sprintf("select count(c.Credit_Note_ID) as Count from credit_note as c INNER JOIN orders AS o ON o.Order_ID=c.Order_ID where o.Customer_ID=%d", mysql_real_escape_string($customerId)));
			$creditCount = $data->Row['Count'];
			$data->Disconnect();

			$data = new DataQuery(sprintf("select count(*) as Count from customer_product AS cp INNER JOIN product AS p ON p.Product_ID=cp.Product_ID where cp.Customer_ID=%d", mysql_real_escape_string($customerId)));
			$productCount = $data->Row['Count'];
			$data->Disconnect();
			
			$data = new DataQuery(sprintf("select count(*) as Count from enquiry where Customer_ID=%d", mysql_real_escape_string($customerId)));
			$enquiryCount = $data->Row['Count'];
			$data->Disconnect();
			
			$options[] = sprintf('<li><a href="customer_orders.php?customer=%1$d">Orders (%2$d)</a></li>', $customerId, $orderCount);
			$options[] = sprintf('<li><a href="customer_order_purchases.php?customer=%1$d">Orders Purchases (%2$d)</a></li>', $customerId, $orderPurchaseCount);
			$options[] = sprintf('<li><a href="customer_quotes.php?customer=%1$d">Quotes (%2$d)</a></li>', $customerId, $quoteCount);
			$options[] = sprintf('<li><a href="customer_invoices.php?customer=%1$d">Invoices (%2$d)</a></li>', $customerId, $invoiceCount);
			$options[] = sprintf('<li><a href="customer_returns.php?customer=%1$d">Returns (%2$d)</a></li>', $customerId, $returnCount);
			$options[] = sprintf('<li><a href="customer_credits.php?customer=%1$d">Credits (%2$d)</a></li>', $customerId, $creditCount);
			$options[] = sprintf('<li><a href="customer_products.php?customer=%1$d">Products (%2$d)</a></li>', $customerId, $productCount);
			$options[] = sprintf('<li><a href="customer_enquiries.php?customer=%1$d">Enquiries (%2$d)</a></li>', $customerId, $enquiryCount);
			$options[] = sprintf('<li><a href="customer_affiliate.php?customer=%1$d">Affiliate Information</a></li>', $customerId);
			$options[] = sprintf('<li><a href="discount_schema_customer.php?customer=%1$d">Discount Schema Options</a></li>', $customerId);
			$options[] = sprintf('<li><a href="customer_credit.php?customer=%1$d">Credit Account Settings</a></li>', $customerId);
			$options[] = sprintf('<li><a href="customer_security.php?id=%1$d">Customer Security</a></li>', $customerId);
		}

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM contact_note WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$noteCount = $data->Row['Count'];
		$data->Disconnect();
		
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM contact_document WHERE contactId=%d", mysql_real_escape_string($customer->Contact->ID)));
		$documentCount = $data->Row['count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM contact_schedule WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$scheduleCount = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM campaign_contact WHERE Contact_ID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$campaignCount = $data->Row['Count'];
		$data->Disconnect();

	    $data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM contact_appointment WHERE ContactID=%d", mysql_real_escape_string($customer->Contact->ID)));
		$appointmentCount = $data->Row['Count'];
		$data->Disconnect();
		
		$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM contact_product_trade WHERE contactId=%d", mysql_real_escape_string($customer->Contact->ID)));
		$tradeCount = $data->Row['count'];
		$data->Disconnect();

		if(!empty($supplierId)) {
			$data = new DataQuery(sprintf('SELECT COUNT(Supplier_Product_ID) AS Count FROM product AS p INNER JOIN supplier_product AS s ON s.Product_ID=p.Product_ID AND s.Supplier_ID=%1$d WHERE p.LockedSupplierID=%1$d OR p.DropSupplierID=%1$d', mysql_real_escape_string($supplierId)));
			$productCount = $data->Row['Count'];
			$data->Disconnect();

			$options[] = sprintf('<li><a href="products_supplier.php?sid=%d&cid=%d">Products Supplied (%d)</a></li>', $supplierId, $customer->Contact->ID, $productCount);
			$options[] = sprintf('<li><a href="supplier_security.php?id=%1$d&cid=%d">Supplier Security</a></li>', $supplierId, $customer->Contact->ID);
			$options[] = sprintf('<li><a href="supplier_settings.php?sid=%d&cid=%d">Supplier Settings</a></li>', $supplierId, $customer->Contact->ID);
			
			if($supplierIsComparable == 'Y') {
				$options[] = sprintf('<li><a href="supplier_categories.php?sid=%d&cid=%d">Supplier Categories</a></li>', $supplierId, $customer->Contact->ID);
			}
		}

		$options[] = sprintf('<li><a href="contact_notes.php?cid=%1$d">Notes (%2$d)</a></li>', $customer->Contact->ID, $noteCount);
		$options[] = sprintf('<li><a href="contact_documents.php?cid=%1$d">Documents (%2$d)</a></li>', $customer->Contact->ID, $documentCount);
		$options[] = sprintf('<li><a href="contact_schedules.php?cid=%1$d">Schedules (%2$d)</a></li>', $customer->Contact->ID, $scheduleCount);
		$options[] = sprintf('<li><a href="contact_campaigns.php?cid=%1$d">Campaigns (%2$d)</a></li>', $customer->Contact->ID, $campaignCount);
		$options[] = sprintf('<li><a href="contact_appointments.php?cid=%1$d">Appointments (%2$d)</a></li>', $customer->Contact->ID, $appointmentCount);
		$options[] = sprintf('<li><a href="contact_trade_products.php?cid=%1$d">Trade Products (%2$d)</a></li>', $customer->Contact->ID, $tradeCount);
		$options[] = sprintf('<li><a href="contact_proforma_account.php?cid=%1$d">Proforma Account Settings</a></li>', $customer->Contact->ID);
		$options[] = sprintf('<li><a href="?action=requestcatalogue&cid=%1$d">Request Catalogue</a></li>', $customer->Contact->ID);
		
		if($customer->Contact->Parent->ID > 0) {
			$page = new Page(sprintf('<a href="?cid=%d">%s</a> &gt; %s', $customer->Contact->Parent->ID, $customer->Contact->Parent->Organisation->Name, trim(sprintf('%s %s', $customer->Contact->Person->Name, $customer->Contact->Person->LastName))));
			$page->Display('header');
		} else {
			$page = new Page(trim(sprintf('%s %s', $customer->Contact->Person->Name, $customer->Contact->Person->LastName)));
			$page->Display('header');
		}
		
		echo sprintf('<div class="contactOptions"><ul>%s</ul></div>', implode($options));
		?>

		<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td valign="top" width="49.5%">
				
					<h3>Personal Details</h3>
					<br />
				
					<table width="100%" class="invoiceAddresses" style="background-color: #e6f1fa;">
						<tr>
							<td valign="top">
								<strong>Personal Address</strong><br />
								<?php
								echo trim(sprintf('%s %s', $customer->Contact->Person->Name, $customer->Contact->Person->LastName));
								echo '<br />';
								echo $customer->Contact->Person->Address->GetLongString();
								?>
							</td>
							<td valign="top"></td>
						</tr>
						<tr>
							<td>
								<strong>Sage Account Reference</strong><br />
								<?php echo $customer->Contact->IntegrationReference; ?>
							</td>
							<td>
								<strong>Account Manager</strong><br />
								<?php echo trim(sprintf('%s %s', $customer->Contact->AccountManager->Person->Name, $customer->Contact->AccountManager->Person->LastName)); ?>
							</td>
						</tr>
						<tr>
							<td>
								<strong>Phone (#1)</strong><br />
								<?php echo sprintf('%s%s', $customer->Contact->Person->Phone1, !empty($customer->Contact->Person->Phone1Ext) ? sprintf(' (ext; %s)', $customer->Contact->Person->Phone1Ext) : ''); ?>
							</td>
							<td>
								<strong>Phone (#2)</strong><br />
								<?php echo sprintf('%s%s', $customer->Contact->Person->Phone2, !empty($customer->Contact->Person->Phone2Ext) ? sprintf(' (ext; %s)', $customer->Contact->Person->Phone2Ext) : ''); ?>
							</td>
						</tr>
						<tr>
							<td>
								<strong>Mobile</strong><br />
								<?php echo $customer->Contact->Person->Mobile; ?>
							</td>
							<td></td>
						</tr>
						<tr>
							<td>
								<strong>Fax</strong><br />
								<?php echo $customer->Contact->Person->Fax; ?>
							</td>
							<td>
								<strong>Email</strong><br />
								<?php echo $customer->Contact->Person->Email; ?>
							</td>
						</tr>
						<tr>
							<td>
								<strong>Status</strong><br />
								<?php echo $customer->Contact->Status->Name; ?>
							</td>
							<td></td>
						</tr>
						<tr>
							<td>
								<strong>Position (Orders)</strong><br />
								<?php echo $customer->Contact->PositionOrders; ?>
							</td>
							<td>
								<strong>Position (Turnover)</strong><br />
								<?php echo $customer->Contact->PositionTurnover; ?>
							</td>
						</tr>
						<tr>
							<td>
								<strong>Catalogue Sent</strong><br />
								<?php
								if($customer->Contact->CatalogueSentOn != '0000-00-00 00:00:00'){
								    echo "Catalogue sent on " . cDatetime($customer->Contact->CatalogueSentOn, 'shortdate');
								    if($customer->Contact->IsCatalogueRequested == "Y"){
									echo " (Request Sent Again - <span> " . sprintf('<a href="?action=cancelrequestcatalogue&cid=%1$d">Cancel Request</a>', $customer->Contact->ID) . "</span>)";
								    }else{
									echo ' - <span>' . sprintf('<a href="?action=requestcatalogue&cid=%1$d">Request New Catalogue</a>', $customer->Contact->ID) . "</span>";
								    }
								}else if($customer->Contact->IsCatalogueRequested == "Y"){
								    echo "Never sent but requested &nbsp;<span> " . sprintf('<a href="?action=cancelrequestcatalogue&cid=%1$d">Cancel Request</a>', $customer->Contact->ID) . "</span>";
								}else{
								    
								    echo '<em>&lt;Never&gt;</em>&nbsp;<span>' . sprintf('<a href="?action=requestcatalogue&cid=%1$d">Request Catalogue</a>', $customer->Contact->ID) . "</span>";
								}
?>								
								
								
								
							</td>
							<td></td>
						</tr>
					</table>
			
					<br />
					<input name="edit" type="button" value="edit profile" class="btn" onclick="window.location.href = '?action=<?php echo ($customer->Contact->Parent->ID > 0) ? 'updatechild' : 'updateind'; ?>&cid=<?php echo $customer->Contact->ID; ?>'" />
					
				</td>
				<td valign="top" width="1%"></td>
				<td valign="top" width="49.5%">
				
					<?php
					if($customer->Contact->Parent->ID > 0) {
						?>
				
						<h3>Switch Contact</h3>
						<br />
						
						<?php
						$table = new DataTable('subcontacts');
						$table->SetExtractVarsLink(array('action,confirm,cid'));
						$table->SetSQL(sprintf("SELECT c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name, p.Department FROM contact AS c INNER JOIN person AS p ON c.Person_ID=p.Person_ID WHERE c.Parent_Contact_ID=%d", mysql_real_escape_string($customer->Contact->Parent->ID)));
						$table->AddField('ID#', 'Contact_ID', 'left');
						$table->AddField('Contact', 'Contact_Name', 'left');
						$table->AddField('Department', 'Department', 'left');
						$table->AddLink("?cid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
						$table->SetMaxRows(25);
						$table->SetOrderBy('Contact_ID');
						$table->Finalise();
						$table->DisplayTable();
						echo '<br />';
						$table->DisplayNavigation();
					} else {
						?>
						
						<h3>Organisation</h3>
						<br />
						
						<p>This contact does not currently belong to an oranisation. Click below to add to a new organisation.</p>

						<input name="add" type="button" value="add to organisation" class="btn" onclick="window.location.href = '?action=addtoneworg&cid=<?php echo $customer->Contact->ID; ?>';" />
						
						<?php
					}
					?>
					
				</td>
			</tr>
		</table>
		
		<?php
		exit;
	}
	
	if(isset($_SESSION['data']['account_schedules']['last_contact'])) {
		echo '<br /><br />';
		echo '<input class="btn" type="button" value="return to account schedules" onclick="window.self.location.href = \'account_schedules.php\'" />';	
	}
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}