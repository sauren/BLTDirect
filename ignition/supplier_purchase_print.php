<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');

$session->Secure(2);

$page = new Page('Purchase Print', 'Print list of purchases.');
$page->Display('header');

if(isset($_REQUEST['date']) && (preg_match(sprintf("/%s/", $form->RegularExp['date_ddmmyyy']), $_REQUEST['date']))) {
	$overallCost = 0;

	$date = str_replace('/', '', $_REQUEST['date']);
	$date = sprintf('%s-%s-%s', substr($date, 4, 4), substr($date, 2, 2), substr($date, 0, 2));

	$startDate = date('Y-m-d 00:00:00', strtotime($date));
	$endDate = date('Y-m-d 00:00:00', strtotime($date) + 86400);

	$data = new DataQuery(sprintf("SELECT o.*, w.Warehouse_ID FROM warehouse AS w INNER JOIN order_line AS ol ON ol.Despatch_From_ID=w.Warehouse_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID WHERE w.Type_Reference_ID=%d AND w.Type='S' AND o.Status LIKE 'Despatched' AND o.Despatched_On BETWEEN '%s' AND '%s' GROUP BY o.Order_ID ORDER BY o.Order_ID ASC", mysql_real_escape_string($session->Supplier->ID), mysql_real_escape_string($startDate), mysql_real_escape_string($endDate)));
	if($data->TotalRows > 0) {
		while($data->Row) {
			$order = new Order($data->Row['Order_ID']);
			$order->GetLines();

			echo sprintf('<h1 style="font-size: 14px;">Order <strong>%d</strong></h1>', $order->ID);
			?>

			<table border="0" cellpadding="6" cellspacing="0" width="100%">
	          <tr>
	           	<td align="left" valign="top" width="100%">

                  <table cellspacing="0" class="orderDetails">
	                  <tr>
	                    <th>Qty</th>
	                    <th>Product</th>
	                    <th>Cost per unit</th>
	                    <th>Part No.</th>
	                    <th>Quickfind</th>
	                  </tr>

		                <?php
		                $totalCost = 0;

		                for($i=0; $i < count($order->Line); $i++){
		                	if(($data->Row['Warehouse_ID'] == $order->Line[$i]->DespatchedFrom->ID) && ($order->Line[$i]->DespatchID > 0) && (trim(strtolower($order->Line[$i]->Status)) != 'cancelled')) {
		                		$cost = '';
		                		$partNum = '';

								$data2 = new DataQuery(sprintf('SELECT * FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d', mysql_real_escape_string($session->Supplier->ID), mysql_real_escape_string($order->Line[$i]->Product->ID)));
	                			if($data2->TotalRows > 0) {
	                				$cost = $data2->Row['Cost'];
	                				$partNum = $data2->Row['Supplier_SKU'];
	                			}
	                			$data2->Disconnect();
								?>

				                  <tr>
				                    <td><?php echo $order->Line[$i]->Quantity; ?>x</td>
				                    <td><?php echo $order->Line[$i]->Product->Name; ?></td>

									<?php
									if($cost != '') {
										$totalCost += $cost * $order->Line[$i]->Quantity;
										echo sprintf("<td>&pound;%s</td>", number_format($cost, 2, '.', ','));
									} else {
										echo '<td>-</td>';
									}

									if($partNum != '') {
										echo sprintf("<td>%s&nbsp;</td>", $partNum);
									} else {
										echo '<td>-</td>';
									}
									?>

				                    <td><?php echo $order->Line[$i]->Product->ID; ?></td>
				                  </tr>

				                  <?php
		                	}
		                }

		                $overallCost += $totalCost;
		                ?>
	                </table>

	               </td>
	              </tr>
	              <tr>
	                <td align="right">

		                <table border="0" cellpadding="6" cellspacing="0" class="orderTotals">
		                  <tr>
		                    <th colspan="2">Order Summary</th>
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
		                  <tr>
		                    <td>Total Cost:</td>
		                    <td align="right">
		                      &pound;<?php echo number_format($totalCost, 2, '.', ','); ?>
		                    </td>
		                  </tr>
		                </table>

	                </td>
	              </tr>
	            </table>

			<?php
            $data->Next();
		}
		?>

		<br /><br />
		<div style="border:1px solid #ccc; padding: 10px; background-color: #f9f9f9;">
			<h2 style="font-size:16px; margin: 0 0 5px 0;">Overall Cost</h2>
			<h3>&pound;<?php echo number_format($overallCost, 2, '.', ','); ?></h3>
		</div>

		<?php
	} else {
		echo '<strong style="text-align: center;">There are no purchase orders for the specified period.</strong>';
	}
	$data->Disconnect();
} else {
	echo '<strong style="text-align: center;">Please specify a valid period for purchase orders.</strong>';
}

$page->Display('footer');
require_once('lib/common/app_footer.php');