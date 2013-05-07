<?php
/*
product_price.php
Version 1.0

Ignition, eBusiness Solution
http://www.deveus.com

Copyright (c) Deveus Software, 2004
All Rights Reserved.

Notes:
*/
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBarcodes.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'disable'){
	$session->Secure(3);
	disable();
	exit;
} elseif($action == 'enable'){
	$session->Secure(3);
	enable();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['barcodeID'])){
		productBarcodes::DeleteProduct($_REQUEST['barcodeID']);
	}
	redirect(sprintf("Location: product_barcodes.php?pid=%d", $_REQUEST['pid']));
}

function enable() {
	$product = new Product($_REQUEST['pid']);
	$product->BarcodesApplicable = "Y";
	$product->Update();
	redirect(sprintf("Location: product_barcodes.php?pid=%d", $_REQUEST['pid']));
}

function disable() {
	$product = new Product($_REQUEST['pid']);
	$product->BarcodesApplicable = "N";
	$product->Update();

	productBarcodes::DeleteProductID($_REQUEST['pid']);

	redirect(sprintf("Location: product_barcodes.php?pid=%d", $_REQUEST['pid']));
}

function view(){
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Product Barcodes', $_REQUEST['pid']),'You can add current and future barcodes to each product.');

	$product = new Product($_REQUEST['pid']);

	$page->Display('header');

	if ($product->BarcodesApplicable == 'Y') {
		$sql = sprintf("select * from product_barcode where ProductID = %d", mysql_real_escape_string($_REQUEST['pid']));
		$table = new DataTable("com");
		$table->SetSQL($sql);
		$table->AddField('Brand', 'Brand', 'right');
		$table->AddField('Barcode', 'Barcode', 'left');
		$table->AddField('Quantity In Pack', 'Quantity', 'left');
		$brands = new DataQuery("select Manufacturer_ID, Manufacturer_Name from manufacturer");
		$table->AddLink('product_barcodes.php?action=update&barcode_ID=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Update this price\" border=\"0\">",'ProductBarcodeID');

		$table->AddLink("javascript:confirmRequest('product_barcodes.php?action=remove&confirm=true&barcodeID=%s','Are you sure you want to remove this product barcode? Note: you should keep at least one effective barcode.');",
		"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
		"ProductBarcodeID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Brand");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		echo sprintf('<input type="button" name="add" value="add a new product barcode" class="btn" onclick="window.location.href=\'product_barcodes.php?action=add&pid=%d\'">', $_REQUEST['pid']);
		echo "&nbsp;";
		echo sprintf('<input type="button" name="add" value="barcodes are not applicable" class="btn" onclick="window.location.href=\'product_barcodes.php?action=disable&pid=%d\'">', $_REQUEST['pid']);
	} else {
		echo sprintf('<input type="button" name="add" value="re-enable barcodes" class="btn" onclick="window.location.href=\'product_barcodes.php?action=enable&pid=%d\'">', $_REQUEST['pid']);
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$brands = new DataQuery("select Manufacturer_ID, Manufacturer_Name from manufacturer");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('brand', 'Brand', 'Select', '', 'float', 1, 11, true);
	while($brands->Row){
		$form->AddOption('brand', $brands->Row['Manufacturer_ID'], $brands->Row['Manufacturer_Name']);
		$brands->Next();
	}
	$form->AddField('barcode', 'Barcode', 'text', '', 'float', 1, 11);
	$form->AddField('quantity', 'Quantity per pack', 'text', 1, 'numeric_unsigned', 1, 9);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			if($form->GetValue('quantity') < 1) {
				$form->AddError('Quantity must be greater than 0.', 'quantity');
			}
		}

		if($form->Valid) {
			$barcode= new ProductBarcodes();
			// bellow needs doing simon
			$brand_names = new DataQuery(sprintf("select Manufacturer_Name from manufacturer where Manufacturer_ID = %d", $form->GetValue('brand')));
			$barcode->ProductID = $form->GetValue('pid');
			$barcode->Barcode = $form->GetValue('barcode');
			$barcode->Brand = $brand_names->Row['Manufacturer_Name'];
			$barcode->Quantity = $form->GetValue('quantity');
			$barcode->ManufacturerID = $form->GetValue('brand');
			$barcode->Add();

			redirect(sprintf("Location: product_barcodes.php?pid=%d", $form->GetValue('pid')));
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_barcodes.php?pid=%s">Product Barcodes</a> &gt; Add Product Barcode', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add a Product Barcode.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $window->Open();
	echo $window->AddHeader('Please fill out the fields bellow to add a barcode to the product');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('brand'), $form->GetHTML('brand') . $form->GetIcon('brand'));
	echo $webForm->AddRow($form->GetLabel('barcode'), $form->GetHTML('barcode') . $form->GetIcon('barcode'));
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_barcodes.php?pid=%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');


	$brands = new DataQuery("select Manufacturer_ID, Manufacturer_Name from manufacturer");
	$update_barcode = new DataQuery(sprintf("select * from product_barcode where ProductBarcodeID = %d", $_REQUEST['barcode_ID']));

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', '', 'hidden', 0, 'numeric_unsigned', 1, 11, false);
	$form->AddField('barcode_ID', 'barcode', 'hidden', $_REQUEST['barcode_ID'], 'numeric_unsigned', 1, 11);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('brand', 'Brand', 'Select', $update_barcode->Row['ManufacturerID'], 'float', 1, 11, true);
	while($brands->Row){
		$form->AddOption('brand', $brands->Row['Manufacturer_ID'], $brands->Row['Manufacturer_Name']);
		$brands->Next();
	}
	$form->AddField('barcode', 'Barcode', 'text', $update_barcode->Row['Barcode'], 'float', 1, 11);
	$form->AddField('quantity', 'Quantity per pack', 'text', $update_barcode->Row['Quantity'], 'numeric_unsigned', 1, 9);


	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()){
			if($form->GetValue('quantity') < 1) {
				$form->AddError('Quantity must be greater than 0.', 'quantity');
			}
		}

		if($form->Valid) {
			$updates = new productBarcodes();
			$brand_names = new DataQuery(sprintf("select Manufacturer_Name from manufacturer where Manufacturer_ID = %d", $form->GetValue('brand')));
			$updates->ID = $form->GetValue('barcode_ID');
			$updates->ProductID = $form->GetValue('pid');
			$updates->Barcode = $form->GetValue('barcode');
			$updates->Brand = $brand_names->Row['Manufacturer_Name'];
			$updates->Quantity = $form->GetValue('quantity');
			$updates->ManufacturerID = $form->GetValue('brand');
			$updates->Update();
			redirect(sprintf("Location: %s?pid=%d", $_SERVER['PHP_SELF'], $updates->ProductID));
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_barcodes.php?pid=%s">Product Barcodes</a> &gt; Update Product Barcode', $form->GetValue('pid'), $form->GetValue('pid')),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Update Product barcode.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('price');
	echo $form->GetHTML('pid');
	echo $form->GetHTML('barcode_ID');
	echo $window->Open();
	echo $window->AddHeader('Use the fields supplied to update the barcode');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('brand'), $form->GetHTML('brand') . $form->GetIcon('brand'));
	echo $webForm->AddRow($form->GetLabel('barcode'), $form->GetHTML('barcode') . $form->GetIcon('barcode'));
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_barcodes.php?pid=%s\';"> <input type="submit" name="update" value="Update" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}