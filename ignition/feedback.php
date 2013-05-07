<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	if(isset($_REQUEST['feedback'])) {
		$data = new DataQuery(sprintf("DELETE FROM feedback WHERE Feedback_ID=%d LIMIT 1", mysql_real_escape_string($_REQUEST['feedback'])));
		$data->Disconnect();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Feedback.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 62);
	$form->AddField('description', 'Description', 'textarea', '', 'anything', 1, 2000, true, 'style="width:100%; height:250px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$feedback = new Feedback();
			$feedback->Description = $form->GetValue('description');
			$feedback->Name = $form->GetValue('title');
			$feedback->Add();

			redirect("Location: feedback.php");
		}
	}

	$page = new Page('Add Feedback', 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow('',sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'feedback.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Feedback.php');

	$feedback = new Feedback($_REQUEST['feedback']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('feedback', 'Feedback ID', 'hidden', $feedback->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $feedback->Name, 'anything', 1, 62);
	$form->AddField('description', 'Description', 'textarea', $feedback->Description, 'anything', 1, 2000, true, 'style="width:100%; height:250px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$article->Description = $form->GetValue('description');
			$article->Name = $form->GetValue('title');
			$article->Update();

			redirect("Location: feedback.php");
		}
	}

	$page = new Page('Update Feedback','Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Update');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('feedback');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title').$form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description').$form->GetIcon('description'));
	echo $webForm->AddRow('',sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'feedback.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Customer Feedback','This area allows you to maintain articles published within this category.');
	$page->Display('header');

	$table = new DataTable('feedback');
	$table->SetSQL("select * from feedback");
	$table->AddField('ID#', 'Feedback_ID', 'right');
	$table->AddField('Title', 'Title', 'left');
	$table->AddField('Created', 'Created_On', 'left');
	$table->AddLink("feedback.php?action=update&feedback=%s",
	"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",
	"Feedback_ID");
	$table->AddLink("javascript:confirmRequest('feedback.php?action=remove&confirm=true&feedback=%s','Are you sure you want to remove this Customer Feedback?');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Feedback_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new feedback" class="btn" onclick="window.location.href=\'feedback.php?action=add\'" />');
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}