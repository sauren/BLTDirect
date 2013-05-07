<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');

$isExpress = (param('express') == 'true') ? true : false;

if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])){
	$url = ($GLOBALS['USE_SSL'])?$GLOBALS['HTTPS_SERVER']:$GLOBALS['HTTP_SERVER'];
	$self = substr($_SERVER['PHP_SELF'], 1);
	$qs = '';
	if(!empty($_SERVER['QUERY_STRING'])){$qs = '?' . $_SERVER['QUERY_STRING'];}
	redirect(sprintf("Location: %s%s%s", $url, $self, $qs));
}

if(param('direct', false) === false){
	redirect("Location: gateway.php");
}

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('direct', 'Direct', 'hidden', '', 'paragraph', 1, 255);
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


$form->AddField('lname', 'Last Name', 'text', '', 'name', 1, 60, true);
$form->AddField('position', 'Position', 'text', '', 'anything', 1, 100, false);
$form->AddField('email', 'Email Address', 'text', '', 'email', NULL, NULL);
$form->AddField('phone', 'Daytime Phone', 'text', '', 'telephone', NULL, NULL, true);
$form->AddField('name', 'Business Name', 'text', '', 'address', 1, 100, ($isBusiness)?true:false);
$form->AddField('businesspostcode', 'Postcode', 'text', '', 'postcode', 1, 10,  false);
$form->AddField('businessaddress1', 'Property Name/Number', 'text', '', 'address', 1, 150, ($isBusiness)?true:false);
$form->AddField('businessaddress2', 'Street', 'text', '', 'address', 1, 150, ($isBusiness)?true:false);
$form->AddField('businessaddress3', 'Area', 'text', '', 'address', 1, 150, false);
$form->AddField('businesscity', 'City', 'text', '', 'address', 1, 150, ($isBusiness)?true:false);

$form->AddField('businesscountry', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, ($isBusiness)?true:false, 'onChange="propogateRegions(\'businessregion\', this);"');
$form->AddOption('businesscountry', '0', '');
$form->AddOption('businesscountry', '222', 'United Kingdom');

$data = new DataQuery("select * from countries order by Country asc");
while($data->Row){
	$form->AddOption('businesscountry', $data->Row['Country_ID'], $data->Row['Country']);
	$data->Next();
}
$data->Disconnect();

