<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'archive'){
	$session->Secure(3);
	archive();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){
	$page = new Page('Returns Pending Action', 'Below is a list of returns and/or requests, which require further action.');
	$page->Display('header');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('archived', 'Show Archived', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('status', 'Filter by status', 'select', 'N', 'alpha', 0, 1, false);
	$form->AddOption('status','N','No Filter');
	$form->AddOption('status','P','View Returns Pending Action');
	$form->AddOption('status','W','View Returns Awaiting Arrival');
	$form->AddOption('status','D','View Refused Returns Only');
	$form->AddOption('status','R','View Recieved Returns');

	$window = new StandardWindow('Filter Returns');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status'));
	echo $webForm->AddRow($form->GetLabel('archived'), $form->GetHTML('archived'));
	echo $webForm->AddRow('','<input type="submit" name="search" value="Search" class="btn">');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	echo "<br>";

	$sqlWhere = '';

	if($form->GetValue('status') == 'N'){
		$sqlWhere .= " AND (r.Status NOT LIKE 'unread' AND r.Status NOT LIKE 'Resolved')";
	} elseif($form->GetValue('status') == 'P'){
		$sqlWhere .= " AND (r.Status LIKE 'received' OR r.Status LIKE 'pending')";
	} elseif($form->GetValue('status') == 'W'){
		$sqlWhere .= " AND r.Status LIKE 'waiting'";
	} elseif($form->GetValue('status') == 'D'){
		$sqlWhere .= " AND r.Status LIKE 'refused'";
	} elseif($form->GetValue('status') == 'D'){
		$sqlWhere .= " AND r.Status LIKE 'refused'";
	}

	$sqlWhere .= sprintf(" AND r.Is_Archived='%s'", $form->GetValue('archived'));

	$table = new DataTable("returns");
	$table->SetSQL(sprintf("SELECT r.*, o.Order_ID, o.Billing_Organisation_Name, rr.Reason_Title, o.Billing_Last_Name, o.Billing_First_Name FROM `return` AS r INNER JOIN order_line AS ol ON r.Order_Line_ID = ol.Order_Line_ID INNER JOIN orders AS o ON o.Order_ID = ol.Order_ID INNER JOIN return_reason AS rr ON r.Reason_ID = rr.Reason_ID WHERE 0=0%s", $sqlWhere));
	$table->AddField('ID#', 'Return_ID', 'right');
	$table->AddField('Requested', 'Requested_On', 'left');
	$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_First_Name', 'left');
	$table->AddField('Surname', 'Billing_Last_Name', 'left');

	if($form->GetValue('status') == 'N') {
		$table->AddField('Status', 'Status', 'right');
	}

	$table->AddField('Order Number', 'Order_ID', 'right');
	$table->AddField('Reason', 'Reason_Title');

	if($form->GetValue('archived') == 'N') {
		$table->AddLink("returns_pending.php?id=%s&action=archive","<img src=\"./images/icon_view_1.gif\" alt=\"Archive Return\" border=\"0\">", "Return_ID");
	}

	$table->AddLink("return_details.php?id=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Return Details\" border=\"0\">",  "Return_ID");
	$table->AddLink("javascript:confirmRequest('returns_pending.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this return?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Return_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Requested_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->ExecuteSQL();
	echo $table->GetTableHeader();

	if($table->Table->TotalRows > 0){
		do{

			echo '<tr class="dataRow" onMouseOver="setClassName(this, \'dataRowOver\');" onMouseOut="setClassName(this, \'dataRow\');">';
			echo sprintf('<td  align="right">%d&nbsp;</td>', $table->Table->Row['Return_ID']);
			echo sprintf('<td  align="left">%s&nbsp;</td>', $table->Table->Row['Requested_On']);
			echo sprintf('<td  align="left">%s&nbsp;</td>', $table->Table->Row['Billing_Organisation_Name']);
			echo sprintf('<td  align="left">%s&nbsp;</td>', $table->Table->Row['Billing_First_Name']);
			echo sprintf('<td  align="left">%s&nbsp;</td>', $table->Table->Row['Billing_Last_Name']);

			if($form->GetValue('status') == 'N') {
				echo sprintf('<td  align="right">%s&nbsp;</td>', $table->Table->Row['Status']);
			}

			echo sprintf('<td  align="right"><a href="order_details.php?orderid=%d">%d</a>&nbsp;</td>', $table->Table->Row['Order_ID'], $table->Table->Row['Order_ID']);
			echo sprintf('<td  align="left">%s&nbsp;</td>', $table->Table->Row['Reason_Title']);


			echo sprintf('<td nowrap  align="center" width="16"><a href="returns_pending.php?id=%d&amp;action=archive"><img src="./images/icon_view_1.gif" alt="Archive Return" border="0"></a></td>', $table->Table->Row['Return_ID']);
			echo sprintf('<td nowrap  align="center" width="16"><a href="return_details.php?id=%d"><img src="./images/folderopen.gif" alt="Open Return Details" border="0"></a></td>', $table->Table->Row['Return_ID']);
			echo sprintf('<td nowrap  align="center" width="16"><a href="javascript:confirmRequest(\'returns_pending.php?action=remove&amp;confirm=true&amp;id=%d\',\'Are you sure you want to remove this return?\');"><img src="./images/aztector_6.gif" alt="Remove" border="0"></a></td>', $table->Table->Row['Return_ID']);
			echo "</tr>\n\n";

			$table->Table->Next();
		} while($table->Table->Row);
	} else {
		echo sprintf("<tr class=\"dataRow\"><td colspan=\"%s\">No Records Found</td></tr>", (count($table->Fields)/$table->NFields) + $table->LinkColumns);
	}

	echo "</table><br>";
	$table->DisplayNavigation();
	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function archive() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');

	if(isset($_REQUEST['id'])) {
		$return = new ProductReturn($_REQUEST['id']);
		$return->IsArchived = 'Y';
		$return->Update();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
}

function update(){
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');

	if(isset($_REQUEST['id']) && isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		$return = new ProductReturn($_REQUEST['id']);
		$return->Delete();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}
?>
