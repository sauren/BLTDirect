<?php
require_once('lib/common/app_header.php');

$session->Secure(2);

if($action == "use"){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	$person = new DataQuery(sprintf("select Person_ID, Name_First, Name_Last from person where Person_ID=%d", mysql_real_escape_string($_REQUEST['id'])));
	$page = new Page();
	$page->DisableTitle();
	$page->Display('header');
	echo sprintf("<script>popFindPerson(%d, '%s %s');</script>", $person->Row['Person_ID'], $person->Row['Name_First'], $person->Row['Name_Last']);
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
	$person->Disconnect();
} else {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', '', 'hidden', 'search', 'alpha', 6, 6);
	$form->AddField('str', 'Search String', 'text', '', 'numeric_unsigned', 3, 150);

	$page = new Page();
	$window = new StandardWindow("Search for a Person.");
	$webForm = new StandardForm;

	$page->DisableTitle();
	$page->SetFocus('str');
	$page->Display('header');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $window->Open();
	echo $window->AddHeader('Enter a persons first and/or last name and click the search button.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('person') . ' ' . $form->GetHTML('str'), '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	if($action == "search"){
		$strings = explode(" ", $_REQUEST['str']);
		$tempSQL = "select * from person where";

		for($i=0; $i < count($strings); $i++){
			if($i > 0){
				$tempSQL .= " AND ";
			}
			$tempSQL .= " (Name_First LIKE '%" . $strings[$i] . "%' OR Name_Last LIKE '%" . $strings[$i] . "%')";
		}

		$table = new DataTable('findPerson');
		$table->SetSQL($tempSQL);
		$table->AddField("ID", "Person_ID", "right");
		$table->AddField("First Name", "Name_First", "left");
		$table->AddField("Last Name", "Name_Last", "left");
		$table->AddLink("popFindPerson.php?action=use&id=%s",
		"[Use]",
		"Person_ID");
		$table->SetMaxRows(10);
		$table->SetOrderBy("Name_Last");
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