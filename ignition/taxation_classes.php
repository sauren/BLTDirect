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
} elseif($action == "changer"){
	$session->Secure(3);
	changer();
	exit;
} elseif($action == "set150"){
	$session->Secure(3);
	set150();
	exit;
} elseif($action == "set175"){
	$session->Secure(3);
	set175();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxClass.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('class', 'Tax Class Title', 'text', '', 'alpha_numeric', 1, 60);
	$form->AddField('description', 'Tax Class Description', 'textarea', '', 'paragraph', 1, 255, false, 'style="width:100%, height:100px"');
	$form->AddField('default', 'Is Default Taxation Class?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$group = new TaxClass;
			$group->Name = $form->GetValue('class');
			$group->Description = $form->GetValue('description');
			$group->IsDefault = $form->GetValue('default');
			$group->Add();

			redirect("Location: taxation_classes.php");
		}
	}

	$page = new Page('Add Tax Class','');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add a Taxation Class.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('class'), $form->GetHTML('class') . $form->GetIcon('class'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('default'), $form->GetHTML('default') . $form->GetIcon('default'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'taxation_classes.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxClass.php');

	$group = new TaxClass($_REQUEST['taxc']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('taxc', 'Tax Class ID', 'hidden', $group->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('class', 'Tax Class Title', 'text', $group->Name, 'alpha_numeric', 1, 60);
	$form->AddField('description', 'Tax Class Description', 'textarea', $group->Description, 'paragraph', 1, 255, false, 'style="width:100%, height:100px"');
	$form->AddField('default', 'Is Default Taxation Class?', 'checkbox', $group->IsDefault, 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$group->Name = $form->GetValue('class');
			$group->Description = $form->GetValue('description');
			$group->IsDefault = $form->GetValue('default');
			$group->Update();

			redirect("Location: taxation_classes.php");
		}
	}

	$page = new Page('Update Tax Class','');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update a Taxation Class.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('taxc');
	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('class'), $form->GetHTML('class') . $form->GetIcon('class'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('default'), $form->GetHTML('default') . $form->GetIcon('default'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'taxation_classes.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxClass.php');

	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['taxc'])){
		$group = new TaxClass;
		$group->Remove($_REQUEST['taxc']);
	}

	redirect("Location: taxation_classes.php");
}

function set150() {
	new DataQuery(sprintf("UPDATE tax SET Tax_Rate=%f WHERE Tax_Rate=%f", 15, 17.5));

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function set175() {
	new DataQuery(sprintf("UPDATE tax SET Tax_Rate=%f WHERE Tax_Rate=%f", 17.5, 15));

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Taxation Class Settings','You can create many different Taxation Classes.');
	$page->Display('header');

	$table = new DataTable('taxClass');
	$table->SetSQL("select * from tax_class");
	$table->AddField('ID#', 'Tax_Class_ID', 'right');
	$table->AddField('Name', 'Tax_Class_Title', 'left');
	$table->AddField('Default', 'Is_Default', 'center');
	$table->AddLink("taxation.php?taxc=%s",
							"<img src=\"./images/folderopen.gif\" alt=\"View Options for this Class\" border=\"0\">",
							"Tax_Class_ID");
	$table->AddLink("taxation_classes.php?action=update&taxc=%s",
							"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",
							"Tax_Class_ID");
	$table->AddLink("javascript:confirmRequest('taxation_classes.php?action=remove&confirm=true&taxc=%s','Are you sure you want to remove this Taxation Class? Note: this operation will remove all related information. Please do not remove Default Tax Classes.');",
							"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
							"Tax_Class_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Tax_Class_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo '<input type="button" name="add" value="add a new class" class="btn" onclick="window.location.href=\'taxation_classes.php?action=add\'" /> ';
	echo '<input type="button" name="changer" value="global tax changer" class="btn" onclick="window.location.href=\'taxation_classes.php?action=changer\'" /> ';
	echo '<br /><br />';
	echo '<input type="button" name="15" value="set 15.0% tax rate" class="btn" onclick="window.location.href=\'taxation_classes.php?action=set150\';" /> ';
	echo '<input type="button" name="17.5" value="set 17.5% tax rate" class="btn" onclick="window.location.href=\'taxation_classes.php?action=set175\';" /> ';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function changer(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'changer', 'alpha', 7, 7);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('classFrom', 'From Tax Class ID', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('classFrom', '0', 'All Products with Unassigned Tax');
	$form->AddField('classTo', 'To Tax Class ID', 'select', '', 'numeric_unsigned', 1, 11);
	$getClasses = new DataQuery("select * from tax_class order by Tax_Class_Title");
	while($getClasses->Row){
		$form->AddOption('classFrom', $getClasses->Row['Tax_Class_ID'], $getClasses->Row['Tax_Class_Title']);
		$form->AddOption('classTo', $getClasses->Row['Tax_Class_ID'], $getClasses->Row['Tax_Class_Title']);
		$getClasses->Next();
	}
	$getClasses->Disconnect();

	$updated = 0;
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$data = new DataQuery(sprintf("update product set Tax_Class_ID=%d where Tax_Class_ID=%d",
											mysql_real_escape_string($form->GetValue('classTo')),
											mysql_real_escape_string($form->GetValue('classFrom'))));
			$updated = $data->AffectedRows;
		}
	}

	$page = new Page('<a href="taxation_classes.php">Tax Class Settings</a> &gt; Global Changes',
	'Do you need to swap one tax class for another amongst all products? Or, do you need set all products with unassigned tax classes to a known class? You can do it here.');
	$page->Display('header');
	if(!empty($updated)){
		echo "<p class=\"alert\">" . $updated . " Products were updated</p>";
	}
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	echo $form->Open();
	echo $form->GetHtml('action');
	echo $form->GetHtml('confirm');
	echo "Change ";
	echo $form->GetHtml('classFrom');
	echo " to ";
	echo $form->GetHtml('classTo');
	echo '<br /><br /><input type="submit" name="submit" value="submit" class="btn" />';
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
