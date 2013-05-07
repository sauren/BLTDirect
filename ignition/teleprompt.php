<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

if($action == "add") {
	$session->Secure(3);
	add();
} elseif($action == "update") {
	$session->Secure(3);
	update();
} elseif($action == "remove") {
	$session->Secure(3);
	remove();
} else {
	$session->Secure(2);
	view();
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/TelePrompt.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','add','alpha',3,3);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('title','Title','text','','anything',0,100, true, 'style="width:100%;"');
	$form->AddField('ref','Reference','text','','anything',0,100, true, 'style="width:100%;"');
	$form->AddField('body','Body','textarea','','anything',0,1000000, false,'rows="30" style="width:100%;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()) {
			$prompt = new TelePrompt();
			$prompt->Title = $form->GetValue('title');
			$prompt->Ref = $form->GetValue('ref');
			$prompt->Body = $form->GetValue('body');
			$prompt->Add();

			redirect('Location: teleprompt.php');
		}
	}

	$page = new Page('<a href="teleprompt.php">TelePrompts</a> &gt; Add Teleprompt', 'Here you can add a new teleprompt');
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the teleprompt information');
	$webForm = new StandardForm();
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'),$form->GetHTML('title').$form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('ref'),$form->GetHTML('ref').$form->GetIcon('ref'));
	echo $webForm->AddRow($form->GetLabel('body'),$form->GetHTML('body').$form->GetIcon('body'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'teleprompt.php\';" />&nbsp;<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/TelePrompt.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$prompt = new TelePrompt($_REQUEST['pid']);
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','update','alpha',6,6);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('pid','Teleprompt ID','hidden',$prompt->ID,'numeric_unsigned',0,11);
	$form->AddField('title','Title','text',$prompt->Title,'anything',0,100, true, 'style="width:100%;"');
	$form->AddField('ref','Reference','text',$prompt->Ref,'anything',0,100, true, 'style="width:100%;"');
	$form->AddField('body','Body','textarea',$prompt->Body,'anything',0,1000000, false,'rows="30" style="width:100%;"');
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true")
	{
		if($form->Validate()) {
			$prompt->Title = $form->GetValue('title');
			$prompt->Ref = $form->GetValue('ref');
			$prompt->Body = $form->GetValue('body');
			$prompt->Update();
			
			redirect('Location: teleprompt.php');
		}
	}

	$page = new Page('<a href="teleprompt.php'.$id.'">Tele Prompts</a> &gt; Update','Here you can edit this teleprompt');
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid)
	{
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please edit the document information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();

	echo $webForm->AddRow($form->GetLabel('title'),$form->GetHTML('title').$form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('ref'),$form->GetHTML('ref').$form->GetIcon('ref'));
	echo $webForm->AddRow($form->GetLabel('body'),$form->GetHTML('body').$form->GetIcon('body'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';" />&nbsp;<input type="submit" name="update" value="update" class="btn" tabindex="%s">',$_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/TelePrompt.php');
	if(isset($_REQUEST['pid']) && is_numeric($_REQUEST['pid'])) {
		$prompt = new TelePrompt($_REQUEST['pid']);
		$prompt->Remove();
	}

	redirect("Location: teleprompt.php");
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Document.php');

	$page = new Page('TelePrompts', "Here you can view the Teleprompts");
	$page->Display('header');

	$tableId = "com";
	$current = isset($_REQUEST[$tableId.'_Current']) ? $_REQUEST[$tableId.'_Current'] : 1;

	$table = new DataTable($tableId);
	$table->SetSQL("SELECT * FROM teleprompt");
	$table->AddField("ID#","TelePrompt_ID",'left');
	$table->AddField("Title","Title");
	$table->AddField("Reference","Ref");
	
	$table->AddLink("teleprompt.php?action=update&pid=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update this teleprompt\" border=\"0\">",  "TelePrompt_ID");
	$table->AddLink("javascript:confirmRequest('teleprompt.php?action=remove&pid=%s','Are you sure you want to remove this teleprompt?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove this teleprompt\" border=\"0\">", "TelePrompt_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Title');
	$table->Finalise();
	$table->DisplayTable();

	echo "<br />";

	$table->DisplayNavigation();

	echo '<br />';
	echo '<input name="add" type="button" value="add a teleprompt" class="btn" onclick="window.location.href=\'./teleprompt.php?action=add\'">';

	$page->Display('footer');
}
?>