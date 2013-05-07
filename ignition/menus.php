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
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Menu.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$menu = new Menu();
		$menu->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Menu.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 30, true);
	$form->AddField('link', 'Link', 'text', '', 'link', 1, 1024, true);

	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			$menu = new Menu();
			$menu->Title = $form->GetValue('title');
			$menu->Link = $form->GetValue('link');
			$menu->Add();

		   	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="menus.php">Menus</a> &gt; Add Menu', 'Please complete the form below.');
	$page->Display('header');
	if (!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add menu');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('link'), $form->GetHTML('link') . $form->GetIcon('link'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'menus.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function update() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Menu.php');

	$menu = new Menu($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Menu ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $menu->Title, 'anything', 1, 30, true);
	$form->AddField('link', 'Link', 'text', $menu->Link, 'link', 1, 1024, true);

	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			$menu->Title = $form->GetValue('title');
			$menu->Link = $form->GetValue('link');
			$menu->Update();

		   	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="menus.php">Menus</a> &gt; Update Menu', 'Please complete the form below.');
	$page->Display('header');

	if (!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update menu');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('link'), $form->GetHTML('link') . $form->GetIcon('link'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'menus.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function view() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Menus', 'This area allows you to add custom menus to yoru site.');
	$page->Display('header');

	$table = new DataTable('menus');
	$table->SetSQL("SELECT * FROM menu");
	$table->AddField('ID#', 'Menu_ID', 'right');
	$table->AddField('Title', 'Title', 'left');
	$table->AddLink("menus.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Item\" border=\"0\">", "Menu_ID");
	$table->AddLink("javascript:confirmRequest('menus.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Menu_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add menu" class="btn" onclick="window.location.href=\'menus.php?action=add\'">';

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}
?>