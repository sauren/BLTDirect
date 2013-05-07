<?php
require_once('../classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecFilter.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

$cacheFile = "cache/search_filter.html";

if ((!isset($_REQUEST['search'])  || !$_REQUEST['search']) && file_exists($GLOBALS["DIR_WS_ROOT"].$cacheFile) && filemtime($GLOBALS["DIR_WS_ROOT"].$cacheFile) > (time()-(60*60))) {
	readfile($GLOBALS["DIR_WS_ROOT"].$cacheFile);
	exit;
}

$searchPage = "search.php";

$form = new Form($searchPage, 'GET');
$form->AddField('search', 'Search', 'hidden', '', 'anything', 0, 255, false);

$filter = new ProductSpecFilter();
$filter->SearchString = htmlentities($form->GetValue('search'));
$filter->Build();

$isAdvanced = ((count($filter->Filter) > 0) || (count($filter->Category) > 0) || (isset($_REQUEST['show']) && (strtolower($_REQUEST['show']) == 'advanced'))) ? true : false;

if (!isset($_REQUEST['search']) || !$_REQUEST['search']) {
	ob_start();
}
?>

								<?php
								if((count($filter->SpecGroup) > 0) || (count($filter->Filter) > 0)) {
									echo '<div class="Search">';

									if(count($filter->Filter) > 0) {
										echo '<div class="Filters">';
										echo '<p><strong>Remove Filters</strong><br />Click to remove any of the below filters from your search criteria.</p>';
										echo '<div style="float: left;">';

										echo sprintf('<div class="FilterOption FilterOptionRemove"><a href="%s%s#LoadFilterSearchBox"><div class="FilterOptionCross FilterOptionCrossRemove1">Remove All Filters</div></a></div>', $searchPage, isset($_REQUEST['search']) ? sprintf('?search=%s', htmlentities($_REQUEST['search'])) : '');

										foreach($filter->Filter as $filterItem) {
											$tempFilterStr = $filter->GetFilterQueryString($filterItem->ID);

											echo sprintf('<div class="FilterOption"><a href="%s%s#LoadFilterSearchBox"><div class="FilterOptionCross FilterOptionCross4">%s: %s</div></a></div>', $searchPage, (strlen($tempFilterStr) > 0) ? sprintf('?%s', $tempFilterStr) : '', $filterItem->Group->Name, $filterItem->GetUnitValue());
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

											echo sprintf('<div class="FilterOption"><a href="%s%s#LoadFilterSearchBox"><div class="FilterOptionCross FilterOptionCross3">%s</div></a></div>', $searchPage, (strlen($tempFilterStr) > 0) ? sprintf('?%s', $tempFilterStr) : '', $categoryItem->Name);
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
											$maxRows = 1;
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
											echo sprintf('<th valign="top"><strong>%s</strong>%s</th>', $filter->SpecGroup[$i]['Name'], (count($columnArr[0]) > $maxRows) ? sprintf(' <span id="SearchMore-%d">(<a href="javascript:showGroup(%s);">show more</a>)</span>', $filter->SpecGroup[$i]['Group_ID'], $filter->SpecGroup[$i]['Group_ID']) : '');
											echo '<td>';

											echo sprintf('<table class="SpecValues" id="SearchGroup-%d">', $filter->SpecGroup[$i]['Group_ID']);

											for($j=0; $j<count($columnArr[0]); $j++) {
												echo sprintf('<tr %s>', ($j < $maxRows) ? '' : 'style="display: none;"');

												for($k=0; $k<$maxColumns; $k++) {
													if(isset($columnArr[$k][$j])) {
														echo sprintf('<td width="%s%%" valign="top"><a href="%s?%sfilter=%d#LoadFilterSearchBox">%s</a> (%d)</td>', number_format(100/$maxColumns, 2, '.', ''), $searchPage, (strlen($filter->FilterQueryString) > 0) ? sprintf('%s&', $filter->FilterQueryString) : '', $columnArr[$k][$j]['Value_ID'], $columnArr[$k][$j]['UnitValue'], $columnArr[$k][$j]['Products']);
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
								?>
<?php
if (!isset($_REQUEST['search']) || !$_REQUEST['search']) {
	$output = ob_get_clean();
	file_put_contents($GLOBALS["DIR_WS_ROOT"].$cacheFile, $output);
	echo $output;
}