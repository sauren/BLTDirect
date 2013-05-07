<?php
require_once('../classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerSession.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DiscountCollection.php");

$session = new CustomerSession();
$session->Start();

$products = array();
$count = 0;
$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 0;

$dicountCollection = new DiscountCollection();
$dicountCollection->Get($session->Customer);

$data = new DataQuery(sprintf("SELECT po.Product_ID, pi.Image_Src FROM product_offers AS po INNER JOIN product AS p ON p.Product_ID=po.Product_ID INNER JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='N' AND pi.Is_Primary='N' AND pi.Image_Title LIKE 'specialoffer' WHERE p.Is_Active='Y' AND p.Discontinued='N' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND ((po.Offer_Start_On<=NOW() AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On='000-00-00 00:00:00') OR (po.Offer_Start_On='0000-00-00 00:00:00' AND po.Offer_End_On>NOW()) OR (po.Offer_Start_On<=NOW() AND po.Offer_End_On='0000-00-00 00:00:00')) GROUP BY po.Product_ID ORDER BY RAND()"));
while($data->Row) {
	$product = new Product($data->Row['Product_ID']);
	$useCustomPrice = false;

	if($session->IsLoggedIn) {
		if(count($dicountCollection->Line) > 0){
			list($tempLineTotal, $discountName) = $dicountCollection->DiscountProduct($product, 1);

			if($tempLineTotal < $product->PriceCurrent)  {
				$priceSavingPercent = round((($product->PriceRRP - $tempLineTotal) / $product->PriceRRP) * 100);

				if($priceSavingPercent > 0) {
					$useCustomPrice = true;

					if(($limit == 0) || ($count < $limit)) {
						$products[$data->Row['Product_ID']] = true;
						
						$name = $product->Name;
						$name = (strlen($name) > 50) ? trim(substr($name, 0, 47)) . '...' : $name;

						echo sprintf("%s{br}", $product->ID);
						echo sprintf("%s{br}", $name);
						echo sprintf("%s{br}", number_format($tempLineTotal, 2, '.', ','));
						echo sprintf("%s{br}", $priceSavingPercent);
						echo sprintf("%s{br}{br}", (!empty($data->Row['Image_Src']) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$data->Row['Image_Src'])) ? $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$data->Row['Image_Src'] : ' ');

						$count++;
					}
				}
			}
		}
	}

	if(!$useCustomPrice) {
		if($product->PriceSavingPercent > 0) {
			if(($limit == 0) || ($count < $limit)) {
				$products[$data->Row['Product_ID']] = true;
				
				$name = $product->Name;
				$name = (strlen($name) > 50) ? trim(substr($name, 0, 47)) . '...' : $name;

				echo sprintf("%s{br}", $product->ID);
				echo sprintf("%s{br}", $name);
				echo sprintf("%s{br}", number_format($product->PriceCurrent, 2, '.', ','));
				echo sprintf("%s{br}", $product->PriceSavingPercent);
				echo sprintf("%s{br}{br}", (!empty($data->Row['Image_Src']) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$data->Row['Image_Src'])) ? $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$data->Row['Image_Src'] : ' ');

				$count++;
			}
		}
	}

	$data->Next();
}
$data->Disconnect();

if(($limit == 0) || ($count < $limit)) {
	$data = new DataQuery(sprintf("SELECT spo.Product_ID, pi.Image_Src FROM product_special_offer AS spo INNER JOIN product AS p ON p.Product_ID=spo.Product_ID INNER JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='N' AND pi.Is_Primary='N' AND pi.Image_Title LIKE 'specialoffer'WHERE p.Is_Active='Y' AND p.Discontinued='N' AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) GROUP BY spo.Product_ID"));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$product = new Product($data->Row['Product_ID']);
			$useCustomPrice = false;

			if($session->IsLoggedIn) {
				if(count($dicountCollection->Line) > 0){
					list($tempLineTotal, $discountName) = $dicountCollection->DiscountProduct($product, 1);

					if($tempLineTotal < $product->PriceCurrent)  {
						$priceSavingPercent = round((($product->PriceRRP - $tempLineTotal) / $product->PriceRRP) * 100);

						if(($priceSavingPercent > 0) && !empty($product->PriceRRP)) {
							$useCustomPrice = true;

							if(($limit == 0) || ($count < $limit)) {
								echo sprintf("%s{br}", $product->ID);
								echo sprintf("%s{br}", $product->Name);
								echo sprintf("%s{br}", number_format($tempLineTotal, 2, '.', ','));
								echo sprintf("%s{br}", $priceSavingPercent);
								echo sprintf("%s{br}{br}", (!empty($data->Row['Image_Src']) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$data->Row['Image_Src'])) ? $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$data->Row['Image_Src'] : ' ');

								$count++;
							}
						}
					}
				}
			}

			if(!$useCustomPrice) {
				$priceSavingPercent = round((($product->PriceRRP - $product->PriceCurrent) / $product->PriceRRP) * 100);

				if(($priceSavingPercent > 0) && !empty($product->PriceRRP)) {
					if(($limit == 0) || ($count < $limit)) {
						echo sprintf("%s{br}", $product->ID);
						echo sprintf("%s{br}", $product->Name);
						echo sprintf("%s{br}", number_format($product->PriceCurrent, 2, '.', ','));
						echo sprintf("%s{br}", $priceSavingPercent);
						echo sprintf("%s{br}{br}", (!empty($data->Row['Image_Src']) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$data->Row['Image_Src'])) ? $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$data->Row['Image_Src'] : ' ');

						$count++;
					}
				}
			}
		}

		$data->Next();
	}
	$data->Disconnect();
}

$GLOBALS['DBCONNECTION']->Close();