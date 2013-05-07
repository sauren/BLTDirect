<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PostcodeAnywhere.php');

$session->Secure(2);

$address = new Address();
if(isset($_REQUEST['find']) && isset($_REQUEST['postcode']) && $GLOBALS['POSTCODEANYWHERE_ACTIVE']){
	$postcode = $_REQUEST['postcode'];
	$building = '';
	$check = new PostcodeAnywhere;
	$check->GetAddress($building, $postcode);
	$address = $check->Address;
}

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

$form->AddField('fname', 'First Name', 'text', '', 'name', 1, 60, false);
$form->AddField('lname', 'Last Name', 'text', '', 'name', 1, 60, false);
$form->AddField('position', 'Position', 'text', '', 'anything', 1, 100, false);
$form->AddField('email', 'Email Address', 'text', '', 'email', NULL, NULL);
$form->AddField('noemail', 'No Email Address', 'checkbox', '', 'boolean', 1, 1, false);
$form->AddField('phone', 'Daytime Phone', 'text', '', 'telephone', NULL, NULL, false);
$form->AddField('fax', 'Fax', 'text', '', 'telephone', NULL, NULL, false);
$form->AddField('name', 'Business Name', 'text', '', 'anything', 1, 100, ($isBusiness)?true:false);
$form->AddField('businesspostcode', 'Postcode', 'text', '', 'postcode', 1, 10, false);
$form->AddField('businessaddress1', 'Property Name/Number', 'text', '', 'address', 1, 150, false);
$form->AddField('businessaddress2', 'Street', 'text', '', 'address', 1, 150, false);
$form->AddField('businessaddress3', 'Area', 'text', '', 'address', 1, 150, false);
$form->AddField('businesscity', 'City', 'text', '', 'address', 1, 150, false);

$form->AddField('businesscountry', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, false, 'onChange="propogateRegions(\'businessregion\', this);"');
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
	$form->AddField('businessregion', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false);
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

$form->AddField('reg', 'Company Registration', 'text', '', 'anything', 1, 50, false);

$form->AddField('postcode', 'Postcode', 'text', '', 'postcode', 1, 10, false);
$form->AddField('address1', 'Property Name/Number','text', 'address', '', 'address', 1, 150, false);
$form->AddField('address2', 'Street', 'text', '', 'address', 1, 150, false);
$form->AddField('address3', 'Area', 'text', '', 'address', 1, 150, false);
$form->AddField('city', 'City', 'text', '', 'address', 1, 150, false);

$str = 'onChange="propogateRegions(\'region\', this);" ' ;
$form->AddField('country', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, false, $str);
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
	$form->AddField('region', 'Region', 'select', '', 'numeric_unsigned', 1, 11, false);
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

	$form->Validate();

	$isOrg = (strtolower($form->GetValue('account')) == 'o')? true : false;
	$email = (isset($_REQUEST['noemail']) && ($_REQUEST['noemail'] == 'Y')) ? '0@no-email.co.uk' : $form->GetValue('email');

	$customer = new Customer();
	$customer->Username = $email;
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
			$contact->Organisation->Phone1 = $customer->Contact->Person->Phone1;
			$contact->Organisation->Fax = $customer->Contact->Person->Fax;
			$contact->Organisation->Email = $customer->Contact->Person->Email;
			$contact->Organisation->CompanyNo = $form->GetValue('reg');
			$contact->Add();

			$customer->Contact->Parent->ID = $contact->ID;
			$customer->Contact->Update();
		}

		redirect(sprintf('Location: enquiry_summary.php?customerid=%d', $customer->ID));
	}
}

