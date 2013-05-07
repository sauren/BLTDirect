<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LbukLinked.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

$connections = getSyncConnections();
$products = array();

for($i=0;$i<count($connections);$i++) {
	$data = new DataQuery(sprintf("SELECT Product_ID, Product_Title, Discontinued FROM product ORDER BY Product_ID ASC"), $connections[$i]['Connection']);
	while($data->Row) {
		$product = isset($products[$data->Row['Product_ID']]) ? $products[$data->Row['Product_ID']] : array();
		$product[$i] = $data->Row;

		$products[$data->Row['Product_ID']] = $product;

		$data->Next();
	}
	$data->Disconnect();
}

$highlights = array();

foreach($products as $id=>$product) {
	$highlight = false;

	$titles = array();
	$discontinued = array();

	for($j=0;$j<count($connections);$j++) {
		if(!isset($product[$j])) {
			$highlight = true;
		} else {
			$titles[$product[$j]['Product_Title']] = true;
			$discontinued[$product[$j]['Discontinued']] = true;
		}
	}

	if(count($titles) > 1) {
		//$highlight = true;
	}

	if(count($discontinued) > 1) {
		$highlight = true;
	}

	$highlights[$id] = $highlight;
}

ksort($products);

$remote = array();

$data = new DataQuery(sprintf("SELECT remoteId, localId FROM lbuk_linked"));
while($data->Row) {
	$remote[$data->Row['remoteId']] = $data->Row['localId'];

	$data->Next();
}
$data->Disconnect();

foreach($products as $id=>$product) {
	for($j=1;$j<count($connections);$j++) {
		if(!isset($product[0])) {
			$form->AddField(sprintf('link_%d_%d', $id, $j), 'Link Product', 'text', isset($remote[$id]) ? $remote[$id] : '', 'numeric_unsigned', 1, 11, false, 'size="4"');
		}
	}
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		foreach($products as $id=>$product) {
			for($j=1;$j<count($connections);$j++) {
				if(!isset($product[0])) {
					$localId = $form->GetValue(sprintf('link_%d_%d', $id, $j));

					$linked = new LbukLinked();
					$linked->deleteByRemoteId($id);

					if(!empty($localId)) {
						$linked->localId = $localId;
						$linked->remoteId = $id;
						$linked->add();
					}
				}
			}
		}

		redirectTo('?action=view');
	}
}

$page = new Page('Product Synchronisation Report', '');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetErrors();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
?>

<br />
<h3>Products Unsynchronised</h3>
<p>Listing unsynchronised products between sites.</p>

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>ID</strong></td>

		<?php
		for($i=0;$i<count($connections);$i++) {
			?>

			<td style="border-bottom:1px solid #aaaaaa;" align="left" colspan="<?php echo ($i > 0) ? 3 : 2; ?>"><strong><?php print $connections[$i]['Title']; ?></strong></td>
			
			<?php
		}
		?>

	</tr>

	<?php
	foreach($products as $id=>$product) {
		?>

		<tr class="dataRow" <?php echo $highlights[$id] ? 'style="background-color: #ff9;"' : ''; ?>>
			<td align="left"><a href="product_profile.php?pid=<?php print $id; ?>" target="_blank"><?php print $id; ?></a></td>

			<?php
			for($j=0;$j<count($connections);$j++) {
				?>

				<td align="left"><?php echo isset($product[$j]) ? $product[$j]['Product_Title'] : ''; ?></td>
				<td align="left"><?php echo (isset($product[$j]) && ($product[$j]['Discontinued'] == 'Y')) ? 'Discontinued' : ''; ?></td>
				
				<?php
				if($j > 0) {
					?>

					<td align="left">
						<?php
						if(!isset($product[0])) {
							echo $form->GetHTML(sprintf('link_%d_%d', $id, $j));
						}
						?>
					</td>

					<?php
				}
			}
			?>

		</tr>

		<?php
	}
	?>

</table>
<br />

<input type="submit" class="btn" name="link" value="link" />

<?php
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');