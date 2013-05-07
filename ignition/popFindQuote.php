<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(2);

if(!isset($_REQUEST['callback'])) {
	echo '<script language="javascript" type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the callback function is absent.\'); window.close();</script>';
	require_once('lib/common/app_footer.php');
	exit;
}

if($action == 'found'){
	found();
	exit;
} else {
	view();
	exit;
}

function found(){
	if(isset($_REQUEST['id'])){
		$page = new Page();
		$page->DisableTitle();
		$page->Display('header');
		echo sprintf('<script language="javascript" type="text/javascript">window.opener.%s(%d); window.close();</script>', $_REQUEST['callback'], $_REQUEST['id']);
		$page->Display('footer');

		require_once('lib/common/app_footer.php');
		exit;
	}

	redirect(sprintf("Location: %s?callback=%s", $_SERVER['PHP_SELF'], $_REQUEST['callback']));
}

function view() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('callback', 'Callback Function', 'hidden', '', 'alpha', 4, 4);
	$form->AddField('string', 'Search for ...', 'text', '', 'anything', 1, 255);
	$form->AddField('type', 'Search Type..', 'select', (isset($_REQUEST['type']) ? $_REQUEST['type'] : 'Order_ID'), 'alpha_numeric', 1, 60);
	$form->AddOption('type', 'Product', 'Product by Quickfind');
	$form->AddOption('type', 'Billing_Name', 'Name');
	$form->AddOption('type', 'Shipping_Name', 'Shipping Name');
	$form->AddOption('type', 'Billing_Zip', 'Postcode');
	$form->AddOption('type', 'Shipping_Zip', 'Shipping Postcode');

	$window = new StandardWindow("Search for a Quote");
	$webForm = new StandardForm;

	$page = new Page('Quote Search', '');
	$page->SetFocus('string');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	echo $form->Open();
	echo $form->GetHTML('callback');
	echo $window->Open();
	echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('string'), $form->GetHTML('string') . $form->GetHTML('type') . '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	if(isset($_REQUEST['string']) && !empty($_REQUEST['string'])){
		$sqlSelect = "SELECT q.*";
		$sqlTables = " FROM quote AS q";
		$sqlClause = " WHERE 0=0";

		switch($form->GetValue('type')){
			case 'Billing_Name':
				parse_search_string(stripslashes($form->GetValue('string')), $keywords);

				if(count($keywords) > 0) {
					$sqlClause .= sprintf(" AND");
				}

				for($i=0; $i < count($keywords); $i++) {
					$sqlClause .= sprintf(" (q.Billing_First_Name LIKE '%%%s%%' OR q.Billing_Last_Name LIKE '%%%s%%')", addslashes(stripslashes($keywords[$i])), addslashes(stripslashes($keywords[$i])));
				}

				break;
			case 'Billing_Zip':
				$sqlClause .= sprintf(" AND q.Billing_Zip='%s'", addslashes(stripslashes($form->GetValue('string'))));
				break;
			case 'Shipping_Name':
				parse_search_string(stripslashes($form->GetValue('string')), $keywords);

				if(count($keywords) > 0) {
					$sqlClause .= sprintf(" AND");
				}

				for($i=0; $i < count($keywords); $i++) {
					$sqlClause .= sprintf(" (q.Shipping_First_Name LIKE '%%%s%%' OR q.Shipping_Last_Name LIKE '%%%s%%')", addslashes(stripslashes($keywords[$i])), addslashes(stripslashes($keywords[$i])));
				}

				break;
			case 'Shipping_Zip':
				$sqlClause .= sprintf(" AND q.Shipping_Zip='%s'", addslashes(stripslashes($form->GetValue('string'))));
				break;
			case 'Product':
				$sqlTables .= sprintf(" INNER JOIN quote_line AS ql ON q.Quote_ID=ql.Quote_ID");
				$sqlClause .= sprintf(" AND ql.Product_ID=%d", $form->GetValue('string'));
				break;
		}

		$table = new DataTable('results');
		$table->SetSQL(sprintf('%s%s%s', $sqlSelect, $sqlTables, $sqlClause));
		$table->AddField("ID", "Quote_ID", "right");
		$table->AddField('Date', 'Created_On', 'left');
		$table->AddField('Status', 'Status', 'left');
		$table->AddField('Name', 'Billing_First_Name', 'left');
		$table->AddField('Surname', 'Billing_Last_Name', 'left');
		$table->AddField('Postcode', 'Billing_Zip', 'left');
		$table->AddField('Shipping Name', 'Shipping_First_Name', 'left');
		$table->AddField('Shipping Surname', 'Shipping_Last_Name', 'left');
		$table->AddField('Shipping Postcode', 'Shipping_Zip', 'left');
		$table->AddLink("popFindQuote.php?action=found&id=%s", "[Use]", "Quote_ID");
		$table->SetMaxRows(10);
		$table->SetOrderBy("Created_On");
		$table->Order = 'DESC';
		$table->Finalise();
		echo "<br>";
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>