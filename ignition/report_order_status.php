<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

$warehouse = array();

$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, o.Org_Name FROM warehouse AS w INNER JOIN supplier AS s ON s.Supplier_ID=w.Type_Reference_ID AND s.Is_Favourite='Y' INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE w.Type='S' ORDER BY o.Org_Name ASC"));
while($data->Row) {
	$warehouse[$data->Row['Warehouse_ID']] = array(	'Name' => $data->Row['Org_Name'],
													'OrdersPending' => 0,
													'OrdersBackordered' => 0);

	$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Despatch_From_ID=%d WHERE o.Status LIKE 'Packing' OR o.Status LIKE 'Partially Despatched'", $data->Row['Warehouse_ID']));
	$warehouse[$data->Row['Warehouse_ID']]['OrdersPending'] += $data2->Row['Count'];
	$data2->Disconnect();

	$data2 = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Count FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID AND ol.Despatch_From_ID=%d WHERE o.Status LIKE 'Packing' OR o.Status LIKE 'Partially Despatched' AND o.Backordered='Y'", $data->Row['Warehouse_ID']));
	$warehouse[$data->Row['Warehouse_ID']]['OrdersBackordered'] += $data2->Row['Count'];
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();

$page = new Page('Order Status Report', '');
$page->Display('header');
?>

<h3>Outstanding Orders</h3>
<p>Order counts for your favourite warehouse suppliers.</p>

<table width="100%" border="0" >
	<tr>
		<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Warehouse</strong></td>
		<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Pending Orders</strong></td>
		<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Backorders</strong></td>
	</tr>

	<?php
	foreach($warehouse as $warehouseItem) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td align="left"><?php echo $warehouseItem['Name']; ?></td>
			<td align="left"><?php echo number_format($warehouseItem['OrdersPending'], 0, '.', ''); ?></td>
			<td align="left"><?php echo number_format($warehouseItem['OrdersBackordered'], 0, '.', ''); ?></td>
		</tr>

		<?php
	}
	?>

</table>

<?php
$page->Display('footer');

require_once('lib/common/app_footer.php');
?>