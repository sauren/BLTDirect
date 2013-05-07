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
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductPrice.php');

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
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['pid'])){
		$data = new DataQuery(sprintf("delete from product_prices where Product_Price_ID=%d", mysql_real_escape_string($_REQUEST['price'])));
	}

	redirect(sprintf("Location: product_price.php?pid=%d", $_REQUEST['pid']));
}

function view(){
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Product Prices', $_REQUEST['pid']),'You can add current and future prices to each product. Keeping a history of prices is sometimes useful.');

	$page->Display('header');
	$sql = sprintf("SELECT *
						FROM product_prices
						where Product_ID=%d", mysql_real_escape_string($_REQUEST['pid']));
	$table = new DataTable("com");
	$table->SetSQL($sql);
	$table->AddField('RRP Price', 'Price_Base_RRP', 'right');
	$table->AddField('Our Price', 'Price_Base_Our', 'right');
	$table->AddField('Quantity', 'Quantity', 'center');
	$table->AddField('Inc. Tax', 'Is_Tax_Included', 'center');
	$table->AddField('Date Effective', 'Price_Starts_On', 'left');
	$table->AddLink('product_price.php?action=update&price=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Update this price\" border=\"0\">",'Product_Price_ID');

	$table->AddLink("javascript:confirmRequest('product_price.php?action=remove&confirm=true&price=%s','Are you sure you want to remove this product price? Note: you should keep at least one effective price.');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Product_Price_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Price_Starts_On");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new product price" class="btn" onclick="window.location.href=\'product_price.php?action=add&pid=%d\'">', $_REQUEST['pid']);
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('rrp', 'RRP Price', 'text', '', 'float', 1, 11, false);
	$form->AddField('price', 'Our Price', 'text', '', 'float', 1, 11);
	$form->AddField('quantity', 'Quantity', 'text', 1, 'numeric_unsigned', 1, 9);
	$form->AddField('tax', 'Inclusive of Tax?', 'checkbox', 'N', 'boolean', 1, 1, false);
	$year = cDatetime(getDatetime(), 'y');
	$form->AddField('start', 'Date Effective', 'datetime', '0000-00-00 00:00:00', 'datetime', $year-10, $year+10);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			if($form->GetValue('quantity') < 1) {
				$form->AddError('Quantity must be greater than 0.', 'quantity');
			}
		}

		if($form->Valid) {
			$price = new ProductPrice();
			$price->ProductID = $form->GetValue('pid');
			$price->PriceOurs = $form->GetValue('price');
			$price->PriceRRP = $form->GetValue('rrp');
			$price->IsTaxIncluded = $form->GetValue('tax');
			$price->PriceStartsOn = $form->GetValue('start');
			$price->Quantity = $form->GetValue('quantity');
			$price->Add();

			redirect(sprintf("Location: product_price.php?pid=%d", $form->GetValue('pid')));
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_price.php?pid=%s">Product Prices</a> &gt; Add Product Price', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add a Product Price.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $window->Open();
	echo $window->AddHeader('Do not enter a currency symbol in the price fields. All prices should be entered in your default currency.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('rrp'), $form->GetHTML('rrp') . $form->GetIcon('rrp'));
	echo $webForm->AddRow($form->GetLabel('price'), $form->GetHTML('price') . $form->GetIcon('price'));
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
	echo $webForm->AddRow($form->GetLabel('tax'), $form->GetHTML('tax') . $form->GetIcon('tax'));
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start') . $form->GetIcon('start'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_price.php?pid=%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
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

	$price = new ProductPrice($_REQUEST['price']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', '', 'hidden', 0, 'numeric_unsigned', 1, 11, false);
	$form->AddField('price', '', 'hidden', $price->ID, 'numeric_unsigned', 1, 11, false);
	$form->AddField('rrp', 'RRP Price', 'text', $price->PriceRRP, 'float', 1, 11, false);
	$form->AddField('pricing', 'Our Price', 'text', $price->PriceOurs, 'float', 1, 11);
	$form->AddField('quantity', 'Quantity', 'text', $price->Quantity, 'numeric_unsigned', 1, 9);
	$form->AddField('tax', 'Inclusive of Tax?', 'checkbox', $price->IsTaxIncluded, 'boolean', 1, 1, false);
	$year = cDatetime(getDatetime(), 'y');
	$form->AddField('start', 'Date Effective', 'datetime', $price->PriceStartsOn, 'datetime', $year-10, $year+10);


	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()){
			if($form->GetValue('quantity') < 1) {
				$form->AddError('Quantity must be greater than 0.', 'quantity');
			}
		}

		if($form->Valid) {
			$price->PriceOurs = $form->GetValue('pricing');
			$price->PriceRRP = $form->GetValue('rrp');
			$price->IsTaxIncluded = $form->GetValue('tax');
			$price->PriceStartsOn = $form->GetValue('start');
			$price->Quantity = $form->GetValue('quantity');
			$price->Update();

			redirect(sprintf("Location: %s?pid=%d", $_SERVER['PHP_SELF'], $price->ProductID));
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_price.php?pid=%s">Product Prices</a> &gt; Add Product Price', $form->GetValue('pid'), $form->GetValue('pid')),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Update Product Price.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('price');
	echo $form->GetHTML('pid');
	echo $window->Open();
	echo $window->AddHeader('Do not enter a currency symbol in the price fields. All prices should be entered in your default currency.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('rrp'), $form->GetHTML('rrp') . $form->GetIcon('rrp'));
	echo $webForm->AddRow($form->GetLabel('pricing'), $form->GetHTML('pricing') . $form->GetIcon('pricing'));
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
	echo $webForm->AddRow($form->GetLabel('tax'), $form->GetHTML('tax') . $form->GetIcon('tax'));
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start') . $form->GetIcon('start'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_price.php?pid=%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetValue('pid'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}