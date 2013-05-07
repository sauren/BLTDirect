<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

if($action == 'cancel'){
	$session->Secure(3);
	cancel();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function cancel() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');

	if(isset($_REQUEST['pid'])) {
		$purchase = new Purchase($_REQUEST['pid']);
		$purchase->Cancel();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	if(isset($_REQUEST['status'])) {
		if(strlen($_REQUEST['status']) == 0) {
			$_REQUEST['status'] = 'U';
		}
	}

	$page = new Page("Purchases","Here you can view purchase orders made by your particular branch");
	$page->Display('header');

	$form = new Form($_SERVER['PHP_SELF'],'GET');

	$form->AddField('status','Filter by status','select','U','alpha_numeric',0,40,false);
	$form->AddOption('status','N','No filter');
	$form->AddOption('status','F','View Fulfilled orders only');
	$form->AddOption('status','U','View Unfulfilled orders only');

	$window = new StandardWindow('Filter orders');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('status'),$form->GetHTML('status').'<input type="submit" name="search" value="Search" class="btn">');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	echo '<br />';

	if($form->GetValue('status')=='N') {
		$sql = sprintf("SELECT * FROM purchase p INNER JOIN users u ON u.Branch_ID = p.For_Branch WHERE p.Purchase_Status NOT LIKE 'Irrelevant' AND p.Purchase_Status NOT LIKE 'Cancelled' AND u.User_ID=%d ",$GLOBALS['SESSION_USER_ID']);
	} elseif($form->GetValue('status')=='F') {
		$sql = sprintf("SELECT * FROM purchase p INNER JOIN users u ON u.Branch_ID = p.For_Branch WHERE p.Purchase_Status NOT LIKE 'Irrelevant' AND p.Purchase_Status NOT LIKE 'Cancelled' AND u.User_ID=%d AND p.Purchase_Status LIKE 'Fulfilled'",$GLOBALS['SESSION_USER_ID']);
	} elseif($form->GetValue('status')=='U') {
		$sql = sprintf("SELECT * FROM purchase p INNER JOIN users u ON u.Branch_ID = p.For_Branch WHERE p.Purchase_Status NOT LIKE 'Irrelevant' AND p.Purchase_Status NOT LIKE 'Cancelled' AND u.User_ID=%d AND (p.Purchase_Status LIKE 'Partially Fulfilled' OR p.Purchase_Status LIKE 'Unfulfilled')",$GLOBALS['SESSION_USER_ID']);
	}

	$table = new DataTable("com");
	$table->SetSQL($sql);
	$table->AddField('ID#','Purchase_ID');
	$table->AddField('Date Ordered','Purchased_On');
	$table->AddField('Type','Type');
	$table->AddField('Organisation','Supplier_Organisation_Name');
	$table->AddField('First Name','Supplier_First_Name');
	$table->AddField('Last Name','Supplier_Last_Name');
	$table->AddField('Status','Purchase_Status');
	$table->AddField('Custom Reference', 'Custom_Reference_Number');
	$table->AddField('Notes','Order_Note');
	$table->AddLink('purchase_edit.php?pid=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Update the purchase settings\" border=\"0\">",'Purchase_ID');
	$table->AddLink("javascript:confirmRequest('purchases_view.php?action=cancel&pid=%s','Are you sure you want to cancel this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Cancel\" border=\"0\">","Purchase_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Purchased_On');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
}