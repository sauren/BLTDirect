<?php
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
    <div class="maincontent">
<div class="maincontent1">
                <?php
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
							
							include('../lib/templates/best_products_wspl.php');
						}
					}
				}?>
</div>
</div>