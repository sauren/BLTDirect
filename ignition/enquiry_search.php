<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->secure(2);
	view();
	exit;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('search', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('enquiryid', 'Enquiry ID', 'text', '', 'numeric_unsigned', 1, 11, false);
	$form->AddField('keywords', 'Keywords', 'textarea', '', 'anything', 1, 2048, false, 'rows="5"');
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
	$form->AddField('pending', 'Pending Action', 'select', '', 'alpha_numeric', 0, 1, false);
	$form->AddOption('pending', '', '-- All --');
	$form->AddOption('pending', 'Y', 'Yes');
	$form->AddOption('pending', 'N', 'No');
	$form->AddField('category', 'Category Type', 'select', '', 'numeric_unsigned', 1, 11, false);
	$form->AddOption('category', '0', '-- All --');

	$data = new DataQuery(sprintf("SELECT * FROM enquiry_type ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('category', $data->Row['Enquiry_Type_ID'], $data->Row['Name']);
		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Enquiry Search','');
	$page->Display('header');

	$sql = "";

	if(isset($_REQUEST['search']) && strtolower($_REQUEST['search']) == "true"){
		if($form->Validate()){
			$sql = sprintf("SELECT e.*, et.Name, CONCAT_WS(' ' , p.Name_First, p.Name_Initial, p.Name_Last) AS Customer, CONCAT_WS(' ', p2.Name_First, p2.Name_Initial, p2.Name_Last) AS Owner, o.Org_Name FROM enquiry AS e LEFT JOIN enquiry_type AS et ON et.Enquiry_Type_ID=e.Enquiry_Type_ID LEFT JOIN users AS u ON u.User_ID=e.Owned_By LEFT JOIN person AS p2 ON p2.Person_ID=u.Person_ID INNER JOIN customer AS cu ON cu.Customer_ID=e.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN address AS a ON a.Address_ID=p.Address_ID INNER JOIN enquiry_line AS el ON e.Enquiry_ID=el.Enquiry_ID WHERE c.Contact_Type='I' ");

			if(strlen($form->GetValue('enquiryid')) > 0) {
				$sql .= sprintf(" AND e.Enquiry_ID=%d", mysql_real_escape_string($form->GetValue('enquiryid')));
			}

			if(strlen($form->GetValue('keywords')) > 0) {
				$parts = explode(' ', mysql_real_escape_string($form->GetValue('keywords')));
				$partsSql = '';

				if(count($parts) > 0) {
					for($i=0; $i<count($parts); $i++) {
						$partsSql .= sprintf(" OR el.Message LIKE '%%%s%%'", $parts[$i]);
						$partsSql .= sprintf(" OR e.Subject LIKE '%%%s%%'", $parts[$i]);
					}

					$partsSql = substr($partsSql, 4, strlen($partsSql));

					$sql .= sprintf(" AND (%s)", $partsSql);
				}
			}

			if(strlen($form->GetValue('pending')) > 0) {
				$sql .= sprintf(" AND e.Is_Pending_Action='%s'", mysql_real_escape_string($form->GetValue('pending')));
			}

			if($form->GetValue('category') > 0) {
				$sql .= sprintf(" AND e.Enquiry_Type_ID=%d", mysql_real_escape_string($form->GetValue('category')));
			}

			if(strlen($form->GetValue('fname')) > 0) {
				$sql .= sprintf(" AND p.Name_First LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('fname')));
			}

			if(strlen($form->GetValue('lname')) > 0) {
				$sql .= sprintf(" AND p.Name_Last LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('lname')));
			}

			if(strlen($form->GetValue('org')) > 0) {
				$sql .= sprintf(" AND o.Org_Name LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('org')));
			}

			if(strlen($form->GetValue('email')) > 0) {
				$sql .= sprintf(" AND p.Email LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('email')));
			}

			if(strlen($form->GetValue('phone')) > 0) {
				$sql .= sprintf(" AND (REPLACE(p.Phone_1, ' ', '') LIKE REPLACE('%%%s%%', ' ', '') OR REPLACE(p.Phone_2, ' ', '') LIKE REPLACE('%%%s%%', ' ', ''))", mysql_real_escape_string($form->GetValue('phone')), mysql_real_escape_string($form->GetValue('phone')));
			}

			if(strlen($form->GetValue('mobile')) > 0) {
				$sql .= sprintf(" AND REPLACE(p.Mobile, ' ', '') LIKE REPLACE('%%%s%%', ' ', '')", mysql_real_escape_string($form->GetValue('mobile')));
			}

			if(strlen($form->GetValue('fax')) > 0) {
				$sql .= sprintf(" AND REPLACE(p.Fax, ' ', '') LIKE REPLACE('%%%s%%', ' ', '')", mysql_real_escape_string($form->GetValue('fax')));
			}

			if(strlen($form->GetValue('address1')) > 0) {
				$sql .= sprintf(" AND a.Address_Line_1 LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('address1')));
			}

			if(strlen($form->GetValue('address2')) > 0) {
				$sql .= sprintf(" AND a.Address_Line_2 LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('address2')));
			}

			if(strlen($form->GetValue('address3')) > 0) {
				$sql .= sprintf(" AND a.Address_Line_3 LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('address3')));
			}

			if(strlen($form->GetValue('city')) > 0) {
				$sql .= sprintf(" AND a.City LIKE '%%%s%%'", mysql_real_escape_string($form->GetValue('city')));
			}

			if(strlen($form->GetValue('postcode')) > 0) {
				$sql .= sprintf(" AND REPLACE(a.Zip, ' ', '') LIKE REPLACE('%%%s%%', ' ', '')", mysql_real_escape_string($form->GetValue('postcode')));
			}

			$sql .= " GROUP BY e.Enquiry_ID";
		} else {
			echo $form->GetError();
			echo "<br />";
		}
	}

	if(!empty($sql)){
		echo '<table width="100%"><tr><td valign="top">';
	}

	$window = new StandardWindow("Search for a Contact.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('search');
	echo $window->Open();
	echo $window->AddHeader('Search for contacts by any of the below fields.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('enquiryid'), $form->GetHTML('enquiryid'));
	echo $webForm->AddRow($form->GetLabel('keywords'), $form->GetHTML('keywords'));
	echo $webForm->AddRow($form->GetLabel('pending'), $form->GetHTML('pending'));
	echo $webForm->AddRow($form->GetLabel('category'), $form->GetHTML('category'));
	echo $webForm->AddRow($form->GetLabel('fname'), $form->GetHTML('fname'));
	echo $webForm->AddRow($form->GetLabel('lname'), $form->GetHTML('lname'));
	echo $webForm->AddRow($form->GetLabel('org'), $form->GetHTML('org'));
	echo $webForm->AddRow($form->GetLabel('email'), $form->GetHTML('email'));
	echo $webForm->AddRow($form->GetLabel('phone'), $form->GetHTML('phone'));
	echo $webForm->AddRow($form->GetLabel('mobile'), $form->GetHTML('mobile'));
	echo $webForm->AddRow($form->GetLabel('fax'), $form->GetHTML('fax'));
	echo $webForm->AddRow($form->GetLabel('postcode'), $form->GetHTML('postcode'));
	echo $webForm->AddRow($form->GetLabel('address1'), $form->GetHTML('address1'));
	echo $webForm->AddRow($form->GetLabel('address2'), $form->GetHTML('address2'));
	echo $webForm->AddRow($form->GetLabel('address3'), $form->GetHTML('address3'));
	echo $webForm->AddRow($form->GetLabel('city'), $form->GetHTML('city'));
	echo $webForm->AddRow('', '<input type="submit" name="searchButton" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	if(!empty($sql)){
		echo '</td><td width="10"></td><td valign="top">';

		$table = new DataTable("cl");
		$table->SetSQL($sql);
		$table->Order = "DESC";
		$table->SetMaxRows(25);
		$table->SetOrderBy("Modified_On");
		$table->Finalise();
		?>

		<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
			<thead>
				<tr>
					<th nowrap="nowrap" class="dataHeadOrdered">Modified On</th>
					<th nowrap="nowrap">Organisation</th>
					<th nowrap="nowrap">Customer</th>
					<th nowrap="nowrap">Reference</th>
					<th nowrap="nowrap">Type</th>
					<th nowrap="nowrap">Owner</th>
					<th colspan="2">&nbsp;</th>
				</tr>
			</thead>
			<tbody>

				<?php
				$enquiry = new Enquiry();

				$data = new DataQuery($table->SQL);
				if($data->TotalRows > 0) {
					while($data->Row) {
						$enquiry->ID = $data->Row['Enquiry_ID'];
						$enquiry->Prefix = $data->Row['Prefix'];
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td class="dataOrdered" align="left"><?php echo $data->Row['Modified_On']; ?></td>
							<td align="left"><?php echo $data->Row['Org_Name']; ?>&nbsp;</td>
							<td align="left"><?php echo $data->Row['Customer']; ?>&nbsp;</td>
							<td align="left"><?php echo $enquiry->GetReference(); ?>&nbsp;</td>
							<td align="left"><?php echo $data->Row['Name']; ?>&nbsp;</td>
							<td align="left"><?php echo trim(sprintf('%s %s', $data->Row['Owner_First'], $data->Row['Owner_Last'])); ?>&nbsp;</td>
							<td nowrap align="center" width="16"><a href="enquiry_details.php?enquiryid=<?php echo $data->Row['Enquiry_ID']; ?>"><img src="./images/folderopen.gif" alt="Open Enquiry" border="0"></a></td>
							<td nowrap align="center" width="16"><a href="javascript:confirmRequest('<?php echo $_SERVER['PHP_SELF']; ?>?action=remove&confirm=true&id=<?php echo $data->Row['Enquiry_ID']; ?>','Are you sure you want to remove this enquiry?');"><img src="./images/aztector_6.gif" alt="Remove" border="0"></a></td>
						</tr>

						<?php
						$data->Next();
					}
				} else {
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td align="left" colspan="10">No Records Found</td>
					</tr>

					<?php
				}
				$data->Disconnect();
				?>

			</tbody>
		</table><br />

		<?php
		$table->DisplayNavigation();

		echo '</td></tr></table>';
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');

	if(isset($_REQUEST['id'])) {
		$enquiry = new Enquiry($_REQUEST['id']);
		$enquiry->Delete();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}