<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Organisation.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$session->Secure(3);

$contact = new Contact($_REQUEST['cid']);

$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Edit Organisation Profile', $contact->ID, $contact->Organisation->Name), "You can edit the organisations profile here.");

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('cid','Contact ID','hidden','','numeric_unsigned',1,10);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('name','Name','text',$contact->Organisation->Name,'anything',1,100);
$form->AddField('integrationreference','Sage Account Reference','text',$contact->IntegrationReference,'paragraph',1,64, false);
$form->AddField('phone1','Phone 1','text',$contact->Organisation->Phone1,'anything',1,15, false);
$form->AddField('phone1ext','Phone 1 extension','text',$contact->Organisation->Phone1Ext,'anything',1,5,false);
$form->AddField('phone2','Phone 1','text',$contact->Organisation->Phone2,'anything',1,15,false);
$form->AddField('phone2ext','Phone 1 extension','text',$contact->Organisation->Phone2Ext,'anything',1,5,false);
$form->AddField('fax','Fax','text',$contact->Organisation->Fax,'anything',1,15,false);
$form->AddField('email','Email','text',$contact->Organisation->Email,'email');
$form->AddField('url','Website','text',$contact->Organisation->Url,'link',1,100,false);
$form->AddField('company','Company Number','text',$contact->Organisation->CompanyNo,'anything',1,100,false);
$form->AddField('tax','Tax Number','text',$contact->Organisation->TaxNo,'anything',1,100,false);
$form->AddField('ishighdiscount','Is High Discount','checkbox',$contact->IsHighDiscount,'boolean',1,1,false);
$form->AddField('orgType', 'Organisation Type', 'select', $contact->Organisation->Type->ID, 'numeric_unsigned', 1, 11);
$form->AddOption('orgType', '0', '');

$orgTypes = new DataQuery('select * from organisation_type order by Org_Type asc');
while($orgTypes->Row){
	$form->AddOption('orgType', $orgTypes->Row['Org_Type_ID'], $orgTypes->Row['Org_Type']);

	$orgTypes->Next();
}
$orgTypes->Disconnect();

$form->AddField('orgIndustry', 'Organisation Industry', 'select', $contact->Organisation->Industry->ID, 'numeric_unsigned', 1, 11);
$form->AddOption('orgIndustry', '0', '');

$orgInd = new DataQuery('select * from organisation_industry order by Industry_Name asc');
while($orgInd->Row){
	$form->AddOption('orgIndustry', $orgInd->Row['Industry_ID'], $orgInd->Row['Industry_Name']);

	$orgInd->Next();
}
$orgInd->Disconnect();

$form->AddField('account', 'Account Manager', 'select', $contact->AccountManager->ID, 'numeric_unsigned', 1, 11, true, (($contact->AccountManager->ID == 0) || ($GLOBALS['SESSION_USER_ID'] == 3) || ($contact->AccountManager->ID == $GLOBALS['SESSION_USER_ID'])) ? '' : 'disabled="disabled"');
$form->AddOption('account', '0', '');

$data = new DataQuery('SELECT u.User_ID, p.Name_First, p.Name_Last FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY p.Name_First, p.Name_Last ASC');
while($data->Row){
	$form->AddOption('account', $data->Row['User_ID'], trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])));

	$data->Next();
}
$data->Disconnect();

$form->AddField('generategroups', 'Generate Groups', 'checkbox', $contact->GenerateGroups, 'boolean', 1, 1, false);

$form->AddField('address1', 'Address Line 1', 'text', $contact->Organisation->Address->Line1, 'anything', 1, 150, false);
$form->AddField('address2', 'Address Line 2', 'text', $contact->Organisation->Address->Line2, 'anything', 1, 150, false);
$form->AddField('address3', 'Address Line 3', 'text', $contact->Organisation->Address->Line3, 'anything', 1, 150, false);
$form->AddField('city', 'City/Town', 'text', $contact->Organisation->Address->City, 'alpha_numeric', 1, 100, false);
$form->AddField('country', 'Country', 'select', $contact->Organisation->Address->Country->ID, 'numeric_unsigned', 1, 11, false, 'onChange="propogateRegions(\'region\', this);"');
$form->AddOption('country', '0', '');
$form->AddOption('country', '222', 'United Kingdom');

