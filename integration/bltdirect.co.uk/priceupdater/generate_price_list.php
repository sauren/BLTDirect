<?php
/*
 * This is a cron script with the intention of generating a list of recent prices.
 * Initially the list generated should be large on first run, but should quiten down soon after that.
 */

ini_set('max_execution_time', '3600');
ini_set('display_errors','on');
ini_set('memory_limit', '1024M');

require_once('../../../ignition/lib/classes/ApplicationHeader.php');


define("BLT_DIRECT_UK_GEOZONE_ID",22);

//Indicies Define;
define("BUK_CSV_PRODUCT_ID_INDEX",0);
define("BUK_CSV_ASSOCIATIVE_PRODUCT_TITLE",1);
define("BUK_CSV_TAX_RATE_INDEX",2);
define("BUK_CSV_PRICE_PRICE_UPDATED_ON_INDEX",3);
define("BUK_CSV_PRICE_FROM_PRICE_INDEX",4);
define("BUK_CSV_PRICE_TO_PRICE_INDEX",5);
define("BUK_CSV_PRICE_FROM_INC_TAX_INDEX",6);
define("BUK_CSV_PRICE_TO_INC_TAX_INDEX",7);

define("BUK_DATE_FILE_NAME","date_of_last_export.txt");
define("BUK_PRICE_FILE_NAME","price_export.csv");

define("BUK_DEFAULT_START_DATE",'0000-00-00 00:00:00');

define("BUK_CSV_IMPORT_MAIL_ERRORS",true);


class PriceListGenerator{

    var $ErrorList;
    
    function getCsvHeaders(){
	$line = array();
	$line[BUK_CSV_PRODUCT_ID_INDEX] = "Product_ID";
	$line[BUK_CSV_ASSOCIATIVE_PRODUCT_TITLE] = "Associative_Product_Title";
	$line[BUK_CSV_TAX_RATE_INDEX] = "Tax_Rate";
	$line[BUK_CSV_PRICE_PRICE_UPDATED_ON_INDEX] = "Price_Updated_On";
	$line[BUK_CSV_PRICE_FROM_PRICE_INDEX] = "Price_From";
	$line[BUK_CSV_PRICE_TO_PRICE_INDEX] = "Price_To";
	$line[BUK_CSV_PRICE_FROM_INC_TAX_INDEX] = "Price_From_Inc_Tax";
	$line[BUK_CSV_PRICE_TO_INC_TAX_INDEX] = "Price_To_Inc_Tax";
	return($line);
    }

    function RowToCsvLine($dataQueryRow){
	$line = array();
	$line[BUK_CSV_PRODUCT_ID_INDEX] = $dataQueryRow['Product_ID'];
	$line[BUK_CSV_ASSOCIATIVE_PRODUCT_TITLE] = $dataQueryRow['Associative_Product_Title'];
	$line[BUK_CSV_TAX_RATE_INDEX] = $dataQueryRow['Tax_Rate'];
	$line[BUK_CSV_PRICE_PRICE_UPDATED_ON_INDEX] = $dataQueryRow["Price_Updated_On"];
	$line[BUK_CSV_PRICE_FROM_PRICE_INDEX] = $dataQueryRow["Price_From"];
	$line[BUK_CSV_PRICE_TO_PRICE_INDEX] = $dataQueryRow["Price_To"];
	$line[BUK_CSV_PRICE_FROM_INC_TAX_INDEX] = $dataQueryRow["Price_From_Inc_Tax"];
	$line[BUK_CSV_PRICE_TO_INC_TAX_INDEX] = $dataQueryRow["Price_To_Inc_Tax"];
	return($line);
    }

    function getLastDate(){
	$fp = fopen(BUK_DATE_FILE_NAME,'r');
	if(!$fp){
	    $this->addError("Unable to open file for reading: ". BUK_DATE_FILE_NAME);
	}
	$last_date = fgets($fp);
	fclose($fp);
	return $last_date;
    }

    function writeLastDate($date){
	$fp = fopen(BUK_DATE_FILE_NAME,"w");
	if(!$fp){
	    $this->addError("Unable to open file for writing: ". BUK_DATE_FILE_NAME);
	}
	fwrite($fp,$date);
	fclose($fp);
    }

