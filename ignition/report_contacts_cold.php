<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$page = new Page('Cold Contacts Report', '');
$page->Display('header');

$green = '#25D70E';
$yellow = '#D7D50C';
$red = '#D7550D';

$data = new DataQuery(sprintf("SELECT c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Contact_Name, MAX(od.Created_On) AS Last_Ordered_On, COUNT(DISTINCT od.Order_ID) AS Order_Count FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID LEFT JOIN customer AS cu ON c.Contact_ID=cu.Contact_ID LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID INNER JOIN contact_status AS cs ON c.Contact_Status_ID=cs.Contact_Status_ID WHERE cs.Name LIKE 'Cold' GROUP BY c.Contact_ID"));

echo sprintf('<br /><h3>Cold Contacts</h3>');
echo sprintf('<p>Listing %d cold contacts.</p>', $data->TotalRows);
?>

<table width="100%" border="0">
	<tr>
		<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Contact ID</strong></td>
		<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Contact</strong></td>
		<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Orders</strong></td>
		<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Last Ordered</strong></td>
	</tr>

	<?php
	$orders = 0;

	while($data->Row) {
		$orders += $data->Row['Order_Count'];
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><a href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php echo $data->Row['Contact_ID']; ?></a></td>
			<td><?php echo $data->Row['Contact_Name']; ?></td>
			<td><?php echo $data->Row['Order_Count']; ?></td>
			<td><?php echo cDatetime($data->Row['Last_Ordered_On'], 'shortdatetime'); ?></td>
		</tr>

		<?php
		$data->Next();
	}
	?>

	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><strong><?php echo $orders; ?></strong></td>
		<td>&nbsp;</td>
	</tr>
</table><br />

<?php
$data->Disconnect();

$page->Display('footer');

require_once('lib/common/app_footer.php');
?>