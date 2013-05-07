<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(3);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'discontinue', 'alpha', 11, 11);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()){
		// Hurrah! Create a new entry.
		$product = new Product($form->GetValue('pid'));
		$product->Discontinued = 'N';
		$finalString = stristr($product->Description,'</p>');
		if($finalString != false){
			$product->Description = substr($finalString,4);
		}
		$product->DiscontinuedBecause = "";
		$product->DiscontinuedOn = "0000-00-00 00:00:00";
		$product->DiscontinuedBy = 0;
		$product->Update();
		redirect(sprintf("Location: product_profile.php?pid=%d", $form->GetValue('pid')));
		exit;
	}
}

$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Discontinue', $_REQUEST['pid']),'The more information you supply the better your system will become.');

$page->Display('header');
// Show Error Report if Form Object validation fails
if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}
$window = new StandardWindow("Discontinue Product.");
$webForm = new StandardForm;
echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('action');
echo $form->GetHTML('pid');
echo $window->Open();
echo $window->AddHeader('All fields are required on this form');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow("&nbsp;","Click below to recommission an item, By recommissioning a product, all data concerning its recomissioning is removed and the product becomes available for sale again");
echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_profile.php?pid=%s\';"> <input type="submit" name="recontinue" value="recontinue" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();
echo "<br>";
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>