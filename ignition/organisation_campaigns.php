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
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

	$contact = new Contact($_REQUEST['ocid']);

	$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Campaigns for %s contacts', $contact->ID, $contact->Organisation->Name, $contact->Organisation->Name), sprintf('Below is the campaign roster for all contacts of %s.', $contact->Organisation->Name));
	$page->Display('header');

	$sql = sprintf("SELECT cp.Title, cc.Campaign_Contact_ID, cp.Campaign_ID, cc.Created_On, p.Name_First, p.Name_Last FROM campaign_contact AS cc INNER JOIN contact AS c ON cc.Contact_ID=c.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID INNER JOIN campaign AS cp ON cc.Campaign_ID=cp.Campaign_ID WHERE c.Parent_Contact_ID=%d", mysql_real_escape_string($contact->ID));
	$table = new DataTable("campaigns");
	$table->SetSQL($sql);
	$table->AddField('Added Date', 'Created_On', 'left');
	$table->AddField('First Name', 'Name_First', 'left');
	$table->AddField('Last Name', 'Name_Last', 'left');
	$table->AddField('Campaign', 'Title', 'left');
	$table->AddLink("campaign_profile.php?id=%s","<img src=\"./images/folderopen.gif\" alt=\"Open Campaign Details\" border=\"0\">","Campaign_ID");
	$table->AddLink("javascript:confirmRequest('organisation_campaigns.php?action=remove&id=%s&ocid=".$contact->ID."','Are you sure you want to remove this contact from this campaign?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Campaign_Contact_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	?>

	<strong>Add Contact to Campaign:</strong>
	<select onchange="window.self.location.href='contact_campaigns.php?action=add&cid=' + this.value;">
		<option value="">-- Select Contact--</option>
		<?php
		$data = new DataQuery(sprintf("SELECT c.Contact_ID, p.Name_First, p.Name_Last FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE c.Parent_Contact_ID=%d ORDER BY p.Name_First, p.Name_Last ASC", mysql_real_escape_string($contact->ID)));
		while($data->Row) {
			echo sprintf('<option value="%d">%s</option>', $data->Row['Contact_ID'], trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])));
			$data->Next();
		}
		$data->Disconnect();
		?>
	</select>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
}

function update(){
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');

	if(isset($_REQUEST['id'])) {
		$contact = new CampaignContact();
		$contact->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s?ocid=%d", $_SERVER['PHP_SELF'], $_REQUEST['ocid']));
}