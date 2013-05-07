<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view() {
	$page = new Page('Resolved Returns', 'Below is a list of all resolved returns.');
	$page->Display('header');
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('return', 'Return ID', 'text', '', 'numeric_unsigned', 1, 11, false);
	
        $sql = "SELECT r.*, o.Order_ID, o.Billing_Organisation_Name, rr.Reason_Title,
                o.Billing_Last_Name, o.Billing_First_Name FROM `return` AS r
                INNER JOIN order_line AS ol ON r.Order_Line_ID = ol.Order_Line_ID
                INNER JOIN orders AS o ON o.Order_ID = ol.Order_ID
                INNER JOIN return_reason AS rr ON r.Reason_ID = rr.Reason_ID
                where r.Status='Resolved' ";
        
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if(strlen($form->GetValue('return')) > 0) {
				$sql .= sprintf("AND r.Return_ID LIKE '%s' ", $form->GetValue('return'));
			}
		}
	}
	
	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
        
	$window = new StandardWindow('Filter returns');
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('return'), $form->GetHTML('return'));
	echo $webForm->AddRow('', '<input type="submit" name="search" value="Search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();
	
	echo '<br />';
	
	$table = new DataTable("returns");
	$table->SetSQL($sql);
	$table->AddField('Return ID#', 'Return_ID', 'left');
	$table->AddField('Requested', 'Requested_On', 'left');
	$table->AddField('Organisation', 'Billing_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_First_Name', 'left');
	$table->AddField('Surname', 'Billing_Last_Name', 'left');
	$table->AddField('Order Number', 'Order_ID', 'right');
	$table->AddField('Reason', 'Reason_Title');
	$table->AddLink("return_details.php?id=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Return Details\" border=\"0\">", "Return_ID");
	$table->AddLink("javascript:confirmRequest('returns_resolved.php?action=remove&id=%s','Are you sure you want to remove this return?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Return_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Requested_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	if(isset($_REQUEST['id'])) {
		$return = new ProductReturn($_REQUEST['id']);
		$return->Delete();
	}
	
	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}