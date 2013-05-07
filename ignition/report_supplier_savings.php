<?php
require_once('lib/common/app_header.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
	$form->AddOption('range', 'all', '-- All --');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'thisminute', 'This Minute');
	$form->AddOption('range', 'thishour', 'This Hour');
	$form->AddOption('range', 'thisday', 'This Day');
	$form->AddOption('range', 'thismonth', 'This Month');
	$form->AddOption('range', 'thisyear', 'This Year');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lasthour', 'Last Hour');
	$form->AddOption('range', 'last3hours', 'Last 3 Hours');
	$form->AddOption('range', 'last6hours', 'Last 6 Hours');
	$form->AddOption('range', 'last12hours', 'Last 12 Hours');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastday', 'Last Day');
	$form->AddOption('range', 'last2days', 'Last 2 Days');
	$form->AddOption('range', 'last3days', 'Last 3 Days');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			switch($form->GetValue('range')) {
				case 'all': 		$start = date('Y-m-d H:i:s', 0);
				$end = date('Y-m-d H:i:s');
				break;

				case 'thisminute': 	$start = date('Y-m-d H:i:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thishour': 	$start = date('Y-m-d H:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisday': 	$start = date('Y-m-d 00:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thismonth': 	$start = date('Y-m-01 00:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")));
				$end = date('Y-m-d H:i:s');
				break;

				case 'lasthour': 	$start = date('Y-m-d H:00:00', mktime(date("H")-1, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last3hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-3, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last6hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-6, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last12hours': $start = date('Y-m-d H:00:00', mktime(date("H")-12, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastday': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last2days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last3days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastmonth': 	$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last3months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last6months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;

				case 'lastyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-1));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
				case 'last2years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-2));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
				case 'last3years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-3));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
			}

			redirect(sprintf("Location: %s?action=report&start=%s&end=%s", $_SERVER['PHP_SELF'], $start, $end));
		} else {
			if($form->Validate()){
				redirect(sprintf("Location: %s?action=report&start=%s&end=%s", $_SERVER['PHP_SELF'], sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2))))))));
			}
		}
	}

	$page = new Page('Supplier Savings Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Report on Supplier Savings");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('range'), $form->GetHTML('range'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Or select the date range from below for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'hidden', '', 'anything', 1, 19);
	$form->AddField('end', 'End Date', 'hidden', '', 'anything', 1, 19);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		foreach($_REQUEST as $key=>$value) {
			if(preg_match('/^stock_held_([0-9]+)_([0-9]+)$/', $key, $matches)) {
				new DataQuery(sprintf("UPDATE supplier_product SET Is_Stock_Held='%s' WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($value), mysql_real_escape_string($matches[1]), mysql_real_escape_string($matches[2])));
			}
		}
		
		redirect(sprintf("Location: %s?action=report&start=%s&end=%s", $_SERVER['PHP_SELF'], $form->GetValue('start'), $form->GetValue('end')));
	}
	
	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleData = function(hash) {
			var data = document.getElementById(\'data-\' + hash);
			var image = document.getElementById(\'image-\' + hash);
			
			if(data && image) {
				if(data.style.display == \'\') {
					data.style.display = \'none\';
					image.src = \'images/button-plus.gif\';
				} else {
					data.style.display = \'\';
					image.src = \'images/button-minus.gif\';
				}
			}
		}	
		</script>');

	$page = new Page('Supplier Savings Report : ' . cDatetime($form->GetValue('start'), 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead($script);
	$page->Display('header');
	
	$supplierData = array();
	
	$data = new DataQuery(sprintf("SELECT MD5(CONCAT_WS(' ', ol.Product_ID, ol.Despatch_From_ID, ol.Cost)) AS Hash, o.Order_ID, o.Order_Prefix, o.Created_On, o.Total, CONCAT_WS(' ', o.Billing_First_Name, o.Billing_Last_Name, IF(LENGTH(o.Billing_Organisation_Name) > 0, CONCAT('(', o.Billing_Organisation_Name, ')'), '')) AS Billing_Contact, ol.Product_ID, ol.Product_Title, og.Org_Name AS Supplier, SUM(ol.Quantity) AS Quantity, ol.Cost, sp.Cost AS Cheaper_Cost, sog.Org_Name AS Alternative_Supplier, ((ol.Cost - sp.Cost) * SUM(ol.Quantity)) AS Total_Saving, sp.Supplier_ID, sp.Is_Stock_Held FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Despatch_From_ID>0 INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type LIKE 'S' INNER JOIN supplier AS s ON s.Supplier_ID=w.Type_Reference_ID AND (s.Supplier_ID=3 OR s.Supplier_ID=4 OR s.Supplier_ID=5 OR s.Supplier_ID=22) INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN organisation AS og ON og.Org_ID=c.Org_ID INNER JOIN supplier_product AS sp ON sp.Product_ID=ol.Product_ID AND sp.Cost>0 AND sp.Cost<ol.Cost INNER JOIN supplier AS ss ON ss.Supplier_ID=sp.Supplier_ID AND (ss.Supplier_ID=3 OR ss.Supplier_ID=4 OR ss.Supplier_ID=5 OR ss.Supplier_ID=22) INNER JOIN contact AS sc ON sc.Contact_ID=ss.Contact_ID INNER JOIN organisation AS sog ON sog.Org_ID=sc.Org_ID WHERE o.Created_On>='%s' AND o.Created_On<'%s' GROUP BY ol.Product_ID, s.Supplier_ID, ol.Cost, sp.Supplier_Product_ID, o.Order_ID ORDER BY Total_Saving DESC, sp.Cost ASC", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		if(!isset($supplierData[$data->Row['Hash']])) {
			$supplierData[$data->Row['Hash']] = array();
		}
		
		$supplierData[$data->Row['Hash']][] = $data->Row;
		
		$data->Next();
	}	
	$data->Disconnect();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('start');
	echo $form->GetHTML('end');
	?>

	<br />
	<h3>Potential Savings</h3>
	<p>Comparing despatch product costs against the above suppliers for the given period.</p>

	<table cellspacing="0" class="orderDetails">
	
		<?php
		if(count($supplierData) > 0) {
			$totalSupplierCost = 0;
			$totalAlternativeCost = 0;
			$totalSaving = 0;
			?>
			
			<tr>
				<td nowrap="nowrap" style="border: none;" colspan="5">&nbsp;</td>
				<td nowrap="nowrap" style="border: none; text-align: center; background-color: #d5ffad; font-size: 11pt; padding: 10px;" colspan="3">Supplier</td>
				<td nowrap="nowrap" style="border: none; text-align: center; background-color: #ffe0ad; font-size: 11pt; padding: 10px;" colspan="3">Alternative</td>
				<td nowrap="nowrap" style="border: none; text-align: center; background-color: #ffadad; font-size: 11pt; padding: 10px;" colspan="2">Savings</td>
			</tr>
			<tr>
				<th nowrap="nowrap" width="1%">&nbsp;</th>
				<th nowrap="nowrap" width="1%">&nbsp;</th>
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
					<td><?php echo ($supplierItem[0]['Is_Stock_Held'] == 'N') ? sprintf('<input type="checkbox" name="stock_held_%d_%d" value="Y" />', $supplierItem[0]['Supplier_ID'], $supplierItem[0]['Product_ID']) : '&nbsp;'; ?></td>
					<td><?php echo $supplierItem[0]['Product_Title']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $supplierItem[0]['Product_ID']; ?>" target="_blank"><?php echo $supplierItem[0]['Product_ID']; ?></a></td>
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
					<td colspan="12">
					
						<table cellspacing="0" class="orderDetails">
							<tr>
								<th nowrap="nowrap" width="10%" style="padding-right: 5px;">Order Reference</th>
								<th nowrap="nowrap" width="30%" style="padding-right: 5px;">Ordered On</th>
								<th nowrap="nowrap" width="30%" style="padding-right: 5px;">Contact</th>
								<th nowrap="nowrap" width="30%" style="padding-right: 5px; text-align: right;">Total</th>
							</tr>
							
							<?php
							$orders = array();
							
							foreach($supplierItem as $orderItem) {
								if(!isset($orders[$orderItem['Order_ID']])) {
									$orders[$orderItem['Order_ID']] = true;
									?>
									
									<tr>
										<td><a href="order_details.php?orderid=<?php echo $orderItem['Order_ID']; ?>" target="_blank"><?php echo $orderItem['Order_Prefix']; ?><?php echo $orderItem['Order_ID']; ?></a></td>
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
											
												<table cellspacing="0" class="orderDetails">
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
				<td colspan="5">&nbsp;</td>
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
	<br />
	
	<input type="submit" class="btn" name="update" value="update" />
	
	<?php
	echo $form->Close();
	
	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}