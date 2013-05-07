<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

	$contact = new Contact($_REQUEST['cid']);

	$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));

	$customer = new Customer($data->Row['Customer_ID']);
	$customer->Contact->Get();

	$data->Disconnect();

	$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Contact Campaigns', $contact->ID, $contact->Person->Name, $contact->Person->LastName), 'Below is a list of campaigns for this contact.');
	$page->Display('header');

	$sql = sprintf("SELECT cp.Title, cc.Campaign_Contact_ID, cp.Campaign_ID, cc.Created_On FROM campaign_contact AS cc INNER JOIN campaign AS cp ON cc.Campaign_ID=cp.Campaign_ID WHERE cc.Contact_ID=%d", mysql_real_escape_string($contact->ID));
	$table = new DataTable("campaigns");
	$table->SetSQL($sql);
	$table->AddField('Added Date', 'Created_On', 'left');
	$table->AddField('Campaign', 'Title', 'left');
	$table->AddLink("campaign_profile.php?id=%s","<img src=\"./images/folderopen.gif\" alt=\"Open Campaign Details\" border=\"0\">","Campaign_ID");
	$table->AddLink("javascript:confirmRequest('contact_campaigns.php?action=remove&id=%s&cid=".$contact->ID."','Are you sure you want to remove this contact from this campaign?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Campaign_Contact_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";

	echo sprintf('<input name="campaign" type="button" id="campaign" value="add to campaign" class="btn" onclick="window.self.location.href = \'contact_campaigns.php?action=add&cid=%d\';" />', $contact->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');

	$contact = new Contact($_REQUEST['cid']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Contact ID', 'hidden', '', 'numeric_unsigned', 1, 11);

	$ownerId = isset($_REQUEST['owner']) ? $_REQUEST['owner'] : 0;

	if($ownerId > 0) {
		$form->AddField('owner', 'Owned By', 'hidden', $ownerId, 'numeric_unsigned', 1, 11);
	} else {
		$form->AddField('owner', 'Owned By', 'select', '', 'numeric_unsigned', 1, 11);
		$form->AddOption('owner', '', '');

		$data = new DataQuery(sprintf("SELECT u.User_ID, p.Name_First, p.Name_Last FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY p.Name_First, p.Name_Last ASC"));
		while($data->Row) {
			$form->AddOption('owner', $data->Row['User_ID'], trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])));
			$data->Next();
		}
		$data->Disconnect();
	}

	$form->AddField('campaign', 'Campaign', 'select', '', 'numeric_unsigned', 1, 11, true);
	$form->AddOption('campaign', '', '');

	$campaigns = '';

	$data = new DataQuery(sprintf("SELECT Campaign_ID FROM campaign_contact WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
	while($data->Row) {
		$campaigns .= sprintf(' Campaign_ID<>%d AND ', $data->Row['Campaign_ID']);

		$data->Next();
	}
	$data->Disconnect();

	if(strlen($campaigns) > 0) {
		$campaigns = sprintf('WHERE %s', substr($campaigns, 0, -5));
	}

	$data = new DataQuery(sprintf("SELECT * FROM campaign %s ORDER BY Title ASC", mysql_real_escape_string($campaigns)));
	while($data->Row) {
		$form->AddOption('campaign', $data->Row['Campaign_ID'], $data->Row['Title']);
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$campaignContact = new CampaignContact();
			$campaignContact->Contact->ID = $contact->ID;
			$campaignContact->Campaign->ID = $form->GetValue('campaign');
			$campaignContact->OwnedBy = $form->GetValue('owner');
			$campaignContact->Add();

			redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $contact->ID));
		}
	}

	$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; <a href="%s?cid=%d">Contact Campaigns</a> &gt; Add Campaign', $contact->ID, $contact->Person->Name, $contact->Person->LastName, $_SERVER['PHP_SELF'], $contact->ID), 'Add this contact to a campaign.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add to Campaign');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cid');
	if($ownerId > 0) {
		echo $form->GetHTML('owner');
	}
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('campaign'), $form->GetHTML('campaign') . $form->GetIcon('campaign'));
	if($ownerId == 0) {
		echo $webForm->AddRow($form->GetLabel('owner'), $form->GetHTML('owner') . $form->GetIcon('owner'));
	}
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="javascript:history.back(1);"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $contact->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');

	if(isset($_REQUEST['id'])) {
		$contact = new CampaignContact();
		$contact->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $_REQUEST['cid']));
}