<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable_mobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$groups = array();
$groups[223] = false;
$groups[224] = false;
$groups[221] = false;
$groups[41] = false;

$zeroMatch = 'Sorry, no matches found.';

$specificationGroups = array();
$specificationValues = array();

foreach($groups as $groupId=>$groupData) {
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM product_specification_combine WHERE productSpecificationGroupId=%d", mysql_real_escape_string($groupId)));	
	if($data->Row['count'] > 0) {
		$groups[$groupId] = true;
	}
	$data->Disconnect();
}

$uncombinedGroups = array();

foreach($groups as $groupId=>$groupData) {
	$uncombinedGroups[] = $groupId;
}

$data = new DataQuery(sprintf("SELECT Group_ID, Name, Data_Type FROM product_specification_group WHERE Group_ID IN (%s) ORDER BY Name ASC", implode(', ', $uncombinedGroups)));
while($data->Row) {
	$specificationGroups[$data->Row['Group_ID']] = $data->Row;
	$specificationValues[$data->Row['Group_ID']] = array();
	
	if(!$groups[$data->Row['Group_ID']]) {
		$data2 = new DataQuery(sprintf("SELECT psv.Group_ID, psv.Value_ID, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification_value AS psv INNER JOIN product_specification AS ps ON ps.Value_ID=psv.Value_ID INNER JOIN product_specification_group AS psg ON psg.Group_ID=psv.Group_ID WHERE psv.Group_ID=%d AND psv.Hide='N' GROUP BY psv.Value_ID ORDER BY Value ASC", mysql_real_escape_string($data->Row['Group_ID'])));
		while($data2->Row) {
			$specificationValues[$data->Row['Group_ID']][] = $data2->Row;
			
			$data2->Next();
		}
		$data2->Disconnect();
	} else {
		$data2 = new DataQuery(sprintf("SELECT id, name FROM product_specification_combine WHERE productSpecificationGroupId=%d ORDER BY name ASC", mysql_real_escape_string($data->Row['Group_ID'])));
		while($data2->Row) {
			$specificationValues[$data->Row['Group_ID']][] = $data2->Row;
			
			$data2->Next();
		}
		$data2->Disconnect();
	}

	if($data->Row['Data_Type'] == 'numeric') {
		$sortArray = array();
		$cacheArray = $specificationValues[$data->Row['Group_ID']];
		
		for($j=0; $j<count($specificationValues[$data->Row['Group_ID']]); $j++) {
			$sortArray[$specificationValues[$data->Row['Group_ID']][$j]['Value_ID']] = $specificationValues[$data->Row['Group_ID']][$j]['Value'];
		}

		asort($sortArray, SORT_NUMERIC);
		
		$specificationValues[$data->Row['Group_ID']] = array();
		
		foreach($sortArray as $valueId=>$value) {
			for($j=0; $j<count($cacheArray); $j++) {
				if($cacheArray[$j]['Value_ID'] == $valueId) {
					$specificationValues[$data->Row['Group_ID']][] = $cacheArray[$j];
					break;
				}
			}
		}
	}
	
	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

foreach($specificationGroups as $group) {
	$form->AddField('group_' . $group['Group_ID'], $group['Name'], 'select', '0', 'numeric_unsigned', 1, 11, false, 'onchange="getRightFinderResults(this);"');	
	$form->AddOption('group_' . $group['Group_ID'], '0', '');
	
	foreach($specificationValues[$group['Group_ID']] as $value) {
		if(!$groups[$group['Group_ID']]) {
			$form->AddOption('group_' . $group['Group_ID'], $value['Value_ID'], $value['UnitValue']);
		} else {
			$form->AddOption('group_' . $group['Group_ID'], $value['id'], $value['name']);
		}
	}
	
	if(param('finder_group_'.$group['Group_ID'])) {
		$form->SetValue('group_' . $group['Group_ID'], param('finder_group_' . $group['Group_ID']));
	}
}

$sql = '';
$sqlTotalRows = '';

$productPrices = array();
$productOffers = array();

$totalResults = 0;

if(param('confirm')) {
	$sqlSelect = '';
	$sqlFrom = '';
	$sqlWhere = '';

	$index = 0;
	
	foreach($specificationGroups as $group) {
		if($form->GetValue('group_' . $group['Group_ID']) > 0) {
			$index++;
			
			if(!$groups[$group['Group_ID']]) {
				$sqlFrom .= sprintf('INNER JOIN product_specification AS ps%1$d ON ps%1$d.Product_ID=p.Product_ID AND ps%1$d.Value_ID=%2$d ', $index, $form->GetValue('group_' . $group['Group_ID']));
			} else {
				$sqlFrom .= sprintf('INNER JOIN product_specification AS ps%1$d ON ps%1$d.Product_ID=p.Product_ID INNER JOIN product_specification_combine_value AS pscv%1$d ON pscv%1$d.productSpecificationValueId=ps%1$d.Value_ID AND pscv%1$d.productSpecificationCombineId=%2$d ', $index, $form->GetValue('group_' . $group['Group_ID']));
			}
		}
	}

	$sql = sprintf("SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Discontinued_Show_Price, p.Product_Codes, p.Cache_Specs_Primary, p.Meta_Title, p.SKU, p.Order_Min, p.Average_Despatch, p.CacheBestCost, p.CacheRecentCost, pi.Image_Thumb, MIN(ws.Backorder_Expected_On) AS Backorder_Expected_On %s FROM product AS p LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Backordered='Y' LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='Y' AND pi.Is_Primary='Y' %s WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' AND p.Integration_ID=0 AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) %s GROUP BY p.Product_ID", $sqlSelect, $sqlFrom, $sqlWhere);
	$sqlTotalRows = sprintf("SELECT COUNT(*) AS TotalRows FROM product AS p %s WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' AND p.Integration_ID=0 AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) %s", $sqlFrom, $sqlWhere);

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_prices SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity, pp.Price_Starts_On FROM product AS p INNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() %s WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) %s", mysql_real_escape_string($sqlFrom), mysql_real_escape_string($sqlWhere)));
	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_offers SELECT p.Product_ID, po.Price_Offer, po.Offer_Start_On FROM product AS p INNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) %s WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) %s", mysql_real_escape_string($sqlFrom), mysql_real_escape_string($sqlWhere)));

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
	
	$data = new DataQuery($sqlTotalRows);
	$totalResults = $data->Row['TotalRows'];
	$data->Disconnect();
}

