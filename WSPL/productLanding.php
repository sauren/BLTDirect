<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountCollection.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductLanding.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxCalculator.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/TaxClass.php");

function getCategories($categoryId) {
	$items = array($categoryId);
	
	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		$items = array_merge($items, getCategories($data->Row['Category_ID']));
		
		$data->Next();	
	}
	$data->Disconnect();
	
	return $items;
}

if(!id_param('landingid')) {
	redirectTo('./');	
}

$landing = new ProductLanding();

if(!$landing->get(id_param('landingid'))) {
	redirectTo('./');
}

$landing->specGroup->Get();
$landing->getProducts();

$discountCollection = new DiscountCollection();
$discountCollection->Get($session->Customer);

$taxClass = new TaxClass();

$specId = id_param('specid', 0);

$scriptFile = './productLanding.php';

if(stristr($_SERVER['PHP_SELF'], $scriptFile) === false) {
	$_SERVER['PHP_SELF'] = $scriptFile;
	$_SERVER['SCRIPT_NAME'] = $scriptFile;
	$_SERVER['QUERY_STRING'] = sprintf('landingid=%d&specid=%d&nm=%s', $landing->id, $specId, urlencode($landing->name));
}

if(!isset($_SESSION['Landing'][$landing->id]['Layout'])) {
	$_SESSION['Landing'][$landing->id]['Layout'] = 'table';
}

