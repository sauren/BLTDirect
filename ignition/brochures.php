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
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Brochure.php');
	
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$brochure = new Brochure();
		$brochure->Delete($_REQUEST['id']);
	}
	
	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Brochure.php');
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 255, true);
	$form->AddField('default', 'Is Default', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('fromdate', 'Active From Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('todate', 'Active To Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('image', 'Image (Menu)', 'file', '', 'file', NULL, NULL, true);
	$form->AddField('image2', 'Image (Spread)', 'file', '', 'file', NULL, NULL, true);
	$form->AddField('download', 'Download', 'file', '', 'file', NULL, NULL, true);
	
	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			$brochure = new Brochure();
			
			if(strlen($form->GetValue('fromdate')) > 0) {
				$brochure->ActiveFromDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('fromdate'), 6, 4), substr($form->GetValue('fromdate'), 3, 2), substr($form->GetValue('fromdate'), 0, 2));
			}
			
			if(strlen($form->GetValue('todate')) > 0) {
				$brochure->ActiveToDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('todate'), 6, 4), substr($form->GetValue('todate'), 3, 2), substr($form->GetValue('todate'), 0, 2));
			}							
			if(($brochure->ActiveFromDate != '0000-00-00 00:00:00') && ($brochure->ActiveToDate != '0000-00-00 00:00:00')) {
				if($brochure->ActiveToDate <= $brochure->ActiveFromDate) {
					$form->AddError('Active To Date must come after Active From Date.');
				}
			}
			
			if($form->Valid) {	
				$brochure->Name = $form->GetValue('name');
				$brochure->IsDefault = $form->GetValue('default');
				
				if($brochure->Add('image', 'image2', 'download')) {
			   		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
				} else {
					for($i=0; $i<count($brochure->Image->Errors); $i++) {
						$form->AddError($brochure->Image->Errors[$i]);
					}
					
					for($i=0; $i<count($brochure->Image2->Errors); $i++) {
						$form->AddError($brochure->Image2->Errors[$i]);
					}

					for($i=0; $i<count($brochure->Download->Errors); $i++) {
						$form->AddError($brochure->Download->Errors[$i]);
					}
				}
			}
		}
	}
	
	$page = new Page('Add Brochure', 'Please complete the form below.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
	$page->Display('header');
		if (!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow('Add brochure');
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
	echo $webForm->AddRow($form->GetLabel('image2'), $form->GetHTML('image2') . $form->GetIcon('image2'));
	echo $webForm->AddRow($form->GetLabel('download'), $form->GetHTML('download') . $form->GetIcon('download'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'brochures.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Brochure.php');
	
	$brochure = new Brochure($_REQUEST['id']);
	
	$fromDate = '';
	$toDate = '';
	
	if($brochure->ActiveFromDate > '0000-00-00 00:00:00') {
		$fromDate = date('d/m/Y', strtotime($brochure->ActiveFromDate));
	}
	
	if($brochure->ActiveToDate > '0000-00-00 00:00:00') {
		$toDate = date('d/m/Y', strtotime($brochure->ActiveToDate));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Brochure ID', 'hidden', $brochure->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $brochure->Name, 'anything', 1, 255, true);
	$form->AddField('default', 'Is Default', 'checkbox', $brochure->IsDefault, 'boolean', 1, 1, false);
	$form->AddField('fromdate', 'Active From Date', 'text', $fromDate, 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('todate', 'Active To Date', 'text', $toDate, 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('image', 'Image (Menu)', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('image2', 'Image (Spread)', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('download', 'Download', 'file', '', 'file', NULL, NULL, false);
	
	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			if(strlen($form->GetValue('fromdate')) > 0) {
				$brochure->ActiveFromDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('fromdate'), 6, 4), substr($form->GetValue('fromdate'), 3, 2), substr($form->GetValue('fromdate'), 0, 2));
			} else {
				$brochure->ActiveFromDate = '0000-00-00 00:00:00';
			}
			
			if(strlen($form->GetValue('todate')) > 0) {
				$brochure->ActiveToDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('todate'), 6, 4), substr($form->GetValue('todate'), 3, 2), substr($form->GetValue('todate'), 0, 2));
			} else {
				$brochure->ActiveToDate = '0000-00-00 00:00:00';
			}
			
			if(($brochure->ActiveFromDate != '0000-00-00 00:00:00') && ($brochure->ActiveToDate != '0000-00-00 00:00:00')) {
				if($brochure->ActiveToDate <= $brochure->ActiveFromDate) {
					$form->AddError('Active To Date must come after Active From Date.');
				}
			}
			
			if($form->Valid) {			
				$brochure->Name = $form->GetValue('name');
				$brochure->IsDefault = $form->GetValue('default');
				
				if($brochure->Update('image', 'image2', 'download')) {
			   		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
				} else {
					for($i=0; $i<count($brochure->Image->Errors); $i++) {
						$form->AddError($brochure->Image->Errors[$i]);
					}
					
					for($i=0; $i<count($brochure->Image2->Errors); $i++) {
						$form->AddError($brochure->Image2->Errors[$i]);
					}

					for($i=0; $i<count($brochure->Download->Errors); $i++) {
						$form->AddError($brochure->Download->Errors[$i]);
					}
				}
			}
		}
	}
	
	$page = new Page('Update Brochure', 'Please complete the form below.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
	$page->Display('header');
		if (!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow('Update brochure');
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
	
	if(!empty($brochure->Image->FileName) && file_exists($GLOBALS['BROCHURE_MENU_IMAGE_DIR_FS'].$brochure->Image->FileName)) {
		echo $webForm->AddRow('Current Image', sprintf('<img src="%s%s" alt="%s" />', $GLOBALS['BROCHURE_MENU_IMAGE_DIR_WS'], $brochure->Image->FileName, $brochure->Name));
	}
	
	echo $webForm->AddRow($form->GetLabel('image2'), $form->GetHTML('image2') . $form->GetIcon('image2'));
	
	if(!empty($brochure->Image2->FileName) && file_exists($GLOBALS['BROCHURE_SPREAD_IMAGE_DIR_FS'].$brochure->Image2->FileName)) {
		echo $webForm->AddRow('Current Image (Spread)', sprintf('<img src="%s%s" alt="%s" />', $GLOBALS['BROCHURE_SPREAD_IMAGE_DIR_WS'], $brochure->Image2->FileName, $brochure->Name));
	}
	
	echo $webForm->AddRow($form->GetLabel('download'), $form->GetHTML('download') . $form->GetIcon('download'));
	
	if(!empty($brochure->Download->FileName) && file_exists($GLOBALS['BROCHURE_DOWNLOAD_DIR_FS'].$brochure->Download->FileName)) {
		echo $webForm->AddRow('Current Download', sprintf('<a href="%s%s" target="_blank">%s</a>', $GLOBALS['BROCHURE_DOWNLOAD_DIR_WS'], $brochure->Download->FileName, $brochure->Download->FileName));
	}
	
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'brochures.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function view() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	
	$page = new Page('Brochures', 'This area allows you to maintain brochures for your website.');
	$page->Display('header');
	
	$table = new DataTable('brochures');
	$table->SetSQL("SELECT * FROM brochure");
	$table->AddField('ID#', 'Brochure_ID', 'right');
	$table->AddField('Name', 'Name', 'left');
	$table->AddField('Is Default', 'Is_Default', 'center');
	$table->AddField('Active From Date', 'Active_From_Date', 'left');
	$table->AddField('Active To Date', 'Active_To_Date', 'left');
	$table->AddLink("brochures.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Brochure\" border=\"0\">", "Brochure_ID");
	$table->AddLink("javascript:confirmRequest('brochures.php?action=remove&id=%s','Are you sure you want to remove this brochure?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Brochure_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add brochure" class="btn" onclick="window.location.href=\'brochures.php?action=add\'">';
	
	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}
?>