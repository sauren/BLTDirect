<?php
ini_set('max_execution_time', '90000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

$order = new Order();

$data = new DataQuery(sprintf("SELECT o.Order_ID, o.TaxExemptCode, o.Nominal_Code, pm.Reference, c.Nominal_Code AS Country_Nominal_Code, c.Nominal_Code_Tax_Free AS Country_Nominal_Code_Tax_Free, c.Nominal_Code_Account AS Country_Nominal_Code_Account, c.Nominal_Code_Account_Tax_Free AS Country_Nominal_Code_Account_Tax_Free FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID LEFT JOIN countries AS c ON c.Country_ID=o.Shipping_Country_ID ORDER BY o.Order_ID ASC"));
while($data->Row) {
	$order->ID = $data->Row['Order_ID'];
	$order->TaxExemptCode = $data->Row['TaxExemptCode'];
	$order->NominalCode = $data->Row['Nominal_Code'];
	$order->PaymentMethod->Reference = $data->Row['Reference'];
	$order->Shipping->Address->Country->NominalCode = $data->Row['Country_Nominal_Code'];
	$order->Shipping->Address->Country->NominalCodeTaxFree = $data->Row['Country_Nominal_Code_Tax_Free'];
	$order->Shipping->Address->Country->NominalCodeAccount = $data->Row['Country_Nominal_Code_Account'];
	$order->Shipping->Address->Country->NominalCodeAccountTaxFree = $data->Row['Country_Nominal_Code_Account_Tax_Free'];
	$order->CalculateNominalCode();
	
	new DataQuery(sprintf("UPDATE orders SET Nominal_Code='%s' WHERE Order_ID=%d", $order->NominalCode, $order->ID));
	
	echo sprintf('%d: %s - %s<br />', $order->ID, $data->Row['Nominal_Code'], $order->NominalCode);
	
	$data->Next();
}
$data->Disconnect();