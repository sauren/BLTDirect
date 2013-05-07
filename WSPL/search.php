<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable_mobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecFilter.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SearchFailure.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

$useAjaxFilter = !param('filter', false);

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('search', 'Search', 'text', '', 'anything', 0, 255, false, 'style="width: 50%;"');

$searchString = '';
$searchFullTextSearch = array();
$searchWhereItems = array();

if(param('search')) {
	if($form->Validate()) {
		$searchString = $form->GetValue('search');
		$searchString = strtolower($searchString);
		$searchString = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $searchString));

		$data = new DataQuery(sprintf("SELECT psvr.valueRewrite, psv.Value FROM product_specification_value_rewrite AS psvr INNER JOIN product_specification_value AS psv ON psv.Value_ID=psvr.valueId WHERE '%s' LIKE CONCAT('%%', psvr.valueRewrite, '%%') GROUP BY psv.Value_ID", $searchString));
		while($data->Row) {
			$searchString = str_ireplace($data->Row['valueRewrite'], $data->Row['Value'], $searchString);

			$data->Next();
		}
		$data->Disconnect();

		$searchStringReplace = $searchString;

		$data = new DataQuery(sprintf("SELECT DISTINCT unit FROM product_specification_group_unit"));
		while($data->Row) {
			$groups = array();

			$data2 = new DataQuery(sprintf("SELECT psg.Name, psg.Units FROM product_specification_group_unit AS psgu INNER JOIN product_specification_group AS psg ON psg.Group_ID=psgu.groupId WHERE psgu.unit LIKE '%s'", $data->Row['unit']));
			while($data2->Row) {
				$groups[$data2->Row['Name']] = $data2->Row['Units'];

				$data2->Next();
			}
			$data2->Disconnect();

			preg_match_all(sprintf('/([^\s]+)(\s?)%s(\s|$)+/i', $data->Row['unit']), $searchString, $matches);

			foreach($matches[0] as $match) {
				$searchStringReplace = str_replace(trim($match), '', $searchStringReplace);
			}

			foreach($matches[1] as $match) {
				$item = array();

				foreach($groups as $groupName=>$groupUnits) {
					$item[] = sprintf('p.Cache_Specs LIKE \'%1$s=%2$s %3$s%%\' OR p.Cache_Specs LIKE \'%%;%1$s=%2$s %3$s%%\'', mysql_real_escape_string($groupName), mysql_real_escape_string(trim($match)), mysql_real_escape_string($groupUnits));
				}

				$searchWhereItems[] = sprintf('(%s)', implode(' OR ', $item));
			}

			$data->Next();
		}
		$data->Disconnect();

		$searchString = $searchStringReplace;

		$searchItems = explode(' ', $searchString);

		foreach($searchItems as $searchItem) {
			if(!empty($searchItem)) {
				$searchFullTextSearch[] = sprintf('"%s"', mysql_real_escape_string($searchItem));
				$searchWhereItems[] = sprintf('(p.Product_Title LIKE \'%1$s%%\' OR p.Product_Title LIKE \'%% %1$s%%\' OR p.Product_Codes LIKE \'%%%1$s%%\' OR p.Product_ID LIKE \'%1$s%%\' OR pb.Barcode LIKE \'%1$s%%\' OR p.Cache_Specs LIKE \'%%%1$s%%\')', mysql_real_escape_string($searchItem));
			}
		}

		$replacements = array();

		foreach($searchItems as $searchItem) {
			$data = new DataQuery(sprintf("SELECT term, replacement FROM search_substitute WHERE '%s' LIKE CONCAT(term, '%%')", mysql_real_escape_string($searchItem)));
			while($data->Row) {
				$replacements[] = str_replace($searchItem, $data->Row['replacement'], $searchString);
				
				$data->Next();
			}
			$data->Disconnect();
		}

		$substitutes = array();

		$data = new DataQuery(sprintf("SELECT 'product' AS type, p.Product_ID AS id, p.Product_Title AS title FROM search_keyword AS sk INNER JOIN search_keyword_product AS skp ON skp.searchKeywordId=sk.id INNER JOIN product AS p ON p.Product_ID=skp.productId WHERE '%s' LIKE CONCAT(term, '%%') AND p.Is_Active='Y' and p.Is_Demo_Product='N' UNION SELECT 'category' AS type, c.Category_ID AS id, c.Category_Title AS title FROM search_keyword AS sk INNER JOIN search_keyword_category AS skc ON skc.searchKeywordId=sk.id INNER JOIN product_categories AS c ON c.Category_ID=skc.categoryId WHERE '%s' LIKE CONCAT(term, '%%') ORDER BY title ASC LIMIT 10", mysql_real_escape_string($searchString), mysql_real_escape_string($searchString)));
		while($data->Row) {
			$substitutes[] = $data->Row;
			
			$data->Next();
		}
		$data->Disconnect();
	}
}

