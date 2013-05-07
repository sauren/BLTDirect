<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "duplicate"){
	$session->Secure(3);
	duplicate();
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignEvent.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: campaigns.php"));
	}

	if(isset($_REQUEST['eid']) && is_numeric($_REQUEST['eid'])){
		$contact = new CampaignEvent();
		$contact->Delete($_REQUEST['eid']);
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $_REQUEST['id']));
}

function duplicate(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignEvent.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: campaigns.php"));
	}

	if(isset($_REQUEST['eid']) && is_numeric($_REQUEST['eid'])){
		$event = new CampaignEvent($_REQUEST['eid']);
		$event->IsAutomatic = 'N';
		$event->Add();
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $_REQUEST['id']));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignEvent.php');

	$campaign = new Campaign();

	if(!$campaign->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Campaign ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('type', 'Event Type', 'select', '', 'anything', 1, 1, true);
	$form->AddOption('type', '', '-- Select -- ');
	$form->AddOption('type', 'E', 'Email');
	$form->AddOption('type', 'L', 'Letter');
	$form->AddOption('type', 'F', 'Fax');
	$form->AddOption('type', 'P', 'Phone');
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 128, true);
	$form->AddField('isdefault', 'Is Active by Default', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('isdated', 'Is Dated Event', 'checkbox', 'Y', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$event = new CampaignEvent();
			$event->Campaign->ID = $campaign->ID;
			$event->Type = $form->GetValue('type');
			$event->Title = $form->GetValue('title');
			$event->IsDefault = $form->GetValue('isdefault');
			$event->IsDated = $form->GetValue('isdated');
			$event->Add();

			redirect(sprintf("Location: campaign_events.php?action=update&id=%d&eid=%d", $campaign->ID, $event->ID));
		}
	}

	$page = new Page(sprintf('<a href="campaign_profile.php?id=%d">Campaign Profile</a> &gt; <a href="campaign_events.php?id=%d">Edit Events</a> &gt; Add New Event', $campaign->ID, $campaign->ID),'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Event');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type').$form->GetIcon('type'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title').$form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('isdefault'), $form->GetHTML('isdefault').$form->GetIcon('isdefault'));
	echo $webForm->AddRow($form->GetLabel('isdated'), $form->GetHTML('isdated').$form->GetIcon('isdated'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'campaign_events.php?id=%d\';"> <input type="submit" name="continue" value="continue" class="btn" tabindex="%s">', $campaign->ID, $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignEvent.php');

	$campaign = new Campaign();
	if(!$campaign->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$event = new CampaignEvent();
	if(!$event->Get($_REQUEST['eid'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('direct', 'Direct', 'hidden', 'campaign_events.php', 'anything', 1, 255, false);
	$form->AddField('id', 'Campaign ID', 'hidden', $campaign->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('eid', 'Event ID', 'hidden', $event->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Import Template', 'hidden', 0, 'numeric_unsigned', 1, 11, false);
	$form->AddField('title', 'Title', 'text', $event->Title, 'anything', 1, 128, true);
	$form->AddField('isdefault', 'Is Active by Default', 'checkbox', $event->IsDefault, 'boolean', 1, 1, false);
	$form->AddField('template', 'Template', 'textarea', $event->Template, 'anything', 1, 2000, false, 'style="width:100%;" rows="20"');

	if($event->Type == 'E') {
		$form->AddField('isautomatic', 'Is Triggered Automatically', 'checkbox', $event->IsAutomatic, 'boolean', 1, 1, false);
		$form->AddField('isautomaticdisabling', 'Disable Trigger On Complete', 'checkbox', $event->IsAutomaticDisabling, 'boolean', 1, 1, false);
		$form->AddField('subject', 'Subject', 'text', $event->Subject, 'anything', 1, 255, false, 'style="width:100%;"');
		$form->AddField('isbcc', 'Is Blind Carbon Copy (BCC)', 'checkbox', $event->IsBcc, 'boolean', 1, 1, false);
		$form->AddField('bcccount', 'Maximum BCC Count', 'text', $event->MaximumBccCount, 'numeric_unsigned', 1, 11);
		$form->AddField('fromaddress', 'From Address', 'text', $event->FromAddress, 'paragraph', 1, 255, false);
		$form->AddField('queuerate', 'Queue Rate', 'text', $event->QueueRate, 'numeric_unsigned', 1, 11);
	}

	if($event->IsDated == 'Y') {
		$value = ($event->Scheduled == 0) ? time() : $event->Scheduled;
		$form->AddField('scheduled', 'Scheduled', 'text', date('d/m/Y', $value), 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	} else {
		$a = periodToArray($event->Scheduled);
		$form->AddField('months', 'Scheduled: Months', 'select', $a['month'], 'numeric_unsigned', 1, 11, true);
		$form->AddField('days', 'Scheduled: Days', 'select', $a['day'], 'numeric_unsigned', 1, 11, true);

		for($i = 0; $i<12; $i++) {
			$form->AddOption('months', $i, $i);
		}

		for($i = 0; $i<31; $i++) {
			$form->AddOption('days', $i, $i);
		}
	}

	$form->AddField('owner', 'Owner', 'select', $event->OwnedBy, 'numeric_unsigned', 1, 11);
	$form->AddOption('owner', '0', '');

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('owner', $data->Row['User_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()) {
			$event->Title = $form->GetValue('title');
			$event->IsDefault = $form->GetValue('isdefault');
			$event->Template = $form->GetValue('template');
			$event->OwnedBy = $form->GetValue('owner');

			if($event->Type == 'E') {
				$event->IsAutomatic = $form->GetValue('isautomatic');
				$event->IsAutomaticDisabling = $form->GetValue('isautomaticdisabling');
				$event->IsBcc = $form->GetValue('isbcc');
				$event->Subject = $form->GetValue('subject');
				$event->MaximumBccCount = $form->GetValue('bcccount');
				$event->FromAddress = $form->GetValue('fromaddress');
				$event->QueueRate = $form->GetValue('queuerate');
			}

			if($event->IsDated == 'Y') {
				$event->Scheduled = strtotime(date('Y-m-d 00:00:00', mktime(0, 0, 0, substr($form->GetValue('scheduled'), 3, 2), substr($form->GetValue('scheduled'), 0, 2), substr($form->GetValue('scheduled'), 6, 4))));
			} else {
				$event->Scheduled = ($form->GetValue('months') * (365.25 / 12) * 86400) + ($form->GetValue('days') * 86400);
			}

			$event->Update();

			redirect(sprintf("Location: %s?id=%d", $form->GetValue('direct'), $campaign->ID));
		}
	}

	$script = '<script language="javascript" type="text/javascript">
		var getDocumentNodeCallback = function() {
			changeTemplate();
		}

		var templateResponse = function(response) {
			tinyMCE.execInstanceCommand(\'mceFocus\', false, \'template\');
			tinyMCE.activeEditor.setContent(response);
		}

		var request = new HttpRequest();
		request.setCaching(false);
		request.setHandlerResponse(templateResponse);

		var changeTemplate = function() {
			var documentid = document.getElementById(\'parent\').value;

			request.abort();
		    request.post(\'lib/ajax/document.php\', "documentid="+documentid);
		}
		</script>';

	$page = new Page(sprintf('<a href="campaign_profile.php?id=%d">Campaign Profile</a> &gt; <a href="campaign_events.php?id=%d">Edit Events</a> &gt; Edit Event', $campaign->ID, $campaign->ID),'Please complete the form below.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
	$page->AddToHead($script);
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Edit Event');
	$webForm = new StandardForm();

	echo '<span style="display: none;" id="parentCaption"></span>';

	$type = ($event->Type == 'E') ? 'Email' : (($event->Type == 'L') ? 'Letter' : (($event->Type == 'F') ? 'Fax' : (($event->Type == 'P') ? 'Phone' : sprintf('<em>%s</em>', $event->Type))));

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('direct');
	echo $form->GetHTML('id');
	echo $form->GetHTML('eid');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('Event Type', $type);
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title').$form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('isdefault'), $form->GetHTML('isdefault').$form->GetIcon('isdefault'));
	echo $webForm->AddRow('Is Dated Event', ($event->IsDated == 'Y') ? 'Yes' : 'No');

	if($event->Type == 'E') {
		echo $webForm->AddRow($form->GetLabel('isautomatic'), $form->GetHTML('isautomatic').$form->GetIcon('isautomatic'));
		echo $webForm->AddRow($form->GetLabel('isautomaticdisabling'), $form->GetHTML('isautomaticdisabling').$form->GetIcon('isautomaticdisabling'));
		echo $webForm->AddRow($form->GetLabel('isbcc'), $form->GetHTML('isbcc').$form->GetIcon('isbcc'));
		echo $webForm->AddRow($form->GetLabel('bcccount'), $form->GetHTML('bcccount').$form->GetIcon('bcccount'));
	}

	if($event->IsDated == 'Y') {
		echo $webForm->AddRow($form->GetLabel('scheduled'), $form->GetHTML('scheduled').$form->GetIcon('scheduled'));
	} else {
		echo $webForm->AddRow('Scheduled', $form->GetHTML('months') . ' Months since contact association.<br /><br />' . $form->GetHTML('days') . ' Days since contact association.');
	}

	if($event->Type == 'E') {
		echo $webForm->AddRow($form->GetLabel('subject'), $form->GetHTML('subject').$form->GetIcon('subject'));
	}

	$tmpParentTxt = ' <a href="javascript:popUrl(\'documents.php?action=getnode&callback=true\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';

	echo $webForm->AddRow($form->GetLabel('template').$tmpParentTxt, $form->GetHTML('template'));

	if($event->Type == 'E') {
		echo $webForm->AddRow($form->GetLabel('queuerate'), $form->GetHTML('queuerate').$form->GetIcon('queuerate'));
		echo $webForm->AddRow($form->GetLabel('fromaddress'), $form->GetHTML('fromaddress').$form->GetIcon('fromaddress'));
	}

	echo $webForm->AddRow($form->GetLabel('owner'), $form->GetHTML('owner').$form->GetIcon('owner'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'campaign_events.php?id=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $campaign->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');

	$campaign = new Campaign();
	if(!$campaign->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: campaigns.php"));
	}

	$page = new Page(sprintf('<a href="campaign_profile.php?id=%d">Campaign Profile</a> &gt; Edit Events', $campaign->ID),'This are allows you to manage events for this campaign.');
	$page->Display('header');
	?>

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th nowrap="nowrap" class="dataHeadOrdered">ID#</th>
				<th nowrap="nowrap">Event Type</th>
				<th nowrap="nowrap">Scheduled</th>
				<th nowrap="nowrap">Type</th>
				<th nowrap="nowrap">Title</th>
				<th nowrap="nowrap">Is Default</th>
				<th nowrap="nowrap">Is Automatic</th>
				<th nowrap="nowrap">Is Automatic Disabling</th>
				<th nowrap="nowrap">Is BCC</th>
				<th nowrap="nowrap">Max BCC</th>
				<th nowrap="nowrap">Created On</th>
				<th colspan="3">&nbsp;</th>
			</tr>
		</thead>
		<tbody>

			<?php
			$data = new DataQuery(sprintf("SELECT * FROM campaign_event WHERE Campaign_ID=%d ORDER BY Is_Dated, Scheduled ASC", mysql_real_escape_string($campaign->ID)));
			if($data->TotalRows > 0) {
				while($data->Row) {
					$type = ($data->Row['Type'] == 'E') ? 'Email' : (($data->Row['Type'] == 'L') ? 'Letter' : (($data->Row['Type'] == 'F') ? 'Fax' : (($data->Row['Type'] == 'P') ? 'Phone' : sprintf('<em>%s</em>', $data->Row['Type']))));
					$scheduled = ($data->Row['Is_Dated'] == 'Y') ? cDatetime(date('Y-m-d 00:00:00', $data->Row['Scheduled']), 'shortdate') : ucwords(periodToString($data->Row['Scheduled']));
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td class="dataOrdered" align="left"><?php echo $data->Row['Campaign_Event_ID']; ?></td>
						<td align="left"><?php echo ($data->Row['Is_Dated'] == 'Y') ? 'Dated' : 'Timed'; ?></td>
						<td align="left"><?php echo $scheduled; ?></td>
						<td align="left"><?php echo $type; ?></td>
						<td align="left"><?php echo $data->Row['Title']; ?></td>
						<td align="center"><?php echo $data->Row['Is_Default']; ?></td>
						<td align="center"><?php echo ($data->Row['Type'] == 'E') ? $data->Row['Is_Automatic'] : 'N/A'; ?></td>
						<td align="center"><?php echo ($data->Row['Type'] == 'E') ? $data->Row['Is_Automatic_Disabling'] : 'N/A'; ?></td>
						<td align="center"><?php echo ($data->Row['Type'] == 'E') ? $data->Row['Is_Bcc'] : 'N/A'; ?></td>
						<td align="left"><?php echo ($data->Row['Type'] == 'E') ? $data->Row['Maximum_Bcc_Count'] : 'N/A'; ?></td>
						<td align="left"><?php echo cDatetime($data->Row['Created_On'], 'shortdatetime'); ?></td>
						<td nowrap align="center" width="16"><a href="campaign_events.php?action=duplicate&id=<?php print $campaign->ID; ?>&eid=<?php print $data->Row['Campaign_Event_ID']; ?>"><img src="./images/icon_pages_1.gif" alt="Duplicate Event" border="0"></a></td>
						<td nowrap align="center" width="16"><a href="campaign_events.php?action=update&id=<?php print $campaign->ID; ?>&eid=<?php print $data->Row['Campaign_Event_ID']; ?>"><img src="./images/icon_edit_1.gif" alt="Edit Event" border="0"></a></td>
						<td nowrap align="center" width="16"><a href="javascript:confirmRequest('campaign_events.php?action=remove&confirm=true&id=<?php print $campaign->ID; ?>&eid=<?php print $data->Row['Campaign_Event_ID']; ?>','Are you sure you want to remove this event from this campaign?');"><img src="./images/aztector_6.gif" alt="Remove" border="0"></a></td>
					</tr>

					<?php
					$data->Next();
				}
			} else {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td colspan="10">No Records Found</td>
				</tr>

				<?php
			}
			$data->Disconnect();
			?>

		</tbody>
	</table><br />

	<?php
	echo sprintf('<input type="button" name="add" value="add event" class="btn" onclick="window.location.href=\'campaign_events.php?action=add&id=%d\'" />', $campaign->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}