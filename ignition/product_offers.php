<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecialOffer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$offer = new ProductSpecialOffer();
		$offer->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha_numeric', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('product', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Product', 'text', '', 'paragraph', 1, 60, false, 'onfocus="this.blur();"');
	$form->AddField('offer', 'Base Offer (%)', 'text', '', 'numeric_unsigned', 1, 11);
	$form->AddField('tolerance', 'Base Offer Tolerance (%)', 'text', '', 'numeric_unsigned', 1, 11);
	$form->AddField('inactive', 'Inactive Period (Days)', 'text', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if($form->GetValue('offer') > 100) {
				$form->AddError('Base Offer (%) cannot exceed 100%', 'offer');
			}
			
			if($form->GetValue('tolerance') > 100) {
				$form->AddError('Base Offer Tolerance (%) cannot exceed 100%', 'tolerance');
			}
			
			if($form->Valid) {
				$offer = new ProductSpecialOffer();
				$offer->ProductID = $form->GetValue('product');
				$offer->BaseOfferPercent = $form->GetValue('offer');
				$offer->BaseOfferTolerance = $form->GetValue('tolerance');
				$offer->InactivePeriod = $form->GetValue('inactive');
				$offer->Add();
	
				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			}
		}
	}

	$page = new Page('<a href="product_offers.php">Special Offers</a> &gt; Add Special Offer', 'Add a product to special offers.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add a product.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('product');
	echo $window->Open();
	echo $window->AddHeader('Click the magnifying glass to search for a product from your catalogue.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name') . '<a href="javascript:popUrl(\'product_search.php?serve=pop\', 700, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', $form->GetHTML('name'));
	echo $webForm->AddRow($form->GetLabel('offer'), $form->GetHTML('offer'));
	echo $webForm->AddRow($form->GetLabel('tolerance'), $form->GetHTML('tolerance'));
	echo $webForm->AddRow($form->GetLabel('inactive'), $form->GetHTML('inactive'));
	echo $webForm->AddRow('', '<input type="submit" name="add" value="add" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function update() {
	$offer = new ProductSpecialOffer($_REQUEST['id']);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha_numeric', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Special Offer ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('offer', 'Base Offer (%)', 'text', $offer->BaseOfferPercent, 'numeric_unsigned', 1, 11);
	$form->AddField('tolerance', 'Base Offer Tolerance (%)', 'text', $offer->BaseOfferTolerance, 'numeric_unsigned', 1, 11);
	$form->AddField('inactive', 'Inactive Period (Days)', 'text', $offer->InactivePeriod, 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if($form->GetValue('offer') > 100) {
				$form->AddError('Base Offer (%) cannot exceed 100%', 'offer');
			}
			
			if($form->GetValue('tolerance') > 100) {
				$form->AddError('Base Offer Tolerance (%) cannot exceed 100%', 'tolerance');
			}
			
			if($form->Valid) {
				$offer->BaseOfferPercent = $form->GetValue('offer');
				$offer->BaseOfferTolerance = $form->GetValue('tolerance');
				$offer->InactivePeriod = $form->GetValue('inactive');
				$offer->Update();
	
				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			}
		}
	}

	$page = new Page('<a href="product_offers.php">Special Offers</a> &gt; Update Special Offer', 'Update an existing special offer.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update a product.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Edit the fields for this special offer.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('offer'), $form->GetHTML('offer'));
	echo $webForm->AddRow($form->GetLabel('tolerance'), $form->GetHTML('tolerance'));
	echo $webForm->AddRow($form->GetLabel('inactive'), $form->GetHTML('inactive'));
	echo $webForm->AddRow('', '<input type="submit" name="update" value="update" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Special Offers', '');
	$page->Display('header');

	$table = new DataTable('offers');
	$table->SetSQL("SELECT pso.*, p.Product_Title, p.SKU FROM product_special_offer AS pso INNER JOIN product AS p ON p.Product_ID=pso.Product_ID");
	$table->AddField('ID#', 'Product_ID', 'right');
	$table->AddField('SKU', 'SKU', 'left');
	$table->AddField('Product Title', 'Product_Title', 'left');
	$table->AddField('Base Offer (%)', 'Base_Offer_Percent', 'right');
	$table->AddField('Base Offer Tolerance (%)', 'Base_Offer_Tolerance', 'right');
	$table->AddField('Inactive Period (Days)', 'Inactive_Period', 'right');
	$table->AddLink("product_offers.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Product_Offer_ID");
	$table->AddLink("javascript:confirmRequest('product_offers.php?action=remove&id=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Product_Offer_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" class="btn" value="add product" onclick="window.self.location.href=\'product_offers.php?action=add\'" />';

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>