$page = new Page('Create New Enquiry', '');
$page->LinkScript('js/regions.php');
$page->LinkScript('js/pcAnywhere.js');
$page->LinkScript('../js/jquery.js');
$page->AddToHead("
	<script language=\"javascript\" type=\"text/javascript\">
		function swapAccount(obj){
			var department = document.getElementById('department');
			var position = document.getElementById('position');
			var business = document.getElementById('business');
			var personal = document.getElementById('personal');

			if(obj.value == 'O'){
				//department.style.display = 'block';
				position.style.display = 'block';
				business.style.display = 'block';
				personal.style.display = 'none';
			} else {
				//department.style.display = 'none';
				position.style.display = 'none';
				business.style.display = 'none';
				personal.style.display = 'block';
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

		Address.account = '".$GLOBALS['POSTCODEANYWHERE_ACCOUNT']."';
		Address.licence = '".$GLOBALS['POSTCODEANYWHERE_LICENCE']."';

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
	");

$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

echo $form->Open();
echo $form->GetHtml('action');
echo $form->GetHtml('confirm');
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
			<td width="50%">
		<table width="100%" cellspacing="0" class="form">
							<tr>
								<th colspan="4">Contact Details</th>
							</tr>
							<tr>
							  	<td width="28%">Name</td>
								<td colspan="3"><table border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td><?php echo $form->GetLabel('title'); ?><br />
                                            <?php echo $form->GetHtml('title'); ?>&nbsp;</td>
                                        <td><?php echo $form->GetLabel('fname'); ?> <?php echo $form->GetIcon('fname'); ?><br />
                                            <?php echo $form->GetHtml('fname'); ?>&nbsp;</td>
                                        <td><?php echo $form->GetLabel('lname'); ?> <?php echo $form->GetIcon('lname'); ?><br />
                                            <?php echo $form->GetHtml('lname'); ?>&nbsp;</td>
							</tr>
                                </table></td>
							</tr>
							<tr id="position" style="display:<?php echo ($isBusiness)?'block':'none';?>;">
								<td width="28%"><?php echo $form->GetLabel('position'); ?></td>
								<td colspan="3"><?php echo $form->GetHtml('position'); ?><?php echo $form->GetIcon('position'); ?></td>
							</tr>
							<tr>
							  <td> <?php echo $form->GetLabel('noemail'); ?> </td>
							  <td colspan="3"> <?php echo $form->GetHtml('noemail'); ?> (Check this box if the customer has no email address).</td>
							</tr>
							<tr>
							  <td> <?php echo $form->GetLabel('email'); ?> </td>
							  <td colspan="3"> <?php echo $form->GetHtml('email'); ?> <?php echo $form->GetIcon('email'); ?></td>
							</tr>
							<tr>
							  <td> <?php echo $form->GetLabel('phone'); ?> </td>
							  <td colspan="3"> <?php echo $form->GetHtml('phone'); ?> <?php echo $form->GetIcon('phone'); ?></td>
              </tr>
              <tr>
							  <td> <?php echo $form->GetLabel('fax'); ?> </td>
							  <td colspan="3"> <?php echo $form->GetHtml('fax'); ?> <?php echo $form->GetIcon('fax'); ?></td>
              </tr>
            </table>
			  <div  id="business" style="display:<?php echo ($isBusiness)?'block':'none';?>;">
			<br />
		<table width="100%" cellspacing="0" class="form">
							<tr>
                        <th colspan="5">Business Details </th>
							</tr>
							<tr>
                        <td width="28%"><?php echo $form->GetLabel('name'); ?> </td>
                        <td width="72%" colspan="4"><?php echo $form->GetHtml('name'); ?> <?php echo $form->GetIcon('name'); ?></td>
							</tr>
							<tr>
                        <td><?php echo $form->GetLabel('businesspostcode'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businesspostcode'); ?> <?php echo $form->GetIcon('businesspostcode'); ?>
						<a href="javascript:Address.find(document.getElementById('businesspostcode'));"><img src="../images/searchIcon.gif" border="0" align="absmiddle" />
						 Auto-complete address (UK residents)</a> </td>
							</tr>
							<tr>
                        <td width="28%"><?php echo $form->GetLabel('businessaddress1'); ?> </td>
                        <td width="72%" colspan="4"><?php echo $form->GetHtml('businessaddress1'); ?> <?php echo $form->GetIcon('businessaddress1'); ?></td>
						  </tr>
							<tr>
                        <td><?php echo $form->GetLabel('businessaddress2'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businessaddress2'); ?> <?php echo $form->GetIcon('businessaddress2'); ?></td>
						  </tr>
							<tr>
                        <td><?php echo $form->GetLabel('businessaddress3'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businessaddress3'); ?> <?php echo $form->GetIcon('businessaddress3'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businesscity'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businesscity'); ?> <?php echo $form->GetIcon('businesscity'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businesscountry'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businesscountry'); ?> <?php echo $form->GetIcon('businesscountry'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('businessregion'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('businessregion'); ?> <?php echo $form->GetIcon('businessregion'); ?></td>
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
                    </div>

                    <div id="personal" style="display:<?php echo ($isBusiness)?'none' : 'block';?>">

					<table width="100%" cellspacing="0" class="form">
                      <tr>
                        <th colspan="5">Address Details</th>
                      </tr>
					  <tr>
                        <td><?php echo $form->GetLabel('postcode'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?>
						<a href="javascript:Address.find(document.getElementById('postcode'));"><img src="../images/searchIcon.gif" border="0" align="absmiddle" />
						 Auto-complete address (UK residents)</a>
						</td>
                      </tr>
                      <tr>
                        <td width="28%"><?php echo $form->GetLabel('address1'); ?> </td>
                        <td width="72%" colspan="4"><?php echo $form->GetHtml('address1'); ?> <?php echo $form->GetIcon('address1'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('address2'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('address2'); ?> <?php echo $form->GetIcon('address2'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('address3'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('address3'); ?> <?php echo $form->GetIcon('address3'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('city'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('city'); ?> <?php echo $form->GetIcon('city'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('country'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('country'); ?> <?php echo $form->GetIcon('country'); ?></td>
                      </tr>
                      <tr>
                        <td><?php echo $form->GetLabel('region'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('region'); ?> <?php echo $form->GetIcon('region'); ?></td>
                      </tr>

                    </table>
                    <br />
                    </div>

			        <input name="continue" type="submit" class="btn" id="continue" value="continue" />

				<?php echo $form->Close(); ?>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>