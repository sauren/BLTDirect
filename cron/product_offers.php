<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'Product Special Offers';
$cron->scriptFileName = 'product_offers.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_WARNING;

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');

$data = new DataQuery(sprintf("SELECT * FROM product_special_offer WHERE Inactive_Period>0 AND (Base_Offer_Percent>0 OR Base_Offer_Tolerance>0)"));
while($data->Row) {
	$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_offers WHERE Product_ID=%d AND ((Offer_Start_On<=NOW() AND Offer_End_On>NOW()) OR (Offer_Start_On='0000-00-00 00:00:00' AND Offer_End_On='000-00-00 00:00:00') OR (Offer_Start_On='0000-00-00 00:00:00' AND Offer_End_On>NOW()) OR (Offer_Start_On<=NOW() AND Offer_End_On='0000-00-00 00:00:00'))", $data->Row['Product_ID']));
	if($data2->Row['Count'] == 0) {
		$product = new Product($data->Row['Product_ID']);
		
		$percentReduction = $data->Row['Base_Offer_Percent'] + rand($data->Row['Base_Offer_Tolerance'] * -1, $data->Row['Base_Offer_Tolerance']);
		$percentReduction = ($percentReduction > 100) ? $data->Row['Base_Offer_Percent'] : $percentReduction;
		$percentReduction = ($percentReduction < 0) ? $data->Row['Base_Offer_Percent'] : $percentReduction;
			
		if($percentReduction > 0) {
			$data3 = new DataQuery(sprintf("SELECT ADDDATE(Offer_End_On, INTERVAL %d DAY) AS New_Offer_Date FROM product_offers WHERE Product_ID=%d AND Offer_End_On<>'0000-00-00 00:00:00' ORDER BY Offer_End_On DESC LIMIT 0, 1", $data->Row['Inactive_Period'], $data->Row['Product_ID']));
			if($data3->TotalRows > 0) {
				if(strtotime($data3->Row['New_Offer_Date']) < time()) {
					$insertData = new ProductOffer();
					$insertData->ProductID = $product->ID;
					$insertData->priceOffer = ($product->PriceCurrent / 100) * (100 - $percentReduction);
					$insertData->offerStart = date('Y-m-d H:i:s');
					$insertData->offerEnd = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m'), date('d') + 7, date('Y')));
					$insertData->isTaxIncluded = 'N';
					$insertData->add();

					$cron->log(sprintf('Added Offer: %s [%d]', $product->Name, $product->ID), Cron::LOG_LEVEL_INFO);
				}
			} else {
				$insertData = new ProductOffer();
				$insertData->ProductID = $product->ID;
				$insertData->priceOffer = ($product->PriceCurrent / 100) * (100 - $percentReduction);
				$insertData->offerStart = date('Y-m-d H:i:s');
				$insertData->offerEnd = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m'), date('d') + 7, date('Y')));
				$insertData->isTaxIncluded = 'N';
				$insertData->add();			

				$cron->log(sprintf('Added Offer: %s [%d]', $product->Name, $product->ID), Cron::LOG_LEVEL_INFO);
			}
			$data3->Disconnect();
		}
	}
	$data2->Disconnect();

	$data->Next();
}
$data->Disconnect();
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();