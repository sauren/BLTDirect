<?php
ini_set('max_execution_time', '900');

require_once('lib/common/app_header.php');

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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['cid'])){
		$product = new Product();
		$product->DeleteFromCategoryById($_REQUEST['cid']);
	}

	redirect(sprintf("Location: product_in_categories.php?pid=%d", $_REQUEST['pid']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Product In Categories', $_REQUEST['pid']),'This product is associated with the following categories.');

	$page->Display('header');
	$sql = sprintf("SELECT pc.Products_In_Categories_ID, c.Category_ID, c.Category_Title
						FROM product_in_categories as pc
						LEFT join product_categories as c
						on pc.Category_ID=c.Category_ID
						where pc.Product_ID=%d", $_REQUEST['pid']);
	$table = new DataTable("com");
	$table->SetSQL($sql);
	$table->AddField('Title', 'Category_Title', 'left');
	$table->AddLink("product_list.php?cat=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open and View Products for this Category\" border=\"0\">", "Category_ID");
	$table->AddLink("javascript:confirmRequest('product_in_categories.php?action=remove&confirm=true&cid=%s','Are you sure you want to remove this product from the selected category? Note: this operation removes the relationship only.');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Products_In_Categories_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Category_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add product to a category" class="btn" onclick="window.location.href=\'product_in_categories.php?action=add&pid=%d\'">', $_REQUEST['pid']);
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Category', 'hidden', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$product = new Product($form->GetValue('pid'));
			$product->AddToCategory($form->GetValue('parent'));
			redirect(sprintf("Location: product_in_categories.php?pid=%d", $form->GetValue('pid')));
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_in_categories.php?pid=%s">Product In Categories</a> &gt; Add Product to a Category', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Add a Product to a Category.");
	$webForm = new StandardForm;
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category. Please do not add products to the _root.');
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
?>