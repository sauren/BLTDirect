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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$postage = new Postage;
		$postage->Delete($_REQUEST['id']);
	}

	redirect("Location: postage.php");
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form("postage.php");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Postage Band Name (i.e. Next Day Delivery)', 'text', '', 'alpha_numeric', 3, 60);
	$form->AddField('days', 'Number of Days for Delivery', 'text', '', 'numeric_unsigned', 1, 3);
	$form->AddField('cuttoff', 'Cutt Off Time (24 Hour Clock)', 'text', '00:00', 'time', NULL, NULL);
	$form->AddField('message','Cutt Off Message','text','','alpha_numeric',0,400,false);
	$form->AddField('description', 'Postage Description', 'text', '', 'paragraph', 1, 160, false);
	$form->AddField('startday', 'Postage Start Day', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('startday', '0', '');
	$form->AddField('endday', 'Postage End Day', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('endday', '0', '');
	$form->AddField('starttime', 'Postage Start Time', 'text', '00:00', 'time', 5, 5);
	$form->AddField('endtime', 'Postage End Time', 'text', '00:00', 'time', 5, 5);

	for($i=1; $i<=7; $i++) {
		$day = '';

		switch($i) {
			case 1:
				$day = 'Monday';
				break;
			case 2:
				$day = 'Tuesday';
				break;
			case 3:
				$day = 'Wednesday';
				break;
			case 4:
				$day = 'Thursday';
				break;
			case 5:
				$day = 'Friday';
				break;
			case 6:
				$day = 'Saturday';
				break;
			case 7:
				$day = 'Sunday';
				break;
		}

		$form->AddOption('startday', $i, $day);
		$form->AddOption('endday', $i, $day);
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$postage = new Postage;
			$postage->Name = $form->GetValue('name');
			$postage->Days = $form->GetValue('days');
			$postage->Message = $form->GetValue('message');
			$postage->CuttOffTime = $form->GetValue('cuttoff');
			$postage->Description = $form->GetValue('description');
			$postage->StartDay = $form->GetValue('startday');
			$postage->StartTime = $form->GetValue('starttime');
			$postage->EndDay = $form->GetValue('endday');
			$postage->EndTime = $form->GetValue('endtime');
			$postage->Add();

			redirect("Location: postage.php");
		}
	}

	$page = new Page('Add a New Postage Band','Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Postage Settings');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('days'), $form->GetHTML('days') . $form->GetIcon('days'));
	echo $webForm->AddRow($form->GetLabel('cuttoff') . '<br />Set to 00:00 for all day availability.', $form->GetHTML('cuttoff') . $form->GetIcon('cuttoff'));
	echo $webForm->AddRow($form->GetLabel('message'),$form->GetHTML('message').$form->GetIcon('message'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('startday'), $form->GetHTML('startday') . $form->GetIcon('startday'));
	echo $webForm->AddRow($form->GetLabel('starttime'), $form->GetHTML('starttime') . $form->GetIcon('starttime'));
	echo $webForm->AddRow($form->GetLabel('endday'), $form->GetHTML('endday') . $form->GetIcon('endday'));
	echo $webForm->AddRow($form->GetLabel('endtime'), $form->GetHTML('endtime') . $form->GetIcon('endtime'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'postage.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$postage = new Postage($_REQUEST['id']);

	$form = new Form("postage.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Postage ID', 'hidden', $postage->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Postage Band Name (i.e. Next Day Delivery)', 'text', $postage->Name, 'alpha_numeric', 3, 60);
	$form->AddField('days', 'Number of Days for Delivery', 'text', $postage->Days, 'numeric_unsigned', 1, 3);
	$form->AddField('cuttoff', 'Cutt Off Time (24 Hour Clock)', 'text', $postage->CuttOffTime, 'time', NULL, NULL);
	$form->AddField('message','Cutt Off Message','text',$postage->Message,'alpha_numeric',0,400,false);
	$form->AddField('description', 'Postage Description', 'text', $postage->Description, 'paragraph', 1, 160, false);
	$form->AddField('startday', 'Postage Start Day', 'select', $postage->StartDay, 'numeric_unsigned', 1, 11);
	$form->AddOption('startday', '0', '');
	$form->AddField('endday', 'Postage End Day', 'select', $postage->EndDay, 'numeric_unsigned', 1, 11);
	$form->AddOption('endday', '0', '');
	$form->AddField('starttime', 'Postage Start Time', 'text', $postage->StartTime, 'time', 5, 5);
	$form->AddField('endtime', 'Postage End Time', 'text', $postage->EndTime, 'time', 5, 5);

	for($i=1; $i<=7; $i++) {
		$day = '';

		switch($i) {
			case 1:
				$day = 'Monday';
				break;
			case 2:
				$day = 'Tuesday';
				break;
			case 3:
				$day = 'Wednesday';
				break;
			case 4:
				$day = 'Thursday';
				break;
			case 5:
				$day = 'Friday';
				break;
			case 6:
				$day = 'Saturday';
				break;
			case 7:
				$day = 'Sunday';
				break;
		}

		$form->AddOption('startday', $i, $day);
		$form->AddOption('endday', $i, $day);
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$postage->Message = $form->GetValue('message');
			$postage->Name = $form->GetValue('name');
			$postage->Days = $form->GetValue('days');
			$postage->CuttOffTime = $form->GetValue('cuttoff');
			$postage->Description = $form->GetValue('description');
			$postage->StartDay = $form->GetValue('startday');
			$postage->StartTime = $form->GetValue('starttime');
			$postage->EndDay = $form->GetValue('endday');
			$postage->EndTime = $form->GetValue('endtime');
			$postage->Update();

			redirect("Location: postage.php");
		}
	}

	$page = new Page('Update a Postage Band','Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Update Postage Settings');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('days'), $form->GetHTML('days') . $form->GetIcon('days'));
	echo $webForm->AddRow($form->GetLabel('cuttoff') . '<br />Set to 00:00 for all day availability.', $form->GetHTML('cuttoff') . $form->GetIcon('cuttoff'));
	echo $webForm->AddRow($form->GetLabel('message'),$form->GetHTML('message').$form->GetIcon('message'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('startday'), $form->GetHTML('startday') . $form->GetIcon('startday'));
	echo $webForm->AddRow($form->GetLabel('starttime'), $form->GetHTML('starttime') . $form->GetIcon('starttime'));
	echo $webForm->AddRow($form->GetLabel('endday'), $form->GetHTML('endday') . $form->GetIcon('endday'));
	echo $webForm->AddRow($form->GetLabel('endtime'), $form->GetHTML('endtime') . $form->GetIcon('endtime'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'postage.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Postage Band Settings','Postage bands allow you to standardise the names and description of your Shipping Settings.');
	$page->Display('header');

	$table = new DataTable('postage');
	$table->SetSQL("select * from postage");
	$table->AddField('ID#', 'Postage_ID', 'right');
	$table->AddField('Name', 'Postage_Title', 'left');
	$table->AddField('Days for Delivery', 'Postage_Days', 'right');
	$table->AddField('Cut Off Time', 'Cutt_Off_Time', 'right');
	$table->AddLink("postage.php?action=update&id=%s",
					"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",
					"Postage_ID");
	$table->AddLink("javascript:confirmRequest('postage.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this postage band? IMPORTANT: removing this postage band will remove any shipping settings associated with it.');",
					"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
					"Postage_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Postage_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add a new postage band" class="btn" onclick="window.location.href=\'postage.php?action=add\'">';
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>