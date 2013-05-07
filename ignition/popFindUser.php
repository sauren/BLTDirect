<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

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
		$user = new User($_REQUEST['id']);

		$page = new Page();
		$page->DisableTitle();
		$page->Display('header');
		echo sprintf('<script language="javascript" type="text/javascript">window.opener.%s(%d, \'%s\', \'%s\'); window.close();</script>', $_REQUEST['callback'], $user->ID, $user->Person->Name, $user->Person->LastName);
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

	$window = new StandardWindow("Search for a User");
	$webForm = new StandardForm();

	$page = new Page('User Search', '');
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
	echo $webForm->AddRow($form->GetLabel('string'), $form->GetHTML('string') . '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	if(isset($_REQUEST['string']) && !empty($_REQUEST['string'])){
		$strings = explode(" ", $_REQUEST['string']);

		$sql = "SELECT u.User_Name, u.User_ID, p.Name_First, p.Name_Last FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE";

		for($i=0; $i < count($strings); $i++){
			if($i > 0){
				$sql .= " AND ";
			}
			$sql .= " (p.Name_First LIKE '%" . $strings[$i] . "%' OR p.Name_Last LIKE '%" . $strings[$i] . "%' OR u.User_Name LIKE '%" . $strings[$i] . "%')";
		}

		$table = new DataTable('results');
		$table->SetSQL($sql);
		$table->AddField("ID", "User_ID", "right");
		$table->AddField("Username", "User_Name", "left");
		$table->AddField("First Name", "Name_First", "left");
		$table->AddField("Last Name", "Name_Last", "left");
		$table->AddLink("popFindUser.php?action=found&id=%s", "[Use]", "User_ID");
		$table->SetMaxRows(10);
		$table->SetOrderBy("Name_Last, Name_First");
		$table->Order = 'ASC';
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