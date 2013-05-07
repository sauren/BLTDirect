<?php
require_once ('lib/common/app_header.php');

if ($action == "add") {
	$session->Secure(3);
	add();
	exit();
} elseif ($action == "update") {
	$session->Secure(3);
	update();
	exit();
} elseif ($action == "remove") {
	$session->Secure(3);
	remove();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function remove() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$catalogue = new Catalogue();
		$catalogue->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 120, true, 'style="width: 300px;"');
	$form->AddField('description', 'Description', 'textarea', '', 'anything', 1, 1024, false, 'style="width: 100%;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$catalogue = new Catalogue();
			$catalogue->Title = $form->GetValue('title');
			$catalogue->Description = $form->GetValue('description');
			$catalogue->Add();

			redirect(sprintf("Location: catalogue_profile.php?id=%d", $catalogue->ID));
		}
	}

	$page = new Page('<a href="catalogues.php">Catalogues</a> &gt; Add New Catalogue', 'Please complete the form below.');
	$page->SetEditor(true);
	$page->Display('header');

	if (!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Catalogue');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'catalogues.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function view() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Catalogues', 'This area allows you to maintain  catalogues for your website.');
	$page->Display('header');

	$table = new DataTable('catalogues');
	$table->SetSQL("SELECT * FROM catalogue");
	$table->AddField('ID#', 'Catalogue_ID', 'right');
	$table->AddField('Title', 'Title', 'left');
	$table->AddLink("catalogue_profile.php?id=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Catalogue Profile\" border=\"0\">", "Catalogue_ID");
	$table->AddLink("javascript:confirmRequest('catalogues.php?action=remove&id=%s','Are you sure you want to remove this catalogue?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Catalogue_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add catalogue" class="btn" onclick="window.location.href=\'catalogues.php?action=add\'">';

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}
?>