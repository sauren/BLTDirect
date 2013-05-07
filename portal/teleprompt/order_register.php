<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PostcodeAnywhere.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TelePrompt.php');

$session->Secure(2);

$address = new Address();
if(isset($_REQUEST['find']) && isset($_REQUEST['postcode']) && $GLOBALS['POSTCODEANYWHERE_ACTIVE']){
	$postcode = $_REQUEST['postcode'];
	$building = '';
	$check = new PostcodeAnywhere;
	$check->GetAddress($building, $postcode);
	$address = $check->Address;
}

global $cart;
$cart = new Cart($session, true);
$cart->Calculate();

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('account', 'User Type', 'select', 'I', 'alpha', 1, 1, true, 'onchange="swapAccount(this)"');
$form->AddOption('account', 'O', 'Business');
$form->AddOption('account', 'I', 'Home');

$isBusiness = ($form->GetValue('account') == 'O') ? true : false;

$form->AddField('title', 'Title', 'select', '', 'anything', 0, 20, false);
$form->AddOption('title', '', '');

$title = new DataQuery("select * from person_title order by Person_Title");
while($title->Row){
	$form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
	$title->Next();
}
$title->Disconnect();

$form->AddField('fname', 'First Name', 'text', '', 'name', 1, 60, true);
$form->AddField('lname', 'Last Name', 'text', '', 'name', 1, 60, true);
$form->AddField('position', 'Position', 'text', '', 'anaything', 1, 100, false);
$form->AddField('email', 'Email Address', 'text', '', 'email', NULL, NULL);
$form->AddField('noemail', 'No Email Address', 'checkbox', 'N', 'boolean', 1, 1, false);
$form->AddField('phone', 'Daytime Phone', 'text', '', 'telephone', NULL, NULL, false);
$form->AddField('fax', 'Fax', 'text', '', 'telephone', NULL, NULL, false);

$password = new Password(PASSWORD_LENGTH_CUSTOMER);

$form->AddField('password', 'Password', 'hidden', $password->Value, 'password', PASSWORD_LENGTH_CUSTOMER, 100);

$form->AddField('name', 'Business Name', 'text', '', 'anything', 1, 100, ($isBusiness)?true:false);
$form->AddField('businesspostcode', 'Postcode', 'text', $address->Zip, 'postcode', 1, 10, false);
$form->AddField('businessaddress1', 'Property Name/Number', 'text', $address->Line1, 'address', 1, 150, ($isBusiness)?true:false);
$form->AddField('businessaddress2', 'Street', 'text', $address->Line2, 'address', 1, 150, ($isBusiness)?true:false);
$form->AddField('businessaddress3', 'Area', 'text', $address->Line3, 'address', 1, 150, false);
$form->AddField('businesscity', 'City', 'text', $address->City, 'address', 1, 150, ($isBusiness)?true:false);

$form->AddField('businesscountry', 'Country', 'select', $address->Country->ID, 'numeric_unsigned', 1, 11, false, 'onChange="propogateRegions(\'businessregion\', this);"');
$form->AddOption('businesscountry', '0', '');
$form->AddOption('businesscountry', '222', 'United Kingdom');

$data = new DataQuery("select * from countries order by Country asc");
while($data->Row){
	$form->AddOption('businesscountry', $data->Row['Country_ID'], $data->Row['Country']);
	$data->Next();
}
$data->Disconnect();

$regionCount = 0;

