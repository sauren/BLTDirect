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
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductOffers.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['oid'])){
		$data = new ProductOffer();
		$data->Delete($_REQUEST['oid']);

	}
	redirect(sprintf("Location: product_offer.php?pid=%d", $_REQUEST['pid']));
	exit;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Product Offers', $_REQUEST['pid']),'You can add current and future offers to each product. Keeping a history of offers is sometimes useful.');

	$page->Display('header');
	$sql = sprintf("SELECT *
						FROM product_offers
						where Product_ID=%d", $_REQUEST['pid']);
	$table = new DataTable("com");
	$table->SetSQL($sql);
	$table->AddField('Price Offer', 'Price_Offer', 'right');
	$table->AddField('Inc. Tax', 'Is_Tax_Included', 'center');
	$table->AddField('Starts', 'Offer_Start_On', 'left');
	$table->AddField('Ends', 'Offer_End_On', 'left');
	$table->AddLink("javascript:confirmRequest('product_offer.php?action=remove&confirm=true&oid=%s','Are you sure you want to remove this product offer?');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Product_Offer_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Offer_End_On");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new product offer" class="btn" onclick="window.location.href=\'product_offer.php?action=add&pid=%d\'">', $_REQUEST['pid']);
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

	$form->AddField('price', 'Our Price', 'text', '', 'float', 1, 11);
	$form->AddField('tax', 'Inclusive of Tax?', 'checkbox', 'N', 'boolean', 1, 1, false);
	$year = cDatetime(getDatetime(), 'y');
	$form->AddField('start', 'Offer Starts On', 'datetime', '0000-00-00 00:00:00', 'datetime', $year-1, $year+10);
	$form->AddField('end', 'Offer Ends On', 'datetime', '0000-00-00 00:00:00', 'datetime', $year-1, $year+10);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$data = new ProductOffer();
			$data->ProductID = $form->GetValue('pid');
			$data->priceOffer = $form->GetValue('price');
			$data->isTaxIncluded = $form->GetValue('tax');
			$data->offerStart = $form->GetValue('start');
			$data->offerEnd = $form->GetValue('end');
			$data->Add();
			redirect(sprintf("Location: product_offer.php?pid=%d", $form->GetValue('pid')));
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_offer.php?pid=%s">Product Offers</a> &gt; Add Product Offer', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Add a Product Offer.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $window->Open();
	echo $window->AddHeader('Do not enter a currency symbol in the price fields. All prices should be entered in your default currency.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('price'), $form->GetHTML('price') . $form->GetIcon('price'));
	echo $webForm->AddRow($form->GetLabel('tax'), $form->GetHTML('tax') . $form->GetIcon('tax'));
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start') . $form->GetIcon('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end') . $form->GetIcon('end'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_offer.php?pid=%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>