<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');

if($action == 'update') {
	$session->Secure(2);
	update($session->Supplier->ID);
	exit;
} else {
	$session->Secure(2);
	view($session->Supplier->ID);
	exit;
}

function update($supplierId) {
	$warehouseStock = new WarehouseStock();

	if(!isset($_REQUEST['sid']) || !$warehouseStock->Get($_REQUEST['sid'])) {
		redirectTo('?action=view');
	}

	$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type='S' AND Type_Reference_ID=%d", $supplierId));
	if($data->TotalRows > 0) {
		$warehouseId = $data->Row['Warehouse_ID'];
	} else {
		redirectTo('?action=view');
	}
	$data->Disconnect();

	if($warehouseStock->Warehouse->ID != $warehouseId) {
		redirectTo('?action=view');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('sid','sid','hidden', '','numeric_unsigned',0,11);	
	$form->AddField('location','Shelf Location','text', $warehouseStock->Location, 'alpha_numeric',1,45);

	$foundSup = false;

	$supFinder = new DataQuery(sprintf("SELECT * FROM supplier_product WHERE Supplier_ID = %d AND Product_ID=%d", mysql_real_escape_string($supplierId), mysql_real_escape_string($warehouseStock->Product->ID)));
	if($supFinder->TotalRows > 0){
		$supplierProd = new SupplierProduct($supFinder->Row['Supplier_Product_ID']);
		$foundSup = true;
		$form->AddField('sku','Product Part Number','text',$supplierProd->SKU,'alpha_numeric',0,32);
	}
	$supFinder->Disconnect();

	$form->AddField('stock', 'Quantity In Stock', 'text', $warehouseStock->QuantityInStock, 'numeric_unsigned',1,11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if($foundSup) {
				$supplierProd->SKU = $form->GetValue('sku');
			}

			$warehouseStock->Location = $form->GetValue('location');
			$warehouseStock->QuantityInStock = $form->GetValue('stock');

			if($form->Valid){
				if($foundSup) {
					$supplierProd->Update();
				}

				$warehouseStock->Update();

				redirect("Location: supplier_products_stocked.php");
			}
		}
	}

	$page = new Page('<a href="?action=view">Stocked Products</a> &gt; Update Product', 'Update stock details here.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update stock.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('sid');

	echo $window->Open();
	echo $window->AddHeader('Edit stock details');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('location'),$form->GetHTML('location').$form->GetIcon('location'));
	echo $webForm->AddRow($form->GetLabel('stock'),$form->GetHTML('stock').$form->GetIcon('stock'));

	if($foundSup){
		echo $webForm->AddRow($form->GetLabel('sku'),$form->GetHTML('sku').$form->GetIcon('sku'));
	}

	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'supplier_products_stocked.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();
}

function view($supplierId) {
    $page = new Page('Stocked Products', 'Listing stocked products.');
	$page->Display('header');

	$table = new DataTable('products');
	$table->SetSQL(sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU, ws.Stock_ID, ws.Shelf_Location, ws.Quantity_In_Stock FROM warehouse AS w INNER JOIN warehouse_stock AS ws ON w.Warehouse_ID=ws.Warehouse_ID INNER JOIN product p ON p.Product_ID=ws.Product_ID WHERE w.Type_Reference_ID=%d AND w.Type='S'", $supplierId));
	$table->AddField('ID', 'Product_ID');
	$table->AddField('Product','Product_Title');
	$table->AddField('SKU','SKU');
	$table->AddField('Shelf Location', 'Shelf_Location');
	$table->AddField('Quantity','Quantity_In_Stock', 'right');
	$table->AddLink('?action=update&sid=%s',"<img src=\"images/icon_edit_1.gif\" alt=\"Update \" border=\"0\">",'Stock_ID');
	$table->AddLink("../../product.php?pid=%s", "<img src=\"images/folderopen.gif\" alt=\"View Product\" border=\"0\">", "Product_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Product_Title');
	$table->Order = 'ASC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}