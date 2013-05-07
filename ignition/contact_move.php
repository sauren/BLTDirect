<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');

foreach($_REQUEST as $key=>$value) {
	if(substr($key, 0, 7) == 'exclude') {
		if(isset($_REQUEST[$key]) && ($_REQUEST[$key] == 'N')) {
			unset($_REQUEST[$key]);
		}
	}
}

$form = new Form($_SERVER['PHP_SELF'], 'get');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('contactid', 'Contact ID', 'text', '', 'numeric_unsigned', 1, 11, false);
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

$page = new Page('Search Contacts', '');
$page->Display('header');

$sqlSelect = '';
$sqlFrom = '';
$sqlWhere = '';

$isTemporary = '';

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()){
		$isTemporary = $form->GetValue('istemp');

		$sqlSelect = sprintf("SELECT cu.Customer_ID, c.Contact_ID, c.Parent_Contact_ID, c.Is_Active, c.Is_Customer, c.Is_Supplier, p.Name_First, p.Name_Initial, p.Name_Last, o.Org_Name ");
		$sqlFrom = sprintf("FROM contact AS c LEFT JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN address AS a ON a.Address_ID=p.Address_ID ");
		$sqlWhere = sprintf("WHERE c.Contact_Type='I'");

		if(strlen($form->GetValue('contactid')) > 0) {
			if($form->GetValue('excludecontactid') == 'N') {
				$sqlWhere .= sprintf(" AND c.Contact_ID=%d", mysql_real_escape_string($form->GetValue('contactid')));
			} else {
				$sqlWhere .= sprintf(" AND c.Contact_ID<>%d", mysql_real_escape_string($form->GetValue('contactid')));
			}
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
				$sqlWhere .= sprintf(" AND REPLACE(a.Zip, ' ', '') LIKE REPLACE('%%%s%%', ' ', '')", mysql_real_escape_string($form->GetValue('postcode')));
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
	} else {
		echo $form->GetError();
		echo "<br />";
	}
}

