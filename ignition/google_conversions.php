<?php
require_once('lib/common/app_header.php');

if($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/GoogleConversion.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$conversion = new GoogleConversion();
		$conversion->Delete($_REQUEST['id']);
	}

	redirect('Location: ?action=view');
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/GoogleConversion.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('conversions', 'Conversions', 'text', '', 'numeric_unsigned', 1, 11, true);
	$form->AddField('month', 'Month', 'select', date('m'), 'anything', 1, 11);
	$form->AddOption('month', '', '');

	for($i=1; $i<=12; $i++) {          
		$form->AddOption('month', date('m', mktime(0, 0, 0, $i, 1, date('Y'))), date('F', mktime(0, 0, 0, $i, 1, date('Y'))));
	}

	$form->AddField('year', 'Year', 'select', date('Y'), 'anything', 1, 11);
	$form->AddOption('year', '', '');

	for($i=date('Y')-10; $i<=date('Y'); $i++) {
		$form->AddOption('year', $i, $i);
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$conversion = new GoogleConversion();
			$conversion->Conversions = $form->GetValue('conversions');
			$conversion->Month = sprintf('%s-%s-01 00:00:00', $form->GetValue('year'), $form->GetValue('month'));
			$conversion->Add();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page(sprintf('<a href="%s">Google Conversions</a> &gt; Add Conversion', $_SERVER['PHP_SELF']), 'Add conversion figures here.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Adding conversion figures');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Enter conversion details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('conversions'), $form->GetHTML('conversions') . $form->GetIcon('conversions'));
	echo $webForm->AddRow($form->GetLabel('month'), $form->GetHTML('month') . ' ' . $form->GetHTML('year') . $form->GetIcon('end'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'overheads.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/GoogleConversion.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
	}

	$conversion = new GoogleConversion($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Google Conversion ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('conversions', 'Conversions', 'text', $conversion->Conversions, 'numeric_unsigned', 1, 11, true);
	$form->AddField('month', 'Month', 'select', substr($conversion->Month, 5, 2), 'anything', 1, 11);
	$form->AddOption('month', '', '');

	$found = false;
	
	for($i=1; $i<=12; $i++) {
		$form->AddOption('month', date('m', mktime(0, 0, 0, $i, 1, date('Y'))), date('F', mktime(0, 0, 0, $i, 1, date('Y'))));
		
		if($i == substr($conversion->Month, 5, 2)) {
			$found = true;	
		}
	}
	
	if(!$found) {
		$form->AddOption('month', date('m', mktime(0, 0, 0, substr($conversion->Month, 5, 2), 1, date('Y'))), date('F', mktime(0, 0, 0, substr($conversion->Month, 5, 2), 1, date('Y'))));
	}

	$form->AddField('year', 'Year', 'select', substr($conversion->Month, 0, 4), 'anything', 1, 11);
	$form->AddOption('year', '', '');

	$found = false;
	
	for($i=date('Y')-10; $i<=date('Y'); $i++) {
		$form->AddOption('year', $i, $i);
		
		if($i == substr($conversion->Month, 0, 4)) {
			$found = true;	
		}
	}

	if(!$found) {
		$form->AddOption('year', substr($conversion->Month, 0, 4), substr($conversion->Month, 0, 4));
	}
	
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$conversion->Conversions = $form->GetValue('conversions');
			$conversion->Month = sprintf('%s-%s-01 00:00:00', $form->GetValue('year'), $form->GetValue('month'));
			$conversion->Update();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page(sprintf('<a href="%s">Google Conversions</a> &gt; Update Conversion', $_SERVER['PHP_SELF']), 'Update existing conversion figures here.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Updating conversion figures');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Enter conversion details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('conversions'), $form->GetHTML('conversions') . $form->GetIcon('conversions'));
	echo $webForm->AddRow($form->GetLabel('month'), $form->GetHTML('month') . ' ' . $form->GetHTML('year') . $form->GetIcon('end'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'overheads.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Google Conversions', 'Listing all available Google conversions.');
	$page->Display('header');

	$table = new DataTable('conversions');
	$table->SetSQL("SELECT * FROM google_conversion");
	$table->AddField("ID#", "GoogleConversionID");
	$table->AddField("Month", "Month", "left");
	$table->AddField("Conversions", "Conversions", "right");
	$table->AddLink("?action=update&id=%s","<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "GoogleConversionID");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "GoogleConversionID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Month");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add new conversions" class="btn" onclick="window.location.href=\'?action=add\'" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}