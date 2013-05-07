<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

$barcodes = array();

$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, pb.Barcode, pb.Brand, pb.Quantity FROM product_barcode AS pb INNER JOIN product AS p ON p.Product_ID=pb.ProductID ORDER BY p.Product_Title ASC, pb.Brand ASC, pb.Quantity ASC"));
while($data->Row) {
	$barcodes[] = $data->Row;
	
	$data->Next();
}
$data->Disconnect();

$page = new Page('Order Status Report', '');
$page->Display('header');
?>

<h3>Barcodes</h3>
<p>Listing <strong><?php echo count($barcodes); ?></strong> product barcodes.</p>

<table width="100%" border="0" >
	<tr>
		<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Product ID</strong></td>
		<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Name</strong></td>
		<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Barcode</strong></td>
		<td align="left" style="border-bottom:1px solid #aaaaaa" align="left"><strong>Brand</strong></td>
		<td align="left" style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quantity</strong></td>
	</tr>

	<?php
	if(!empty($barcodes)) {
		foreach($barcodes as $barcode) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="left"><?php echo $barcode['Product_ID']; ?></td>
				<td align="left"><a href="product_profile.php?pid=<?php echo $barcode['Product_ID']; ?>"><?php echo $barcode['Product_Title']; ?></a></td>
				<td align="left"><?php echo $barcode['Barcode']; ?></td>
				<td align="left"><?php echo $barcode['Brand']; ?></td>
				<td align="right"><?php echo $barcode['Quantity']; ?></td>
			</tr>

			<?php
		}
	} else {
		?>
		
		<tr>
			<td align="center" colspan="5">There are no items available for viewing.</td>
		</tr>
		
		<?php
	}
	?>

</table>

<?php
$page->Display('footer');

require_once('lib/common/app_footer.php');
?>