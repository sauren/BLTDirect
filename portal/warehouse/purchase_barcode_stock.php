<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

if($action == 'step2') {
	$session->Secure();
	step2();
	exit;
} else {
	$session->Secure();
	step1();
	exit;
}
	
function step1() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'step1', 'alpha_numeric', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('barcode', 'Product Barcode', 'text', '', 'paragraph', 1, 120);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p INNER JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID WHERE pb.Barcode='%s'", mysql_real_escape_string($form->GetValue('barcode'))));
			if($data->TotalRows == 0) {
				$form->AddError('Barcode could not be found.', 'barcode');
			} else {
				$data2 = new DataQuery(sprintf("SELECT pu.Purchase_ID, pul.Purchase_Line_ID FROM purchase AS pu INNER JOIN purchase_line AS pul ON pul.Purchase_ID=pu.Purchase_ID AND pu.For_Branch>0 AND pu.Purchase_Status IN ('Partially Fulfilled', 'Unfulfilled') INNER JOIN product AS p ON p.Product_ID=pul.Product_ID INNER JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID WHERE pb.Barcode='%s' GROUP BY pu.Purchase_ID", mysql_real_escape_string($form->GetValue('barcode'))));
				if($data2->TotalRows <> 1) {
					redirectTo('?action=step2&barcode=' . $form->GetValue('barcode'));
				} else {
					redirectTo(sprintf('purchase_edit.php?pid=%1$d&lineid=%2$d#line-%2$d', $data2->Row['Purchase_ID'], $data2->Row['Purchase_Line_ID']));
				}
				$data2->Disconnect();
			}
			$data->Disconnect();
		}
	}
	
	$page = new Page('Purchase Barcode Stock Insert (Step 1)', 'Search for a purchased product barcode.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Search for a product barcode');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Search for barcode.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('barcode'), $form->GetHTML('barcode'));
	echo $webForm->AddRow('', '<input type="submit" name="continue" value="continue" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function step2() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'step2', 'alpha_numeric', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('barcode', 'Product Barcode', 'hidden', '', 'paragraph', 1, 120);
	$form->AddField('purchase', 'Purchase', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('purchase', '', '');
	
	$purchases = array();
	
	$data = new DataQuery(sprintf("SELECT pu.Purchase_ID, pu.Supplier_Organisation_Name, pu.Purchased_On, pul.Purchase_Line_ID FROM purchase AS pu INNER JOIN purchase_line AS pul ON pul.Purchase_ID=pu.Purchase_ID AND pu.For_Branch>0 AND pu.Purchase_Status IN ('Partially Fulfilled', 'Unfulfilled') INNER JOIN product AS p ON p.Product_ID=pul.Product_ID INNER JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID WHERE pb.Barcode='%s' GROUP BY pu.Purchase_ID ORDER BY pu.Purchase_ID ASC", mysql_real_escape_string($form->GetValue('barcode'))));
	while($data->Row) {
		$purchases[$data->Row['Purchase_ID']] = $data->Row;
	
		$data->Next();
	}
	$data->Disconnect();
	
	foreach($purchases as $purchase) {
		$form->AddOption('purchase', $purchase['Purchase_ID'], sprintf('#%d - %s (%s)', $purchase['Purchase_ID'], $purchase['Supplier_Organisation_Name'], cDatetime($purchase['Purchased_On'], 'shortdate')));
	}
				
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			redirectTo(sprintf('purchase_edit.php?pid=%1$d&line=%2$d#line-%2$d', $form->GetValue('purchase'), $purchases[$form->GetValue('purchase')]['Purchase_Line_ID']));
		}
	}

	$page = new Page('Purchase Barcode Stock Insert (Step 2)', 'Search for a purchased product barcode.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Select a purchase to insert stock into.');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('barcode');

	echo $window->Open();
	echo $window->AddHeader('Select purchase.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('purchase'), $form->GetHTML('purchase'));
	echo $webForm->AddRow('', '<input type="submit" name="continue" value="continue" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}