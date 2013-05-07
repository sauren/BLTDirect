<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierCategory.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
} elseif($action == 'add'){
	$session->Secure(2);
	add();
} else{
	$session->Secure(2);
	view();
}

function remove() {
	if(isset($_REQUEST['scid'])){
		$supplier = new SupplierCategory($_REQUEST['scid']);
		$supplier->Delete();
	}

	redirect(sprintf("Location: supplier_categories.php?sid=%d&cid=%d", $_REQUEST['sid'], $_REQUEST['cid']));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('sid', 'Supplier ID', 'hidden', $_REQUEST['sid'], 'numeric_unsigned', 1, 11);
	$form->AddField('cid', 'Contact ID', 'hidden', $_REQUEST['cid'], 'numeric_unsigned', 1, 11);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			$supplier = new SupplierCategory();
			$supplier->SupplierID = $form->GetValue('sid');
			$supplier->CategoryID = $form->GetValue('parent');
			$supplier->Add();

			redirect(sprintf("Location: supplier_categories.php?sid=%d&cid=%d", $form->GetValue('sid'), $form->GetValue('cid')));
		}
	}

	$name = new DataQuery(sprintf('SELECT p.Name_First,p.Name_Last FROM contact c INNER JOIN person p ON c.Person_ID = p.Person_ID WHERE c.Contact_ID = %d', mysql_real_escape_string($_REQUEST['cid'])));

	$page = new Page(sprintf("<a href=contact_profile.php?cid=%d>%s %s</a> &gt Supplier Categories",$_REQUEST['cid'], $name->Row['Name_First'],$name->Row['Name_Last']), "View the settings of this supplier.");
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Add a category to this supplier');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('sid');
	echo $form->GetHTML('cid');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'supplier_categories.php?sid=%d&cid=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_REQUEST['sid'], $_REQUEST['cid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function view() {
	$name = new DataQuery(sprintf('SELECT p.Name_First,p.Name_Last FROM contact c INNER JOIN person p ON c.Person_ID = p.Person_ID WHERE c.Contact_ID = %d',mysql_real_escape_string($_REQUEST['cid'])));

	$page = new Page(sprintf("<a href=contact_profile.php?cid=%d>%s %s</a> &gt Supplier Categories",$_REQUEST['cid'], $name->Row['Name_First'],$name->Row['Name_Last']), "Edit the list of categories that this supplier may compare prices for.");
	$page->Display('header');

	$table = new DataTable("com");
	$table->SetSQL(sprintf("SELECT sc.Supplier_Category_ID, pc.Category_Title FROM supplier_categories AS sc INNER JOIN product_categories AS pc ON pc.Category_ID=sc.Category_ID WHERE sc.Supplier_ID=%d", mysql_real_escape_string($_REQUEST['sid'])));
	$table->AddField('ID','Supplier_Category_ID','right');
	$table->AddField('Category','Category_Title');
	$table->AddLink("javascript:confirmRequest('supplier_categories.php?action=remove&cid=".$_REQUEST['cid']."&sid=".$_REQUEST['sid']."&scid=%s','Are you sure you want to remove this category?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove this category\" border=\"0\">","Supplier_Category_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Category_Title');
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$cats = '';
	$count = 0;

	$data = new DataQuery(sprintf("SELECT Category_ID FROM supplier_categories WHERE Supplier_ID=%d", mysql_real_escape_string($_REQUEST['sid'])));
	while($data->Row) {
		$cats .= ' AND Category_ID<>'.$data->Row['Category_ID'];
		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT Category_ID, Category_Title FROM product_categories WHERE Category_Parent_ID=1%s", mysql_real_escape_string($cats)));
	while($data->Row) {
		$count++;
		$data->Next();
	}
	$data->Disconnect();

	if($count > 0) {
		echo '<br /><input type="button" type="submit" value="add a new category" class = "btn" onclick="window.location.href=\'supplier_categories.php?action=add&sid='.$_REQUEST['sid'].'&cid='.$_REQUEST['cid'].'\';" />';
	}

	$page->Display('footer');
}

require_once('lib/common/app_footer.php');
?>