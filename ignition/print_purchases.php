<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

$session->Secure();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Purchase Details</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="../css/lightbulbs.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="../ignition/css/i_content.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="../css/lightbulbs_print.css" rel="stylesheet" type="text/css" media="print" />
	<link href="../warehouse/css/default.css" rel="stylesheet" type="text/css" media="screen" />
	<script language="javascript" src="js/generic_1.js" type="text/javascript"></script>
</head>
<body>

<div id="Page">
	<div id="PageContent">

		<?php
		$print = false;

		if(isset($_REQUEST['supplier']) && ($_REQUEST['supplier'] > 0)) {
			if(isset($_REQUEST['date']) && (preg_match(sprintf("/%s/", $form->RegularExp['date_ddmmyyy']), $_REQUEST['date']))) {

				$overallCost = 0;

				$date = str_replace('/', '', $_REQUEST['date']);
				$date = sprintf('%s-%s-%s', substr($date, 4, 4), substr($date, 2, 2), substr($date, 0, 2));

				$startDate = date('Y-m-d 00:00:00', strtotime($date));
				$endDate = date('Y-m-d 00:00:00', strtotime($date) + 86400);

				$data = new DataQuery(sprintf("SELECT o.*, w.Warehouse_ID, w.Type FROM warehouse AS w INNER JOIN order_line AS ol ON ol.Despatch_From_ID=w.Warehouse_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID WHERE w.Type='S' AND w.Type_Reference_ID=%d AND o.Status LIKE 'Despatched' AND o.Despatched_On BETWEEN '%s' AND '%s' GROUP BY o.Order_ID ORDER BY o.Order_ID ASC", mysql_real_escape_string($_REQUEST['supplier']), mysql_real_escape_string($startDate), mysql_real_escape_string($endDate)));
				if($data->TotalRows > 0) {
					$warehouse = new Warehouse($data->Row['Warehouse_ID']);

					$print = true;

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

		                    		<?php if($data->Row['Type'] == 'S') { ?>
					                    <th>Cost per unit</th>
					                    <th>Part No.</th>
				                    <?php } ?>

				                    <th>Quickfind</th>
				                  </tr>

					                <?php
					                $totalCost = 0;

					                for($i=0; $i < count($order->Line); $i++){
					                	if(($data->Row['Warehouse_ID'] == $order->Line[$i]->DespatchedFrom->ID) && ($order->Line[$i]->DespatchID > 0) && (trim(strtolower($order->Line[$i]->Status)) != 'cancelled')) {
					                		$cost = '';
					                		$partNum = '';

		                  					if($warehouse->Type == 'S'){

					                			$data2 = new DataQuery(sprintf('SELECT * FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d', mysql_real_escape_string($warehouse->Contact->ID), mysql_real_escape_string($order->Line[$i]->Product->ID)));
					                			if($data2->TotalRows > 0) {
					                				$cost = $data2->Row['Cost'];
					                				$partNum = $data2->Row['Supplier_SKU'];
					                			}
					                			$data2->Disconnect();
											}
											?>

							                  <tr>
							                    <td><?php echo $order->Line[$i]->Quantity; ?>x</td>
							                    <td><?php echo $order->Line[$i]->Product->Name; ?></td>

												<?php
												if($warehouse->Type == 'S'){
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
					                  <?php
					                  if($warehouse->Type == 'S'){
					                  	?>
						                  <tr>
						                    <td>Total Cost:</td>
						                    <td align="right">
						                      &pound;<?php echo number_format($totalCost, 2, '.', ','); ?>
						                    </td>
						                  </tr>
						                 <?php
					                  }
					                  ?>
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
						<table width="100%">
							<tr>
								<td width="50%">
									<h2 style="font-size:16px; margin: 0 0 5px 0;">Warehouse</h2>
									<h3><?php echo $warehouse->Name; ?></h3>
								</td>
								<td width="50%">
									<h2 style="font-size:16px; margin: 0 0 5px 0;">Overall Cost</h2>
									<h3>&pound;<?php echo number_format($overallCost, 2, '.', ','); ?></h3>
								</td>
							</tr>
						</table>
					</div>

					<?php
				} else {
					echo '<strong style="text-align: center;">There are no purchase orders for the specified period for this supplier.</strong>';
				}
				$data->Disconnect();
			} else {
				echo '<strong style="text-align: center;">Please specify a valid period for purchase orders.</strong>';
			}
		} else {
			echo '<strong style="text-align: center;">Please specify a valid supplier for purchase orders.</strong>';
		}
		?>

	</div>
</div>

<?php
if($print) {
	?>
	<script type="text/javascript">
		window.self.print();
		window.close();
	</script>
	<?php
}
?>

</body>
</html>