$table = new DataTable('products');
$table->SetSQL($sql);
$table->SetExtractVars();
$table->SetTotalRowSQL($sqlTotalRows);
$table->SetMaxRows(15);
$table->SetOrderBy('Product_Title');
$table->Order = 'ASC';

if(!empty($sql)) {
	$table->Finalise();
	$table->ExecuteSQL();
}
include("ui/nav.php");
include("ui/search.php");
?>
<script type="text/javascript">
	using("mootools.XHR");
</script>
	<?php
echo '<script type="text/javascript">';
echo 'var rightFinderGroups = new Array();';
	foreach($specificationGroups as $group) {
		echo sprintf('rightFinderGroups.push(\'bulbFinderSelect_%d\');', $group['Group_ID']);
		echo sprintf('rightFinderGroups.push(%s);', ($groups[$group['Group_ID']]) ? 'true' : 'false');
	}
    echo '</script>';
	?>

<!--<script type="text/javascript" src="js/right.js"></script>
-->    
<script language="javascript" type="text/javascript">
function getRightFinderResults(obj) {
	var values = new Array();
	var combinations = new Array();
	var filterElement = null;
	var container = jQuery(obj).closest('.bulbFinderForm');
	for(var i=0; i<rightFinderGroups.length; i=i+2) {
		filterElement = jQuery('.' + rightFinderGroups[i] + ' select', container);
		if(filterElement) {
			if(filterElement.val() > 0) {
				if(!rightFinderGroups[i+1]) {
					values.push(filterElement.val());
				} else {
					combinations.push(filterElement.val());
				}
			}
		}
	}

	if((values.length > 0) || (combinations.length > 0)) {
		jQuery(".results", container).hide();
		jQuery(".loader", container).show();
		jQuery.get(
			'../ignition/lib/util/loadBulbFinder.php?values=' + values.join(',') + '&combinations=' + combinations.join(','), function(results){
			updateRightFinderResults(results, container);
		});
	} else {
		updateRightFinderResults(0, container);
	}
}

