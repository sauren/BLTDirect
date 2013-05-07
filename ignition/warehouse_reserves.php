<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseReserve.php');

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
	if(isset($_REQUEST['rid'])) {
		$reserve = new WarehouseReserve($_REQUEST['rid']);
		$reserve->delete();

		redirect(sprintf('Location: ?pid=%d', $reserve->product->ID));
	}
	
	redirectTo('product_search.php');
}

function add() {
	$product = new Product();

	if(!isset($_REQUEST['pid']) || !$product->Get($_REQUEST['pid'])) {
		redirectTo('product_search.php');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('wid', 'Warehouse', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('wid', '', '');

	$data = new DataQuery("SELECT * FROM warehouse WHERE Type='S' ORDER BY Warehouse_Name ASC");
	while($data->Row) {
		$form->AddOption('wid', $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('quantity', 'Quantity', 'text', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$reserve = new WarehouseReserve();
			$reserve->warehouse->ID = $form->GetValue('wid');
			$reserve->product->ID = $form->GetValue('pid');
			$reserve->quantity = $form->GetValue('quantity');
			$reserve->add();

			redirectTo(sprintf('?pid=%d', $product->ID));
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%d">%s</a> &gt; <a href="?pid=%d">Warehouse Reserves</a> &gt; Add Reserve', $product->ID, $product->Name, $product->ID), 'Manage stock reserves for warehouses.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add stock reserves to warehouses.');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('pid');

	echo $window->Open();
	echo $window->CloseContent();
	echo $window->AddHeader('Please fill in the rest of the stock details');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('wid'),$form->GetHTML('wid').$form->GetIcon('wid'));
	echo $webForm->AddRow($form->GetLabel('quantity'),$form->GetHTML('quantity').$form->GetIcon('quantity'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?pid=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $product->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function update() {
	$reserve = new WarehouseReserve();

	if(!isset($_REQUEST['rid']) || !$reserve->Get($_REQUEST['rid'])) {
		redirectTo('product_search.php');
	}

	$reserve->product->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('rid', 'Reserve ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('quantity', 'Quantity', 'text', $reserve->quantity, 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$reserve->quantity = $form->GetValue('quantity');
			$reserve->update();

			redirectTo(sprintf('?pid=%d', $reserve->product->ID));
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%d">%s</a> &gt; <a href="?pid=%d">Warehouse Reserves</a> &gt; Update Reserve', $reserve->product->ID, $reserve->product->Name, $reserve->product->ID), 'Manage stock reserves for warehouses.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update stock reserves to warehouses.');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('rid');

	echo $window->Open();
	echo $window->CloseContent();
	echo $window->AddHeader('Please fill in the rest of the stock details');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?pid=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $reserve->product->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function view() {
	$product = new Product();

	if(!isset($_REQUEST['pid']) || !$product->Get($_REQUEST['pid'])) {
		redirectTo('product_search.php');
	}
	
	$page = new Page(sprintf('<a href="product_profile.php?pid=%d">%s</a> &gt; Warehouse Reserves', $product->ID, $product->Name), 'Manage stock reserves for warehouses.');
	$page->Display('header');
	
	$table = new DataTable('reserves');
	$table->SetSQL(sprintf('SELECT w.Warehouse_Name, wr.* FROM warehouse_reserve AS wr INNER JOIN warehouse AS w ON w.Warehouse_ID=wr.warehouseId WHERE wr.productId=%d', mysql_real_escape_string($product->ID)));
	$table->AddField('ID','id');
	$table->AddField('Warehouse','Warehouse_Name');
	$table->AddField('Quantity','quantity','right');
	$table->AddLink('?action=update&rid=%s', '<img src="images/icon_edit_1.gif" alt="Update" border="0" />', 'id');
	$table->AddLink('javascript:confirmRequest(\'?action=remove&rid=%s\', \'Are you sure you want to remove this item?\');', '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'id');
	$table->SetOrderBy('Warehouse_Name');
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" type="submit" value="add reserve" class="btn" onclick="window.location.href=\'?action=add&pid=%d\'" />', $product->ID);
	
	$page->Display('footer');
}