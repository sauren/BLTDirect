<?php
require_once('lib/common/app_header.php');

if($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReferrerDataObject.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$referrer = new ReferrerDataObject();
		$referrer->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReferrerDataObject.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('domain', 'Domain', 'text', '', 'anything', 1, 255);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			$referrer = new ReferrerDataObject();
			$referrer->Domain = $form->GetValue('domain');
			$referrer->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="referrers.php">Referrers</a> &gt; Add Referrer', 'Add a new referrer here.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a referrer');
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an *.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('domain'), $form->GetHTML('domain') . $form->GetIcon('domain'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'referrers.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReferrerDataObject.php');

	$referrer = new ReferrerDataObject($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Referrer ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('domain', 'Domain', 'text', $referrer->Domain, 'anything', 1, 255);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			$referrer->Domain = $form->GetValue('domain');
			$referrer->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="referrers.php">Referrers</a> &gt; Update Referrer', 'Update an existing referrer here.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating a referrer');
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an *.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('domain'), $form->GetHTML('domain') . $form->GetIcon('domain'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'referrers.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Referrers', 'Manage referrers for your website here.');
	$page->Display('header');

	$table = new DataTable('results');
	$table->SetSQL("SELECT * FROM referrer");
	$table->AddField("ID", "ReferrerID", "right");
	$table->AddField("Domain", "Domain", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Domain");
	$table->AddLink("referrers.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "ReferrerID");
	$table->AddLink("javascript:confirmRequest('referrers.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "ReferrerID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add referrer" class="btn" onclick="window.location.href=\'referrers.php?action=add\'">';
	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>