<?php
	require_once('lib/common/app_header.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BreadCrumb.php');

	$session->Secure(2);

	$category = new Category;
	$product = new Product;

	if(isset($_REQUEST['cat']) && !empty($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])){
		$category->Get($_REQUEST['cat']);
		$breadCrumb = new BreadCrumb();
		$breadCrumb->LinkCode = "./order_products.php?cat=%s";
		$breadCrumb->Get($category->ID);
	}
	if(isset($_REQUEST['pid']) && !empty($_REQUEST['pid']) && is_numeric($_REQUEST['pid'])){
		$product->Get($_REQUEST['pid']);
	}

	$page = new Page('Create a New Order Manually', '');
	$page->Display('header');
?>
<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top"><p><strong><?php echo $product->Name; ?></strong><br /><?php if(isset($breadCrumb)) echo $breadCrumb->Text; ?></p>

			<table border="0" width="100%" cellspacing="0">
				<tr>
					<td width="45%" valign="top"><p align="center">
					<?php
					if(!empty($product->DefaultImage->Large->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Large->FileName)){
						echo sprintf('<img src="%s%s" border="0" />', $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $product->DefaultImage->Large->FileName);
					}
					?>
					</p>

				<?php
						$data = new DataQuery(sprintf("SELECT psg.Name, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID INNER JOIN product_specification_group AS psg ON psv.Group_ID=psg.Group_ID WHERE ps.Product_ID=%d AND psg.Is_Visible='Y' ORDER BY psg.Name ASC", mysql_real_escape_string($product->ID)));
						if($data->TotalRows > 0){
							?>

				    		<table cellspacing="0" class="catProducts">
								<tr>
									<th colspan="2">Technical Specification</th>
								</tr>

								<?php
								while($data->Row){
									?>

									<tr>
										<td><?php echo $data->Row['Name']; ?></td>
										<td><?php echo $data->Row['UnitValue']; ?></td>
									</tr>

									<?php
									$data->Next();
								}
								?>

							</table>

							<?php
						}
						$data->Disconnect();
						?>

						</td>
				  <td width="15" valign="top">&nbsp;</td>
			      <td valign="top"><p>
				  		<?php
							if(!is_null($product->PriceRRP) && $product->PriceRRP > 0){
						?>
						RRP Price<span class="oldPrice"> &pound;<?php echo number_format($product->PriceRRP, 2, '.', ','); ?></span><br />
						<?php
						}
						if($product->PriceCurrent < $product->PriceOurs){
						?>
							Our Price <span class="oldPrice">&pound;<?php echo number_format($product->PriceOurs, 2, '.', ','); ?></span><br />
							Offer Price <span class="currentPrice">&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?></span><br />
						<?php
						} else {
						?>
							Our Price <span class="currentPrice">&pound;<?php echo number_format($product->PriceOurs, 2, '.', ','); ?></span><br /><span class="smallGreyText">(&pound;<?php echo number_format($product->PriceCurrentIncTax, 2, '.', ','); ?> inc.  VAT)</span><br />
						<?php
						}
						echo $product->GetDespatchInfo();
						?>

						</p>


						<p>
						<?php echo $product->GetBuyIt($category->ID, false, 'order_customise.php'); ?>
						<br />
					  </p>
					  <p>
					  <span class="smallGreyText">
					  QuickFind #: <?php echo $product->ID; ?><br />
					  Part Number: <?php echo $product->SKU; ?>
</span></p>
					  <p><strong>Product Information</strong><br />
					  <?php echo nl2br($product->Description); ?></p>
					  <table cellspacing="0" border="0">
                        <tr>
                          <td width="100"><span class="smallGreyText">Manufacturer:</span></td>
                          <td>
                            <?php
						if(!empty($product->Manufacturer->URL)){
							echo sprintf("<a href=\"%s\" target=\"_blank\">%s</a>",
											$product->Manufacturer->URL,
											$product->Manufacturer->Name);
						} else {
							echo $product->Manufacturer->Name;
						}
					?>
                          </td>
                        </tr>
                        <tr>
                          <td><span class="smallGreyText">Model:</span></td>
                          <td><?php echo $product->Model; ?></td>
                        </tr>
                      </table>
					  <br />
                 </td>
				</tr>
			</table>
			  <?php

				// Get Components
				$components = new DataQuery("select * from product_components where Is_Active='Y' and Component_Of_Product_ID=" . mysql_real_escape_string($product->ID));
				if($components->TotalRows > 0){
			?>
			  <br />

			<strong>Components</strong>
			<p>This product includes the following components. You can view more information about individual components by clicking on the component name.</p>
			<table cellspacing="0" class="catProducts">
				<tr>
					<th>Quantity</th>
					<th>Component Name</th>
					<th>Manufacturer</th>
				</tr>
			<?php
				while($components->Row){
					$component = new Product($components->Row['Product_ID']);
			?>
				<tr>
					<td><?php echo $components->Row['Component_Quantity']; ?>x</td>
					<td><a href="order_product.php?pid=<?php echo $component->ID; ?>"><strong><?php echo $component->Name; ?></strong><br /><?php echo $component->Blurb; ?></a></td>
					<td><?php echo $component->Manufacturer->Name; ?></td>
				</tr>
			<?php
					$components->Next();
				}
			?>
			</table>
			<?php
				}

				// Check for Products in this Category
				$related = new DataQuery(sprintf("select * from product_related where Related_To_Product_ID=%d and Is_Active='Y'",
								mysql_real_escape_string($product->ID)));
				if($related->TotalRows > 0){
			?>
			<br />
			<strong>Related Products</strong>
			<table cellspacing="0" class="catProducts">
				<tr>
					<th colspan="2">Products Related to <?php echo $product->Name; ?></th>
					<th colspan="1">Manufacturer</th>
					<th colspan="2">Price</th>
				</tr>
			<?php

					while($related->Row){
						$prod = new Product($related->Row['Product_ID']);
			?>
				<tr>
					<td>
					<?php
					if(!empty($prod->DefaultImage->Thumb->FileName) && file_exists('images/products/'.$prod->DefaultImage->Thumb->FileName)) {
						?>

						<a href="./order_product.php?pid=<?php echo $prod->ID; ?>&cat=<?php echo $category->ID; ?>"><img src="./images/products/<?php echo $prod->DefaultImage->Thumb->FileName; ?>" border="0" width="<?php echo $prod->DefaultImage->Thumb->Width; ?>" height="<?php echo $prod->DefaultImage->Thumb->Height; ?>" alt="Click to View Product Information" /></a></td>

						<?php
					}
					?>
					<td>
						<a href="./order_product.php?pid=<?php echo $prod->ID; ?>&cat=<?php echo $category->ID; ?>" title="Click for More Information"><strong><?php echo $prod->Name; ?></strong><br />
				  <span class="smallGreyText">QuickFind #: <?php echo $prod->ID; ?>, Part Number: <?php echo $prod->SKU; ?></span></a>				  </td>
					<td><?php echo $prod->Manufacturer->Name; ?></td>
					<td align="right" class="price">
						&pound;<?php echo number_format($prod->PriceCurrent, 2, '.', ','); ?><br />
						<span class="smallGreyText">+ VAT</span>
					</td>
					<td nowrap="nowrap" align="right"><?php echo $prod->GetBuyIt($category->ID, true); ?></td>
				</tr>
			<?php
						$related->Next();
					}
			?>
			</table>
			<?php
				}
				$related->Disconnect();
			?>

    </td>
  </tr>
</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>