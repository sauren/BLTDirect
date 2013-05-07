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
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Logo.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$logo = new Logo();
		$logo->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Logo.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 255, true);
	$form->AddField('default', 'Is Default', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('fromdate', 'Active From Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('todate', 'Active To Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, true);

	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			$logo = new Logo();

			if(strlen($form->GetValue('fromdate')) > 0) {
				$logo->ActiveFromDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('fromdate'), 6, 4), substr($form->GetValue('fromdate'), 3, 2), substr($form->GetValue('fromdate'), 0, 2));
			}

			if(strlen($form->GetValue('todate')) > 0) {
				$logo->ActiveToDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('todate'), 6, 4), substr($form->GetValue('todate'), 3, 2), substr($form->GetValue('todate'), 0, 2));
			}

			if(($logo->ActiveFromDate != '0000-00-00 00:00:00') && ($logo->ActiveToDate != '0000-00-00 00:00:00')) {
				if($logo->ActiveToDate <= $logo->ActiveFromDate) {
					$form->AddError('Active To Date must come after Active From Date.');
				}
			}

			if($form->Valid) {
				$logo->Name = $form->GetValue('name');
				$logo->IsDefault = $form->GetValue('default');

				if($logo->Add('image')) {
			   		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
				} else {
					for($i=0; $i<count($logo->Image->Errors); $i++) {
						$form->AddError($logo->Image->Errors[$i]);
					}
				}
			}
		}
	}

	$page = new Page('Add Logo', 'Please complete the form below.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
	$page->Display('header');

	if (!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add logo');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('default'), $form->GetHTML('default') . $form->GetIcon('default'));
	echo $webForm->AddRow($form->GetLabel('fromdate'), $form->GetHTML('fromdate') . $form->GetIcon('fromdate'));
	echo $webForm->AddRow($form->GetLabel('todate'), $form->GetHTML('todate') . $form->GetIcon('todate'));
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'logos.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Logo.php');

	$logo = new Logo($_REQUEST['id']);

	$fromDate = '';
	$toDate = '';

	if($logo->ActiveFromDate > '0000-00-00 00:00:00') {
		$fromDate = date('d/m/Y', strtotime($logo->ActiveFromDate));
	}

	if($logo->ActiveToDate > '0000-00-00 00:00:00') {
		$toDate = date('d/m/Y', strtotime($logo->ActiveToDate));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Logo ID', 'hidden', $logo->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $logo->Name, 'anything', 1, 255, true);
	$form->AddField('default', 'Is Default', 'checkbox', $logo->IsDefault, 'boolean', 1, 1, false);
	$form->AddField('fromdate', 'Active From Date', 'text', $fromDate, 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('todate', 'Active To Date', 'text', $toDate, 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);

	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			if(strlen($form->GetValue('fromdate')) > 0) {
				$logo->ActiveFromDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('fromdate'), 6, 4), substr($form->GetValue('fromdate'), 3, 2), substr($form->GetValue('fromdate'), 0, 2));
			} else {
				$logo->ActiveFromDate = '0000-00-00 00:00:00';
			}

			if(strlen($form->GetValue('todate')) > 0) {
				$logo->ActiveToDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('todate'), 6, 4), substr($form->GetValue('todate'), 3, 2), substr($form->GetValue('todate'), 0, 2));
			} else {
				$logo->ActiveToDate = '0000-00-00 00:00:00';
			}

			if(($logo->ActiveFromDate != '0000-00-00 00:00:00') && ($logo->ActiveToDate != '0000-00-00 00:00:00')) {
				if($logo->ActiveToDate <= $logo->ActiveFromDate) {
					$form->AddError('Active To Date must come after Active From Date.');
				}
			}

			if($form->Valid) {
				$logo->Name = $form->GetValue('name');
				$logo->IsDefault = $form->GetValue('default');

				if($logo->Update('image')) {
			   		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
				} else {
					for($i=0; $i<count($logo->Image->Errors); $i++) {
						$form->AddError($logo->Image->Errors[$i]);
					}
				}
			}
		}
	}

	$page = new Page('Update Logo', 'Please complete the form below.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
	$page->Display('header');

	if (!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Update logo');
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
	echo $webForm->AddRow($form->GetLabel('default'), $form->GetHTML('default') . $form->GetIcon('default'));
	echo $webForm->AddRow($form->GetLabel('fromdate'), $form->GetHTML('fromdate') . $form->GetIcon('fromdate'));
	echo $webForm->AddRow($form->GetLabel('todate'), $form->GetHTML('todate') . $form->GetIcon('todate'));
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));

	if(!empty($logo->Image->FileName) && file_exists($GLOBALS['LOGO_IMAGE_DIR_FS'].$logo->Image->FileName)) {
		echo $webForm->AddRow('Current Image', sprintf('<img src="%s%s" alt="%s" />', $GLOBALS['LOGO_IMAGE_DIR_WS'], $logo->Image->FileName, $logo->Name));
	}

	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'logos.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function view() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Logos', 'This area allows you to maintain logos for your website.');
	$page->Display('header');

	$table = new DataTable('logos');
	$table->SetSQL("SELECT * FROM logo");
	$table->AddField('ID#', 'Logo_ID', 'right');
	$table->AddField('Name', 'Name', 'left');
	$table->AddField('Is Default', 'Is_Default', 'center');
	$table->AddField('Active From Date', 'Active_From_Date', 'left');
	$table->AddField('Active To Date', 'Active_To_Date', 'left');
	$table->AddLink("logos.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Logo\" border=\"0\">", "Logo_ID");
	$table->AddLink("javascript:confirmRequest('logos.php?action=remove&id=%s','Are you sure you want to remove this logo?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Logo_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add logo" class="btn" onclick="window.location.href=\'logos.php?action=add\'">';

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}
?>