<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(2);

$page = new Page('Order Print Picking', 'Print list of orders.');
$page->Display('header');

$orderNumber = 0;

$data = new DataQuery(sprintf("SELECT o.*, w.Warehouse_ID FROM warehouse w
		INNER JOIN order_line ol on ol.Despatch_From_ID = w.Warehouse_ID
		INNER JOIN orders o on o.Order_ID=ol.Order_ID
		WHERE w.Type_Reference_ID=%d AND w.Type='S' AND ol.Despatch_ID=0
		AND ((ol.Line_Status LIKE 'Backordered' OR o.Backordered='Y') AND ol.Line_Status NOT LIKE 'Cancelled') AND o.Status NOT LIKE 'Cancelled' GROUP BY o.Order_ID
		",mysql_real_escape_string($session->Supplier->ID)));

if($data->TotalRows == 0){
	echo '<strong style="text-align: center;">There are no orders to be printed off</strong>';
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
            <td valign="top" class="shipping"><p> <strong>Shipping Address:</strong><br />
                    <?php echo $order->Shipping->GetFullName(); ?><br />
                    <?php echo $order->Shipping->Address->Line1; ?></p></td>
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
           	<th>Cost per unit</th>
            <th>Part No.</th>
            <th>Quickfind</th>
          </tr>
        <?php
          $rowCount = 0;

          for($i=0; $i < count($order->Line); $i++){

          	if($data->Row['Warehouse_ID'] == $order->Line[$i]->DespatchedFrom->ID && $order->Line[$i]->DespatchID == 0){
          		$rowCount++;
          		$wareHouseId = $order->Line[$i]->DespatchedFrom->ID;
          		$showCost = true;
          		$prodCost = false;
          		$supplierPartNo = '';
      			$costFinder = new DataQuery(sprintf('SELECT * FROM supplier_product WHERE Supplier_ID = %d AND Product_ID = %d',mysql_real_escape_string($session->Supplier->ID),mysql_real_escape_string($order->Line[$i]->Product->ID)));
      			$prodCost = $costFinder->Row['Cost'];
      			$supplierPartNo = $costFinder->Row['Supplier_SKU'];
		?>
          <tr>
            <td><?php echo $order->Line[$i]->Quantity; ?>x</td>
            <td><?php echo $order->Line[$i]->Product->Name; ?></td>
			<td>
				<?php $warehouseLocation = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock
														WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1",
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
				}
			if($showCost){
				if($prodCost == false){
					echo "<td>n\\a</td>";
				}else{
					echo sprintf("<td>%s</td>", number_format($prodCost, 2, '.', ','));
				}
				echo sprintf("<td>%s&nbsp;</td>", $supplierPartNo);
			}
			?>
            <td><?php echo $order->Line[$i]->Product->ID; ?></td>
          </tr>
          <tr>
            <td colspan="<?php echo ($showCost)? "7":"6";?>" align="left">Cart Weight: ~<?php echo $order->Weight; ?>Kg</td>
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
    </table><br />
	<?php

      	}
      }

      $data->Next();

	}
}
$data->Disconnect();

$page->Display('footer');
require_once('lib/common/app_footer.php');