<?php
require_once('lib/common/app_header.php');

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
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LampTemperature.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','add','alpha',3,3);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('reference', 'Reference', 'text', '', 'anything', 0, 60);
	$form->AddField('colour', 'Colour', 'text', '', 'anything', 0, 60);
	$form->AddField('value', 'Specification', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('value', '0', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=42 ORDER BY Value ASC"));
	while($data->Row) {
		$form->AddOption('value', $data->Row['Value_ID'], $data->Row['Value']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('cr1ra', 'CR1 Ra', 'text', '', 'anything', 0, 16, false);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			$lampTemperature = new LampTemperature();
			$lampTemperature->Reference = $form->GetValue('reference');
			$lampTemperature->Colour = $form->GetValue('colour');
			$lampTemperature->Value->ID = $form->GetValue('value');
			$lampTemperature->CR1Ra = $form->GetValue('cr1ra');
			$lampTemperature->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Lamp Temperatures</a> &gt; Add Temperature', $_SERVER['PHP_SELF']), 'Here you can add a new lamp colour temperature.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the lamp temperature information.');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('reference'),$form->GetHTML('reference').$form->GetIcon('reference'));
	echo $webForm->AddRow($form->GetLabel('colour'),$form->GetHTML('colour').$form->GetIcon('colour'));
	echo $webForm->AddRow($form->GetLabel('value'),$form->GetHTML('value').$form->GetIcon('value'));
	echo $webForm->AddRow($form->GetLabel('cr1ra'),$form->GetHTML('cr1ra').$form->GetIcon('cr1ra'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';" />&nbsp;<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LampTemperature.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$lampTemperature = new LampTemperature();

	if(!$lampTemperature->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','update','alpha',6,6);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('id', 'Lamp Temperature ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('reference', 'Reference', 'text', $lampTemperature->Reference, 'anything', 0, 60);
	$form->AddField('colour', 'Colour', 'text', $lampTemperature->Colour, 'anything', 0, 60);
	$form->AddField('value', 'Specification', 'select', $lampTemperature->Value->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('value', '0', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=42 ORDER BY Value ASC"));
	while($data->Row) {
		$form->AddOption('value', $data->Row['Value_ID'], $data->Row['Value']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('cr1ra', 'CR1 Ra', 'text', $lampTemperature->CR1Ra, 'anything', 0, 16, false);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			$lampTemperature->Reference = $form->GetValue('reference');
			$lampTemperature->Colour = $form->GetValue('colour');
			$lampTemperature->Value->ID = $form->GetValue('value');
			$lampTemperature->CR1Ra = $form->GetValue('cr1ra');
			$lampTemperature->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Lamp Temperatures</a> &gt; Update Temperature', $_SERVER['PHP_SELF']), 'Here you can update an existing lamp colour temperature.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the lamp temperature information.');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('reference'),$form->GetHTML('reference').$form->GetIcon('reference'));
	echo $webForm->AddRow($form->GetLabel('colour'),$form->GetHTML('colour').$form->GetIcon('colour'));
	echo $webForm->AddRow($form->GetLabel('value'),$form->GetHTML('value').$form->GetIcon('value'));
	echo $webForm->AddRow($form->GetLabel('cr1ra'),$form->GetHTML('cr1ra').$form->GetIcon('cr1ra'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';" />&nbsp;<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LampTemperature.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$temperature = new LampTemperature();
		$temperature->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

	$page = new Page("Lamp Temperatures", "Manage lamp temperatures for aiding product searching.");
	$page->Display('header');

	$table = new DataTable("com");
	$table->SetSQL("SELECT lt.*, psv.Value FROM lamp_temperature AS lt LEFT JOIN product_specification_value AS psv ON psv.Value_ID=lt.Specification_Value_ID");
	$table->AddField("Temperature ID#", "Lamp_Temperature_ID", 'left');
	$table->AddField("Reference", "Reference");
	$table->AddField("Colour", "Colour");
	$table->AddField("Specification Value", "Value");
	$table->AddField("CR1 Ra", "CR1_Ra");
	$table->AddLink("lamp_temperatures.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update this item\" border=\"0\">",  "Lamp_Temperature_ID");
	$table->AddLink("javascript:confirmRequest('lamp_temperatures.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove this affiliate\" border=\"0\">", "Lamp_Temperature_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Value');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input name="add" type="submit" value="add new temperature" class="btn" onclick="window.location.href=\'lamp_temperatures.php?action=add\'">';

	$page->Display('footer');
}

require_once('lib/common/app_header.php');
?>