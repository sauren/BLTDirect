<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(2);
view();
exit();


function view() {
	$page = new Page("Unsupplied Products Report","Here you can see the products that do not have any suppliers");
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	?>

	<h3><br />Products Without A Supplier</h3>
	<p>All unsupplied products.</p>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Title</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>SKU</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="center"><strong>Add Suppliers</strong></td>
	  </tr>

	  <?php
		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU FROM product AS p LEFT JOIN supplier_product AS s ON p.Product_ID=s.Product_ID WHERE s.Supplier_Product_ID IS NULL GROUP BY Product_ID"));

		while($data->Row) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo strip_tags($data->Row['Product_Title']); ?></td>
				<td><?php echo $data->Row['SKU']; ?></td>
				<td align="right"><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']?>"><?php echo $data->Row['Product_ID']; ?></a></td>
				<td align="center"><a href="supplier_product.php?pid=<?php echo $data->Row['Product_ID']; ?>"><img src="./images/icon_edit_1.gif" alt="Edit the suppliers for this product" border="0"></a></td>
	  		</tr>

	  		<?php

			$data->Next();
		}
	  ?>

	  </table>

	<?php
	$page->Display('footer');
}
?>