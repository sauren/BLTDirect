<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryBreadCrumb.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable_mobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecFilter.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
function checkCategories($id) {
	$data = new DataQuery(sprintf("SELECT Category_Parent_ID, Product_Offer_ID FROM product_categories WHERE Category_ID=%d", mysql_real_escape_string($id)));
	if($data->TotalRows > 0) {
		return ($data->Row['Product_Offer_ID'] > 0) ? $data->Row['Product_Offer_ID'] : checkCategories($data->Row['Category_Parent_ID']);
	} else {
		return 0;
	}
	$data->Disconnect();
}

$category = new Category();
if(!$category->Get(id_param('cat', 1))) {
	redirect("Location: index.php");
}

if(($category->IsRedirecting == 'Y') && (strlen(trim($category->RedirectUrl))) > 0) {
	redirect(sprintf("Location: %s", trim($category->RedirectUrl)));
}

if($session->Customer->Contact->IsTradeAccount == 'Y') {
	$category->Layout = 'Table';
}

$breadCrumb = new CategoryBreadCrumb();
$breadCrumb->Get($category->ID);

$scriptFile = 'products.php';

if(stristr($_SERVER['PHP_SELF'], $scriptFile) === false) {
	$_SERVER['PHP_SELF'] = $scriptFile;
	$_SERVER['SCRIPT_NAME'] = $scriptFile;
	$_SERVER['QUERY_STRING'] = sprintf('cat=%d&nm=%s', $category->ID, urlencode(!empty($category->MetaTitle) ? $category->MetaTitle : $category->Name));
}

if(!isset($_SESSION['Category'][$category->ID]['Layout'])) {
	$_SESSION['Category'][$category->ID]['Layout'] = strtolower($category->Layout);
}

if(param('layout')) {
	$_SESSION['Category'][$category->ID]['Layout'] = strtolower(param('layout'));
}

$filter = new ProductSpecFilter();

if($category->ID > 0) {
	if($category->IsFilterAvailable == 'Y') {
		$filter->Build();

		if($action == 'listmore') {
			$groupFound = false;

			if(id_param('group')) {
				if(count($filter->SpecGroup) > 0) {
					for($i=0; $i<count($filter->SpecGroup); $i++) {

						if($filter->SpecGroup[$i]['Group_ID'] == id_param('group')) {
							$groupFound = true;
							break;
						}
					}
				}
			}

			if(!$groupFound) {
				redirect(sprintf("Location: %s%s", $_SERVER['PHP_SELF'], (strlen($filter->FilterQueryString) > 0) ? sprintf('?%s', $filter->FilterQueryString) : ''));
			}
		}

		$specColour = array();
		$maxColours = 9;
		$index = 0;

		$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Is_Hidden='N' AND Is_Filterable='Y' ORDER BY Sequence_Number, Group_ID ASC"));
		while($data->Row) {
			if($index >= $maxColours) {
				$index = 0;
			}

			$index++;

			$specColour[$data->Row['Group_ID']] = $index;

			$data->Next();
		}
		$data->Disconnect();
	}
}

$specificationTitle = array();
$specificationTitleStr = '';

if(count($filter->Filter) > 0) {
	foreach($filter->Filter as $filterItem) {
		$specificationTitle[] = sprintf('%s %s', $filterItem->GetUnitValue(), $filterItem->Group->Name);
	}

	$specificationTitleStr = sprintf('%s, ', implode(', ', $specificationTitle));
}

