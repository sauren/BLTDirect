<?php
ini_set('max_execution_time', '1800');
ini_set('memory_limit', '512M');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Google Base Data Feed';
$fileName = 'google_base_datafeed.php';

## BEGIN SCRIPT
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/TaxCalculator.php');

$feed = 'bltdirect';

function formatText($text) {
	return trim(htmlentities(preg_replace('/[^a-zA-Z0-9\s.-_\'\/"!%()]/', '', strip_tags($text))));
}

$log[] = sprintf("Generating Google Base XML Data Feed\n");

if(file_exists(sprintf('%s%s.xml', $GLOBALS['GOOGLE_BASE_FS'], $feed))) {
	unlink(sprintf('%s%s.xml', $GLOBALS['GOOGLE_BASE_FS'], $feed));
}

$fh = fopen(sprintf('%s%s.xml', $GLOBALS['GOOGLE_BASE_FS'], $feed), 'w');

if($fh) {
	fwrite($fh, "<?xml version=\"1.0\"?>\n");
	fwrite($fh, "<rss version=\"2.0\" xmlns:g=\"http://base.google.com/ns/1.0\" xmlns:c=\"http://base.google.com/cns/1.0\">\n");
	fwrite($fh, "<channel>\n");
	fwrite($fh, sprintf("\t<title>%s</title>\n", $GLOBALS['COMPANY']));
	fwrite($fh, sprintf("\t<link>%s</link>\n", $GLOBALS['HTTP_SERVER']));
	fwrite($fh, sprintf("\t<description>%s.</description>\n", $GLOBALS['COMPANY_DESCRIPTION']));

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.SKU, p.Product_Title, p.Product_Blurb, p.Product_Description, p.Weight, p.Is_Stocked, p.Google_Base_Suffix, m.Manufacturer_Name, pb.Barcode, pb.Brand FROM product AS p LEFT JOIN manufacturer AS m ON p.Manufacturer_ID=m.Manufacturer_ID LEFT JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID AND pb.Quantity=1 WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' AND p.Is_Complementary='N' GROUP BY p.Product_ID ORDER BY p.Product_ID ASC"));
	while($data->Row) {
		$price = 0;

		$data2 = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=NOW() ORDER BY Price_Starts_On DESC LIMIT 0, 1", $data->Row['Product_ID']));
		if($data2->TotalRows > 0) {
			$price = $data2->Row['Price_Base_Our'];
		}
		$data2->Disconnect();

		$data2 = new DataQuery(sprintf("SELECT Price_Offer FROM product_offers WHERE Product_ID=%d AND ((Offer_Start_On<=NOW() AND Offer_End_On>NOW()) OR (Offer_Start_On='000-00-00 00:00:00' AND Offer_End_On='000-00-00 00:00:00') OR (Offer_Start_On='000-00-00 00:00:00' AND Offer_End_On<NOW()) OR (Offer_Start_On>=NOW() AND Offer_End_On='000-00-00 00:00:00')) ORDER BY Price_Offer ASC LIMIT 0, 1", $data->Row['Product_ID']));
		if($data2->TotalRows > 0) {
			if(($price == 0) || ($data2->Row['Price_Offer'] < $price)) {
				$price = $data2->Row['Price_Offer'];
			}
		}
		$data2->Disconnect();

		if($price > 0) {
			$requiredCount = 0;

			if(!empty($data->Row['Brand'])) {
				$requiredCount++;
			} elseif(!empty($data->Row['Manufacturer_Name'])) {
				$requiredCount++;
			}
			
			if(!empty($data->Row['Barcode'])) {
				$requiredCount++;
			}

			if(!empty($data->Row['SKU'])) {
				$requiredCount++;
			}

			if($requiredCount >= 2) {
				$taxCalculator = new TaxCalculator($price, $GLOBALS['DEFAULT_SHIPPING_COUNTRY'], $GLOBALS['DEFAULT_SHIPPING_REGION'], $GLOBALS['DEFAULT_TAX_ON_SHIPPING']);

				$price += round($taxCalculator->GetTax($price, $GLOBALS['DEFAULT_TAX_ON_SHIPPING']), 2);

				fwrite($fh, "\t<item>\n");
				fwrite($fh, sprintf("\t\t<title>%s</title>\n", formatText(sprintf('%s %s', strip_tags($data->Row['Product_Title']), $data->Row['Google_Base_Suffix']))));
				fwrite($fh, sprintf("\t\t<link>%sproduct.php?pid=%d</link>\n", $GLOBALS['HTTP_SERVER'], $data->Row['Product_ID']));
				fwrite($fh, sprintf("\t\t<description><![CDATA[%s]]></description>\n", (strlen(formatText($data->Row['Product_Description'])) > 0) ? formatText($data->Row['Product_Description']) : formatText($data->Row['Product_Blurb'])));
				fwrite($fh, sprintf("\t\t<g:id>%d</g:id>\n", $data->Row['Product_ID']));
				fwrite($fh, sprintf("\t\t<g:price>%s</g:price>\n", number_format($price, 2, '.', '')));
				
				$condition = 'new';
				if(preg_match('/\([^)]*refurb[^)]*\)/i', $productTitle)){
					$condition = 'refurbished';
				}
				fwrite($fh, sprintf("\t\t<g:condition>%s</g:condition>\n", $condition));

				$imageIndex = 0;

				$data2 = new DataQuery(sprintf("SELECT Image_Src FROM product_images WHERE Is_Active='Y' AND Product_ID=%d GROUP BY Image_Src ORDER BY Is_Primary ASC", $data->Row['Product_ID']));
				while($data2->Row) {
					if (!empty($data2->Row['Image_Src']) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'] . $data2->Row['Image_Src'])) {
						$imageLink = substr($GLOBALS['HTTP_SERVER'], 0, -1) . $GLOBALS['PRODUCT_IMAGES_DIR_WS'] . $data2->Row['Image_Src'];

						if($imageIndex > 0) {
							fwrite($fh, sprintf("\t\t<g:additional_image_link>%s</g:additional_image_link>\n", $imageLink));
						} else {
							fwrite($fh, sprintf("\t\t<g:image_link>%s</g:image_link>\n", $imageLink));
						}

						$imageIndex++;
					}

					$data2->Next();
				}
				$data2->Disconnect();

				if(!empty($data->Row['Brand'])) {
					fwrite($fh, sprintf("\t\t<g:brand><![CDATA[%s]]></g:brand>\n", htmlentities($data->Row['Brand'])));

				} elseif(!empty($data->Row['Manufacturer_Name'])) {
					fwrite($fh, sprintf("\t\t<g:brand><![CDATA[%s]]></g:brand>\n", formatText($data->Row['Manufacturer_Name'])));
				}
				
				if(!empty($data->Row['Barcode'])) {
					fwrite($fh, sprintf("\t\t<g:gtin>%s</g:gtin>\n", $data->Row['Barcode']));
				}

				if(!empty($data->Row['SKU'])) {
					fwrite($fh, sprintf("\t\t<g:mpn>%s</g:mpn>\n", formatText($data->Row['SKU'])));
				}

				if($data->Row['Weight'] > 0) {
					fwrite($fh, sprintf("\t\t<g:weight>%d kg</g:weight>\n", $data->Row['Weight']));
				}

				fwrite($fh, sprintf("\t\t<g:availability>%s</g:availability>\n", ($data->Row['Is_Stocked'] == 'Y') ? 'in stock' : 'available for order'));	
				fwrite($fh, sprintf("\t\t<g:google_product_category>Home &amp; Garden &gt; Lighting</g:google_product_category>\n"));
				fwrite($fh, sprintf("\t\t<g:product_type>Home &amp; Garden &gt; Lighting &gt; Lamps</g:product_type>\n"));
				fwrite($fh, sprintf("\t\t<g:product_type>Home &amp; Garden &gt; Lighting &gt; Lightbulbs</g:product_type>\n"));
				
				$specifications = array();

				$data2 = new DataQuery(sprintf("SELECT psg.Name, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID INNER JOIN product_specification_group AS psg ON psv.Group_ID=psg.Group_ID AND psg.Is_Hidden='N' WHERE ps.Product_ID=%d", $data->Row['Product_ID']));
				while ($data2->Row) {
					$specifications[] = $data2->Row;
				
					$data2->Next();
				}
				$data2->Disconnect();

				$count = 0;

				foreach($specifications as $specification) {
					if($count < 10) {
						$value = str_replace(' ', '', formatText($specification['UnitValue']));

						if(!empty($value)) {
							if(!preg_match('/^[0-9]*$/', $specification['UnitValue'])) {
								fwrite($fh, sprintf("\t\t<g:adwords_labels><![CDATA[%s]]></g:adwords_labels>\n", strtolower($value)));

								$count++;
							}
						}
					}
				}

				foreach($specifications as $specification) {
					$specTitle = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $specification['Name']))));
					$specValue = formatText($specification['UnitValue']);

					if(!empty($specTitle) && !empty($specValue)) {
						if(is_numeric(substr($specTitle, 0, 1))) {
							$specTitle = sprintf('spec_%s', $specTitle);
						}

						if($specTitle == 'description'){
							$specTitle = 'spec_description';
						}
						if($specTitle == 'manufacturer'){
							$specTitle = 'product_manufacturer';
						}

						fwrite($fh, sprintf("\t\t<c:%s type=\"%s\"><![CDATA[%s]]></c:%s>\n", $specTitle, (preg_match('/^[0-9]*$/', $specValue) && ($specValue >= -2147483648) && ($specValue <= 2147483647)) ? 'int' : 'string', $specValue, $specTitle));
					}
				}

				fwrite($fh, "\t</item>\n");
			}
		}

		$data->Next();
	}
	$data->Disconnect();

	fwrite($fh, "</channel>\n");
	fwrite($fh, "</rss>");

	fclose($fh);
}
## END SCRIPT

$logHeader[] = sprintf("Script: %s", $script);
$logHeader[] = sprintf("File Name: %s", $fileName);
$logHeader[] = sprintf("Date Executed: %s", date('Y-m-d H:i:s'));
$logHeader[] = sprintf("Execution Time: %s seconds", number_format(microtime(true) - $timing, 4, '.', ''));
$logHeader[] = '';

$log = array_merge($logHeader, $log);

if ($mailLog) {
	$mail = new htmlMimeMail5();
	$mail->setFrom('root@bltdirect.com');
	$mail->setSubject(sprintf("Cron [%s] <root@bltdirect.com> php /var/www/vhosts/bltdirect.com/httpdocs/cron/%s", $script, $fileName));
	$mail->setText(implode("\n", $log));
	$mail->send(array('adam@azexis.com'));
}

echo implode("<br />", $log);

$GLOBALS['DBCONNECTION']->Close();