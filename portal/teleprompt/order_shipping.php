<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Address.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TelePrompt.php');

$session->Secure(2);

$cart = new Cart($session, true);
$cart->Calculate();
$cart->Customer->Get();
$cart->Customer->Contact->Get();
$cart->Customer->Contact->Person->Get();

if(empty($cart->Customer->ID)) {
	redirect('Location: order_checkout.php');
}
if($cart->TotalLines == 0){
	redirect("Location: order_cart.php");
}
if(!empty($cart->ShipTo) && empty($action)){
	redirect("Location: order_summary.php");
}

if($action == "remove"){
	if(isset($_REQUEST['contact']) && !empty($_REQUEST['contact']) && isset($_REQUEST['type']) && strtolower($_REQUEST['type']) == "contact"){
		$formDetail = new CustomerContact;
		$formDetail->Delete($_REQUEST['contact']);
	}
	redirect("Location: " . $_SERVER['PHP_SELF']);
} elseif($action == "edit"){
	$formDetail = new CustomerContact;
	$formDetail->Get($_REQUEST['contact']);
	$formTitle = "Edit";
	$formType = "contact";
} elseif($action == "editbilling"){
	$formDetail = new Person;
	if(empty($cart->Customer->Contact->ID)) $cart->Customer->Get();
	if(empty($cart->Customer->Contact->Person->ID)) $cart->Customer->Contact->Get();
	$formDetail = $cart->Customer->Contact->Person;
	$formTitle = "Edit Address";
	$formType = "billing";
} else {
	$formDetail = new CustomerContact;
	$formDetail->Address->Country->ID = $GLOBALS['SYSTEM_COUNTRY'];
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
$form->AddField('title', 'Title', 'select', $formDetail->Title, 'anything', 0, 20, false);
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
	$form->AddField('oname', 'Organisation Name', 'text', $formDetail->OrgName, 'anything', 1, 60, false);
}

$form->AddField('address1', 'Property Name/Number', 'text', $formDetail->Address->Line1, 'address', 1, 15, true);
$form->AddField('address2', 'Street', 'text',  $formDetail->Address->Line2, 'address', 1, 150, true);
$form->AddField('address3', 'Area', 'text',  $formDetail->Address->Line3, 'address', 1, 150, false);
$form->AddField('city', 'City', 'text',  $formDetail->Address->City, 'address', 1, 150, true);
$form->AddField('country', 'Country', 'select',  $formDetail->Address->Country->ID, 'numeric_unsigned', 1, 11, false, 'onChange="propogateRegions(\'region\', this);"');
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
	$form->AddField('region', 'Region', 'select',  $formDetail->Address->Region->ID, 'numeric_unsigned', 1, 11, false);
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

$form->AddField('postcode', 'Postcode/Zip', 'text',  $formDetail->Address->Zip, 'postcode', 1, 10, false);

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
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

		$formDetail->Customer = $cart->Customer->ID;
		if($action == "addaddress"){
			$formDetail->Add();
			$cart->ShipTo = $formDetail->ID;
		} elseif($action == "edit" || $action == "editbilling"){
			$formDetail->Update();
			$cart->ShipTo = 'billing';
		}
		$cart->Update();
		redirect("Location: " . $_SERVER['PHP_SELF']);
	}
}