$regionCount = 0;
$businessCountryId = $form->GetValue('businesscountry');
$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($businessCountryId)));
$regionCount = $region->TotalRows;
if($regionCount > 0){
	$form->AddField('businessregion', 'Region', 'select', '', 'numeric_unsigned', 1, 11, ($isBusiness)?true:false);
	$form->AddOption('businessregion', '', '');
	while($region->Row){
		$form->AddOption('businessregion', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
} else {
	$form->AddField('businessregion', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
	$form->AddOption('businessregion', '', '');
}
$region->Disconnect();

$form->AddField('industry', 'Your Industry', 'select', '', 'numeric_unsigned', 1, 11, false);
$form->AddOption('industry', '', '');
$industry = new DataQuery("select * from organisation_industry order by Industry_Name asc");
while($industry->Row){
	$form->AddOption('industry', $industry->Row['Industry_ID'], $industry->Row['Industry_Name']);
	$industry->Next();
}
$industry->Disconnect();






$form->AddField('asBusiness', 'Same as Business Address', 'checkbox', 'N', 'boolean', NULL, NULL, false, 'onkeypress="asbusiness(this);" onclick="asbusiness(this);"');

$asBusinessFieldValue = $form->GetValue('asBusiness');
$isAsBusiness = ($asBusinessFieldValue == 'Y')?true:false;

$form->AddField('postcode', 'Postcode', 'text', '', 'anything', 1, 10, false, ($isAsBusiness)?'disabled="disabled"':'');
if($isAsBusiness) $form->SetValue('postcode', $form->GetValue('businesspostcode'));
$form->AddField('address1', 'Property Name/Number', 'text', '', 'address', 1, 150, (!$isBusiness || !$isAsBusiness)?true:false, ($isAsBusiness)?'disabled="disabled"':'');
if($isAsBusiness) $form->SetValue('address1', $form->GetValue('businessaddress1'));
$form->AddField('address2', 'Street', 'text', '', 'address', 1, 150, (!$isBusiness || !$isAsBusiness)?true:false, ($isAsBusiness)?'disabled="disabled"':'');
if($isAsBusiness) $form->SetValue('address2', $form->GetValue('businessaddress2'));
$form->AddField('address3', 'Area', 'text', '', 'address', 1, 150, false, ($isAsBusiness)?'disabled="disabled"':'');
if($isAsBusiness) $form->SetValue('address3', $form->GetValue('businessaddress3'));
$form->AddField('city', 'City', 'text', '', 'address', 1, 150, (!$isBusiness || !$isAsBusiness)?true:false, ($isAsBusiness)?'disabled="disabled"':'');
if($isAsBusiness) $form->SetValue('city', $form->GetValue('businesscity'));

$str = 'onChange="propogateRegions(\'region\', this);" ' ;
$str .= ($isAsBusiness)?'disabled="disabled"':'';
$form->AddField('country', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, true, $str);
$form->AddOption('country', '0', '');
$form->AddOption('country', '222', 'United Kingdom');

$data = new DataQuery("select * from countries order by Country asc");
while($data->Row){
	$form->AddOption('country', $data->Row['Country_ID'], $data->Row['Country']);
	$data->Next();
}
$data->Disconnect();






if($isAsBusiness) $form->SetValue('country', $form->GetValue('businesscountry'));

$regionCount = 0;
$countryId = $form->GetValue('country');
$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($countryId)));
$regionCount = $region->TotalRows;
if($regionCount > 0){
	$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, (!$isBusiness)?true:false, ($isAsBusiness)?'disabled="disabled"':'');
	$form->AddOption('region', '', '');

	while($region->Row){
		$form->AddOption('region', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
} else {
	$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false, 'disabled="disabled"');
	$form->AddOption('region', '', '');
}
$region->Disconnect();

if($isAsBusiness) $form->SetValue('region', $form->GetValue('businessregion'));

if(!$isExpress){
	$form->AddField('password', 'Password', 'password', '', 'password', PASSWORD_LENGTH_CUSTOMER, 100);
	$form->AddField('confirmPassword', 'Confirm Password', 'password', '', 'password', PASSWORD_LENGTH_CUSTOMER, 100);
}

$form->AddField('terms', 'Agree to Terms and Conditions', 'checkbox', 'N', 'boolean', 1, 1, true);
$form->AddField('subscribe', 'Subscribe to newsletter which includes special offers and new products', 'checkbox', 'N', 'boolean', 1, 1, false);

$confirmPassError  =  "";
$userError = "";
$emailError = "";

$login = new Form($_SERVER['PHP_SELF']);
$login->AddField('action', 'Action', 'hidden', 'login', 'alpha', 4, 6);
$login->SetValue('action', 'login');
$login->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$login->AddField('direct', 'Direct', 'hidden', '', 'link_relative', 1, 255);
$login->AddField('username', 'E-mail Address', 'text', $form->GetValue('email'), 'anything', 6, 100);
$login->AddField('password', 'Password', 'password', '', 'password', 6, 100);

$assistant = new Form($_SERVER['PHP_SELF']);
$assistant->TabIndex = 3;
$assistant->AddField('action', 'Action', 'hidden', 'assistant', 'alpha', 1, 11);
$assistant->SetValue('action', 'assistant');
$assistant->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$assistant->AddField('emailOrUser', 'Email Address', 'text', $form->GetValue('email'), 'paragraph', 1, 100);
$assistant->AddField('direct', 'Direct', 'hidden', '', 'link_relative', 1, 255);

$customer = new Customer();

if(strtolower(param('confirm', '')) == "true"){
	if($action == "login"){
		$login->Validate();

		if($session->Login($login->GetValue('username'), $login->GetValue('password'))){
			redirect("Location: " . $login->GetValue('direct'));
		} else {
			$login->AddError("Sorry you were unable to login. Please check your email address and password and try again. If you are struggling to log in you can use our forgotten password facility to retrieve your password.");
		}
	} elseif ($action == "assistant") {
		$str = $assistant->GetValue('emailOrUser');
		if(empty($str)){
			redirect("Location: gateway.php");
		} else {
			$customer = new Customer();

			if(!$customer->IsUnique($str)){
				$customer->Get();
				$customer->Contact->Get();
				$customer->ResetPasswordEmail();

				redirect(sprintf("Location: gateway.php?assistant=successful"));
			} else {
				$assistant->AddError('Sorry, we could not find your entry in our database.');
			}
		}
	} else {
		$form->Validate();

		if(!$isExpress){
			if($form->GetValue('password') != $form->GetValue('confirmPassword')){
				$form->AddError('Confirm Password is not the same as Password.', 'confirmPassword');
				$confirmPassError = "Is not the same as your Password.";
			}
		}

		$isOrg = (strtolower($form->GetValue('account')) == 'o')? true : false;


		if($isOrg){

		  	if($form->GetValue('businesscountry') == 0){
		  		$form->AddError('You have yet to select a business country.', 'accountscountry');
		  	}
		  	/*if($form->GetValue('businessregion') == 0){
		  		$form->AddError('You have yet to select a business region.', 'businessregion');
		  	}*/
		  	
		  	if($form->GetValue('asBusiness') == 'N') {

			  	if($form->GetValue('country') == 0){
	    			$form->AddError('You have yet to select a country.', 'country');
		  		}
		  		/*if($form->GetValue('region') == 0){
		    		$form->AddError('You have yet to select a region.', 'region');
		  		}*/
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


		$customer = new Customer();
		$customer->Username = $form->GetValue('email');

		if(!$isExpress){
			$customer->SetPassword($form->GetValue('password'));
		}

		$customer->Contact->Type = 'I';
		$customer->Contact->IsCustomer = 'Y';
		$customer->Contact->Person->Title = $form->GetValue('title');
		$customer->Contact->Person->Name = addslashes($form->GetValue('fname'));
		$customer->Contact->Person->LastName = addslashes($form->GetValue('lname'));
		$customer->Contact->Person->Phone1 = $form->GetValue('phone');
		$customer->Contact->Person->Email = $form->GetValue('email');
		$customer->Contact->Person->Address->Line1 = addslashes(($isAsBusiness)?$form->GetValue('businessaddress1'):$form->GetValue('address1'));
		$customer->Contact->Person->Address->Line2 = addslashes(($isAsBusiness)?$form->GetValue('businessaddress2'):$form->GetValue('address2'));
		$customer->Contact->Person->Address->Line3 = addslashes(($isAsBusiness)?$form->GetValue('businessaddress3'):$form->GetValue('address3'));
		$customer->Contact->Person->Address->City = addslashes(($isAsBusiness)?$form->GetValue('businesscity'):$form->GetValue('city'));
		$customer->Contact->Person->Address->Country->ID = ($isAsBusiness)?$form->GetValue('businesscountry'):$form->GetValue('country');
		$customer->Contact->Person->Address->Region->ID = ($isAsBusiness)?$form->GetValue('businessregion'):$form->GetValue('region');
		$customer->Contact->Person->Address->Zip = addslashes(($isAsBusiness)?$form->GetValue('businesspostcode'):$form->GetValue('postcode'));

		if(!$isExpress){
			if($form->GetValue('subscribe') == 'Y') {
				$customer->Contact->OnMailingList = 'H';
			} else {
				$customer->Contact->OnMailingList = 'N';
			}
		} else {
			$customer->Contact->OnMailingList = 'H';
		}

		if(!$customer->IsEmailUnique($form->GetValue('email'))){
			$form->AddError('Email address already exists on our system.', 'email');
			$emailError = "<span class=\"alert\">Already Exists in our Database.</span>";
		}

		if($form->Valid){
			$customer->Contact->Add();
			$customer->Add();

			$session->Customer->ID = $customer->ID;
			$session->Update();

			if($isOrg){
				$customer->Contact->Person->Position = $form->GetValue('position');

				$contact = new Contact;
				$contact->Type = 'O';
				$contact->IsCustomer = 'Y';
				$contact->Organisation->Name = addslashes($form->GetValue('name'));
				$contact->Organisation->Type->ID = 0;
				$contact->Organisation->Industry->ID = $form->GetValue('industry');
				$contact->Organisation->Address->Line1 = addslashes($form->GetValue('businessaddress1'));
				$contact->Organisation->Address->Line2 = addslashes($form->GetValue('businessaddress2'));
				$contact->Organisation->Address->Line3 = addslashes($form->GetValue('businessaddress3'));
				$contact->Organisation->Address->City = addslashes($form->GetValue('businesscity'));
				$contact->Organisation->Address->Country->ID = $form->GetValue('businesscountry');
				$contact->Organisation->Address->Region->ID = $form->GetValue('businessregion');
				$contact->Organisation->Address->Zip = addslashes($form->GetValue('businesspostcode'));
				$contact->Organisation->Phone1 = $customer->Contact->Person->Phone1;
				$contact->Organisation->Email = $customer->GetEmail();
				$contact->Add();

				$customer->Contact->Parent->ID = $contact->ID;
				$customer->Contact->Update();

			}
			redirect("Location: " . $form->GetValue('direct'));
		}
	}
}

require_once('lib/' . $renderer . $_SERVER['PHP_SELF']);
require_once('lib/common/appFooter.php');