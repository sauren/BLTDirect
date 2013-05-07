<?php
ini_set('max_execution_time', '900');

require_once('lib/common/app_header.php');

$session->Secure(2);
report();
exit();

function report(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'clear', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	if(strtolower($_REQUEST['confirm']) == 'true') {
		$data = new DataQuery(sprintf("SELECT Product_ID FROM product WHERE Associative_Product_Title<>''"));
		while($data->Row) {
			if(isset($_REQUEST[$data->Row['Product_ID']])) {
				$data2 = new DataQuery(sprintf("UPDATE product SET Associative_Product_Title='' WHERE Product_ID=%d", $data->Row['Product_ID']));
				$data2->Disconnect();
			}

			$data->Next();
		}
		$data->Disconnect();

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$page = new Page('Product Association Report', '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	?>

	<br />
	<h3>Products Associated</h3>
	<p>Listing products associated between BLT Direct.com and BLT Direct.co.uk sites.</p>

	<input type="submit" name="clear" value="clear associations" class="btn" /><br /><br />

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left" valign="top"><strong>Product ID</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left" valign="top"><strong>Product</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left" valign="top"><strong>Association</strong></td>
		<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
	  </tr>

		<?php
		$data = new DataQuery(sprintf("SELECT Product_ID, Product_Title, Associative_Product_Title FROM product WHERE Associative_Product_Title<>''"));
		if($data->TotalRows > 0) {
			while($data->Row) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td align="left"><a href="product_profile.php?pid=<?php print $data->Row['Product_ID']; ?>" target="_blank"><?php print $data->Row['Product_ID']; ?></a></td>
					<td align="left"><?php print strip_tags($data->Row['Product_Title']); ?></td>
					<td align="left"><?php print $data->Row['Associative_Product_Title']; ?></td>
					<td><?php print sprintf('<input type="checkbox" name="%d" />', $data->Row['Product_ID']); ?></td>
				</tr>

				<?php
				$data->Next();
			}
		} else {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="center" colspan="4">There are no product associations.</td>
			</tr>

			<?php
		}
		$data->Disconnect();

		?>

	</table><br />

	<input type="submit" name="clear" value="clear associations" class="btn" />

	<?php
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>