$country = new DataQuery("select * from countries order by Country asc");
while($country->Row) {
	$form->AddOption('country', $country->Row['Country_ID'], $country->Row['Country']);

	$country->Next();
}
$country->Disconnect();

$form->AddField('region', 'Region', 'select', $contact->Organisation->Address->Region->ID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('region', '0', '');

$regions = new DataQuery(sprintf("select * from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($contact->Organisation->Address->Country->ID)));
while($regions->Row){
	$form->AddOption('region', $regions->Row['Region_ID'], $regions->Row['Region_Name']);

	$regions->Next();
}
$regions->Disconnect();

$form->AddField('zip', 'Postal Code', 'text', $contact->Organisation->Address->Zip, 'alpha_numeric', 1, 10, false);

$form->AddField('useinvoiceaddress','Use Invoice Address','checkbox',$contact->Organisation->UseInvoiceAddress,'boolean',1,1,false);
$form->AddField('invoicename', 'Invoice Name', 'text', $contact->Organisation->InvoiceName, 'anything', 0, 240, false);
$form->AddField('invoiceaddress1', 'Address Line 1', 'text', $contact->Organisation->InvoiceAddress->Line1, 'anything', 1, 150, false);
$form->AddField('invoiceaddress2', 'Address Line 2', 'text', $contact->Organisation->InvoiceAddress->Line2, 'anything', 1, 150, false);
$form->AddField('invoiceaddress3', 'Address Line 3', 'text', $contact->Organisation->InvoiceAddress->Line3, 'anything', 1, 150, false);
$form->AddField('invoicecity', 'City/Town', 'text', $contact->Organisation->InvoiceAddress->City, 'alpha_numeric', 1, 100, false);
$form->AddField('invoicecountry', 'Country', 'select', $contact->Organisation->InvoiceAddress->Country->ID, 'numeric_unsigned', 1, 11, false, 'onChange="propogateRegions(\'invoiceregion\', this);"');
$form->AddOption('invoicecountry', '0', '');
$form->AddOption('invoicecountry', '222', 'United Kingdom');

$country = new DataQuery("select * from countries order by Country asc");
while($country->Row) {
	$form->AddOption('invoicecountry', $country->Row['Country_ID'], $country->Row['Country']);

	$country->Next();
}
$country->Disconnect();

$form->AddField('invoiceregion', 'Region', 'select', $contact->Organisation->InvoiceAddress->Region->ID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('invoiceregion', '0', '');

$regions = new DataQuery(sprintf("select * from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($contact->Organisation->InvoiceAddress->Country->ID)));
while($regions->Row){
	$form->AddOption('invoiceregion', $regions->Row['Region_ID'], $regions->Row['Region_Name']);

	$regions->Next();
}
$regions->Disconnect();

$form->AddField('invoicezip', 'Postal Code', 'text', $contact->Organisation->InvoiceAddress->Zip, 'alpha_numeric', 1, 10, false);
$form->AddField('invoicephone', 'Invoice Phone', 'text', $contact->Organisation->InvoicePhone, 'anything', 0, 240, false);
$form->AddField('invoiceemail', 'Invoice Email', 'text', $contact->Organisation->InvoiceEmail, 'anything', 0, 100, false);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$contact->Organisation->Name = $form->GetValue('name');
		$contact->Organisation->Type->ID = $form->GetValue('orgType');
		$contact->Organisation->Industry->ID = $form->GetValue('orgIndustry');
		$contact->Organisation->Phone1 = $form->GetValue('phone1');
		$contact->Organisation->Fax = $form->GetValue('fax');
		$contact->Organisation->Phone1Ext = $form->GetValue('phone1ext');
		$contact->Organisation->Phone2 = $form->GetValue('phone2');
		$contact->Organisation->Phone2Ext = $form->GetValue('phone2ext');
		$contact->Organisation->Email = $form->GetValue('email');
		$contact->Organisation->Url = $form->GetValue('url');
		$contact->Organisation->Address->Line1 = $form->GetValue('address1');
		$contact->Organisation->Address->Line2 = $form->GetValue('address2');
		$contact->Organisation->Address->Line3 = $form->GetValue('address3');
		$contact->Organisation->Address->City = $form->GetValue('city');
		$contact->Organisation->Address->Country->ID = $form->GetValue('country');
		$contact->Organisation->Address->Region->ID = $form->GetValue('region');
		$contact->Organisation->Address->Zip = $form->GetValue('zip');
		$contact->Organisation->UseInvoiceAddress = $form->GetValue('useinvoiceaddress');
		$contact->Organisation->InvoiceName = $form->GetValue('invoicename');
        $contact->Organisation->InvoiceAddress->Line1 = $form->GetValue('invoiceaddress1');
		$contact->Organisation->InvoiceAddress->Line2 = $form->GetValue('invoiceaddress2');
		$contact->Organisation->InvoiceAddress->Line3 = $form->GetValue('invoiceaddress3');
		$contact->Organisation->InvoiceAddress->City = $form->GetValue('invoicecity');
		$contact->Organisation->InvoiceAddress->Country->ID = $form->GetValue('invoicecountry');
		$contact->Organisation->InvoiceAddress->Region->ID = $form->GetValue('invoiceregion');
		$contact->Organisation->InvoiceAddress->Zip = $form->GetValue('invoicezip');
		$contact->Organisation->InvoicePhone = $form->GetValue('invoicephone');
		$contact->Organisation->InvoiceEmail = $form->GetValue('invoiceemail');
		$contact->Organisation->TaxNo = $form->GetValue('tax');
		$contact->Organisation->CompanyNo = $form->GetValue('company');
		$contact->IntegrationReference = $form->GetValue('integrationreference');

		if(($contact->AccountManager->ID == 0) || ($GLOBALS['SESSION_USER_ID'] == 3) || ($contact->AccountManager->ID == $GLOBALS['SESSION_USER_ID'])) {
			$contact->AccountManager->ID = $form->GetValue('account');
		}
		
		$contact->GenerateGroups = $form->GetValue('generategroups');
		$contact->IsHighDiscount = $form->GetValue('ishighdiscount');
		$contact->Update();
		$contact->UpdateAccountManager();

		redirect(sprintf("Location: contact_profile.php?cid=%d", $form->GetValue('cid')));
	}
}

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}
$page->LinkScript('js/pcAnywhere.js');
$page->LinkScript('js/regions.php');
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
$window = new StandardWindow('Please change your details');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('cid');
echo $window->Open();
echo $window->AddHeader('Company Details');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('name'),$form->GetHTML('name').$form->GetIcon('name'));
echo $webForm->AddRow($form->GetLabel('integrationreference'),$form->GetHTML('integrationreference').$form->GetIcon('integrationreference'));
echo $webForm->AddRow($form->GetLabel('orgType'),$form->GetHTML('orgType').$form->GetIcon('orgType'));
echo $webForm->AddRow($form->GetLabel('orgIndustry'),$form->GetHTML('orgIndustry').$form->GetIcon('orgIndustry'));
echo $webForm->AddRow($form->GetLabel('company'),$form->GetHTML('company').$form->GetIcon('company'));
echo $webForm->AddRow($form->GetLabel('tax'),$form->GetHTML('tax').$form->GetIcon('tax'));
echo $webForm->AddRow($form->GetLabel('ishighdiscount'),$form->GetHTML('ishighdiscount').$form->GetIcon('ishighdiscount'));
echo $webForm->AddRow($form->GetLabel('account'), $form->GetHTML('account') . $form->GetIcon('account'));
echo $webForm->AddRow($form->GetLabel('generategroups'), $form->GetHTML('generategroups') . $form->GetIcon('generategroups'));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->AddHeader('Contact Details');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('fax'),$form->GetHTML('fax').$form->GetIcon('fax'));
echo $webForm->AddRow($form->GetLabel('phone1'),$form->GetHTML('phone1').$form->GetIcon('phone1'));
echo $webForm->AddRow($form->GetLabel('phone1ext'),$form->GetHTML('phone1ext').$form->GetIcon('phone1ext'));
echo $webForm->AddRow($form->GetLabel('phone2'),$form->GetHTML('phone2').$form->GetIcon('phone2'));
echo $webForm->AddRow($form->GetLabel('phone2ext'),$form->GetHTML('phone2ext').$form->GetIcon('phone2ext'));
echo $webForm->AddRow($form->GetLabel('email'),$form->GetHTML('email').$form->GetIcon('email'));
echo $webForm->AddRow($form->GetLabel('url'),$form->GetHTML('url').$form->GetIcon('url'));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->AddHeader('Address Details');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('zip'), $form->GetHTML('zip') . $form->GetIcon('zip') . ' <a href="javascript:Address.find(document.getElementById(\'zip\'));"><img src="../images/searchIcon.gif" border="0" align="absmiddle" /> Auto-complete address (UK residents)</a>');
echo $webForm->AddRow($form->GetLabel('address1'),$form->GetHTML('address1').$form->GetIcon('address1'));
echo $webForm->AddRow($form->GetLabel('address2'),$form->GetHTML('address2').$form->GetIcon('address2'));
echo $webForm->AddRow($form->GetLabel('address3'),$form->GetHTML('address3').$form->GetIcon('address3'));
echo $webForm->AddRow($form->GetLabel('city'),$form->GetHTML('city').$form->GetIcon('city'));
echo $webForm->AddRow($form->GetLabel('country'),$form->GetHTML('country').$form->GetIcon('country'));
echo $webForm->AddRow($form->GetLabel('region'),$form->GetHTML('region').$form->GetIcon('region'));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->AddHeader('Invoice Address.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('useinvoiceaddress'), $form->GetHTML('useinvoiceaddress') . $form->GetIcon('useinvoiceaddress'));
echo $webForm->AddRow($form->GetLabel('invoicename'), $form->GetHTML('invoicename') . $form->GetIcon('invoicename'));
echo $webForm->AddRow($form->GetLabel('invoiceaddress1'), $form->GetHTML('invoiceaddress1') . $form->GetIcon('invoiceaddress1'));
echo $webForm->AddRow($form->GetLabel('invoiceaddress2'), $form->GetHTML('invoiceaddress2') . $form->GetIcon('invoiceaddress2'));
echo $webForm->AddRow($form->GetLabel('invoiceaddress3'), $form->GetHTML('invoiceaddress3') . $form->GetIcon('invoiceaddress3'));
echo $webForm->AddRow($form->GetLabel('invoicecity'), $form->GetHTML('invoicecity') . $form->GetIcon('invoicecity'));
echo $webForm->AddRow($form->GetLabel('invoicecountry'), $form->GetHTML('invoicecountry') . $form->GetIcon('invoicecountry'));
echo $webForm->AddRow($form->GetLabel('invoiceregion'), $form->GetHTML('invoiceregion') . $form->GetIcon('invoiceregion'));
echo $webForm->AddRow($form->GetLabel('invoicezip'), $form->GetHTML('invoicezip') . $form->GetIcon('invoicezip'));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->AddHeader('Invoice Contact Details');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('invoicephone'),$form->GetHTML('invoicephone').$form->GetIcon('invoicephone'));
echo $webForm->AddRow($form->GetLabel('invoiceemail'),$form->GetHTML('invoiceemail').$form->GetIcon('invoiceemail'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'contact_profile.php?cid=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetValue('cid'), $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $window->Close();
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');