$form2 = new Form($_SERVER['PHP_SELF'], 'get');
$form2->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form2->AddField('id', 'Campaign ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form2->AddField('primary', 'Primary contact', 'radio', 0, 'numeric_unsigned', 1, 11, false);
$form2->AddField('contactid', 'Contact ID', 'hidden', '', 'numeric_unsigned', 1, 11, false);
$form2->AddField('fname', 'First Name', 'hidden', '', 'paragraph', 1, 255, false);
$form2->AddField('lname', 'Last Name', 'hidden', '', 'paragraph', 1, 255, false);
$form2->AddField('org', 'Organisation', 'hidden', '', 'paragraph', 1, 255, false);
$form2->AddField('email', 'Email Address', 'hidden', '', 'paragraph', 1, 255,false);
$form2->AddField('phone', 'Phone Number', 'hidden', '', 'telephone', 1, 32, false);
$form2->AddField('mobile', 'Mobile Number', 'hidden', '', 'telephone', 1, 32, false);
$form2->AddField('fax', 'Fax Number', 'hidden', '', 'telephone', 1, 32, false);
$form2->AddField('postcode', 'Postcode', 'hidden', '', 'anything', 1, 32, false);
$form2->AddField('address1', 'Property Name/Number', 'hidden', '', 'alpha_numeric', 1, 150, false);
$form2->AddField('address2', 'Street', 'hidden', '', 'alpha_numeric', 1, 150, false);
$form2->AddField('address3', 'Area', 'hidden', '', 'alpha_numeric', 1, 150, false);
$form2->AddField('city', 'City', 'hidden', '', 'alpha_numeric', 1, 150, false);
$form2->AddField('quotes', 'Quotes', 'hidden', '', 'anything', 1, 128, false);
$form2->AddField('orders', 'Orders', 'hidden', '', 'anything', 1, 128, false);
$form2->AddField('excludecontactid', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludefname', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludelname', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludeorg', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludeemail', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludephone', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludemobile', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludefax', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludepostcode', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludeaddress1', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludeaddress2', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludeaddress3', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);
$form2->AddField('excludecity', 'Exclude', 'hidden', 'N', 'boolean', 1, 1, false);

$contacts = array();

if(strlen($sqlSelect.$sqlFrom.$sqlWhere) > 0) {
	$table = new DataTable('Search');
	$table->SetSQL($sqlSelect.$sqlFrom.$sqlWhere);
	$table->SetTotalRowSQL("SELECT COUNT(*) AS TotalRows ".$sqlFrom.$sqlWhere);
	$table->SetMaxRows(100);
	$table->SetOrderBy('Contact_ID');
	$table->SetExtractVars();
	$table->Finalise();
	$table->ExecuteSQL();

	while($table->Table->Row) {
		$form2->AddField('move_'.$table->Table->Row['Contact_ID'], 'Move contact', 'checkbox', 'N', 'boolean', 1, 1, false);
		$form2->AddOption('primary', $table->Table->Row['Contact_ID'], '');

		$contactItem = array();
		$contactItem['Parent_Contact_ID'] = $table->Table->Row['Parent_Contact_ID'];
		$contactItem['Contact_ID'] = $table->Table->Row['Contact_ID'];
		$contactItem['Customer_ID'] = $table->Table->Row['Customer_ID'];
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
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['primary'])) {
		$primaryCustomer = '';

		for($i=0;$i<count($contacts);$i++) {
			if($_REQUEST['primary'] == $contacts[$i]['Contact_ID']) {
				$primaryCustomer = (is_numeric($contacts[$i]['Customer_ID']) && ($contacts[$i]['Customer_ID'] > 0)) ? 'Y' : 'N';
				break;
			}
		}
	}

	if($form2->Valid) {
		if(isset($_REQUEST['primary'])) {
			for($i=0;$i<count($contacts);$i++) {
				if($form2->GetValue('move_'.$contacts[$i]['Contact_ID']) == 'Y') {
					if($_REQUEST['primary'] != $contacts[$i]['Contact_ID']) {
						$data = new DataQuery(sprintf("SELECT Parent_Contact_ID FROM contact WHERE Contact_ID=%d", mysql_real_escape_string($_REQUEST['primary'])));
						if($data->TotalRows > 0) {
							$c = new Contact($contacts[$i]['Contact_ID']);
							$c->Parent->ID = $data->Row['Parent_Contact_ID'];
							$c->Update();
						}
						$data->Disconnect();
					}
				}
			}
			
			$validEntries = array();
			$sqlStr = '';

			foreach($_REQUEST as $key=>$value) {
				if(($key != 'primary') && (substr($key, 0, (strlen($key) >= 5) ? 5 : strlen($key)) != 'move')) {
					$validEntries[] = sprintf('%s=%s', $key, $value);
				}
			}

			if(count($validEntries) > 0) {
				$sqlStr = sprintf('?%s', implode('&', $validEntries));
			}

			redirect(sprintf("Location: %s", $sqlStr));
		}
	}
}

if(!$form2->Valid) {
	echo $form2->GetError();
	echo '<br />';
}

if(strlen($sqlSelect.$sqlFrom.$sqlWhere) > 0) {
	echo '<table width="100%"><tr><td valign="top">';
}

$window = new StandardWindow("Search for a Contact.");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('confirm');

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
echo $webForm->AddRow($form->GetLabel('orders'), $form->GetHTML('orders'));
echo $webForm->AddRow($form->GetLabel('quotes'), $form->GetHTML('quotes'));
echo $webForm->AddRow($form->GetLabel('istemp'), $form->GetHTML('istemp'));
echo $webForm->AddRow('', '<input type="submit" name="searchButton" value="search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

if(strlen($sqlSelect.$sqlFrom.$sqlWhere) > 0) {
	echo '</td><td width="10"></td><td valign="top">';

	echo $form2->Open();
	echo $form2->GetHTML('confirm');
	echo $form2->GetHTML('id');
	echo $form2->GetHTML('contactid');
	echo $form2->GetHTML('fname');
	echo $form2->GetHTML('lname');
	echo $form2->GetHTML('org');
	echo $form2->GetHTML('email');
	echo $form2->GetHTML('phone');
	echo $form2->GetHTML('mobile');
	echo $form2->GetHTML('fax');
	echo $form2->GetHTML('postcode');
	echo $form2->GetHTML('address1');
	echo $form2->GetHTML('address2');
	echo $form2->GetHTML('address3');
	echo $form2->GetHTML('city');
	echo $form2->GetHTML('quotes');
	echo $form2->GetHTML('orders');
	echo $form2->GetHTML('excludecontactid');
	echo $form2->GetHTML('excludefname');
	echo $form2->GetHTML('excludelname');
	echo $form2->GetHTML('excludeorg');
	echo $form2->GetHTML('excludeemail');
	echo $form2->GetHTML('excludephone');
	echo $form2->GetHTML('excludemobile');
	echo $form2->GetHTML('excludefax');
	echo $form2->GetHTML('excludepostcode');
	echo $form2->GetHTML('excludeaddress1');
	echo $form2->GetHTML('excludeaddress2');
	echo $form2->GetHTML('excludeaddress3');
	echo $form2->GetHTML('excludecity');
	?>

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th>&nbsp;</th>
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
			if(count($contacts) > 0) {
				for($i=0;$i<count($contacts);$i++) {
				?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td align="center" width="16">
							<?php
							if($contacts[$i]['Parent_Contact_ID'] > 0) {
								echo $form2->GetHTML('primary', $i+1);
							}
							?>
						</td>
						<td align="center" width="16"><?php echo $form2->GetHTML('move_'.$contacts[$i]['Contact_ID']); ?></td>
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
			} else {
				?>

				<tr class="dataRow">
					<td colspan="10">No Records Found</td>
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
	<input name="update" type="submit" id="update" value="update" class="btn"  />

	<?php
	echo $form2->Close();

	echo '</td></tr></table>';
}

$page->Display('footer');
require_once('lib/common/app_footer.php');