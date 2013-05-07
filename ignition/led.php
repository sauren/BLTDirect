<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LedLocation.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LedProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LedType.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

if($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'addlocation') {
	$session->Secure(3);
	addLocation();
	exit;
} elseif($action == 'addproduct') {
	$session->Secure(3);
	addProduct();
	exit;
} elseif($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'removelocation') {
	$session->Secure(3);
	removeLocation();
	exit;
} elseif($action == 'removeproduct') {
	$session->Secure(3);
	removeProduct();
	exit;
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'updatelocation') {
	$session->Secure(3);
	updateLocation();
	exit;
} elseif($action == 'updateproduct') {
	$session->Secure(3);
	updateProduct();
	exit;
} elseif($action == 'products') {
	$session->Secure(3);
	products();
	exit;
} elseif($action == 'locations') {
	$session->Secure(3);
	locations();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$type = new LedType();
		$type->delete($_REQUEST['id']);
	}
	redirect('Location: ?action=view');
}

function removeLocation() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$location = new LedLocation($_REQUEST['id']);
		$location->delete();
		
		redirect('Location: ?action=locations&id=' . $location->typeId);		
	}

	redirect('Location: ?action=view');
}

function removeProduct() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$product = new LedProduct($_REQUEST['id']);
		$product->delete();
		
		redirect('Location: ?action=products&id=' . $product->locationId);		
	}

	redirect('Location: ?action=view');
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'paragraph', 0, 120);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$type = new LedType();
			$type->name = $form->GetValue('name');
			$type->add();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">LED Types</a> &gt; Add Type', 'Add a new type.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a new type');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('Enter type details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addLocation() {
	$type = new LedType();
	
	if(!isset($_REQUEST['id']) || !$type->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addlocation', 'alpha', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Type ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', '', 'paragraph', 0, 120);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$location = new LedLocation();
			$location->type->id = $form->GetValue('id');
			$location->name = $form->GetValue('name');
			$location->add();

			redirect('Location: ?action=locations&id=' . $type->id);
		}
	}

	$page = new Page(sprintf('<a href="?action=view">LED Types</a> &gt; <a href="?action=locations&id=%d">LED Locations</a> &gt; Add Location', $type->id), 'Add a new location.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a new location');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Enter location details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=locations&id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $type->id, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addProduct() {
	$location = new LedLocation();
	
	if(!isset($_REQUEST['id']) || !$location->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addproduct', 'alpha', 10, 10);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Type ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('productId', 'Product ID', 'text', '', 'numeric_unsigned', 1, 11);
	$form->AddField('quantity', 'Quantity', 'text', '', 'numeric_unsigned', 1, 11);
	$form->AddField('position', 'Position', 'text', '', 'paragraph', 1, 120);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$product = new LedProduct();
			$product->location->id = $form->GetValue('id');
			$product->product->ID = $form->GetValue('productId');
			$product->quantity = $form->GetValue('quantity');
			$product->position = $form->GetValue('position');
			$product->add();

			redirect('Location: ?action=products&id=' . $location->id);
		}
	}

	$page = new Page(sprintf('<a href="?action=view">LED Types</a> &gt; <a href="?action=locations&id=%d">LED Locations</a> &gt; <a href="?action=products&id=%d">LED Products</a> &gt; Add Product', $location->type->id, $location->id), 'Add a new product.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a new product');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Enter product details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('productId'), $form->GetHTML('productId') . $form->GetIcon('productId'));
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
	echo $webForm->AddRow($form->GetLabel('position'), $form->GetHTML('position') . $form->GetIcon('position'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=products&id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $location->id, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$type = new LedType();
	
	if(!isset($_REQUEST['id']) || !$type->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $type->name, 'paragraph', 0, 120);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$type->name = $form->GetValue('name');
			$type->update();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">LED Type</a> &gt; Update Type', 'Edit a type.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating an existing type');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Update type details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function updateLocation() {
	$location = new LedLocation();
	
	if(!isset($_REQUEST['id']) || !$location->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'updatelocation', 'alpha', 14, 14);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $location->name, 'paragraph', 0, 120);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$location->name = $form->GetValue('name');
			$location->update();

			redirect('Location: ?action=locations&id=' . $location->type->id);
		}
	}

	$page = new Page(sprintf('<a href="?action=view">LED Types</a> &gt; <a href="?action=locations&id=%d">LED Locations</a> &gt; Add Location', $location->type->id), 'Edit a location.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating an existing location');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Update location details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=locations&id=%d\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $location->type->id, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function updateProduct() {
	$product = new LedProduct();
	
	if(!isset($_REQUEST['id']) || !$product->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$product->location->get();
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'updateproduct', 'alpha', 13, 13);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('productId', 'Product ID', 'text', $product->product->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('quantity', 'Quantity', 'text', $product->quantity, 'numeric_unsigned', 1, 11);
	$form->AddField('position', 'Position', 'text', $product->position, 'paragraph', 1, 120);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$product->product->ID = $form->GetValue('productId');
			$product->quantity = $form->GetValue('quantity');
			$product->position = $form->GetValue('position');
			$product->update();

			redirect('Location: ?action=products&id=' . $product->location->id);
		}
	}

	$page = new Page(sprintf('<a href="?action=view">LED Types</a> &gt; <a href="?action=locations&id=%d">LED Locations</a> &gt; <a href="?action=products&id=%d">LED Products</a> &gt; Add Product', $product->location->type->id, $product->location->id), 'Edit a product.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating an existing product');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Update product details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('productId'), $form->GetHTML('productId') . $form->GetIcon('productId'));
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
	echo $webForm->AddRow($form->GetLabel('position'), $form->GetHTML('position') . $form->GetIcon('position'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=products&id=%d\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $product->location->id, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function products() {
	$location = new LedLocation();
	
	if(!isset($_REQUEST['id']) || !$location->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$page = new Page(sprintf('<a href="?action=view">LED Types</a> &gt; <a href="?action=locations&id=%d">LED Locations</a> &gt; LED Products', $location->type->id), 'Manage products.');
	$page->Display('header');

	$table = new DataTable('ledlocation');
	$table->SetSQL(sprintf("SELECT lp.*, p.Product_Title FROM led_product AS lp LEFT JOIN product AS p ON p.Product_ID=lp.productId WHERE lp.locationId=%d", $location->id));
	$table->AddField("ID#", "id");
	$table->AddField("Quantity", "quantity", "left");
	$table->AddField("Product ID", "productId", "left");
	$table->AddField("Name", "Product_Title", "left");
	$table->AddField("Position", "position", "left");
	$table->AddLink('?action=updateproduct&id=%s', '<img src="images/icon_edit_1.gif" alt="Update" border="0" />', 'id');
	$table->AddLink('javascript:confirmRequest(\'?action=removeproduct&id=%s\', \'Are you sure you wish to remove this item?\');', '<img src="images/button-cross.gif" alt="Remove" border="0" />', 'id');
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add product" class="btn" onclick="window.location.href=\'?action=addproduct&id=%d\'" /> ', $location->id);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function locations() {
	$type = new LedType();
	
	if(!isset($_REQUEST['id']) || !$type->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$page = new Page('<a href="?action=view">LED Types</a> &gt; LED Locations', 'Manage locations.');
	$page->Display('header');

	$table = new DataTable('ledlocation');
	$table->SetSQL(sprintf("SELECT * FROM led_location WHERE typeId=%d", $type->id));
	$table->AddField("ID#", "id");
	$table->AddField("Name", "name", "left");
	$table->AddLink('?action=products&id=%s', '<img src="images/folderopen.gif" alt="Products" border="0" />', 'id');
	$table->AddLink('?action=updatelocation&id=%s', '<img src="images/icon_edit_1.gif" alt="Update" border="0" />', 'id');
	$table->AddLink('javascript:confirmRequest(\'?action=removelocation&id=%s\', \'Are you sure you wish to remove this item?\');', '<img src="images/button-cross.gif" alt="Remove" border="0" />', 'id');
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add location" class="btn" onclick="window.location.href=\'?action=addlocation&id=%d\'" /> ', $type->id);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('LED Types', 'Manage LED types.');
	$page->Display('header');

	$table = new DataTable('ledtype');
	$table->SetSQL("SELECT * FROM led_type");
	$table->AddField("ID#", "id");
	$table->AddField("Name", "name", "left");
	$table->AddLink('?action=locations&id=%s', '<img src="images/folderopen.gif" alt="Locations" border="0" />', 'id');
	$table->AddLink('?action=update&id=%s', '<img src="images/icon_edit_1.gif" alt="Update" border="0" />', 'id');
	$table->AddLink('javascript:confirmRequest(\'?action=remove&id=%s\', \'Are you sure you wish to remove this item?\');', '<img src="images/button-cross.gif" alt="Remove" border="0" />', 'id');
	$table->SetMaxRows(25);
	$table->SetOrderBy("name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input type="button" name="add" value="add type" class="btn" onclick="window.location.href=\'?action=add\'" /> ';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}