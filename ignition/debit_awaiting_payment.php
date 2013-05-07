<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
} elseif($action == 'open') {
	$session->Secure(2);
	open();
} elseif($action == 'email') {
	$session->Secure(2);
	email();
} else {
	$session->Secure(2);
	view();
}

function email () {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');

	$debit = new Debit($_REQUEST['id']);

	if($debit->EmailSupplier()) {
		redirect(sprintf("Location: %s?action=open&id=%d&email=sent", $_SERVER['PHP_SELF'], $_REQUEST['id']));
	} else {
		redirect(sprintf("Location: %s?action=open&id=%d&email=failed", $_SERVER['PHP_SELF'], $_REQUEST['id']));
	}
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');

	if(isset($_REQUEST['id']) && isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		$debit = new Debit();
		$debit->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function open(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'open', 'alpha', 1, 10);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 1, 10);
	$form->AddField('id', '', 'hidden', $_REQUEST['id'], 'numeric_unsigned', 1, 11);

	$debit = new Debit($_REQUEST['id']);
	$html = $debit->GetDocument();
	$html .= '<br><br><br>';

	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true')) {
		$debit->IsPaid = 'Y';
		$debit->Update();

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	echo $form->Open();
	echo $form->GetHtml('action');
	echo $form->GetHtml('confirm');
	echo $form->GetHtml('id');
	?>

	<script language="text/javascript" type="text/javascript">
	function printme(){
		var doc = document.getElementById('print');
		doc.style.display= "none";
		window.self.print();
		doc.style.display="inline";
	}
	</script>

	<?php
	echo $html;

	if(!isset($_REQUEST['print'])) {
		if(isset($_REQUEST['email']) && ($_REQUEST['email'] == 'sent')) {
			echo '<p><strong>The supplier has been successfully emailed.</strong></p>';
		} elseif(isset($_REQUEST['email']) && ($_REQUEST['email'] == 'failed')) {
			echo '<p><strong>The supplier could not be emailed because their failed to provide an email address.</strong></p>';
		}

		echo "&nbsp;<input type='button' class='btn' name='print' id='print' value='Print Debit Note' onclick='printme()' />";
		echo '&nbsp;<input type="button" class="btn" name="email" id="email" value="Email Supplier" onclick="window.location.href=\''.$_SERVER['PHP_SELF'].'?action=email&id='.$_REQUEST['id'].'\';" />';

		if($debit->IsPaid == 'N') {
			echo "&nbsp;<input type='submit' class='btn' value='Mark Paid' name='pay' />";
		}
	}

	echo $form->Close();
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page("Debit/Credit Note Matching (Opt 1)", "Here you can view debits awaiting payment.");
	$page->Display('header');

	$table = new DataTable("com");
	$table->SetSQL("SELECT *, CONCAT(Prefix, Debit_ID) AS Debit_Reference FROM debit WHERE Is_Paid='N' AND Status<>''");
	$table->AddField('Date Debited','Created_On');
	$table->AddField('Reference', 'Debit_Reference');
	$table->AddField('Organisation','Debit_Organisation');
	$table->AddField('First Name','Debit_First_Name');
	$table->AddField('Last Name','Debit_Last_Name');
	$table->AddField('Amount Due','Debit_Total');
	$table->AddLink('debit_awaiting_payment.php?action=open&id=%s',"<img src=\"./images/folderopen.gif\" alt=\"Open this debit note\" border=\"0\">",'Debit_ID');
	$table->AddLink("javascript:confirmRequest('debit_awaiting_payment.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this debit note?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">","Debit_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Created_On');
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
}

require_once('lib/common/app_footer.php');