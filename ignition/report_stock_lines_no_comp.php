<?php
ini_set('max_execution_time', '120');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			report($form->GetValue('parent'),($form->GetValue('subfolders') =='Y')?true:false);
			exit;
		}
	}

	$page = new Page('Stock Line Report', 'Please select a product category to report on.');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Report on stock from a category.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $form->Close();
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($cat, $sub){

	$clientString = "";

	if($cat != 0) {
		if($sub) {
			$clientString = sprintf("AND (cat.Category_ID=%d %s) ", mysql_real_escape_string($cat), mysql_real_escape_string(GetChildIDS($cat)));
		} else {
			$clientString = sprintf("AND (cat.Category_ID=%d) ", mysql_real_escape_string($cat));
		}
	} else {
		if(!$sub) {
			$clientString = sprintf("AND (cat.Category_ID=%d) ", mysql_real_escape_string($cat));
		}
	}

	$data = new DataQuery(sprintf("SELECT ws.Product_ID, COUNT(c.Product_ID) AS Components
							FROM warehouse_stock ws
							INNER JOIN warehouse w ON ws.Warehouse_ID = w.Warehouse_ID
							INNER JOIN users u ON w.Type_Reference_ID = u.Branch_ID
							INNER JOIN product p ON ws.Product_ID = p.Product_ID
							LEFT JOIN product_in_categories AS cat ON p.Product_ID=cat.Product_ID
							LEFT JOIN product_components AS c ON c.Component_Of_Product_ID=p.Product_ID
							WHERE w.Type='B' AND u.User_ID=%d AND p.Discontinued<>'Y'
							%sGROUP BY p.Product_ID", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), $clientString));

	$products = '';
	$productArr = array();
	$virtualProducts = array();

	$counter = 0;

	while($data->Row) {
		$products .= sprintf('ws.Product_ID=%d OR ', $data->Row['Product_ID']);
		$productArr[$data->Row['Product_ID']] = $data->Row['Product_ID'];
		$counter++;

		if($data->Row['Components'] > 0) {
			$virtualProducts[$data->Row['Product_ID']] = $data->Row['Product_ID'];
		}
		$data->Next();
	}
	$data->Disconnect();

	if($counter > 0) {
		$products = substr($products, 0, -4);
	}

	## capture all products for 6 date periods
	$periods = array();
	$periods['7'] = 7;
	$periods['14'] = 14;
	$periods['21'] = 21;
	$periods['60'] = 60;
	$periods['90'] = 90;
	$periods['120'] = 120;

	$dataArr = array();

	if($counter > 0) {
		foreach($periods as $k => $v) {
			$itemArr = array();

			$data = new DataQuery(sprintf("SELECT ws.Quantity_In_Stock, ws.Product_ID,
									p.Product_Title, p.Position_Quantities_Recent, p.Position_Orders_Recent, pc.Component_Quantity, sp.Cost
									FROM warehouse_stock ws
									INNER JOIN warehouse w ON ws.Warehouse_ID = w.Warehouse_ID
									INNER JOIN users u ON w.Type_Reference_ID = u.Branch_ID
									INNER JOIN product p ON ws.Product_ID = p.Product_ID
									INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID
									INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID
									LEFT JOIN product_components AS pc ON pc.Product_ID=p.Product_ID
									LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
									WHERE w.Type='B' AND u.User_ID=%d AND p.Discontinued<>'Y'
									AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
									AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
									AND (%s)
									AND sp.Preferred_Supplier='Y'
									GROUP BY p.Product_ID Order By (sp.Cost*ws.Quantity_In_Stock) DESC", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($v), mysql_real_escape_string($products)));

			while($data->Row) {
				$item = array();
				$item['Product_ID'] = $data->Row['Product_ID'];
				$item['Product_Title'] = strip_tags($data->Row['Product_Title']);
                $item['Position_Quantities'] = $data->Row['Position_Quantities_Recent'];
				$item['Position_Orders'] = $data->Row['Position_Orders_Recent'];
				$item['Quantity_In_Stock'] = $data->Row['Quantity_In_Stock'];
				$item['Component'] = empty($data->Row['Component_Quantity']) ? false : true;
				$item['Cost'] = $data->Row['Cost'];

				$itemArr[$data->Row['Product_ID']] = $item;

				$data->Next();
			}
			$data->Disconnect();

			$dataArr[$k] = $itemArr;
		}
	}

	$page = new Page('Stock Line Report');
	$page->AddToHead('<style>td.virtual { background-color: #fdf; } td.component { background-color: #ddf; } td.red { background-color: #fcc; } td.green { background-color: #cfc; } td.darkred { background-color: #f99; } td.darkgreen { background-color: #8f8; } td.verydarkgreen { background-color: #0f0; }</style>');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	$used = array();
	$notused = array();
	?>
	<br />
	<h3>Stock Line A</h3>
	<p>Below are the details of stock for line A.<br />Products sold at least once within the last 7 days, and at least 10 times within the last 60 days.</p>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name </strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind </strong></td>
        <td style="border-bottom:1px solid #aaaaaa"><strong>Position (Quantity)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Position (Orders)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Supplier Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Current Price</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Optimum Qty</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Stocked</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Sold</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Count</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Stock Value</strong> </td>
	  </tr>

		<?php
		$totalValue = 0;

		foreach($dataArr['7'] as $k => $v) {
			$item = $dataArr['7'][$k];

			if(!$item['Component']) {
				if(!isset($virtualProducts[$item['Product_ID']])) {

					$periodDays = 60;
					$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", mysql_real_escape_string($item['Product_ID']), $periodDays));

					if($data->Row['count'] >= 10) {
						$used[$item['Product_ID']] = true;

						$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc", mysql_real_escape_string($item['Product_ID'])));
					if(($item['Component'])) {
						$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity * pc.Component_Quantity) AS qty
													FROM orders AS o
													INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID
													INNER JOIN product_components AS pc ON ol.Product_ID=pc.Component_Of_Product_ID
													WHERE pc.Product_ID=%d
													AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')",
													mysql_real_escape_string($item['Product_ID']), $periodDays));
					}

						$qtyOptimum = ceil(($data->Row['qty'] + ($item['Component'] ? $data2->Row['qty'] : 0)) / ($periodDays/30));
						$qtyStock = $item['Quantity_In_Stock'];

						if($qtyOptimum > $qtyStock) {

							if(($qtyOptimum > 0) && ($qtyStock == 0)) {
								$class = 'class="darkred"';
							} elseif(($qtyStock <= ($qtyOptimum * 0.5))) {
								$class = 'class="darkred"';
							} else {
								$class = 'class="red"';
							}
						} else {
							if(($qtyStock >= ($qtyOptimum * 3))) {
								$class = 'class="verydarkgreen"';
							} elseif(($qtyStock >= ($qtyOptimum * 1.5))) {
								$class = 'class="darkgreen"';
							} else {
								$class = 'class="green"';
							}
						}
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td <?php print ($item['Component']) ? 'class="component"' : ''; ?>><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
						<td align="right"><?php echo $item['Product_ID']; ?></td>
                        <td><?php echo $item['Position_Quantities']; ?></td>
						<td><?php echo $item['Position_Orders']; ?></td>
						<td align="right"><?php echo (empty($item['Cost']))?"N/A":"&pound;".$item['Cost']; ?></td>
						<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
						<td align="right"><?php print $qtyOptimum; ?></td>
						<td align="right" <?php print $class; ?>><?php echo $item['Quantity_In_Stock']; ?></td>
						<td align="right"><?php echo ($data->Row['qty'] + (($item['Component']) ? $data2->Row['qty'] : 0)); ?></td>
						<td align="right"><?php echo ($data->Row['count'] + (($item['Component']) ? $data2->Row['count'] : 0)); ?></td>
						<td align="right">&pound;<?php echo number_format(((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']),2,'.',','); ?></td>
						</tr>

						<?php
					if(($item['Component'])) {
						$data2->Disconnect();
					}

						$totalValue += ((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']);

						$priceFind->Disconnect();
					} else {
						$notused[$item['Product_ID']] = $item;
					}

					$data->Disconnect();
				}
			}
		}

		foreach($dataArr['7'] as $k => $v) {
			$item = $dataArr['7'][$k];

			if(!$item['Component']) {
				if(isset($virtualProducts[$item['Product_ID']])) {

					$periodDays = 60;
					$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", mysql_real_escape_string($item['Product_ID']), $periodDays));

					if($data->Row['count'] >= 10) {
						$used[$item['Product_ID']] = true;

						$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc", mysql_real_escape_string($item['Product_ID'])));
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td class="virtual"><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
						<td align="right"><?php echo $item['Product_ID']; ?></td>
                        <td><?php echo $item['Position_Quantities']; ?></td>
						<td><?php echo $item['Position_Orders']; ?></td>
						<td align="right">N/A</td>
						<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
					<td align="right">N/A</td>
						<td align="right">N/A</td>
						<td align="right">N/A</td>
						<td align="right"><?php echo $data->Row['qty']; ?></td>
						<td align="right"><?php echo $data->Row['count']; ?></td>
						<td align="right">N/A</td>
						</tr>

						<?php
						$priceFind->Disconnect();
					} else {
						$notused[$item['Product_ID']] = $item;
					}

					$data->Disconnect();
				}
			}
		}
		?>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td colspan="11"><strong>Total Stock Value</strong></td>
			<td align="right"><strong>&pound;<?php print number_format($totalValue,2,'.',','); ?></strong></td>
		</tr>
	</table><br />

	<h3>Stock Line B</h3>
	<p>Below are the details of stock for line B.<br />Products sold at least once within the last 14 days, and at least 10 times within the last 90 days, and not in Stock Line A.</p>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name </strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind </strong></td>
        <td style="border-bottom:1px solid #aaaaaa"><strong>Position (Quantity)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Position (Orders)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Supplier Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Current Price</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Optimum Qty</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Stocked</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Sold</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Count</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Stock Value</strong> </td>
	  </tr>

		<?php
		$totalValue = 0;

		foreach($dataArr['14'] as $k => $v) {
			$item = $dataArr['14'][$k];

			if(!$item['Component']) {
				if(!isset($virtualProducts[$item['Product_ID']])) {

					if(!isset($used[$item['Product_ID']])) {
						$periodDays = 90;
						$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", mysql_real_escape_string($item['Product_ID']), $periodDays));

						if($data->Row['count'] >= 10) {

							$used[$item['Product_ID']] = true;
							unset($notused[$item['Product_ID']]);

							$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc",mysql_real_escape_string($item['Product_ID'])));
						if(($item['Component'])) {
							$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity * pc.Component_Quantity) AS qty
														FROM orders AS o
														INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID
														INNER JOIN product_components AS pc ON ol.Product_ID=pc.Component_Of_Product_ID
														WHERE pc.Product_ID=%d
														AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')",
														mysql_real_escape_string($item['Product_ID']), $periodDays));
						}

						$qtyOptimum = ceil(($data->Row['qty'] + ($item['Component'] ? $data2->Row['qty'] : 0)) / ($periodDays/30));
						$qtyStock = $item['Quantity_In_Stock'];

						if($qtyOptimum > $qtyStock) {

							if(($qtyOptimum > 0) && ($qtyStock == 0)) {
								$class = 'class="darkred"';
							} elseif(($qtyStock <= ($qtyOptimum * 0.5))) {
								$class = 'class="darkred"';
							} else {
								$class = 'class="red"';
							}
						} else {
							if(($qtyStock >= ($qtyOptimum * 3))) {
								$class = 'class="verydarkgreen"';
							} elseif(($qtyStock >= ($qtyOptimum * 1.5))) {
								$class = 'class="darkgreen"';
							} else {
								$class = 'class="green"';
							}
						}
							?>

							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td <?php print ($item['Component']) ? 'class="component"' : ''; ?>><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
							<td align="right"><?php echo $item['Product_ID']; ?></td>
                            <td><?php echo $item['Position_Quantities']; ?></td>
							<td><?php echo $item['Position_Orders']; ?></td>
							<td align="right"><?php echo (empty($item['Cost']))?"N/A":"&pound;".$item['Cost']; ?></td>
							<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
						<td align="right"><?php print $qtyOptimum; ?></td>
						<td align="right" <?php print $class; ?>><?php echo $item['Quantity_In_Stock']; ?></td>
						<td align="right"><?php echo ($data->Row['qty'] + (($item['Component']) ? $data2->Row['qty'] : 0)); ?></td>
						<td align="right"><?php echo ($data->Row['count'] + (($item['Component']) ? $data2->Row['count'] : 0)); ?></td>
							<td align="right">&pound;<?php echo number_format(((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']),2,'.',','); ?></td>
							</tr>

							<?php
						if(($item['Component'])) {
							$data2->Disconnect();
						}
							$totalValue += ((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']);

							$priceFind->Disconnect();
						} else {
							$notused[$item['Product_ID']] = $item;
						}
						$data->Disconnect();
					} else {
						$notused[$item['Product_ID']] = $item;
					}
				}
			}
		}

		foreach($dataArr['14'] as $k => $v) {
			$item = $dataArr['14'][$k];

			if(!$item['Component']) {
				if(isset($virtualProducts[$item['Product_ID']])) {

					if(!isset($used[$item['Product_ID']])) {
						$periodDays = 90;
						$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", $item['Product_ID'], $periodDays));

						if($data->Row['count'] >= 10) {

							$used[$item['Product_ID']] = true;
							unset($notused[$item['Product_ID']]);

							$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc",$item['Product_ID']));
							?>

							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td class="virtual"><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
							<td align="right"><?php echo $item['Product_ID']; ?></td>
                            <td><?php echo $item['Position_Quantities']; ?></td>
							<td><?php echo $item['Position_Orders']; ?></td>
							<td align="right">N/A</td>
							<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
						<td align="right">N/A</td>
							<td align="right">N/A</td>
							<td align="right">N/A</td>
							<td align="right"><?php echo $data->Row['qty']; ?></td>
							<td align="right"><?php echo $data->Row['count']; ?></td>
							<td align="right">N/A</td>
							</tr>

							<?php
							$priceFind->Disconnect();
						} else {
							$notused[$item['Product_ID']] = $item;
						}
						$data->Disconnect();
					} else {
						$notused[$item['Product_ID']] = $item;
					}
				}
			}
		}
		?>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td colspan="11"><strong>Total Stock Value</strong></td>
			<td align="right"><strong>&pound;<?php print number_format($totalValue,2,'.',','); ?></strong></td>
		</tr>
	</table><br />

	<h3>Stock Line C</h3>
	<p>Below are the details of stock for line C.<br />Products sold at least once within the last 21 days, and at least 5 times within the last 120 days, and not in Stock Line A or B.</p>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name </strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind </strong></td>
        <td style="border-bottom:1px solid #aaaaaa"><strong>Position (Quantity)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Position (Orders)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Supplier Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Current Price</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Optimum Qty</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Stocked</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Sold</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Count</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Stock Value</strong> </td>
	  </tr>

		<?php
		$totalValue = 0;

		foreach($dataArr['21'] as $k => $v) {
			$item = $dataArr['21'][$k];

			if(!$item['Component']) {
				if(!isset($virtualProducts[$item['Product_ID']])) {

					if(!isset($used[$item['Product_ID']])) {
						$periodDays = 120;
						$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", $item['Product_ID'], $periodDays));

						if($data->Row['count'] >= 5) {
							$used[$item['Product_ID']] = true;
							unset($notused[$item['Product_ID']]);

							$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc",mysql_real_escape_string($item['Product_ID'])));
						if(($item['Component'])) {
							$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity * pc.Component_Quantity) AS qty
														FROM orders AS o
														INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID
														INNER JOIN product_components AS pc ON ol.Product_ID=pc.Component_Of_Product_ID
														WHERE pc.Product_ID=%d
														AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')",
														mysql_real_escape_string($item['Product_ID']), $periodDays));
						}

						$qtyOptimum = ceil(($data->Row['qty'] + ($item['Component'] ? $data2->Row['qty'] : 0)) / ($periodDays/30));
						$qtyStock = $item['Quantity_In_Stock'];

						if($qtyOptimum > $qtyStock) {

							if(($qtyOptimum > 0) && ($qtyStock == 0)) {
								$class = 'class="darkred"';
							} elseif(($qtyStock <= ($qtyOptimum * 0.5))) {
								$class = 'class="darkred"';
							} else {
								$class = 'class="red"';
							}
						} else {
							if(($qtyStock >= ($qtyOptimum * 3))) {
								$class = 'class="verydarkgreen"';
							} elseif(($qtyStock >= ($qtyOptimum * 1.5))) {
								$class = 'class="darkgreen"';
							} else {
								$class = 'class="green"';
							}
						}
							?>

							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td <?php print ($item['Component']) ? 'class="component"' : ''; ?>><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
							<td align="right"><?php echo $item['Product_ID']; ?></td>
                            <td><?php echo $item['Position_Quantities']; ?></td>
							<td><?php echo $item['Position_Orders']; ?></td>
							<td align="right"><?php echo (empty($item['Cost']))?"N/A":"&pound;".$item['Cost']; ?></td>
							<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
						<td align="right"><?php print $qtyOptimum; ?></td>
						<td align="right" <?php print $class; ?>><?php echo $item['Quantity_In_Stock']; ?></td>
						<td align="right"><?php echo ($data->Row['qty'] + (($item['Component']) ? $data2->Row['qty'] : 0)); ?></td>
						<td align="right"><?php echo ($data->Row['count'] + (($item['Component']) ? $data2->Row['count'] : 0)); ?></td>
							<td align="right">&pound;<?php echo number_format(((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']),2,'.',','); ?></td>
							</tr>

							<?php
						if(($item['Component'])) {
							$data2->Disconnect();
						}

							$totalValue += ((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']);

							$priceFind->Disconnect();
						} else {
							$notused[$item['Product_ID']] = $item;
						}
						$data->Disconnect();
					} else {
						$notused[$item['Product_ID']] = $item;
					}
				}
			}
		}

		foreach($dataArr['21'] as $k => $v) {
			$item = $dataArr['21'][$k];

			if(!$item['Component']) {
				if(isset($virtualProducts[$item['Product_ID']])) {

					if(!isset($used[$item['Product_ID']])) {
						$periodDays = 120;
						$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", mysql_real_escape_string($item['Product_ID']), $periodDays));

						if($data->Row['count'] >= 5) {
							$used[$item['Product_ID']] = true;
							unset($notused[$item['Product_ID']]);

							$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc",mysql_real_escape_string($item['Product_ID'])));
							?>

							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td class="virtual"><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
							<td align="right"><?php echo $item['Product_ID']; ?></td>
                            <td><?php echo $item['Position_Quantities']; ?></td>
							<td><?php echo $item['Position_Orders']; ?></td>
							<td align="right">N/A</td>
							<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
						<td align="right">N/A</td>
							<td align="right">N/A</td>
							<td align="right">N/A</td>
							<td align="right"><?php echo $data->Row['qty']; ?></td>
							<td align="right"><?php echo $data->Row['count']; ?></td>
							<td align="right">N/A</td>
							</tr>

							<?php
							$priceFind->Disconnect();

						} else {
							$notused[$item['Product_ID']] = $item;
						}

						$data->Disconnect();
					} else {
						$notused[$item['Product_ID']] = $item;
					}
				}
			}
		}
		?>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td colspan="11"><strong>Total Stock Value</strong></td>
			<td align="right"><strong>&pound;<?php print number_format($totalValue,2,'.',','); ?></strong></td>
		</tr>
	</table><br />

	<h3>Stock Line D</h3>
	<p>Below are the details of stock for line D.<br />Products sold at least once within the last 120 days, and not in Stock Line A, B or C.</p>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name </strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind </strong></td>
        <td style="border-bottom:1px solid #aaaaaa"><strong>Position (Quantity)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Position (Orders)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Supplier Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Current Price</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Optimum Qty</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Stocked</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Sold</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Count</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Stock Value</strong> </td>
	  </tr>

		<?php
		$totalValue = 0;

		foreach($dataArr['120'] as $k => $v) {
			$item = $dataArr['120'][$k];

			if(!$item['Component']) {
				if(!isset($virtualProducts[$item['Product_ID']])) {

					if(!isset($used[$item['Product_ID']])) {
						$used[$item['Product_ID']] = true;

						$periodDays = 120;
						$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", mysql_real_escape_string($item['Product_ID']), $periodDays));

						$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc",mysql_real_escape_string($item['Product_ID'])));

					if(($item['Component'])) {
						$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity * pc.Component_Quantity) AS qty
													FROM orders AS o
													INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID
													INNER JOIN product_components AS pc ON ol.Product_ID=pc.Component_Of_Product_ID
													WHERE pc.Product_ID=%d
													AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')",
													mysql_real_escape_string($item['Product_ID']), $periodDays));
					}

					$qtyOptimum = ceil(($data->Row['qty'] + ($item['Component'] ? $data2->Row['qty'] : 0)) / ($periodDays/30));
					$qtyStock = $item['Quantity_In_Stock'];

					if($qtyOptimum > $qtyStock) {

						if(($qtyOptimum > 0) && ($qtyStock == 0)) {
							$class = 'class="darkred"';
						} elseif(($qtyStock <= ($qtyOptimum * 0.5))) {
							$class = 'class="darkred"';
						} else {
							$class = 'class="red"';
						}
					} else {
						if(($qtyStock >= ($qtyOptimum * 3))) {
							$class = 'class="verydarkgreen"';
						} elseif(($qtyStock >= ($qtyOptimum * 1.5))) {
							$class = 'class="darkgreen"';
						} else {
							$class = 'class="green"';
						}
					}
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td <?php print ($item['Component']) ? 'class="component"' : ''; ?>><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
						<td align="right"><?php echo $item['Product_ID']; ?></td>
                        <td><?php echo $item['Position_Quantities']; ?></td>
						<td><?php echo $item['Position_Orders']; ?></td>
						<td align="right"><?php echo (empty($item['Cost']))?"N/A":"&pound;".$item['Cost']; ?></td>
						<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
					<td align="right"><?php print $qtyOptimum; ?></td>
					<td align="right" <?php print $class; ?>><?php echo $item['Quantity_In_Stock']; ?></td>
					<td align="right"><?php echo ($data->Row['qty'] + (($item['Component']) ? $data2->Row['qty'] : 0)); ?></td>
					<td align="right"><?php echo ($data->Row['count'] + (($item['Component']) ? $data2->Row['count'] : 0)); ?></td>
						<td align="right">&pound;<?php echo number_format(((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']),2,'.',','); ?></td>
						</tr>

						<?php
					if(($item['Component'])) {
						$data2->Disconnect();
					}

						$totalValue += ((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']);

						$priceFind->Disconnect();

						$data->Disconnect();
					}
				}
			}
		}

		foreach($dataArr['120'] as $k => $v) {
			$item = $dataArr['120'][$k];

			if(!$item['Component']) {
				if(isset($virtualProducts[$item['Product_ID']])) {

					if(!isset($used[$item['Product_ID']])) {
						$used[$item['Product_ID']] = true;

						$periodDays = 120;
						$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", mysql_real_escape_string($item['Product_ID']), $periodDays));

						$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc",mysql_real_escape_string($item['Product_ID'])));
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td class="virtual"><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
						<td align="right"><?php echo $item['Product_ID']; ?></td>
                        <td><?php echo $item['Position_Quantities']; ?></td>
						<td><?php echo $item['Position_Orders']; ?></td>
						<td align="right">N/A</td>
						<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
					<td align="right">N/A</td>
						<td align="right">N/A</td>
						<td align="right">N/A</td>
						<td align="right"><?php echo $data->Row['qty']; ?></td>
						<td align="right"><?php echo $data->Row['count']; ?></td>
						<td align="right">N/A</td>
						</tr>

						<?php
						$priceFind->Disconnect();

						$data->Disconnect();
					}
				}
			}
		}
		?>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td colspan="9"><strong>Total Stock Value</strong></td>
			<td align="right"><strong>&pound;<?php print number_format($totalValue,2,'.',','); ?></strong></td>
		</tr>
	</table><br />

	<?php
	$dataArr = array();
	$counter = 0;
	$products = '';
	foreach($productArr as $k => $v) {
		if(!isset($used[$k])) {
			$products .= sprintf('ws.Product_ID=%d OR ', $k);
		}
	}

	if($counter > 0) {
		$products = substr($products, 0, -4);

		$data = new DataQuery(sprintf("SELECT ws.Quantity_In_Stock, ws.Product_ID,
									p.Product_Title, pc.Component_Quantity, sp.Cost
									FROM warehouse_stock ws
									INNER JOIN warehouse w ON ws.Warehouse_ID = w.Warehouse_ID
									INNER JOIN users u ON w.Type_Reference_ID = u.Branch_ID
									INNER JOIN product p ON ws.Product_ID = p.Product_ID
									INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID
									INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID
									LEFT JOIN product_components AS pc ON pc.Product_ID=p.Product_ID
									LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
									WHERE w.Type='B' AND u.User_ID=%d AND p.Discontinued<>'Y'
									AND o.Created_On<ADDDATE(Now(), -120)
									AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
									AND (%s)
									AND sp.Preferred_Supplier='Y'
									GROUP BY p.Product_ID Order By (sp.Cost*ws.Quantity_In_Stock) DESC", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($products)));

		while($data->Row) {
			$item = array();
			$item['Product_ID'] = $data->Row['Product_ID'];
			$item['Product_Title'] = strip_tags($data->Row['Product_Title']);
			$item['Quantity_In_Stock'] = $data->Row['Quantity_In_Stock'];
			$item['Component'] = empty($data->Row['Component_Quantity']) ? false : true;
			$item['Cost'] = $data->Row['Cost'];

			$dataArr[$data->Row['Product_ID']] = $item;

			$data->Next();
		}
		$data->Disconnect();
	}
	?>

	<h3>Stock Line E</h3>
	<p>Below are the details of stock for line E.<br />Products previously sold, but not sold within the last 120 days.</p>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name </strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind </strong></td>
        <td style="border-bottom:1px solid #aaaaaa"><strong>Position (Quantity)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Position (Orders)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Supplier Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Current Price</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Optimum Qty</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Stocked</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Sold</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Count</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Stock Value</strong> </td>
	  </tr>

		<?php
		$totalValue = 0;

		foreach($dataArr as $k => $v) {
			$item = $dataArr[$k];

			if(!$item['Component']) {
				if(!isset($virtualProducts[$item['Product_ID']])) {

					$used[$item['Product_ID']] = true;

					$periodDays = 120;
					$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On<ADDDATE(Now(), -%d)
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", mysql_real_escape_string($item['Product_ID']), $periodDays));

					$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc",mysql_real_escape_string($item['Product_ID'])));

				if(($item['Component'])) {
					$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity * pc.Component_Quantity) AS qty
												FROM orders AS o
												INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID
												INNER JOIN product_components AS pc ON ol.Product_ID=pc.Component_Of_Product_ID
												WHERE pc.Product_ID=%d
												AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')",
												mysql_real_escape_string($item['Product_ID']), $periodDays));
				}

				$qtyOptimum = ceil(($data->Row['qty'] + ($item['Component'] ? $data2->Row['qty'] : 0)) / ($periodDays/30));
				$qtyStock = $item['Quantity_In_Stock'];

				if($qtyOptimum > $qtyStock) {

					if(($qtyOptimum > 0) && ($qtyStock == 0)) {
						$class = 'class="darkred"';
					} elseif(($qtyStock <= ($qtyOptimum * 0.5))) {
						$class = 'class="darkred"';
					} else {
						$class = 'class="red"';
					}
				} else {
					if(($qtyStock >= ($qtyOptimum * 3))) {
						$class = 'class="verydarkgreen"';
					} elseif(($qtyStock >= ($qtyOptimum * 1.5))) {
						$class = 'class="darkgreen"';
					} else {
						$class = 'class="green"';
					}
				}
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td <?php print ($item['Component']) ? 'class="component"' : ''; ?>><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
					<td align="right"><?php echo $item['Product_ID']; ?></td>
                    <td><?php echo $item['Position_Quantities']; ?></td>
					<td><?php echo $item['Position_Orders']; ?></td>
					<td align="right"><?php echo (empty($item['Cost']))?"N/A":"&pound;".$item['Cost']; ?></td>
					<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
				<td align="right"><?php print $qtyOptimum; ?></td>
				<td align="right" <?php print $class; ?>><?php echo $item['Quantity_In_Stock']; ?></td>
				<td align="right"><?php echo ($data->Row['qty'] + (($item['Component']) ? $data2->Row['qty'] : 0)); ?></td>
				<td align="right"><?php echo ($data->Row['count'] + (($item['Component']) ? $data2->Row['count'] : 0)); ?></td>
					<td align="right">&pound;<?php echo number_format(((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']),2,'.',','); ?></td>
					</tr>

					<?php
				if(($item['Component'])) {
					$data2->Disconnect();
				}

					$totalValue += ((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']);

					$priceFind->Disconnect();

					$data->Disconnect();
				}
			}
		}

		foreach($dataArr as $k => $v) {
			$item = $dataArr[$k];

			if(!$item['Component']) {
				if(isset($virtualProducts[$item['Product_ID']])) {

					$used[$item['Product_ID']] = true;

					$periodDays = 120;
					$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID WHERE ol.Product_ID=%d AND o.Created_On<ADDDATE(Now(), -%d)
												AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", mysql_real_escape_string($item['Product_ID']), $periodDays));

					$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc",mysql_real_escape_string($item['Product_ID'])));
					?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td <?php print ($item['Component']) ? 'class="component"' : ''; ?>><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
				<td align="right"><?php echo $item['Product_ID']; ?></td>
                <td><?php echo $item['Position_Quantities']; ?></td>
				<td><?php echo $item['Position_Orders']; ?></td>
				<td align="right"><?php echo (empty($item['Cost']))?"N/A":"&pound;".$item['Cost']; ?></td>
				<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
				<td align="right"><?php print $qtyOptimum; ?></td>
				<td align="right" <?php print $class; ?>><?php echo $item['Quantity_In_Stock']; ?></td>
				<td align="right"><?php echo ($data->Row['qty'] + (($item['Component']) ? $data2->Row['qty'] : 0)); ?></td>
				<td align="right"><?php echo ($data->Row['count'] + (($item['Component']) ? $data2->Row['count'] : 0)); ?></td>
					<td align="right">&pound;<?php echo number_format(((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']),2,'.',','); ?></td>
					</tr>

					<?php
					$totalValue += ((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']);

					$priceFind->Disconnect();

					$data->Disconnect();
				}
			}
		}
		?>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td colspan="11"><strong>Total Stock Value</strong></td>
			<td align="right"><strong>&pound;<?php print number_format($totalValue,2,'.',','); ?></strong></td>
		</tr>
	</table><br />

	<?php
	$counter = 0;
	$dataArr = array();
	$products = '';
	foreach($productArr as $k => $v) {
		if(!isset($used[$k])) {
			$products .= sprintf('ws.Product_ID=%d OR ', $k);
			$counter++;
		}
	}
	if($counter > 0) {
		$products = substr($products, 0, -4);

		$data = new DataQuery(sprintf("SELECT ws.Quantity_In_Stock, ws.Product_ID,
									p.Product_Title, pc.Component_Quantity, sp.Cost
									FROM warehouse_stock ws
									INNER JOIN warehouse w ON ws.Warehouse_ID = w.Warehouse_ID
									INNER JOIN users u ON w.Type_Reference_ID = u.Branch_ID
									INNER JOIN product p ON ws.Product_ID = p.Product_ID
									LEFT JOIN product_components AS pc ON pc.Product_ID=p.Product_ID
									LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
									WHERE w.Type='B' AND u.User_ID=%d AND p.Discontinued<>'Y'
									AND (%s)
									AND sp.Preferred_Supplier='Y'
									GROUP BY p.Product_ID Order By (sp.Cost*ws.Quantity_In_Stock) DESC", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($products)));

		while($data->Row) {
			$item = array();
			$item['Product_ID'] = $data->Row['Product_ID'];
			$item['Product_Title'] = strip_tags($data->Row['Product_Title']);
			$item['Quantity_In_Stock'] = $data->Row['Quantity_In_Stock'];
			$item['Component'] = empty($data->Row['Component_Quantity']) ? false : true;
			$item['Cost'] = $data->Row['Cost'];

			$dataArr[$data->Row['Product_ID']] = $item;

			$data->Next();
		}
		$data->Disconnect();
	}
	?>

	<h3>Stock Line F</h3>
	<p>Below are the details of stock for line F.<br />Products never sold since October 2006.</p>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name </strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind </strong></td>
        <td style="border-bottom:1px solid #aaaaaa"><strong>Position (Quantity)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Position (Orders)</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Supplier Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Current Price</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Optimum Qty</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Stocked</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Stock Value</strong> </td>
	  </tr>

		<?php
		$totalValue = 0;

		foreach($dataArr as $k => $v) {
			$item = $dataArr[$k];
			if(!$item['Component']) {
				if(!isset($virtualProducts[$item['Product_ID']])) {
					$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc",mysql_real_escape_string($item['Product_ID'])));
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td <?php print ($item['Component']) ? 'class="component"' : ''; ?>><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
					<td align="right"><?php echo $item['Product_ID']; ?></td>
                    <td><?php echo $item['Position_Quantities']; ?></td>
					<td><?php echo $item['Position_Orders']; ?></td>
					<td align="right"><?php echo (empty($item['Cost']))?"N/A":"&pound;".$item['Cost']; ?></td>
					<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
				<td align="right"><?php print $qtyOptimum; ?></td>
					<td align="right"><?php echo $item['Quantity_In_Stock']; ?></td>
					<td align="right">&pound;<?php echo number_format(((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']),2,'.',','); ?></td>
					</tr>

					<?php
					$totalValue += ((empty($item['Cost'])) ? 0 : $item['Cost']*$item['Quantity_In_Stock']);

					$priceFind->Disconnect();
				}
			}
		}

		foreach($dataArr as $k => $v) {
			$item = $dataArr[$k];

			if(!$item['Component']) {
				if(isset($virtualProducts[$item['Product_ID']])) {
					$priceFind = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc",mysql_real_escape_string($item['Product_ID'])));
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td class="virtual"><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
					<td align="right"><?php echo $item['Product_ID']; ?></td>
                    <td><?php echo $item['Position_Quantities']; ?></td>
					<td><?php echo $item['Position_Orders']; ?></td>
					<td align="right">N/A</td>
					<td align="right"><?php echo (empty($priceFind->Row['Price_Base_Our']))?"N/A":"&pound;".$priceFind->Row['Price_Base_Our']; ?></td>
					<td align="right">N/A</td>
					<td align="right">N/A</td>
				<td align="right">N/A</td>
				<td align="right">N/A</td>

					</tr>

					<?php
					$priceFind->Disconnect();
				}
			}
		}
		?>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td colspan="9"><strong>Total Stock Value</strong></td>
			<td align="right"><strong>&pound;<?php print number_format($totalValue,2,'.',','); ?></strong></td>
		</tr>
	</table><br />
		<?php
}

function GetChildIDS($cat){
	$string = "";
	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID = %d",mysql_real_escape_string($cat)));
	while($children->Row){
		$string .= "OR cat.Category_ID = ".$children->Row['Category_ID']." ";
		$string .= GetChildIDS($children->Row['Category_ID']);
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}
?>