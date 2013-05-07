<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

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
	if(isset($_REQUEST['pid'])){
		$category = new ProductCart();
		$category->ID = $_REQUEST['pid'];
		$category->Delete();
	}

	redirect(sprintf("Location: product_cart.php"));
}

function view() {
	$page = new Page("Products in Last Minute Shopping",'');
	$page->Display('header');

	$table = new DataTable('prod');
	$table->SetSQL("SELECT pc.*, p.Product_Title, p.SKU FROM product_cart AS pc INNER JOIN product AS p ON p.Product_ID=pc.Product_ID");
	$table->AddField('ID#', 'Product_ID', 'right');
	$table->AddField('SKU', 'SKU', 'left');
	$table->AddField('Product Title', 'Product_Title', 'left');
	$table->AddLink("javascript:confirmRequest('product_cart.php?action=remove&confirm=true&pid=%s','Are you sure you want to remove this product from the last minute shopping? Note: you will NOT lose any product information by performing this operation.');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">","Product_Cart_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";

	echo '<input type="button" class="btn" value="add product" onclick="window.self.location.href=\'product_cart.php?action=add\'" />';

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function add(){
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha_numeric', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('product', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Product', 'text', '', 'paragraph', 1, 2048, false, 'onFocus="this.Blur();"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$cp = new ProductCart();
			$cp->ProductID = $form->GetValue('product');
			$cp->Add();

			redirect(sprintf("Location: product_cart.php"));
		}
	}

	$page = new Page('<a href="product_cart.php">Products in Last Minute Shopping</a> &gt; Add Product', 'Add another product to last minute shopping.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add a product.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('product');
	echo $window->Open();
	echo $window->AddHeader('Click the Magnifying Glass to search for a product from your catalogue.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_search.php?serve=pop\', 700, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('name') . $temp_1, $form->GetHTML('name') . '<input type="submit" name="add" value="add" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>