$filter = new ProductSpecFilter();
$filter->SearchString = htmlentities($form->GetValue('search'));

$isAdvanced = false;

if(!$useAjaxFilter) {
	$filter->Build();

	$isAdvanced = ((count($filter->Filter) > 0) || (count($filter->Category) > 0) || (strtolower(param('show')) == 'advanced')) ? true : false;
}

$sql = '';

$productPrices = array();
$productOffers = array();

if((count($filter->Filter) > 0) || (count($filter->Category) > 0) || !empty($searchString) || !empty($searchWhereItems)) {
	$sqlSelect = sprintf('SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Discontinued_Show_Price, p.Product_Codes, p.Cache_Specs_Primary, p.Meta_Title, p.SKU, p.Order_Min, p.Average_Despatch, p.CacheBestCost, p.CacheRecentCost, pi.Image_Thumb, MIN(ws.Backorder_Expected_On) AS Backorder_Expected_On, MATCH(p.Product_Title, p.Product_Codes, p.Product_Description, p.Meta_Title, p.Meta_Description, p.Meta_Keywords, p.Cache_Specs) AGAINST(\'%s\') AS Score, IF(p.Position_Orders=0, 9999999, p.Position_Orders) AS Position ', implode(' ', $searchFullTextSearch));
	$sqlFrom = 'LEFT JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID ';
	$sqlWhere = 'WHERE p.Is_Active=\'Y\' AND p.Is_Demo_Product=\'N\' AND p.Discontinued=\'N\' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start=\'0000-00-00 00:00:00\' AND p.Sales_End=\'0000-00-00 00:00:00\') OR (p.Sales_Start=\'0000-00-00 00:00:00\' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End=\'0000-00-00 00:00:00\')) ';

	if(!$useAjaxFilter) {
		if(count($filter->Filter) > 0) {
			$index = 0;

			foreach($filter->Filter as $groupId=>$specValue) {
				$index++;

				$sqlFrom .= sprintf("INNER JOIN product_specification AS ps%d ON ps%d.Product_ID=p.Product_ID AND ps%d.Value_ID=%d ", mysql_real_escape_string($index), mysql_real_escape_string($index), mysql_real_escape_string($index), mysql_real_escape_string($specValue->ID));
			}
		}

		if(count($filter->Category) > 0) {
			$sqlFrom .= sprintf("INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.product_ID AND (pic.Category_ID=%s) ", mysql_real_escape_string(implode(' OR pic.Category_ID=', $filter->Categories)));
		}
	}

	if(!empty($searchWhereItems)) {
		$sqlWhere .= sprintf(' AND (%s)', implode(' AND ', $searchWhereItems));
	}

	$sql = sprintf("%sFROM product AS p LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Backordered='Y' LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='Y' AND pi.Is_Primary='Y' %s%s GROUP BY p.Product_ID", $sqlSelect, $sqlFrom, $sqlWhere);
	$sqlTotalRows = sprintf("SELECT COUNT(*) AS TotalRows FROM product AS p %s%s", $sqlFrom, $sqlWhere);

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_prices SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity, pp.Price_Starts_On FROM product AS p INNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() %s%s", $sqlFrom, $sqlWhere));
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_offers SELECT p.Product_ID, po.Price_Offer, po.Offer_Start_On FROM product AS p INNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) %s%s", $sqlFrom, $sqlWhere));

	$data = new DataQuery(sprintf("SELECT * FROM temp_prices ORDER BY Price_Starts_On ASC"));
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

	$data = new DataQuery(sprintf("SELECT * FROM temp_offers ORDER BY Offer_Start_On ASC"));
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
}

$disableFilters = (count($filter->Filter) >= Setting::GetValue('spec_filter_limit')) ? true : false;

