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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PublicHoliday.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$holiday = new PublicHoliday();
		$holiday->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PublicHoliday.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('title', 'Title', 'text', '', 'anything', 0, 50, true);
	$form->AddField('holidaydate', 'Holiday Date', 'text', '', 'date_ddmmyyyy', 10, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$holiday = new PublicHoliday();
			$holiday->Title = $form->GetValue('title');
			$holiday->HolidayDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('holidaydate'), 6, 4), substr($form->GetValue('holidaydate'), 3, 2), substr($form->GetValue('holidaydate'), 0, 2));
			$holiday->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Public Holidays</a> &gt; Add Holiday', $_SERVER['PHP_SELF']), 'Add a new public holiday here.');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add public holiday.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Add');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('holidaydate'), $form->GetHTML('holidaydate') . $form->GetIcon('holidaydate'));
	echo $webForm->AddRow('','<input type="submit" name="add" value="add" class="btn" />');
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PublicHoliday.php');

	$holiday = new PublicHoliday($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Public Holiday ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $holiday->Title, 'anything', 0, 50, true);
	$form->AddField('holidaydate', 'Holiday Date', 'text', sprintf('%s/%s/%s', substr($holiday->HolidayDate, 8, 2), substr($holiday->HolidayDate, 5, 2), substr($holiday->HolidayDate, 0, 4)), 'date_ddmmyyyy', 10, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$holiday->Title = $form->GetValue('title');
			$holiday->HolidayDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('holidaydate'), 6, 4), substr($form->GetValue('holidaydate'), 3, 2), substr($form->GetValue('holidaydate'), 0, 2));
			$holiday->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Public Holidays</a> &gt; Update Holiday', $_SERVER['PHP_SELF']), 'Update an existing public holiday here.');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update public holiday.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Update');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('holidaydate'), $form->GetHTML('holidaydate') . $form->GetIcon('holidaydate'));
	echo $webForm->AddRow('','<input type="submit" name="update" value="update" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Public Holidays', 'View public holidays.');
	$page->Display('header');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('year', 'Year', 'select', '0', 'numeric_unsigned', 1,11);
	$form->AddOption('year', '0', '');

	$data = new DataQuery(sprintf("SELECT DISTINCT(YEAR(Holiday_Date)) AS Year FROM public_holiday ORDER BY Year ASC"));
	while($data->Row) {
		$form->AddOption('year', $data->Row['Year'], $data->Row['Year']);

		$data->Next();
	}
	$data->Disconnect();

	$window = new StandardWindow("Filter public holidays by year.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Filter');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('year'), $form->GetHTML('year') . '<input type="submit" name="filter" value="filter" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

    echo '<br />';

    $table = new DataTable('holidays');
	$table->SetSQL(sprintf("SELECT * FROM public_holiday%s", ($form->GetValue('year') > 0) ? sprintf(" WHERE YEAR(Holiday_Date)=%d", $form->GetValue('year')) : ''));
	$table->AddField("ID#", "Public_Holiday_ID");
	$table->AddField("Date", "Holiday_Date");
	$table->AddField("Title", "Title");
	$table->AddLink("public_holidays.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update item\" border=\"0\">", "Public_Holiday_ID");
	$table->AddLink("javascript:confirmRequest('public_holidays.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"./images/button-cross.gif\" alt=\"Remove item\" border=\"0\">", "Public_Holiday_ID");
	$table->SetMaxRows(25);
    $table->SetOrderBy('Holiday_Date');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add public holiday" class="btn" onclick="window.location.href=\'public_holidays.php?action=add\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}