$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($form->GetValue('businesscountry'))));
$regionCount = $region->TotalRows;
if($regionCount > 0){
	$form->AddField('businessregion', 'Region', 'select', $address->Region->ID, 'numeric_unsigned', 1, 11, false);
	$form->AddOption('businessregion', '0', '');
	while($region->Row){
		$form->AddOption('businessregion', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
} else {
	$form->AddField('businessregion', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
	$form->AddOption('businessregion', '0', '');
}
$region->Disconnect();

$form->AddField('accountstitle', 'Title', 'select', '', 'anything', 0, 20, false, 'disabled="disabled"');
$form->AddOption('accountstitle', '', '');

$title = new DataQuery("select * from person_title order by Person_Title");
while($title->Row){
	$form->AddOption('accountstitle', $title->Row['Person_Title'], $title->Row['Person_Title']);
	$title->Next();
}
$title->Disconnect();

$form->AddField('accountsinvoice', 'Separate Invoice Address', 'checkbox', 'N', 'boolean', 1, 1, false, 'onclick="toggleAccounts(this);"');
$asAccountFieldValue = $form->GetValue('accountsinvoice');
$isAsAccount = ($asAccountFieldValue == 'Y')?true:false;

$form->AddField('accountsname', 'Name', 'text', '', 'name', 1, 60, ($isAsAccount)?true:false, ($form->GetValue('accountsinvoice') == 'N') ? 'disabled="disabled"' : '');
$form->AddField('accountspostcode', 'Postcode', 'text', '', 'postcode', 1, 10, false, ($form->GetValue('accountsinvoice') == 'N') ? 'disabled="disabled"' : '');
$form->AddField('accountsaddress1', 'Property Name/Number', 'text', '', 'address', 1, 150, ($isAsAccount)?true:false, ($form->GetValue('accountsinvoice') == 'N') ? 'disabled="disabled"' : '');
$form->AddField('accountsaddress2', 'Street', 'text', '', 'address', 1, 150, ($isAsAccount)?true:false, ($form->GetValue('accountsinvoice') == 'N') ? 'disabled="disabled"' : '');
$form->AddField('accountsaddress3', 'Area', 'text', '', 'address', 1, 150, false, ($form->GetValue('accountsinvoice') == 'N') ? 'disabled="disabled"' : '');
$form->AddField('accountscity', 'City', 'text', '', 'address', 1, 150, ($isAsAccount)?true:false, ($form->GetValue('accountsinvoice') == 'N') ? 'disabled="disabled"' : '');
$form->AddField('accountscountry', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, ($isAsAccount)?true:false, 'onchange="propogateRegions(\'accountsregion\', this);"' . (($form->GetValue('accountsinvoice') == 'N') ? ' disabled="disabled"' : ''));
$form->AddOption('accountscountry', '0', '');
$form->AddOption('accountscountry', '222', 'United Kingdom');

$data = new DataQuery("select * from countries order by Country asc");
while($data->Row){
	$form->AddOption('accountscountry', $data->Row['Country_ID'], $data->Row['Country']);
	$data->Next();
}
$data->Disconnect();

$regionCount = 0;

$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($form->GetValue('accountscountry'))));
$regionCount = $region->TotalRows;
if($regionCount > 0){
	$form->AddField('accountsregion', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false, ($form->GetValue('accountsinvoice') == 'N') ? 'disabled="disabled"' : '');
	$form->AddOption('accountsregion', '0', '');
	while($region->Row){
		$form->AddOption('accountsregion', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
} else {
	$form->AddField('accountsregion', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false, ($form->GetValue('accountsinvoice') == 'N') ? 'disabled="disabled"' : '');
	$form->AddOption('accountsregion', '0', '');
}
$region->Disconnect();

$form->AddField('type', 'Business Type', 'select', '', 'numeric_unsigned', 1, 11, false);
$form->AddOption('type', '0', '');
$type = new DataQuery("select * from organisation_type order by Org_Type asc");
while($type->Row){
	$form->AddOption('type', $type->Row['Org_Type_ID'], $type->Row['Org_Type']);
	$type->Next();
}
$type->Disconnect();

$form->AddField('industry', 'Industry', 'select', '', 'numeric_unsigned', 1, 11, false);
$form->AddOption('industry', '0', '');
$industry = new DataQuery("select * from organisation_industry order by Industry_Name asc");
while($industry->Row){
	$form->AddOption('industry', $industry->Row['Industry_ID'], $industry->Row['Industry_Name']);
	$industry->Next();
}
$industry->Disconnect();

$form->AddField('reg', 'Company Registration', 'text', '', 'anything', 1, 50, (!$isBusiness)?true:false);
$form->AddField('postcode', 'Postcode', 'text', $address->Zip, 'postcode', 1, 10, false);
$form->AddField('address1', 'Property Name/Number', 'text', $address->Line1, 'address', 1, 150, (!$isBusiness)?true:false);
$form->AddField('address2', 'Street', 'text', $address->Line2, 'address', 1, 150, (!$isBusiness)?true:false);
$form->AddField('address3', 'Area', 'text', $address->Line3, 'address', 1, 150, false);
$form->AddField('city', 'City', 'text', $address->City, 'address', 1, 150, (!$isBusiness)?true:false);

$str = 'onChange="propogateRegions(\'region\', this);" ' ;
$form->AddField('country', 'Country', 'select', $address->Country->ID, 'numeric_unsigned', 1, 11, false, $str);
$form->AddOption('country', '0', '');
$form->AddOption('country', '222', 'United Kingdom');

$data = new DataQuery("select * from countries order by Country asc");
while($data->Row){
	$form->AddOption('country', $data->Row['Country_ID'], $data->Row['Country']);
	$data->Next();
}
$data->Disconnect();

$regionCount = 0;

$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($form->GetValue('country'))));
$regionCount = $region->TotalRows;
if($regionCount > 0){
	$form->AddField('region', 'Region', 'select', $address->Region->ID, 'numeric_unsigned', 1, 11, false);
	$form->AddOption('region', '0', '');
	while($region->Row){
		$form->AddOption('region', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
} else {
	$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
	$form->AddOption('region', '0', '');
}
$region->Disconnect();

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if(isset($_REQUEST['noemail']) && ($_REQUEST['noemail'] == 'Y')) {
		$form->InputFields['email']->Required = false;
	}

	if(isset($_REQUEST['account']) && strtolower($_REQUEST['account']) == 'i'){
		$form->InputFields['reg']->Required = false;
	}

	$form->Validate();

	$isOrg = (strtolower($form->GetValue('account')) == 'o')? true : false;

	$isOrg = (strtolower($form->GetValue('account')) == 'o')? true : false;

	if($isOrg){
	  	if($form->GetValue('businesscountry') == 0){
	  		$form->AddError('You have yet to select a business country.', 'accountscountry');
	  	}
	  	/*if($form->GetValue('businessregion') == 0){
	  		$form->AddError('You have yet to select a business region.', 'businessregion');
	  	}*/
	  	
	  	if($form->GetValue('accountsinvoice') == 'Y') {
		  	if($form->GetValue('accountscountry') == 0){
		  		$form->AddError('You have yet to select an account country.', 'accountscountry');
		  	}
		  	/*if($form->GetValue('accountsregion') == 0){
		  		$form->AddError('You have yet to select an account region.', 'accountsregion');
		  	}*/
	  	}

	  	if($form->GetValue('type') == 0){
	  		$form->AddError('You have yet to select your business type.', 'type');
	  	}

	  	if($form->GetValue('industry') == 0){
	  		$form->AddError('You have yet to select your industry.', 'industry');
	  	}

  	} else{
		if($form->GetValue('country') == 0){
    		$form->AddError('You have yet to select a country.', 'country');
  		}
  		/*if($form->GetValue('region') == 0){
    		$form->AddError('You have yet to select a region.', 'region');
  		}*/
  	}

	$email = (isset($_REQUEST['noemail']) && ($_REQUEST['noemail'] == 'Y')) ? '0@no-email.co.uk' : $form->GetValue('email');

	$customer = new Customer();
	$customer->Username = $email;
	$customer->SetPassword($form->GetValue('password'));
	$customer->Contact->Type = 'I';
	$customer->Contact->IsCustomer = 'Y';
	$customer->Contact->OnMailingList = 'H';
	$customer->Contact->Person->Title = $form->GetValue('title');
	$customer->Contact->Person->Name = $form->GetValue('fname');
	$customer->Contact->Person->LastName = $form->GetValue('lname');
	$customer->Contact->Person->Phone1 = $form->GetValue('phone');
	$customer->Contact->Person->Fax = $form->GetValue('fax');
	$customer->Contact->Person->Email = $customer->Username;
	$customer->Contact->Person->Address->Line1 = ($isOrg)?$form->GetValue('businessaddress1'):$form->GetValue('address1');
	$customer->Contact->Person->Address->Line2 = ($isOrg)?$form->GetValue('businessaddress2'):$form->GetValue('address2');
	$customer->Contact->Person->Address->Line3 = ($isOrg)?$form->GetValue('businessaddress3'):$form->GetValue('address3');
	$customer->Contact->Person->Address->City = ($isOrg)?$form->GetValue('businesscity'):$form->GetValue('city');
	$customer->Contact->Person->Address->Country->ID = ($isOrg)?$form->GetValue('businesscountry'):$form->GetValue('country');
	$customer->Contact->Person->Address->Region->ID = ($isOrg)?$form->GetValue('businessregion'):$form->GetValue('region');
	$customer->Contact->Person->Address->Zip = ($isOrg)?$form->GetValue('businesspostcode'):$form->GetValue('postcode');

	if($form->Valid){
		$customer->Contact->Add();
		$customer->Add();

		if(isset($_REQUEST['noemail']) && ($_REQUEST['noemail'] == 'Y')) {
			$customer->Username = sprintf('%d@no-email.co.uk', $customer->ID);
			$customer->Contact->Person->Email = $customer->Username;
			$customer->Update();
		}

		if($isOrg){
			$customer->Contact->Person->Position = $form->GetValue('position');

			$contact = new Contact();
			$contact->Type = 'O';
			$contact->IsCustomer = 'Y';
			$contact->Organisation->Name = $form->GetValue('name');
			$contact->Organisation->Type->ID = $form->GetValue('type');
			$contact->Organisation->Industry->ID = $form->GetValue('industry');
			$contact->Organisation->Address->Line1 = $form->GetValue('businessaddress1');
			$contact->Organisation->Address->Line2 = $form->GetValue('businessaddress2');
			$contact->Organisation->Address->Line3 = $form->GetValue('businessaddress3');
			$contact->Organisation->Address->City = $form->GetValue('businesscity');
			$contact->Organisation->Address->Country->ID = $form->GetValue('businesscountry');
			$contact->Organisation->Address->Region->ID = $form->GetValue('businessregion');
			$contact->Organisation->Address->Zip = $form->GetValue('businesspostcode');
			
			if($form->GetValue('accountsinvoice') == 'Y') {
				$contact->Organisation->InvoiceName = $form->GetValue('accountsname');
				$contact->Organisation->InvoiceAddress->Line1 = $form->GetValue('accountsaddress1');
				$contact->Organisation->InvoiceAddress->Line2 = $form->GetValue('accountsaddress2');
				$contact->Organisation->InvoiceAddress->Line3 = $form->GetValue('accountsaddress3');
				$contact->Organisation->InvoiceAddress->City = $form->GetValue('accountscity');
				$contact->Organisation->InvoiceAddress->Country->ID = $form->GetValue('accountscountry');
				$contact->Organisation->InvoiceAddress->Region->ID = $form->GetValue('accountsregion');
				$contact->Organisation->InvoiceAddress->Zip = $form->GetValue('accountspostcode');
				$contact->Organisation->UseInvoiceAddress = 'Y'; 
			}
			
			$contact->Organisation->Phone1 = $customer->Contact->Person->Phone1;
			$contact->Organisation->Fax = $customer->Contact->Person->Fax;
			$contact->Organisation->Email = $customer->Contact->Person->Email;
			$contact->Organisation->CompanyNo = $form->GetValue('reg');
			$contact->Add();

			$customer->Contact->Parent->ID = $contact->ID;
			$customer->Contact->Update();
		}

		$cart = new Cart($session, true);
		$cart->Customer->ID = $customer->ID;
		$cart->Update();

		redirect("Location: order_shipping.php");
	}
}

$scripts = sprintf('<script language="javascript" type="text/javascript">
	var toggleAccounts = function(obj) {
		if(obj.checked) {
			document.getElementById(\'accountsname\').removeAttribute(\'disabled\');
			document.getElementById(\'accountspostcode\').removeAttribute(\'disabled\');
			document.getElementById(\'accountsaddress1\').removeAttribute(\'disabled\');
			document.getElementById(\'accountsaddress2\').removeAttribute(\'disabled\');
			document.getElementById(\'accountsaddress3\').removeAttribute(\'disabled\');
			document.getElementById(\'accountscity\').removeAttribute(\'disabled\');
			document.getElementById(\'accountscountry\').removeAttribute(\'disabled\');
		} else {
			document.getElementById(\'accountsname\').setAttribute(\'disabled\', \'disabled\');
			document.getElementById(\'accountspostcode\').setAttribute(\'disabled\', \'disabled\');
			document.getElementById(\'accountsaddress1\').setAttribute(\'disabled\', \'disabled\');
			document.getElementById(\'accountsaddress2\').setAttribute(\'disabled\', \'disabled\');
			document.getElementById(\'accountsaddress3\').setAttribute(\'disabled\', \'disabled\');
			document.getElementById(\'accountscity\').setAttribute(\'disabled\', \'disabled\');
			document.getElementById(\'accountscountry\').setAttribute(\'disabled\', \'disabled\');
			document.getElementById(\'accountsregion\').setAttribute(\'disabled\', \'disabled\');
			
			if(document.getElementById(\'accountsregion\').options.length > 1) {
				document.getElementById(\'accountsregion\').removeAttribute(\'disabled\');
			}
		}
	}

	window.onload = function() {
		var e = document.getElementById(\'accountsinvoice\');

		toggleAccounts(e);
	}
	</script>');

$page = new Page('Create New Order', '');
$page->LinkScript('js/regions.php');
$page->LinkScript('js/pcAnywhere.js');
$page->LinkScript('../../js/jquery.js');
$page->AddToHead("
	<script language=\"javascript\" type=\"text/javascript\">
		function swapAccount(obj){
			var position = document.getElementById('blockposition');
			var business = document.getElementById('blockbusiness');
			var personal = document.getElementById('blockpersonal');

			if(obj.value == 'O'){
				position.style.display = 'table-row';
				business.style.display = '';
				personal.style.display = 'none';
			} else {
				position.style.display = 'none';
				business.style.display = 'none';
				personal.style.display = '';
			}
		}

		jQuery(function($) {
			$(document).ready(function(){

				var address1 = $('#address1');
				var address2 = $('#address2');
				var address3 = $('#address3');
				var city = $('#city');
				var country = $('#country');
				var region = $('#region');
				var postcode = $('#postcode');

				var businessAddress1 = $('#businessaddress1');
				var businessAddress2 = $('#businessaddress2');
				var businessAddress3 = $('#businessaddress3');
				var businessCity = $('#businesscity');
				var businessCountry = $('#businesscountry');
				var businessRegion = $('#businessregion');
				var businessPostcode = $('#businesspostcode');

				var businessAddress1Changed = false;
				var businessAddress2Changed = false;
				var businessAddress3Changed = false;
				var businessCityChanged = false;
				var businessCountryChanged = false;
				var businessRegionChanged = false;
				var businessPostcodeChanged = false;

				address1.change(function(){
					if(!businessAddress1Changed && !businessAddress1.val()){
						businessAddress1.val(address1.val());
					}
				});
				
				businessAddress1.change(function(){
					businessAddress1Changed = true;
				});

				address2.change(function(){
					if(!businessAddress2Changed && !businessAddress2.val()){
						businessAddress2.val(address2.val());
					}
				});
				
				businessAddress2.change(function(){
					businessAddress2Changed = true;
				});

				address3.change(function(){
					if(!businessAddress3Changed && !businessAddress3.val()){
						businessAddress3.val(address3.val());
					}
				});
				
				businessAddress3.change(function(){
					businessAddress3Changed = true;
				});

				city.change(function(){
					if(!businessCityChanged && !businessCity.val()){
						businessCity.val(city.val());
					}
				});
				
				businessCity.change(function(){
					businessCityChanged = true;
				});

				country.change(function(){
					if(!businessCountryChanged){
						businessCountry.val(country.val());
						businessCountry.trigger('change');
					}
				});
				
				businessCountry.change(function(){
					businessCountryChanged = true;
				});

				region.change(function(){
					if(!businessRegionChanged){
						businessRegion.val(region.val());
					}
				});
				
				businessRegion.change(function(){
					businessRegionChanged = true;
				});

				postcode.change(function(){
					if(!businessPostcodeChanged && !businessPostcode.val()){
						businessPostcode.val(postcode.val());
					}
				});
				
				businessPostcode.change(function(){
					businessPostcodeChanged = true;
				});
			});
		});



		function getBusinessAddress() {
			var businessCountry = document.getElementById('businesscountry');

			if(businessCountry) {
				businessCountry.options.selectedIndex = 1;
				propogateRegions('businessregion', businessCountry);
				Address.find(document.getElementById('businesspostcode'));
			}
		}

		function getAccountsAddress() {
			var accountsCountry = document.getElementById('accountscountry');

			if(accountsCountry) {
				accountsCountry.options.selectedIndex = 1;
				propogateRegions('accountsregion', accountsCountry);
				Address.find(document.getElementById('accountspostcode'));
			}
		}

		function getAddress() {
			var country = document.getElementById('country');

			if(country) {
				country.options.selectedIndex = 1;
				propogateRegions('region', country);
				Address.find(document.getElementById('postcode'));
			}
		}

		Address.account = '".$GLOBALS['POSTCODEANYWHERE_ACCOUNT']."';
		Address.licence = '".$GLOBALS['POSTCODEANYWHERE_LICENCE']."';

		Address.add('businesspostcode', 'line1', 'businessaddress2');
		Address.add('businesspostcode', 'line2', 'businessaddress3');
		Address.add('businesspostcode', 'line3', null);
		Address.add('businesspostcode', 'city', 'businesscity');
		Address.add('businesspostcode', 'county', 'businessregion');

		Address.add('accountspostcode', 'line1', 'accountsaddress2');
		Address.add('accountspostcode', 'line2', 'accountsaddress3');
		Address.add('accountspostcode', 'line3', null);
		Address.add('accountspostcode', 'city', 'accountscity');
		Address.add('accountspostcode', 'county', 'accountsregion');

		Address.add('postcode', 'line1', 'address2');
		Address.add('postcode', 'line2', 'address3');
		Address.add('postcode', 'line3', null);
		Address.add('postcode', 'city', 'city');
		Address.add('postcode', 'county', 'region');
	</script>");

$page->AddToHead($scripts);
$page->Display('header');
?>

<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top">
    
    	<?php
	    $prompt = new TelePrompt();
		$prompt->Output('orderregistercustomer');
		
		echo $prompt->Body;

			if(!$form->Valid){
				echo $form->GetError();
				echo "<br>";
			}
			echo $form->Open();
			echo $form->GetHtml('action');
			echo $form->GetHtml('confirm');
			echo $form->GetHtml('password');
			?>
			<table width="100%" cellspacing="0" class="form">
              <tr>
                <th width="100%">Account Type</th>
              </tr>
              <tr>
                <td>
                  <label for="account">I am a </label>
				  <?php echo $form->GetHtml('account'); ?> user.
                </td>
              </tr>
            </table>
			<br />

			<table width="100%" cellspacing="0" class="form">
              <tr>
								<th colspan="2">Contact Details</th>
              </tr>
              <tr>
							  	<td width="28%">Name</td>
								<td width="72%"><table border="0" cellspacing="0" cellpadding="0">
	                                    <tr>
	                                        <td><?php echo $form->GetLabel('title'); ?><br /><?php echo $form->GetHtml('title'); ?>&nbsp;</td>
	                                        <td><?php echo $form->GetLabel('fname'); ?> <?php echo $form->GetIcon('fname'); ?><br /><?php echo $form->GetHtml('fname'); ?>&nbsp;</td>
	                                        <td><?php echo $form->GetLabel('lname'); ?> <?php echo $form->GetIcon('lname'); ?><br /><?php echo $form->GetHtml('lname'); ?>&nbsp;</td>
										</tr>
	                                </table>
	                                
                                </td>
							</tr>
							<tr id="blockposition" style="display: <?php echo ($isBusiness) ? 'table-row' : 'none'; ?>;">
								<td><?php echo $form->GetLabel('position'); ?></td>
								<td><?php echo $form->GetHtml('position'); ?><?php echo $form->GetIcon('position'); ?></td>
							</tr>
							<tr>
							  <td><?php echo $form->GetLabel('noemail'); ?> </td>
							  <td><?php echo $form->GetHtml('noemail'); ?> (Check this box if the customer has no email address).</td>
							</tr>
							<tr>
							  <td><?php echo $form->GetLabel('email'); ?> </td>
							  <td><?php echo $form->GetHtml('email'); ?> <?php echo $form->GetIcon('email'); ?></td>
							</tr>
							<tr>
							  <td><?php echo $form->GetLabel('phone'); ?> </td>
							  <td><?php echo $form->GetHtml('phone'); ?> <?php echo $form->GetIcon('phone'); ?></td>
			              </tr>
			              <tr>
							  <td><?php echo $form->GetLabel('fax'); ?> </td>
							  <td><?php echo $form->GetHtml('fax'); ?> <?php echo $form->GetIcon('fax'); ?></td>
			              </tr>
			            </table>
			  <div id="blockbusiness" style="display:<?php echo ($isBusiness)?'block':'none';?>;">
			<br />
		<table width="100%" cellspacing="0" class="form">
							<tr>
                        <th colspan="2">Business Details </th>
							</tr>
							<tr>
                        <td width="28%"><?php echo $form->GetLabel('name'); ?> </td>
                        <td width="72%"><?php echo $form->GetHtml('name'); ?> <?php echo $form->GetIcon('name'); ?></td>
							</tr>
							<tr>
                        <td><?php echo $form->GetLabel('businesspostcode'); ?> </td>
                        <td><?php echo $form->GetHtml('businesspostcode'); ?> <?php echo $form->GetIcon('businesspostcode'); ?>
						<a href="javascript:getBusinessAddress();"><img src="images/icon_search_1.gif" border="0" align="absmiddle" />
						 Auto-complete address (UK residents)</a> </td>
							</tr>
							<tr>
                        <td><?php echo $form->GetLabel('businessaddress1'); ?> </td>
                        <td><?php echo $form->GetHtml('businessaddress1'); ?> <?php echo $form->GetIcon('businessaddress1'); ?></td>
							</tr>
							<tr>
                        <td><?php echo $form->GetLabel('businessaddress2'); ?> </td>
                        <td><?php echo $form->GetHtml('businessaddress2'); ?> <?php echo $form->GetIcon('businessaddress2'); ?></td>
							</tr>
							<tr>
                        <td><?php echo $form->GetLabel('businessaddress3'); ?> </td>
                        <td><?php echo $form->GetHtml('businessaddress3'); ?> <?php echo $form->GetIcon('businessaddress3'); ?></td>
						  </tr>
							<tr>
                        <td><?php echo $form->GetLabel('businesscity'); ?> </td>
                        <td><?php echo $form->GetHtml('businesscity'); ?> <?php echo $form->GetIcon('businesscity'); ?></td>
						  </tr>
							<tr>
                        <td><?php echo $form->GetLabel('businesscountry'); ?> </td>
                        <td><?php echo $form->GetHtml('businesscountry'); ?> <?php echo $form->GetIcon('businesscountry'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businessregion'); ?> </td>
                        <td><?php echo $form->GetHtml('businessregion'); ?> <?php echo $form->GetIcon('businessregion'); ?></td>
                      </tr>
					  <tr>
						<td><?php echo $form->GetLabel('type'); ?></td>
						<td><?php echo $form->GetHtml('type'); ?> <?php echo $form->GetIcon('type'); ?></td>
					</tr>
					<tr>
						<td><?php echo $form->GetLabel('industry'); ?></td>
						<td><?php echo $form->GetHtml('industry'); ?> <?php echo $form->GetIcon('industry'); ?></td>
					</tr>
					<tr>
						<td><?php echo $form->GetLabel('reg'); ?></td>
						<td><?php echo $form->GetHtml('reg'); ?> <?php echo $form->GetIcon('reg'); ?></td>
					</tr>
			  </table>
			  <br />

			  <table width="100%" cellspacing="0" class="form">
							<tr>
                        <th colspan="2">Accounts Details </th>
							</tr>
							<tr>
							  <td width="28%"><?php echo $form->GetLabel('accountsinvoice'); ?> </td>
							  <td width="72%"><?php echo $form->GetHtml('accountsinvoice'); ?></td>
							</tr>
							<tr>
							  <td> <?php echo $form->GetLabel('accountsname'); ?> </td>
							  <td><?php echo $form->GetHtml('accountsname'); ?> <?php echo $form->GetIcon('accountsname'); ?></td>
							</tr>
							<tr>
		                        <td><?php echo $form->GetLabel('accountspostcode'); ?> </td>
		                        <td><?php echo $form->GetHtml('accountspostcode'); ?> <?php echo $form->GetIcon('accountspostcode'); ?>
								<a href="javascript:Address.find(document.getElementById('accountspostcode'));"><img src="images/icon_search_1.gif" border="0" align="absmiddle" />Auto-complete address (UK residents)</a> </td>
							</tr>
							<tr>
                        <td width="28%"><?php echo $form->GetLabel('accountsaddress1'); ?> </td>
                        <td width="72%"><?php echo $form->GetHtml('accountsaddress1'); ?> <?php echo $form->GetIcon('accountsaddress1'); ?></td>
						  </tr>
							<tr>
                        <td><?php echo $form->GetLabel('accountsaddress2'); ?> </td>
                        <td><?php echo $form->GetHtml('accountsaddress2'); ?> <?php echo $form->GetIcon('accountsaddress2'); ?></td>
						  </tr>
							<tr>
                        <td><?php echo $form->GetLabel('accountsaddress3'); ?> </td>
                        <td><?php echo $form->GetHtml('accountsaddress3'); ?> <?php echo $form->GetIcon('accountsaddress3'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('accountscity'); ?> </td>
                        <td><?php echo $form->GetHtml('accountscity'); ?> <?php echo $form->GetIcon('accountscity'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('accountscountry'); ?> </td>
                        <td><?php echo $form->GetHtml('accountscountry'); ?> <?php echo $form->GetIcon('accountscountry'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('accountsregion'); ?> </td>
                        <td><?php echo $form->GetHtml('accountsregion'); ?> <?php echo $form->GetIcon('accountsregion'); ?></td>
                      </tr>
			  </table>

					<br />
                    </div>

                    <div id="blockpersonal" style="display:<?php echo ($isBusiness)?'none' : 'block';?>">

					<table width="100%" cellspacing="0" class="form">
                      <tr>
                        <th colspan="2">Address Details</th>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('postcode'); ?> </td>
                        <td><?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?>
						<a href="javascript:getAddress();"><img src="images/icon_search_1.gif" border="0" align="absmiddle" />
						 Auto-complete address (UK residents)</a>
						</td>
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
                    </table>
					<br />
                    </div>

			        <input name="continue" type="submit" class="btn" id="continue" value="continue" />

						<?php echo $form->Close(); ?>


    </td>
  </tr>
</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>
