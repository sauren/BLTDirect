<?php
require_once('lib/common/app_header.php');

if(!isset($_REQUEST['coupon'])){
	redirect("Location: discount_coupons.php");
}
	
if($action == "add_product"){
	$session->Secure(3);
	addProduct();
	exit;
} elseif($action == "add_category"){
	$session->Secure(3);
	addCategory();
	exit;
} elseif($action == "remove_product"){
	$session->Secure(3);
	removeProduct();
	exit;
} elseif($action == "remove_category"){
	$session->Secure(3);
	removeCategory();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
	
	$coupon = new Coupon($_REQUEST['coupon']);
	
	$page = new Page(sprintf('<a href="discount_coupons.php">%s</a> &gt; Discount Coupon Settings', $coupon->Name), sprintf('Edit Discount Coupon Settings for Coupon Reference %s.', $coupon->Reference));
	$page->Display('header');
	
	if(strtoupper($coupon->IsAllProducts) == 'Y'){
		echo sprintf('This Coupon Applies to All Products in your Catalogue. If you would like to change this setting please <a href="discount_coupons.php?action=update&coupon=%d">click here</a>', $coupon->ID);
	} else {
		$table = new DataTable('products');
		$table->SetSQL(sprintf("select p.Product_ID, p.Product_Title from coupon_product as cp inner join product as p on cp.Product_ID=p.Product_ID where cp.Coupon_ID=%d", $coupon->ID));
		$table->AddField('ID#', 'Product_ID', 'right');
		$table->AddField('Name', 'Product_Title', 'left');
		$table->AddLink("javascript:confirmRequest('discount_coupon_settings.php?action=remove_product&confirm=true&pid=%s','Are you sure you want to remove this product from this coupon? IMPORTANT: removing this product may effect any active promotions.');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",  "Product_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Product_ID");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		
		echo "<br>";
		echo sprintf('<input type="button" name="addProduct" value="add product to coupon" class="btn" onclick="window.location.href=\'discount_coupon_settings.php?action=add_product&coupon=%d\'"> ', $coupon->ID);
		echo sprintf('<input type="button" name="addCategory" value="add category to coupon" class="btn" onclick="window.location.href=\'discount_coupon_settings.php?action=add_category&coupon=%d\'"> ', $coupon->ID);
		echo sprintf('<input type="button" name="removeCategory" value="remove category from coupon" class="btn" onclick="window.location.href=\'discount_coupon_settings.php?action=remove_category&coupon=%d\'"> ', $coupon->ID);
	}
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addProduct(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CouponProduct.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
	
	$coupon = new Coupon($_REQUEST['coupon']);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add_product', 'alpha_numeric', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('coupon', 'Coupon ID', 'hidden', $_REQUEST['coupon'], 'numeric_unsigned', 1, 11);
	$form->AddField('product', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Product', 'text', '', 'paragraph', 1, 60, false, 'onFocus="this.Blur();"');
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$cp = new CouponProduct;
			$cp->CouponID = $form->GetValue('coupon');
			$cp->ProductID = $form->GetValue('product');
			$cp->Add();
			
			redirect(sprintf("Location: discount_coupon_settings.php?coupon=%d", $form->GetValue('coupon')));
		}
	}
	
	$page = new Page(sprintf('<a href="discount_coupons.php">%s</a> &gt; <a href="discount_coupon_settings.php?coupon=%d">Discount Coupon Settings</a> &gt; Add Product to Coupon', $coupon->Name, $coupon->ID), sprintf('Add another Product to Coupon Reference %s.', $coupon->Reference));
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow("Add a Coupon Product.");
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('coupon');
	echo $form->GetHTML('product');
	echo $window->Open();
	echo $window->AddHeader('Click the Magnifying Glass to search for a product from your catalogue.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_search.php?serve=pop\', 500, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('name') . $temp_1, $form->GetHTML('name') . '<input type="submit" name="add" value="add" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addCategory(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
	
	$coupon = new Coupon($_REQUEST['coupon']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add_category', 'alpha_numeric', 1, 20);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('coupon', 'Coupon ID', 'hidden', $_REQUEST['coupon'], 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'Y', 'boolean', NULL, NULL, false);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$coupon->AddCategory($form->GetValue('parent'), ($form->GetValue('subfolders') == 'Y') ? true : false);
			
			redirect(sprintf("Location: discount_coupon_settings.php?coupon=%d", $form->GetValue('coupon')));
		}
	}
	
	$page = new Page(sprintf('<a href="discount_coupons.php">%s</a> &gt; <a href="discount_coupon_settings.php?coupon=%d">Discount Coupon Settings</a> &gt; Add Category of Products to Coupon', $coupon->Name, $coupon->ID), sprintf('Add an entire Category of Products to Coupon Reference %s.', $coupon->Reference));
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Add a Category of Products to Coupon.");
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('coupon');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent'), '<span id="parentCaption">_root</span>&nbsp;' . $temp_1);
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('', '<input type="submit" name="add" value="add" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function removeProduct(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
	
	$coupon = new Coupon;
	$coupon->ID = $_REQUEST['coupon'];
	
	if(isset($_REQUEST['confirm'])){
		$sql = sprintf("delete from coupon_product where Coupon_ID=%d and Product_ID=%d", mysql_real_escape_string($coupon->ID), mysql_real_escape_string($_REQUEST['pid']));
		$data = new DataQuery($sql);
	}
	
	redirect("Location: discount_coupon_settings.php?coupon=" . $coupon->ID);
}

function removeCategory(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
	
	$coupon = new Coupon($_REQUEST['coupon']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'remove_category', 'alpha_numeric', 1, 20);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('coupon', 'Coupon ID', 'hidden', $_REQUEST['coupon'], 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', '', 'numeric_unsigned', 1, 11);
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$coupon->DeleteCategory($form->GetValue('parent'));
			 redirect(sprintf("Location: discount_coupon_settings.php?coupon=%d", $form->GetValue('coupon')));
		}
	}
	
	$page = new Page(sprintf('<a href="discount_coupons.php">%s</a> &gt; <a href="discount_coupon_settings.php?coupon=%d">Discount Coupon Settings</a> &gt; Remove Category of Products from Coupon', $coupon->Name, $coupon->ID), sprintf('Remove an entire Category of Products from Coupon Reference %s.', $coupon->Reference));
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Remove a Category of Products from Coupon.");
	$webForm = new StandardForm;
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('coupon');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption"></span>&nbsp; &nbsp;<input type="submit" name="remove" value="remove" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}