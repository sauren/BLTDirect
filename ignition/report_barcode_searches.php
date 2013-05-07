<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/DataTable.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/Product.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/Form.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/StandardWindow.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/StandardForm.php');

$barcodes = array();

$searches = explode("\n", file_get_contents($GLOBALS['DATA_DIR_FS'].'local/logs/barcodes.txt'));

foreach($searches as $search) {
	$key = trim($search);

	if(!empty($key)) {
		if(!isset($barcodes[$key])) {
			$barcodes[$key] = 0;
		}

		$barcodes[$key]++;
	}
}

ksort($barcodes);

$page = new Page('Barcode Searches Report', '');
$page->Display('header');
?>

<h3>Barcode Searches</h3>
<br />

<table width="100%" border="0" cellpadding="3" cellspacing="0">
	<tr style="background-color: #eeeeee;">
		<td style="border-bottom: 1px solid #dddddd;"><strong>Barcode</strong></td>
		<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Frequency</strong></td>
	</tr>

	<?php
	foreach($barcodes as $key=>$value) {
		?>

		<tr>
			<td style="border-top:1px solid #dddddd;"><?php echo $key; ?></td>
			<td style="border-top:1px solid #dddddd;" align="right"><?php echo $value; ?></td>
		</tr>

		<?php
	}
	?>

</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');