    function exportLatestPrices(){
	//First off get the last date if the file exists
	if(file_exists(BUK_DATE_FILE_NAME)){
	    $last_date = $this->getLastDate();
	}else{
	    $last_date = BUK_DEFAULT_START_DATE;
	}

	$priceLookup = new DataQuery(sprintf("SELECT p.*,
	    IF(ISNULL(p.tax_rate),p.Price_From,ROUND(((p.Price_From)+((p.tax_rate/100)*p.Price_From)),2)) AS Price_From_Inc_Tax,
	    IF(ISNULL(p.tax_rate),p.Price_To,ROUND(((p.Price_To)+((p.tax_rate/100)*p.Price_To)),2)) AS Price_To_Inc_Tax
	    FROM
	    (SELECT p.Product_ID, p.Tax_Rate, p.Short_Title AS Associative_Product_Title ,Price_Updated_On,MIN(Price_Base_Our) AS Price_From ,MAX(Price_Base_Our) AS Price_To
		    FROM (
			    SELECT p.*,IFNULL(Price_Offer,Price_Base_Our) AS Price_Base_Our, t.Tax_Rate As Tax_Rate,
			    IF(Price_Offer IS NOT NULL, po.Offer_Start_On,IF(Last_Offer_Date IS NULL,Price_Starts_On,IF(Price_Starts_On > Last_Offer_Date,Price_Starts_On,Last_Offer_Date))) AS Price_Updated_On
			    FROM 
			    (
				    SELECT Product_ID, IF(LOCATE('(',Associative_Product_Title) > 0,REVERSE(SUBSTRING(REVERSE(Associative_Product_Title),LOCATE('(',REVERSE(Associative_Product_Title))+2)),Associative_Product_Title) AS Short_Title,
				    Associative_Product_Title,p.Tax_Class_ID FROM product p Where Associative_Product_Title IS NOT NULL AND Associative_Product_Title != ''
			    ) p
			    INNER JOIN product_prices_current ppc ON p.Product_ID = ppc.Product_ID
			    INNER JOIN tax t ON p.Tax_Class_ID = t.Tax_Class_ID AND t.Geozone_ID = %d
			    LEFT JOIN (SELECT * FROM product_offers po WHERE Offer_Start_On <= NOW() AND Offer_End_On >= NOW()) po ON po.Product_ID = p.Product_ID
			    LEFT JOIN (SELECT Product_ID, MAX(Offer_End_On) AS Last_Offer_Date FROM (SELECT * FROM product_offers pe WHERE Offer_Start_On <= NOW() AND Offer_End_On <= NOW()) pe GROUP BY Product_ID) pe ON p.Product_ID = pe.Product_ID

		    ) p GROUP BY Short_Title
	    ) p WHERE Price_Updated_On >= '%s' ORDER BY Price_Updated_On DESC",BLT_DIRECT_UK_GEOZONE_ID,$last_date));

	//Check if theres more than one row, if not we can just end now
	if($priceLookup->TotalRows > 0){
	    //First things first, see if the date has changed, if it has we need to update and safe the new one at the end. If not we just skip everything
	    $new_date = $priceLookup->Row['Price_Updated_On'];
	    if(strcmp($new_date,$last_date) != 0){
		//Strings are different so we need to generate the CSV
		$fp = fopen(BUK_PRICE_FILE_NAME,"w");
		if(!$fp){
		    $this->addError("Unable to open file for reading: ". BUK_PRICE_FILE_NAME);
		}
		fputcsv($fp, $this->getCsvHeaders());
		while($priceLookup->Row){
		    fputcsv($fp,$this->RowToCsvLine($priceLookup->Row));
		    $priceLookup->Next();
		}
		fclose($fp);
		$this->writeLastDate($new_date);
	    }
	    //Strings are the same so do nothing
	}
	$priceLookup->Disconnect();    
    }
    
     function addError($error_message){
	if(empty($this->ErrorList)){
	    $this->ErrorList = array();
	}
	$this->ErrorList[] = $error_message;
    }
    
    function reportErrors(){
	if(!empty($this->ErrorList) && count($this->ErrorList) > 0){
	    if(BUK_CSV_IMPORT_MAIL_ERRORS){
		$mail = new htmlMimeMail5();
		$mail->setFrom('root@bltdirect.com');
		$mail->setSubject(sprintf("BLTUK Price Generator Errors"));
		$mail->setText(implode("\n", $this->ErrorList));
		$mail->send(array('bug@azexis.com'));
	    }else{
		printf("Errors Reported:<br />");
		printf(implode("<br />", $this->ErrorList));
	    }
	}
    }
}

$PriceListGenerator = new PriceListGenerator();
$PriceListGenerator->exportLatestPrices();
$PriceListGenerator->reportErrors();





?>
