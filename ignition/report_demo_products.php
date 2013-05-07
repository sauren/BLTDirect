<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

$products = array();

$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU, p.Demo_Notes, SUM(ol.Quantity) AS Quantity, u.User_ID, CONCAT_WS(' ', pr.Name_First, pr.Name_Last) AS User FROM product AS p LEFT JOIN order_line AS ol ON ol.Product_ID=p.Product_ID LEFT JOIN users AS u ON u.User_ID=p.Created_By LEFT JOIN person AS pr ON pr.Person_ID=u.Person_ID WHERE p.Is_Demo_Product='Y' AND p.Is_Demo_Processed='N' GROUP BY p.Product_ID ORDER BY p.Product_Title ASC"));
while($data->Row) {
	$products[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

for($i=0; $i<count($products); $i++) {
    $form->AddField('select_'.$products[$i]['Product_ID'], 'Select', 'checkbox', 'N', 'boolean', 1, 1, false);
    $form->AddField('notes_'.$products[$i]['Product_ID'], sprintf('Notes for \'%s\'', $products[$i]['Product_Title']), 'textarea', $products[$i]['Demo_Notes'], 'anything', null, null, false);
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['deleteselected'])) {
		if($form->Validate()) {
			$product = new Product();

			for($i=0; $i<count($products); $i++) {
	            if($form->GetValue('select_'.$products[$i]['Product_ID']) == 'Y') {
					$product->Delete($products[$i]['Product_ID']);
				}
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	} elseif(isset($_REQUEST['processselected'])) {
		if($form->Validate()) {
			for($i=0; $i<count($products); $i++) {
	            if($form->GetValue('select_'.$products[$i]['Product_ID']) == 'Y') {
	            	$product = new Product($products[$i]['Product_ID']);
	            	$product->IsDemoProcessed = 'Y';
					$product->Update();
				}
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	} elseif(isset($_REQUEST['update'])) {
		if($form->Validate()) {
			for($i=0; $i<count($products); $i++) {
	        	$product = new Product($products[$i]['Product_ID']);
	           	$product->DemoNotes = $form->GetValue('notes_'.$products[$i]['Product_ID']);
				$product->Update();
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}
}

$page = new Page('Demo Products Report', '');
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('confirm');
?>

<br />
<h3>Demo Products</h3>
<p>Listing <strong><?php echo count($products); ?></strong> demo products.</p>

<table width="100%" border="0" >
	<tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left" nowrap="nowrap"><strong>Quickfind ID</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left" nowrap="nowrap"><strong>Name</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left" nowrap="nowrap"><strong>SKU</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left" nowrap="nowrap"><strong>Creator</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right" nowrap="nowrap"><strong>Quantity Sold</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left" nowrap="nowrap"><strong>Notes</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" width="1%" nowrap="nowrap">&nbsp;</td>
	</tr>

	<?php
	if(count($products) > 0) {
		for($i=0; $i<count($products); $i++) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><a href="product_profile.php?pid=<?php echo $products[$i]['Product_ID']; ?>"><?php echo $products[$i]['Product_ID']; ?></a></td>
				<td><?php echo $products[$i]['Product_Title']; ?></td>
				<td><?php echo $products[$i]['SKU']; ?></td>
				<td><?php echo $products[$i]['User']; ?></td>
				<td align="right"><?php echo $products[$i]['Quantity']; ?></td>
				<td><?php echo $form->GetHTML('notes_'.$products[$i]['Product_ID']); ?></td>
				<td align="center"><?php echo $form->GetHTML('select_'.$products[$i]['Product_ID']); ?></td>
			</tr>

			<?php
		}
	} else {
		?>

		<tr>
			<td align="center" colspan="7">There are no items available for viewing.</td>
		</tr>

		<?php
	}
	?>

</table>
<br />

<input type="submit" name="processselected" value="mark as dealt with" class="btn" />
<input type="submit" name="update" value="update" class="btn" />

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');