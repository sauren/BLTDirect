<?php
require_once('lib/common/app_header.php');

if($action == "add") {
	$session->Secure(3);
	add();
} elseif($action == "update") {
	$session->Secure(3);
	update();
} elseif($action == "remove") {
	$session->Secure(3);
	remove();
} else {
	$session->Secure(2);
	view();
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/ControlSupplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','add','alpha',3,3);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('supplier','Supplier','select','','numeric_unsigned', 1, 11);

	$exclude = array();
	
	$data = new DataQuery(sprintf("SELECT Supplier_ID FROM control_supplier"));
	while($data->Row) {
		$exclude[] = $data->Row['Supplier_ID'];
			
		$data->Next();
	}
	$data->Disconnect();
	
	$excludeStr = (count($exclude) > 0) ? sprintf('WHERE s.Supplier_ID<>%s', implode(' AND s.Supplier_ID<>', mysql_real_escape_string($exclude))) : '';
	
	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier FROM supplier AS s INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID %s ORDER BY Supplier ASC", mysql_real_escape_string($excludeStr)));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier']);
		
		$data->Next();
	}
	$data->Disconnect();
	
	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == "true")) {
		if($form->Validate()) {
			$supplier = new ControlSupplier();
			$supplier->SupplierID = $form->GetValue('supplier');
			$supplier->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Control Supplier Settings</a> &gt; Add Supplier', $_SERVER['PHP_SELF']), 'Here you can add a new supplier to this control form.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the supplier information');
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'),$form->GetHTML('supplier').$form->GetIcon('supplier'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';" />&nbsp;<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/ControlSupplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));	
	}
	
	$supplier = new ControlSupplier();
	
	if(!$supplier->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','update','alpha',6,6);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('id','','hidden',$supplier->ID,'numeric_unsigned',1,11);
	$form->AddField('supplier','Supplier','select', $supplier->SupplierID, 'numeric_unsigned', 1, 11);

	$exclude = array();
	
	$data = new DataQuery(sprintf("SELECT Supplier_ID FROM control_supplier"));
	while($data->Row) {
		if($data->Row['Supplier_ID'] != $supplier->SupplierID) {
			$exclude[] = $data->Row['Supplier_ID'];
		}
			
		$data->Next();
	}
	$data->Disconnect();
	
	$excludeStr = (count($exclude) > 0) ? sprintf('WHERE s.Supplier_ID<>%s', implode(' AND s.Supplier_ID<>', $exclude)) : '';
	
	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier FROM supplier AS s INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID %s ORDER BY Supplier ASC", mysql_real_escape_string($excludeStr)));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier']);
		
		$data->Next();
	}
	$data->Disconnect();
	
	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == "true")) {
		if($form->Validate()) {
			$supplier->SupplierID = $form->GetValue('supplier');
			$supplier->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Control Supplier Settings</a> &gt; Update Supplier', $_SERVER['PHP_SELF']), 'Here you can edit a supplier for this control form.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the supplier information');
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'),$form->GetHTML('supplier').$form->GetIcon('supplier'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';" />&nbsp;<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/ControlSupplier.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$supplier = new ControlSupplier();
		$supplier->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

	$page = new Page("Control Supplier Settings", "Manage settings for the supplier control form here.");
	$page->Display('header');

	$table = new DataTable("com");
	$table->SetSQL("SELECT s.Supplier_ID, cs.Control_Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier FROM control_supplier AS cs INNER JOIN supplier AS s ON s.Supplier_ID=cs.Supplier_ID INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID");
	$table->AddField('ID','Supplier_ID','right');
	$table->AddField('Supplier','Supplier');
	$table->AddLink("control_supplier_settings.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Control_Supplier_ID");
	$table->AddLink("javascript:confirmRequest('control_supplier_settings.php?action=remove&id=%s','Are you sure you want to remove this supplier?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Control_Supplier_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo "<br />";
	$table->DisplayNavigation();

	echo '<br /><input name="add" type="submit" value="add a new supplier" class="btn" onclick="window.location.href=\'./control_supplier_settings.php?action=add\'">';

	$page->Display('footer');
}

require_once('lib/common/app_footer.php');
?>