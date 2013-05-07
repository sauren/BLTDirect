<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Despatch Details</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="css/i_content.css" rel="stylesheet" type="text/css" media="screen" />
	<script language="javascript" src="js/generic_1.js" type="text/javascript"></script>
</head>
<body>



	<div id="Page">
		<div id="PageContent">
		<?php
		if($_REQUEST['status']=='N'){
			$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID = 0
					", mysql_real_escape_string($_REQUEST['bid']))."AND (Status LIKE '%partial%' or o.Status LIKE 'packing') GROUP BY o.Order_ID";
		}elseif($_REQUEST['status']=='PK'){
			$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID = 0
					AND o.Status LIKE 'packing' GROUP BY o.Order_ID", mysql_real_escape_string($_REQUEST['bid']));
		}elseif($_REQUEST['status']=='PD'){
			$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID = 0
					", mysql_real_escape_string($_REQUEST['bid']))."AND Status LIKE '%partial%' GROUP BY o.Order_ID";
		}else{
			$sql = sprintf("SELECT o.* from warehouse w
					INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
					INNER JOIN orders o on o.Order_ID = ol.Order_ID
					where w.Warehouse_ID = %d AND ol.Despatch_ID = 0
					", mysql_real_escape_string($_REQUEST['bid']))."AND (ol.Line_Status LIKE 'Backordered' OR o.Backordered='Y') GROUP BY o.Order_ID";
		}
		$orderNumber = 0;
		$warehouse = new Warehouse($_REQUEST['bid']);

		$data = new DataQuery($sql);

		if($data->TotalRows == 0){
			echo "There are no orders to be printed off";
		}else{
			while($data->Row){

				if($orderNumber != 0){
					if($_REQUEST['style']=='page'){
						echo "<h1 style='page-break-after:always'></h1>";
					}else{
						echo "<hr>";
					}
				}

				$orderNumber = $data->Row['Order_ID'];
				$order = new Order($orderNumber);

				$order->GetLines();
				$order->Customer->Get();
				$order->Customer->Contact->Get();
			?>
			<table width="100%"  border="0" cellspacing="0" cellpadding="0">
			<tr>
			<td>


			<table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
			<tr>
			<td valign="top" class="billing"><p><strong>Organisation/Individual:</strong><br />
                            <?php echo $order->GetBillingAddress();  ?></p></td>
                    <td valign="top" class="shipping"><p><strong>Shipping Address:</strong><br />
                            <?php echo $order->GetShippingAddress();  ?></p></td>
                    <td valign="top" class="shipping"><p><strong>Invoice Address:</strong><br />
                            <?php echo $order->GetInvoiceAddress();  ?></p></td>
                  </tr>

                </table>
			    </td><td align="right" valign="middle"><table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
                  <tr>
                    <th valign="top"> Order Ref: </th>
                    <td valign="top"><?php echo $order->Prefix . $order->ID; ?></td>
                  </tr>
				  <tr>
                    <th valign="top"> Customer Ref: </th>
                    <td valign="top"><?php echo $order->CustomID; ?> &nbsp;</td>
                  </tr>
                  <tr>
                    <th valign="top">Customer: </th>
                    <td valign="top"><?php echo $order->Customer->Contact->Person->GetFullName(); ?></td>
                  </tr>
                  <tr>
                    <th>Order Status:</th>
                    <td valign="top"><?php echo $order->Status; ?></td>
                  </tr>
                  <tr>
                    <th valign="top">&nbsp </th>
                    <td valign="top">&nbsp</td>
                  </tr>
                  <tr>
                    <th valign="top">&nbsp </th>
                    <td valign="top">

					&nbsp;
					</td>
                  </tr>
                  <tr>
                    <th valign="top">&nbsp;</th>
                    <td valign="top">&nbsp;</td>
                  </tr>
                  <tr>
                    <th valign="top">Order Date: </th>
                    <td valign="top"><?php echo cDatetime($order->OrderedOn, 'shortdate'); ?></td>
                  </tr>

                </table>                </td>
              </tr>
              <tr>
                <td colspan="2"><br>                  <br>
                  <table cellspacing="0" class="orderDetails">
                  <tr>
                    <th>Qty</th>
                    <th>Product</th>
                    <th>Location</th>
                    <th>Despatched</th>
					 <?php if($wareHouse->Type == 'S'){
                    	echo "<th>Cost</th>";
                    }?>
                    <th>Quickfind</th>
                  </tr>
                  <?php
                  $rowCount = 0;

                  for($i=0; $i < count($order->Line); $i++){

                  	if($_REQUEST['bid'] == $order->Line[$i]->DespatchedFrom->ID && $order->Line[$i]->DespatchID == 0){
                  		$rowCount++;
                  		$wareHouseId = $order->Line[$i]->DespatchedFrom->ID;

                  		$showCost = false;
                  		$prodCost = false;
                  		if($wareHouse->Type == 'S'){
                  			$showCost = true;
                  			$costFinder = new DataQuery(sprintf('SELECT * FROM supplier_product WHERE Supplier_ID = %d AND Product_ID = %d',mysql_real_escape_string($session->Warehouse->Contact->ID),mysql_real_escape_string($order->Line[$i]->Product->ID)));
                  			$prodCost = $costFinder->Row['Cost'];
                  		}
					?>
                  <tr>
                    <td>

					<?php echo $order->Line[$i]->Quantity; ?>x</td>
                    <td>
						<?php echo $order->Line[$i]->Product->Name; ?>
					</td>
					<td>
						<?php $warehouseLocation = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1",
					mysql_real_escape_string($order->Line[$i]->DespatchedFrom->ID),
					mysql_real_escape_string($order->Line[$i]->Product->ID)));
					echo $warehouseLocation->Row['Shelf_Location'];
					$warehouseLocation->Disconnect();
								?>&nbsp;
					</td>
					<td>Not despatched</td>
					<?php
					if(strtolower($order->Line[$i]->Status) == 'cancelled'){
					?>
					<td colspan="1" align="center">Cancelled</td>
					<?php
						}if($showCost){?>
					<td><?if($prodCost == false){
						echo "n\\a";
					}else{ echo number_format($order->Line[$i]->Quantity * $prodCost, 2, '.', ',');  }?></td>

					<?php } ?>
                    <td><?php echo $order->Line[$i]->Product->ID; ?></td>
                  </tr>
                  <tr>
                    <td colspan="5" align="left">Cart Weight: ~<?php echo $order->Weight; ?>Kg</td>
                  </tr>
                </table>
              </tr>
              <tr>
                <td align="left" valign="top">
                <td align="right"><table border="0" cellpadding="6" cellspacing="0" class="orderTotals">
                  <tr>
                    <th colspan="2">Shipping Information</th>
                  </tr>
                  <tr>
                    <td>Delivery Option:</td>
                    <td align="right">
                      <?php

                      $order->Postage->Get();
                      echo $order->Postage->Name;

						?>
                    </td>
                  </tr>
                </table></td>
              </tr>
            </table>
			<?php

			?><?php

                  	}
                  }

                  $data->Next();

				}
		}
		$data->Disconnect();

?>
</div>
  	</div>
<script type="text/javascript">
window.self.print();
</script>
</body>
</html>