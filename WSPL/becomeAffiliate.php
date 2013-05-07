<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');

if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])){
	$url = ($GLOBALS['USE_SSL'])?$GLOBALS['HTTPS_SERVER']:$GLOBALS['HTTP_SERVER'];
	$self = substr($_SERVER['PHP_SELF'], 1);
	$qs = '';
	if(!empty($_SERVER['QUERY_STRING'])){
		$qs = '?' . $_SERVER['QUERY_STRING'];
	}
	redirect(sprintf("Location: %s%s%s", $url, $self, $qs));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'send', 'alpha', 4, 4);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('title', 'Title', 'select', '', 'anything', 0, 20, false);
$form->AddOption('title', '', '');
$form->AddField('fname', 'First Name', 'text', '', 'anything', 1, 60, false);
$form->AddField('lname', 'Last Name', 'text', '', 'anything', 1, 60, false);
$form->AddField('email', 'Email Address', 'text', '', 'email', NULL, NULL);
$form->AddField('phone', 'Phone', 'text', '', 'telephone', NULL, NULL);
$form->AddField('type', 'Category', 'select', '', 'numeric_unsigned', 1, 11);
$form->AddOption('type', '', '');
$form->AddField('subject', 'Subject', 'hidden', 'I am interested in becoming an affiliate of BLT Direct.', 'anything', 1, 255);
$form->AddField('message', 'Message', 'textarea', '', 'paragraph', 1, 16284, true, 'style="width:90%; height:150px;"');
$form->AddField('bname', 'Business Name', 'text', '', 'anything', 1, 255, false);

$title = new DataQuery("SELECT * FROM person_title ORDER BY Person_Title ASC");
while($title->Row){
	$form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
	$title->Next();
}
$title->Disconnect();

$data = new DataQuery(sprintf("SELECT * FROM enquiry_type WHERE Is_Public='Y' ORDER BY Name ASC"));
while($data->Row) {
	$form->AddOption('type', $data->Row['Enquiry_Type_ID'], $data->Row['Name']);

	$data->Next();
}
$data->Disconnect();

if(strtolower(param('confirm')) == "true"){
	if($form->Validate()){
		$customer = new Customer();
		$customerFound = false;
		
		$emailAddress = trim(strtolower($form->GetValue('email')));

		$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Username LIKE '%s'", mysql_real_escape_string($emailAddress)));
		if($data->TotalRows > 0) {
			if($customer->Get($data->Row['Customer_ID'])) {
				$customerFound = true;
			}
		}
		$data->Disconnect();

		if(!$customerFound) {
			$customer->Username = trim(strtolower($form->GetValue('email')));
			$customer->Contact->Type = 'I';
			$customer->Contact->IsCustomer = 'Y';
			$customer->Contact->OnMailingList = 'H';
			$customer->Contact->Person->Title = $form->GetValue('title');
			$customer->Contact->Person->Name = $form->GetValue('fname');
			$customer->Contact->Person->LastName = $form->GetValue('lname');
			$customer->Contact->Person->Phone1 = $form->GetValue('phone');
			$customer->Contact->Person->Email  = $form->GetValue('email');
			$customer->Contact->Add();
			$customer->Add();

			if(strlen(trim($form->GetValue('bname'))) > 0) {
				$contact = new Contact();
				$contact->Type = 'O';
				$contact->Organisation->Name = $form->GetValue('bname');
				$contact->Organisation->Type->ID = 0;
				$contact->Organisation->Email = $customer->GetEmail();
				$contact->Add();

				$customer->Contact->Parent->ID = $contact->ID;
				$customer->Contact->Update();
			}

			$session->Customer->ID = $customer->ID;
			$session->Update();

			$direct = '?newaccount=true';
		}

		$enquiry = new Enquiry();
		$enquiry->Type->ID = $form->GetValue('type');
		$enquiry->Customer->ID = $customer->ID;
		$enquiry->Status = 'Unread';
		$enquiry->Subject = $form->GetValue('subject');
		$enquiry->Add();

		$enquiryLine = new EnquiryLine();
		$enquiryLine->Enquiry->ID = $enquiry->ID;
		$enquiryLine->IsCustomerMessage = 'Y';
		$enquiryLine->Message = $form->GetValue('message');
		$enquiryLine->Add();

		redirect(sprintf("Location: thanks.php%s", $direct));
	}
}
?>
<?php
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Become An Affiliate</span></div>
<div class="maincontent">
<div class="maincontent1">
<!--<p class="breadCrumb"><a href="index.php">Home</a></p>-->
<p>Why not put one of our banners on your website and earn revenue from people who click through to us and place orders. If you are interested fill in your contact details below and a member of staff will contact you with further information.</p>
              <p>Please provide a description of the site you wish to place our banner on.</p>

			  <?php
			  if(!$form->Valid){
			  	echo $form->GetError();
			  	echo "<br>";
			  }
			  echo $form->Open();
			  echo $form->GetHtml('action');
			  echo $form->GetHtml('confirm');
			  echo $form->GetHtml('subject');
			?>

			  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="bluebox">
                <tr>
                  <td><h3 class="blue">Contact Us Form </h3>
                  <p class="blue">Please complete the fields below. Required fields are marked with an asterisk (*).</p>

				  <table border="0" cellspacing="0" cellpadding="5">
					<tr>
					  <td>Title<?php echo $form->GetIcon('title'); ?><br /><?php echo $form->GetHTML('title'); ?></td>
                      </tr>
                      <tr>
					  <td>First Name<?php echo $form->GetIcon('fname'); ?> <br /><?php echo $form->GetHTML('fname'); ?></td>
                      </tr>
                      <tr>
					  <td>Last Name<?php echo $form->GetIcon('lname'); ?> <br /><?php echo $form->GetHTML('lname'); ?></td>
					</tr>
				  </table>
				  <br />
				  <p>Business Name<?php echo $form->GetIcon('bname'); ?><br /><?php echo $form->GetHTML('bname'); ?></p>
				  <p>Email Address<?php echo $form->GetIcon('email'); ?><br /><?php echo $form->GetHTML('email'); ?></p>
				  <p>Phone<?php echo $form->GetIcon('phone'); ?><br /><?php echo $form->GetHTML('phone'); ?></p>
				  <p>Category<?php echo $form->GetIcon('type'); ?><br /><?php echo $form->GetHTML('type'); ?></p>

				  <p>Your Website Description<?php echo $form->GetIcon('message'); ?><br /><?php echo $form->GetHTML('message'); ?></p>
				  <p><input name="Send" type="submit" class="submit" id="Send" value="Send" /></p>


				  </td>
                </tr>
              </table>

			  <?php echo $form->Close(); ?>
              <br />

			  <h3>Contact Us Directly</h3>
              <p>If you prefer to contact us directly by phone, fax or post please find our details below: </p>
              <p>BLT Direct,<br />Unit 9, The Quadrangle, <br />The Drift, Nacton Road,<br />Ipswich, Suffolk IP3 9QR</p>
              <ul>
                <li><strong>Telephone</strong><br /><?php echo Setting::GetValue('telephone_sales_hotline'); ?> (Sales)<br /><?php echo  Setting::GetValue('telephone_customer_services'); ?> (Customer Service)<br />&nbsp;</li>
                <li><strong>Fax</strong><br />01473 718 128</li>
              </ul>
              </div>
              </div>
              <?php include("ui/footer.php")?>
              <?php include('../lib/common/appFooter.php'); ?>