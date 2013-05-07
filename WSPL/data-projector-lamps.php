<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Category.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');
?>
<?php
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Projector Lamps</span></div>
<div class="maincontent">
<div class="maincontent1">
<!--					<p class="breadCrumb"><a href="/index.php">Home</a> / <a href="/products.php">Products</a></p>-->

					<div style="float: right; padding: 0 0 10px 10px;">
						<img src="/images/diamonde.jpg" width="224" height="90" />
					</div>

					<p>We only sell Projector Lamps that perform to the projector manufacturers' original specification. We do not sell copy Projector Lamps, branded or otherwise, that lowers the projectors' performance level and risks your health and safety.</p>
					<p>Where available, we offer these Projector Lamps:</p>
					<p><strong>Original Manufacturers Projector Lamps</strong><br />As supplied by the original projector manufacturer for the optimum performance.</p>
					<p><strong>Diamond Projector Lamps - Philips UHP / Osram VIP &amp; P-VIP Projector Lamps</strong><br />Authorised by Philips and Osram, the original bulb manufacturers for the above projector lamps, this is a lower cost alternative to the original manufacturers projector lamp. Complete with a new chassis, this lamp gives identical performance to the Original Manufacturers Lamp with an unprecedented 4 month warranty, Although endorsed by a number of projector manufacturers, the lamp may invalidate some projector warranties; please check if the warranty on the projector is still current or affected before purchase.</p>

					<div style="clear: both;"></div>

					<table class="bluebox" cellspacing="0" border="0" height="140">
						<tr>
							<td valign="top" width="200" rowspan="2" class="center"><img src="images/projector_lamps_1.gif" alt="Projector Lamps" width="148" height="120" class="img" /></td>
							<td valign="top" colspan="2">
								<strong style="font-size: 12px;">Projector Lamps Selector</strong><br />Please select the manufacturer and model that you require from the drop-down menu.
							</td>
						</tr>
						<tr>
							<td valign="top">

								<table height="100%" cellspacing="0" border="0">
									<tr>
										<th style="text-align: left;">Manufacturer:</th>
										<td>
											<select name="manufactuerer" onchange="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?manufacturer=' + this.value;">
												<option value="">-- Select --</option>

												<?php
												$data = new DataQuery(sprintf("SELECT m.Manufacturer_ID, m.Manufacturer_Name FROM manufacturer AS m INNER JOIN product AS p ON p.Manufacturer_ID=m.Manufacturer_ID WHERE p.Is_Active='Y' AND p.Discontinued='N' AND p.Integration_ID>0 GROUP BY m.Manufacturer_ID ORDER BY m.Manufacturer_Name ASC"));
												while($data->Row) {
													echo sprintf('<option value="%s"%s>%s</option>', $data->Row['Manufacturer_ID'], (($data->Row['Manufacturer_ID'] == id_param('manufacturer')) ? ' selected="selected"' : ''), $data->Row['Manufacturer_Name']);

													$data->Next();
												}
												$data->Disconnect();
												?>
											</select>
									  </td>
									</tr>

									<?php
									if(id_param('manufacturer')) {
										?>

										<tr>
											<th style="text-align: left;">Model:</th>
											<td>
												<select name="model" onchange="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?manufacturer=<?php echo id_param('manufacturer'); ?>&amp;model=' + this.value;">
													<option value="">-- Select --</option>

													<?php
													$data = new DataQuery(sprintf("SELECT p.Model FROM product AS p WHERE p.Is_Active='Y' AND p.Discontinued='N' AND p.Integration_ID>0 AND p.Manufacturer_ID=%d GROUP BY p.Model ORDER BY p.Model ASC", id_param('manufacturer')));
													while($data->Row) {
														echo sprintf('<option value="%s"%s>%s</option>', $data->Row['Model'], (($data->Row['Model'] == param('model')) ? ' selected="selected"' : ''), $data->Row['Model']);

														$data->Next();
													}
													$data->Disconnect();
													?>

												</select>
										  </td>
										</tr>

										<?php
									} else {
										?>

										<tr>
											<td colspan="2">&nbsp;</td>
										</tr>

										<?php
									}

									if(param('model')) {
										?>

										<tr>
											<th style="text-align: left;">Lamp:</th>
											<td>
												<select name="lamp" onchange="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?manufacturer=<?php echo id_param('manufacturer'); ?>&amp;model=<?php echo urlencode(param('model')); ?>&amp;product=' + this.value;">
													<option value="">-- Select --</option>

													<?php
													$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p WHERE p.Is_Active='Y' AND p.Discontinued='N' AND p.Integration_ID>0 AND p.Manufacturer_ID=%d AND p.Model LIKE '%s' ORDER BY p.Product_Title ASC", id_param('manufacturer'), mysql_real_escape_string(param('model'))));
													while($data->Row) {
														echo sprintf('<option value="%s"%s>%s</option>', $data->Row['Product_ID'], (($data->Row['Product_ID'] == id_param('product')) ? ' selected="selected"' : ''), strip_tags($data->Row['Product_Title']));

														$data->Next();
													}
													$data->Disconnect();
													?>

												</select>
										  </td>
										</tr>

										<?php
									} else {
										?>

										<tr>
											<td colspan="2">&nbsp;</td>
										</tr>

										<?php
									}
									?>

								</table>

							</td>
						</tr>
					</table>
					<br />

					<?php
					if(id_param('product')) {
						$product = new Product(id_param('product'));
						?>

						<table class="whitebox" cellspacing="0" border="0">
							<tr>
								<td valign="top" width="200" rowspan="2" class="center"><img id="projectorImage" src="images/projector_lamps_1.gif" alt="Projector Lamps" width="148" height="120" class="img" /></td>
								<td valign="top" colspan="2">
									<strong style="font-size: 12px;"><?php echo $product->Name; ?></strong>
								</td>
							</tr>
							<tr>
								<td valign="top">

									<?php
			            			?>

			                		<p>
			                			Our Price <span class="currentPrice">&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?></span><br />
			                			<span class="smallGreyText">(&pound;<?php echo number_format($product->PriceCurrentIncTax, 2, '.', ','); ?> inc. VAT)</span><br /><br />

			                			<?php
			                			$data = new DataQuery(sprintf("SELECT SUM(Quantity_In_Stock) AS Quantity FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", mysql_real_escape_string($GLOBALS['JL_WAREHOUSE']), $product->ID));
			                			if(($data->TotalRows > 0) && ($data->Row['Quantity'] > 0)) {
						                	echo sprintf('<span style="color: #c00;">Stock Available:</span> %s', $data->Row['Quantity']);
			                			} else {
			                				echo sprintf('<span style="color: #c00;">Not in Stock</span><br />Available within %s day(s)', $product->DespatchDaysMax - 2);
			                			}
			                			$data->Disconnect();
						                ?>

			            			</p>

						            <?php echo $product->GetBuyIt(); ?>
			                    	<br />

			                    	<p><span class="callTag">Buy Online or Call: <span class="phone"><?php echo Setting::GetValue('telephone_sales_hotline'); ?></span></span></p>
			                    	<p><span class="smallGreyText">SKU #: <?php echo $product->SKU; ?></span></p>

								</td>
							</tr>
						</table>
						<br />

						<?php
					}
					?>

					<p>We stock many projector lamps, if the projector lamps you require are not availble please call our sales helpline on <?php echo Setting::GetValue('telephone_sales_hotline'); ?> for a quotation. </p>

					<br /><br /><br /><br />

					<table class="whitebox" cellspacing="0" border="0">
		              	<tr>
		              		<td>

								<p><strong>For other projector lamps, ANSI Coded Projector Lamps, A1 Projector Lamps range, photographic and studio lamps, please see our Specialist Lamps section below.</strong></p>

								<?php
								$category = new Category(92);

								if(!empty($category->CategoryOrder)){
									$sql = sprintf("select * from product_categories where Category_Parent_ID=%d and Is_Active='Y' order by %s", $category->ID, mysql_real_escape_string($category->CategoryOrder));
								} else {
									$sql = sprintf("select * from product_categories where Category_Parent_ID=%d and Is_Active='Y' ORDER BY Category_Title", $category->ID);
								}

								$children = new DataQuery($sql);

								$subCategory = new Category();

								if($category->ShowImages == 'Y') {
									$productColumns = 3;
									if($children->TotalRows > 0){
										echo "<table class=\"productCategories clear\">";
										$tempColumn = 0;
										$rows = 0;
										while($children->Row){
											$subCategory->ID = $children->Row['Category_ID'];
											$subCategory->Name = $children->Row['Category_Title'];
											$subCategory->MetaTitle = $children->Row['Meta_Title'];

											$url = $subCategory->GetUrl();

											++$tempColumn;
											++$rows;
											if($tempColumn == 1) echo "<tr>";

											$tempStr = "<td>%s<br /><p>%s</p></td>";
											// Check Image
											if(!empty($children->Row['Category_Thumb']) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].'images/categories/'.$children->Row['Category_Thumb'])) {
												$image =  sprintf("<a href=\"%s\"><img src=\"/images/categories/%s\" alt=\"%s\"/></a>", $url, $children->Row['Category_Thumb'], $children->Row['Meta_Title']);
											} else {
												$image =  sprintf("<a href=\"%s\"><img src=\"/images/template/image_coming_soon_2.jpg\" alt=\"%s\"/></a>", $url, $children->Row['Meta_Title']);
											}
											$link = sprintf('<a href="%s" title="%s">%s</a>', $url, $children->Row['Meta_Title'],  $children->Row['Category_Title']);
											echo sprintf($tempStr, $image, $link);
											//Check Column
											if(($tempColumn == $productColumns) || ($rows == $children->TotalRows)){
												echo "</tr>";
												$tempColumn = 0;
											}
											$children->Next();
										}
										echo "</table>";
									}
								} else {
									$productColumns = 2;
									if($children->TotalRows > 0){
										$childrenArr = array();

										while($children->Row){
											$subCategory->ID = $children->Row['Category_ID'];
											$subCategory->Name = $children->Row['Category_Title'];
											$subCategory->MetaTitle = $children->Row['Meta_Title'];

											$url = $subCategory->GetUrl();

											$childrenArr[] = sprintf('<a href="%s" title="%s">%s</a>', $url, $children->Row['Meta_Title'],  $children->Row['Category_Title']);
											$children->Next();
										}

										$tempColumn = 0;
										$rows = 0;
										$columnArr = array();
										$col = 0;
										$count = 0;

										for($i=0;$i < count($childrenArr); $i++) {
											if($count >= (count($childrenArr) / $productColumns)) {
												$col++;
												$count = 0;
											}

											$columnArr[$col][] = $childrenArr[$i];
											$count++;
										}

										echo "<table class=\"productCategories clear\">";

										for($i=0;$i < count($columnArr[0]); $i++) {

											echo "<tr>";

											for($j=0;$j < $productColumns; $j++) {
												if(isset($columnArr[$j][$i])) {
													$link = $columnArr[$j][$i];
												} else {
													$link = '&nbsp;';
												}

												echo sprintf("<td style=\"text-align: left;\">%s</td>", $link);
											}

											echo "</tr>";
										}

										echo "</table>";
									}
								}

								$children->Disconnect();
								?>

							</td>
						</tr>
					</table>
                    </div>
                    </div>                   
<?php include("ui/footer.php");?>
<?php include('../lib/common/appFooter.php'); ?>