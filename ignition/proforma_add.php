<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProForma.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$session->Secure(3);

$proForma = new ProForma($_REQUEST['proformaid']);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('proformaid', 'Order ID', 'hidden', $proForma->ID, 'numeric_unsigned', 1, 11);
$form->AddField('quantity', 'Quantity', 'text', '1', 'numeric_unsigned', 1, 9);
$form->AddField('product', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('name', 'Product', 'text', '', 'paragraph', 1, 60, false, 'onFocus="this.Blur();"');

if($action == 'add' && isset($_REQUEST['confirm'])){
	if($form->Validate()){
		$proForma->AddLine($form->GetValue('quantity'), $form->GetValue('product'));
	}

	if($form->Valid){
		redirect("Location: proforma_details.php?proformaid=". $proForma->ID);
	}
}

$page = new Page(sprintf('Add Product to Pro Forma Ref: %s%s', $proForma->Prefix, $proForma->ID),'Use the search box to add a product to this pro forma.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

$window = new StandardWindow("Add a Product.");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('proformaid');
echo $form->GetHTML('product');
echo $window->Open();
echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
echo $window->OpenContent();
echo $webForm->Open();
$temp_1 = '<a href="javascript:popUrl(\'product_search.php?serve=pop\', 500, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
echo $webForm->AddRow($form->GetLabel('name') . $temp_1, $form->GetHTML('name') . '<input type="submit" name="add" value="add" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');
?>
