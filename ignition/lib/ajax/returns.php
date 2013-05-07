<?php
require_once('../classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');

$order = new Order($_REQUEST['orderid']);
$order->GetLines();

echo "LineID,Name,Quantity,Price\n";

for($i=0; $i<count($order->Line); $i++) {
	$order->Line[$i]->Product->Get();
	
	if($order->Line[$i]->Product->IsNonReturnable == 'N') {
		echo sprintf("%s,%s,%s,%s\n", $order->Line[$i]->ID, $order->Line[$i]->Product->Name, $order->Line[$i]->Quantity, $order->Line[$i]->Price);
	}
}

$GLOBALS['DBCONNECTION']->Close();
?>