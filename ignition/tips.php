<?php
	/*
		Module:		Tips
		Version:	1.0
		Product:	Ignition
		
		Copyright (c) Deveus Software, 2004
	*/
	require_once('lib/common/app_header.php');
	
	if($action == 'add'){
		$session->Secure(3);
		addTips();
		exit;
	} elseif($action == 'remove'){
		$session->Secure(3);
		removeTips();
		exit;
	} elseif($action == 'update'){
		$session->Secure(3);
		updateTips();
		exit;
	} elseif($action == 'tip'){
		viewTip();
		exit;
	} else {
		getTips();
		exit;
	}
	
	function viewTip(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		$data = new DataQuery(sprintf('select * from registry_tips where Tip_ID=%d', $_REQUEST['id']));
		$page = new Page($data->Row['Tip_Title'], $data->Row['Tip_Description']);
		$page->Display('header');
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	function removeTips(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == 'true'){
			if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
				$remove = new DataQuery(sprintf("delete from registry_tips where Tip_ID=%d", $_REQUEST['id']));
			}
		}
		$url = sprintf("tips.php?action=view%s", extractVars('action,confirm,id'));
		redirect(sprintf("Location: %s", $url));
	}

	function updateTips(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		
		$data = new DataQuery(sprintf('select * from registry_tips where Tip_ID=%d', $_REQUEST['id']));
		
		$form = new Form("tips.php");
		$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('tip', 'Tip ID', 'hidden', $data->Row['Tip_ID'], 'numeric_unsigned', 1, 11);
		$form->AddField('script', 'Script ID', 'select', $data->Row['Registry_ID'], 'numeric_unsigned', 1, 11);
		
		$scriptOptions = new DataQuery('select * from registry order by Script_Name asc');
		if($scriptOptions->TotalRows > 0){
			do{
				$form->AddOption('script', $scriptOptions->Row['Registry_ID'], $scriptOptions->Row['Script_Name']);
				$scriptOptions->Next();
			} while($scriptOptions->Row);
		}
		$scriptOptions->Disconnect();
		$form->AddField('title', 'Tip Title', 'text', $data->Row['Tip_Title'], 'alpha_numeric', 3, 150);
		$form->AddField('description', 'Tip Description', 'textarea', $data->Row['Tip_Description'], 'paragraph', 5, 2000, true, 'style="width:250px; height:150px;"');
	
		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$insertForm = new DataQuery(sprintf("update registry_tips set Registry_ID=%d, Tip_Title='%s', Tip_Description='%s', Modified_On=Now(), Modified_By=%d where Tip_ID", 
													$form->GetValue('script'),
													$form->GetValue('title'),
													$form->GetValue('description'),
													$GLOBALS['SESSION_USER_ID']));
				redirect(sprintf("Location: tips.php?script=%d", $form->GetValue('script')));
			}
		}
		
		$page = new Page('Updating a Tip','You are updating a tip which will appear within the built-in Tips Window and are designed to assist an inuitive user interface.');
		$page->Display('header');
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		$window = new StandardWindow('Update a Tip');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('tip');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow('Assign to script: ', $form->GetHTML('script') . $form->GetIcon('script'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'tips.php?script=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetValue('script'), $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	function addTips(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		
		$form = new Form("tips.php");
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('script', 'Script ID', 'select', $_REQUEST['script'], 'numeric_unsigned', 1, 11);
		
		$scriptOptions = new DataQuery('select * from registry order by Script_Name asc');
		if($scriptOptions->TotalRows > 0){
			do{
				$form->AddOption('script', $scriptOptions->Row['Registry_ID'], $scriptOptions->Row['Script_Name']);
				$scriptOptions->Next();
			} while($scriptOptions->Row);
		}
		$scriptOptions->Disconnect();
		$form->AddField('title', 'Tip Title', 'text', '', 'alpha_numeric', 3, 150);
		$form->AddField('description', 'Tip Description', 'textarea', '', 'paragraph', 5, 2000, true, 'style="width:250px; height:150px;"');
	
		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$insertForm = new DataQuery(sprintf("insert into registry_tips (Registry_ID, Tip_Title, Tip_Description, Created_On, Created_By) values (%d, '%s', '%s', Now(), %d)", 
													$form->GetValue('script'),
													$form->GetValue('title'),
													$form->GetValue('description'),
													$GLOBALS['SESSION_USER_ID']));
				redirect(sprintf("Location: tips.php?script=%d", $form->GetValue('script')));
				exit;
			}
		}
		
		$page = new Page('Adding Tips Window Items','You are adding a tip of an associated script from the registry.');
		$page->AddOnLoad("document.getElementById('title').focus();");
		$page->Display('header');
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		$window = new StandardWindow('Add a Tip to a Registry Script');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow('Assign to script: ', $form->GetHTML('script') . $form->GetIcon('script'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'tips.php?script=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetValue('script'), $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	function getTips(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
		$page = new Page(
					'Ignition Tips Registry',
					'Add, Edit, and Remove Tips associated with scripts in the Script Registry.');
		$page->Display('header');
		
		// Initialise DataTable
		$table = new DataTable('tips');
		$table->SetSQL(sprintf("SELECT registry.Script_File, registry_tips.Tip_ID, registry_tips.Tip_Title FROM registry_tips INNER JOIN registry ON registry_tips.Registry_ID=registry.Registry_ID where registry_tips.Registry_ID=%d", $_REQUEST['script']));
		$table->AddField("ID#", "Tip_ID", "right");
		$table->AddField("Script", "Script_File", "left");
		$table->AddField("Tip Title", "Tip_Title", "left");
		$table->SetMaxRows(10);
		$table->SetOrderBy("Tip_ID");
		$table->AddLink("tips.php?action=update&id=%s", 
						"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Tip\" border=\"0\">", 
						"Tip_ID");
		$table->AddLink("javascript:confirmRequest('tips.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this tip?');", 
						"<img src=\"./images/aztector_6.gif\" alt=\"Remove Tip\" border=\"0\">", 
						"Tip_ID");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		echo sprintf(' <input type="button" name="back" value="back" class="btn" onclick="window.location.href=\'registry.php?action=view%s\'">', extractVars('action,confirm,id'));
		echo sprintf(' <input type="button" name="add" value="add tip" class="btn" onclick="window.location.href=\'tips.php?action=add%s\'">', extractVars('action,confirm,id'));
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>
