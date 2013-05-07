<?php
ini_set('max_execution_time', '3600');
ini_set('memory_limit', '1024M');

require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "addgroup"){
	$session->Secure(3);
	addgroup();
	exit;
} elseif($action == "addcontact"){
	$session->Secure(3);
	addcontact();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "removeall"){
	$session->Secure(3);
	removeall();
	exit;
} elseif($action == "addall"){
	$session->Secure(3);
	addall();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: campaigns.php"));
	}

	if(isset($_REQUEST['aid']) && is_numeric($_REQUEST['aid'])){
		$contact = new CampaignContact();
		$contact->Delete($_REQUEST['aid']);
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $_REQUEST['id']));
}

function removeall() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');
	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: campaigns.php"));
	}
	$data = new DataQuery(sprintf("DELETE campaign_contact_event FROM campaign_contact, campaign_contact_event WHERE campaign_contact.Campaign_Contact_ID=campaign_contact_event.Campaign_Contact_ID AND campaign_contact.Campaign_ID=%d", $_REQUEST['id']));
	$data->Disconnect();

	$contact = new CampaignContact();
	$contact->DeleteByCampaign($_REQUEST['id']);

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $_REQUEST['id']));
}

function addall() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: campaigns.php"));
	}

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_group_contact SELECT Contact_ID FROM campaign_contact WHERE Campaign_ID=%d", $_REQUEST['id']));
	new DataQuery(sprintf("CREATE INDEX Contact_ID ON temp_group_contact (Contact_ID)"));

	$campaignContact = new CampaignContact();
	$campaignContact->Campaign->ID = $_REQUEST['id'];


	$data = new DataQuery(sprintf("SELECT c.Contact_ID FROM contact AS c LEFT JOIN temp_group_contact AS tgc ON tgc.Contact_ID=c.Contact_ID WHERE tgc.Contact_ID IS NULL AND c.Contact_Type='I'"));
	while($data->Row) {
		$campaignContact->Contact->ID = $data->Row['Contact_ID'];
		$campaignContact->Add();
		$data->Next();
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $_REQUEST['id']));
}

