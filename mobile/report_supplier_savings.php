<?php
require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$start = date('Y-m-01 00:00:00');
	$end = date('Y-m-d H:i:s');
	
	$supplierData = array();
	
	$data = new DataQuery(sprintf("SELECT MD5(CONCAT_WS(' ', ol.Product_ID, ol.Despatch_From_ID, ol.Cost)) AS Hash, o.Order_ID, o.Order_Prefix, o.Created_On, o.Total, CONCAT_WS(' ', o.Billing_First_Name, o.Billing_Last_Name, IF(LENGTH(o.Billing_Organisation_Name) > 0, CONCAT('(', o.Billing_Organisation_Name, ')'), '')) AS Billing_Contact, ol.Product_ID, ol.Product_Title, og.Org_Name AS Supplier, SUM(ol.Quantity) AS Quantity, ol.Cost, sp.Cost AS Cheaper_Cost, sog.Org_Name AS Alternative_Supplier, ((ol.Cost - sp.Cost) * SUM(ol.Quantity)) AS Total_Saving FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_From_ID>0 INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type LIKE 'S' INNER JOIN supplier AS s ON s.Supplier_ID=w.Type_Reference_ID AND (s.Supplier_ID=3 OR s.Supplier_ID=4 OR s.Supplier_ID=5 OR s.Supplier_ID=22) INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN organisation AS og ON og.Org_ID=c.Org_ID INNER JOIN supplier_product AS sp ON sp.Product_ID=ol.Product_ID AND sp.Cost>0 AND sp.Cost<ol.Cost INNER JOIN supplier AS ss ON ss.Supplier_ID=sp.Supplier_ID AND (ss.Supplier_ID=3 OR ss.Supplier_ID=4 OR ss.Supplier_ID=5 OR ss.Supplier_ID=22) INNER JOIN contact AS sc ON sc.Contact_ID=ss.Contact_ID INNER JOIN organisation AS sog ON sog.Org_ID=sc.Org_ID WHERE o.Created_On>='%s' AND o.Created_On<'%s' GROUP BY ol.Product_ID, s.Supplier_ID, ol.Cost, sp.Supplier_Product_ID, o.Order_ID ORDER BY Total_Saving DESC, sp.Cost ASC", $start, $end));
	while($data->Row) {
		if(!isset($supplierData[$data->Row['Hash']])) {
			$supplierData[$data->Row['Hash']] = array();
		}
		
		$supplierData[$data->Row['Hash']][] = $data->Row;
		
		$data->Next();
	}	
	$data->Disconnect();
	?>

	<html>
	<head>
	<style>
		body, th, td {
			font-family: arial, sans-serif;
			font-size: 0.8em;
		}
		h1, h2, h3, h4, h5, h6 {
			margin-bottom: 0;
			padding-bottom: 0;
		}
		h1 {
			font-size: 1.6em;
		}
		h2 {
			font-size: 1.2em;
		}
		p {
			margin-top: 0;
		}
		table {
			width: 100%;
		}
		table th {
			border-bottom: 1px solid #999999;
			text-align: left;
		}
		table td {
			border-bottom: 1px dashed #aaaaaa;
			padding: 5px;
		}
	</style>
	<script language="javascript" type="text/javascript">
		var toggleData = function(hash) {
			var data = document.getElementById('data-' + hash);
			var image = document.getElementById('image-' + hash);
			
			if(data && image) {
				if(data.style.display == '') {
					data.style.display = 'none';
					image.src = 'images/button-plus.gif';
				} else {
					data.style.display = '';
					image.src = 'images/button-minus.gif';
				}
			}
		}	
	</script>
	</head>
	<body>

	<h1>Supplier Savings Report</h1>

	<br />
	<h3>Potential Savings</h3>
	<p>Comparing despatch product costs against the above suppliers for the given period.</p>

	<table cellspacing="0">
	
		<?php
		if(count($supplierData) > 0) {
			$totalSupplierCost = 0;
			$totalAlternativeCost = 0;
			$totalSaving = 0;
			?>
			
			<tr>
				<td nowrap="nowrap" style="border: none;" colspan="4">&nbsp;</td>
				<td nowrap="nowrap" style="border: none; text-align: center; background-color: #d5ffad; font-size: 11pt; padding: 10px;" colspan="3">Supplier</td>
				<td nowrap="nowrap" style="border: none; text-align: center; background-color: #ffe0ad; font-size: 11pt; padding: 10px;" colspan="3">Alternative</td>
				<td nowrap="nowrap" style="border: none; text-align: center; background-color: #ffadad; font-size: 11pt; padding: 10px;" colspan="2">Savings</td>
			</tr>
			<tr>
				<th nowrap="nowrap">&nbsp;</th>
				<th nowrap="nowrap" style="padding-right: 5px;">Product Name</th>
				<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
				<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Qty</th>
				<th nowrap="nowrap" style="padding-right: 10px; background-color: #d5ffad;">Name</th>
				<th nowrap="nowrap" style="padding-right: 10px; text-align: right; background-color: #d5ffad;">Cost</th>
				<th nowrap="nowrap" style="padding-right: 10px; text-align: right; background-color: #d5ffad;">Total</th>
				<th nowrap="nowrap" style="padding-right: 10px; background-color: #ffe0ad;">Name</th>
				<th nowrap="nowrap" style="padding-right: 10px; text-align: right; background-color: #ffe0ad;">Cost</th>
				<th nowrap="nowrap" style="padding-right: 10px; text-align: right; background-color: #ffe0ad;">Total</th>
				<th nowrap="nowrap" style="padding-right: 10px; text-align: right; background-color: #ffadad;">Cost</th>
				<th nowrap="nowrap" style="padding-right: 10px; text-align: right; background-color: #ffadad;">Total</th>
			</tr>
	
			<?php
			foreach($supplierData as $hash=>$supplierItem) {
				$totalSupplierCost += $supplierItem[0]['Cost'] * $supplierItem[0]['Quantity'];
				$totalAlternativeCost += $supplierItem[0]['Cheaper_Cost'] * $supplierItem[0]['Quantity'];
				$totalSaving += ($supplierItem[0]['Cost'] - $supplierItem[0]['Cheaper_Cost']) * $supplierItem[0]['Quantity'];
				?>
				
				<tr>
					<td><a href="javascript:toggleData('<?php echo $hash; ?>');"><img src="images/button-plus.gif" id="image-<?php echo $hash; ?>" /></a></td>
					<td><?php echo $supplierItem[0]['Product_Title']; ?></td>
					<td><?php echo $supplierItem[0]['Product_ID']; ?></td>
					<td align="right"><?php echo $supplierItem[0]['Quantity']; ?></td>
					<td style="background-color: #bdff95;"><?php echo $supplierItem[0]['Supplier']; ?></td>
					<td align="right" style="background-color: #bdff95;">&pound;<?php echo number_format($supplierItem[0]['Cost'], 2, '.', ','); ?></td>
					<td align="right" style="background-color: #bdff95;">&pound;<?php echo number_format($supplierItem[0]['Cost'] * $supplierItem[0]['Quantity'], 2, '.', ','); ?></td>
					<td style="background-color: #ffca95;"><?php echo $supplierItem[0]['Alternative_Supplier']; ?></td>
					<td align="right" style="background-color: #ffca95;">&pound;<?php echo number_format($supplierItem[0]['Cheaper_Cost'], 2, '.', ','); ?></td>
					<td align="right" style="background-color: #ffca95;">&pound;<?php echo number_format($supplierItem[0]['Cheaper_Cost'] * $supplierItem[0]['Quantity'], 2, '.', ','); ?></td>
					<td align="right" style="background-color: #ff9595;">&pound;<?php echo number_format($supplierItem[0]['Cost'] - $supplierItem[0]['Cheaper_Cost'], 2, '.', ','); ?></td>
					<td align="right" style="background-color: #ff9595;">&pound;<?php echo number_format(($supplierItem[0]['Cost'] - $supplierItem[0]['Cheaper_Cost']) * $supplierItem[0]['Quantity'], 2, '.', ','); ?></td>
				</tr>
				<tr style="display: none;" id="data-<?php echo $hash; ?>">
					<td>&nbsp;</td>
					<td colspan="11">
					
						<table cellspacing="0">
							<tr>
								<th nowrap="nowrap" width="10%" style="padding-right: 5px;">Order Reference</th>
								<th nowrap="nowrap" width="30%" style="padding-right: 5px;">Ordered On</th>
								<th nowrap="nowrap" width="30%" style="padding-right: 5px;">Billing Contact</th>
								<th nowrap="nowrap" width="30%" style="padding-right: 5px; text-align: right;">Total</th>
							</tr>
							
							<?php
							$orders = array();
							
							foreach($supplierItem as $orderItem) {
								if(!isset($orders[$orderItem['Order_ID']])) {
									$orders[$orderItem['Order_ID']] = true;
									?>
									
									<tr>
										<td><?php echo $orderItem['Order_Prefix']; ?><?php echo $orderItem['Order_ID']; ?></td>
										<td><?php echo $orderItem['Created_On']; ?></td>
										<td><?php echo $orderItem['Billing_Contact']; ?></td>
										<td align="right">&pound;<?php echo number_format($orderItem['Total'], 2, '.', ','); ?></td>
									</tr>
									
									<?php
									$data = new DataQuery(sprintf("SELECT w.Warehouse_Name, ownt.Name AS Type, own.Note FROM order_warehouse_note AS own LEFT JOIN order_warehouse_note_type AS ownt ON ownt.Order_Warehouse_Note_Type_ID=own.Order_Warehouse_Note_Type_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=own.Warehouse_ID WHERE own.Order_ID=%d ORDER BY own.Order_Warehouse_Note_ID ASC", mysql_real_escape_string($orderItem['Order_ID'])));
									if($data->TotalRows > 0) {
										?>
										
										<tr>
											<td>&nbsp;</td>
											<td colspan="3">
											
												<table cellspacing="0">
													<tr>
														<th nowrap="nowrap" width="30%" style="padding-right: 5px;">Warehouse</th>
														<th nowrap="nowrap" width="20%" style="padding-right: 5px;">Type</th>
														<th nowrap="nowrap" width="50%" style="padding-right: 5px;">Note</th>
													</tr>
					
													<?php
													while($data->Row) {
														?>
									
														<tr>
															<td><?php echo $data->Row['Warehouse_Name']; ?></td>
															<td><?php echo $data->Row['Type']; ?></td>
															<td><?php echo $data->Row['Note']; ?></td>
														</tr>
														
														<?php
														$data->Next();
													}
													?>
											
												</table>
												
											</td>
										</tr>
										
									<?php
									}
									$data->Disconnect();
								}
							}
							?>
							
						</table>					
					
					</td>
				</tr>
				
				<?php
			}
			?>
			
			<tr>
				<td colspan="4">&nbsp;</td>
      			<td style="background-color: #d5ffad;" colspan="2">&nbsp;</td>
      			<td align="right" style="background-color: #d5ffad;"><strong>&pound;<?php echo number_format($totalSupplierCost, 2, '.', ','); ?></strong></td>
      			<td style="background-color: #ffe0ad;" colspan="2">&nbsp;</td>
      			<td align="right" style="background-color: #ffe0ad;"><strong>&pound;<?php echo number_format($totalAlternativeCost, 2, '.', ','); ?></strong></td>
      			<td align="right" style="background-color: #ffadad;">&nbsp;</td>
      			<td align="right" style="background-color: #ffadad;"><strong>&pound;<?php echo number_format($totalSaving, 2, '.', ','); ?></strong></td>
      		</tr>
			
			<?php
		} else {
			?>
			
			<tr>
				<td align="center" colspan="6">There are not items available for viewing.</td>
			</tr>
			
			<?php
		}
		?>
	
	</table>
	
	</body>
	</html>

	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}