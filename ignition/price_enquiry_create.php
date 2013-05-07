<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');

$session->Secure(3);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')){
	if($form->Validate()) {
		$priceEnquiry = new PriceEnquiry();
		$priceEnquiry->Status = 'Pending';
		$priceEnquiry->Add();

		redirect(sprintf("Location: price_enquiry_details.php?id=%d", $priceEnquiry->ID));
	}
}

$page = new Page('Create New Price Enquiry', 'Enter the price enquiry details.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br />";
}

echo $form->Open();
echo $form->GetHTML('confirm');

$window = new StandardWindow("New Price Enquiry");
$webForm = new StandardForm();

echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow('', '<input type="submit" name="create" value="create" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');