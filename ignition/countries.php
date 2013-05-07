<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	addCountries();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	updateCountries();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	removeCountries();
	exit;
}else {
	$session->Secure(2);
	viewCountries();
	exit;
}

function removeCountries(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$country = new Country;
		$country->ID = $_REQUEST['id'];
		$country->Remove();
		redirect("Location: countries.php");
	} else {
		viewCountries();
	}
}

function addCountries(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form("countries.php");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('country', 'Country Name', 'text', '', 'alpha_numeric', 3, 64);
	$form->AddField('iso2', 'ISO Code 2', 'text', '', 'alpha', 2, 2);
	$form->AddField('language', 'Language', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('language', '0', 'No Language Set');
	$language = new DataQuery("select * from languages");
	do{
		$form->AddOption('language', $language->Row['Language_ID'], $language->Row['Language']);
		$language->Next();
	} while($language->Row);
	$language->Disconnect();

	$form->AddField('currency', 'Currency', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('currency', '0', 'No Currency Set');
	$currency = new DataQuery("select * from currencies");
	do{
		$form->AddOption('currency', $currency->Row['Currency_ID'], $currency->Row['Currency']);
		$currency->Next();
	} while($currency->Row);
	$currency->Disconnect();

	$form->AddField('sales', 'Allow Sales', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('regions', 'Allow Custom Regions', 'checkbox', 'N', 'boolean', 1, 1, false);

	$form->AddField('address', 'Address Format', 'select', '1', 'numeric_unsigned', 1, 11);
	$form->AddOption('address', '0', 'No Format Set');
	$address = new DataQuery("select * from address_format");
	do{
		$form->AddOption('address', $address->Row['Address_Format_ID'], $address->Row['Address_Format']);
		$address->Next();
	} while($address->Row);
	$address->Disconnect();
	
	$nominalCodes = array();
	$nominalCodes['4100'] = '4100: Sales';
	$nominalCodes['4103'] = '4103: Sales - EC VAT Free';
	$nominalCodes['4104'] = '4104: Sales - WW VAT Free';
	$nominalCodes['4105'] = '4105: Sales - UK VAT Free';
	$nominalCodes['4106'] = '4106: Sales - UK Credit Account';
	$nominalCodes['4109'] = '4109: Sales - EC Credit Account';
	$nominalCodes['4110'] = '4110: Sales - WW Credit Account';
	$nominalCodes['4111'] = '4111: Sales - UK Credit Account VAT Free';
	$nominalCodes['4112'] = '4112: Sales - EC Credit Account VAT Free';

	$form->AddField('nominal', 'Nominal Code', 'select', '', 'paragraph', 1, 16, true);
	$form->AddOption('nominal', '', '');
	$form->AddField('nominaltaxfree', 'Nominal Code (Tax Free)', 'select', '', 'paragraph', 1, 16, true);
	$form->AddOption('nominaltaxfree', '', '');
	$form->AddField('nominalaccount', 'Nominal Code - Accounts', 'select', '', 'paragraph', 1, 16, true);
	$form->AddOption('nominalaccount', '', '');
	$form->AddField('nominalaccounttaxfree', 'Nominal Code - Accounts (Tax Free)', 'select', '', 'paragraph', 1, 16, true);
	$form->AddOption('nominalaccounttaxfree', '', '');
	
	foreach($nominalCodes as $code=>$description) {
		$form->AddOption('nominal', $code, $description);
		$form->AddOption('nominaltaxfree', $code, $description);
		$form->AddOption('nominalaccount', $code, $description);
		$form->AddOption('nominalaccounttaxfree', $code, $description);
	}
	
    $form->AddField('tax', 'Exempt Tax Code', 'select', '0', 'numeric_unsigned', 1, 11, true, 'onchange="toggleTaxRate(this);"');

	$selectedValue = 0;

	$data = new DataQuery("SELECT Tax_Code_ID, Integration_Reference, Description, Rate FROM tax_code ORDER BY Integration_Reference ASC");
	while($data->Row){
		$form->AddOption('tax', $data->Row['Tax_Code_ID'], sprintf('T%s: %s (%s%%)', $data->Row['Integration_Reference'], $data->Row['Description'], $data->Row['Rate']));

		if($GLOBALS['SAGE_TAX_EXEMPT_CODE'] == $data->Row['Integration_Reference']) {
			$selectedValue = $data->Row['Tax_Code_ID'];
		}

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$country = new Country();
			$country->Name = $form->GetValue('country');
			$country->ISOCode2 = $form->GetValue('iso2');
			$country->Language->ID = $form->GetValue('language');
			$country->Currency->ID = $form->GetValue('currency');
			$country->AddressFormat = $form->GetValue('address');
			$country->AllowSales = $form->GetValue('sales');
			$country->AllowCustomRegions = $form->GetValue('regions');
            $country->NominalCode = $form->GetValue('nominal');
            $country->NominalCodeTaxFree = $form->GetValue('nominaltaxfree');
            $country->NominalCodeAccount = $form->GetValue('nominalaccount');
            $country->NominalCodeAccountTaxFree = $form->GetValue('nominalaccounttaxfree');
			$country->ExemptTaxCode->ID = $form->GetValue('tax');
			$country->Add();

			redirect("Location: countries.php");
		}
	}

	$form->SetValue('tax', $selectedValue);

	$page = new Page('Adding a Country','If a country has changed name please use the update facility rather than adding a new country.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Adding a Country');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('country'), $form->GetHTML('country') . $form->GetIcon('country'));
	echo $webForm->AddRow($form->GetLabel('iso2'), $form->GetHTML('iso2') . $form->GetIcon('iso2'));
	echo $webForm->AddRow($form->GetLabel('language'), $form->GetHTML('language') . $form->GetIcon('language'));
	echo $webForm->AddRow($form->GetLabel('currency'), $form->GetHTML('currency') . $form->GetIcon('currency'));
	echo $webForm->AddRow($form->GetLabel('sales'), $form->GetHTML('sales') . $form->GetIcon('sales'));
	echo $webForm->AddRow($form->GetLabel('regions'), $form->GetHTML('regions') . $form->GetIcon('regions'));
	echo $webForm->AddRow($form->GetLabel('tax'), $form->GetHTML('tax') . $form->GetIcon('tax'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'countries.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Nominal codes.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('nominal'), $form->GetHTML('nominal') . $form->GetIcon('nominal'));
	echo $webForm->AddRow($form->GetLabel('nominaltaxfree'), $form->GetHTML('nominaltaxfree') . $form->GetIcon('nominaltaxfree'));
	echo $webForm->AddRow($form->GetLabel('nominalaccount'), $form->GetHTML('nominalaccount') . $form->GetIcon('nominalaccount'));
	echo $webForm->AddRow($form->GetLabel('nominalaccounttaxfree'), $form->GetHTML('nominalaccounttaxfree') . $form->GetIcon('nominalaccounttaxfree'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'countries.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Contact your administrator if you are confused about Address Formats.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('address'), $form->GetHTML('address') . $form->GetIcon('address'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'countries.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function updateCountries(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$country = new Country($_REQUEST['id']);
	$form = new Form("countries.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', $country->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('country', 'Country Name', 'text', $country->Name, 'alpha_numeric', 3, 64);
	$form->AddField('iso2', 'ISO Code 2', 'text', $country->ISOCode2, 'alpha', 2, 2);

	$form->AddField('language', 'Language', 'select', $country->Language->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('language', '0', 'No Language Set');
	$language = new DataQuery("select * from languages");
	do{
		$form->AddOption('language', $language->Row['Language_ID'], $language->Row['Language']);
		$language->Next();
	} while($language->Row);
	$language->Disconnect();

	$form->AddField('currency', 'Currency', 'select', $country->Currency->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('currency', '0', 'No Currency Set');
	$currency = new DataQuery("select * from currencies");
	do{
		$form->AddOption('currency', $currency->Row['Currency_ID'], $currency->Row['Currency']);
		$currency->Next();
	} while($currency->Row);
	$currency->Disconnect();

	$form->AddField('sales', 'Allow Sales', 'checkbox', $country->AllowSales, 'boolean', 1, 1, false);
	$form->AddField('regions', 'Allow Custom Regions', 'checkbox', $country->AllowCustomRegions, 'boolean', 1, 1, false);

	$form->AddField('address', 'Address Format', 'select', $country->AddressFormat->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('address', '0', 'No Format Set');
	$address = new DataQuery("select * from address_format");
	do{
		$form->AddOption('address', $address->Row['Address_Format_ID'], $address->Row['Address_Format']);
		$address->Next();
	} while($address->Row);
	$address->Disconnect();

	$nominalCodes = array();
	$nominalCodes['4100'] = '4100: Sales';
	$nominalCodes['4103'] = '4103: Sales - EC VAT Free';
	$nominalCodes['4104'] = '4104: Sales - WW VAT Free';
	$nominalCodes['4105'] = '4105: Sales - UK VAT Free';
	$nominalCodes['4106'] = '4106: Sales - UK Credit Account';
	$nominalCodes['4109'] = '4109: Sales - EC Credit Account';
	$nominalCodes['4110'] = '4110: Sales - WW Credit Account';
	$nominalCodes['4111'] = '4111: Sales - UK Credit Account VAT Free';
	$nominalCodes['4112'] = '4112: Sales - EC Credit Account VAT Free';

	$form->AddField('nominal', 'Nominal Code', 'select', $country->NominalCode, 'paragraph', 1, 16, true);
	$form->AddOption('nominal', '', '');
	$form->AddField('nominaltaxfree', 'Nominal Code (Tax Free)', 'select', $country->NominalCodeTaxFree, 'paragraph', 1, 16, true);
	$form->AddOption('nominaltaxfree', '', '');
	$form->AddField('nominalaccount', 'Nominal Code - Accounts', 'select', $country->NominalCodeAccount, 'paragraph', 1, 16, true);
	$form->AddOption('nominalaccount', '', '');
	$form->AddField('nominalaccounttaxfree', 'Nominal Code - Accounts (Tax Free)', 'select', $country->NominalCodeAccountTaxFree, 'paragraph', 1, 16, true);
	$form->AddOption('nominalaccounttaxfree', '', '');
	
	foreach($nominalCodes as $code=>$description) {
		$form->AddOption('nominal', $code, $description);
		$form->AddOption('nominaltaxfree', $code, $description);
		$form->AddOption('nominalaccount', $code, $description);
		$form->AddOption('nominalaccounttaxfree', $code, $description);
	}
    $form->AddField('tax', 'Exempt Tax Code', 'select', $country->ExemptTaxCode->ID, 'numeric_unsigned', 1, 11, true, 'onchange="toggleTaxRate(this);"');

	$data = new DataQuery("SELECT Tax_Code_ID, Integration_Reference, Description, Rate FROM tax_code ORDER BY Integration_Reference ASC");
	while($data->Row){
		$form->AddOption('tax', $data->Row['Tax_Code_ID'], sprintf('T%s: %s (%s%%)', $data->Row['Integration_Reference'], $data->Row['Description'], $data->Row['Rate']));

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$country->Name = $form->GetValue('country');
			$country->ISOCode2 = $form->GetValue('iso2');
			$country->Language->ID = $form->GetValue('language');
			$country->Currency->ID = $form->GetValue('currency');
			$country->AddressFormat = $form->GetValue('address');
			$country->AllowSales = $form->GetValue('sales');
			$country->AllowCustomRegions = $form->GetValue('regions');
            $country->NominalCode = $form->GetValue('nominal');
            $country->NominalCodeTaxFree = $form->GetValue('nominaltaxfree');
            $country->NominalCodeAccount = $form->GetValue('nominalaccount');
            $country->NominalCodeAccountTaxFree = $form->GetValue('nominalaccounttaxfree');
			$country->ExemptTaxCode->ID = $form->GetValue('tax');
			$country->Update();

			redirect("Location: countries.php");
		}
	}

	$page = new Page('Updating Country Settings','Be aware that changes to regional and country settings may affect other areas of your system. If you are unsure please always contact your administrator before making changes.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Update Country Settings');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('country'), $form->GetHTML('country') . $form->GetIcon('country'));
	echo $webForm->AddRow($form->GetLabel('iso2'), $form->GetHTML('iso2') . $form->GetIcon('iso2'));
	echo $webForm->AddRow($form->GetLabel('language'), $form->GetHTML('language') . $form->GetIcon('language'));
	echo $webForm->AddRow($form->GetLabel('currency'), $form->GetHTML('currency') . $form->GetIcon('currency'));
	echo $webForm->AddRow($form->GetLabel('sales'), $form->GetHTML('sales') . $form->GetIcon('sales'));
	echo $webForm->AddRow($form->GetLabel('regions'), $form->GetHTML('regions') . $form->GetIcon('regions'));
	echo $webForm->AddRow($form->GetLabel('tax'), $form->GetHTML('tax') . $form->GetIcon('tax'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'countries.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Nominal codes.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('nominal'), $form->GetHTML('nominal') . $form->GetIcon('nominal'));
	echo $webForm->AddRow($form->GetLabel('nominaltaxfree'), $form->GetHTML('nominaltaxfree') . $form->GetIcon('nominaltaxfree'));
	echo $webForm->AddRow($form->GetLabel('nominalaccount'), $form->GetHTML('nominalaccount') . $form->GetIcon('nominalaccount'));
	echo $webForm->AddRow($form->GetLabel('nominalaccounttaxfree'), $form->GetHTML('nominalaccounttaxfree') . $form->GetIcon('nominalaccounttaxfree'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'countries.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	
	echo $window->AddHeader('Contact your administrator if you are confused about Address Formats.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('address'), $form->GetHTML('address') . $form->GetIcon('address'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'countries.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function viewCountries(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Country Settings', 'Make all your country settings here. By default only the UK is allowed sales.');
	$page->Display('header');

	$table = new DataTable('countries');
	$table->SetSQL("SELECT c.*, CONCAT('T', tc.Integration_Reference) AS Exempt_Tax_Code FROM countries AS c LEFT JOIN tax_code AS tc ON tc.Tax_Code_ID=c.Exempt_Tax_Code_ID");
	$table->AddField('ID#', 'Country_ID', 'right');
	$table->AddField('Name', 'Country', 'left');
	$table->AddField('ISO Code', 'ISO_Code_2', 'left');
	$table->AddField('Allow Sales', 'Allow_Sales', 'center');
	$table->AddField('Allow Custom Regions', 'Allow_Custom_Regions', 'center');
	$table->AddField('Nominal Code', 'Nominal_Code', 'left');
	$table->AddField('Nominal Code (Tax Free)', 'Nominal_Code_Tax_Free', 'left');
	$table->AddField('Nominal Code - Accounts', 'Nominal_Code_Account', 'left');
	$table->AddField('Nominal Code - Accounts (Tax Free)', 'Nominal_Code_Account_Tax_Free', 'left');
	$table->AddField('Exempt Tax Code', 'Exempt_Tax_Code', 'left');
	$table->AddLink("regions.php?ctry=%s",
					"<img src=\"./images/icon_regions_1.gif\" alt=\"View Regions for this Country\" border=\"0\">",
					"Country_ID");
	$table->AddLink("countries.php?action=update&id=%s",
					"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",
					"Country_ID");
	$table->AddLink("javascript:confirmRequest('countries.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this country? IMPORTANT: removing a country could affect other site settings and may cause errors elsewhere on your site. If you are unsure please contact your administrator.');",
					"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
					"Country_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Country");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add a new country" class="btn" onclick="window.location.href=\'countries.php?action=add\'">';
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}