<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$order = new Order();

$data = new DataQuery(sprintf("SELECT Order_ID FROM orders WHERE Status LIKE 'Despatched' ORDER BY Order_ID DESC LIMIT 0, 10"));
while($data->Row) {
	$order->Get($data->Row['Order_ID']);
	$order->GetLines();

	$oldTotal = $order->Total;

	###
	$order->TotalLines = count($order->Line);
	$order->SubTotal = $order->FreeTextValue;

	$order->TotalDiscount = 0;
	$order->TotalTax = 0;
	$order->Total = 0;
	$order->Weight = 0;

	$taxCalculator = new GlobalTaxCalculator($order->Shipping->Address->Country->ID, $order->Shipping->Address->Region->ID);

	if(strtoupper($order->IsTaxExempt) == 'N' || empty($order->TaxExemptCode)){
		$data2 = new DataQuery("SELECT Tax_Class_ID FROM tax_class WHERE Is_Default='Y'");
		if($data2->TotalRows > 0) {
			$temptax = $taxCalculator->GetTax($order->FreeTextValue, $data2->Row['Tax_Class_ID']);
			$order->TotalTax += round($temptax, 2);
		}
		$data2->Disconnect();
	}

	for($i=0; $i < count($order->Line); $i++){
		$order->SubTotal += $order->Line[$i]->Total;

		if($order->Line[$i]->Product->ID > 0) {
			$order->TotalDiscount += $order->Line[$i]->Discount;
		}

		$order->TotalTax += $order->Line[$i]->Tax;
		$order->Weight += ($order->Line[$i]->Quantity * $order->Line[$i]->Product->Weight);
	}

	$order->Total = $order->TotalTax + $order->TotalShipping + $order->SubTotal - $order->TotalDiscount;
	###

	//$order->Update();

	if($order->Total != $oldTotal) {
		echo $order->ID . ': ' . $oldTotal . ' - ' . $order->Total . '<br />';
	}

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>