<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$session->Secure();

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';

$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

$form->AddField('department', 'Department', 'text', $session->Customer->Contact->Person->Department, 'anything', 1, 40, false);
$form->AddField('position', 'Position', 'text', $session->Customer->Contact->Person->Position, 'anything', 1, 100, false);
$form->AddField('name', 'Business Name', 'text', $session->Customer->Contact->Parent->Organisation->Name, 'anything', 1, 100);

$form->AddField('type', 'Business Type', 'select', $session->Customer->Contact->Parent->Organisation->Type->ID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('type', '0', '');

$type = new DataQuery("select * from organisation_type order by Org_Type asc");
while($type->Row){
	$form->AddOption('type', $type->Row['Org_Type_ID'], $type->Row['Org_Type']);
	$type->Next();
}
$type->Disconnect();

$form->AddField('industry', 'Industry', 'select', $session->Customer->Contact->Parent->Organisation->Industry->ID, 'numeric_unsigned', 1, 11, false);
$form->AddOption('industry', '0', '');

$industry = new DataQuery("select * from organisation_industry order by Industry_Name asc");
while($industry->Row){
	$form->AddOption('industry', $industry->Row['Industry_ID'], $industry->Row['Industry_Name']);
	$industry->Next();
}
$industry->Disconnect();

$form->AddField('reg', 'Company Registration', 'text', $session->Customer->Contact->Parent->Organisation->CompanyNo, 'anything', 1, 50, false);

if(strtolower(param('confirm')) == "true"){
	$form->Validate();
	if($form->Valid){
		$session->Customer->Contact->Person->Department = $form->GetValue('department');
		$session->Customer->Contact->Person->Position = $form->GetValue('position');
		$session->Customer->Contact->Person->Update();

		$session->Customer->Contact->Parent->Organisation->Name = $form->GetValue('name');
		$session->Customer->Contact->Parent->Organisation->Type->ID = $form->GetValue('type');
		$session->Customer->Contact->Parent->Organisation->Industry->ID = $form->GetValue('industry');
		$session->Customer->Contact->Parent->Organisation->CompanyNo = $form->GetValue('reg');
		$session->Customer->Contact->Parent->Organisation->Update();

		redirect("Location: accountcenter.php");
	}
}
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">My Business Profile</span></div>
<div class="maincontent">
<div class="maincontent1">
              <div id="orderConfirmation">
				<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
			</div><?php if($session->Customer->Contact->HasParent){ ?>
                <?php
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
                  <th colspan="2">Your Business Profile</th>
                </tr>
                <tr>
                  <td width="50%" align="right"><?php echo $form->GetLabel('department'); ?></td>
                  <td><?php echo $form->GetHtml('department'); ?> <?php echo $form->GetIcon('department'); ?></td>
                </tr>
                <tr>
                  <td align="right"><?php echo $form->GetLabel('position'); ?></td>
                  <td><?php echo $form->GetHtml('position'); ?> <?php echo $form->GetIcon('position'); ?></td>
                </tr>
              </table>
              <br />
              <table width="100%" cellspacing="0" class="form">
                <tr>
                  <th colspan="2">Your Business Details </th>
                </tr>
                <tr>
                  <td width="50%" align="right"><?php echo $form->GetLabel('name'); ?></td>
                  <td><?php echo $form->GetHtml('name'); ?> <?php echo $form->GetIcon('name'); ?></td>
                </tr>
                <tr>
                  <td align="right"><?php echo $form->GetLabel('type'); ?></td>
                  <td><?php echo $form->GetHtml('type'); ?> <?php echo $form->GetIcon('type'); ?></td>
                </tr>
                <tr>
                  <td align="right"><?php echo $form->GetLabel('industry'); ?></td>
                  <td><?php echo $form->GetHtml('industry'); ?> <?php echo $form->GetIcon('industry'); ?></td>
                </tr>
                <tr>
                  <td align="right"><?php echo $form->GetLabel('reg'); ?></td>
                  <td><?php echo $form->GetHtml('reg'); ?> <?php echo $form->GetIcon('reg'); ?></td>
                </tr>
              </table>
              <p> <br />
                  <input name="Update" type="submit" class="submit" id="Update" value="Update" />
              </p>
              <?php
					echo $form->Close();
				} else {
					echo "<p>Sorry, you do not have a Business Account.</p>";
				}
			  ?>
			  
</div>
</div>

<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>