function updateRightFinderResults(matches, container) {
	var results = jQuery('.right-results', container);
	jQuery(".loader", container).hide();
	jQuery(".results", container).show();

	if(results.length) {
		results.show();	
	}
	
	var resultsMatches = jQuery('.right-results-matches', container);
	
	if(resultsMatches.length) {
		if(matches.total && matches.total > 0) {
			resultsMatches.html(matches.total + ' matches');
		} else {
			resultsMatches.html('<?php echo $zeroMatch; ?>');
		}
	}
	var resultsShow = jQuery('.right-results-show', container);
	
	if(resultsShow.length) {
		if(matches.total && matches.total > 0){
			resultsShow.show();
		} else {
			resultsShow.hide();
		}
	}
	
	if(!matches.total || (matches.combine.length == 0 && matches.values.length==0)){
		jQuery('.bulbFinderForm select option', container).show();
	} else {
		// reset values
		jQuery('select', container).each(function(i){
			var select = jQuery(select);

			jQuery('option', jQuery(this)).each(function(j){
				var opt = jQuery(this);
				if(!opt.attr('data-label')){
					opt.attr('data-label', opt.text());
				}
				if(opt.text() != ''){
					//opt.text(opt.attr('data-label') + ' (0)');
					opt.hide();
				}
			});
		});

		// update select boxes
		for(var i in matches.values){
			// get the select using group id
			// go through each option set all numbers to 0
			var match = matches.values[i];
			var select = jQuery('.bulbFinderSelect_' + match['Group_ID'] + ' select', container);
			if(select.length){
				var opt = jQuery('option[value="'+match['Value_ID']+'"]', select);
				//opt.text(opt.attr('data-label') + ' ('+match['Total']+')');
				opt.show();
			}
		}
		for(var i in matches.combine){
			// get the select using group id
			// go through each option set all numbers to 0
			var match = matches.combine[i];
			var select = jQuery('.bulbFinderSelect_' + match['Group_ID'] + ' select', container);
			if(select.length){
				var opt = jQuery('option[value="'+match['Value_ID']+'"]', select);
				//opt.text(opt.attr('data-label') + ' ('+match['Total']+')');
				opt.show();
			}
		}
	}
}
</script>
<script language="javascript" type="text/javascript">
		function getFinderResults() {
			var xhr = new XHR({
				onSuccess: function(response) {
					updateFinderResults(response);
				},

				onFailure: function() {
					updateFinderResults(0);
				}
			});

			var values = new Array();
			var combinations = new Array();
			
			var filterElement = null;

			for(var i=0; i<finderGroups.length; i=i+2) {
				filterElement = document.getElementById(finderGroups[i]);

				if(filterElement) {
					if(filterElement.value > 0) {
						if(!finderGroups[i+1]) {
							values.push(filterElement.value);
						} else {
							combinations.push(filterElement.value);
						}
					}
				}
			}

			if((values.length > 0) || (combinations.length > 0)) {
				xhr.send('../ignition/lib/util/loadBulbFinder.php?values=' + values.join(',') + '&combinations=' + combinations.join(','));
			} else {
				updateFinderResults(0);
			}
		}
		
		function updateFinderResults(matches) {
			var results = document.getElementById('results');
			
			if(results) {
				results.style.display = 'table-row';	
			}
			
			var resultsMatches = document.getElementById('results-matches');
			
			if(resultsMatches) {
				if(matches > 0) {
					resultsMatches.innerHTML = matches + ' matches';
				} else {
					resultsMatches.innerHTML = '<?php echo $zeroMatch; ?>';
				}
			}
			
			var resultsShow = document.getElementById('results-show');
		
			if(resultsShow) {
				resultsShow.style.display = (matches > 0) ? '' : 'none';	
			}
		}
	</script>
    					<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Bulb Finder</span></div>
<div class="maincontent">
<div class="maincontent1">

					<p>Use our simple bulb finder below to find your bulb.</p>
					<?php //include('../lib/templates/bought_wspl.php');
					 ?>
					<?php
					echo $form->Open();
					echo $form->GetHTML('confirm');
					?>
					
					<table style="width:100%;" class="form bulbFinderForm">
						<tr>
							<th colspan="2">Bulb Details</th>
						</tr>
					
						<?php
						foreach($groups as $groupId=>$groupData) {
							?>
							
							<tr>
								<td style="width:28%; vertical-align:top;"><?php echo $form->GetLabel('group_' . $groupId); ?></td>
								<td style="width:72%; vertical-align:top;" class="bulbFinderSelect_<?php echo $groupId; ?>"><?php echo $form->GetHtml('group_' . $groupId); ?></td>
							</tr>
						
							<?php
						}
						?>
						
						<tr <?php echo empty($sql) ? 'style="display: none;"' : ''; ?> class="right-results">
							<td style="width:28%; vertical-align:top;"><strong>Results</strong></td>
							<td style="width:72%; vertical-align:top;">
								
								<div class="loader"></div>
								<div class="results">
									<div class="spacer">
										<strong>Results</strong><br />
										<span class="right-results-matches"><?php echo ($totalResults > 0) ? sprintf('%d matches', $totalResults) : $zeroMatch; ?></span>
									</div>
									

									<div class="spacer right-results-show" <?php echo ($totalResults > 0) ? '' : 'style="display: none;"'; ?>>
										<br />
										<input type="submit" class="submit" name="search" value="Show Bulbs" />
									</div>
								</div>
							</td>
						</tr>
					</table>
					<br />
					
					<?php
					echo $form->Close();
					
					if(!empty($sql)) {
						if($table->Table->TotalRows > 0) {
							?>

							<table class="list">

								<?php
								while($table->Table->Row){
									$subProduct = new Product();
									$subProduct->ID = $table->Table->Row['Product_ID'];
									$subProduct->Name = strip_tags($table->Table->Row['Product_Title']);
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
					}?>
                    </div>
                    </div>
                    <?php include("ui/footer.php");
 require_once('../lib/common/appFooter.php');