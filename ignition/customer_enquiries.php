<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');

$customer = new Customer($_REQUEST['customer']);
$customer->Contact->Get();
$tempHeader = "";

if($customer->Contact->HasParent){
	$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s</a> &gt; ", $customer->Contact->Parent->ID, $customer->Contact->Parent->Organisation->Name);
}
$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s %s</a> &gt;", $customer->Contact->ID, $customer->Contact->Person->Name, $customer->Contact->Person->LastName);

$page = new Page(sprintf('%s Enquiry History for %s', $tempHeader, $customer->Contact->Person->GetFullName()),sprintf('Below is the enquiry history for %s only.', $customer->Contact->Person->GetFullName()));
$page->Display('header');

$table = new DataTable("results");
$table->SetSQL(sprintf("SELECT * FROM enquiry WHERE Customer_ID=%d", mysql_real_escape_string($customer->ID)));
$table->SetMaxRows(25);
$table->SetOrderBy("Created_On");
$table->Order = "DESC";
$table->Finalise();
?>

<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
	<thead>
		<tr>
			<th class="dataHeadOrdered">Enquired On</th>
			<th nowrap="nowrap">Reference</th>
			<th nowrap="nowrap">Subject</th>
			<th nowrap="nowrap">Status</th>
			<th>&nbsp;</th>
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
					<td class="dataOrdered" align="left"><?php echo $data->Row['Created_On']; ?></td>
					<td align="left"><?php echo $enquiry->GetReference(); ?>&nbsp;</td>
					<td align="left"><?php print $data->Row['Subject']; ?>&nbsp;</td>
					<td align="left"><?php print $data->Row['Status']; ?>&nbsp;</td>
					<td nowrap align="center" width="16"><a href="enquiry_details.php?enquiryid=<?php echo $data->Row['Enquiry_ID']; ?>"><img src="./images/folderopen.gif" alt="Open Enquiry Details" border="0"></a></td>
				</tr>

				<?php
				$data->Next();
			}
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="left" colspan="5">No Records Found</td>
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