$script = sprintf('<script language="javascript" type="text/javascript">
		function getAddress() {
			var country = document.getElementById(\'country\');

			if(country) {
				country.options.selectedIndex = 1;
				propogateRegions(\'region\', country);
				Address.find(document.getElementById(\'postcode\'));
			}
		}
		</script>');

$page = new Page('Create a New Order Manually', '');
$page->LinkScript('js/regions.php');
$page->AddToHead('<script language="javascript" type="text/javascript" src="js/pcAnywhere.js"></script>');
$page->AddToHead(sprintf("<script language=\"javascript\" type=\"text/javascript\">Address.account = '%s';Address.licence = '%s';Address.add('postcode', 'line1', 'address2');Address.add('postcode', 'line2', 'address3');Address.add('postcode', 'line3', null);Address.add('postcode', 'city', 'city');Address.add('postcode', 'county', 'region');</script>", $GLOBALS['POSTCODEANYWHERE_ACCOUNT'], $GLOBALS['POSTCODEANYWHERE_LICENCE']));
$page->AddToHead($script);
$page->Display('header');
?>
<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top">
    
    	<?php
	    $prompt = new TelePrompt();
		$prompt->Output('ordershippingaddress');
		
		echo $prompt->Body;
		?>

		<strong>Shipping Address</strong>
			<p>If the detail below are not those of customer's please <a href="order_register.php">click here to make a new registration</a>. Please select your shipping address from below. You may also add additional shipping addresses, which will be kept for use later.</p>
			<?php
			if($action != "edit" && $action != "editbilling"){
			?>
			<table class="checkoutSelectAddress" cellspacing="0">
			<?php
			$contacts = new DataQuery(sprintf("select * from customer_contact where Customer_ID=%d", $cart->Customer->ID));
			while ($contacts->Row){
				$cc = new CustomerContact;
				$cc->ID = $contacts->Row['Customer_Contact_ID'];
				$cc->OrgName = $contacts->Row['Org_Name'];
				$cc->Title = $contacts->Row['Name_Title'];
				$cc->Name = $contacts->Row['Name_First'];
				$cc->Initial = $contacts->Row['Name_Initial'];
				$cc->LastName = $contacts->Row['Name_Last'];
				$cc->Address->Get($contacts->Row['Address_ID']);
			?>
			<tr>
			 	<td nowrap="nowrap"><?php
			 	echo $cc->GetFullName();
			 	echo "<br />";
			 	echo $cc->Address->GetFormatted('<br />');
					?>

				</td>
				<td>
					<form action="order_summary.php" method="post">
						<input type="hidden" name="action" value="ship" />
						<input type="hidden" name="shipTo" value="<?php echo $cc->ID; ?>" />
						<input type="submit" class="btn" name="Ship to this Address" value="Ship to this Address" />
					</form>

					<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
						<input type="hidden" name="action" value="edit" />
						<input type="hidden" name="contact" value="<?php echo $cc->ID; ?>" />
						<input type="hidden" name="type" value="contact" />
						<input type="submit" class="btn" name="Edit" value="Edit" />
					</form>

					<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
						<input type="hidden" name="action" value="remove" />
						<input type="hidden" name="contact" value="<?php echo $cc->ID; ?>" />
						<input type="hidden" name="type" value="contact" />
						<input type="submit" class="btn" name="Remove" value="Remove" />
					</form>
				</td>
			 </tr>
			<?php
			$contacts->Next();
			}
			$contacts->Disconnect();
			unset($contacts);
			?>

			 <?php
			 $contact = new Contact($cart->Customer->Contact->ID);
			 $shippingCalc = new ShippingCalculator();
			 ?>
			 <tr>
			 	<td nowrap="nowrap" class="billing">
				<?php
				echo $contact->Person->GetFullName();
				if($contact->HasParent){
					echo "<br />";
					echo $contact->Parent->Organisation->Name;
				}
				echo "<br />";
				echo $contact->Person->Address->GetFormatted('<br />');
				?>
				</td>
				<td class="billing"><strong>This is your address.</strong><br />(You may not remove your address, but you can edit it.)<br/><br />
				<form action="order_summary.php" method="post">
					<input type="hidden" name="shipTo" value="billing" />
					<input type="submit" class="btn" name="Ship to this Address" value="Ship to this Address" />
				</form>

				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
					<input type="hidden" name="action" value="editBilling" />
					<input type="hidden" name="contact" value="" />
					<input type="hidden" name="type" value="billing" />
					<input type="submit" class="btn" name="Edit" value="Edit" />
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
              <?php
              if($action != "editbilling") {
              	  ?>
              	  
              	  <tr>
	                <td><?php echo $form->GetLabel('oname'); ?></td>
	              <td>
	                <?php echo $form->GetHtml('oname'); ?><?php echo $form->GetIcon('oname'); ?></td>
	              </tr>
              
              	<?php
			  }
			  ?>
              <tr>
                <td><?php echo $form->GetLabel('postcode'); ?></td>
                <td><?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?> <a href="javascript:getAddress();"><img src="../images/searchIcon.gif" border="0" align="absmiddle" /> Auto-complete address (UK residents)</a></td>
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
                <td>&nbsp;</td>
                <td><input name="<?php echo $formTitle; ?>" type="submit" class="btn" id="<?php echo $formTitle; ?>" value="<?php echo $formTitle; ?>" /></td>
              </tr>
            </table>
			<?php echo $form->Close(); ?>


	</td>
  </tr>
</table>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>
