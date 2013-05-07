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
} elseif($action == 'approve'){
	$session->Secure(3);
	approve();
	exit;
} elseif($action == 'print'){
	$session->Secure(3);
	printAuthorsation();
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserHoliday.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	if(isset($_REQUEST['holidayid']) && is_numeric($_REQUEST['holidayid'])){
		$holiday = new UserHoliday($_REQUEST['holidayid']);
		$holiday->Delete();

		$user = new User($holiday->User->ID);
		$user->Recalculate();
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $holiday->User->ID));
}

function approve(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserHoliday.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	if(isset($_REQUEST['holidayid']) && is_numeric($_REQUEST['holidayid'])){
		$holiday = new UserHoliday($_REQUEST['holidayid']);
		$holiday->Approve();
		
		$user = new User($holiday->User->ID);
		$user->Recalculate();
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $holiday->User->ID));
}

function printAuthorsation(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserHoliday.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	if(isset($_REQUEST['holidayid']) && is_numeric($_REQUEST['holidayid'])){
		$holiday = new UserHoliday($_REQUEST['holidayid']);
		$holiday->Approve();
		
		$user = new User($holiday->User->ID);
		$user->Recalculate();
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $holiday->User->ID));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserHoliday.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: users.php"));
	}

	$user = new User($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'User ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('startdate', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this, 0, 6);" onfocus="scwShow(this, this, 0, 6);"');
	$form->AddField('startmeridiem', 'Start Meridiem', 'select', 'AM', 'alpha', 2, 2);
	$form->AddOption('startmeridiem', 'AM', 'AM');
	$form->AddOption('startmeridiem', 'PM', 'PM');
	$form->AddField('enddate', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this, 0, 6);" onfocus="scwShow(this, this, 0, 6);"');
	$form->AddField('endmeridiem', 'End Meridiem', 'select', 'PM', 'alpha', 2, 2);
	$form->AddOption('endmeridiem', 'AM', 'AM');
	$form->AddOption('endmeridiem', 'PM', 'PM');
	$form->AddField('notes', 'Notes', 'textarea', '', 'anything', 1, 2048, false, 'rows="5" style="width: 300px;"');
	$form->AddField('status', 'Status', 'select', 'Pending', 'alpha', 1, 30, true, 'onchange="toggleDeclined(this);"');
	$form->AddOption('status', 'Approved', 'Approved');
	$form->AddOption('status', 'Declined', 'Declined');
	$form->AddOption('status', 'Pending', 'Pending');
	$form->AddField('declined', 'Declined Because', 'textarea', '', 'anything', 1, 2048, false, 'rows="5" style="width: 300px;" disabled="disabled"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()) {
			if(strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2))) < strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2)))) {
				$form->AddError('End Date cannot be before Start Date.', 'enddate');
			} elseif((strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2))) == strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2)))) && (($form->GetValue('startmeridiem') == 'PM') && ($form->GetValue('endmeridiem') == 'AM'))) {
				$form->AddError('End Date cannot be before Start Date.', 'enddate');
			} elseif(date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2)))) != date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2))))) {
				$form->AddError('End Date cannot be a different year to the Start Date.', 'enddate');
			}

			if($form->Valid) {
				$holiday = new UserHoliday();
				$holiday->User->ID = $user->ID;
				$holiday->StartDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2));
				$holiday->StartMeridiem = $form->GetValue('startmeridiem');
				$holiday->EndDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2));
				$holiday->EndMeridiem = $form->GetValue('endmeridiem');
				$holiday->Notes = $form->GetValue('notes');
				$holiday->Status = $form->GetValue('status');
				$holiday->Add();
				
				if($holiday->Status == 'Approved') {
					$holiday->Approve();
					
				} elseif($holiday->Status == 'Declined') {
					$holiday->Decline($form->GetValue('declined'));
				}

				$user->Recalculate();

				redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $user->ID));
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleDeclined = function(obj) {
			var e = document.getElementById(\'declined\');

			if(e) {
				if(obj.value == \'Declined\') {
					e.removeAttribute(\'disabled\');
				} else {
					e.setAttribute(\'disabled\', \'disabled\');
				}
			}
		}
		</script>');

	$page = new Page(sprintf('<a href="users.php">Users</a> &gt; <a href="%s?id=%d">Holidays</a> &gt; Add Holiday', $_SERVER['PHP_SELF'], $user->ID), sprintf('Add a new holiday for %s.', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName))));
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Adding a holiday');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Add holiday.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('startdate'), $form->GetHTML('startdate') . $form->GetHTML('startmeridiem'));
	echo $webForm->AddRow($form->GetLabel('enddate'), $form->GetHTML('enddate') . $form->GetHTML('endmeridiem'));
	echo $webForm->AddRow($form->GetLabel('notes'), $form->GetHTML('notes') . $form->GetIcon('notes'));
	echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status') . $form->GetIcon('status'));
	echo $webForm->AddRow($form->GetLabel('declined'), $form->GetHTML('declined') . $form->GetIcon('declined'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'user_holiday.php?id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $user->ID, $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserHoliday.php');

	if(!isset($_REQUEST['holidayid'])) {
		redirect(sprintf("Location: users.php"));
	}

	$holiday = new UserHoliday($_REQUEST['holidayid']);

	$user = new User($holiday->User->ID);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('holidayid', 'User Holiday ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('startdate', 'Start Date', 'text', sprintf('%s/%s/%s', substr($holiday->StartDate, 8, 2), substr($holiday->StartDate, 5, 2), substr($holiday->StartDate, 0, 4)), 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this, 0, 6);" onfocus="scwShow(this, this, 0, 6);"');
	$form->AddField('startmeridiem', 'Start Meridiem', 'select', $holiday->StartMeridiem, 'alpha', 2, 2);
	$form->AddOption('startmeridiem', 'AM', 'AM');
	$form->AddOption('startmeridiem', 'PM', 'PM');
	$form->AddField('enddate', 'End Date', 'text', sprintf('%s/%s/%s', substr($holiday->EndDate, 8, 2), substr($holiday->EndDate, 5, 2), substr($holiday->EndDate, 0, 4)), 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this, 0, 6);" onfocus="scwShow(this, this, 0, 6);"');
	$form->AddField('endmeridiem', 'End Meridiem', 'select', $holiday->EndMeridiem, 'alpha', 2, 2);
	$form->AddOption('endmeridiem', 'AM', 'AM');
	$form->AddOption('endmeridiem', 'PM', 'PM');
	$form->AddField('notes', 'Notes', 'textarea', $holiday->Notes, 'anything', 1, 2048, false, 'rows="5" style="width: 300px;"');
	$form->AddField('status', 'Status', 'select', $holiday->Status, 'alpha', 1, 30, true, 'onchange="toggleDeclined(this);"');
	$form->AddOption('status', 'Approved', 'Approved');
	$form->AddOption('status', 'Declined', 'Declined');
	$form->AddOption('status', 'Pending', 'Pending');
	$form->AddField('declined', 'Declined Because', 'textarea', $holiday->DeclinedBecause, 'anything', 1, 2048, false, sprintf('rows="5" style="width: 300px;" %s', ($holiday->Status != 'Declined') ? 'disabled="disabled"' : ''));

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if(strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2))) < strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2)))) {
				$form->AddError('End Date cannot be before Start Date.', 'enddate');
			} elseif((strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2))) == strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2)))) && (($form->GetValue('startmeridiem') == 'PM') && ($form->GetValue('endmeridiem') == 'AM'))) {
				$form->AddError('End Date cannot be before Start Date.', 'enddate');
			}

			if($form->Valid) {
				$holiday->User->ID = $user->ID;
				$holiday->StartDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2));
				$holiday->StartMeridiem = $form->GetValue('startmeridiem');
				$holiday->EndDate = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2));
				$holiday->EndMeridiem = $form->GetValue('endmeridiem');
				$holiday->Notes = $form->GetValue('notes');
				$holiday->Status = $form->GetValue('status');
								
				if($form->GetValue('status') != $holiday->Status) {
					if($holiday->Status == 'Approved') {
						$holiday->Approve();

					} elseif($holiday->Status == 'Declined') {
						$holiday->Decline($form->GetValue('declined'));
						
					} else {
						$holiday->ApprovedOn = '0000-00-00 00:00:00';
						$holiday->ApprovedBy = 0;
						$holiday->DeclinedBecause = '';
						$holiday->DeclinedOn = '0000-00-00 00:00:00';
						$holiday->DeclinedBy = 0;
					}
				}
				
				$holiday->Update();
				
				$user->Recalculate();

				redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $user->ID));
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleDeclined = function(obj) {
			var e = document.getElementById(\'declined\');

			if(e) {
				if(obj.value == \'Declined\') {
					e.removeAttribute(\'disabled\');
				} else {
					e.setAttribute(\'disabled\', \'disabled\');
				}
			}
		}
		</script>');

	$page = new Page(sprintf('<a href="users.php">Users</a> &gt; <a href="%s?id=%d">Holidays</a> &gt; Update Holiday', $_SERVER['PHP_SELF'], $user->ID), sprintf('Update a holiday for %s.', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName))));
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Updating a holiday');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('holidayid');

	echo $window->Open();
	echo $window->AddHeader('Add holiday.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('startdate'), $form->GetHTML('startdate') . $form->GetHTML('startmeridiem'));
	echo $webForm->AddRow($form->GetLabel('enddate'), $form->GetHTML('enddate') . $form->GetHTML('endmeridiem'));
	echo $webForm->AddRow($form->GetLabel('notes'), $form->GetHTML('notes') . $form->GetIcon('notes'));
	echo $webForm->AddRow($form->GetLabel('status'), $form->GetHTML('status') . $form->GetIcon('status'));
	echo $webForm->AddRow($form->GetLabel('declined'), $form->GetHTML('declined') . $form->GetIcon('declined'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'user_holiday.php?id=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $user->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: users.php"));
	}

	$user = new User($_REQUEST['id']);

	$page = new Page(sprintf('<a href="users.php">Users</a> &gt; Holidays'), sprintf('Listing holiday records for %s.', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName))));
	$page->Display('header');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'User ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('year', 'Year', 'select', '0', 'numeric_unsigned', 1,11);
	$form->AddOption('year', '0', '');

	$data = new DataQuery(sprintf("SELECT DISTINCT(YEAR(Start_Date)) AS Year FROM user_holiday WHERE User_ID=%d ORDER BY Year ASC", mysql_real_escape_string($form->GetValue('id'))));
	while($data->Row) {
		$form->AddOption('year', $data->Row['Year'], $data->Row['Year']);

		$data->Next();
	}
	$data->Disconnect();

	$window = new StandardWindow("Filter holidays");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Filter holidays by year.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('year'), $form->GetHTML('year') . '<input type="submit" name="filter" value="filter" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

    echo '<br />';

	$table = new DataTable('holidays');
	$table->SetSQL(sprintf("SELECT *, CONCAT_WS(' ', DATE_FORMAT(DATE(Start_Date), '%%d/%%m/%%Y'), Start_Meridiem) AS Starting_Date, CONCAT_WS(' ', DATE_FORMAT(DATE(End_Date), '%%d/%%m/%%Y'), End_Meridiem) AS Ending_Date, FORMAT(DATEDIFF(End_Date, Start_Date)+1+IF(Start_Meridiem='AM', 0, -0.5)+IF(End_Meridiem='PM', 0, -0.5), 1) AS Days FROM user_holiday WHERE User_ID=%d %s", mysql_real_escape_string($form->GetValue('id')), ($form->GetValue('year') > 0) ? sprintf("AND YEAR(Start_Date)=%d", mysql_real_escape_string($form->GetValue('year'))) : ''));
	$table->AddField("ID", "User_Holiday_ID", "left");
	$table->AddField("Created On", "Created_On", "left");
	$table->AddField("Start Date", "Starting_Date", "left");
	$table->AddField("End Date", "Ending_Date", "left");
	$table->AddField("Days", "Days", "right");
	$table->AddField("Status", "Status", "left");
	$table->AddLink("user_holiday.php?action=approve&holidayid=%s", "<img src=\"./images/button-tick.gif\" alt=\"Approve Holidays\" border=\"0\">", "User_Holiday_ID", true, false, array('Status', '==', 'Pending'));
	$table->AddLink("javascript:popUrl('user_holiday_print.php?id=%s', 800, 600);", "<img src=\"./images/icon_print_1.gif\" alt=\"Print Authorisation\" border=\"0\">", "User_Holiday_ID", true, false, array('Status', '==', 'Approved'));
	$table->AddLink("user_holiday.php?action=update&holidayid=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "User_Holiday_ID");
	$table->AddLink("javascript:confirmRequest('user_holiday.php?action=remove&holidayid=%s','Are you sure you want to remove this item?');", "<img src=\"./images/button-cross.gif\" alt=\"Remove\" border=\"0\">", "User_Holiday_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	?>

	<br />

	<table width="100%">
		<tr>
			<td align="left">
				<input type="button" name="add" value="add holiday" class="btn" onclick="window.location.href='user_holiday.php?action=add&id=<?php echo $user->ID; ?>';">
			</td>
			<td align="right">
                <input type="button" name="view" value="view calendar" class="btn" onclick="window.location.href='user_holiday_calendar.php?id=<?php echo $user->ID; ?>';">
			</td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}