$disableFilters = (count($filter->Filter) >= Setting::GetValue('spec_filter_limit')) ? true : false;

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('showall', 'Show All', 'checkbox', ($category->IsProductListAvailable == 'Y') ? 'N' : 'Y', 'boolean', 1, 1, false, 'onclick="toggleProductList(this);"');
$form->SetValue('showall', (param('products_Current')) ? 'Y' : $form->GetValue('showall'));
?>
<?php include("ui/nav.php");
include("ui/search.php");?>
<meta name="Keywords" content="<?php echo $category->MetaKeywords; ?>" />
<meta name="Description" content="<?php echo $category->MetaDescription; ?>" />
<link rel="canonical" href="<?php echo sprintf('$GLOBALS["MOBILE_LINK"]/products.php?cat=%d', $category->ID); ?>" />
<link href="css/Filter.css" rel="stylesheet" type="text/css" />
<?php
	if($category->ID > 0) {
		if($category->IsFilterAvailable == 'Y') {
			?>

			<script type="text/javascript">
				var menuFilter1 = new bltdirect.ui.Menu('menuFilter1');
				menuFilter1.addClass('topMenu', 'FilterMenuContainer', 'down');
				menuFilter1.addClass('subMenu', 'FilterContainer FilterMenuContainerSubMenu', 'left');

				<?php
				for($i=0; $i<count($filter->SpecGroup); $i++) {
					$rows = ($filter->MaxRows >= count($filter->SpecGroup[$i]['Values'])) ? count($filter->SpecGroup[$i]['Values']) : $filter->MaxRows;

					echo sprintf("menuFilter1.add('filter%s', null, '%s', '%s?action=listmore&amp;group=%d%s', null, 'topMenu');\n", preg_replace('/[^A-Za-z0-9]/', '', ucwords(strtolower($filter->SpecGroup[$i]['Name']))), $filter->SpecGroup[$i]['Name'], $_SERVER['PHP_SELF'], $filter->SpecGroup[$i]['Group_ID'], (strlen($filter->FilterQueryString) > 0) ? sprintf('&amp;%s', $filter->FilterQueryString) : '');

					for($j=0; $j<$rows; $j++) {
						if((count($filter->SpecGroup[$i]['Values']) <= $rows) || ((count($filter->SpecGroup[$i]['Values']) > $rows) && ($j < $rows-1))) {
							echo sprintf("menuFilter1.add('filter%s', 'filter%s', '%s (%d)', '%s?%sfilter=%d', null, 'subMenu');\n", preg_replace('/[^A-Za-z0-9]/', '', ucwords(strtolower($filter->SpecGroup[$i]['Values'][$j]['Value']))), preg_replace('/[^A-Za-z0-9]/', '', ucwords(strtolower($filter->SpecGroup[$i]['Name']))), $filter->SpecGroup[$i]['Values'][$j]['UnitValue'], $filter->SpecGroup[$i]['Values'][$j]['Products'], $_SERVER['PHP_SELF'], (strlen($filter->FilterQueryString) > 0) ? sprintf('%s&amp;', $filter->FilterQueryString) : '', $filter->SpecGroup[$i]['Values'][$j]['Value_ID']);
						}
					}

					if(count($filter->SpecGroup[$i]['Values']) > $rows) {
						echo sprintf("menuFilter1.add('filterListMore', 'filter%s', 'List More', '%s?action=listmore&amp;group=%d%s', null, 'subMenu');\n", preg_replace('/[^A-Za-z0-9]/', '', ucwords(strtolower($filter->SpecGroup[$i]['Name']))), $_SERVER['PHP_SELF'], $filter->SpecGroup[$i]['Group_ID'], (strlen($filter->FilterQueryString) > 0) ? sprintf('&amp;%s', $filter->FilterQueryString) : '');
					}
				}
				?>

				Interface.addListener(menuFilter1);
			</script>

			<?php
		}
	}
	?>
	<script type="text/javascript">
	var toggleProductList = function(obj) {
		var e = null;

		e = document.getElementById('products-list-all');

		if(e) {
			e.style.display = (!obj.checked) ? '' : 'none';
		}


		e = document.getElementById('products-list-partial');

		if(e) {
			e.style.display = (!obj.checked) ? 'none' : '';
		}
	}
	</script>
    <div class="cartmiddle1"><p style="font-size:16px;color:#333;  vertical-align:middle;"><?php echo htmlspecialchars($category->Name); ?></p></div>
    <div class="maincontent">
