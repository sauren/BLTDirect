<?php
require_once('lib/common/app_header.php');

if($action == 'report') {
	$session->Secure(3);
	report();
	exit();
} elseif($action == 'replenish') {
	$session->Secure(3);
	replenish();
	exit();
} elseif($action == 'finished') {
	$session->Secure(2);
	finished();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function finished() {
	$page = new Page('Stock Replenish Report');
	$page->Display('header');

	echo "<p>Replenishment complete. <a href=\"".$_SERVER['PHP_SELF']."\">Click here</a> to replenish another selection.</p>";

	$page->Display('footer');
}

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);
	$form->AddField('period', 'Replenish Period', 'select', 1, 'numeric_unsigned', 1, 11);
	$form->AddOption('period', 1, '1 Month');
	$form->AddOption('period', 2, '2 Month');
	$form->AddOption('period', 3, '3 Month');
	$form->AddField('backtrack', 'Backtrack Period', 'select', 1, 'numeric_unsigned', 1, 11);
	$form->AddOption('backtrack', 1, '1 Month');
	$form->AddOption('backtrack', 2, '2 Month');
	$form->AddOption('backtrack', 3, '3 Month');
	$form->AddField('reorder', 'Reorder Quantities', 'select', 10, 'numeric_unsigned', 1, 11);
	$form->AddOption('reorder', 6, 6);
	for($i = 10; $i<=100; $i=$i+10) {
		$form->AddOption('reorder', $i, $i);
	}

	$form->AddField('supplier', 'Supplier', 'select', 0, 'numeric_unsigned', 1, 11);
	$form->AddOption('supplier', 0, '-- All --');

	$sql = sprintf("SELECT s.Supplier_ID, p.Name_First,p.Name_Last, o.Org_Name FROM supplier s
					INNER JOIN contact c on s.Contact_ID =  c.Contact_ID
					INNER JOIN person p on c.Person_ID = p.Person_ID
					LEFT JOIN contact c2 on c2.Contact_ID = c.Parent_Contact_ID
					LEFT JOIN organisation o on c2.Org_ID = o.Org_ID");

	$data = new DataQuery($sql);
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], (strlen($data->Row['Org_Name']) > 0) ? $data->Row['Org_Name'] : sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last']));
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			redirect(sprintf("Location: %s?action=report&cat=%d&sub=%s&backtrack=%d&period=%d&reorder=%d&supplier=%d", $_SERVER['PHP_SELF'], $form->GetValue('parent'),$form->GetValue('subfolders'), $form->GetValue('backtrack'), $form->GetValue('period'), $form->GetValue('reorder'), $form->GetValue('supplier')));
		}
	}

	$page = new Page('Stock Replenish Report', 'Please select a product category to report on.');
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
	echo $window->AddHeader('Select replenishment details for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('backtrack'), $form->GetHTML('backtrack'));
	echo $webForm->AddRow($form->GetLabel('period'), $form->GetHTML('period'));
	echo $webForm->AddRow($form->GetLabel('reorder'), $form->GetHTML('reorder'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Select a supplier for this stock report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page('Stock Replenish Report');
	$page->Display('header');

	$cat = $_REQUEST['cat'];
	$sub = ($_REQUEST['sub'] == 'Y') ? true : false;
	$backtrack = $_REQUEST['backtrack'];
	$period = $_REQUEST['period'];
	$reorder = $_REQUEST['reorder'];
	$supplier = $_REQUEST['supplier'];

	$tableString = "";
	$clientString = "";

	if($cat != 0) {
		if($sub) {
			$clientString .= sprintf("AND (cat.Category_ID=%d %s) ", $cat, GetChildIDS($cat));
		} else {
			$clientString .= sprintf("AND (cat.Category_ID=%d) ", $cat);
		}
	} else {
		if(!$sub) {
			$clientString .= sprintf("AND (cat.Category_ID=%d) ", $cat);
		}
	}

	if($supplier > 0) {
		$tableString .= "INNER JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID";
		$clientString .= sprintf("AND sp.Preferred_Supplier='Y' AND sp.Supplier_ID=%d ", $supplier);
	}

	$data = new DataQuery(sprintf("SELECT ws.Product_ID, COUNT(c.Product_ID) AS Components
							FROM warehouse_stock ws
							INNER JOIN warehouse w ON ws.Warehouse_ID = w.Warehouse_ID
							INNER JOIN users u ON w.Type_Reference_ID = u.Branch_ID
							INNER JOIN product p ON ws.Product_ID = p.Product_ID
							LEFT JOIN product_in_categories AS cat ON p.Product_ID=cat.Product_ID
							LEFT JOIN product_components AS c ON c.Component_Of_Product_ID=p.Product_ID
							%s
							WHERE w.Type='B' AND u.User_ID=%d AND p.Discontinued<>'Y'
							%sGROUP BY p.Product_ID", mysql_real_escape_string($tableString), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), $clientString));

	$products = '';
	$virtualProducts = array();

	$counter = 0;

	while($data->Row) {
		$products .= sprintf('ws.Product_ID=%d OR ', $data->Row['Product_ID']);
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

	$dataArr = array();

	$itemArr = array();
	if($counter > 0) {

		$data = new DataQuery(sprintf("SELECT ws.Quantity_In_Stock, ws.Product_ID,
								p.Product_Title, pc.Component_Quantity, sp.Cost, g.Org_Name, ps.Name_First, ps.Name_Last
								FROM warehouse_stock ws
								INNER JOIN warehouse w ON ws.Warehouse_ID = w.Warehouse_ID
								INNER JOIN users u ON w.Type_Reference_ID = u.Branch_ID
								INNER JOIN product p ON ws.Product_ID = p.Product_ID
								INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID
								INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID
								LEFT JOIN product_components AS pc ON pc.Product_ID=p.Product_ID
								LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
								LEFT JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID
								LEFT JOIN contact AS c ON c.Contact_ID=s.Contact_ID
								LEFT JOIN person ps on c.Person_ID = ps.Person_ID
								LEFT JOIN contact c2 on c2.Contact_ID = c.Parent_Contact_ID
								LEFT JOIN organisation AS g ON g.Org_ID=c2.Org_ID
								WHERE w.Type='B' AND u.User_ID=%d AND p.Discontinued<>'Y'
								AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
								AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
								AND (%s)
								AND sp.Preferred_Supplier='Y'
								GROUP BY p.Product_ID ORDER BY g.Org_Name ASC", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), ($backtrack*30), mysql_real_escape_string($products)));

		while($data->Row) {
			$item = array();
			$item['Product_ID'] = $data->Row['Product_ID'];
			$item['Product_Title'] = strip_tags($data->Row['Product_Title']);
			$item['Quantity_In_Stock'] = $data->Row['Quantity_In_Stock'];
			$item['Component'] = empty($data->Row['Component_Quantity']) ? false : true;
			$item['Cost'] = $data->Row['Cost'];
			$item['Supplier'] = (strlen($data->Row['Org_Name']) > 0) ? $data->Row['Org_Name'] : sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last']);

			$itemArr[$data->Row['Product_ID']] = $item;

			$data->Next();
		}
		$data->Disconnect();
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'replenish', 'alpha', 1, 20);
	$form->SetValue('action', 'replenish');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cat', 'Category', 'hidden', $cat, 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Sub Folders', 'hidden', $sub, 'alpha', 1, 1);
	$form->AddField('backtrack', 'Backtrack', 'hidden', $backtrack, 'numeric_unsigned', 1, 11);
	$form->AddField('period', 'Period', 'hidden', $period, 'numeric_unsigned', 1, 11);
	$form->AddField('reorder', 'Reorder', 'hidden', $reorder, 'numeric_unsigned', 1, 11);

	$totalReorderValue = 0;
	$productArray = array();

	foreach($itemArr as $item) {
		if(!isset($virtualProducts[$item['Product_ID']])) {
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity) AS qty
											FROM orders AS o
											INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID
											WHERE ol.Product_ID=%d
											AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
											AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')",
											mysql_real_escape_string($item['Product_ID']), ($backtrack*30)));


			if(($item['Component'])) {
				$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS count, SUM(ol.Quantity * pc.Component_Quantity) AS qty
											FROM orders AS o
											INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID
											INNER JOIN product_components AS pc ON ol.Product_ID=pc.Component_Of_Product_ID
											WHERE pc.Product_ID=%d
											AND o.Created_On BETWEEN ADDDATE(Now(), -%d) AND Now()
											AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')",
											mysql_real_escape_string($item['Product_ID']), ($backtrack*30)));
			}

			$qtyOptimum = (ceil(($data->Row['qty'] + ($item['Component'] ? $data2->Row['qty'] : 0)) / $backtrack) * $period);
			$qtyStock = $item['Quantity_In_Stock'];

			if($qtyOptimum > $qtyStock) {
				$toOrder = $qtyOptimum - $qtyStock;
				if($toOrder < $reorder) {
					$toOrder = $reorder;
				} else {
					$toOrder = (ceil($toOrder/$reorder)) * $reorder;
				}

				$productItem = array();
				$productItem['Product_ID'] = $item['Product_ID'];
				$productItem['Product_Title'] = $item['Product_Title'];
				$productItem['Supplier'] = $item['Supplier'];
				$productItem['Cost'] = $item['Cost'];
				$productItem['Quantity_Optimum'] = $qtyOptimum;
				$productItem['Quantity_In_Stock'] = $item['Quantity_In_Stock'];
				$productItem['Order_Quantity'] = $toOrder;
				$productItem['Cost'] = $item['Cost'];
				$productItem['Difference'] = $qtyOptimum - $qtyStock;

				$form->AddField('replenish_product_'.$item['Product_ID'], 'Replenish Product '.$item['Product_Title'], 'hidden', $item['Product_ID'], 'numberic_unsigned', 1, 11, true);
				$form->AddField('replenish_quantity_'.$item['Product_ID'], 'Replenish Quantity for '.$item['Product_Title'], 'select', $toOrder, 'numberic_unsigned', 1, 11, true);

				for($i=1; $i<=$toOrder; $i++) {
					$form->AddOption('replenish_quantity_'.$item['Product_ID'], $i, $i);
				}

				$max = 100;
				$limit = ($toOrder > $max) ? $toOrder : $max;

				for($i=$toOrder+$reorder; $i<=$limit; $i=$i+10) {
					$form->AddOption('replenish_quantity_'.$item['Product_ID'], $i, $i);
				}

				$productArray[] = $productItem;

				$totalReorderValue += $toOrder*$item['Cost'];
			}

			if(($item['Component'])) {
				$data2->Disconnect();
			}
		}

		$data->Disconnect();
	}

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cat');
	echo $form->GetHTML('sub');
	echo $form->GetHTML('backtrack');
	echo $form->GetHTML('period');
	echo $form->GetHTML('reorder');
	?>
	<br />
	<h3>Stock To Replenish</h3>
	<p>Stock details for the last <?php print ($backtrack * 30); ?> days where optimum quantity for <?php print ($period * 30); ?> days is greater than the stock count.</p>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="center">&nbsp;</td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Product Name </strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Quickfind </strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Supplier</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Supplier Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Optimum Qty</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Qty Stocked</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Reorder Quantity</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Reorder Value</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="center"><strong>Warehouse Stocked</strong> </td>
	  </tr>

		<?php
		foreach($productArray as $item) {
			 echo $form->GetHTML('replenish_product_'.$item['Product_ID']);
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="center"><input type="checkbox" name="replenish_active_<?php echo $item['Product_ID']; ?>" checked="checked" /></td>
				<td align="left" nowrap="nowrap"><a target="_blank" href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>" style="color: #000;"><?php echo $item['Product_Title']; ?></a></td>
				<td align="left"><?php echo $item['Product_ID']; ?></td>
				<td align="left" nowrap="nowrap"><?php echo $item['Supplier']; ?></td>
				<td align="right">&pound;<?php echo $item['Cost']; ?></td>
				<td align="right"><?php echo $item['Quantity_Optimum']; ?></td>
				<td align="right"><?php echo $item['Quantity_In_Stock']; ?></td>
				<td align="right"><?php echo $form->GetHTML('replenish_quantity_'.$item['Product_ID']); ?></td>
				<td align="right">&pound;<?php echo number_format(($item['Order_Quantity']*$item['Cost']), 2, '.', ','); ?></td>
				<td align="center"><input type="checkbox" name="warehouse_stocked_<?php echo $item['Product_ID']; ?>" checked="checked" /></td>
			</tr>

			<?php
		}
		?>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td colspan="9"><strong>Total Reorder Value</strong></td>
			<td align="right"><strong>&pound;<?php print number_format($totalReorderValue,2,'.',','); ?></strong></td>
			<td>&nbsp;</td>
		</tr>
	</table><br />

	<input type="submit" value="replenish stock" name="replenishstock" class="btn" />

	<?php
	echo $form->Close();

	echo $page->Display('footer');
}

function replenish() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

	$products = array();
	$purchases = array();

	$data = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM users AS u INNER JOIN warehouse w ON w.Type_Reference_ID=u.Branch_ID AND w.Type='B' WHERE u.User_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	$warehouseId = $data->Row['Warehouse_ID'];
	$data->Disconnect();

	$compStr = 'replenish_active_';

	foreach($_REQUEST as $key=>$request) {
		if(strlen($key) > strlen($compStr)) {
			if(substr($key, 0, strlen($compStr)) == $compStr) {
				$products[$_REQUEST['replenish_product_' . substr($key, strlen($compStr), strlen($key))]] = $_REQUEST['replenish_quantity_' . substr($key, strlen($compStr), strlen($key))];
			}
		}
	}

	if($warehouseId > 0) {
		$compStr = 'replenish_product_';

		foreach($_REQUEST as $key=>$request) {
			if(strlen($key) > strlen($compStr)) {
				if(substr($key, 0, strlen($compStr)) == $compStr) {
					$productId = substr($key, strlen($compStr), strlen($key));

					if(!isset($_REQUEST['warehouse_stocked_'.$productId])) {
						if(isset($products[$productId])) {
							unset($products[$productId]);
						}

						new DataQuery(sprintf("DELETE FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", mysql_real_escape_string($warehouseId), mysql_real_escape_string($productId)));
					}
				}
			}
		}
	}

	$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse w INNER JOIN users u ON u.Branch_ID=w.Type_Reference_ID WHERE w.Type='B' AND u.User_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	$warehouse = $data->Row['Warehouse_ID'];
	$data->Disconnect();

	$user = new User($GLOBALS['SESSION_USER_ID']);

	foreach($products as $product=>$quantity) {
		$data = new DataQuery(sprintf("SELECT s.Supplier_ID, sp.Cost FROM product AS p
								INNER JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
								INNER JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID
								WHERE p.Product_ID=%d
								AND sp.Preferred_Supplier='Y'", mysql_real_escape_string($product)));

		if($data->TotalRows > 0) {
			if(!isset($purchases[$data->Row['Supplier_ID']])) {
				$supplier = new Supplier($data->Row['Supplier_ID']);
				$supplier->Contact->Get();

				$purchase = new Purchase();
				$purchase->SupplierID = $supplier->ID;
				$purchase->PurchasedOn = getDatetime();
				$purchase->Status = 'Unfulfilled';
				$purchase->PSID = 0;
				$purchase->Branch = $user->Branch->ID;
				$purchase->Warehouse->ID = $warehouse;
				$purchase->Postage = 0;
				$purchase->Supplier = $supplier->Contact->Person;
				$purchase->SupOrg = ($supplier->Contact->HasParent)? $supplier->Contact->Parent->Organisation->Name: '';
				$purchase->Person = $user->Person;
				$purchase->Organisation = $user->Branch->Name;

				$data2 = new DataQuery(sprintf("SELECT o.Fax FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.Person_ID=%d", mysql_real_escape_string($supplier->Contact->Person->ID)));
				$purchase->Supplier->Fax = $data2->Row['Fax'];
				$data2->Disconnect();

				$purchase->Add();

				$purchases[$data->Row['Supplier_ID']] = $purchase;
			}

			$purchases[$data->Row['Supplier_ID']]->AddLine($product, $quantity);

			foreach($purchases[$data->Row['Supplier_ID']]->Line as $line) {
				if($line->Product->ID == $product) {
					$line->Cost = $data->Row['Cost'];
					$line->SuppliedBy = $data->Row['Supplier_ID'];
					$line->Update();

					break;
				}
			}
		}
		$data->Disconnect();
	}

	redirect(sprintf("Location: %s?action=finished", $_SERVER['PHP_SELF']));
}

function GetChildIDS($cat){
	$string = '';
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