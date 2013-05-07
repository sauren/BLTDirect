<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

if(isset($_REQUEST['confirm'])) {
	foreach($_REQUEST as $key=>$value) {
		if(preg_match('/shipping_class_([0-9]+)/', $key, $matches)) {
			new DataQuery(sprintf("UPDATE product SET Shipping_Class_ID=%d WHERE Product_ID=%d", mysql_real_escape_string($value), mysql_real_escape_string($matches[1])));
		}
	}

	redirect(sprintf('Location: %s', $_SERVER['PHP_SELF']));
}

$classes = array();
$classes = array('Shipping_Class_ID' => 0, 'Shipping_Class_Title' => '');

$data = new DataQuery("SELECT Shipping_Class_ID, Shipping_Class_Title FROM shipping_class ORDER BY Shipping_Class_Title ASC");
while($data->Row) {
	$classes[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$page = new Page("Uncategorised Products Report","Here you can see products with no category.");
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('confirm');
?>

<h3>Products Without A Category</h3>
<p>All products with no category.</p>

<table width="100%" border="0">
  <tr>
	<td style="border-bottom:1px solid #aaaaaa"><strong>Product Title</strong></td>
	<td style="border-bottom:1px solid #aaaaaa"><strong>SKU</strong></td>
	<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quickfind</strong></td>
	<td style="border-bottom:1px solid #aaaaaa"><strong>Shipping Class</strong></td>
  </tr>

	<?php
	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU, p.Shipping_Class_ID FROM product AS p LEFT JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID LEFT JOIN product_categories AS pc ON pc.Category_ID=c.Category_ID WHERE c.Product_ID IS NULL OR pc.Category_ID IS NULL GROUP BY p.Product_ID"));
	while($data->Row) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo strip_tags($data->Row['Product_Title']); ?></td>
			<td><?php echo $data->Row['SKU']; ?></td>
			<td align="right"><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']?>" target="_blank"><?php echo $data->Row['Product_ID']; ?></a></td>
            <td>
				<select name="shipping_class_<?php echo $data->Row['Product_ID']; ?>">

					<?php
					foreach($classes as $class) {
						echo sprintf('<option value="%d"%s>%s</option>', $class['Shipping_Class_ID'], ($class['Shipping_Class_ID'] == $data->Row['Shipping_Class_ID']) ? ' selected="selected"' : '', $class['Shipping_Class_Title']);
					}
					?>

				</select>
			</td>
		</tr>

		<?php
		$data->Next();
	}
	$data->Disconnect();
	?>

</table>
<br />

<input type="submit" name="update" value="update" class="btn" />

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');