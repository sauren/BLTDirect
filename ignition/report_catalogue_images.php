<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$page = new Page('Catalogue Images Report', '');
$page->Display('header');
?>

<br />
<h3>Missing Category Images</h3>
<p>Listing all categories which are missing at least one hi-res catalogue image.</p>

<table width="100%" border="0" >
	<tr>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>ID#</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Category</strong></td>
	</tr>

	<?php
	$data = new DataQuery(sprintf("SELECT pc.Category_ID, pc.Category_Title FROM product_categories AS pc LEFT JOIN category_catalogue_image AS cci ON pc.Category_ID=cci.Category_ID WHERE cci.Category_Catalogue_Image_ID IS NULL ORDER BY pc.Category_Title ASC"));
	while($data->Row) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><a href="category_catalogue_images.php?cat=<?php echo $data->Row['Category_ID']; ?>"><?php echo $data->Row['Category_ID']; ?></a></td>
			<td><?php echo $data->Row['Category_Title']; ?></td>
		</tr>

		<?php
		$data->Next();
	}
	$data->Disconnect();
	?>

</table>

<?php
$page->Display('footer');

require_once('lib/common/app_footer.php');
?>