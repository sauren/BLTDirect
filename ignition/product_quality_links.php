<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductQuality.php');

$quality = array('Value' => array(), 'Premium' => array());

$data = new DataQuery(sprintf("SELECT Product_ID, Product_Title, Quality FROM product WHERE Quality<>''"));
while($data->Row) {
	$quality[$data->Row['Quality']][] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('productid', 'Product', 'hidden', '0', 'numeric_unsigned', 1, 11);

$options = array();

if($form->GetValue('productid') > 0) {
	$data = new DataQuery(sprintf("SELECT id, productId FROM product_quality WHERE parentId=%d", mysql_real_escape_string($form->GetValue('productid'))));
	while($data->Row) {
		$options[$data->Row['productId']] = $data->Row['id'];

		$data->Next();
	}
	$data->Disconnect();

	$key = 'Premium';

	foreach($quality[$key] as $qualityItem) {
		$form->AddField('option_' . $qualityItem['Product_ID'], 'Product', 'checkbox', isset($options[$qualityItem['Product_ID']]) ? 'Y' : 'N', 'boolean', 1, 1, false);
	}
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$key = 'Premium';

		foreach($quality[$key] as $qualityItem) {
			if($form->GetValue('option_' . $qualityItem['Product_ID']) == 'Y') {
				if(!isset($options[$qualityItem['Product_ID']])) {
					$quality = new ProductQuality();
					$quality->parent->ID = $form->GetValue('productid');
					$quality->product->ID = $qualityItem['Product_ID'];
					$quality->add();
				}
			} else {
				if(isset($options[$qualityItem['Product_ID']])) {
					$quality = new ProductQuality();
					$quality->delete($options[$qualityItem['Product_ID']]);
				}
			}
		}
		
		redirectTo('?productid=' . $form->GetValue('productid'));
	}
}

$page = new Page('Product Quality Links', 'Link products based on quality.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('productid');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td valign="top" width="49%">

			<?php
			$key = 'Value';
			?>

			<h3><?php echo $key; ?></h3>
			<br />

			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
				<thead>
					<tr>
						<th>ID</th>
						<th>Product</th>
						<th width="1%">&nbsp;</th>
					</tr>
				</thead>
				<tbody>

					<?php
					foreach($quality[$key] as $qualityItem) {
						?>
						
						<tr>
							<td <?php echo ($qualityItem['Product_ID'] == $form->GetValue('productid')) ? 'style="background-color: #fff;"' : ''; ?>><?php echo $qualityItem['Product_ID']; ?></td>
							<td <?php echo ($qualityItem['Product_ID'] == $form->GetValue('productid')) ? 'style="background-color: #fff;"' : ''; ?>><?php echo $qualityItem['Product_Title']; ?></td>
							<td <?php echo ($qualityItem['Product_ID'] == $form->GetValue('productid')) ? 'style="background-color: #fff;"' : ''; ?>>
								
								<?php
								if($qualityItem['Product_ID'] <> $form->GetValue('productid')) {
									?>

									<a href="?productid=<?php echo $qualityItem['Product_ID']; ?>"><img src="images/button-tick.gif" border="0" /></a>

									<?php
								}
								?>

							</td>
						</tr>
						
						<?php	
					}
					?>
					
				</tbody>
			</table>


		</td>
		<td valign="top" width="2%">
		<td valign="top" width="49%">

			<?php
			$key = 'Premium';

			if($form->GetValue('productid') > 0) {
				?>

				<h3><?php echo $key; ?></h3>
				<br />

				<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
					<thead>
						<tr>
							<th>ID</th>
							<th>Product</th>
							<th width="1%">&nbsp;</th>
						</tr>
					</thead>
					<tbody>

						<?php
						foreach($quality[$key] as $qualityItem) {
							?>
							
							<tr>
								<td><?php echo $qualityItem['Product_ID']; ?></td>
								<td><?php echo $qualityItem['Product_Title']; ?></td>
								<td><?php echo $form->GetHTML('option_' . $qualityItem['Product_ID']); ?></td>
							</tr>
							
							<?php	
						}
						?>
						
					</tbody>
				</table>

				<?php
			}
			?>

		</td>
	</tr>
</table>
<br />

<input type="submit" class="btn" name="update" value="update" />

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');