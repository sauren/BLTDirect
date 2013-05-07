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

	$window = new StandardWindow("Search for a Customer");
	$webForm = new StandardForm;

	$page = new Page('Customer Search', '');
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
		$strings = explode(" ", $_REQUEST['string']);
		$tempSQL = "SELECT c.Customer_ID, p.Name_First, p.Name_Last, r.Region_Name, a.Zip FROM customer AS c INNER JOIN contact AS n ON c.Contact_ID=n.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID INNER JOIN address AS a ON a.Address_ID=p.Address_ID LEFT JOIN regions AS r ON a.Region_ID=r.Region_ID WHERE";

		for($i=0; $i < count($strings); $i++){
			if($i > 0){
				$tempSQL .= " AND ";
			}
			$tempSQL .= sprintf(" (p.Name_First LIKE '%%%s%%' OR p.Name_Last LIKE '%%%s%%' OR REPLACE(a.Zip, ' ', '') LIKE REPLACE('%%%s%%', ' ', ''))", addslashes(stripslashes($strings[$i])), addslashes(stripslashes($strings[$i])), addslashes(stripslashes($strings[$i])));
		}

		$table = new DataTable('results');
		$table->SetSQL($tempSQL);
		$table->AddField("ID", "Customer_ID", "right");
		$table->AddField("First Name", "Name_First", "left");
		$table->AddField("Last Name", "Name_Last", "left");
		$table->AddField("Region", "Region_Name", "left");
		$table->AddField("Postcode", "Zip", "left");
		$table->AddLink("popFindCustomer.php?action=found&id=%s", "[Use]", "Customer_ID");
		$table->SetMaxRows(10);
		$table->SetOrderBy("Customer_ID");
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