<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(2);

$direct = "supplier_registered.php";
if(isset($_REQUEST['direct'])) $direct = $_REQUEST['direct'];

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('direct', 'Direct', 'hidden', $direct, 'paragraph', 1, 255);
$form->AddField('account', 'Account Type', 'select', 'O', 'alpha', 1, 1);
$form->AddOption('account', 'O', 'Organisation');
$form->AddOption('account', 'I', 'Individual');

$form->AddField('title', 'Title', 'select', 'Mr', 'alpha', 1, 4);
$title = new DataQuery("select * from person_title order by Person_Title");
while($title->Row){
	$form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
	$title->Next();
}
$title->Disconnect();
unset($title);

$form->AddField('fname', 'First Name', 'text', '', 'alpha_numeric', 1, 60);
$form->AddField('iname', 'Initial', 'text', '', 'alpha', 1, 1, false, 'size="1"');
$form->AddField('lname', 'Last Name', 'text', '', 'alpha_numeric', 1, 60);

$form->AddField('email', 'Your Email Address', 'text', '', 'email', NULL, NULL);
$form->AddField('phone', 'Daytime Phone', 'text', '', 'telephone', NULL, NULL);
$form->AddField('mobile', 'Mobile Phone', 'text', '', 'telephone', NULL, NULL, false);

$form->AddField('address1', 'Property Name/Number', 'text', '', 'alpha_numeric', 1, 150);
$form->AddField('address2', 'Street', 'text', '', 'alpha_numeric', 1, 150);
$form->AddField('address3', 'Area', 'text', '', 'alpha_numeric', 1, 150, false);
$form->AddField('city', 'City', 'text', '', 'alpha_numeric', 1, 150);

// Country
$form->AddField('country', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
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

$form->AddField('postcode', 'Postcode', 'text', '', 'alpha_numeric', 1, 10);

$password = new Password(PASSWORD_LENGTH_SUPPLIER);

$form->AddField('password', 'Password', 'hidden', $password->Value, 'password', PASSWORD_LENGTH_SUPPLIER, 100);

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	$form->Validate();
	$isOrg = (strtolower($form->GetValue('account')) == 'o')? true : false;

	$supplier = new Supplier();
	$supplier->Username = $form->GetValue('email');
	$supplier->SetPassword($form->GetValue('password'));
	$supplier->Contact->Type = 'I';
	$supplier->Contact->IsSupplier = 'Y';
	$supplier->Contact->Person->Title = $form->GetValue('title');
	$supplier->Contact->Person->Name = $form->GetValue('fname');
	$supplier->Contact->Person->LastName = $form->GetValue('lname');
	$supplier->Contact->Person->Initial = $form->GetValue('iname');
	$supplier->Contact->Person->Phone1 = $form->GetValue('phone');
	$supplier->Contact->Person->Mobile = $form->GetValue('mobile');
	$supplier->Contact->Person->Email = $form->GetValue('email');
	$supplier->Contact->Person->Address->Line1 = $form->GetValue('address1');
	$supplier->Contact->Person->Address->Line2 = $form->GetValue('address2');
	$supplier->Contact->Person->Address->Line3 = $form->GetValue('address3');
	$supplier->Contact->Person->Address->City = $form->GetValue('city');
	$supplier->Contact->Person->Address->Country->ID = $form->GetValue('country');
	$supplier->Contact->Person->Address->Region->ID = $form->GetValue('region');
	$supplier->Contact->Person->Address->Zip = $form->GetValue('postcode');

	if($form->Valid){
		$supplier->Contact->Add();
		$supplier->Add();

		if($isOrg){
			redirect(sprintf("Location: supplier_registerBusiness.php?direct=%s&cid=%s", $direct, $supplier->ID));
		} else {
			redirect(sprintf("Location: %s?cid=%s", $direct, $supplier->ID));
		}
	}
}

$page = new Page('Create a New Supplier Account', 'Please complete the form below to create a new supplier account. The supplier will automatically be sent an email containing an automatically generated password.');
$page->LinkScript('js/regions.php');
$page->Display('header');

$window = new StandardWindow('Add');
?>
	<p>Required fields are marked with an asterisk (*) and must be filled to complete your registration.</p>
			<?php
			if(!$form->Valid){
				echo $form->GetError();
				echo "<br>";
			}

			echo $form->Open();
			echo $form->GetHtml('action');
			echo $form->GetHtml('confirm');
			echo $form->GetHtml('direct');
			echo $form->GetHtml('password');
			?>
<?php
echo $window->Open();
echo $window->AddHeader('Account Type');
echo $window->OpenContent();

?>
			<table width="100%" cellspacing="0" class="form">
              <tr>
                <td>Please select the type of account you would like. <br />
                  <br />
                  <label for="account">You are an </label>
                  <?php echo $form->GetHtml('account'); ?>.				</td>
              </tr>
            </table>
			<br />
<?php
echo $window->CloseContent();
echo $window->AddHeader('Your Contact Details');
echo $window->OpenContent();
?>
		<table width="100%" cellspacing="0" class="form">
							<tr>
							  <td width="28%"><?php echo $form->GetLabel('title'); ?></td>
							<td>                              <?php echo $form->GetHtml('title'); ?><?php echo $form->GetIcon('title'); ?></td>
							</tr>
							<tr>
							  <td width="28%"><?php echo $form->GetLabel('fname'); ?></td>
							<td>                              <?php echo $form->GetHtml('fname'); ?><?php echo $form->GetIcon('fname'); ?></td>
							</tr>
							<tr>
							  <td width="28%"><?php echo $form->GetLabel('iname'); ?></td>
							<td>							  <?php echo $form->GetHtml('iname'); ?><?php echo $form->GetIcon('iname'); ?></td>
							</tr>
							<tr>
							  <td width="28%"><?php echo $form->GetLabel('lname'); ?></td>
							<td>							  <?php echo $form->GetHtml('lname'); ?><?php echo $form->GetIcon('lname'); ?></td>
							</tr>
							<tr>
							  <td> <?php echo $form->GetLabel('email'); ?> </td>
							  <td> <?php echo $form->GetHtml('email'); ?> <?php echo $form->GetIcon('email'); ?></td>
						  </tr>
							<tr>
							  <td> <?php echo $form->GetLabel('phone'); ?> </td>
							  <td> <?php echo $form->GetHtml('phone'); ?> <?php echo $form->GetIcon('phone'); ?></td>
						  </tr>
							<tr>
							  <td> <?php echo $form->GetLabel('mobile'); ?> </td>
							  <td><?php echo $form->GetHtml('mobile'); ?> <?php echo $form->GetIcon('mobile'); ?></td>
						  </tr>
</table>

					<br />
<?php
echo $window->CloseContent();
echo $window->AddHeader('Your Address');
echo $window->OpenContent();
?>

					<table width="100%" cellspacing="0" class="form">
                      <tr>
                        <td colspan="5">Please complete your address below. This must be the same as your credit card billing address.</td>
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
                      <tr>
                        <td><?php echo $form->GetLabel('postcode'); ?> </td>
                        <td colspan="4"><?php echo $form->GetHtml('postcode'); ?> <?php echo $form->GetIcon('postcode'); ?></td>
                      </tr>
                    </table>
					<br />
			            <p>&nbsp;</p>
			            <p align="right">
			              <input name="Continue" type="submit" class="btn" id="Continue" value="Continue" />
		                </p>
<?php
echo $window->CloseContent();
echo $window->Close();
?>
						<?php echo $form->Close(); ?>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>