<?php
require_once('lib/common/app_header.php');

if($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	if(isset($_REQUEST['confirm'])) {
		new DataQuery(sprintf("DELETE FROM package WHERE Package_ID=%d", mysql_real_escape_string($_REQUEST['id'])));
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Package.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 120);
	$form->AddField('width', 'Width (m)', 'text', '', 'float', 1, 11);
	$form->AddField('height', 'Height (m)', 'text', '', 'float', 1, 11);
	$form->AddField('depth', 'Depth (m)', 'text', '', 'float', 1, 11);
	$form->AddField('weight', 'Weight (kg)', 'text', '', 'float', 1, 11);
	$form->AddField('reduction', 'Reduction Percent (%)', 'text', '', 'float', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$package = new Package();
			$package->Name = $form->GetValue('name');
			$package->Width = $form->GetValue('width');
			$package->Height = $form->GetValue('height');
			$package->Depth = $form->GetValue('depth');
			$package->Weight = $form->GetValue('weight');
			$package->ReductionPercent = $form->GetValue('reduction');
			$package->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="packages.php">Packages</a> &gt; Add Package', 'Add a new package type here.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Package');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('width'), $form->GetHTML('width') . $form->GetIcon('width'));
	echo $webForm->AddRow($form->GetLabel('height'), $form->GetHTML('height') . $form->GetIcon('height'));
	echo $webForm->AddRow($form->GetLabel('depth'), $form->GetHTML('depth') . $form->GetIcon('depth'));
	echo $webForm->AddRow($form->GetLabel('weight'), $form->GetHTML('weight') . $form->GetIcon('weight'));
	echo $webForm->AddRow($form->GetLabel('reduction'), $form->GetHTML('reduction') . $form->GetIcon('reduction'));
	echo $webForm->AddRow('&nbsp;', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.location.href = \'packages.php\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Package.php');

	$package = new Package($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Package ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $package->Name, 'anything', 1, 120);
	$form->AddField('width', 'Width (m)', 'text', $package->Width, 'float', 1, 11);
	$form->AddField('height', 'Height (m)', 'text', $package->Height, 'float', 1, 11);
	$form->AddField('depth', 'Depth (m)', 'text', $package->Depth, 'float', 1, 11);
	$form->AddField('weight', 'Weight (kg)', 'text', $package->Weight, 'float', 1, 11);
	$form->AddField('reduction', 'Reduction Percent (%)', 'text', $package->ReductionPercent, 'float', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$package->Name = $form->GetValue('name');
			$package->Width = $form->GetValue('width');
			$package->Height = $form->GetValue('height');
			$package->Depth = $form->GetValue('depth');
			$package->Weight = $form->GetValue('weight');
			$package->ReductionPercent = $form->GetValue('reduction');
			$package->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="packages.php">Packages</a> &gt; Update Package', 'Edit an existing package type here.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update Package');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('width'), $form->GetHTML('width') . $form->GetIcon('width'));
	echo $webForm->AddRow($form->GetLabel('height'), $form->GetHTML('height') . $form->GetIcon('height'));
	echo $webForm->AddRow($form->GetLabel('depth'), $form->GetHTML('depth') . $form->GetIcon('depth'));
	echo $webForm->AddRow($form->GetLabel('weight'), $form->GetHTML('weight') . $form->GetIcon('weight'));
	echo $webForm->AddRow($form->GetLabel('reduction'), $form->GetHTML('reduction') . $form->GetIcon('reduction'));
	echo $webForm->AddRow('&nbsp;', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.location.href = \'packages.php\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Packages', 'Manage packaging types here.');
	$page->Display('header');

    $table = new DataTable('permissions');
	$table->SetSQL(sprintf("SELECT * FROM package"));
	$table->AddField('ID#', 'Package_ID', 'left');
	$table->AddField('Name', 'Name', 'left');
	$table->AddField('Width (m)', 'Width', 'right');
	$table->AddField('Height (m)', 'Height', 'right');
	$table->AddField('Depth (m)', 'Depth', 'right');
	$table->AddField('Weight (kg)', 'Weight', 'right');
	$table->AddField('Reduction Percent (%)', 'Reduction_Percent', 'right');
	$table->AddLink("packages.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Package_ID");
	$table->AddLink("javascript:confirmRequest('packages.php?action=remove&confirm=true&id=%s', 'Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Package_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" name="add package" value="add package" class="btn" onclick="window.location.href=\'packages.php?action=add\'" />');

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>