<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequestLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSearch.php');

$session->Secure(3);

$returnRequest = new SupplierReturnRequest();

if(!$returnRequest->Get($_REQUEST['requestid'])) {
	redirect(sprintf('Location: supplier_return_requests_pending.php'));
}

$isEditable = (strtolower($returnRequest->Status) == 'pending') ? true : false;

if(!$isEditable) {
	redirect(sprintf('Location: supplier_return_request_details.php?requestid=%d', $returnRequest->ID));
}

$returnRequestLine = new SupplierReturnRequestLine();

if(!$returnRequestLine->Get($_REQUEST['lineid'])) {
	redirect(sprintf('Location: supplier_return_request_details.php?requestid=%d', $returnRequest->ID));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('requestid', 'Supplier Return Request ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('lineid', 'Supplier Return Request Line ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('pid', 'Product ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('string', 'Search for...', 'text', '', 'anything', 1, 255);

if(isset($_REQUEST['confirm'])) {
	if($form->Valid) {
		if($form->GetValue('pid') > 0) {
			$returnRequestLine->RelatedProduct->ID = $form->GetValue('pid');
			$returnRequestLine->Update();

			redirect(sprintf('Location: %s?requestid=%d', $_SERVER['PHP_SELF'], $form->GetValue('requestid')));
		}
	}
}

$page = new Page(sprintf('<a href="supplier_return_request_details.php?requestid=%d">[#%d] Supplier Return Request Details</a> &gt; Related Product', $returnRequest->ID, $returnRequest->ID), 'Set the related product for the return request line.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('requestid');
echo $form->GetHTML('lineid');
echo $form->GetHTML('pid');

$window = new StandardWindow("Search for a Product.");
$webForm = new StandardForm;

echo $window->Open();
echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('string'), $form->GetHTML('string') . '<input type="submit" name="search" value="search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo '<br />';

if(isset($_REQUEST['string']) && !empty($_REQUEST['string'])) {
	$search = new ProductSearch($_REQUEST['string'], sprintf('%s?confirm=true&requestid=%d&lineid=%d&pid=', $_SERVER['PHP_SELF'], $returnRequest->ID, $returnRequestLine->ID));
	$search->PrepareSQL();

	$table = new DataTable('results');
	$table->AddField('ID#', 'Product_ID', 'left');
	$table->AddField('Title', 'Product_Title', 'left');
	$table->AddLink('', '');
	$table->SetSQL($search->Query);
	$table->OrderBy = 'score';
	$table->SetMaxRows(10);
	$table->Order = 'DESC';
	$table->Finalise();
	$table->ExecuteSQL();

	echo $table->GetTableHeader();

	while($table->Table->Row){
		$product = new Product($table->Table->Row['Product_ID']);

		echo '<tr>';

		if(!empty($product->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Thumb->FileName)) {
			echo sprintf('<td><img src="%s%s" /></td>', $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $product->DefaultImage->Thumb->FileName);
		} else {
			echo sprintf('<td></td>');
		}

		echo sprintf('<td><strong><a href="product_profile.php?pid=%s">%s</a></strong><br />Quickfind: <strong>%s</strong>, SKU: %s, Price &pound;%s (Inc. VAT)</td>', $product->ID, $product->Name, $product->ID, $product->SKU, number_format($product->PriceCurrentIncTax, 2));
		echo sprintf('<td width="1%%"><a href="%s?confirm=true&requestid=%d&lineid=%d&pid=%d"><img src="images/button-tick.gif" alt="Update Settings" border="0"></a></td>', $_SERVER['PHP_SELF'], $returnRequest->ID, $returnRequestLine->ID, $product->ID);
		echo '</tr>';

		$table->Next();
	}

	echo '</table>';
	echo '<br />';

	$table->DisplayNavigation();
}

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');