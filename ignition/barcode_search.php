<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('barcode', 'Product Barcode', 'text', '', 'numeric_unsigned', 1, 120, true);

$sqlSelect = '';
$sqlFrom = '';
$sqlWhere = '';
$sqlOther = '';

$productId = 0;
$productTitle = '';

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p INNER JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID WHERE pb.Barcode='%s'", mysql_real_escape_string($form->GetValue('barcode'))));
		if($data->TotalRows == 0) {
			$form->AddError('Barcode could not be found.', 'barcode');
		} else {
			$productId = $data->Row['Product_ID'];
			$productTitle = strip_tags($data->Row['Product_Title']);
		}
		$data->Disconnect();
		
		if($form->Valid) {
			$sqlSelect = sprintf("SELECT * ");
			$sqlFrom = sprintf("INNER JOIN product AS p ON p.Product_ID=x.Product_ID INNER JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID ");
			$sqlWhere = sprintf("WHERE pb.Barcode='%s' ", $form->GetValue('barcode'));
			$sqlOther = sprintf("GROUP BY p.Product_ID ");
		}
	}
}

$page = new Page('Barcode Search', 'Search for a product barcode.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Search for a product barcode');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Search for barcode.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('barcode'), $form->GetHTML('barcode'));

if(isset($_REQUEST['confirm'])) {
	if($form->Valid) {
		echo $webForm->AddRow('Product Name', sprintf('<a href="product_profile.php?pid=%d">%s</a>', $productId, $productTitle));
	}
}

echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

echo '<br />';

if(isset($_REQUEST['confirm'])) {
	if($form->Valid) {
		echo '<h3>Purchase Orders</h3>';
		echo sprintf('<p>Items containing the product registered with the barcode <strong>%s</strong>.</p>', $form->GetValue('barcode'));
		
		$table = new DataTable('purchases');
		$table->SetSQL(sprintf("%sFROM purchase AS pu INNER JOIN purchase_line AS x ON x.Purchase_ID=pu.Purchase_ID %s%s AND pu.For_Branch>0 AND pu.Purchase_Status IN ('Partially Fulfilled', 'Unfulfilled') %s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlOther));
		$table->SetExtractVars();
		$table->AddField('Purchase ID#','Purchase_ID');
		$table->AddField('Date Ordered','Purchased_On');
		$table->AddField('Organisation','Supplier_Organisation_Name');
		$table->AddField('First Name','Supplier_First_Name');
		$table->AddField('Last Name','Supplier_Last_Name');
		$table->AddField('Status','Purchase_Status');
		$table->AddField('Custom Reference', 'Custom_Reference_Number');
		$table->AddField('Notes','Order_Note');
		$table->AddLink('purchase_edit.php?pid=%s', '<img src="images/folderopen.gif" alt="View" border="0" />', 'Purchase_ID');
		$table->SetMaxRows(25);
		$table->SetOrderBy('Purchased_On');
		$table->Order = 'DESC';
		$table->Finalise();
		$table->DisplayTable();
		echo '<br />';
		$table->DisplayNavigation();
		
		echo '<br />';
		
		echo '<h3>Customer Returns</h3>';
		echo sprintf('<p>Items containing the product registered with the barcode <strong>%s</strong>.</p>', $form->GetValue('barcode'));
		
		$table = new DataTable('returns');
		$table->SetSQL(sprintf("%sFROM `return` AS r INNER JOIN order_line AS x ON r.Order_Line_ID=x.Order_Line_ID INNER JOIN orders AS o ON o.Order_ID=x.Order_ID INNER JOIN return_reason AS rr ON r.Reason_ID=rr.Reason_ID %s%s AND r.Status NOT IN ('Cancelled', 'Resolved') %s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlOther));
		$table->SetExtractVars();
		$table->AddField('Return_ID ID#', 'Return_ID', 'right');
		$table->AddField('Requested', 'Requested_On', 'left');
		$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
		$table->AddField('Name', 'Billing_First_Name', 'left');
		$table->AddField('Surname', 'Billing_Last_Name', 'left');
		$table->AddField('Status', 'Status', 'right');
		$table->AddField('Order Number', 'Order_ID', 'right');
		$table->AddField('Reason', 'Reason_Title');
		$table->AddLink('return_details.php?id=%s', '<img src="images/folderopen.gif" alt="View" border="0" />', 'Return_ID');
		$table->SetMaxRows(25);
		$table->SetOrderBy('Requested_On');
		$table->Order = 'DESC';
		$table->Finalise();
		$table->DisplayTable();
		echo '<br />';
		$table->DisplayNavigation();
	}
}

$page->Display('footer');
require_once('lib/common/app_footer.php');