function addcontact() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');

	$campaign = new Campaign();
	if(!$campaign->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$contacts = array();

	foreach($_REQUEST as $key=>$contact) {
		if(stristr(substr($key, 0, 7), 'select_')) {
			$contacts[] = substr($key, 7, strlen($key));
		}
	}

	$campaignContact = new CampaignContact();
	$campaignContact->Campaign->ID = $campaign->ID;

	foreach($contacts as $contact) {
		$campaignContact->Contact->ID = $contact;
		$campaignContact->Add();
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $campaign->ID));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');

	$campaign = new Campaign();

	if(!$campaign->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Campaign ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('contactid', 'Contact ID', 'text', '', 'numeric_unsigned', 1, 11, false);
	$form->AddField('fname', 'First Name', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('lname', 'Last Name', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('org', 'Organisation', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('email', 'Email Address', 'text', '', 'paragraph', 1, 255,false);
	$form->AddField('phone', 'Phone Number', 'text', '', 'telephone', 1, 32, false);
	$form->AddField('mobile', 'Mobile Number', 'text', '', 'telephone', 1, 32, false);
	$form->AddField('fax', 'Fax Number', 'text', '', 'telephone', 1, 32, false);
	$form->AddField('postcode', 'Postcode', 'text', '', 'postcode', 1, 32, false);
	$form->AddField('address1', 'Property Name/Number', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('address2', 'Street', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('address3', 'Area', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('city', 'City', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('quotes', 'Quotes', 'select', '', 'anything', 1, 128, false);
	$form->AddOption('quotes', '', '-- All --');
	$form->AddOption('quotes', 'Y', 'Yes');
	$form->AddOption('quotes', 'N', 'No');
	$form->AddField('orders', 'Orders', 'select', '', 'anything', 1, 128, false);
	$form->AddOption('orders', '', '-- All --');
	$form->AddOption('orders', 'Y', 'Yes');
	$form->AddOption('orders', 'N', 'No');
	$form->AddField('istemp', 'Is Temporary', 'select', 'N', 'anything', 1, 128, false);
	$form->AddOption('istemp', '', '');
	$form->AddOption('istemp', 'Y', 'Yes');
	$form->AddOption('istemp', 'N', 'No');
	$form->AddField('excludecontactid', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludefname', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludelname', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludeorg', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludeemail', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludephone', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludemobile', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludefax', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludepostcode', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludeaddress1', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludeaddress2', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludeaddress3', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('excludecity', 'Exclude', 'checkbox', 'N', 'boolean', 1, 1, false);

	$page = new Page(sprintf('<a href="campaign_profile.php?id=%d">Campaign Profile</a> &gt; <a href="campaign_recipients.php?id=%d">Edit Recipients</a> &gt; Add New Recipients', $campaign->ID, $campaign->ID),'This are allows you to add recipients for this campaign.');
	$page->Display('header');

	$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_group_contact SELECT Contact_ID FROM campaign_contact WHERE Campaign_ID=%d", $campaign->ID));
	$data->Disconnect();

	$data = new DataQuery(sprintf("CREATE INDEX Contact_ID ON temp_group_contact (Contact_ID)"));
	$data->Disconnect();

	$sql = '';
	$isTemporary = '';

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$isTemporary = $form->GetValue('istemp');

			$sql = sprintf("SELECT c.Contact_ID, c.Is_Active, c.Is_Customer, c.Is_Supplier, p.Name_First, p.Name_Initial, p.Name_Last, o.Org_Name FROM temp_contact AS c LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN address AS a ON a.Address_ID=p.Address_ID LEFT JOIN temp_group_contact AS tgc ON tgc.Contact_ID=c.Contact_ID WHERE tgc.Contact_ID IS NULL AND c.Contact_Type='I'");

			if(strlen($form->GetValue('contactid')) > 0) {
				if($form->GetValue('excludecontactid') == 'N') {
					$sql .= sprintf(" AND c.Contact_ID=%d", $form->GetValue('contactid'));
				} else {
					$sql .= sprintf(" AND c.Contact_ID<>%d", $form->GetValue('contactid'));
				}
			}

			if(strlen($form->GetValue('fname')) > 0) {
				if($form->GetValue('excludefname') == 'N') {
					$sql .= sprintf(" AND p.Name_First LIKE '%%%s%%'", $form->GetValue('fname'));
				} else {
					$sql .= sprintf(" AND p.Name_First NOT LIKE '%%%s%%'", $form->GetValue('fname'));
				}
			}

			if(strlen($form->GetValue('lname')) > 0) {
				if($form->GetValue('excludelname') == 'N') {
					$sql .= sprintf(" AND p.Name_Last LIKE '%%%s%%'", $form->GetValue('lname'));
				} else {
					$sql .= sprintf(" AND p.Name_Last NOT LIKE '%%%s%%'", $form->GetValue('lname'));
				}
			}

			if(strlen($form->GetValue('org')) > 0) {
				if($form->GetValue('excludeorg') == 'N') {
					$sql .= sprintf(" AND o.Org_Name LIKE '%%%s%%'", $form->GetValue('org'));
				} else {
					$sql .= sprintf(" AND o.Org_Name NOT LIKE '%%%s%%'", $form->GetValue('org'));
				}
			}

			if(strlen($form->GetValue('email')) > 0) {
				if($form->GetValue('excludeemail') == 'N') {
					$sql .= sprintf(" AND p.Email LIKE '%%%s%%'", $form->GetValue('email'));
				} else {
					$sql .= sprintf(" AND p.Email NOT LIKE '%%%s%%'", $form->GetValue('email'));
				}
			}

			if(strlen($form->GetValue('phone')) > 0) {
				if($form->GetValue('excludephone') == 'N') {
					$sql .= sprintf(" AND (REPLACE(p.Phone_1, ' ', '') LIKE REPLACE('%%%s%%', ' ', '') OR REPLACE(p.Phone_2, ' ', '') LIKE REPLACE('%%%s%%', ' ', ''))", $form->GetValue('phone'), $form->GetValue('phone'));
				} else {
					$sql .= sprintf(" AND (REPLACE(p.Phone_1, ' ', '') NOT LIKE REPLACE('%%%s%%', ' ', '') AND REPLACE(p.Phone_2, ' ', '') NOT LIKE REPLACE('%%%s%%', ' ', ''))", $form->GetValue('phone'), $form->GetValue('phone'));
				}
			}

			if(strlen($form->GetValue('mobile')) > 0) {
				if($form->GetValue('excludemobile') == 'N') {
					$sql .= sprintf(" AND REPLACE(p.Mobile, ' ', '') LIKE REPLACE('%%%s%%', ' ', '')", $form->GetValue('mobile'));
				} else {
					$sql .= sprintf(" AND REPLACE(p.Mobile, ' ', '') NOT LIKE REPLACE('%%%s%%', ' ', '')", $form->GetValue('mobile'));
				}
			}

			if(strlen($form->GetValue('fax')) > 0) {
				if($form->GetValue('excludefax') == 'N') {
					$sql .= sprintf(" AND REPLACE(p.Fax, ' ', '') LIKE REPLACE('%%%s%%', ' ', '')", $form->GetValue('fax'));
				} else {
					$sql .= sprintf(" AND REPLACE(p.Fax, ' ', '') NOT LIKE REPLACE('%%%s%%', ' ', '')", $form->GetValue('fax'));
				}
			}

			if(strlen($form->GetValue('address1')) > 0) {
				if($form->GetValue('excludeaddress1') == 'N') {
					$sql .= sprintf(" AND a.Address_Line_1 LIKE '%%%s%%'", $form->GetValue('address1'));
				} else {
					$sql .= sprintf(" AND a.Address_Line_1 NOT LIKE '%%%s%%'", $form->GetValue('address1'));
				}
			}

			if(strlen($form->GetValue('address2')) > 0) {
				if($form->GetValue('excludeaddress2') == 'N') {
					$sql .= sprintf(" AND a.Address_Line_2 LIKE '%%%s%%'", $form->GetValue('address2'));
				} else {
					$sql .= sprintf(" AND a.Address_Line_2 NOT LIKE '%%%s%%'", $form->GetValue('address2'));
				}
			}

			if(strlen($form->GetValue('address3')) > 0) {
				if($form->GetValue('excludeaddress3') == 'N') {
					$sql .= sprintf(" AND a.Address_Line_3 LIKE '%%%s%%'", $form->GetValue('address3'));
				} else {
					$sql .= sprintf(" AND a.Address_Line_3 NOT LIKE '%%%s%%'", $form->GetValue('address3'));
				}
			}

			if(strlen($form->GetValue('city')) > 0) {
				if($form->GetValue('excludecity') == 'N') {
					$sql .= sprintf(" AND a.City LIKE '%%%s%%'", $form->GetValue('city'));
				} else {
					$sql .= sprintf(" AND a.City NOT LIKE '%%%s%%'", $form->GetValue('city'));
				}
			}

			if(strlen($form->GetValue('postcode')) > 0) {
				if($form->GetValue('excludepostcode') == 'N') {
					$sql .= sprintf(" AND REPLACE(a.Zip, ' ', '') LIKE '%%%s%%'", str_replace(' ', '', $form->GetValue('postcode')));
				} else {
					$sql .= sprintf(" AND REPLACE(a.Zip, ' ', '') NOT LIKE REPLACE('%%%s%%', ' ', '')", $form->GetValue('postcode'));
				}
			}

			if(strlen($form->GetValue('orders')) > 0) {
				if($form->GetValue('orders') == 'Y') {
					$sql .= " AND 0<(SELECT COUNT(sso.Order_ID) FROM contact AS ssc LEFT JOIN customer AS sscu ON sscu.Contact_ID=ssc.Contact_ID LEFT JOIN orders AS sso ON sso.Customer_ID=sscu.Customer_ID WHERE ssc.Contact_ID=c.Contact_ID)";
				} elseif($form->GetValue('orders') == 'N') {
					$sql .= " AND 0=(SELECT COUNT(sso.Order_ID) FROM contact AS ssc LEFT JOIN customer AS sscu ON sscu.Contact_ID=ssc.Contact_ID LEFT JOIN orders AS sso ON sso.Customer_ID=sscu.Customer_ID WHERE ssc.Contact_ID=c.Contact_ID)";
				}
			}

			if(strlen($form->GetValue('quotes')) > 0) {
				if($form->GetValue('quotes') == 'Y') {
					$sql .= " AND 0<(SELECT COUNT(ssq.Quote_ID) FROM contact AS ssc LEFT JOIN customer AS sscu ON sscu.Contact_ID=ssc.Contact_ID LEFT JOIN quotes AS ssq ON ssq.Customer_ID=sscu.Customer_ID WHERE ssc.Contact_ID=c.Contact_ID)";
				} elseif($form->GetValue('quotes') == 'N') {
					$sql .= " AND 0=(SELECT COUNT(ssq.Quote_ID) FROM contact AS ssc LEFT JOIN customer AS sscu ON sscu.Contact_ID=ssc.Contact_ID LEFT JOIN quotes AS ssq ON ssq.Customer_ID=sscu.Customer_ID WHERE ssc.Contact_ID=c.Contact_ID)";
				}
			}

		} else {
			echo $form->GetError();
			echo "<br />";
		}
	}

	$form2 = new Form($_SERVER['PHP_SELF']);
	$form2->AddField('action', 'Action', 'hidden', 'true', 'addcontact', 10, 10);
	$form2->SetValue('action', 'addcontact');
	$form2->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form2->AddField('id', 'Campaign ID', 'hidden', '', 'numeric_unsigned', 1, 11);

	$contacts = array();

	if(!empty($sql)){
		new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_contact SELECT c.* FROM contact AS c %s", (strlen($isTemporary) > 0) ? sprintf("WHERE c.Is_Temporary='%s'", $isTemporary) : ''));

		$table = new DataTable('Search');
		$table->SetSQL($sql);
		$table->SetMaxRows(1000);
		$table->SetOrderBy('Contact_ID');
		$table->SetExtractVars();
		$table->Finalise();
		$table->ExecuteSQL();

		while($table->Table->Row) {
			$form2->AddField('select_'.$table->Table->Row['Contact_ID'], 'Select Contact', 'checkbox', 'N', 'boolean', 1, 1, false);

			$contactItem = array();
			$contactItem['Contact_ID'] = $table->Table->Row['Contact_ID'];
			$contactItem['Org_Name'] = $table->Table->Row['Org_Name'];
			$contactItem['Name_First'] = $table->Table->Row['Name_First'];
			$contactItem['Name_Last'] = $table->Table->Row['Name_Last'];
			$contactItem['Is_Customer'] = $table->Table->Row['Is_Customer'];
			$contactItem['Is_Supplier'] = $table->Table->Row['Is_Supplier'];
			$contactItem['Is_Active'] = $table->Table->Row['Is_Active'];

			$contacts[] = $contactItem;

			$table->Table->Next();
		}
		$table->Table->Disconnect();

		echo '<table width="100%"><tr><td valign="top">';
	}

	$window = new StandardWindow("Search for a Contact.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Search for contacts by any of the below fields.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('contactid'), $form->GetHTML('contactid') . $form->GetHTML('excludecontactid').$form->GetLabel('excludecontactid'));
	echo $webForm->AddRow($form->GetLabel('fname'), $form->GetHTML('fname') . $form->GetHTML('excludefname').$form->GetLabel('excludefname'));
	echo $webForm->AddRow($form->GetLabel('lname'), $form->GetHTML('lname') . $form->GetHTML('excludelname').$form->GetLabel('excludelname'));
	echo $webForm->AddRow($form->GetLabel('org'), $form->GetHTML('org') . $form->GetHTML('excludeorg').$form->GetLabel('excludeorg'));
	echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email') . $form->GetHTML('excludeemail').$form->GetLabel('excludeemail'));
	echo $webForm->AddRow($form->GetLabel('phone'), $form->GetHTML('phone') . $form->GetHTML('excludephone').$form->GetLabel('excludephone'));
	echo $webForm->AddRow($form->GetLabel('mobile'), $form->GetHTML('mobile') . $form->GetHTML('excludemobile').$form->GetLabel('excludemobile'));
	echo $webForm->AddRow($form->GetLabel('fax'), $form->GetHTML('fax') . $form->GetHTML('excludefax').$form->GetLabel('excludefax'));
	echo $webForm->AddRow($form->GetLabel('postcode'), $form->GetHTML('postcode') . $form->GetHTML('excludepostcode').$form->GetLabel('excludepostcode'));
	echo $webForm->AddRow($form->GetLabel('address1'), $form->GetHTML('address1') . $form->GetHTML('excludeaddress1').$form->GetLabel('excludeaddress1'));
	echo $webForm->AddRow($form->GetLabel('address2'), $form->GetHTML('address2') . $form->GetHTML('excludeaddress2').$form->GetLabel('excludeaddress2'));
	echo $webForm->AddRow($form->GetLabel('address3'), $form->GetHTML('address3') . $form->GetHTML('excludeaddress3').$form->GetLabel('excludeaddress3'));
	echo $webForm->AddRow($form->GetLabel('city'), $form->GetHTML('city') . $form->GetHTML('excludecity').$form->GetLabel('excludecity'));
	echo $webForm->AddRow($form->GetLabel('leadsource'), $form->GetHTML('leadsource'));
	echo $webForm->AddRow($form->GetLabel('orders'), $form->GetHTML('orders'));
	echo $webForm->AddRow($form->GetLabel('quotes'), $form->GetHTML('quotes'));
	echo $webForm->AddRow($form->GetLabel('istemp'), $form->GetHTML('istemp'));
	echo $webForm->AddRow('', '<input type="submit" name="searchButton" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	if(!empty($sql)){
		echo '</td><td width="10"></td><td valign="top">';

		echo $form2->Open();
		echo $form2->GetHTML('action');
		echo $form2->GetHTML('confirm');
		echo $form2->GetHTML('id');
		?>

		<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
			<thead>
				<tr>
					<th><input type="checkbox" name="checkall" id="checkall" onclick="checkUncheckAll(this)" /></th>
					<th class="dataHeadOrdered">ID#</th>
					<th>Organisation</th>
					<th nowrap="nowrap">First Name</th>
					<th nowrap="nowrap">Last Name</th>
					<th>Customer</th>
					<th>Supplier</th>
					<th>Active</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>

				<?php
				for($i=0;$i<count($contacts);$i++) {
				?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td align="left" width="16"><?php echo $form2->GetHTML('select_'.$contacts[$i]['Contact_ID']); ?></td>
						<td class="dataOrdered" align="left"><?php echo $contacts[$i]['Contact_ID']; ?></td>
						<td align="left"><?php echo $contacts[$i]['Org_Name']; ?>&nbsp;</td>
						<td align="left"><?php print $contacts[$i]['Name_First']; ?>&nbsp;</td>
						<td align="left"><?php print $contacts[$i]['Name_Last']; ?>&nbsp;</td>
						<td align="center"><?php print $contacts[$i]['Is_Customer']; ?></td>
						<td align="center"><?php print $contacts[$i]['Is_Supplier']; ?></td>
						<td align="center"><?php print $contacts[$i]['Is_Active']; ?></td>
						<td nowrap align="center" width="16"><a href="contact_profile.php?cid=<?php echo $contacts[$i]['Contact_ID']; ?>"><img src="images/icon_edit_1.gif" alt="Update Contact" border="0" /></a></td>
					</tr>

					<?php
				}
				?>

			</tbody>
		</table><br />

		<?php
		$table->DisplayNavigation();
		?>

		<br />
		<input type="submit" name="addcontact" value="add contacts" class="btn" />

		<?php
		echo $form2->Close();

		echo '</td></tr></table>';
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function addgroup(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignContact.php');

	$campaign = new Campaign();
	if(!$campaign->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addgroup', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Campaign ID', 'hidden', $campaign->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('group', 'Contact Group', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('group', '0', '-- Select -- ');

	$data = new DataQuery(sprintf("SELECT g.Contact_Group_ID, g.Name, COUNT(c.Contact_ID) AS Contacts FROM contact_group AS g LEFT JOIN contact_group_assoc AS c ON c.Contact_Group_ID=g.Contact_Group_ID GROUP BY g.Contact_Group_ID ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('group', $data->Row['Contact_Group_ID'], sprintf('%s (%d)', $data->Row['Name'], $data->Row['Contacts']));

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		$form->Validate();

		if($form->GetValue('group') == 0) {
			$form->AddError('Contact Group must have a selected value.', 'group');
		}

		if($form->Valid){
			$contact = new CampaignContact();
			$contact->Campaign->ID = $campaign->ID;

			new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_group_contact SELECT Contact_ID FROM campaign_contact WHERE Campaign_ID=%d", $campaign->ID));
			new DataQuery(sprintf("CREATE INDEX Contact_ID ON temp_group_contact (Contact_ID)"));

			$data = new DataQuery(sprintf("SELECT a.Contact_ID FROM contact_group_assoc AS a INNER JOIN contact AS c ON c.Contact_ID=a.Contact_ID LEFT JOIN temp_group_contact AS t ON t.Contact_ID=a.Contact_ID WHERE t.Contact_ID IS NULL AND a.Contact_Group_ID=%d", mysql_real_escape_string($form->GetValue('group'))));
			while($data->Row) {
				$contact->Contact->ID = $data->Row['Contact_ID'];
				$contact->Add();

				$data->Next();
			}
			$data->Disconnect();

			redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $campaign->ID));
		}
	}

	$page = new Page(sprintf('<a href="campaign_profile.php?id=%d">Campaign Profile</a> &gt; <a href="campaign_recipients.php?id=%d">Edit Recipients</a> &gt; Add New Recipients From Group', $campaign->ID, $campaign->ID),'This are allows you to add recipients for this campaign.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Recipients');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('group'), $form->GetHTML('group') . $form->GetIcon('group'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'campaign_recipients.php?id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $campaign->ID, $form->GetTabIndex()));
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
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$page = new Page(sprintf('<a href="campaign_profile.php?id=%d">Campaign Profile</a> &gt; Edit Recipients', $campaign->ID),'This are allows you to manage recipients for this campaign.');
	$page->Display('header');

	$table = new DataTable('recipients');
	$table->SetSQL(sprintf("SELECT cc.Campaign_Contact_ID, c.Contact_ID, c.Is_Active, c.Is_Customer, c.Is_Supplier, p.Name_First, p.Name_Initial, p.Name_Last, o.Org_Name FROM campaign_contact AS cc INNER JOIN contact AS c ON c.Contact_ID=cc.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN address AS a ON a.Address_ID=p.Address_ID WHERE cc.Campaign_ID=%d", mysql_real_escape_string($campaign->ID)));
	$table->AddField('ID#', 'Contact_ID', 'left');
	$table->AddField('Organisation', 'Org_Name', 'left');
	$table->AddField('First Name', 'Name_First', 'left');
	$table->AddField('Last Name', 'Name_Last', 'left');
	$table->AddField('Customer', 'Is_Customer', 'center');
	$table->AddField('Supplier', 'Is_Supplier', 'center');
	$table->AddField('Active', 'Is_Active', 'center');
	$table->AddLink("contact_profile.php?cid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Campaign Group\" border=\"0\">", "Contact_ID");
	$table->AddLink("javascript:confirmRequest('campaign_recipients.php?action=remove&confirm=true&id=".$campaign->ID."&aid=%s','Are you sure you want to remove this recipient from this campaign?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Campaign_Contact_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Contact_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	echo '<br />';

	echo sprintf('<input type="button" name="add" value="add recipients" class="btn" onclick="window.location.href=\'campaign_recipients.php?action=add&id=%d\'" /> ', $campaign->ID);
	echo sprintf('<input type="button" name="addgroup" value="add recipients from group" class="btn" onclick="window.location.href=\'campaign_recipients.php?action=addgroup&id=%d\'" /> ', $campaign->ID);
	echo sprintf('<input type="button" name="addall" value="add all recipients" class="btn" onclick="window.location.href=\'campaign_recipients.php?action=addall&id=%d\'" /> ', $campaign->ID);
	echo sprintf('<input type="button" name="removeall" value="remove all recipients" class="btn" onclick="window.location.href=\'campaign_recipients.php?action=removeall&id=%d\'" />', $campaign->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>