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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Overhead.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$overhead = new Overhead();
		$overhead->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Overhead.php');

	$startDateDay = date('d/m/Y');
	$endDateDay = date('d/m/Y', time() + 86400);
	$startDateMonth = date('01/m/Y');
	$endDateMonth = date('01/m/Y', mktime(0, 0, 0, date('m')+1, 1, date('Y')));

	$boundary = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y")));

	if(time() < strtotime($boundary)) {
		$startFinancialYear = date('d/m/Y', mktime(0, 0, 0, 5, 1, date("Y")-1));
		$endFinancialYear = date('d/m/Y', mktime(0, 0, 0, 5, 1, date("Y")));
	} else {
		$startFinancialYear = date('d/m/Y', mktime(0, 0, 0, 5, 1, date("Y")));
		$endFinancialYear = date('d/m/Y', mktime(0, 0, 0, 5, 1, date("Y")+1));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('type', 'Type', 'select', '', 'numeric_unsigned', 1, 11, true);
	$form->AddOption('type', '', '');

	$data = new DataQuery(sprintf("SELECT Overhead_Type_ID, Name FROM overhead_type ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('type', $data->Row['Overhead_Type_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 128, true);
	$form->AddField('value', 'Value', 'text', '0.00', 'float', 1, 11, true);
	$form->AddField('period', 'Period', 'select', 'D', 'alpha', 1, 1, true, 'onchange="populateDates(this);"');
	$form->AddOption('period', 'D', 'Daily');
	$form->AddOption('period', 'M', 'Monthly');
	$form->AddOption('period', 'Y', 'Yearly');
	$form->AddField('workingday', 'Working Days Only', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('start', 'Start Date', 'text', $startDateDay, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'End Date', 'text', $endDateDay, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$overhead = new Overhead();
			$overhead->Type->ID = $form->GetValue('type');
			$overhead->Name = $form->GetValue('name');
			$overhead->Value = $form->GetValue('value');
			$overhead->Period = $form->GetValue('period');
			$overhead->IsWorkingDaysOnly = $form->GetValue('workingday');
			$overhead->StartDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2));
			$overhead->EndDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2));

			if($overhead->StartDate >= $overhead->EndDate) {
				$form->AddError('End Date cannot be before Start Date.', 'end');
			}

			if($form->Valid) {
				$overhead->Add();

				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var populateDates = function(obj) {
			var start = document.getElementById(\'start\');
			var end = document.getElementById(\'end\');

			if(obj.value == \'D\') {
				start.value = \'%s\';
				end.value = \'%s\';
			} else if((obj.value == \'M\') || (obj.value == \'Y\')) {
				start.value = \'%s\';
				end.value = \'%s\';
			}
		}
		</script>', $startDateDay, $endDateDay, $startDateMonth, $endDateMonth);

	$script = sprintf('<script language="javascript" type="text/javascript">
		var insertFinancialYear = function() {
			var start = document.getElementById(\'start\');
			var end = document.getElementById(\'end\');

			start.value = \'%s\';
			end.value = \'%s\';
		}
		</script>', $startFinancialYear, $endFinancialYear);

	$page = new Page(sprintf('<a href="%s">Overheads</a> &gt; Add Overhead', $_SERVER['PHP_SELF']), 'Add an overhead here.');
	$page->AddOnLoad("document.getElementById('name').focus();");
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Adding an Overhead');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Enter an overhead.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $form->GetIcon('value'));
	echo $webForm->AddRow($form->GetLabel('period'), $form->GetHTML('period') . $form->GetIcon('period'));
	echo $webForm->AddRow($form->GetLabel('workingday'), $form->GetHTML('workingday') . $form->GetIcon('workingday'));
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start') . $form->GetIcon('start') . ' (<a href="javascript:insertFinancialYear();">Insert Financial Year</a>)');
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end') . $form->GetIcon('end'));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Overhead.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
	}

	$overhead = new Overhead($_REQUEST['id']);

	$start = ($overhead->StartDate != '0000-00-00 00:00:00') ? sprintf('%s/%s/%s', substr($overhead->StartDate, 8, 2), substr($overhead->StartDate, 5, 2), substr($overhead->StartDate, 0, 4)) : '';
	$end = ($overhead->EndDate != '0000-00-00 00:00:00') ? sprintf('%s/%s/%s', substr($overhead->EndDate, 8, 2), substr($overhead->EndDate, 5, 2), substr($overhead->EndDate, 0, 4)) : '';

	$boundary = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y")));

	if(time() < strtotime($boundary)) {
		$startFinancialYear = date('d/m/Y', mktime(0, 0, 0, 5, 1, date("Y")-1));
		$endFinancialYear = date('d/m/Y', mktime(0, 0, 0, 5, 1, date("Y")));
	} else {
		$startFinancialYear = date('d/m/Y', mktime(0, 0, 0, 5, 1, date("Y")));
		$endFinancialYear = date('d/m/Y', mktime(0, 0, 0, 5, 1, date("Y")+1));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('type', 'Type', 'select', $overhead->Type->ID, 'numeric_unsigned', 1, 11, true);

	$data = new DataQuery(sprintf("SELECT Overhead_Type_ID, Name FROM overhead_type ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('type', $data->Row['Overhead_Type_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('name', 'Name', 'text', $overhead->Name, 'anything', 1, 128, true);
	$form->AddField('value', 'Value', 'text', $overhead->Value, 'float', 1, 11, true);
	$form->AddField('period', 'Period', 'select', $overhead->Period, 'alpha', 1, 1, true);
	$form->AddOption('period', 'D', 'Daily');
	$form->AddOption('period', 'M', 'Monthly');
	$form->AddOption('period', 'Y', 'Yearly');
	$form->AddField('workingday', 'Working Days Only', 'checkbox', $overhead->IsWorkingDaysOnly, 'boolean', 1, 1, false);
	$form->AddField('start', 'Start Date', 'text', $start, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'End Date', 'text', $end, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$overhead->Type->ID = $form->GetValue('type');
			$overhead->Name = $form->GetValue('name');
			$overhead->Value = $form->GetValue('value');
			$overhead->Period = $form->GetValue('period');
			$overhead->IsWorkingDaysOnly = $form->GetValue('workingday');
			$overhead->StartDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2));
			$overhead->EndDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2));

			if($overhead->StartDate >= $overhead->EndDate) {
				$form->AddError('End Date cannot be before Start Date.', 'end');
			}

			if($form->Valid) {
				$overhead->Update();

				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var insertFinancialYear = function() {
			var start = document.getElementById(\'start\');
			var end = document.getElementById(\'end\');

			start.value = \'%s\';
			end.value = \'%s\';
		}
		</script>', $startFinancialYear, $endFinancialYear);

	$page = new Page(sprintf('<a href="%s">Overheads</a> &gt; Update Overhead', $_SERVER['PHP_SELF']), 'Update an overhead here.');
	$page->AddOnLoad("document.getElementById('name').focus();");
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Adding an Overhead');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Enter an overhead.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $form->GetIcon('value'));
	echo $webForm->AddRow($form->GetLabel('period'), $form->GetHTML('period') . $form->GetIcon('period'));
	echo $webForm->AddRow($form->GetLabel('workingday'), $form->GetHTML('workingday') . $form->GetIcon('workingday'));
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start') . $form->GetIcon('start') . ' (<a href="javascript:insertFinancialYear();">Insert Financial Year</a>)');
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end') . $form->GetIcon('end'));
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

	$page = new Page('Overheads', 'Listing all available overheads.');
	$page->Display('header');

	$table = new DataTable('overheads');
	$table->SetSQL("SELECT o.*, ot.Name AS Type FROM overhead AS o INNER JOIN overhead_type AS ot ON o.Overhead_Type_ID=ot.Overhead_Type_ID");
	$table->AddField("ID#", "Overhead_ID");
	$table->AddField("Name", "Name", "left");
	$table->AddField("Type", "Type", "left");
	$table->AddField("Value", "Value", "left");
	$table->AddField("Period", "Period", "center");
	$table->AddField("Working Day", "Is_Working_Days_Only", "center");
	$table->AddField("Start Date", "Start_Date", "left");
	$table->AddField("End Date", "End_Date", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->AddLink("overheads.php?action=update&id=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Overhead_ID");
	$table->AddLink("javascript:confirmRequest('overheads.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Overhead_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add new overhead" class="btn" onclick="window.location.href=\'overheads.php?action=add\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}