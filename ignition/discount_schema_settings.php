<?php
require_once('lib/common/app_header.php');

if(!isset($_REQUEST['schema'])){
	redirect("Location: discount_schemas.php");
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');

	$discount = new DiscountSchema($_REQUEST['schema']);


	$page = new Page(sprintf('<a href="discount_schemas.php">%s</a> &gt; Discount Schema Settings', $discount->Name), sprintf('Edit Discount Schema Settings for Schema Reference %s.', $discount->Reference));
	$page->Display('header');

	if(strtoupper($discount->IsAllProducts) == 'Y'){
		echo sprintf('This Schema Applies to All Products in your Catalogue. If you would like to change this setting please <a href="discount_schemas.php?action=update&schema=%d">click here</a>', $discount->ID);
	} elseif($discount->IsAllProducts == 'B') {
		echo sprintf('This Schema Applies to a specific product band. If you would like to change this setting, or find out which band, please <a href="discount_schemas.php?action=update&schema=%d">click here</a>', $discount->ID);
	} else {

		$table = new DataTable('products');
		$table->SetSQL(sprintf("select DISTINCT(p.Product_ID), p.Product_Title from discount_product as cp inner join product as p on cp.Product_ID=p.Product_ID where cp.Discount_Schema_ID=%d", $discount->ID));
		$table->AddField('ID#', 'Product_ID', 'right');
		$table->AddField('Name', 'Product_Title', 'left');

		$table->AddLink("javascript:confirmRequest('discount_schema_settings.php?action=remove_product&confirm=true&pid=%s','Are you sure you want to remove this product from this schema? IMPORTANT: removing this product will affect customers with this discount schema.');",
		"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
		"Product_ID");

		$table->SetMaxRows(25);
		$table->SetOrderBy("Product_ID");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		echo "<br>";
		echo sprintf('<input type="button" name="addProduct" value="add product to schema" class="btn" onclick="window.location.href=\'discount_schema_settings.php?action=add_product&schema=%d\'"> ', $discount->ID);
		echo sprintf('<input type="button" name="addCategory" value="add category to schema" class="btn" onclick="window.location.href=\'discount_schema_settings.php?action=add_category&schema=%d\'"> ', $discount->ID);
		echo sprintf('<input type="button" name="removeCategory" value="remove category from schema" class="btn" onclick="window.location.href=\'discount_schema_settings.php?action=remove_category&schema=%d\'"> ', $discount->ID);

	}
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addProduct(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountProduct.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');
	$discount = new DiscountSchema($_REQUEST['schema']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add_product', 'alpha_numeric', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('schema', 'Discount Schema ID', 'hidden', $discount->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('product', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Product', 'text', '', 'paragraph', 1, 60, false, 'onFocus="this.Blur();"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$cp = new DiscountProduct;
			$cp->DiscountID = $form->GetValue('schema');
			$cp->ProductID = $form->GetValue('product');
			$cp->Add();

			redirect(sprintf("Location: discount_schema_settings.php?schema=%d", $form->GetValue('schema')));
			exit;
		}
	}
	$page = new Page(sprintf('<a href="discount_schemas.php">%s</a> &gt; <a href="discount_schema_settings.php?schema=%d">Discount Schema Settings</a> &gt; Add Product to Schema', $discount->Name, $discount->ID), sprintf('Add another Product to Schema Reference %s.', $discount->Reference));

	$page->Display('header');
	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Add a Schema Product.");
	$webForm = new StandardForm;
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('schema');
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
	echo "<br>";
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addCategory(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');
	$discount = new DiscountSchema($_REQUEST['schema']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add_category', 'alpha_numeric', 1, 20);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('schema', 'Discount Schema ID', 'hidden', $discount->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$discount->AddCategory($form->GetValue('parent'));
			redirect(sprintf("Location: discount_schema_settings.php?schema=%d", $form->GetValue('schema')));
			exit;
		}
	}
	$page = new Page(sprintf('<a href="discount_schemas.php">%s</a> &gt; <a href="discount_schema_settings.php?schema=%d">Discount Schema Settings</a> &gt; Add Category of Products to Schema', $discount->Name, $discount->ID), sprintf('Add an entire Category of Products to Schema Reference %s.', $discount->Reference));

	$page->Display('header');
	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Add a Category of Products to Schema.");
	$webForm = new StandardForm;
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('schema');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption"></span>&nbsp; &nbsp;<input type="submit" name="add" value="add" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function removeProduct(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');

	$discount = new DiscountSchema;
	$discount->ID = $_REQUEST['schema'];

	if(isset($_REQUEST['confirm'])){
		$sql = sprintf("delete from discount_product where Discount_Schema_ID=%d and Product_ID=%d", mysql_real_escape_string($discount->ID), mysql_real_escape_string($_REQUEST['pid']));
		$data = new DataQuery($sql);
	}
	redirect("Location: discount_schema_settings.php?schema=" . $discount->ID);
}

function removeCategory(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');
	$discount = new DiscountSchema($_REQUEST['schema']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'remove_category', 'alpha_numeric', 1, 20);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('schema', 'Discount Schema ID', 'hidden', $discount->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$discount->DeleteCategory($form->GetValue('parent'));
			redirect(sprintf("Location: discount_schema_settings.php?schema=%d", $form->GetValue('schema')));
			exit;
		}
	}
	$page = new Page(sprintf('<a href="discount_schemas.php">%s</a> &gt; <a href="discount_schema_settings.php?schema=%d">Discount Schema Settings</a> &gt; Remove Category of Products from Schema', $discount->Name, $discount->ID), sprintf('Remove an entire Category of Products from Schema Reference %s.', $discount->Reference));

	$page->Display('header');
	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Remove a Category of Products from Schema.");
	$webForm = new StandardForm;
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('schema');
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
	echo "<br>";
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>