<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "performance"){
	$session->Secure(3);
	performance();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$campaign = new Campaign();
		$campaign->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 255, true, 'style="width: 300px;"');
	$form->AddField('description', 'Description', 'textarea', '', 'anything', 1, 1024, false, 'rows="5" style="width: 300px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$campaign = new Campaign();
			$campaign->Title = $form->GetValue('title');
			$campaign->Description = $form->GetValue('description');
			$campaign->Add();

			redirect(sprintf("Location: campaign_profile.php?id=%d", $campaign->ID));
		}
	}

	$page = new Page('<a href="campaigns.php">Campaigns</a> &gt; Add New Campaign','Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Campaign');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'campaigns.php\';"> <input type="submit" name="continue" value="continue" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Campaigns','This area allows you to view campaign for your system.');
	$page->Display('header');

	$table = new DataTable('campaign');
	$table->SetSQL("SELECT * FROM campaign");
	$table->AddField('ID#', 'Campaign_ID', 'right');
	$table->AddField('Title', 'Title', 'left');
	$table->AddField('Description', 'Description', 'left');
	$table->AddField('Created On', 'Created_On', 'left');
	$table->AddLink("campaigns.php?action=performance&id=%s", "<img src=\"./images/icon_search_1.gif\" alt=\"View Performance\" border=\"0\">", "Campaign_ID");
	$table->AddLink("campaign_profile.php?id=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Campaign Profile\" border=\"0\">", "Campaign_ID");
	$table->AddLink("javascript:confirmRequest('campaigns.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this campaign?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Campaign_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Campaign_ID");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add new campaign" class="btn" onclick="window.location.href=\'campaigns.php?action=add\'" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function performance() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');

	$campaign = new Campaign();

	if(!$campaign->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: campaigns.php"));
	}

	$figures = array();

	$data = new DataQuery(sprintf("SELECT * FROM campaign_event WHERE Campaign_ID=%d AND Type='E' AND Is_Bcc='N'", mysql_real_escape_string($campaign->ID)));
	while($data->Row) {
		$figures[] = array(	'EventID' => $data->Row['Campaign_Event_ID'],
							'EventTitle' => $data->Row['Title'],
							'Data' => array());

		$data->Next();
	}
	$data->Disconnect();

	for($i = 0; $i < count($figures); $i++) {
		$data = new DataQuery(sprintf("SELECT COUNT(cce.Campaign_Contact_Event_ID) AS Count FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y'", mysql_real_escape_string($figures[$i]['EventID'])));
		$figures[$i]['Data']['Recipients'] = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(cce.Campaign_Contact_Event_ID) AS Count FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y' AND cce.Is_Complete='Y'", mysql_real_escape_string($figures[$i]['EventID'])));
		$figures[$i]['Data']['Completed'] = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(cce.Campaign_Contact_Event_ID) AS Count FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y' AND cce.Is_Email_Sent='Y'", mysql_real_escape_string($figures[$i]['EventID'])));
		$figures[$i]['Data']['Sent'] = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(cce.Campaign_Contact_Event_ID) AS Count FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y' AND cce.Is_Email_Failed='Y'", mysql_real_escape_string($figures[$i]['EventID'])));
		$figures[$i]['Data']['Failed'] = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(cce.Campaign_Contact_Event_ID) AS Count FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y' AND cce.Is_Email_Viewed='Y'", mysql_real_escape_string($figures[$i]['EventID'])));
		$figures[$i]['Data']['Viewed'] = $data->Row['Count'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(cce.Campaign_Contact_Event_ID) AS Count FROM campaign_contact_event AS cce INNER JOIN campaign_contact AS cc ON cc.Campaign_Contact_ID=cce.Campaign_Contact_ID WHERE cce.Campaign_Event_ID=%d AND cce.Is_Active='Y' AND cce.Is_Email_Followed='Y'", mysql_real_escape_string($figures[$i]['EventID'])));
		$figures[$i]['Data']['Followed'] = $data->Row['Count'];
		$data->Disconnect();
	}

	$page = new Page(sprintf('<a href="campaigns.php">Campaigns</a> &gt; Campaign Performance (%s [#%s])', $campaign->Title, $campaign->ID), 'View the performance of this campaign.');
	$page->Display('header');

	for($i = 0; $i < count($figures); $i++) {
		?>

		<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
		 <thead>
		 	<tr>
				<th colspan="4">Performance Visualiser</th>
			</tr>
		 </thead>
		 <tbody>
		   <tr>
		   	 <td style="background-color: #eee;">Event</td>
		   	 <td style="background-color: #eee;" colspan="3"><?php echo $figures[$i]['EventTitle']; ?></td>
		   </tr>
		   <tr>
		   	 <td width="10%">Receipients</td>
		   	 <td width="73%"><div style="width: 100%; background-color: #143CC0;">&nbsp;</div></td>
		   	 <td width="10%" align="right"><?php echo number_format($figures[$i]['Data']['Recipients'], 0, '.', ','); ?></td>
		   	 <td width="7%" align="right">100.00%</td>
		   </tr>
		   <tr>
		   	 <td>Completed</td>
		   	 <td><div style="width: <?php echo ($figures[$i]['Data']['Completed'] / $figures[$i]['Data']['Recipients']) * 100; ?>%; background-color: #2DB615;">&nbsp;</div></td>
		   	 <td align="right"><?php echo number_format($figures[$i]['Data']['Completed'], 0, '.', ','); ?></td>
		   	 <td align="right"><?php echo number_format(($figures[$i]['Data']['Completed'] / $figures[$i]['Data']['Recipients']) * 100, 2, '.', ''); ?>%</td>
		   </tr>
		   <tr>
		   	 <td>Sent</td>
		   	 <td><div style="width: <?php echo ($figures[$i]['Data']['Sent'] / $figures[$i]['Data']['Recipients']) * 100; ?>%; background-color: #B6AD15">&nbsp;</div></td>
		   	 <td align="right"><?php echo number_format($figures[$i]['Data']['Sent'], 0, '.', ','); ?></td>
		   	 <td align="right"><?php echo number_format(($figures[$i]['Data']['Sent'] / $figures[$i]['Data']['Recipients']) * 100, 2, '.', ''); ?>%</td>
		   </tr>
		   <tr>
		   	 <td>Failed</td>
		   	 <td><div style="width: <?php echo ($figures[$i]['Data']['Failed'] / $figures[$i]['Data']['Recipients']) * 100; ?>%; background-color: #9E1412;">&nbsp;</div></td>
		   	 <td align="right"><?php echo number_format($figures[$i]['Data']['Failed'], 0, '.', ','); ?></td>
		   	 <td align="right"><?php echo number_format(($figures[$i]['Data']['Failed'] / $figures[$i]['Data']['Recipients']) * 100, 2, '.', ''); ?>%</td>
		   </tr>
		   <tr>
		   	 <td>Viewed</td>
		   	 <td><div style="width: <?php echo ($figures[$i]['Data']['Viewed'] / $figures[$i]['Data']['Recipients']) * 100; ?>%; background-color: #2DB615;">&nbsp;</div></td>
		   	 <td align="right"><?php echo number_format($figures[$i]['Data']['Viewed'], 0, '.', ','); ?></td>
		   	 <td align="right"><?php echo number_format(($figures[$i]['Data']['Viewed'] / $figures[$i]['Data']['Recipients']) * 100, 2, '.', ''); ?>%</td>
		   </tr>
		   <tr>
		   	 <td>Followed</td>
		   	 <td><div style="width: <?php echo ($figures[$i]['Data']['Followed'] / $figures[$i]['Data']['Recipients']) * 100; ?>%; background-color: #2DB615;">&nbsp;</div></td>
		   	 <td align="right"><?php echo number_format($figures[$i]['Data']['Followed'], 0, '.', ','); ?></td>
		   	 <td align="right"><?php echo number_format(($figures[$i]['Data']['Followed'] / $figures[$i]['Data']['Recipients']) * 100, 2, '.', ''); ?>%</td>
		   </tr>
		 </tbody>
		</table><br />

		<?php
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>