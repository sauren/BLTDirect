<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/LedType.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");
$type = new LedType();

if(id_param('id') && !$type->get(id_param('id'))) {
	redirectTo($_SERVER['PHP_SELF']);
}

$groupsType = array();
$groupsEquivalentWattage = array();
$groupsWattage = array();
$groupsLampLife = array();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE 'type'"));
while($data->Row) {
	$groupsType[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%%equivalent%%' AND Reference LIKE '%%wattage%%'"));
while($data->Row) {
	$groupsEquivalentWattage[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE 'wattage'"));
while($data->Row) {
	$groupsWattage[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%%lamp%%' AND Reference LIKE '%%life%%'"));
while($data->Row) {
	$groupsLampLife[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();

$specs = array('LED');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>LED Examples</title>
	<!-- InstanceEndEditable -->

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs_print.css" media="print" />
	<link rel="stylesheet" type="text/css" href="css/Navigation.css" />
	<link rel="stylesheet" type="text/css" href="css/Menu.css" />
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		?>
		<link rel="stylesheet" type="text/css" href="css/Trade.css" />
        <?php
	}
	?>
	<link rel="shortcut icon" href="favicon.ico" />
<!--    <script type='text/javascript' src='http://api.handsetdetection.com/sites/js/43071.js'></script>-->
	<script language="javascript" type="text/javascript" src="js/generic.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance_api.js"></script>
	<script language="javascript" type="text/javascript" src="js/mootools.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance.js"></script>
	<script language="javascript" type="text/javascript" src="js/bltdirect.js"></script>
    <script language="javascript" type='text/javascript' src="js/api.js"></script>
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script language="javascript" type="text/javascript" src="js/bltdirect/template.js"></script>
        <?php
	}
	?>
    
	<script language="javascript" type="text/javascript">
	//<![CDATA[
		<?php
		for($i=0; $i<count($GLOBALS['Cache']['Categories']); $i=$i+2) {
			echo sprintf("menu1.add('navProducts%d', 'navProducts', '%s', '%s', null, 'subMenu');", $i, $GLOBALS['Cache']['Categories'][$i], $GLOBALS['Cache']['Categories'][$i+1]);
		}
		?>
	//]]>
	</script>	
	<!-- InstanceBeginEditable name="head" -->
	<meta name="Keywords" content="light bulbs, light bulb, lightbulbs, lightbulb, lamps, fluorescent, tubes, osram, energy saving, sylvania, philips, ge, halogen, low energy, metal halide, candle, dichroic, gu10, projector, blt direct" />
	<meta name="Description" content="We specialise in supplying lamps, light bulbs and fluorescent tubes, Our stocks include Osram,GE, Sylvania, Omicron, Pro lite, Crompton, Ushio and Philips light bulbs, " />
	<!-- InstanceEndEditable -->
</head>
<body>

    <div id="Wrapper">
        <div id="Header">
            <div id="HeaderInner">
                <?php require('lib/templates/header.php'); ?>
            </div>
        </div>
        <div id="PageWrapper">
            <div id="Page">
                <div id="PageContent">
                    <?php
                    if(strtolower(Setting::GetValue('site_message_active')) == 'true') {
                        ?>

                        <div id="SiteMessage">
                            <div id="SiteMessageLeft">
                                <div id="SiteMessageRight">
                                    <marquee scrollamount="4"><?php echo Setting::GetValue('site_message_value'); ?></marquee>
                                </div>
                            </div>
                        </div>

                        <?php
                    }
                    ?>
                    
                    <a name="top"></a>
                    
                    <!-- InstanceBeginEditable name="pageContent" -->

			        <h1>LED Examples</h1>
			        <p>Use our LED examples to see how much you could save by converting to LED energy saving light bulbs.</p>
			        
			        <?php
			        if(is_numeric($type->id) && $type->id > 0) {
			        	?>
			        	
			        	<p>Calculations are based upon an assumptions of 12 pence per kWh.</p>
			        	
			        	<?php
			        	$products = array();
			        	
			        	$data = new DataQuery(sprintf("SELECT lp.productId FROM led_location AS ll INNER JOIN led_product AS lp ON lp.locationId=ll.id WHERE ll.typeId=%d", mysql_real_escape_string($type->id)));
					    while($data->Row) {
					    	$products[$data->Row['productId']] = $data->Row['productId'];

					    	$data->Next();
						}
						$data->Disconnect();
						
						$dataSpecs = array();
						
						foreach($specs as $spec) {
							$dataSpecs[] = sprintf('\'%s\'', $spec);	
						}
						
						$alternatives = array();

						$data = new DataQuery(sprintf("SELECT pr.Related_To_Product_ID, pr.Product_ID, p.Product_Title, psv.Value FROM product_related AS pr INNER JOIN product AS p ON p.Product_ID=pr.Product_ID INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID INNER JOIN product_specification_value AS psv On psv.Value_ID=ps.Value_ID AND psv.Group_ID=222 WHERE pr.Related_To_Product_ID IN (%s) AND pr.Is_Active='Y' AND p.Is_Active='Y' AND pr.Type LIKE 'Energy Saving Alternative' AND psv.Value IN (%s) GROUP BY pr.Related_To_Product_ID, pr.Product_ID", implode(', ', $products), implode(', ', $dataSpecs)));
						while($data->Row) {
							if(!isset($alternatives[$data->Row['Related_To_Product_ID']])) {
								$alternatives[$data->Row['Related_To_Product_ID']] = array();
							}

							$alternatives[$data->Row['Related_To_Product_ID']][] = $data->Row;
							
							$products[$data->Row['Product_ID']] = $data->Row['Product_ID'];
						
							$data->Next();		
						}
						$data->Disconnect();
						
						$sqlPrices = sprintf("SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity FROM product AS p INNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() WHERE p.Product_ID IN (%s) AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' ORDER BY pp.Price_Starts_On ASC", implode(', ', $products));
						$sqlOffers = sprintf("SELECT p.Product_ID, po.Price_Offer FROM product AS p INNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) WHERE p.Product_ID IN (%s) AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' ORDER BY po.Offer_Start_On ASC", implode(', ', $products));

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
						
						foreach($productPrices as $productId=>$prices) {
							ksort($productPrices[$productId]);
							reset($productPrices[$productId]);
						}

						foreach($productOffers as $productId=>$prices) {
							ksort($productOffers[$productId]);
							reset($productOffers[$productId]);
						}
						
						$totalPrices = array();
						$totalSavings = array();
						
						foreach($specs as $spec) {
			        		$totalPrices[$spec] = 0;
			        		$totalSavings[$spec] = 0;
						}
					
						$totalPrice = 0;
						?>
						
						<table cellspacing="0" class="catProducts">
							<tr>
								<th colspan="3">&nbsp;</th>
								<th style="text-align: right;">Price<br /><span style="font-weight: normal;">Orginal</span></th>
								
								<?php
								foreach($specs as $spec) {
									?>
									
									<th style="text-align: right;">Price<br /><span style="font-weight: normal;"><?php echo $spec; ?></span></th>
									<th style="text-align: right;">Saving<br /><span style="font-weight: normal;">Over bulb life</span></th>
									
									<?php
								}
								?>
							</tr>
					
							<?php
						    $data = new DataQuery(sprintf("SELECT * FROM led_location WHERE typeId=%d ORDER BY name ASC", mysql_real_escape_string($type->id)));
						    while($data->Row) {
								?>
							
								<tr>
									<th colspan="<?php echo count($specs) + 5; ?>"><?php echo stripslashes($data->Row['name']); ?></th>
								</tr>
								
			      				<?php
						        $data2 = new DataQuery(sprintf("SELECT lp.*, p.Product_Title FROM led_product AS lp INNER JOIN product AS p ON p.Product_ID=lp.productId WHERE lp.locationId=%d AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' ORDER BY p.Product_Title ASC", $data->Row['id']));
						        while($data2->Row) {
						        	$product = new Product();
						        	$product->ID = $data2->Row['productId'];
						        	
									foreach($productPrices[$data2->Row['productId']] as $quantity=>$price) {
										$product->PriceOurs = $price['Price_Base_Our'];
										$product->PriceRRP = $price['Price_Base_RRP'];
										
										break;
									}
									
									if(isset($productOffers[$data2->Row['productId']])) {
										$price = current($productOffers[$data2->Row['productId']]);
									
										$product->PriceOffer = $price['Price_Offer'];
									}
									
									$product->GetPrice();
									
									$shownCustomPrice = false;

									if($session->IsLoggedIn) {
										if($session->Customer->Contact->IsTradeAccount == 'N') {
											if(count($discountCollection->Line) > 0){
												list($discountAmount, $discountName) = $discountCollection->DiscountProduct($product, 1);

												if($discountAmount < $product->PriceCurrent)  {
													$shownCustomPrice = true;

													$product->PriceCurrent = $discountAmount;
													
													$product->PriceCurrentIncTax = $product->PriceCurrent + $globalTaxCalculator->GetTax($discountAmount, $product->TaxClass->ID);
													$product->PriceCurrentIncTax = round($product->PriceCurrentIncTax, 2);
												}
											}
										}
									}

									if(!$shownCustomPrice) {
										if($session->Customer->Contact->IsTradeAccount == 'Y') {
											$retailPrice = $product->PriceCurrent;
											$tradeCost = ($product->CacheRecentCost > 0) ? $product->CacheRecentCost : $product->CacheBestCost;
											
											$product->PriceOurs = ContactProductTrade::getPrice($session->Customer->Contact->ID, $product->ID);
											$product->PriceOurs = ($product->PriceOurs <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $product->ID) / 100) + 1) : $product->PriceOurs;

											$product->PriceCurrent = $product->PriceOurs;
											
											$product->PriceCurrentIncTax = $product->PriceCurrent + $globalTaxCalculator->GetTax($product->PriceCurrent, $product->TaxClass->ID);
											$product->PriceCurrentIncTax = round($product->PriceCurrentIncTax, 2);
										}
									}
									
									$altProducts = array();
			        				
			        				if(isset($alternatives[$product->ID])) {
			        					$productOutput = array();
			        					
			        					foreach($specs as $spec) {
			        						$productOutput[$spec] = null;
										}
			        					
			        					foreach($productOutput as $key=>$outputData) {
			        						foreach($alternatives[$product->ID] as $alternative) {
			        							if(stristr($alternative['Value'], $key)) {
			        								$productOutput[$key] = $alternative;
			        								
			        								break;	
												}
											}
										}
										
										foreach($productOutput as $key=>$outputData) {
											if(!is_null($outputData)) {
												$altProduct = new Product();
						        				$altProduct->ID = $outputData['Product_ID'];
						        				$altProduct->Name = $outputData['Product_Title'];
						        				
												foreach($productPrices[$outputData['Product_ID']] as $quantity=>$price) {
													$altProduct->PriceOurs = $price['Price_Base_Our'];
													$altProduct->PriceRRP = $price['Price_Base_RRP'];
													
													break;
												}
												
												if(isset($productOffers[$outputData['Product_ID']])) {
													$price = current($productOffers[$outputData['Product_ID']]);
												
													$altProduct->PriceOffer = $price['Price_Offer'];
												}
												
												$altProduct->GetPrice();
												
												$shownCustomPrice = false;

												if($session->IsLoggedIn) {
													if($session->Customer->Contact->IsTradeAccount == 'N') {
														if(count($discountCollection->Line) > 0){
															list($discountAmount, $discountName) = $discountCollection->DiscountProduct($altProduct, 1);

															if($discountAmount < $altProduct->PriceCurrent)  {
																$shownCustomPrice = true;

																$altProduct->PriceCurrent = $discountAmount;
																
																$altProduct->PriceCurrentIncTax = $altProduct->PriceCurrent + $globalTaxCalculator->GetTax($discountAmount, $altProduct->TaxClass->ID);
																$altProduct->PriceCurrentIncTax = round($altProduct->PriceCurrentIncTax, 2);
															}
														}
													}
												}

												if(!$shownCustomPrice) {
													if($session->Customer->Contact->IsTradeAccount == 'Y') {
														$retailPrice = $altProduct->PriceCurrent;
														$tradeCost = ($altProduct->CacheRecentCost > 0) ? $altProduct->CacheRecentCost : $altProduct->CacheBestCost;
														
														$altProduct->PriceOurs = ContactProductTrade::getPrice($session->Customer->Contact->ID, $altProduct->ID);
														$altProduct->PriceOurs = ($altProduct->PriceOurs <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $altProduct->ID) / 100) + 1) : $altProduct->PriceOurs;

														$altProduct->PriceCurrent = $altProduct->PriceOurs;
														
														$altProduct->PriceCurrentIncTax = $altProduct->PriceCurrent + $globalTaxCalculator->GetTax($altProduct->PriceCurrent, $altProduct->TaxClass->ID);
														$altProduct->PriceCurrentIncTax = round($altProduct->PriceCurrentIncTax, 2);
													}
												}
												
												$specType = null;
												$specEquivalentWattage = null;
												$specWattage = null;
												$specLampLife = null;

												if(!empty($groupsType)) {
													$data3 = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsType), mysql_real_escape_string($altProduct->ID)));
													if($data3->TotalRows > 0) {
														$specType = $data3->Row['Value'];
													}
													$data3->Disconnect();
												}
												
												if(!empty($groupsEquivalentWattage)) {
													$data3 = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsEquivalentWattage), mysql_real_escape_string($altProduct->ID)));
													if($data3->TotalRows > 0) {
														$specEquivalentWattage = preg_replace('/[^0-9]/', '', $data3->Row['Value']);
													}
													$data3->Disconnect();
												}

												if(!empty($groupsWattage)) {
													$data3 = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsWattage), mysql_real_escape_string($altProduct->ID)));
													if($data3->TotalRows > 0) {
														$specWattage = preg_replace('/[^0-9]/', '', $data3->Row['Value']);
													}
													$data3->Disconnect();
												}

												if(!empty($groupsLampLife)) {
													$data3 = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsLampLife), mysql_real_escape_string($altProduct->ID)));
													if($data3->TotalRows > 0) {
														$specLampLife = preg_replace('/[^0-9]/', '', $data3->Row['Value']);
													}
													$data3->Disconnect();
												}
												
												$energySaving = 0;
												
												if(!empty($specEquivalentWattage) && !empty($specWattage) && !empty($specLampLife)) {
													$energySaving = ($specEquivalentWattage - $specWattage) * (12 / 100 / 1000) * $specLampLife;
												}
												
												$altProducts[$key] = array('Product' => $altProduct, 'Saving' => $energySaving);
											}
										}
									}
									
									$totalPrice += $product->PriceCurrent * $data2->Row['quantity'];
									?>
									
									<tr>
										<td width="10%"><?php echo $data2->Row['position']; ?></td>
										<td width="5%"><?php echo $data2->Row['quantity']; ?>x</td>
										<td><a href="product.php?pid=<?php echo $data2->Row['productId']; ?>"><?php echo strip_tags($data2->Row['Product_Title']); ?></a> (&pound;<?php echo number_format($product->PriceCurrent, 2, '.', ','); ?>)</td>
										<td width="12%%" align="right">&pound;<?php echo number_format($product->PriceCurrent * $data2->Row['quantity'], 2, '.', ','); ?></td>
										<td width="12%">&nbsp;</td>
										<td width="12%">&nbsp;</td>
									</tr>
									
									<?php
									foreach($altProducts as $key=>$outputData) {
										$totalPrices[$key] += $outputData['Product']->PriceCurrent * $data2->Row['quantity'];
										$totalSavings[$key] += $outputData['Saving'] * $data2->Row['quantity'];
										?>
										
										<tr>
											<td style="background-color: #fff;" width="10%">&nbsp;</td>
											<td style="background-color: #fff;" width="5%">&nbsp;</td>
											<td style="background-color: #fff;"><?php echo $key; ?> Alternative: <a href="product.php?pid=<?php echo $outputData['Product']->ID; ?>"><?php echo $outputData['Product']->Name; ?></a> (&pound;<?php echo number_format($outputData['Product']->PriceCurrent, 2, '.', ','); ?>)</td>
											<td style="background-color: #fff;" width="12%">&nbsp;</td>
											<td style="background-color: #fff;" width="12%%" align="right">&pound;<?php echo number_format($outputData['Product']->PriceCurrent * $data2->Row['quantity'], 2, '.', ','); ?></td>
											<td style="background-color: #fff;" width="12%%" align="right">&pound;<?php echo number_format($outputData['Saving'] * $data2->Row['quantity'], 2, '.', ','); ?></td>
										</tr>
									
										<?php	
									}

			        				$data2->Next();
								}
								$data2->Disconnect();
								
			        			$data->Next();
							}
							$data->Disconnect();
							?>
					
						</table>
						<br />
						
						<?php
						$overallPrice = 0;
						$overallSaving = 0;
						
						foreach($specs as $spec) {
							$overallPrice += $totalPrices[$spec];
							$overallSaving += $totalSavings[$spec];
						}
						?>
								
						<table cellspacing="0" class="catProducts">
							<tr>
								<th>&nbsp;</th>
								<th style="text-align: right;">Price<br /><span style="font-weight: normal;">Total</span></th>
								<th style="text-align: right;">LED Price<br /><span style="font-weight: normal;">Additional cost</span></th>
								<th style="text-align: right;">Saving<br /><span style="font-weight: normal;">Total</span></th>
							</tr>
							<tr>
								<td>&nbsp;</td>
								<td width="12%%" align="right"><strong>&pound;<?php echo number_format($totalPrice, 2, '.', ','); ?></strong></td>
								<td width="12%%" align="right"><strong>&pound;<?php echo number_format($overallPrice - $totalPrice, 2, '.', ','); ?></strong></td>
						       	<td width="12%%" align="right"><strong>&pound;<?php echo number_format($overallSaving, 2, '.', ','); ?></strong></td>
			        		</tr>
			        	</table>
			        	<br />
			        	
			        	<table cellspacing="0" class="catProducts">
							<tr>
								<th style="text-align: right; font-size: 14px; color: #0978c6;">Potential saving over predicted manufacturers lamp life of products</th>
							</tr>
							<tr>
						       	<td align="right"><strong>&pound;<?php echo number_format($overallSaving - $totalPrice, 2, '.', ','); ?></strong></td>
			        		</tr>
			        	</table>
						
						<?php
					} else {
						?>
						
						<p>Below are examples of different installations that have been converted to energy saving and LED products. Click on an example to see a breakdown of the installation.</p>
						
				        <table cellspacing="0" class="catProducts">
							<tr>
								<th>Select example dwelling</th>
								<th style="text-align: right;">Potential saving</th>
							</tr>
							
			      			<?php
					        $data9 = new DataQuery(sprintf("SELECT * FROM led_type ORDER BY name ASC"));
					        while($data9->Row) {
					        	$products = array();
			        	
			        			$data = new DataQuery(sprintf("SELECT lp.productId FROM led_location AS ll INNER JOIN led_product AS lp ON lp.locationId=ll.id WHERE ll.typeId=%d", $data9->Row['id']));
							    while($data->Row) {
					    			$products[$data->Row['productId']] = $data->Row['productId'];

					    			$data->Next();
								}
								$data->Disconnect();
								
								$dataSpecs = array();
								
								foreach($specs as $spec) {
									$dataSpecs[] = sprintf('\'%s\'', $spec);	
								}
								
								$alternatives = array();

								$data = new DataQuery(sprintf("SELECT pr.Related_To_Product_ID, pr.Product_ID, p.Product_Title, psv.Value FROM product_related AS pr INNER JOIN product AS p ON p.Product_ID=pr.Product_ID INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID INNER JOIN product_specification_value AS psv On psv.Value_ID=ps.Value_ID AND psv.Group_ID=222 WHERE pr.Related_To_Product_ID IN (%s) AND pr.Is_Active='Y' AND p.Is_Active='Y' AND pr.Type LIKE 'Energy Saving Alternative' AND psv.Value IN (%s) GROUP BY pr.Related_To_Product_ID, pr.Product_ID", implode(', ', $products), implode(', ', $dataSpecs)));
								while($data->Row) {
									if(!isset($alternatives[$data->Row['Related_To_Product_ID']])) {
										$alternatives[$data->Row['Related_To_Product_ID']] = array();
									}

									$alternatives[$data->Row['Related_To_Product_ID']][] = $data->Row;
									
									$products[$data->Row['Product_ID']] = $data->Row['Product_ID'];
								
									$data->Next();		
								}
								$data->Disconnect();
								
								$sqlPrices = sprintf("SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity FROM product AS p INNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() WHERE p.Product_ID IN (%s) AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' ORDER BY pp.Price_Starts_On ASC", implode(', ', $products));
								$sqlOffers = sprintf("SELECT p.Product_ID, po.Price_Offer FROM product AS p INNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) WHERE p.Product_ID IN (%s) AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' ORDER BY po.Offer_Start_On ASC", implode(', ', $products));

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
								
								foreach($productPrices as $productId=>$prices) {
									ksort($productPrices[$productId]);
									reset($productPrices[$productId]);
								}

								foreach($productOffers as $productId=>$prices) {
									ksort($productOffers[$productId]);
									reset($productOffers[$productId]);
								}
								
								$totalPrices = array();
								$totalSavings = array();
								
								foreach($specs as $spec) {
			        				$totalPrices[$spec] = 0;
			        				$totalSavings[$spec] = 0;
								}
							
								$totalPrice = 0;

								$data = new DataQuery(sprintf("SELECT * FROM led_location WHERE typeId=%d ORDER BY name ASC", $data9->Row['id']));
								while($data->Row) {
								    $data2 = new DataQuery(sprintf("SELECT lp.*, p.Product_Title FROM led_product AS lp INNER JOIN product AS p ON p.Product_ID=lp.productId WHERE lp.locationId=%d AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' ORDER BY p.Product_Title ASC", $data->Row['id']));
								    while($data2->Row) {
						        		$product = new Product();
						        		$product->ID = $data2->Row['productId'];
						        		
										foreach($productPrices[$data2->Row['productId']] as $quantity=>$price) {
											$product->PriceOurs = $price['Price_Base_Our'];
											$product->PriceRRP = $price['Price_Base_RRP'];
											
											break;
										}
										
										if(isset($productOffers[$data2->Row['productId']])) {
											$price = current($productOffers[$data2->Row['productId']]);
										
											$product->PriceOffer = $price['Price_Offer'];
										}
										
										$product->GetPrice();
										
										$shownCustomPrice = false;

										if($session->IsLoggedIn) {
											if($session->Customer->Contact->IsTradeAccount == 'N') {
												if(count($discountCollection->Line) > 0){
													list($discountAmount, $discountName) = $discountCollection->DiscountProduct($product, 1);

													if($discountAmount < $product->PriceCurrent)  {
														$shownCustomPrice = true;

														$product->PriceCurrent = $discountAmount;
														
														$product->PriceCurrentIncTax = $product->PriceCurrent + $globalTaxCalculator->GetTax($discountAmount, $product->TaxClass->ID);
														$product->PriceCurrentIncTax = round($product->PriceCurrentIncTax, 2);
													}
												}
											}
										}

										if(!$shownCustomPrice) {
											if($session->Customer->Contact->IsTradeAccount == 'Y') {
												$retailPrice = $product->PriceCurrent;
												$tradeCost = ($product->CacheRecentCost > 0) ? $product->CacheRecentCost : $product->CacheBestCost;
												
												$product->PriceOurs = ContactProductTrade::getPrice($session->Customer->Contact->ID, $product->ID);
												$product->PriceOurs = ($product->PriceOurs <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $product->ID) / 100) + 1) : $product->PriceOurs;

												$product->PriceCurrent = $product->PriceOurs;
												
												$product->PriceCurrentIncTax = $product->PriceCurrent + $globalTaxCalculator->GetTax($product->PriceCurrent, $product->TaxClass->ID);
												$product->PriceCurrentIncTax = round($product->PriceCurrentIncTax, 2);
											}
										}
										
										$altProducts = array();
			        					
			        					if(isset($alternatives[$product->ID])) {
			        						$productOutput = array();
			        						
			        						foreach($specs as $spec) {
			        							$productOutput[$spec] = null;
											}
			        						
			        						foreach($productOutput as $key=>$outputData) {
			        							foreach($alternatives[$product->ID] as $alternative) {
			        								if(stristr($alternative['Value'], $key)) {
			        									$productOutput[$key] = $alternative;
			        									
			        									break;	
													}
												}
											}
											
											foreach($productOutput as $key=>$outputData) {
												if(!is_null($outputData)) {
													$altProduct = new Product();
						        					$altProduct->ID = $outputData['Product_ID'];
						        					$altProduct->Name = $outputData['Product_Title'];
						        					
													foreach($productPrices[$outputData['Product_ID']] as $quantity=>$price) {
														$altProduct->PriceOurs = $price['Price_Base_Our'];
														$altProduct->PriceRRP = $price['Price_Base_RRP'];
														
														break;
													}
													
													if(isset($productOffers[$outputData['Product_ID']])) {
														$price = current($productOffers[$outputData['Product_ID']]);
													
														$altProduct->PriceOffer = $price['Price_Offer'];
													}
													
													$altProduct->GetPrice();
													
													$shownCustomPrice = false;

													if($session->IsLoggedIn) {
														if($session->Customer->Contact->IsTradeAccount == 'N') {
															if(count($discountCollection->Line) > 0){
																list($discountAmount, $discountName) = $discountCollection->DiscountProduct($altProduct, 1);

																if($discountAmount < $altProduct->PriceCurrent)  {
																	$shownCustomPrice = true;

																	$altProduct->PriceCurrent = $discountAmount;
																	
																	$altProduct->PriceCurrentIncTax = $altProduct->PriceCurrent + $globalTaxCalculator->GetTax($discountAmount, $altProduct->TaxClass->ID);
																	$altProduct->PriceCurrentIncTax = round($altProduct->PriceCurrentIncTax, 2);
																}
															}
														}
													}

													if(!$shownCustomPrice) {
														if($session->Customer->Contact->IsTradeAccount == 'Y') {
															$retailPrice = $altProduct->PriceCurrent;
															$tradeCost = ($altProduct->CacheRecentCost > 0) ? $altProduct->CacheRecentCost : $altProduct->CacheBestCost;
															
															$altProduct->PriceOurs = ContactProductTrade::getPrice($session->Customer->Contact->ID, $altProduct->ID);
															$altProduct->PriceOurs = ($altProduct->PriceOurs <= 0) ? $tradeCost * ((TradeBanding::GetMarkup($tradeCost, $altProduct->ID) / 100) + 1) : $altProduct->PriceOurs;

															$altProduct->PriceCurrent = $altProduct->PriceOurs;
															
															$altProduct->PriceCurrentIncTax = $altProduct->PriceCurrent + $globalTaxCalculator->GetTax($altProduct->PriceCurrent, $altProduct->TaxClass->ID);
															$altProduct->PriceCurrentIncTax = round($altProduct->PriceCurrentIncTax, 2);
														}
													}
													
													$specType = null;
													$specEquivalentWattage = null;
													$specWattage = null;
													$specLampLife = null;

													if(!empty($groupsType)) {
														$data3 = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsType), mysql_real_escape_string($altProduct->ID)));
														if($data3->TotalRows > 0) {
															$specType = $data3->Row['Value'];
														}
														$data3->Disconnect();
													}
													
													if(!empty($groupsEquivalentWattage)) {
														$data3 = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsEquivalentWattage), mysql_real_escape_string($altProduct->ID)));
														if($data3->TotalRows > 0) {
															$specEquivalentWattage = preg_replace('/[^0-9]/', '', $data3->Row['Value']);
														}
														$data3->Disconnect();
													}

													if(!empty($groupsWattage)) {
														$data3 = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsWattage), mysql_real_escape_string($altProduct->ID)));
														if($data3->TotalRows > 0) {
															$specWattage = preg_replace('/[^0-9]/', '', $data3->Row['Value']);
														}
														$data3->Disconnect();
													}

													if(!empty($groupsLampLife)) {
														$data3 = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsLampLife), mysql_real_escape_string($altProduct->ID)));
														if($data3->TotalRows > 0) {
															$specLampLife = preg_replace('/[^0-9]/', '', $data3->Row['Value']);
														}
														$data3->Disconnect();
													}
													
													$energySaving = 0;
													
													if(!empty($specEquivalentWattage) && !empty($specWattage) && !empty($specLampLife)) {
														$energySaving = ($specEquivalentWattage - $specWattage) * (12 / 100 / 1000) * $specLampLife;
													}
													
													$altProducts[$key] = array('Product' => $altProduct, 'Saving' => $energySaving);
												}
											}
										}
										
										$totalPrice += $product->PriceCurrent * $data2->Row['quantity'];

										foreach($altProducts as $key=>$outputData) {
											$totalPrices[$key] += $outputData['Product']->PriceCurrent * $data2->Row['quantity'];
											$totalSavings[$key] += $outputData['Saving'] * $data2->Row['quantity'];
										}

			        					$data2->Next();
									}
									$data2->Disconnect();
									
			        				$data->Next();
								}
								$data->Disconnect();

								$overallPrice = 0;
								$overallSaving = 0;
								
								foreach($specs as $spec) {
									$overallPrice += $totalPrices[$spec];
									$overallSaving += $totalSavings[$spec];
								}
								?>
								
								<tr>
									<td><a href="?id=<?php echo $data9->Row['id']; ?>"><?php echo stripslashes($data9->Row['name']); ?></a></td>
									<td align="right">&pound;<?php echo number_format($overallSaving - $totalPrice, 2, ',', '.'); ?></td>
								</tr>
								
								<?php						
			        			$data9->Next();
							}
							$data9->Disconnect();
							?>
							
						</table>
						
						<?php
					}
					?>

					<!-- InstanceEndEditable -->
                </div>
            </div>
            <div id="PageFooter">
                <ul class="links">
                    <li><a href="./terms.php" title="BLT Direct Terms and Conditions of Use and Sale">Terms and Conditions</a></li>
                    <li><a href="./privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
                    <li><a href="./company.php" title="About BLT Direct">About Us</a></li>
                    <li><a href="./sitemap.php" title="Map of Site Contents">Site Map</a></li>
                    <li><a href="./support.php" title="Contact BLT Direct">Contact Us</a></li>
                    <li><a href="./index.php" title="Light Bulbs">Light Bulbs</a></li>
                    <li><a href="./products.php?cat=1251&amp;nm=Christmas+Lights" title="Christmas Lights">Christmas Lights</a></li> 
                    <li><a href="./Projector-Lamps.php" title="Projector Lamps">Projector Lamps</a></li>
                    <li><a href="./articles.php" title="Press Releases/Articles">Press Releases/Articles</a></li>
                </ul>
                
                <p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
            </div>
        </div>
        <div id="LeftNav">
            <?php require('lib/templates/left.php'); ?>
        </div>
        <div id="RightNav">
            <?php require('lib/templates/right.php'); ?>
        
            <div id="Azexis">
                <a href="http://www.azexis.com" target="_blank" title="Web Designers">Web Designers</a>
            </div>
        </div>
    </div>
	<script src="<?php print ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT']) ? 'http://www' : 'https://ssl'; ?>.google-analytics.com/urchin.js" type="text/javascript"></script>
	<script type="text/javascript">
	//<![CDATA[
		_uacct = "UA-1618935-2";
		urchinTracker();
	//]]>
	</script>

	<!-- InstanceBeginEditable name="Tracking Script" -->

<!--
<script>
var parm,data,rf,sr,htprot='http'; if(self.location.protocol=='https:')htprot='https';
rf=document.referrer;sr=document.location.search;
if(top.document.location==document.referrer||(document.referrer == '' && top.document.location != '')) {rf=top.document.referrer;sr=top.document.location.search;}
data='cid=256336&rf=' + escape(rf) + '&sr=' + escape(sr); parm=' border="0" hspace="0" vspace="0" width="1" height="1" '; document.write('<img '+parm+' src="'+htprot+'://stats1.saletrack.co.uk/scripts/stinit.asp?'+data+'">');
</script>
<noscript>
<img src="http://stats1.saletrack.co.uk/scripts/stinit.asp?cid=256336&rf=JavaScri
pt%20Disabled%20Browser" border="0" width="0" height="0" />
</noscript>
-->

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>
<?php include('lib/common/appFooter.php');