if(param('layout')) {
	$_SESSION['Landing'][$landing->id]['Layout'] = strtolower(param('layout'));
}
?>
					<h1 style="text-align: center;"><?php echo $landing->name; ?></h1>
					<br />
					
					<?php echo stripslashes($landing->description); ?>

					<?php
					$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Product_Codes, p.Meta_Title, p.SKU, p.Order_Min, pi.Image_Thumb, MIN(ws.Backorder_Expected_On) AS Backorder_Expected_On FROM product AS p INNER JOIN product_landing_product AS plp ON plp.productId=p.Product_ID AND plp.landingId=%d LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Backordered='Y' LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='Y' AND pi.Is_Primary='Y' WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' GROUP BY p.Product_ID", mysql_real_escape_string($landing->id)));
					if($data->TotalRows > 0) {
						$sqlPrices = sprintf("SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity FROM product AS p INNER JOIN product_landing_product AS plp ON plp.productId=p.Product_ID AND plp.landingId=%d INNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Discontinued='N' AND p.Is_Demo_Product='N' ORDER BY pp.Price_Starts_On ASC", mysql_real_escape_string($landing->id));
						$sqlOffers = sprintf("SELECT p.Product_ID, po.Price_Offer FROM product AS p INNER JOIN product_landing_product AS plp ON plp.productId=p.Product_ID AND plp.landingId=%d INNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Discontinued='N' AND p.Is_Demo_Product='N' ORDER BY po.Offer_Start_On ASC", mysql_real_escape_string($landing->id));

						$productPrices = array();
						$productOffers = array();

						$data2 = new DataQuery($sqlPrices);
						while($data2->Row) {
							if(!isset($productPrices[$data2->Row['Product_ID']])) {
								$productPrices[$data2->Row['Product_ID']] = array();
							}

							$item = array();
							$item['Price_Base_Our'] = $data2->Row['Price_Base_Our'];
							$item['Price_Base_RRP'] = $data2->Row['Price_Base_RRP'];

							$productPrices[$data2->Row['Product_ID']][$data2->Row['Quantity']] = $item;

							$data2->Next();
						}
						$data2->Disconnect();

						$data2 = new DataQuery($sqlOffers);
						while($data2->Row) {
							if(!isset($productOffers[$data2->Row['Product_ID']])) {
								$productOffers[$data2->Row['Product_ID']] = array();
							}

							$item = array();
							$item['Price_Offer'] = $data2->Row['Price_Offer'];

							$productOffers[$data2->Row['Product_ID']][$data2->Row['Price_Offer']] = $item;

							$data2->Next();
						}
						$data2->Disconnect();
						
						$maxLength = 35;
						?>
							
						<div class="grid">
							<h2>Popular <?php echo $landing->name; ?></h2>
		
							<div class="grid-product grid-short">
							
								<?php
								while($data->Row){
									$subProduct = new Product();
									$subProduct->ID = $data->Row['Product_ID'];
									$subProduct->Name = strip_tags($data->Row['Product_Title']);
									$subProduct->HTMLTitle = preg_replace('/<\/p>$/i', '', preg_replace('/^<p[^>]*>/i', '', $data->Row['Product_Title']));
									$subProduct->Codes = $data->Row['Product_Codes'];
									$subProduct->MetaTitle = $data->Row['Meta_Title'];
									$subProduct->SKU = $data->Row['SKU'];
									$subProduct->DefaultImage->Thumb->FileName = $data->Row['Image_Thumb'];
									$subProduct->DefaultImage->Thumb->GetDimensions();
									$subProduct->PriceRRP = 0;
									$subProduct->PriceOurs = 0;
									$subProduct->PriceOffer = 0;
									
									if(isset($productPrices[$subProduct->ID])) {
										if(count($productPrices[$subProduct->ID]) > 0) {
											ksort($productPrices[$subProduct->ID]);
											reset($productPrices[$subProduct->ID]);

											if($subProduct->OrderMin < key($productPrices[$subProduct->ID])) {
												$subProduct->OrderMin = key($productPrices[$subProduct->ID]);
											}

											foreach($productPrices[$subProduct->ID] as $quantity=>$price) {
												$subProduct->PriceOurs = $price['Price_Base_Our'];
												$subProduct->PriceRRP = $price['Price_Base_RRP'];

												break;
											}
										}
									}

									if(isset($productOffers[$subProduct->ID])) {
										if(count($productOffers[$subProduct->ID]) > 0) {
											ksort($productOffers[$subProduct->ID]);
											reset($productOffers[$subProduct->ID]);

											$price = current($productOffers[$subProduct->ID]);

											$subProduct->PriceOffer = $price['Price_Offer'];
										}
									}

									$subProduct->GetPrice();

									include('../lib/templates/productPanel_wspl.php');
									
									$data->Next();
								}
								?>

								<div class="clear"></div>								
							</div>
						</div>

						<?php
					}
					$data->Disconnect();
					
					$categories = array();
					
					if($landing->category->ID > 0) {
						$categories = getCategories($landing->category->ID);
					}
					
					$selectedItem = null;
					
					$cache = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND'], array('lifetime' => 86400 * 7, 'automatic_serialization' => true));
					
					$cacheId = 'product_landing_' . $landing->id;
					$cacheData = array();
					
					if(($cacheData = $cache->load($cacheId)) === false) {
						$cacheData = array();
						
						$data = new DataQuery(sprintf("SELECT psv.Value_ID, psv.Value, IF(psvi2.fileName IS NOT NULL, psvi2.fileName, psvi.fileName) AS fileName, COUNT(DISTINCT p.Product_ID) AS Products FROM product AS p%s INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID AND ps.Value_ID=%d INNER JOIN product_specification AS ps2 ON ps2.Product_ID=p.Product_ID INNER JOIN product_specification_value AS psv on psv.Value_ID=ps2.Value_ID AND psv.Group_ID=%d LEFT JOIN product_specification_value_image AS psvi ON psvi.valueId=psv.Value_ID LEFT JOIN product_specification_value_image AS psvi2 ON psvi2.valueId=psv.Value_ID AND psvi2.reference LIKE '%s' LEFT JOIN product_landing_specification AS pls ON pls.landingId=%d AND pls.valueId=psv.Value_ID WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' GROUP BY psv.Value_ID ORDER BY pls.sequence ASC, psv.Value ASC", ($landing->category->ID > 0) ? sprintf(' INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID IN (%s)', implode(', ', $categories)) : '', mysql_real_escape_string($landing->specValue->ID), mysql_real_escape_string($landing->specGroup->ID), mysql_real_escape_string($landing->imageReference), mysql_real_escape_string($landing->id)));
						while($data->Row) {
							$cacheData[] = $data->Row;
							
							$data->Next();
						}
						$data->Disconnect();
						
						$cache->save($cacheData, $cacheId);
					}
					
					foreach($cacheData as $dataItem) {
						if ($dataItem['Value_ID'] == $specId) {
							$selectedItem = $dataItem;
						}
					}
					
					if((($landing->hideFilter == 'N') && ($specId > 0)) || ($landing->hideFilter == 'Y')) {
						if($landing->hideFilter == 'N') {
							echo '<h2>Filtered Products</h2>';
							
							if($selectedItem) {
								echo sprintf('<p>Now showing products for the %s <strong>%s%s</strong>. If you wish to change %s <a href="/%s">click here</a> to return to the filter page.</p>', strtolower($landing->specGroup->Name), $selectedItem['Value'], !empty($landing->specGroup->Units) ? sprintf(' ' . $landing->specGroup->Units) : '', strtolower($landing->specGroup->Name), str_replace(' ', '-', strtolower($landing->name)));
							}
						} else {
							echo '<h2>Products</h2>';
							echo sprintf('<p>Now showing all <strong>%s</strong>.</p>', $landing->name);
						}

						$sql = sprintf("SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Meta_Title, p.SKU,  p.Product_Codes, p.Order_Min, p.Cache_Specs_Primary, p.Average_Despatch, p.Discontinued_Show_Price, p.CacheBestCost, p.CacheRecentCost, pi.Image_Thumb, MIN(ws.Backorder_Expected_On) AS Backorder_Expected_On FROM product AS p%s INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID AND ps.Value_ID=%d%s LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Backordered='Y' LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='Y' AND pi.Is_Primary='Y' WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' GROUP BY p.Product_ID ", ($landing->category->ID > 0) ? sprintf(' INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID IN (%s)', implode(', ', $categories)) : '', mysql_real_escape_string($landing->specValue->ID), ($specId > 0) ? sprintf(' INNER JOIN product_specification AS ps2 ON ps2.Product_ID=p.Product_ID AND ps2.Value_ID=%d', mysql_real_escape_string($specId)) : '');
						$sqlOrder = sprintf("ORDER BY p.Product_Title ASC");
						$sqlTotalRows = sprintf("SELECT COUNT(DISTINCT p.Product_ID) AS TotalRows FROM product AS p%s INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID AND ps.Value_ID=%d%s WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N'", ($landing->category->ID > 0) ? sprintf(' INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID IN (%s)', implode(', ', $categories)) : '', mysql_real_escape_string($landing->specValue->ID), ($specId > 0) ? sprintf(' INNER JOIN product_specification AS ps2 ON ps2.Product_ID=p.Product_ID AND ps2.Value_ID=%d', mysql_real_escape_string($specId)) : '');
						
						$sqlPrices = sprintf("SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity FROM product AS p%s INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID AND ps.Value_ID=%d%s INNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Discontinued='N' AND p.Is_Demo_Product='N' AND p.Discontinued='N' ORDER BY pp.Price_Starts_On ASC", ($landing->category->ID > 0) ? sprintf(' INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID IN (%s)', implode(', ', $categories)) : '', mysql_real_escape_string($landing->specValue->ID), ($specId > 0) ? sprintf(' INNER JOIN product_specification AS ps2 ON ps2.Product_ID=p.Product_ID AND ps2.Value_ID=%d', mysql_real_escape_string($specId)) : '');
						$sqlOffers = sprintf("SELECT p.Product_ID, po.Price_Offer FROM product AS p%s INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID AND ps.Value_ID=%d%s INNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Discontinued='N' AND p.Discontinued='N' AND p.Is_Demo_Product='N' ORDER BY po.Offer_Start_On ASC", ($landing->category->ID > 0) ? sprintf(' INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID IN (%s)', implode(', ', $categories)) : '', mysql_real_escape_string($landing->specValue->ID), ($specId > 0) ? sprintf(' INNER JOIN product_specification AS ps2 ON ps2.Product_ID=p.Product_ID AND ps2.Value_ID=%d', mysql_real_escape_string($specId)) : '');

						$productPrices = array();
						$productOffers = array();

						$data = new DataQuery($sqlPrices);
						while($data->Row) {
							if(!isset($productPrices[$data->Row['Product_ID']])) {
								$productPrices[$data->Row['Product_ID']] = array();
							}

							$item = array();
							$item['Price_Base_Our'] = $data->Row['Price_Base_Our'];
							$item['Price_Base_RRP'] = $data->Row['Price_Base_RRP'];

							$productPrices[$data->Row['Product_ID']][$data->Row['Quantity']] = $item;

							$data->Next();
						}
						$data->Disconnect();

						$data = new DataQuery($sqlOffers);
						while($data->Row) {
							if(!isset($productOffers[$data->Row['Product_ID']])) {
								$productOffers[$data->Row['Product_ID']] = array();
							}

							$item = array();
							$item['Price_Offer'] = $data->Row['Price_Offer'];

							$productOffers[$data->Row['Product_ID']][$data->Row['Price_Offer']] = $item;

							$data->Next();
						}
						$data->Disconnect();

						$data = new DataQuery($sqlTotalRows);
						if($data->Row['TotalRows'] > 0) {
							?>

							<div class="options">
								<ul>
									<li<?php echo ($_SESSION['Landing'][$landing->id]['Layout'] == 'table') ? ' class="options-primary"' : ''; ?>><a href="/<?php echo str_replace(' ', '-', strtolower($landing->name)); ?>/<?php echo $specId; ?>?layout=table">List View</a></li>
									<li<?php echo ($_SESSION['Landing'][$landing->id]['Layout'] == 'grid') ? ' class="options-primary"' : ''; ?>><a href="/<?php echo str_replace(' ', '-', strtolower($landing->name)); ?>/<?php echo $specId; ?>?layout=grid">Grid View</a></li>
								</ul>
								
								<div class="clear"></div>
							</div>
									
							<?php
						}
						$data->Disconnect();
						
						if($landing->hideFilter == 'N') {
							switch($_SESSION['Landing'][$landing->id]['Layout']) {
								case 'table':
									$table = new DataTable('products');
									$table->SetSQL($sql);
									$table->SetTotalRowSQL($sqlTotalRows);
									$table->SetMaxRows(15);
									$table->SetOrderBy('p.Product_Title');
									$table->Finalise();
									$table->ExecuteSQL();

									if($table->Table->TotalRows > 0) {
										?>

										<table class="list">

											<?php
											while($table->Table->Row) {
												$subProduct = new Product();
												$subProduct->ID = $table->Table->Row['Product_ID'];
												$subProduct->Name = strip_tags($table->Table->Row['Product_Title']);
												$subProduct->HTMLTitle = preg_replace('/<\/p>$/i', '', preg_replace('/^<p[^>]*>/i', '', $table->Table->Row['Product_Title']));
												$subProduct->Codes = $table->Table->Row['Product_Codes'];
												$subProduct->SpecCachePrimary = $table->Table->Row['Cache_Specs_Primary'];
												$subProduct->MetaTitle = $table->Table->Row['Meta_Title'];
												$subProduct->SKU = $table->Table->Row['SKU'];
												$subProduct->DefaultImage->Thumb->FileName = $table->Table->Row['Image_Thumb'];
												$subProduct->OrderMin = $table->Table->Row['Order_Min'];
												$subProduct->AverageDespatch = $table->Table->Row['Average_Despatch'];
												$subProduct->PriceRRP = 0;
												$subProduct->PriceOurs = 0;
												$subProduct->PriceOffer = 0;
												$subProduct->Discontinued = $table->Table->Row['Discontinued'];
												$subProduct->DiscontinuedShowPrice = $table->Table->Row['Discontinued_Show_Price'];
												$subProduct->CacheBestCost = $table->Table->Row['CacheBestCost'];
												$subProduct->CacheRecentCost = $table->Table->Row['CacheRecentCost'];

												if(isset($productPrices[$subProduct->ID])) {
													if(count($productPrices[$subProduct->ID]) > 0) {
														ksort($productPrices[$subProduct->ID]);
														reset($productPrices[$subProduct->ID]);

														if($subProduct->OrderMin < key($productPrices[$subProduct->ID])) {
															$subProduct->OrderMin = key($productPrices[$subProduct->ID]);
														}

														foreach($productPrices[$subProduct->ID] as $quantity=>$price) {
															$subProduct->PriceOurs = $price['Price_Base_Our'];
															$subProduct->PriceRRP = $price['Price_Base_RRP'];

															break;
														}
													}
												}

												if(isset($productOffers[$subProduct->ID])) {
													if(count($productOffers[$subProduct->ID]) > 0) {
														ksort($productOffers[$subProduct->ID]);
														reset($productOffers[$subProduct->ID]);

														$price = current($productOffers[$subProduct->ID]);

														$subProduct->PriceOffer = $price['Price_Offer'];
													}
												}
												
												$subProduct->GetPrice();
												
												include('../lib/templates/productLine_wspl.php');
												
												$table->Next();
											}
											?>

										</table>

										<?php
										$table->DisplayNavigation();
									}
									$table->Disconnect();
	
									break;
									
								case 'grid':
									$data = new DataQuery($sql.$sqlOrder);
									if($data->TotalRows > 0) {
										?>
										
										<div class="grid">
											<div class="grid-product">
											
												<?php
												while($data->Row){
													$subProduct = new Product();
													$subProduct->ID = $data->Row['Product_ID'];
													$subProduct->Name = strip_tags($data->Row['Product_Title']);
													$subProduct->HTMLTitle = preg_replace('/<\/p>$/i', '', preg_replace('/^<p[^>]*>/i', '', $data->Row['Product_Title']));
													$subProduct->Codes = $data->Row['Product_Codes'];
													$subProduct->SpecCachePrimary = $data->Row['Cache_Specs_Primary'];
													$subProduct->MetaTitle = $data->Row['Meta_Title'];
													$subProduct->SKU = $data->Row['SKU'];
													$subProduct->DefaultImage->Thumb->FileName = $data->Row['Image_Thumb'];
													$subProduct->OrderMin = $data->Row['Order_Min'];
													$subProduct->PriceRRP = 0;
													$subProduct->PriceOurs = 0;
													$subProduct->PriceOffer = 0;
													$subProduct->Discontinued = $data->Row['Discontinued'];
													$subProduct->CacheBestCost = $data->Row['CacheBestCost'];

													if(isset($productPrices[$subProduct->ID])) {
														if(count($productPrices[$subProduct->ID]) > 0) {
															ksort($productPrices[$subProduct->ID]);
															reset($productPrices[$subProduct->ID]);

															if($subProduct->OrderMin < key($productPrices[$subProduct->ID])) {
																$subProduct->OrderMin = key($productPrices[$subProduct->ID]);
															}

															foreach($productPrices[$subProduct->ID] as $quantity=>$price) {
																$subProduct->PriceOurs = $price['Price_Base_Our'];
																$subProduct->PriceRRP = $price['Price_Base_RRP'];

																break;
															}
														}
													}

													if(isset($productOffers[$subProduct->ID])) {
														if(count($productOffers[$subProduct->ID]) > 0) {
															ksort($productOffers[$subProduct->ID]);
															reset($productOffers[$subProduct->ID]);

															$price = current($productOffers[$subProduct->ID]);

															$subProduct->PriceOffer = $price['Price_Offer'];
														}
													}

													$subProduct->GetPrice();
													
													$gridClass = 'grid-product-item-long';
													$maxLength = 60;
													
													include('../lib/templates/productPanel_wspl.php');

													$data->Next();
												}
												?>
												
												<div class="clear"></div>
											</div>
										</div>

										<?php
									}
									$data->Disconnect();
								
									break;
							}
						} else {
							switch($_SESSION['Landing'][$landing->id]['Layout']) {
								case 'table':
									$table = new DataTable('products');
									$table->SetSQL($sql);
									$table->SetTotalRowSQL($sqlTotalRows);
									$table->SetMaxRows(15);
									$table->SetOrderBy('p.Product_Title');
									$table->Finalise();
									$table->ExecuteSQL();

									if($table->Table->TotalRows > 0) {
										?>

										<table class="list">

											<?php
											while($table->Table->Row) {
												$subProduct = new Product();
												$subProduct->ID = $table->Table->Row['Product_ID'];
												$subProduct->Name = strip_tags($table->Table->Row['Product_Title']);
												$subProduct->HTMLTitle = preg_replace('/<\/p>$/i', '', preg_replace('/^<p[^>]*>/i', '', $table->Table->Row['Product_Title']));
												$subProduct->Codes = $table->Table->Row['Product_Codes'];
												$subProduct->SpecCachePrimary = $table->Table->Row['Cache_Specs_Primary'];
												$subProduct->MetaTitle = $table->Table->Row['Meta_Title'];
												$subProduct->SKU = $table->Table->Row['SKU'];
												$subProduct->DefaultImage->Thumb->FileName = $table->Table->Row['Image_Thumb'];
												$subProduct->OrderMin = $table->Table->Row['Order_Min'];
												$subProduct->AverageDespatch = $table->Table->Row['Average_Despatch'];
												$subProduct->PriceRRP = 0;
												$subProduct->PriceOurs = 0;
												$subProduct->PriceOffer = 0;
												$subProduct->Discontinued = $table->Table->Row['Discontinued'];
												$subProduct->DiscontinuedShowPrice = $table->Table->Row['Discontinued_Show_Price'];
												$subProduct->CacheBestCost = $table->Table->Row['CacheBestCost'];
												$subProduct->CacheRecentCost = $table->Table->Row['CacheRecentCost'];

												if(isset($productPrices[$subProduct->ID])) {
													if(count($productPrices[$subProduct->ID]) > 0) {
														ksort($productPrices[$subProduct->ID]);
														reset($productPrices[$subProduct->ID]);

														if($subProduct->OrderMin < key($productPrices[$subProduct->ID])) {
															$subProduct->OrderMin = key($productPrices[$subProduct->ID]);
														}

														foreach($productPrices[$subProduct->ID] as $quantity=>$price) {
															$subProduct->PriceOurs = $price['Price_Base_Our'];
															$subProduct->PriceRRP = $price['Price_Base_RRP'];

															break;
														}
													}
												}

												if(isset($productOffers[$subProduct->ID])) {
													if(count($productOffers[$subProduct->ID]) > 0) {
														ksort($productOffers[$subProduct->ID]);
														reset($productOffers[$subProduct->ID]);

														$price = current($productOffers[$subProduct->ID]);

														$subProduct->PriceOffer = $price['Price_Offer'];
													}
												}
												
												$subProduct->GetPrice();
												
												include('../lib/templates/productLine_wspl.php');
												
												$table->Next();
											}
											?>

										</table>

										<?php
										$table->DisplayNavigation();
									}
									$table->Disconnect();
	
									break;
									
								case 'grid':
									$data = new DataQuery($sql);
									if($data->TotalRows > 0) {
										?>

										<div class="grid">
											<div class="grid-product">

												<?php
												while($data->Row){
													$subProduct = new Product();
													$subProduct->ID = $data->Row['Product_ID'];
													$subProduct->Name = strip_tags($data->Row['Product_Title']);
													$subProduct->HTMLTitle = preg_replace('/<\/p>$/i', '', preg_replace('/^<p[^>]*>/i', '', $data->Row['Product_Title']));
													$subProduct->Codes = $data->Row['Product_Codes'];
													$subProduct->SpecCachePrimary = $data->Row['Cache_Specs_Primary'];
													$subProduct->MetaTitle = $data->Row['Meta_Title'];
													$subProduct->SKU = $data->Row['SKU'];
													$subProduct->DefaultImage->Thumb->FileName = $data->Row['Image_Thumb'];
													$subProduct->OrderMin = $data->Row['Order_Min'];
													$subProduct->PriceRRP = 0;
													$subProduct->PriceOurs = 0;
													$subProduct->PriceOffer = 0;
													$subProduct->Discontinued = $data->Row['Discontinued'];
													$subProduct->CacheBestCost = $data->Row['CacheBestCost'];

													if(isset($productPrices[$subProduct->ID])) {
														if(count($productPrices[$subProduct->ID]) > 0) {
															ksort($productPrices[$subProduct->ID]);
															reset($productPrices[$subProduct->ID]);

															if($subProduct->OrderMin < key($productPrices[$subProduct->ID])) {
																$subProduct->OrderMin = key($productPrices[$subProduct->ID]);
															}

															foreach($productPrices[$subProduct->ID] as $quantity=>$price) {
																$subProduct->PriceOurs = $price['Price_Base_Our'];
																$subProduct->PriceRRP = $price['Price_Base_RRP'];

																break;
															}
														}
													}

													if(isset($productOffers[$subProduct->ID])) {
														if(count($productOffers[$subProduct->ID]) > 0) {
															ksort($productOffers[$subProduct->ID]);
															reset($productOffers[$subProduct->ID]);

															$price = current($productOffers[$subProduct->ID]);

															$subProduct->PriceOffer = $price['Price_Offer'];
														}
													}

													$subProduct->GetPrice();
													
													$gridClass = 'grid-product-item-long';
													$maxLength = 60;
													
													include('../lib/templates/productPanel_wspl.php');

													$data->Next();
												}
												?>

												<div class="clear"></div>
											</div>
										</div>

										<?php
									}
									$data->Disconnect();

									break;
							}
						}
					}
					
					if($landing->hideFilter == 'N') {
						?>

						<div class="categoryGrid">
						
							<?php
							foreach($cacheData as $dataItem) {
								?>

								<div class="categoryGridBox">

									<?php
									$image = (!empty($dataItem['fileName']) && file_exists($GLOBALS['SPEC_IMAGES_DIR_FS'].$dataItem['fileName'])) ? sprintf('<br /><img src="%s%s" alt="Filter Option" />', $GLOBALS['SPEC_IMAGES_DIR_WS'], $dataItem['fileName']) : '';
									$link = sprintf('/%s/%d', str_replace(' ', '-', strtolower($landing->name)), $dataItem['Value_ID']);
									
									echo sprintf('<a href="%3$s">%2$s</a><br /><a href="%3$s"><strong>%1$s%6$s (%5$s)</strong></a>', $dataItem['Value'], $image, $link, ($specId == $dataItem['Value_ID']) ? ' class="selected"' : '', $dataItem['Products'], !empty($landing->specGroup->Units) ? sprintf(' ' . $landing->specGroup->Units) : '');
									?>

								</div>

								<?php
							}
							?>
					
							<div class="clear"></div>
						
						<?php
					}
					?>
					<?php require_once('../lib/common/appFooter.php');