if(!empty($sql)) {
	$table = new DataTable('products');
	$table->SetSQL($sql);
	$table->SetTotalRowSQL($sqlTotalRows);
	$table->SetMaxRows(15);
	$table->SetOrderBy('Position');
	$table->Order = 'ASC';
	$table->Finalise();
	$table->ExecuteSQL();

	if($table->TotalRows == 0) {
		if(!empty($searchString)) {
			$searchFailure = new SearchFailure();

			if($searchFailure->getByTerm($searchString, date('Y-m-d'))) {
				$searchFailure->increment();
			} else {
				$searchFailure->frequency = 1;
				$searchFailure->add();
			}
		}
	}
}
include("ui/nav.php");
include("ui/search.php");
?>
<script type="text/javascript">
		using("mootools.XHR");
	</script>
	<script type="text/javascript">
		<!--
		var showGroup = function(id) {
			var e = null;

			e = document.getElementById('SearchMore-' + id);

			if(e) {
				e.style.display = 'none';
			}

			e = document.getElementById('SearchGroup-' + id);

			if(e) {
				for(var i=0; i<e.childNodes.length; i++) {
					if(e.childNodes[i].nodeType == 1) {
						for(var j=0; j<e.childNodes[i].childNodes.length; j++) {
							if(e.childNodes[i].childNodes[j].nodeType == 1) {
								e.childNodes[i].childNodes[j].style.display = '';
							}
						}
					}
				}
			}
		}

		var cachedResponse = <?php echo $isAdvanced ? 'true' : 'false' ?>;
		var toggleAdvanced = function() {
			var search = document.getElementById('AdvancedSearchExpand');
			var image = document.getElementById('AdvancedSearchToggle');
			var text = document.getElementById('AdvancedSearchText');

			var headerLink = image.children[0];

			var expand = function() {
				search.style.display = 'block';
				text.innerHTML = 'Hide Search Refinement';
				image.className = 'Minus';
			}

			var update = function(response) {
				cachedResponse = true;
				headerLink.className = "";
				search.innerHTML = response;
				expand();
			}

			var xhr = new XHR({
				onSuccess: function(response) {
					update(response);
				},

				onFailure: function() {
					headerLink.className = "";
				}
			});

			if(search && text && image) {
				if(search.style.display == 'none') {
					if (cachedResponse) {
						expand();
					} else {
						headerLink.className = "Loading";
						xhr.send('../ignition/lib/ajax/search_ajax_mobile.php?search=<?php echo urlencode($form->GetValue('search')); ?>');
					}
				} else {
					search.style.display = 'none';
					text.innerHTML = 'Refine Your Search Further (e.g. Voltage, Wattage, Length, etc.)';
					image.className = 'Plus';
				}
			}
		}
		
		<?php if ((param('show', '') == 'advanced') || empty($searchString)) { ?>
		window.addEvent("domready", function() {
			toggleAdvanced();
		});
		<?php } ?>
		// -->
	</script>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Product Search</span></div>
<div class="maincontent">
<div class="maincontent1">
<?php /*?>					<?php
					if(!empty($sql) && ($table->TotalRows > 15)) {
						?>
						
						<div class="attention">
							<div class="attention-icon attention-icon-warning"></div>
							<div class="attention-info attention-info-warning">
								<span class="attention-info-title">Search Warning</span><br />
								Wow! Your search has returned <strong><?php echo $table->TotalRows; ?></strong> light bulbs. To help us find the correct light bulb please use our refine search filter below.<br /><br />
								<small>In the majority cases you can refine your search by voltage, wattage, cap/base or colour. Alternatively you can contact our friendly sales team on <?php echo Setting::GetValue('telephone_sales_hotline'); ?>.</small>
							</div>
						</div>
						
						<?php
					}?><?php */?>

					<?php if(!empty($replacements)) {
						$options = array();

						for($i=0; $i<count($replacements); $i++) {
							$options[] = sprintf('<a href="?search=%1$s">%1$s</a>', $replacements[$i]);
						}
						?>
						
						<div class="attention">
							<div class="attention-icon attention-icon-warning"></div>
							<div class="attention-info attention-info-warning">
								<span class="attention-info-title">Did You Mean?</span><br />
								Your search for <strong>&quot;<?php echo param('search'); ?>&quot;</strong> may not match any products. Try the following:<br /><br />
								<?php echo implode(', ', $options); ?>
							</div>
						</div>
						
						<?php
					}
					?>

					<table width="100%">
						<tr>
							<td style="vertical-align:top;" width="50%">
								<p>Enter your criteria for searching our extensive product database.<br />Searching for light bulbs online has never been easier! <b>Watch our video to see exactly how powerful our search can be!</b></p>
								<br />
								<form name="searchForm" id="searchForm" method="get" action="search.php">
									<label for="search"><strong>Search</strong></label><br />
									<p>Enter your search keywords here.</p>
									<input type="text" name="search" value="<?php echo htmlentities(param('search', '')); ?>" />
									<p><input type="submit" name="submit" value="Search" class="submit" /></p>
								</form>
							</td></tr>
                            <tr>
							<td width="100%">
								<iframe width="100%" height="250" src="//www.youtube.com/embed/JeTfl0qFw5Q?rel=0&amp;wmode=transparent"></iframe>
							</td>
						</tr>
					</table>
