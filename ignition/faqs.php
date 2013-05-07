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
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FAQ.php');
	$faq = new FAQ();
	$faq->Delete($_REQUEST['id']);
	redirect("Location: faqs.php");
	exit();

}

function add() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FAQ.php');
	
	$form = new Form("faqs.php");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('question', 'Question', 'text', '', 'paragraph', 1, 255, true, 'style="width:100%"');
	$form->AddField('answer', 'Answer', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:100%; height:150px;"');
	
	// Check if the form has been submitted	

	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {
			// Hurrah! Create a new entry.			$faq = new FAQ();
			$faq->Question = $form->GetValue('question');
			$faq->Answer = $form->GetValue('answer');
			$faq->Add();
			redirect("Location: faqs.php");
		}
	}
	
	$page = new Page('Add a New FAQ', 'Please complete the form below.');
	$page->Display('header');
	
	// Show Error Report if Form Object validation fails	if (!$form->Valid) {
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
	$webForm = new StandardForm();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('question'), $form->GetHTML('question') . $form->GetIcon('question'));
	echo $webForm->AddRow($form->GetLabel('answer'), $form->GetHTML('answer') . $form->GetIcon('answer'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'faqs.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FAQ.php');
	
	$faq = new FAQ($_REQUEST['id']);
	
	$form = new Form("faqs.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'FAQ ID', 'hidden', $faq->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('question', 'Question', 'text', $faq->Question, 'paragraph', 1, 255, true, 'style="width:100%"');
	$form->AddField('answer', 'Answer', 'textarea', $faq->Answer, 'paragraph', 1, 2000, true, 'style="width:100%; height:150px;"');

	if (isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if ($form->Validate()) {			$faq->Question = $form->GetValue('question');
			$faq->Answer = $form->GetValue('answer');
			$faq->Update();
			
			redirect("Location: faqs.php");
		}
	}
	
	$page = new Page('Update FAQ', 'Please complete the form below.');
	$page->Display('header');
	
	// Show Error Report if Form Object validation fails	if (!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow('Update');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('question'), $form->GetHTML('question') . $form->GetIcon('question'));
	echo $webForm->AddRow($form->GetLabel('answer'), $form->GetHTML('answer') . $form->GetIcon('answer'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'faqs.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function view() {
	require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	
	$page = new Page('My Website Frequently Asked Questions', 'This area allows you to maintain the Frequently Asked Questions section of your website.');
	$page->Display('header');
	$table = new DataTable('faq');
	$table->SetSQL("select * from faq");
	$table->AddField('ID#', 'FAQ_ID', 'right');
	$table->AddField('Question', 'Question', 'left');
	$table->AddLink("faqs.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">", "FAQ_ID");
	$table->AddLink("javascript:confirmRequest('faqs.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this FAQ?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "FAQ_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Question");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo "<br>";
	echo '<input type="button" name="add" value="add a new faq" class="btn" onclick="window.location.href=\'faqs.php?action=add\'">';
	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}
?>