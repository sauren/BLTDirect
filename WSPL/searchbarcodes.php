<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable_mobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('barcode', 'Barcode', 'text', '', 'paragraph', 1, 120, true, 'onclick="this.select();"');

$data = new DataQuery(sprintf('SELECT * FROM ip_ignore WHERE ip=%u', ip2long($_SERVER['REMOTE_ADDR'])));
if(empty($data->TotalRows)) {
	$fh = fopen($GLOBALS['DATA_DIR_FS'].'local/logs/barcodes.txt', 'a');

	if($fh) {
		fwrite($fh, $form->GetValue('barcode') . "\r\n");
		fclose($fh);
	}
}
$data->Disconnect();

$sqlSelect = '';
$sqlFrom = '';
$sqlWhere = '';
$sqlGroup = '';

if(param('confirm')) {
	if($form->Validate()) {
		$barcode = trim($form->GetValue('barcode'));

		if((strlen($barcode) < 12) || (strlen($barcode) > 14)) {
			redirectTo('search.php?search=' . $barcode);
		}

		$sqlSelect = 'SELECT p.Product_ID, p.Product_Title, p.Discontinued, p.Discontinued_Show_Price, p.Product_Codes, p.Cache_Specs_Primary, p.Meta_Title, p.SKU, p.Order_Min, p.Average_Despatch, p.CacheBestCost, p.CacheRecentCost, pi.Image_Thumb, MIN(ws.Backorder_Expected_On) AS Backorder_Expected_On ';
		$sqlFrom = 'FROM product AS p INNER JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID LEFT JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID AND ws.Is_Backordered=\'Y\' LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active=\'Y\' AND pi.Is_Primary=\'Y\' ';
		$sqlWhere = 'WHERE p.Is_Active=\'Y\' AND p.Is_Demo_Product=\'N\' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start=\'0000-00-00 00:00:00\' AND p.Sales_End=\'0000-00-00 00:00:00\') OR (p.Sales_Start=\'0000-00-00 00:00:00\' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End=\'0000-00-00 00:00:00\')) ';
		$sqlGroup = 'GROUP BY p.Product_ID ';

		$searchString = $form->GetValue('barcode');
		$searchString = $searchString;
		$searchString = trim(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $searchString));
		
		if(!empty($searchString)) {
			$sqlWhere .= sprintf('AND pb.Barcode LIKE \'%s%%\' ', $searchString);
		}
		
		$productPrices = array();
		$productOffers = array();

		$data = new DataQuery(sprintf("SELECT p.Product_ID, pp.Price_Base_Our, pp.Price_Base_RRP, pp.Quantity, pp.Price_Starts_On %sINNER JOIN product_prices AS pp ON p.Product_ID=pp.Product_ID AND pp.Price_Starts_On<=NOW() %s", $sqlFrom, $sqlWhere));
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

		$data = new DataQuery(sprintf("SELECT p.Product_ID, po.Price_Offer, po.Offer_Start_On %sINNER JOIN product_offers AS po ON p.Product_ID=po.Product_ID AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) %s", $sqlFrom, $sqlWhere));
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
}

if(strlen(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup)) > 0) {
	$table = new DataTable('products');
	$table->SetSQL(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup));
	$table->SetTotalRowSQL(sprintf('SELECT COUNT(DISTINCT p.Product_ID) AS TotalRows %s%s', $sqlFrom, $sqlWhere));
	$table->SetMaxRows(15);
	$table->SetOrderBy('Product_Title');
	$table->Finalise();
	$table->ExecuteSQL();
}
	include("ui/nav.php");
	include("ui/search.php");?>
    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Barcode Search</span></div>
<div class="maincontent">
<div class="maincontent1">
					<p>Search our extensive product database against barcodes.</p>

					<?php
					if(!$form->Valid) {
						echo $form->GetError();
						echo '<br />';
					}
					
					echo $form->Open();
					echo $form->GetHTML('confirm');
					?>

					<div class="searchGrid">

						<?php
						echo sprintf('<strong>%s</strong><br />Scan or type in your barcode<br /><br />', $form->GetLabel('barcode'));
						echo $form->GetHTML('barcode');
						?>
					
						<input type="submit" name="submit" value="Search" class="submit" />
					</div>
					<div class="searchGrid">
						<div class="searchAlternative">
							Can't find your product?<br />
							<a href="search.php">Use our general search facility.</a>
						</div>
					</div>
					<div class="clear"></div>

					<?php
					echo $form->Close();

					if(strlen(sprintf('%s%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup)) > 0) {
						?>
						
						<br /><br />

						<div class="SearchBox">
							<div id="SearchInformation">
								<p>There are <strong><?php echo $table->TotalRows; ?></strong> products matching your search criteria.</p>
							</div>
						</div>

						<?php
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

</div>
</div>
 <?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>

