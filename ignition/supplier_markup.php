<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'getsupplier'){
    $session->Secure(2);
    getsupplier();
    exit;
} else {
	$session->Secure(2);
	view();
	exit;
}
/*
///////////////////////////////////////////
Function:	remove()
Author:		Geoff Willings
Date:		08 Feb 2005
///////////////////////////////////////////
*/
function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierMarkup.php');
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$supplierMarkup = new SupplierMarkup;
		$supplierMarkup->ID = $_REQUEST['id'];
		$supplierMarkup->Remove();
    }
    redirect("Location: supplier_markup.php");
    exit;
}
/*
///////////////////////////////////////////
Function:	add()
Author:		Geoff Willings
Date:		08 Feb 2005
///////////////////////////////////////////
*/
function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierMarkup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form("supplier_markup.php");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('supplierID', 'Supplier ID', 'text', '', 'number', 1, 6);
	$form->AddField('value', 'Markup Value', 'text', '', 'float', 1, 4);

	// Check if the form has been submitted

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$supplierMarkup = new SupplierMarkup;
			$supplierMarkup->SupplierID = $form->GetValue('supplierID');
			$supplierMarkup->Value = $form->GetValue('value');
			$supplierMarkup->Add();
			redirect("Location: supplier_markup.php");
			exit;
		}
	}

	$page = new Page('Add a New Markup','Please complete the form below.');
	$page->Display('header');

	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Markup');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
    $searchIcon =  '<a href="javascript:popUrl(\'supplier_markup.php?action=getsupplier\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('supplierID') . $searchIcon, $form->GetHTML('supplierID') . $form->GetIcon('supplierID'));
	echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $form->GetIcon('value'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'supplier_markup.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
    require_once('lib/common/app_footer.php');
}
/*
///////////////////////////////////////////
Function:	update()
Author:		Geoff Willings
Date:		08 Feb 2005
///////////////////////////////////////////
*/
function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierMarkup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

    $supplierMarkup = new SupplierMarkup($_REQUEST['id']);
	$form = new Form("supplier_markup.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('supplierID', 'Supplier ID', 'text', $supplierMarkup->SupplierID, 'number', 1, 6);
    $form->AddField('id', 'id', 'hidden', $_REQUEST['id'], 'number', 0, 4);
	$form->AddField('value', 'Markup Value', 'text', $supplierMarkup->Value, 'float', 1, 4);

	// Check if the form has been submitted

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$supplierMarkup = new SupplierMarkup($_REQUEST['id']);
			$supplierMarkup->SupplierID = $form->GetValue('supplierID');
			$supplierMarkup->Value = $form->GetValue('value');
			$supplierMarkup->Update();
			redirect("Location: supplier_markup.php");
			exit;
		}
	}

	$page = new Page('Add a New Markup','Please complete the form below.');
	$page->Display('header');

	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Markup');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
    $searchIcon =  '<a href="javascript:popUrl(\'supplier_markup.php?action=getsupplier\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('supplierID') . $searchIcon, $form->GetHTML('supplierID') . $form->GetIcon('supplierID'));
	echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $form->GetIcon('value'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'supplier_markup.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
    require_once('lib/common/app_footer.php');
}
/*
///////////////////////////////////////////
Function:	view()
Author:		Geoff Willings
Date:		08 Feb 2005
///////////////////////////////////////////
*/
function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Markup Settings','This area allows you to add markup for suppliers. With this set, a customer can purchase an item not yet in your product database. On purchasing the product, ignition will automatically add it with the sales price set to the trade price multiplied to the percentage markup specified here. Your customer will then be sold the product at that price.');
	$page->Display('header');

	$table = new DataTable('markup');
    $sql = "SELECT sm.*, o.Org_Name FROM supplier_markup AS sm
            INNER JOIN supplier as s ON s.Supplier_ID = sm.Supplier_ID
            INNER JOIN contact as c ON s.Contact_ID = c.Contact_ID
            INNER JOIN organisation as o ON c.Org_ID = o.Org_ID";
	$table->SetSQL($sql);
	$table->AddField('ID#', 'Markup_ID', 'right');
	$table->AddField('Supplier', 'Org_Name', 'left');
	$table->AddField('Markup', 'Markup_Value', 'right');
	$table->AddLink("supplier_markup.php?action=update&id=%s",
					"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",
					"Markup_ID");
	$table->AddLink("javascript:confirmRequest('supplier_markup.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this Markup?');",
					"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
					"Markup_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Org_Name");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add a new markup" class="btn" onclick="window.location.href=\'supplier_markup.php?action=add\'">';
	$page->Display('footer');
require_once('lib/common/app_footer.php');
}

function getsupplier(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierMarkup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form("supplier_markup.php");
	$form->AddField('action', 'Action', 'hidden', 'getsupplier', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('supplier', 'Supplier\'s Name', 'text', '', 'alpha', 1, 255);

	$page = new Page('Find Supplier','Find the correct supplier by typing its name in the search box below.');
	$page->Display('header');
	$window = new StandardWindow('Find Supplier');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier') . $form->GetIcon('supplier'));
	echo $webForm->AddRow($form->GetLabel('search') . $temp_1, '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";
	echo "<br>";

    if($_REQUEST['confirm'] == true){
        $supplierName = $_REQUEST['supplier'];
        $supplier = new DataQuery('SELECT c.Contact_ID, c.Parent_Contact_ID
                                   FROM supplier AS s
                                   INNER JOIN contact AS c
                                   ON s.Contact_ID = c.Contact_ID
                                   WHERE Org_ID = 0');
        if($supplier->TotalRows > 0){
            while($supplier->Row){
                if($supplier->Row['Parent_Contact_ID'] > 0){
                    $parent = new DataQuery(sprintf('SELECT Org_ID FROM contact
                                                    WHERE Contact_ID = %d',
                                                    mysql_real_escape_string($supplier->Row['Parent_Contact_ID'])));
                    if($parent->Row['Org_ID'] > 0){
                        $update
                            = new DataQuery(sprintf('UPDATE contact SET Org_ID = %d
                                                     WHERE Contact_ID = %d',
                                                     mysql_real_escape_string($parent->Row['Org_ID']),
                                                     mysql_real_escape_string($supplier->Row['Contact_ID'])));
                    }
                    $parent->Disconnect();
                }
                $supplier->Next();
            }
        }
        $supplier->Disconnect();
        $table = new DataTable('markup');
        $sql = "SELECT s.*, o.Org_Name FROM supplier AS s
                INNER JOIN contact as c ON s.Contact_ID = c.Contact_ID
                INNER JOIN organisation as o ON c.Org_ID = o.Org_ID
                WHERE o.Org_Name LIKE '%$supplierName%'";
        $table->SetSQL($sql);
        $table->AddField('ID#', 'Markup_ID', 'right');
        $table->AddField('Supplier', 'Org_Name', 'left');
        $table->AddLink("javascript:popFind('supplierID', '%s')",
                        "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",
                        "Supplier_ID");
        $table->SetMaxRows(25);
        $table->SetOrderBy("Org_Name");
        $table->Finalise();
        $table->DisplayTable();
        echo "<br>";
        $table->DisplayNavigation();
        echo "<br>";
    }
	$page->Display('footer');
require_once('lib/common/app_footer.php');
}