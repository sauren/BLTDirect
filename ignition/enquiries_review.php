<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');

	$page = new Page('Review Enquiries', 'Below is a list of enquiries requiring reviewing.');
	$page->Display('header');

	$sqlType = (isset($_REQUEST['type'])) ? sprintf(" AND et.Developer_Key LIKE '%s'", $_REQUEST['type']) : '';

	$table = new DataTable('enquiries');
	$table->SetSQL(sprintf("SELECT e.*, et.Name, o.Org_Name, p.Name_First, p.Name_Last, p2.Name_First AS Owner_First, p2.Name_Last AS Owner_Last FROM enquiry AS e LEFT JOIN enquiry_type AS et ON et.Enquiry_Type_ID=e.Enquiry_Type_ID INNER JOIN customer AS c ON c.Customer_ID=e.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID LEFT JOIN contact AS n2 ON n.Parent_Contact_ID=n2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=n2.Org_ID LEFT JOIN users AS u ON u.User_ID=e.Owned_By LEFT JOIN person AS p2 ON p2.Person_ID=u.Person_ID WHERE e.Status NOT LIKE 'Closed' AND e.Review_On<>'0000-00-00 00:00:00' AND e.Review_On<=ADDDATE(NOW(), INTERVAL 1 DAY) AND (e.Owned_By=%d OR e.Owned_By=0) %s", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($sqlType)));
	$table->SetMaxRows(25);
	$table->SetOrderBy("Review_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->ExecuteSQL();
	?>

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th nowrap="nowrap" class="dataHeadOrdered">Review On</th>
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
						<td class="dataOrdered" align="left" <?php echo ($data->Row['Is_Big_Enquiry'] == 'Y') ? 'style="background-color: #FF9D9D;"' : (($data->Row['Is_Trade_Enquiry'] == 'Y') ? 'style="background-color: #FEFC6B;"' : ''); ?>><?php echo $data->Row['Review_On']; ?></td>
						<td align="left" <?php echo ($data->Row['Is_Big_Enquiry'] == 'Y') ? 'style="background-color: #FFB3B3;"' : (($data->Row['Is_Trade_Enquiry'] == 'Y') ? 'style="background-color: #FEFDB2;"' : ''); ?>><?php echo $data->Row['Org_Name']; ?>&nbsp;</td>
						<td align="left" <?php echo ($data->Row['Is_Big_Enquiry'] == 'Y') ? 'style="background-color: #FFB3B3;"' : (($data->Row['Is_Trade_Enquiry'] == 'Y') ? 'style="background-color: #FEFDB2;"' : ''); ?>><?php echo trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])); ?>&nbsp;</td>
						<td align="left" <?php echo ($data->Row['Is_Big_Enquiry'] == 'Y') ? 'style="background-color: #FFB3B3;"' : (($data->Row['Is_Trade_Enquiry'] == 'Y') ? 'style="background-color: #FEFDB2;"' : ''); ?>><?php echo $enquiry->GetReference(); ?>&nbsp;</td>
						<td align="left" <?php echo ($data->Row['Is_Big_Enquiry'] == 'Y') ? 'style="background-color: #FFB3B3;"' : (($data->Row['Is_Trade_Enquiry'] == 'Y') ? 'style="background-color: #FEFDB2;"' : ''); ?>><?php echo $data->Row['Name']; ?>&nbsp;</td>
						<td align="left" <?php echo ($data->Row['Is_Big_Enquiry'] == 'Y') ? 'style="background-color: #FFB3B3;"' : (($data->Row['Is_Trade_Enquiry'] == 'Y') ? 'style="background-color: #FEFDB2;"' : ''); ?>><?php echo trim(sprintf('%s %s', $data->Row['Owner_First'], $data->Row['Owner_Last'])); ?>&nbsp;</td>
						<td nowrap align="center" <?php echo ($data->Row['Is_Big_Enquiry'] == 'Y') ? 'style="background-color: #FFB3B3;"' : (($data->Row['Is_Trade_Enquiry'] == 'Y') ? 'style="background-color: #FEFDB2;"' : ''); ?> width="16"><a href="enquiry_details.php?enquiryid=<?php echo $data->Row['Enquiry_ID']; ?>"><img src="./images/folderopen.gif" alt="Open Enquiry" border="0"></a></td>
						<td nowrap align="center" <?php echo ($data->Row['Is_Big_Enquiry'] == 'Y') ? 'style="background-color: #FFB3B3;"' : (($data->Row['Is_Trade_Enquiry'] == 'Y') ? 'style="background-color: #FEFDB2;"' : ''); ?> width="16"><a href="javascript:confirmRequest('<?php echo $_SERVER['PHP_SELF']; ?>?action=remove&confirm=true&id=<?php echo $data->Row['Enquiry_ID']; ?>','Are you sure you want to remove this enquiry?');"><img src="./images/aztector_6.gif" alt="Remove" border="0"></a></td>
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

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');

	if(isset($_REQUEST['id'])) {
		$enquiry = new Enquiry();
		$enquiry->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}
?>