<?php /*?>					<div class="searchGrid">
						<div class="searchAlternative">
							Searching for a barcode?<br />
							<a href="searchbarcodes.php">Use our barcode search facility.</a>
						</div>
					</div>
					<div class="clear"></div><?php */?>
					<?php
					if(!$form->Valid){
						echo $form->GetError();
						echo '<br />';
					}
					?>
						<div class="SearchBox" id="LoadFilterSearchBox">
							<div id="AdvancedSearchToggle" class="<?php echo ($isAdvanced) ? 'Minus' : 'Plus'; ?>" onclick="javascript:toggleAdvanced();">
								<a href="javascript:void(0);"><strong><span id="AdvancedSearchText"><?php echo ($isAdvanced) ? 'Hide Search Refinement' : 'Refine Your Search Further (e.g. voltage, wattage, cap/base or colour)'; ?></span></strong></a>
							</div>
							<div id="AdvancedSearchExpand" <?php echo ($isAdvanced) ? '' : 'style="display: none;"'; ?>>
								<?php
								if((empty($sql) || (!empty($sql) && ($table->TotalRows > 0))) && (count($filter->SpecGroup) > 0)) {
									if((count($filter->SpecGroup) > 0) || (count($filter->Filter) > 0)) {
										echo '<div class="Search">';

										if(count($filter->Filter) > 0) {
											echo '<div class="Filters">';
											echo '<p><strong>Remove Filters</strong><br />Click to remove any of the below filters from your search criteria.</p>';
											echo '<div style="float: left;">';

											echo sprintf('<div class="FilterOption FilterOptionRemove"><a href="%s%s#LoadFilterSearchBox"><span class="FilterOptionCross FilterOptionCrossRemove1">Remove All Filters</span></a></div>', $_SERVER['PHP_SELF'], param('search') ? sprintf('?search=%s', htmlentities(param('search'))) : '');

											foreach($filter->Filter as $filterItem) {
												$tempFilterStr = $filter->GetFilterQueryString($filterItem->ID);

												echo sprintf('<div class="FilterOption"><a href="%s%s#LoadFilterSearchBox"><span class="FilterOptionCross FilterOptionCross4">%s: %s</span></a></div>', $_SERVER['PHP_SELF'], (strlen($tempFilterStr) > 0) ? sprintf('?%s', $tempFilterStr) : '', $filterItem->Group->Name, $filterItem->GetUnitValue());
											}

											echo '</div>';
											echo '<div class="clear"></div>';
											echo '</div>';
										}

										if(count($filter->Category) > 0) {
											echo '<div class="Filters">';
											echo '<p><strong>Remove Categories</strong><br />Click to remove any of the below categories from your search criteria.</p>';
											echo '<div style="float: left;">';

											foreach($filter->Category as $categoryItem) {
												$tempFilterStr = $filter->GetFilterQueryString(array(), $categoryItem->ID);

												echo sprintf('<div class="FilterOption"><a href="%s%s#LoadFilterSearchBox"><span class="FilterOptionCross FilterOptionCross3">%s</span></a></div>', $_SERVER['PHP_SELF'], (strlen($tempFilterStr) > 0) ? sprintf('?%s', $tempFilterStr) : '', $categoryItem->Name);
											}

											echo '</div>';
											echo '<div class="clear"></div>';
											echo '</div>';
										}

										if(count($filter->SpecGroup) > 0) {
											echo '<table class="Specs">';

											$light = true;

											for($i=0; $i<count($filter->SpecGroup); $i++) {
												$maxColumns = 2;
												$maxRows = 2;
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

												echo sprintf('<tr class="%s">', ($light) ? 'bglight' : 'bgdark');
												echo sprintf('<th style="vertical-align:top;"><strong>%s</strong>%s</th>', $filter->SpecGroup[$i]['Name'], (count($columnArr[0]) > $maxRows) ? sprintf('<span id="SearchMore-%d"><br /><a href="javascript:showGroup(%s);">show more</a></span>', $filter->SpecGroup[$i]['Group_ID'], $filter->SpecGroup[$i]['Group_ID']) : '');
												echo '<td>';

												echo sprintf('<table class="SpecValues" id="SearchGroup-%d">', $filter->SpecGroup[$i]['Group_ID']);

												for($j=0; $j<count($columnArr[0]); $j++) {
													echo sprintf('<tr %s>', ($j < $maxRows) ? '' : 'style="display: none;"');

													for($k=0; $k<$maxColumns; $k++) {
														if(isset($columnArr[$k][$j])) {
															if($disableFilters) {
																echo sprintf('<td style="width:%s%%; vertical-align:top;"><span>%s (%d)</span></td>', number_format(100/$maxColumns, 2, '.', ''), $columnArr[$k][$j]['UnitValue'], $columnArr[$k][$j]['Products']);
															} else {
																echo sprintf('<td style="width:%s%%; vertical-align:top;"><a href="%s?%sfilter=%d#LoadFilterSearchBox">%s</a> (%d)</td>', number_format(100/$maxColumns, 2, '.', ''), $_SERVER['PHP_SELF'], (strlen($filter->FilterQueryString) > 0) ? sprintf('%s&amp;', $filter->FilterQueryString) : '', $columnArr[$k][$j]['Value_ID'], $columnArr[$k][$j]['UnitValue'], $columnArr[$k][$j]['Products']);
															}
														} else {
															echo '<td>&nbsp;</td>';
														}
													}

													echo '</tr>';
												}

												echo '</table>';

												echo '</td>';
												echo '</tr>';

												$light = !$light;
											}

											echo '</table>';
										}

										echo '</div>';
									}
								}
								?>

							</div>
					</div>

					<?php
					if(!empty($sql)) {
						?>

						<div class="SearchBox">
							<div id="SearchInformation">
								<p>There are <strong><?php echo $table->TotalRows; ?></strong> products matching your search criteria.</p>
							</div>
						</div>

						<?php
						if(!empty($substitutes)) {
							?>

							<h2>Have you considered?</h2>

						<table class="list">

								<?php
								foreach($substitutes as $substitute) {
									?>

									<tr>
										<td>
											<div class="product-detail">
												<?php
												switch($substitute['type']) {
													case 'product':
														echo sprintf('%s: <a href="product.php?pid=%d"><span class="colour-orange">%s</span></a>', ucwords($substitute['type']), $substitute['id'], $substitute['title']);
														break;

													case 'category':
														echo sprintf('%s: <a href="products.php?cat=%d"><span class="colour-orange">%s</span></a>', ucwords($substitute['type']), $substitute['id'], $substitute['title']);
														break;
												}
												?>

											</div>
										</td>
									</tr>
									
									<?php
								}
								?>

							</table>
							<br />

							<?php
						}

						if(is_numeric($filter->SearchString)) {
							$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Product_Codes, p.Cache_Specs_Primary, p.Meta_Title, p.SKU, pi.Image_Thumb, p.Order_Min, p.Average_Despatch, p.CacheBestCost, p.CacheRecentCost, MIN(ws.Backorder_Expected_On) AS Backorder_Expected_On FROM product AS p LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Backordered='Y' LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='Y' AND pi.Is_Primary='Y' WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Product_ID=%s GROUP BY p.Product_ID", mysql_real_escape_string($filter->SearchString)));
							if($data->TotalRows > 0) {
								?>
								<table class="list list-highlight">
									<?php
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
									$subProduct->AverageDespatch = $data->Row['Average_Despatch'];
									$subProduct->PriceRRP = 0;
									$subProduct->PriceOurs = 0;
									$subProduct->PriceOffer = 0;
									$subProduct->Discontinued = $data->Row['Discontinued'];
									$subProduct->DiscontinuedShowPrice = $table->Table->Row['Discontinued_Show_Price'];
									$subProduct->CacheBestCost = $data->Row['CacheBestCost'];
									$subProduct->CacheRecentCost = $data->Row['CacheRecentCost'];

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
									?>

								</table>
								<br />

								<?php
							}
							$data->Disconnect();
						}

						if($table->Table->TotalRows > 0) {
							?>

							<table class="list">

								<?php
								while($table->Table->Row){
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
					}
					?>
				<?php include('../lib/templates/back_wspl.php'); ?>
</div>
</div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>