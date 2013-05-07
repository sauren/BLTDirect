<?php
ini_set('max_execution_time', 1500);

require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "addcontact"){
	$session->Secure(3);
	addcontact();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "removeall"){
	$session->Secure(3);
	removeAll();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroupAssoc.php');

	if(!isset($_REQUEST['gid'])) {
		redirect(sprintf("Location: contact_groups.php"));
	}

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$contact = new ContactGroupAssoc();
		$contact->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s?gid=%d", $_SERVER['PHP_SELF'], $_REQUEST['gid']));
}

function removeAll(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroupAssoc.php');

	if(!isset($_REQUEST['gid'])) {
		redirect(sprintf("Location: contact_groups.php"));
	}

	$data = new DataQuery(sprintf("DELETE FROM contact_group_assoc WHERE Contact_Group_ID=%d", mysql_real_escape_string($_REQUEST['gid'])));
	$data->Disconnect();

	redirect(sprintf("Location: %s?gid=%d", $_SERVER['PHP_SELF'], $_REQUEST['gid']));
}

function addcontact() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroupAssoc.php');

	if(!isset($_REQUEST['gid'])) {
		redirect(sprintf("Location: contact_group_assoc.php"));
	}

	$group = new ContactGroup($_REQUEST['gid']);
	$contacts = array();

	foreach($_REQUEST as $key=>$contact) {
		if(stristr(substr($key, 0, 7), 'select_')) {
			$contacts[] = substr($key, 7, strlen($key));
		}
	}

	$groupContact = new ContactGroupAssoc();
	$groupContact->ContactGroup->ID = $group->ID;

	foreach($contacts as $contact) {
		$groupContact->Contact->ID = $contact;
		$groupContact->Add();
	}

	redirect(sprintf("Location: contact_group_assoc.php?gid=%d", $group->ID));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroupAssoc.php');

	if(!isset($_REQUEST['gid'])) {
		redirect(sprintf("Location: contact_group_assoc.php"));
	}

	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('gid', 'Group ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('contactid', 'Contact ID', 'text', '', 'numeric_unsigned', 1, 11, false);
	$form->AddField('startdate', 'Created After', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('enddate', 'Created Before', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('fname', 'First Name', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('lname', 'Last Name', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('org', 'Organisation', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('email', 'Email Address', 'text', '', 'paragraph', 1, 255,false);
	$form->AddField('phone', 'Phone Number', 'text', '', 'telephone', 1, 32, false);
	$form->AddField('mobile', 'Mobile Number', 'text', '', 'telephone', 1, 32, false);
	$form->AddField('fax', 'Fax Number', 'text', '', 'telephone', 1, 32, false);
	$form->AddField('postcode', 'Postcode', 'text', '', 'anything', 1, 32, false);
	$form->AddField('address1', 'Property Name/Number', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('address2', 'Street', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('address3', 'Area', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('city', 'City', 'text', '', 'alpha_numeric', 1, 150, false);
	$form->AddField('quotes', 'Quotes', 'select', '', 'anything', 1, 128, false);
	$form->AddOption('quotes', '', '');
	$form->AddOption('quotes', 'Y', 'Yes');
	$form->AddOption('quotes', 'N', 'No');
	$form->AddField('orders', 'Orders', 'select', '', 'anything', 1, 128, false);
	$form->AddOption('orders', '', '');
	$form->AddOption('orders', 'Y', 'Yes');
	$form->AddOption('orders', 'N', 'No');
	$form->AddField('creditaccount', 'Credit Account', 'select', '', 'anything', 1, 128, false);
	$form->AddOption('creditaccount', '', '');
	$form->AddOption('creditaccount', 'Y', 'Yes');
	$form->AddOption('creditaccount', 'N', 'No');
	$form->AddField('orderstartdate', 'Last Ordered After', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('orderenddate', 'Last Ordered Before', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
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
	$form->AddField('manufacturer', 'Products Manufactured By', 'select', '', 'numeric_unsigned', 1, 128, false);
	$form->AddOption('manufacturer', '', '');

	$data = new DataQuery("SELECT * FROM manufacturer ORDER BY Manufacturer_Name ASC");
	while($data->Row) {
		$form->AddOption('manufacturer', $data->Row['Manufacturer_ID'], $data->Row['Manufacturer_Name']);

		$data->Next();
	}
	$data->Disconnect();

    $form->AddField('shippingclass', 'Products In Shipping Class', 'select', '', 'numeric_unsigned', 1, 11, false);
	$form->AddOption('shippingclass', '', '');

	$data = new DataQuery("SELECT * FROM shipping_class ORDER BY Shipping_Class_Title ASC");
	while($data->Row) {
		$form->AddOption('shippingclass', $data->Row['Shipping_Class_ID'], $data->Row['Shipping_Class_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('ordercountmin', 'Order Count (Min)', 'select', '', 'numeric_unsigned', 1, 128, false);
	$form->AddOption('ordercountmin', '', '');

	for($i=0; $i<50; $i++) {
		$form->AddOption('ordercountmin', $i+1, sprintf('%d+', $i+1));
	}

	$form->AddField('ordercountmax', 'Order Count (Max)', 'select', '', 'numeric_unsigned', 1, 128, false);
	$form->AddOption('ordercountmax', '', '');

	for($i=0; $i<50; $i++) {
		$form->AddOption('ordercountmax', $i+1, sprintf('-%d', $i+1));
	}

    $form->AddField('ordertotalmin', 'Order Total (Min)', 'text', '', 'float', 1, 11, false);
    $form->AddField('ordertotalmax', 'Order Total (Max)', 'text', '', 'float', 1, 11, false);
    $form->AddField('orderavgmin', 'Order Average (Min)', 'text', '', 'float', 1, 11, false);
    $form->AddField('orderavgmax', 'Order Average (Max)', 'text', '', 'float', 1, 11, false);
	$form->AddField('products', 'Products', 'text', '', 'anything', 1, 1024, false);
	$form->AddField('ordertype', 'Order Type', 'select', '', 'alpha', 1, 32, false);
	$form->AddOption('ordertype', '', '');
	$form->AddOption('ordertype', 'W', 'Website');
	$form->AddOption('ordertype', 'M', 'Mobile');
	$form->AddOption('ordertype', 'T', 'Telesales');
	$form->AddOption('ordertype', 'F', 'Fax');
	$form->AddOption('ordertype', 'E', 'Email');
	$form->AddField('enquirystartdate', 'Last Enquired After', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('enquiryenddate', 'Last Enquired Before', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('enquiryowner', 'Enquiry Owner', 'select', '', 'numeric_unsigned', 1, 11, false);
	$form->AddOption('enquiryowner', '', '');

	$data = new DataQuery(sprintf("SELECT u.User_ID, p.Name_First, p.Name_Last FROM users AS u INNER JOIN person AS p ON u.Person_ID=p.Person_ID ORDER BY p.Name_First, p.Name_Last"));
	while($data->Row) {
		$form->AddOption('enquiryowner', $data->Row['User_ID'], trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])));

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('industry', 'Industry', 'selectmultiple', '', 'numeric_unsigned', 1, 11, false);
	$form->AddOption('industry', 0, '');

	$data = new DataQuery("select Industry_ID, Industry_Name from organisation_industry");
	while($data->Row){
		$form->AddOption('industry', $data->Row['Industry_ID'], $data->Row['Industry_Name']);
		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('hasorganisation', 'Has Organisation', 'select', '', 'anything', 1, 128, false);
	$form->AddOption('hasorganisation', '', '');
	$form->AddOption('hasorganisation', 'Y', 'Yes');
	$form->AddOption('hasorganisation', 'N', 'No');
	$form->AddField('istemp', 'Is Temporary', 'select', 'N', 'anything', 1, 128, false);
	$form->AddOption('istemp', '', '');
	$form->AddOption('istemp', 'Y', 'Yes');
	$form->AddOption('istemp', 'N', 'No');
	$form->AddField('account', 'Account Manager', 'select', '', 'numeric_unsigned', 1, 11, false, '');
	$form->AddOption('account', '', '');
	$form->AddOption('account', '0', 'None');

	$data = new DataQuery('SELECT u.User_ID, p.Name_First, p.Name_Last FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY p.Name_First, p.Name_Last ASC');
	while($data->Row) {
		$form->AddOption('account', $data->Row['User_ID'], trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])));

		$data->Next();
	}
	$data->Disconnect();

	$group = new ContactGroup($_REQUEST['gid']);

	if(isset($_REQUEST['addselected'])){
		$contacts = array();

		foreach($_REQUEST as $key=>$contact) {
			if(stristr(substr($key, 0, 7), 'select_')) {
				$contacts[] = substr($key, 7, strlen($key));
			}
		}

		$groupContact = new ContactGroupAssoc();
		$groupContact->ContactGroup->ID = $group->ID;

		foreach($contacts as $contact) {
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM contact_group_assoc WHERE Contact_ID=%d AND Contact_Group_ID=%d", mysql_real_escape_string($contact), mysql_real_escape_string($group->ID)));
			if($data->Row['Count'] == 0) {
				$groupContact->Contact->ID = $contact;
				$groupContact->Add();
			}
			$data->Disconnect();
		}

		redirect(sprintf("Location: %s?gid=%d", $_SERVER['PHP_SELF'], $group->ID));
	}

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_group_contact SELECT Contact_ID FROM contact_group_assoc WHERE Contact_Group_ID=%d", mysql_real_escape_string($group->ID)));
	new DataQuery(sprintf("CREATE INDEX Contact_ID ON temp_group_contact (Contact_ID)"));

	$sqlSelect = "";
	$sqlFrom = "";
	$sqlWhere = "";

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$isTemporary = $form->GetValue('istemp');

			$sqlSelect = sprintf("SELECT c.Contact_ID, c.Is_Active, c.Is_Customer, c.Is_Supplier, p.Name_First, p.Name_Initial, p.Name_Last, o.Org_Name ");
			$sqlFrom = sprintf("FROM temp_contact AS c LEFT JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN address AS a ON a.Address_ID=p.Address_ID LEFT JOIN temp_group_contact AS tgc ON tgc.Contact_ID=c.Contact_ID ");
			$sqlWhere = sprintf("WHERE tgc.Contact_ID IS NULL AND c.Contact_Type='I' ");

			if(strlen($form->GetValue('contactid')) > 0) {
				if($form->GetValue('excludecontactid') == 'N') {
					$sqlWhere .= sprintf(" AND c.Contact_ID=%d", mysql_real_escape_string($form->GetValue('contactid')));
				} else {
					$sqlWhere .= sprintf(" AND c.Contact_ID<>%d", mysql_real_escape_string($form->GetValue('contactid')));
				}
			}

			if((strlen($form->GetValue('startdate')) > 0) && (strlen($form->GetValue('enddate')) > 0)) {
				$start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2));
				$end = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2));

				$sqlWhere .= sprintf(" AND c.Created_On BETWEEN '%s' AND '%s'", $start, $end);
			} elseif(strlen($form->GetValue('startdate')) > 0) {
				$start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startdate'), 6, 4), substr($form->GetValue('startdate'), 3, 2), substr($form->GetValue('startdate'), 0, 2));

				$sqlWhere .= sprintf(" AND c.Created_On>='%s'", $start);
			} elseif(strlen($form->GetValue('enddate')) > 0) {
				$end = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enddate'), 6, 4), substr($form->GetValue('enddate'), 3, 2), substr($form->GetValue('enddate'), 0, 2));

				$sqlWhere .= sprintf(" AND c.Created_On<='%s'", $end);
			}

			if(strlen($form->GetValue('account')) > 0) {
				$sqlWhere .= sprintf(" AND c.Account_Manager_ID=%d", mysql_real_escape_string($form->GetValue('account')));
			}

			if(strlen($form->GetValue('fname')) > 0) {
				if($form->GetValue('excludefname') == 'N') {
					$sqlWhere .= sprintf(" AND p.Name_First LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('fname')));
				} else {
					$sqlWhere .= sprintf(" AND p.Name_First NOT LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('fname')));
				}
			}

			if(strlen($form->GetValue('lname')) > 0) {
				if($form->GetValue('excludelname') == 'N') {
					$sqlWhere .= sprintf(" AND p.Name_Last LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('lname')));
				} else {
					$sqlWhere .= sprintf(" AND p.Name_Last NOT LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('lname')));
				}
			}

			if(strlen($form->GetValue('org')) > 0) {
				if($form->GetValue('excludeorg') == 'N') {
					$sqlWhere .= sprintf(" AND o.Org_Name LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('org')));
				} else {
					$sqlWhere .= sprintf(" AND o.Org_Name NOT LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('org')));
				}
			}

			if(strlen($form->GetValue('email')) > 0) {
				if($form->GetValue('excludeemail') == 'N') {
					$sqlWhere .= sprintf(" AND p.Email LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('email')));
				} else {
					$sqlWhere .= sprintf(" AND p.Email NOT LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('email')));
				}
			}

			if(strlen($form->GetValue('phone')) > 0) {
				if($form->GetValue('excludephone') == 'N') {
					$sqlWhere .= sprintf(" AND (REPLACE(p.Phone_1, ' ', '') LIKE REPLACE('%%%s%%', ' ', '') OR REPLACE(p.Phone_2, ' ', '') LIKE REPLACE('%%%s%%', ' ', ''))", mysql_real_escape_string($form->GetValue('phone')), mysql_real_escape_string($form->GetValue('phone')));
				} else {
					$sqlWhere .= sprintf(" AND (REPLACE(p.Phone_1, ' ', '') NOT LIKE REPLACE('%%%s%%', ' ', '') AND REPLACE(p.Phone_2, ' ', '') NOT LIKE REPLACE('%%%s%%', ' ', ''))", mysql_real_escape_string($form->GetValue('phone')), mysql_real_escape_string($form->GetValue('phone')));
				}
			}

			if(strlen($form->GetValue('mobile')) > 0) {
				if($form->GetValue('excludemobile') == 'N') {
					$sqlWhere .= sprintf(" AND REPLACE(p.Mobile, ' ', '') LIKE REPLACE('%%%s%%', ' ', '')", mysql_real_escape_string($form->GetValue('mobile')));
				} else {
					$sqlWhere .= sprintf(" AND REPLACE(p.Mobile, ' ', '') NOT LIKE REPLACE('%%%s%%', ' ', '')", mysql_real_escape_string($form->GetValue('mobile')));
				}
			}

			if(strlen($form->GetValue('fax')) > 0) {
				if($form->GetValue('excludefax') == 'N') {
					$sqlWhere .= sprintf(" AND REPLACE(p.Fax, ' ', '') LIKE REPLACE('%%%s%%', ' ', '')", mysql_real_escape_string($form->GetValue('fax')));
				} else {
					$sqlWhere .= sprintf(" AND REPLACE(p.Fax, ' ', '') NOT LIKE REPLACE('%%%s%%', ' ', '')", mysql_real_escape_string($form->GetValue('fax')));
				}
			}

			if(strlen($form->GetValue('address1')) > 0) {
				if($form->GetValue('excludeaddress1') == 'N') {
					$sqlWhere .= sprintf(" AND a.Address_Line_1 LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('address1')));
				} else {
					$sqlWhere .= sprintf(" AND a.Address_Line_1 NOT LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('address1')));
				}
			}

			if(strlen($form->GetValue('address2')) > 0) {
				if($form->GetValue('excludeaddress2') == 'N') {
					$sqlWhere .= sprintf(" AND a.Address_Line_2 LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('address2')));
				} else {
					$sqlWhere .= sprintf(" AND a.Address_Line_2 NOT LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('address2')));
				}
			}

			if(strlen($form->GetValue('address3')) > 0) {
				if($form->GetValue('excludeaddress3') == 'N') {
					$sqlWhere .= sprintf(" AND a.Address_Line_3 LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('address3')));
				} else {
					$sqlWhere .= sprintf(" AND a.Address_Line_3 NOT LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('address3')));
				}
			}

			if(strlen($form->GetValue('city')) > 0) {
				if($form->GetValue('excludecity') == 'N') {
					$sqlWhere .= sprintf(" AND a.City LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('city')));
				} else {
					$sqlWhere .= sprintf(" AND a.City NOT LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('city')));
				}
			}

			if(strlen($form->GetValue('postcode')) > 0) {
				if($form->GetValue('excludepostcode') == 'N') {
					$sqlWhere .= sprintf(" AND REPLACE(a.Zip, ' ', '') LIKE '%%%s%%'", str_replace(' ', '', mysql_real_escape_string($form->GetValue('postcode'))));
				} else {
					$sqlWhere .= sprintf(" AND REPLACE(a.Zip, ' ', '') NOT LIKE REPLACE('%%%s%%', ' ', '')", mysql_real_escape_string($form->GetValue('postcode')));
				}
			}

			if(strlen($form->GetValue('orders')) > 0) {
				if($form->GetValue('orders') == 'Y') {
					$sqlWhere .= " AND 0<(SELECT COUNT(sso.Order_ID) FROM contact AS ssc LEFT JOIN customer AS sscu ON sscu.Contact_ID=ssc.Contact_ID LEFT JOIN orders AS sso ON sso.Customer_ID=sscu.Customer_ID WHERE ssc.Contact_ID=c.Contact_ID)";
				} elseif($form->GetValue('orders') == 'N') {
					$sqlWhere .= " AND 0=(SELECT COUNT(sso.Order_ID) FROM contact AS ssc LEFT JOIN customer AS sscu ON sscu.Contact_ID=ssc.Contact_ID LEFT JOIN orders AS sso ON sso.Customer_ID=sscu.Customer_ID WHERE ssc.Contact_ID=c.Contact_ID)";
				}
			}

			if(strlen($form->GetValue('quotes')) > 0) {
				if($form->GetValue('quotes') == 'Y') {
					$sqlWhere .= " AND 0<(SELECT COUNT(ssq.Quote_ID) FROM contact AS ssc LEFT JOIN customer AS sscu ON sscu.Contact_ID=ssc.Contact_ID LEFT JOIN quotes AS ssq ON ssq.Customer_ID=sscu.Customer_ID WHERE ssc.Contact_ID=c.Contact_ID)";
				} elseif($form->GetValue('quotes') == 'N') {
					$sqlWhere .= " AND 0=(SELECT COUNT(ssq.Quote_ID) FROM contact AS ssc LEFT JOIN customer AS sscu ON sscu.Contact_ID=ssc.Contact_ID LEFT JOIN quotes AS ssq ON ssq.Customer_ID=sscu.Customer_ID WHERE ssc.Contact_ID=c.Contact_ID)";
				}
			}

			if(strlen($form->GetValue('creditaccount')) > 0) {
				$sqlWhere .= sprintf(" AND cu.Is_Credit_Active='%s'", $form->GetValue('creditaccount'));
			}

			if(strlen($form->GetValue('hasorganisation')) > 0) {
				if($form->GetValue('hasorganisation') == 'Y') {
					$sqlFrom .= sprintf("INNER JOIN contact AS n2 ON c.Parent_Contact_ID=n2.Contact_ID ");
				} elseif($form->GetValue('hasorganisation') == 'N') {
					$sqlFrom .= sprintf("LEFT JOIN contact AS n2 ON c.Parent_Contact_ID=n2.Contact_ID ");
					$sqlWhere .= sprintf(" AND n2.Contact_ID IS NULL ");
				}
			}

			if((strlen($form->GetValue('orderstartdate')) > 0) && (strlen($form->GetValue('orderenddate')) > 0)) {
				$start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('orderstartdate'), 6, 4), substr($form->GetValue('orderstartdate'), 3, 2), substr($form->GetValue('orderstartdate'), 0, 2));
				$end = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('orderenddate'), 6, 4), substr($form->GetValue('orderenddate'), 3, 2), substr($form->GetValue('orderenddate'), 0, 2));

				$sqlWhere .= sprintf(" AND (SELECT osd.Created_On FROM orders AS osd WHERE osd.Customer_ID=cu.Customer_ID ORDER BY osd.Created_On DESC LIMIT 0, 1) BETWEEN '%s' AND '%s'", $start, $end);
			} elseif(strlen($form->GetValue('orderstartdate')) > 0) {
				$start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('orderstartdate'), 6, 4), substr($form->GetValue('orderstartdate'), 3, 2), substr($form->GetValue('orderstartdate'), 0, 2));

				$sqlWhere .= sprintf(" AND (SELECT osd.Created_On FROM orders AS osd WHERE osd.Customer_ID=cu.Customer_ID ORDER BY osd.Created_On DESC LIMIT 0, 1)>'%s'", $start);
			} elseif(strlen($form->GetValue('orderenddate')) > 0) {
				$end = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('orderenddate'), 6, 4), substr($form->GetValue('orderenddate'), 3, 2), substr($form->GetValue('orderenddate'), 0, 2));

				$sqlWhere .= sprintf(" AND (SELECT osd.Created_On FROM orders AS osd WHERE osd.Customer_ID=cu.Customer_ID ORDER BY osd.Created_On DESC LIMIT 0, 1)<'%s'", mysql_real_escape_string($end));
			}

			if(strlen($form->GetValue('manufacturer')) > 0) {
				$sqlWhere .= sprintf(" AND 0<(SELECT COUNT(ssol2.Order_Line_ID) FROM orders AS sso2 INNER JOIN order_line AS ssol2 ON ssol2.Order_ID=sso2.Order_ID INNER JOIN product AS ssp2 ON ssp2.Product_ID=ssol2.Product_ID WHERE sso2.Customer_ID=cu.Customer_ID AND ssp2.Manufacturer_ID=%d)", mysql_real_escape_string($form->GetValue('manufacturer')));
			}

            if(strlen($form->GetValue('shippingclass')) > 0) {
				$sqlWhere .= sprintf(" AND 0<(SELECT COUNT(ssol2.Order_Line_ID) FROM orders AS sso2 INNER JOIN order_line AS ssol2 ON ssol2.Order_ID=sso2.Order_ID INNER JOIN product AS ssp2 ON ssp2.Product_ID=ssol2.Product_ID WHERE sso2.Customer_ID=cu.Customer_ID AND ssp2.Shipping_Class_ID=%d)", mysql_real_escape_string($form->GetValue('shippingclass')));
			}

			if($form->GetValue('ordercountmin') > 0) {
				$sqlWhere .= sprintf(" AND %d<=(SELECT COUNT(sso3.Order_ID) FROM orders AS sso3 WHERE sso3.Customer_ID=cu.Customer_ID)", mysql_real_escape_string($form->GetValue('ordercountmin')));
			}

			if($form->GetValue('ordercountmax') > 0) {
				$sqlWhere .= sprintf(" AND %d<=(SELECT COUNT(sso3.Order_ID) FROM orders AS sso3 WHERE sso3.Customer_ID=cu.Customer_ID)", mysql_real_escape_string($form->GetValue('ordercountmax')));
			}

			if(strlen($form->GetValue('ordertotalmin')) > 0) {
				$sqlWhere .= sprintf(" AND 0<(SELECT COUNT(sso3.Order_ID) FROM orders AS sso3 WHERE sso3.Customer_ID=cu.Customer_ID AND sso3.Total>=%f)", mysql_real_escape_string($form->GetValue('ordertotalmin')));
			}

			if(strlen($form->GetValue('ordertotalmax')) > 0) {
				$sqlWhere .= sprintf(" AND 0<(SELECT COUNT(sso3.Order_ID) FROM orders AS sso3 WHERE sso3.Customer_ID=cu.Customer_ID AND sso3.Total<=%f)", mysql_real_escape_string($form->GetValue('ordertotalmax')));
			}
			
			if(strlen($form->GetValue('orderavgmin')) > 0) {
				$sqlWhere .= sprintf(" AND 0<(SELECT COUNT(sso3.Order_ID) FROM orders AS sso3 WHERE sso3.Customer_ID=cu.Customer_ID HAVING AVG(sso3.Total)>=%f)", mysql_real_escape_string($form->GetValue('orderavgmin')));
			}

			if(strlen($form->GetValue('orderavgmax')) > 0) {
				$sqlWhere .= sprintf(" AND 0<(SELECT COUNT(sso3.Order_ID) FROM orders AS sso3 WHERE sso3.Customer_ID=cu.Customer_ID HAVING AVG(sso3.Total)<=%f)", mysql_real_escape_string($form->GetValue('orderavgmax')));
			}

			if(strlen(trim($form->GetValue('products'))) > 0) {
				$sqlProducts = array();

				$products = explode(',', $form->GetValue('products'));
				foreach($products as $product) {
					$product = trim($product);
					if(is_numeric($product)) {
						$sqlProducts[$product] = $product;
					} elseif(strstr($product, '-')) {
						$productRange = explode('-', $product);
						if(count($productRange) == 2) {
							if(is_numeric($productRange[0])	&& is_numeric($productRange[1])) {
								if($productRange[0] <= $productRange[1]) {
									for($i=$productRange[0]; $i<=$productRange[1]; $i++) {
										$sqlProducts[$i] = $i;
									}
								}
							}
						}
					}
				}

				if(count($sqlProducts) > 0) {
					$sqlProducts = sprintf('ssol4.Product_ID=%s', implode(' OR ssol4.Product_ID=', $sqlProducts));

					$sqlWhere .= sprintf(" AND 0<(SELECT COUNT(ssol4.Order_Line_ID) FROM orders AS sso4 INNER JOIN order_line AS ssol4 ON ssol4.Order_ID=sso4.Order_ID WHERE sso4.Customer_ID=cu.Customer_ID AND (%s))", mysql_real_escape_string($sqlProducts));
				}
			}

			if(strlen($form->GetValue('ordertype')) > 0) {
				$sqlWhere .= sprintf(" AND 0<(SELECT COUNT(sso6.Order_ID) FROM orders AS sso6 WHERE sso6.Customer_ID=cu.Customer_ID AND sso6.Order_Prefix='%s')", mysql_real_escape_string($form->GetValue('ordertype')));
			}

			if((strlen($form->GetValue('enquirystartdate')) > 0) && (strlen($form->GetValue('enquiryenddate')) > 0)) {
				$start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enquirystartdate'), 6, 4), substr($form->GetValue('enquirystartdate'), 3, 2), substr($form->GetValue('enquirystartdate'), 0, 2));
				$end = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enquiryenddate'), 6, 4), substr($form->GetValue('enquiryenddate'), 3, 2), substr($form->GetValue('enquiryenddate'), 0, 2));

				$sqlWhere .= sprintf(" AND (SELECT esd.Created_On FROM enquiry AS esd WHERE esd.Customer_ID=cu.Customer_ID ORDER BY esd.Created_On DESC LIMIT 0, 1) BETWEEN '%s' AND '%s'", $start, $end);
			} elseif(strlen($form->GetValue('enquirystartdate')) > 0) {
				$start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enquirystartdate'), 6, 4), substr($form->GetValue('enquirystartdate'), 3, 2), substr($form->GetValue('enquirystartdate'), 0, 2));

				$sqlWhere .= sprintf(" AND (SELECT esd.Created_On FROM enquiry AS esd WHERE esd.Customer_ID=cu.Customer_ID ORDER BY esd.Created_On DESC LIMIT 0, 1)>'%s'", $start);
			} elseif(strlen($form->GetValue('enquiryenddate')) > 0) {
				$end = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('enquiryenddate'), 6, 4), substr($form->GetValue('enquiryenddate'), 3, 2), substr($form->GetValue('enquiryenddate'), 0, 2));

				$sqlWhere .= sprintf(" AND (SELECT esd.Created_On FROM enquiry AS esd WHERE esd.Customer_ID=cu.Customer_ID ORDER BY esd.Created_On DESC LIMIT 0, 1)<'%s'", mysql_real_escape_string($end));
			}

			if(strlen($form->GetValue('enquiryowner')) > 0) {
				$sqlWhere .= sprintf(" AND 0<(SELECT COUNT(esd2.Enquiry_ID) FROM enquiry AS esd2 WHERE esd2.Customer_ID=cu.Customer_ID AND esd2.Owned_By=%d)", mysql_real_escape_string($form->GetValue('enquiryowner')));
			}

			if(strlen($form->GetValue('industry')) > 0) {
				$industries = array();
				foreach($form->GetValue('industry') as $value){
					if($value <= 0){ continue; }
					$industries[] = $value;
				}
				$sqlWhere .= sprintf(" AND (o.Industry_ID = %s)", mysql_real_escape_string(join(' OR o.Industry_ID = ', $industries)));
			}
		} else {
			echo $form->GetError();
			echo "<br />";
		}
	}

	if(isset($_REQUEST['addall'])) {
		if(strlen(sprintf('%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere)) > 0) {
			new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_contact SELECT c.* FROM contact AS c %s", (strlen($isTemporary) > 0) ? sprintf("WHERE c.Is_Temporary='%s'", mysql_real_escape_string($isTemporary)) : ''));
			new DataQuery(sprintf("CREATE INDEX Contact_ID ON temp_contact (Contact_ID)"));

			$groupContact = new ContactGroupAssoc();
			$groupContact->ContactGroup->ID = $group->ID;

			$data = new DataQuery(sprintf('%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere));
			while($data->Row) {
				$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM contact_group_assoc WHERE Contact_ID=%d AND Contact_Group_ID=%d", mysql_real_escape_string($data->Row['Contact_ID']), mysql_real_escape_string($group->ID)));
				if($data2->Row['Count'] == 0) {
					$groupContact->Contact->ID = $data->Row['Contact_ID'];
					$groupContact->Add();
				}
				$data2->Disconnect();

				$data->Next();
			}
			$data->Disconnect();

			redirect(sprintf("Location: %s?gid=%d", $_SERVER['PHP_SELF'], $group->ID));
		}
	}

	$page = new Page(sprintf('<a href="contact_groups.php">Contact Groups</a> &gt; <a href="contact_group_assoc.php?gid=%d">%s Contacts</a> &gt; Add Contacts', $group->ID, $group->Name), 'This area allows you to maintain contacts for your group.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
	$page->Display('header');

	$contacts = array();

	if(strlen(sprintf('%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere)) > 0) {
		new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_contact SELECT c.* FROM contact AS c %s", (strlen($isTemporary) > 0) ? sprintf("WHERE c.Is_Temporary='%s'", mysql_real_escape_string($isTemporary)) : ''));
		new DataQuery(sprintf("CREATE INDEX Contact_ID ON temp_contact (Contact_ID)"));

		$table = new DataTable('Search');
		$table->SetSQL(sprintf('%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere));
		$table->SetMaxRows(1000);
		$table->SetOrderBy('Contact_ID');
		$table->SetExtractVars();
		$table->Finalise();
		$table->ExecuteSQL();

		while($table->Table->Row) {
			$form->AddField('select_'.$table->Table->Row['Contact_ID'], 'Select Contact', 'checkbox', 'N', 'boolean', 1, 1, false);

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
	echo $form->GetHTML('gid');

	echo $window->Open();
	echo $window->AddHeader('Search for contacts by any of the below fields.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('contactid'), $form->GetHTML('contactid') . $form->GetHTML('excludecontactid').$form->GetLabel('excludecontactid'));
	echo $webForm->AddRow($form->GetLabel('startdate'), $form->GetHTML('startdate'));
	echo $webForm->AddRow($form->GetLabel('enddate'), $form->GetHTML('enddate'));
	echo $webForm->AddRow($form->GetLabel('account'), $form->GetHTML('account'));
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
	echo $webForm->AddRow($form->GetLabel('orders'), $form->GetHTML('orders'));
	echo $webForm->AddRow($form->GetLabel('quotes'), $form->GetHTML('quotes'));
	echo $webForm->AddRow($form->GetLabel('creditaccount'), $form->GetHTML('creditaccount'));
	echo $webForm->AddRow($form->GetLabel('hasorganisation'), $form->GetHTML('hasorganisation'));
	echo $webForm->AddRow($form->GetLabel('istemp'), $form->GetHTML('istemp'));
	echo $webForm->AddRow($form->GetLabel('ordertype'), $form->GetHTML('ordertype'));
	echo $webForm->AddRow($form->GetLabel('orderstartdate'), $form->GetHTML('orderstartdate'));
	echo $webForm->AddRow($form->GetLabel('orderenddate'), $form->GetHTML('orderenddate'));
	echo $webForm->AddRow($form->GetLabel('ordercountmin'), $form->GetHTML('ordercountmin'));
	echo $webForm->AddRow($form->GetLabel('ordercountmax'), $form->GetHTML('ordercountmax'));
    echo $webForm->AddRow($form->GetLabel('ordertotalmin'), $form->GetHTML('ordertotalmin'));
	echo $webForm->AddRow($form->GetLabel('ordertotalmax'), $form->GetHTML('ordertotalmax'));
	echo $webForm->AddRow($form->GetLabel('orderavgmin'), $form->GetHTML('orderavgmin'));
	echo $webForm->AddRow($form->GetLabel('orderavgmax'), $form->GetHTML('orderavgmax'));
	echo $webForm->AddRow($form->GetLabel('manufacturer'), $form->GetHTML('manufacturer'));
	echo $webForm->AddRow($form->GetLabel('shippingclass'), $form->GetHTML('shippingclass'));
	echo $webForm->AddRow($form->GetLabel('products'), $form->GetHTML('products') . '(Example: "215" or "215-221" or "215,216,218" or "215-217,220")');
	echo $webForm->AddRow($form->GetLabel('enquirystartdate'), $form->GetHTML('enquirystartdate'));
	echo $webForm->AddRow($form->GetLabel('enquiryenddate'), $form->GetHTML('enquiryenddate'));
	echo $webForm->AddRow($form->GetLabel('enquiryowner'), $form->GetHTML('enquiryowner'));
	echo $webForm->AddRow($form->GetLabel('industry'), $form->GetHTML('industry'));
	echo $webForm->AddRow('', '<input type="submit" name="searchButton" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	if(strlen(sprintf('%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere)) > 0) {
		echo '</td><td width="10"></td><td valign="top">';
		echo '<form method="post">';
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('gid');
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
						<td align="left" width="16"><?php echo $form->GetHTML('select_'.$contacts[$i]['Contact_ID']); ?></td>
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
		<input type="submit" name="addselected" value="add selected contacts" class="btn" />
		<input type="submit" name="addall" value="add all contacts" class="btn" />

		<?php
		echo "</form>";
		echo '</td></tr></table>';
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactGroup.php');

	if(!isset($_REQUEST['gid'])) {
		redirect(sprintf("Location: contact_group_assoc.php"));
	}

	$group = new ContactGroup($_REQUEST['gid']);

	$page = new Page(sprintf('<a href="contact_groups.php">Contact Groups</a> &gt; %s Contacts', $group->Name), 'This area allows you to maintain contacts for your group.');
	$page->AddToHead('<link rel="stylesheet" type="text/css" href="css/m_groups.css" />');
	$page->Display('header');

	$table = new DataTable('contacts');
	$table->SetSQL(sprintf("SELECT g.Contact_Group_Assoc_ID, c.Contact_ID, c.Parent_Contact_ID, p.Name_First, p.Name_Initial, p.Name_Last, o.Org_Name, p.Phone_1, CONCAT_WS(', ', a.Address_Line_1, a.Address_Line_2, a.City) AS Address, a.Zip FROM contact_group_assoc AS g INNER JOIN contact AS c ON c.Contact_ID=g.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN address AS a ON a.Address_ID=p.Address_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID WHERE g.Contact_Group_ID=%d GROUP BY c.Contact_ID", mysql_real_escape_string($_REQUEST['gid'])));
	$table->SetMaxRows(25);
	$table->SetOrderBy("Contact_ID");
	$table->Finalise();
	$table->ExecuteSQL();

	if($table->TotalRows > 0) {
		$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Order_Count, SUM(o.Total-o.TotalTax) AS Order_Total FROM contact_group_assoc AS ga INNER JOIN contact AS c ON c.Contact_ID=ga.Contact_ID INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID INNER JOIN orders AS o ON o.Customer_ID=cu.Customer_ID WHERE o.Status<>'Cancelled' AND ga.Contact_Group_ID=%d", mysql_real_escape_string($_REQUEST['gid'])));
		$totalOrders = $data->Row['Order_Count'];
		$totalSpend = $data->Row['Order_Total'];
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT COUNT(*) AS Contact_Count FROM contact_group_assoc AS ga WHERE ga.Contact_Group_ID=%d", mysql_real_escape_string($_REQUEST['gid'])));
		$totalContacts = $data->Row['Contact_Count'];
		$data->Disconnect();
		?>

		<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
			<thead>
				<tr>
					<th nowrap="nowrap">Total Contacts</th>
					<th nowrap="nowrap">Total Order</th>
					<th nowrap="nowrap">Total Net Spend</th>
				</tr>
			</thead>
			<tbody>
				<tr class="dataRow">
					<td align="left"><?php print $totalContacts; ?></td>
					<td align="left"><?php print $totalOrders; ?></td>
					<td align="left">&pound;<?php print number_format($totalSpend, 2, '.', ','); ?></td>
				</tr>
			</tbody>
		</table><br />

		<?php
	}
	?>

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th class="dataHeadOrdered" nowrap="nowrap">ID#</th>
				<th nowrap="nowrap">Organisation</th>
				<th nowrap="nowrap">Name</th>
				<th nowrap="nowrap">Phone</th>
				<th nowrap="nowrap">Address</th>
				<th nowrap="nowrap">Zip</th>
				<th nowrap="nowrap">Orders</th>
				<th nowrap="nowrap">Total Net Spend</th>
				<th nowrap="nowrap">Last Ordered</th>
				<th colspan="2">&nbsp;</th>
			</tr>
		</thead>
		<tbody>

			<?php
			if($table->TotalRows > 0) {
				while($table->Table->Row) {
					$contacts = array();

					if($table->Table->Row['Parent_Contact_ID'] > 0) {
						$data = new DataQuery(sprintf("SELECT c3.Contact_ID FROM contact AS c INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN contact AS c3 ON c3.Parent_Contact_ID=c2.Contact_ID WHERE c.Contact_ID=%d", mysql_real_escape_string($table->Table->Row['Contact_ID'])));
						while($data->Row) {
							$contacts[] = $data->Row['Contact_ID'];
							$data->Next();
						}
						$data->Disconnect();
					} else {
						$contacts[] = $table->Table->Row['Contact_ID'];
					}

					$totalOrders = 0;
					$totalSpend = 0;
					$lastOrdered = 0;

					foreach($contacts as $contactId) {
						$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Order_Count, SUM(o.SubTotal - o.TotalDiscount) AS Order_Total, MAX(o.Created_On) AS Last_Ordered FROM contact AS c INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID INNER JOIN orders AS o ON o.Customer_ID=cu.Customer_ID WHERE o.Status<>'Cancelled' AND c.Contact_ID=%d", mysql_real_escape_string($contactId)));
						$totalOrders += $data->Row['Order_Count'];
						$totalSpend += $data->Row['Order_Total'];
						$lastOrdered = (strtotime($data->Row['Last_Ordered']) > $lastOrdered) ? strtotime($data->Row['Last_Ordered']) : $lastOrdered;
						$data->Disconnect();
					}

					if($lastOrdered > (time() - 2592000)) {
						$class = 'dataRowGreen';
					} elseif($lastOrdered > (time() - (2592000 * 2))) {
						$class = 'dataRowAmber';
					} else {
						$class = 'dataRowRed';
					}
					?>

					<tr class="dataRow <?php echo $class; ?>">
						<td class="dataOrdered" align="left"><?php print $table->Table->Row['Contact_ID']; ?></td>
						<td align="left"><?php print $table->Table->Row['Org_Name']; ?>&nbsp;</td>
						<td align="left"><?php print trim(sprintf('%s %s', $table->Table->Row['Name_First'], $table->Table->Row['Name_Last'])); ?>&nbsp;</td>
						<td align="left"><?php print $table->Table->Row['Phone_1']; ?>&nbsp;</td>
						<td align="left"><?php print $table->Table->Row['Address']; ?>&nbsp;</td>
						<td align="left"><?php print $table->Table->Row['Zip']; ?>&nbsp;</td>
						<td align="left"><?php print $totalOrders; ?></td>
						<td align="left">&pound;<?php print number_format($totalSpend, 2, '.', ','); ?></td>
						<td align="left"><?php print date('Y-m-d H:i:s', $lastOrdered); ?></td>
						<td nowrap="nowrap" align="center" width="16"><a href="contact_profile.php?cid=<?php print $table->Table->Row['Contact_ID']; ?>"><img src="./images/folderopen.gif" alt="Open Contact" border="0"></a></td>
						<td nowrap="nowrap" align="center" width="16"><a href="javascript:confirmRequest('contact_group_assoc.php?action=remove&confirm=true&gid=<?php print $_REQUEST['gid']; ?>&id=<?php print $table->Table->Row['Contact_Group_Assoc_ID']; ?>','Are you sure you want to remove this contact?');"><img src="./images/aztector_6.gif" alt="Remove" border="0"></a></td>
					</tr>

				<?php
				$table->Table->Next();
				}
			} else {
				?>

				<tr class="dataRow">
					<td align="left" colspan="11">No Records Found</td>
				</tr>

				<?php
			}
			?>
		</tbody>
	</table><br />

	<?php
	echo "<br>";
	$table->DisplayNavigation();

	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add contacts" class="btn" onclick="window.location.href=\'contact_group_assoc.php?action=add&gid=%d\'" />&nbsp;', $group->ID);
	echo sprintf('<input type="button" name="removeall" value="remove all" class="btn" onclick="window.location.href=\'contact_group_assoc.php?action=removeall&gid=%d\'" />', $group->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>