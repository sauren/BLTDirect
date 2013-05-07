<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$session->Secure(3);

$quote = new Quote($_REQUEST['quoteid']);

$product = new Product();

if(isset($_REQUEST['product'])) {
	$product->Get($_REQUEST['product']);
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('quoteid', 'Order ID', 'hidden', $quote->ID, 'numeric_unsigned', 1, 11);
$form->AddField('quantity', 'Quantity', 'text', '1', 'numeric_unsigned', 1, 9);
$form->AddField('product', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('name', 'Product', 'text', '', 'anything', null, null, false, 'onFocus="this.Blur();"');

if(isset($_REQUEST['find'])){
	if(is_numeric($_REQUEST['name'])){
		$product = new Product();
		if(($product->Get($_REQUEST['name']))){
			$form->SetValue('product', $product->ID);
			$form->SetValue('name', $product->Name);
		}
	}
} elseif(isset($_REQUEST['add'])){
	if($form->Validate()){
		$quote->AddLine($form->GetValue('quantity'), $form->GetValue('product'));
	}

	if($form->Valid){
		redirect("Location: quote_details.php?quoteid=". $quote->ID);
	}
}

$page = new Page(sprintf('Add Product to Quote Ref: %s%s', $quote->Prefix, $quote->ID),'Use the search box to add a product to this quote.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

$window = new StandardWindow("Add a Product.");
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('quoteid');
echo $form->GetHTML('product');
echo $window->Open();
echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . "<input type=\"submit\" name=\"find\" value=\"find\" class=\"btn\" />\n");

if(!empty($product->ID)){
	echo $webForm->AddRow('', '<input type="submit" name="add" value="add" class="btn" />');
}

echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();
echo "<br>";
$page->Display('footer');
require_once('lib/common/app_footer.php');

?>
