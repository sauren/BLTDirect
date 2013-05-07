<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

if($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
} elseif($action == "up"){
	$session->Secure(3);
	moveUp();
} elseif($action == "down"){
	$session->Secure(3);
	moveDown();
} else {
	$session->Secure(2);
	view();
	exit;
}

function moveUp(){
	$lastAttr = 0;

	$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d ORDER BY Sequence_Number ASC", mysql_real_escape_string($_REQUEST['cat'])));
	while($data->Row){
		if(($data->Row['Product_ID'] == $_REQUEST['pid']) && ($lastAttr > 0)){
			$outDat = new DataQuery(sprintf("SELECT Sequence_Number FROM product_in_categories WHERE Product_ID=%d AND Category_ID=%d", mysql_real_escape_string($lastAttr), mysql_real_escape_string($_REQUEST['cat'])));
			$other = $outDat->Row['Sequence_Number'];
			$outDat->Disconnect();

			$outDat = new DataQuery(sprintf("SELECT Sequence_Number FROM product_in_categories WHERE Product_ID=%d AND Category_ID=%d", mysql_real_escape_string($_REQUEST['pid']), mysql_real_escape_string($_REQUEST['cat'])));
			$thisOne = $outDat->Row['Sequence_Number'];
			$outDat->Disconnect();

			new DataQuery(sprintf("UPDATE product_in_categories SET Sequence_Number=%d WHERE Category_ID=%d AND Product_ID=%d", mysql_real_escape_string($other), mysql_real_escape_string($_REQUEST['cat']), mysql_real_escape_string($_REQUEST['pid'])));
			new DataQuery(sprintf("UPDATE product_in_categories SET Sequence_Number=%d WHERE Category_ID=%d AND Product_ID=%d", mysql_real_escape_string($thisOne), mysql_real_escape_string($_REQUEST['cat']), mysql_real_escape_string($lastAttr)));

			break;
		}

		$lastAttr = $data->Row['Product_ID'];
		$data->Next();
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $_REQUEST['cat']));
}

function moveDown(){
	$lastAttr = 0;

	$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d ORDER BY Sequence_Number ASC", mysql_real_escape_string($_REQUEST['cat'])));
	while($data->Row){
		if($data->Row['Product_ID'] == $_REQUEST['pid']){

			$outDat = new DataQuery(sprintf("SELECT Sequence_Number FROM product_in_categories WHERE Product_ID=%d AND Category_ID=%d", $data->Row['Product_ID'], mysql_real_escape_string($_REQUEST['cat'])));
			$thisOne = $outDat->Row['Sequence_Number'];
			$outDat->Disconnect();

			$data->Next();

			$outDat = new DataQuery(sprintf("SELECT Sequence_Number FROM product_in_categories WHERE Product_ID=%d AND Category_ID=%d", $data->Row['Product_ID'], mysql_real_escape_string($_REQUEST['cat'])));
			$other = $outDat->Row['Sequence_Number'];
			$outDat->Disconnect();

			new DataQuery(sprintf("UPDATE product_in_categories SET Sequence_Number=%d WHERE Category_ID=%d AND Product_ID=%d", mysql_real_escape_string($other), mysql_real_escape_string($_REQUEST['cat']), mysql_real_escape_string($_REQUEST['pid'])));
			new DataQuery(sprintf("UPDATE product_in_categories SET Sequence_Number=%d WHERE Category_ID=%d AND Product_ID=%d", mysql_real_escape_string($thisOne), mysql_real_escape_string($_REQUEST['cat']), mysql_real_escape_string($data->Row['Product_ID'])));

			break;
		}

		$data->Next();
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $_REQUEST['cat']));
}

function remove(){
	if(isset($_REQUEST['cat']) && isset($_REQUEST['pid'])){
		$category = new Category;
		$category->ID = $_REQUEST['cat'];
		$category->RemoveProduct($_REQUEST['pid']);
		redirect(sprintf("Location: product_list.php?cat=%d", $category->ID));
	} else {
		redirect("Location: product_list.php");
	}
}

function update(){
	redirect(sprintf("Location: product_profile.php?pid=%d", $_REQUEST['pid']));
}

function view(){
	$order = "Product_ID";

	$category = new Category();
	
	if(isset($_REQUEST['cat'])){

		$data = new DataQuery(sprintf("SELECT `Order` FROM product_categories WHERE Category_ID=%d", mysql_real_escape_string($_REQUEST['cat'])));

		if($data->TotalRows > 0)
		{
			if($data->Row['Order'] == 'sku')
			$order = "SKU";
			elseif($data->Row['Order'] == 'product_title')
			$order = "Product_Title";
			elseif($data->Row['Order'] == 'rank')
			$order = "Sequence_Number";
		}

		$data->Disconnect();

		$category->Get($_REQUEST['cat']);
		
		$catString = $category->Name;
		$sqlString = sprintf("SELECT pic.Product_ID,
								  p.SKU,
								  p.Product_Title
								  FROM product_in_categories AS pic
								  INNER JOIN product AS p
								  ON pic.Product_ID=p.Product_ID
								  WHERE pic.Category_ID=%d", mysql_real_escape_string($category->ID));
	} else {
		$catString = "All Products";
		$sqlString = sprintf("SELECT p.Product_ID, p.SKU, p.Product_Title
                                 FROM product AS p");
	}

	$page = new Page(sprintf("Products: %s", $catString),'');
	$page->Display('header');
	$table = new DataTable('prod');
	$table->SetSQL($sqlString);
	$table->AddField('Auto ID#', 'Product_ID', 'right');
	$table->AddField('SKU', 'SKU', 'left');
	$table->AddField('Product Title', 'Product_Title', 'left');

	if($order == 'Sequence_Number')
	{
		$table->AddLink("product_list.php?cat=".$category->ID."&action=up&pid=%s",
		"<img src=\"./images/aztector_3.gif\" alt=\"Move product up\" border=\"0\">",
		"Product_ID");
		$table->AddLink("product_list.php?cat=".$category->ID."&action=down&pid=%s",
		"<img src=\"./images/aztector_4.gif\" alt=\"Move product down\" border=\"0\">",
		"Product_ID");
	}

	$table->AddLink("product_list.php?action=update&pid=%s",
	"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",
	"Product_ID");
	if(isset($_REQUEST['cat'])){
		$table->AddLink("javascript:confirmRequest('product_list.php?action=remove&confirm=true&pid=%s','Are you sure you want to remove this product from this category? Note: you will NOT lose any product information by performing this operation.');",
		"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
		"Product_ID");
	}
	$table->SetMaxRows(25);
	$table->SetOrderBy($order);
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>