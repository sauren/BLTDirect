<?php
require_once('../lib/common/appHeadermobile.php');
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
include("ui/nav.php");
include("ui/search.php");?>
<script src="../ignition/js/regions.php" type="text/javascript"></script>
<script type="text/javascript">
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

		Address.add('postcode', 'line1', 'address2');
		Address.add('postcode', 'line2', 'address3');
		Address.add('postcode', 'line3', null);
		Address.add('postcode', 'city', 'city');
		Address.add('postcode', 'county', 'region');
	</script>
    <div class="maincontent">
    <div class="maincontent1">
    					<?php if($action == 'edit' || $action == 'editbilling'){ ?>
						<h1>Edit Address</h1>
						<p>Please complete the form below. Required fields are marked with an asterisk.</p>
					<?php } else if(param('type') == 'billing'){ ?>
						<h1>Billing Address</h1>
						<p>Please select your billing address from below. You may also add additional billing addresses, which will be kept for use later.</p>
					<?php } else { ?>
						<h1>Shipping Address</h1>
						<p>Please select your shipping address from below. You may also add additional shipping addresses, which will be kept for use later.</p>
					<?php } ?>

					<?php if(param('status')=='update'){ ?>
						<div class="detailNotification"> 
							<font size="4px"><strong>Shipping Address Missing</strong></font>
							<br/>
							<p>You have been redirected back to this page as the shipping address seleted is incomplete or not valid. Please ammend and save the details to the correct format / required fields before proceeding with the order.</p>
						</div>
						<br/>
					<?php } ?>

					<?php if($action != "edit" && $action != 'editbilling'){ ?>
						<table class="checkoutSelectAddress" cellspacing="0" width="100%">
						<?php
							$cart->Customer->GetContacts();
							for($i=0; $i<count($cart->Customer->Contacts->Line); $i++){
						?>
							<tr>
								<td nowrap="nowrap" width="100%">
									<?php
										echo $cart->Customer->Contacts->Line[$i]->GetFullName();
										echo "<br />";
										echo $cart->Customer->Contacts->Line[$i]->Address->GetFormatted('<br />');
									?>
								</td>
								<td>
									<form action="summary.php" method="post">
										<input type="hidden" name="action" value="ship" />
										<input type="hidden" name="shipTo" value="<?php echo $cart->Customer->Contacts->Line[$i]->ID; ?>" />
										<input type="submit" class="submit" name="Select this Address" value="Select this Address" />
									</form>

									<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
										<input type="hidden" name="action" value="edit" />
										<input type="hidden" name="contact" value="<?php echo $cart->Customer->Contacts->Line[$i]->ID; ?>" />
										<input type="hidden" name="type" value="contact" />
										<input type="submit" class="greySubmit" name="Edit" value="Edit" />
									</form>

									<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
										<input type="hidden" name="action" value="remove" />
										<input type="hidden" name="contact" value="<?php echo $cart->Customer->Contacts->Line[$i]->ID; ?>" />
										<input type="hidden" name="type" value="contact" />
										<input type="submit" class="greySubmit" name="Remove" value="Remove" />
									</form>
								</td>
							</tr>
						<?php
							}
							$shippingCalc = new ShippingCalculator();
					 	?>
							<tr>
								<td nowrap="nowrap" class="billing">
									<?php
										if(empty($cart->Customer->Contact->ID)) $cart->Customer->Get();
										if(empty($cart->Customer->Contact->Person->ID)) $cart->Customer->Contact->Get();
										echo $cart->Customer->Contact->Person->GetFullName();
										if($cart->Customer->Contact->HasParent){
											echo "<br />";
											echo $cart->Customer->Contact->Parent->Organisation->Name;
										}
										echo "<br />";
										echo $cart->Customer->Contact->Person->Address->GetFormatted('<br />');
									?>
								</td>
								<td class="billing">
									<strong>This is your billing address.</strong><br />
									(You may not remove this address, but you can edit it.)<br/>
									<br />
									<form action="summary.php" method="post">
										<input type="hidden" name="shipTo" value="billing" />
										<input type="submit" class="submit" name="Select this Address" value="Select this Address" />
									</form>

									<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
										<input type="hidden" name="action" value="editBilling" />
										<input type="hidden" name="contact" value="" />
										<input type="hidden" name="type" value="<?php echo param('type', 'billing'); ?>" />
										<input type="submit" class="greySubmit" name="Edit" value="Edit" />
									</form>
								</td>
							</tr>
						</table>
						<br />
					<?php
					}

					if(!$form->Valid){
						echo $form->GetError();
						echo "<br>";
					}
					
					echo $form->Open();
					echo $form->GetHtml('action');
					echo $form->GetHtml('confirm');
					echo $form->GetHtml('contact');
					echo $form->GetHtml('type');
					?>
					<table width="100%" cellspacing="0" class="form">
						<tr>
							<th colspan="2"><?php echo $formTitle; ?> Address</th>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('title'); ?></td>
                            </tr><tr>
							<td><?php echo $form->GetHtml('title'); ?><?php echo $form->GetIcon('title'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('fname'); ?></td>
                            </tr><tr>
							<td><?php echo $form->GetHtml('fname'); ?><?php echo $form->GetIcon('fname'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('lname'); ?></td>
                            </tr><tr>
							<td><?php echo $form->GetHtml('lname'); ?><?php echo $form->GetIcon('lname'); ?></td>
						</tr>
						<?php if($action != "editbilling") { ?>
							<tr>
								<td><?php echo $form->GetLabel('oname'); ?></td>
                                </tr><tr>
								<td><?php echo $form->GetHtml('oname'); ?><?php echo $form->GetIcon('oname'); ?></td>
							</tr>
						<?php } ?>
						<tr>
							<td colspan="2">&nbsp;</td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('postcode'); ?> </td>
                            </tr><tr>
							<td>
								<?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?>
								<a href="javascript:getAddress();"><img src="images/searchIcon.gif" border="0" align="absmiddle" />
								Auto-complete address (UK residents)</a>
							</td>
						</tr>
						<tr>
							<td width="28%"><?php echo $form->GetLabel('address1'); ?> </td>
                            </tr><tr>
							<td width="72%"><?php echo $form->GetHtml('address1'); ?> <?php echo $form->GetIcon('address1'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('address2'); ?> </td>
                            </tr><tr>
							<td><?php echo $form->GetHtml('address2'); ?> <?php echo $form->GetIcon('address2'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('address3'); ?> </td>
                            </tr><tr>
							<td><?php echo $form->GetHtml('address3'); ?> <?php echo $form->GetIcon('address3'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('city'); ?> </td>
                            </tr><tr>
							<td><?php echo $form->GetHtml('city'); ?> <?php echo $form->GetIcon('city'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('country'); ?> </td>
                            </tr><tr>
							<td><?php echo $form->GetHtml('country'); ?> <?php echo $form->GetIcon('country'); ?></td>
						</tr>
						<tr>
							<td><?php echo $form->GetLabel('region'); ?> </td>
                            </tr><tr>
							<td><?php echo $form->GetHtml('region'); ?> <?php echo $form->GetIcon('region'); ?></td>
						</tr>
						<tr>
							<td><input name="<?php echo $formTitle; ?>" type="submit" class="submit" id="<?php echo $formTitle; ?>" value="<?php echo $formTitle; ?>" /></td>
						</tr>
					</table>
					<?php echo $form->Close(); ?>
    </div>
    </div>
    <?php include("ui/footer.php");?>
<?php require_once('../lib/common/appFooter.php');