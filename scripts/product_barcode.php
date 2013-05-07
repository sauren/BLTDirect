<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$barcodeSize = 13;
$multiplyOdd = 1;
$multiplyEven = 3;

$data = new DataQuery(sprintf("SELECT Product_ID FROM product"));
while($data->Row) {
	$product = new Product($data->Row['Product_ID']);

	$barcode = $product->ID;

	if(($len = strlen($barcode)) < ($barcodeSize - 1)) {
		for($i=0; $i<($barcodeSize - 1)-$len; $i++) {
			$barcode = '0'.$barcode;
		}
	}

	$sum = 0;

	for($i=0; $i<($barcodeSize - 1); $i++) {
		$position = $i + 1;
		$integer = substr($barcode, $i, 1);

		if((ceil($position / 2) * 2) == $position) {
			$sum += $integer * $multiplyEven;
		} else {
			$sum += $integer * $multiplyOdd;
		}
	}

	$checkDigit = 10 - ($sum % 10);
	$checkDigit = ($checkDigit > 9) ? 0 : $checkDigit;

	$barcode .= $checkDigit;

	$product->InternalBarcode = $barcode;
	$product->Update();

	$data->Next();
}
$data->Disconnect();