<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
report();
exit();

function report(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page('Google Base Report: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	?>

	<br />
	<h3>Invalid Google Base Products</h3>
	<p>Products missing required Google Base fields are listed below.</p>

	<table width="100%" border="0" >
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Missing</strong></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.Product_Blurb, p.Product_Description FROM product AS p WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' AND p.Is_Complementary='N' ORDER BY p.Product_ID ASC"));
		while($data->Row) {
			$description = (strlen(trim(htmlentities($data->Row['Product_Description']))) > 0) ? trim(htmlentities($data->Row['Product_Description'])) : trim(htmlentities($data->Row['Product_Blurb']));
			$price = null;

			$data2 = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=NOW() ORDER BY Price_Starts_On DESC LIMIT 0, 1", mysql_real_escape_string($data->Row['Product_ID'])));
			if($data2->TotalRows > 0) {
				$price = 1;
			}
			$data2->Disconnect();

			if(is_null($price) || strlen($description) == 0) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo strip_tags($data->Row['Product_Title']); ?></td>
					<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo $data->Row['Product_ID']; ?></a></td>
					<td><?php echo (is_null($price) && strlen($description) == 0) ? 'Price &amp; Description' : (is_null($price) ? 'Price' : 'Description'); ?></td>
				</tr>

				<?php
			}

			$data->Next();
		}
		$data->Disconnect();
		?>

	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>