<div class="maincontent1">
<?php /*?>                <?php
				 $bc=$breadCrumb->Text;
					$newbc=str_replace('href="/','href="'. $GLOBALS['MOBILE_LINK'] . '/',$bc);
				?>
				<p class="breadcrumb"><strong><a href="index.php">Home</a> <?php echo $newbc; ?></strong></p>	<?php */?>			
				<?php //include('../lib/templates/bought_wspl.php'); ?>
				
				<?php
				$isStockWarning = false;
				
				$warnCategoriesStock = array(1634);

				$categories = getDirectionalCategories(array($category->ID), false);
				
				foreach($warnCategoriesStock as $categoryItem) {
					if(in_array($categoryItem, $categories)) {
						$isStockWarning = true;
					}		
				}
				
				if($isStockWarning) {
					?>
					
					<div class="attention">
						<div class="attention-icon attention-icon-warning"></div>
						<div class="attention-info attention-info-warning">
							<span class="attention-info-title">Stock Warning</span><br />
							For coloured bulbs please call our sales lines on <?php echo Setting::GetValue('telephone_sales_hotline'); ?> between 8:30 and 17:00. We are currently holding very limited stock - please call to check availability before placing your order.
						</div>
					</div>
					
					<?php
				}
				
								
				if($category->ID > 0) {
					if($category->IsFilterAvailable == 'Y') {
						if((count($filter->SpecGroup) > 0) || (count($filter->Filter) > 0)) {
							?>
                           <span>Refine your search using the filters below</span><br />
							<div class="Filter">
								<div class="FilterBottom">
									<div class="FilterLeft">
										<div class="FilterRight">
											<div class="FilterTop">
												<div class="FilterBottomLeft">
													<div class="FilterBottomRight">
														<div class="FilterTopLeft">
															<div class="FilterTopRight">
																<div class="FilterTypes" id="FilterTypeDiv">											
																</div>                                                                
                                                                <table width="100%" cellspacing="0" cellpadding="5" border="0" class="tblfilter"><tr>
                                                                <?php
																$colcount=0;
																	for($i=0; $i<count($filter->SpecGroup); $i++) {
																		$colcount++;
																		if($disableFilters) {
																			echo sprintf('<td><div class="FilterType"><span class="link"><span class="FilterTypePoint FilterTypePointDisabled"><span class="FilterTypeIcon FilterTypeIconCross">%s</span></span></span></div></td>', htmlspecialchars($filter->SpecGroup[$i]['Name']));
																		} else {
																			echo sprintf('<td><div class="FilterType"><a href="?action=listmore&amp;group=%d%s#FilterTypeDiv" id="filter%s" onmouseover="menuFilter1.onRollOver(\'filter%s\');" onmouseout="menuFilter1.onRollOut(\'filter%s\');"><span class="FilterTypePoint FilterTypePoint%d">%s</span></a></div></td>', $filter->SpecGroup[$i]['Group_ID'], (strlen($filter->FilterQueryString) > 0) ? sprintf('&amp;%s', $filter->FilterQueryString) : '', preg_replace('/[^A-Za-z0-9]/', '', ucwords(strtolower($filter->SpecGroup[$i]['Name']))), preg_replace('/[^A-Za-z0-9]/', '', ucwords(strtolower($filter->SpecGroup[$i]['Name']))), preg_replace('/[^A-Za-z0-9]/', '', ucwords(strtolower($filter->SpecGroup[$i]['Name']))), $specColour[$filter->SpecGroup[$i]['Group_ID']], htmlspecialchars($filter->SpecGroup[$i]['Name']));
																		}
																		if($colcount == 2){
																			echo'</tr><tr>';
																			$colcount=0;
																		}
																	}
																	
																	?></tr>
                                                                    </table>
																<div class="FilterDivider"></div>

																<?php
																if(count($filter->Filter) > 0) {
																	?>

																	<div class="FilterOptions">

																		<?php
																		echo sprintf('<div class="FilterOption FilterOptionRemove"><a href="%s%s#FilterTypeDiv"><span class="FilterOptionCross FilterOptionCrossRemove1">Remove All Filters</span></a></div>', $_SERVER['PHP_SELF'], (strlen($category->ID) > 0) ? sprintf('?cat=%d&amp;nm=%s', $category->ID, urlencode($category->MetaTitle)) : '');

																		foreach($filter->Filter as $filterItem) {
																			$tempFilterStr = $filter->GetFilterQueryString($filterItem->ID);

																			echo sprintf('<div class="FilterOption"><a href="%s%s#FilterTypeDiv"><span class="FilterOptionCross FilterOptionCross%d">%s: %s</span></a></div>', $_SERVER['PHP_SELF'], (strlen($tempFilterStr) > 0) ? sprintf('?%s', $tempFilterStr) : '', isset($specColour[$filterItem->Group->ID]) ? $specColour[$filterItem->Group->ID] : 0, $filterItem->Group->Name, $filterItem->GetUnitValue());
																		}
																		?>

																		<div class="clear"></div>
																	</div>

																	<?php
																}

																if((count($filter->Filter) > 0) && ($action != 'listmore')) {
																	?>

																	<div class="FilterDivider"></div>

																	<div class="FilterInstructions">
																		<p class="title">Bulb Finder Instructions</p>
																		<ul>
																			<li>Scroll down to see the list of products for this section matching your chosen criteria.</li>
																			<li>Select further filter options about the light bulb you have to narrow your search.</li>
																			<li class="red">Remove all filters to navigate back to the product catalogue level.</li>
																		</ul>
																	</div>

																	<?php
																}

																if((count($filter->Filter) > 0) && ($action == 'listmore')) {
																	echo '<div class="FilterDivider"></div>';
																}

																if($action == 'listmore') {
																	for($i=0; $i<count($filter->SpecGroup); $i++) {
																		if($filter->SpecGroup[$i]['Group_ID'] == id_param('group')) {
																			$maxColumns = $filter->MaxCols;
																			$columnArr = array();
																			$tempCol = 0;
																			$count = 0;
																			$itemsUsed = 0;

																			for($j=0;$j < count($filter->SpecGroup[$i]['Values']); $j++) {
																				$columnArr[$tempCol][] = $filter->SpecGroup[$i]['Values'][$j];
																				$count++;

																				if($count >= ceil((count($filter->SpecGroup[$i]['Values']) - $itemsUsed) / ($maxColumns - $tempCol))) {
																					$itemsUsed += count($columnArr[$tempCol]);
																					$tempCol++;
																					$count = 0;
																				}
																			}
																			?>

																			<div class="FilterMore">
																				<table style="width:100%">

																					<?php
																					for($j=0; $j<count($columnArr[0]); $j++) {
																						?>

																						<tr>

																							<?php
																							for($k=0; $k<$maxColumns; $k++) {
																								if(isset($columnArr[$k][$j])) {
																									echo sprintf('<td style="font-size:13px;width:%s%%" valign="top"><a href="%s?%sfilter=%d#FilterTypeDiv">%s</a> (%d)</td>', number_format(100/$maxColumns, 2, '.', ''), $_SERVER['PHP_SELF'], (strlen($filter->FilterQueryString) > 0) ? sprintf('%s&amp;', $filter->FilterQueryString) : '', $columnArr[$k][$j]['Value_ID'], $columnArr[$k][$j]['UnitValue'], $columnArr[$k][$j]['Products']);
																								} else {
																									echo '<td>&nbsp;</td>';
																								}
																							}
																							?>

																						</tr>

																						<?php
																					}
																					?>

																				</table>
																			</div>

																			<?php
																			break;
																		}
																	}
																}
																?>

															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<?php
						}
					}
				}

				if($action != 'listmore') {
					if($category->IsProductListAvailable == 'Y') {
						if(count($filter->Filter) > 0) {
							$index = 0;

							$sqlFrom = '';
							$sqlWhere = '';

							foreach($filter->Filter as $groupId=>$specValue) {
								$index++;

								$sqlFrom .= sprintf("INNER JOIN product_specification AS ps%d ON ps%d.Product_ID=p.Product_ID AND ps%d.Value_ID=%d ", mysql_real_escape_string($index), mysql_real_escape_string($index), mysql_real_escape_string($index), mysql_real_escape_string($specValue->ID));
							}

							if($category->ID > 0) {
								$sqlFrom .= sprintf("INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.product_ID AND (pic.Category_ID=%s) ", implode(' OR pic.Category_ID=', $filter->Categories));
							}

							$sql = sprintf("SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Meta_Title, p.SKU FROM product AS p %s WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' GROUP BY p.Product_ID", $sqlFrom);
						} else {
							$sql = sprintf("SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Meta_Title, p.SKU FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID=%d WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' GROUP BY p.Product_ID", mysql_real_escape_string($category->ID));
						}

						$data = new DataQuery($sql);
						if($data->TotalRows > 0) {
							echo $form->Open();
							?>

							<table cellspacing="0" class="homeProducts">
								<tr>
									<th style="color: #f00;"><?php echo $form->GetHTML('showall'); ?> Show images and prices</th>
								</tr>
							</table><br />

							<div id="products-list-all" <?php echo ($form->GetValue('showall') == 'N') ? '' : 'style="display: none;"'; ?>>
								<table cellspacing="0" class="homeProducts">
									<tr>
										<th>Products</th>
										<th>Part Number</th>
									</tr>

									<?php
									while($data->Row) {
										?>

										<tr>
											<td style="width:75%"><a href="./product.php?pid=<?php echo $data->Row['Product_ID']; ?>&amp;cat=<?php echo $category->ID; ?>&amp;nm=<?php echo urlencode($data->Row['Meta_Title']); ?>" title="<?php echo $data->Row['Meta_Title']; ?>"><?php echo $data->Row['Product_Title']; ?></a></td>
											<td style="width:25%"><?php echo $data->Row['SKU']; ?></td>
										</tr>

										<?php
										$data->Next();
									}
									?>

								</table>
							</div>

							<?php
							echo $form->Close();
						}
						$data->Disconnect();
					}
					
					switch(strtolower($category->Order)) {
						case 'product_title':
							$order = 'p.Product_Title';
							break;
							
						case 'rank':
							$order = 'pic.Sequence_Number';
							break;
							
						case 'sku':
							$order = 'p.SKU';
							break;
							
						default:
							$order = 'p.Product_ID';
							break;
					}
					
					if(count($filter->Filter) > 0) {
						$sqlFrom = '';
						$sqlWhere = '';

						foreach($filter->Filter as $groupId=>$specValue) {
							$index++;

							$sqlFrom .= sprintf("INNER JOIN product_specification AS ps%d ON ps%d.Product_ID=p.Product_ID AND ps%d.Value_ID=%d ", $index, $index, $index, $specValue->ID);
						}

						if(count($filter->Categories) > 0) {
							$sqlFrom .= sprintf("INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.product_ID AND (pic.Category_ID=%s) ", implode(' OR pic.Category_ID=', $filter->Categories));
						}

						$sql = sprintf("SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Discontinued_Show_Price, p.CacheBestCost, p.CacheRecentCost, p.Product_Codes, p.Cache_Specs_Primary, p.Meta_Title, p.SKU, p.Order_Min, p.Average_Despatch, pi.Image_Thumb, MIN(ws.Backorder_Expected_On) AS Backorder_Expected_On FROM product AS p LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Backordered='Y' LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='Y' AND pi.Is_Primary='Y' %s WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' GROUP BY p.Product_ID ", $sqlFrom);
						$sqlOrder = sprintf("ORDER BY %s ASC", $order);
						$sqlTotalRows = sprintf("SELECT COUNT(DISTINCT p.Product_ID) AS TotalRows FROM product AS p %s WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N'", $sqlFrom);

						$sqlPrices = sprintf("SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity FROM product AS p INNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() %s WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' ORDER BY pp.Price_Starts_On ASC", $sqlFrom);
						$sqlOffers = sprintf("SELECT p.Product_ID, po.Price_Offer FROM product AS p INNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) %s WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' ORDER BY po.Offer_Start_On ASC", $sqlFrom);
					} else {
						$sql = sprintf("SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Discontinued_Show_Price, p.CacheBestCost, p.CacheRecentCost, p.Product_Codes, p.Cache_Specs_Primary, p.Meta_Title, p.SKU, p.Order_Min, p.Average_Despatch, pi.Image_Thumb, MIN(ws.Backorder_Expected_On) AS Backorder_Expected_On FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID=%d LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Backordered='Y' LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='Y' AND pi.Is_Primary='Y' WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' GROUP BY p.Product_ID ", mysql_real_escape_string($category->ID));
						$sqlOrder = sprintf("ORDER BY %s ASC", mysql_real_escape_string($order));
						$sqlTotalRows = sprintf("SELECT COUNT(DISTINCT p.Product_ID) AS TotalRows FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID=%d WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N'", mysql_real_escape_string($category->ID));

						$sqlPrices = sprintf("SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID=%d INNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' ORDER BY pp.Price_Starts_On ASC", mysql_real_escape_string($category->ID));
						$sqlOffers = sprintf("SELECT p.Product_ID, po.Price_Offer FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID=%d INNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' ORDER BY po.Offer_Start_On ASC", mysql_real_escape_string($category->ID));
					}

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

						<?php /*?><div class="options">
							<ul>
								<li<?php echo ($_SESSION['Category'][$category->ID]['Layout'] == 'table') ? ' class="options-primary"' : ''; ?>><a href="products.php?layout=table&amp;cat=<?php echo $category->ID; ?>&amp;nm=<?php echo urlencode(!empty($category->MetaTitle) ? $category->MetaTitle : $category->Name); ?>">List View</a></li>
								<li<?php echo ($_SESSION['Category'][$category->ID]['Layout'] == 'grid') ? ' class="options-primary"' : ''; ?>><a href="products.php?layout=grid&amp;cat=<?php echo $category->ID; ?>&amp;nm=<?php echo urlencode(!empty($category->MetaTitle) ? $category->MetaTitle : $category->Name); ?>">Grid View</a></li>
							</ul>
							<div class="clear"></div>
						</div><?php */?>
								
						<?php
					}
					
					switch($_SESSION['Category'][$category->ID]['Layout']) {
						case 'table':
							$table = new DataTable('products');
							$table->SetSQL($sql);
							$table->SetTotalRowSQL($sqlTotalRows);
							$table->SetMaxRows(15);
							$table->SetOrderBy($order);
							$table->Finalise();
							$table->ExecuteSQL();

							if($table->Table->TotalRows > 0) {
								?>

								<div id="products-list-partial" <?php echo ($form->GetValue('showall') == 'N') ? 'style="display: none;"' : ''; ?>>

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
											
											$subCategory = $category;

											$rowClass = '';
											if($subCategory->ShowBuyButton == 'Y'){
												$rowClass .= ' list-show-buy-button';
											}											
											include('../lib/templates/productLine_wspl.php');											
											$table->Next();
										}
										?>

									</table>

									<?php
									$table->DisplayNavigation();
									?>

								</div>

								<?php
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
											
											$subCategory = $category;

											$gridClass = '';
											if($subCategory->CategoryMode == 'Box Rate'){
												$gridClass .= ' grid-boxrate-mode';
											}
											if($subCategory->ShowBuyButton == 'Y'){
												$gridClass .= ' grid-show-buy-button';
											}
											
											$maxLength = 10;
											
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

				new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_category SELECT pc3.Category_ID, pc3.Search_Term_Title AS Category_Title, pc3.Meta_Title, pc3.Category_Thumb, pc3.Sequence FROM product_categories AS pc INNER JOIN product_category_link AS pcl ON pcl.Category_ID=pc.Category_ID INNER JOIN product_categories AS pc2 ON pc2.Category_ID=pcl.Linked_Category_ID INNER JOIN product_categories AS pc3 ON pc2.Category_ID=pc3.Category_Parent_ID WHERE pc.Category_ID=%d AND pc2.Is_Active='Y' AND pc3.Is_Active='Y'", mysql_real_escape_string($category->ID)));

				$children = new DataQuery(sprintf("SELECT Category_ID, Category_Title, Meta_Title, Category_Thumb, Sequence FROM product_categories WHERE Category_Parent_ID=%d AND Is_Active='Y' UNION SELECT Category_ID, Category_Title, Meta_Title, Category_Thumb, Sequence FROM temp_category ORDER BY %s ASC", mysql_real_escape_string($category->ID), !empty($category->CategoryOrder) ? $category->CategoryOrder : 'Category_Title'));
				?>

				<a id="productCatalogue"></a>

				<?php
				$subCategory = new Category();

				if($category->ShowImages == 'Y') {
					if($children->TotalRows > 0) {
						?>
						
						<div class="categoryGrid">
						<table width="100%" cellpadding="5" cellspacing="0"><tr>
							<?php
							$columncount=0;
							while($children->Row) {
								$subCategory->ID = $children->Row['Category_ID'];
								$subCategory->Name = $children->Row['Category_Title'];
								$subCategory->MetaTitle = $children->Row['Meta_Title'];
								
								$url = $subCategory->GetUrl();
								$columncount++;
								?>								
                                <td width="45%">
								<div class="categoryGridBox">
									<a href="<?php echo  $GLOBALS['MOBILE_LINK'] . $url ;?>" title="<?php echo htmlspecialchars($subCategory->MetaTitle); ?>"><img src="<?php echo (!empty($children->Row['Category_Thumb']) && file_exists($GLOBALS['CATEGORY_IMAGES_DIR_FS'].$children->Row['Category_Thumb'])) ? sprintf('%s%s', $GLOBALS['CATEGORY_IMAGES_DIR_WS'], $children->Row['Category_Thumb']) : './images/template/image_coming_soon_2.jpg'; ?>" alt="<?php echo htmlspecialchars($subCategory->MetaTitle); ?>" width="80%" /></a><br />
									<a href="<?php echo  $GLOBALS['MOBILE_LINK'] . $url;?>" title="<?php echo htmlspecialchars($subCategory->MetaTitle); ?>" style="width:100%;"><strong><?php echo htmlspecialchars(str_replace('[SEARCHTERM]', $category->SearchTerm, $children->Row['Category_Title'])); ?></strong></a>
								</div>
								</td>
								<?php
								$children->Next();
								if($columncount==2){
									echo'</tr><tr>';
									$columncount=0;
								}
							}
							?>
                            </tr></table>												
						</div>
						
						<?php
					}
				} else {
					if($children->TotalRows > 0){
						$childrenArr = array();

						while($children->Row){
							$subCategory->ID = $children->Row['Category_ID'];
							$subCategory->Name = $children->Row['Category_Title'];
							$subCategory->MetaTitle = $children->Row['Meta_Title'];

							$url = $subCategory->GetUrl();

							$childrenArr[] = sprintf('<a href="%s" title="%s">%s</a>', $url, htmlspecialchars($children->Row['Meta_Title']), htmlspecialchars(str_replace('[SEARCHTERM]', $category->SearchTerm, $children->Row['Category_Title'])));
							$children->Next();
						}

						$tempColumn = 0;
						$rows = 0;
						$columnArr = array();
						$col = 0;
						$count = 0;

						for($i=0;$i < count($childrenArr); $i++) {
							if($count >= (count($childrenArr) / $category->ColumnCountText)) {
								$count = 0;
							}

							$columnArr[$col][] = $childrenArr[$i];
							$count++;
						}

						echo "<table class=\"productCategories clear\">";
						for($i=0;$i < count($columnArr[0]); $i++) {
							echo "<tr>";

							for($j=0;$j < $category->ColumnCountText; $j++) {
								if(isset($columnArr[$j][$i])) {
									$link = $columnArr[$j][$i];
									$link1=str_replace("/","./",$link);
									$link2=str_replace("<./a>","</a>",$link1);
								} else {
									$link2 = '&nbsp;';
								}

								echo sprintf("<td style=\"text-align: left;\">%s</td>", $link2);
							}
							echo "</tr>";
						}
						echo "</table>";
					}
				}

				$children->Disconnect();
				
				echo $descriptionFooter;

				$subCategory = $category;

				$gridClass = '';
				if($subCategory->CategoryMode == 'Box Rate'){
					$gridClass .= ' grid-boxrate-mode';
				}
				if($subCategory->ShowBuyButton != 'N'){
					$gridClass .= ' grid-show-buy-button';
				}
/**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**//**/

				$descriptionHeader = (substr(trim($category->Description), 0, 3) == '<p>') ? $category->Description : '<p>' . $category->Description . '</p>';
				$descriptionFooter = (substr(trim($category->DescriptionSecondary), 0, 3) == '<p>') ? $category->DescriptionSecondary : '<p>' . $category->DescriptionSecondary . '</p>';
				
				$description = strip_tags($descriptionFooter);
				
				if(!empty($description)) {
					$descriptionHeader .= '<p><a href="#More">Read more</a></p>';
					$descriptionFooter = '<a id="More"></a><h2>' . htmlspecialchars($category->Name) . ' Continued</h2>' . $descriptionFooter;
				}
				
				$var=$descriptionHeader;
				$descriptionHeaderFinal=str_replace('img','imgs',$var);
				if($category->ShowImage == 'Y') {
					if(!empty($category->Large->FileName) && file_exists($GLOBALS['CATEGORY_IMAGES_DIR_FS'].$category->Large->FileName)){
						echo sprintf("<div class=\"categoryDescription\"><img src=\"%s%s\" class=\"left\" alt=\"%s\" />%s</div>", $GLOBALS['CATEGORY_IMAGES_DIR_WS'], $category->Large->FileName, htmlspecialchars($category->Name), $descriptionHeaderFinal);
					} elseif(!empty($category->Description) && ($category->ID != 0)){
						echo sprintf("<div class=\"categoryDescription\"><img src=\"images/template/image_coming_soon_1.jpg\" class=\"left\" alt=\"\" />%s</div>", $descriptionHeaderFinal);
					}
					 if(!empty($category->Description) && ($category->ID == 0)){
						echo sprintf("<div class=\"categoryDescription\">%s</div>", $descriptionHeaderFinal);
					}
				} 
				else {
					echo sprintf("<div class=\"categoryDescription\">%s</div>", $descriptionHeaderFinal);
				}
				
				echo '<div class="clear"></div>';

				if(count($filter->Filter) == 0) {
					if($session->Customer->Contact->IsTradeAccount == 'N') {
						if($category->ShowBestBuys == 'Y') {
							$subProduct = null;
							$subCategory = $category;
							
							if($category->ProductOffer->ID == 0) {
		            			$category->ProductOffer->ID = checkCategories($category->ID);
							}

							if($category->ProductOffer->ID > 0) {
								$category->ProductOffer->Get();
								
								$subProduct = $category->ProductOffer;
							}
							
							//include('../lib/templates/best_wspl.php');
						}
					}
				}
?>
				
				<?php include('../lib/templates/back_wspl.php'); ?>
</div>
</div>
<?php include("ui/footer.php");?>
<?php include('../lib/common/appFooter.php'); ?>