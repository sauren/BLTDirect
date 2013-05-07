<?php require_once('../lib/common/appHeadermobile.php');
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

$form->AddField('fname', 'First Name', 'text', '', 'name', 1, 60, true);
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
include("ui/nav.php");
include("ui/search.php");?>
<script src="../ignition/js/regions.php" type="text/javascript"></script>
	<script type="text/javascript" src="js/pcAnywhere.js"></script>
	<script type="text/javascript">
	function swapAccount(obj){
		var position = document.getElementById('position');
		var business = document.getElementById('business');
		var same = document.getElementById('asbusinessRow');
		var asBusinessBox = document.getElementById('asBusiness');
		var country = document.getElementById('country');
		
		if(obj.value == 'O'){
			position.style.display = '';
			business.style.display = '';
			same.style.display = '';
		} else {
			if(asBusinessBox.checked){
				asBusinessBox.checked = false;
				asbusiness(asBusinessBox);
			}
			country.disabled = false;
			position.style.display = 'none';
			business.style.display = 'none';

			same.style.display = 'none';
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
					businessCountry.trigger("change");
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

	function asbusiness(obj){
		var businessAddress1 = document.getElementById('businessaddress1');
		var businessAddress2 = document.getElementById('businessaddress2');
		var businessAddress3 = document.getElementById('businessaddress3');
		var businessCity = document.getElementById('businesscity');
		var businessCountry = document.getElementById('businesscountry');
		var businessRegion = document.getElementById('businessregion');
		var businessPostcode = document.getElementById('businesspostcode');
		var address1 = document.getElementById('address1');
		var address2 = document.getElementById('address2');
		var address3 = document.getElementById('address3');
		var city = document.getElementById('city');
		var country = document.getElementById('country');
		var region = document.getElementById('region');
		var postcode = document.getElementById('postcode');


		if(obj.checked){
			address1.value = businessAddress1.value;
			address2.value = businessAddress2.value;
			address3.value = businessAddress3.value;
			city.value = businessCity.value;
			postcode.value = businessPostcode.value;

			country.options[businessCountry.selectedIndex].selected = true;
			propogateRegions('region', country);
			region.options[businessRegion.selectedIndex].selected = true;

			address1.disabled = true;
			address2.disabled = true;
			address3.disabled = true;
			city.disabled = true;
			country.disabled = true;
			region.disabled = true;
			postcode.disabled = true;

		} else {
			address1.disabled = false;
			address2.disabled = false;
			address3.disabled = false;
			city.disabled = false;
			country.disabled = false;
			region.disabled = false;
			postcode.disabled = false;

			if(address1.value == businessAddress1.value) address1.value = '';
			if(address2.value == businessAddress2.value) address2.value = '';
			if(address3.value == businessAddress3.value) address3.value = '';
			if(city.value == businessCity.value) city.value = '';
			if(postcode.value == businessPostcode.value) postcode.value = '';
		}
	}

	function getBusinessAddress() {
		var businessCountry = document.getElementById('businesscountry');

		if(businessCountry) {
			businessCountry.options.selectedIndex = 1;
			propogateRegions('businessregion', businessCountry);
			Address.find(document.getElementById('businesspostcode'));
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

	Address.account = '<?php echo $GLOBALS['POSTCODEANYWHERE_ACCOUNT']; ?>';
	Address.licence = '<?php echo $GLOBALS['POSTCODEANYWHERE_LICENCE']; ?>';

	Address.add('businesspostcode', 'line1', 'businessaddress2');
	Address.add('businesspostcode', 'line2', 'businessaddress3');
	Address.add('businesspostcode', 'line3', null);
	Address.add('businesspostcode', 'city', 'businesscity');
	Address.add('businesspostcode', 'county', 'businessregion');

	Address.add('postcode', 'line1', 'address2');
	Address.add('postcode', 'line2', 'address3');
	Address.add('postcode', 'line3', null);
	Address.add('postcode', 'city', 'city');
	Address.add('postcode', 'county', 'region');
	</script>
    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Your Details</span></div>
<div class="maincontent">
<div class="maincontent1">
			<p>Required fields are marked with an asterisk (*) and must be filled to complete your registration.</p>
			
			<?php
				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}
			?>

			<?php if(strtolower(param('confirm', '')) == "true" && (!$assistant->Valid || !$login->Valid || !$customer->IsEmailUnique($form->GetValue('email')))){ ?>
				<div class="emailExistingWarning">
					<p><strong>Your email address already exists within our system.</strong><br />If you remember your Password you can login below, or use our Forgotten Password facility.</p>
				<?php
					if(!$login->Valid){
						echo $login->GetError();
						echo "<br>";
					}

					echo $login->Open();
					echo $login->GetHtml('action');
					echo $login->GetHtml('confirm');
					echo $login->GetHtml('direct');
				?>
					<!-- new stuff -->
					<div id="loginBox" class="gatewayBox">
						<div class="container">
							<div class="title">Existing Customer</div>
							<div class="content">
								<p><?php echo $login->GetLabel('username'); ?>:<br />
								<?php echo $login->GetHtml('username'); ?></p>
								<p><?php echo $login->GetLabel('password'); ?>:<br />
								<?php echo $login->GetHtml('password'); ?></p>
							</div>
							<input type="image" src="images/login/blueBox_continue.gif" alt="Login" class="image" />
						</div>
					</div>
				<?php
					echo $login->Close();

					if(!$assistant->Valid){
						echo $assistant->GetError();
						echo "<br>";
					}
					
					echo $assistant->Open();
					echo $assistant->GetHtml('action');
					echo $assistant->GetHtml('confirm');
					echo $assistant->GetHtml('direct');
				?>
					<div style="float:left; margin:20px;">
						<h3>Forgotten Password?</h3>
						<?php
							if(isset($_REQUEST['assistant']) && ($_REQUEST['assistant'] == 'successful')) {
								echo "<span class=\"alert\">Your password reset information has been sent to your email address.</span><br />";
							} else {
								if(!$assistant->Valid){
									echo "<span class=\"alert\">Sorry, we could not find your entry in our database.</span><br />";
									echo "<br>";
								}
							}

							echo sprintf('%s : %s', $assistant->GetLabel('emailOrUser'), $assistant->GetHtml('emailOrUser'));
						?>			
						<input type="submit" class="greySubmit" name="continue" value="continue" />
					</div>
					<?php
						echo $assistant->Close();
					?>
					<div class="clear"></div>
				</div>
			<?php } ?>
			<div class="clear"></div>
			<br />

			<?php 
				echo $form->Open();
				echo $form->GetHtml('action');
				echo $form->GetHtml('confirm');
				echo $form->GetHtml('direct');
			?>

			<table style="width:100%" cellspacing="0" class="form">
              <tr>
<?php if($isExpress){ ?>
                <th style="width:100%;">Customer Type</th>
<?php } else { ?>
                <th style="width:100%;">Account Type</th>
<?php } ?>
              </tr>
              <tr>
                <td>
                  <label for="account">I am a </label>
				  <?php echo $form->GetHtml('account'); ?> customer.
				</td>
              </tr>
            </table>
			<table style="width:100%" cellspacing="0" class="form">
				<tr>
					<th colspan="4">Your Login Details</th>
				</tr>
				<tr>
                  <td colspan="4">Please remember your below e-mail address for the purposes of logging in.</td>
				</tr>
				<tr>
				  <td style="width:28%;"> <?php echo $form->GetLabel('email'); ?> </td>
				  </tr>
                  <tr>
                  <td colspan="3"> <?php echo $form->GetHtml('email'); ?> <?php echo $form->GetIcon('email'); ?> <?php echo $emailError; ?></td>
                  </tr>
			</table>
		<table style="width:100%;" cellspacing="0" class="form">
		<tr>
			<th colspan="4">Your Contact Details</th>
		</tr>
        <tr>
      		<td align="left"><?php echo $form->GetLabel('title'); ?> <?php echo $form->GetIcon('title'); ?><br />
          	<?php echo $form->GetHtml('title'); ?> </td>
        </tr>
        <tr>       
           <td><?php echo $form->GetLabel('fname'); ?> <?php echo $form->GetIcon('fname'); ?><br />
               <?php echo $form->GetHtml('fname'); ?> </td>
        </tr>
        <tr>
           <td><?php echo $form->GetLabel('lname'); ?> <?php echo $form->GetIcon('lname'); ?><br />
               <?php echo $form->GetHtml('lname'); ?></td>
           </tr>
		<tr id="position" style="display:none">
		<td><?php echo $form->GetLabel('position'); ?><br />
        <?php echo $form->GetHtml('position'); ?><?php echo $form->GetIcon('position'); ?></td> 
		</tr>
		<tr> <td> <?php echo $form->GetLabel('phone'); ?> </td>
		</tr>
        <tr>
        <td colspan="3"> <?php echo $form->GetHtml('phone'); ?> <?php echo $form->GetIcon('phone'); ?></td>
         </tr>
		</table>
			  <div  id="business" style="display:<?php echo ($isBusiness)?'block':'none';?>">
			  <br />
					<table style="width:100%;" cellspacing="0" class="form">
                      <tr>
                        <th colspan="4">Your Business Details </th>
                      </tr>
                      <tr>
                        <td colspan="4">You may add an alternative address for delivery during the order process.</td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('name'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('name'); ?> <?php echo $form->GetIcon('name'); ?></td>                      </tr>
					  <tr>
                        <td><?php echo $form->GetLabel('businesspostcode'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('businesspostcode'); ?> <?php echo $form->GetIcon('businesspostcode'); ?><br  />
						<a href="javascript:getBusinessAddress();"><img src="images/searchIcon.gif" border="0" align="absmiddle" />
						 Auto-complete address (UK residents)</a> </td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businessaddress1'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('businessaddress1'); ?> <?php echo $form->GetIcon('businessaddress1'); ?></td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businessaddress2'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('businessaddress2'); ?> <?php echo $form->GetIcon('businessaddress2'); ?></td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businessaddress3'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('businessaddress3'); ?> <?php echo $form->GetIcon('businessaddress3'); ?></td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businesscity'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('businesscity'); ?> <?php echo $form->GetIcon('businesscity'); ?></td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businesscountry'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('businesscountry'); ?> <?php echo $form->GetIcon('businesscountry'); ?></td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businessregion'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('businessregion'); ?> <?php echo $form->GetIcon('businessregion'); ?></td>
                        </tr>
					<tr>
						<td><?php echo $form->GetLabel('industry'); ?></td>
						</tr>
                        <tr>
                        <td><?php echo $form->GetHtml('industry'); ?> <?php echo $form->GetIcon('industry'); ?></td>
                        </tr>
                    </table>
                    </div>

					<table style="width:100%;" cellspacing="0" class="form">
                      <tr>
                        <th colspan="4">Your Credit Card Billing Address </th>
                      </tr>
                      <tr>
                        <td colspan="5">Please complete your address below. <b>This must be the same as your credit card billing address</b>. You may add an alternative address for delivery during the order process.</td>
                      </tr>
                      <tr id="asbusinessRow" style="display:<?php echo ($isBusiness)?'block':'none';?>">
                        <td><?php echo $form->GetLabel('asBusiness'); ?>
                        <?php echo $form->GetHtml('asBusiness'); ?> <?php echo $form->GetIcon('asBusiness'); ?>
                        </td>
                        </tr>                      
					  <tr>
                        <td><?php echo $form->GetLabel('postcode'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?>
						<a href="javascript:getAddress();"><img src="images/searchIcon.gif" border="0" align="absmiddle" />
						 Auto-complete address (UK residents)</a>
						</td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('address1'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('address1'); ?> <?php echo $form->GetIcon('address1'); ?></td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('address2'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('address2'); ?> <?php echo $form->GetIcon('address2'); ?></td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('address3'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('address3'); ?> <?php echo $form->GetIcon('address3'); ?></td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('city'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('city'); ?> <?php echo $form->GetIcon('city'); ?></td>
                        </tr>
                        
                      <tr>
                        <td><?php echo $form->GetLabel('country'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('country'); ?> <?php echo $form->GetIcon('country'); ?></td>
                        </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('region'); ?> </td>
                        </tr>
                        <tr>
                        <td colspan="4"><?php echo $form->GetHtml('region'); ?> <?php echo $form->GetIcon('region'); ?></td>
                        </tr>
                    </table>
				<?php
				if($isExpress){
					$form->AddField('express', 'express', 'hidden', 'true', 'alpha', 4, 4);
					echo $form->GetHtml('express');
				} else {
				?>
					    <table style="width:100%;" cellspacing="0" class="form">
							<tr>
								<th colspan="2">Your Security Information</th>
							</tr>
							<tr>
								<td colspan="2">Please complete the following fields for your personal security. </td>
							</tr>
							<tr>
							  <td><?php echo $form->GetLabel('password'); ?> (8 - 100 Alphanumeric Characters) <br />	</td>
					  </tr>
                          <tr>
                          <td><?php echo $form->GetHtml('password'); ?> <?php echo $form->GetIcon('password'); ?></td>
                          </tr>
							<tr>
							  <td><?php echo $form->GetLabel('confirmPassword'); ?> <br />					          </td>
                               </tr>
                          <tr>
                          <td><?php echo $form->GetHtml('confirmPassword'); ?> <?php echo $form->GetIcon('confirmPassword')  . " ". $confirmPassError; ?></td>
                          </tr>
				</table>
<?php } ?>
			            <p>&nbsp;</p>
					    <p>
			              <?php echo $form->GetHtml('terms'); ?><br />
					   <label for="terms"> I have read and Accept the </label>
						<a href="terms.php" target="_blank">Terms and Conditions</a> of Use. <?php echo $form->GetIcon('terms'); ?></p>
						<p>
			              <?php echo $form->GetHtml('subscribe'); ?>
						  <label for="subscribe">I would like to subscribe to your newsletter (you can change your mind in your profile).</label>
						</p>
			            <p>
			              <input name="Continue" type="submit" class="submit" id="Continue" value="Continue" />
		                </p>
						<?php echo $form->Close(); ?>
</div>
</div>
<?php include("ui/footer.php");?>                       
<?php require_once('../lib/common/appFooter.php');