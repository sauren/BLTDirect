<?php
ini_set('max_execution_time', '1800');

require_once ('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPrice.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

if($action == 'available') {
	$session->Secure(3);
	available();
	exit();
} elseif($action == 'unavailable') {
	$session->Secure(3);
	unavailable();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function available() {
	if(isset($_REQUEST['pid']) && isset($_REQUEST['supplierid'])) {
		$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($_REQUEST['supplierid']), mysql_real_escape_string($_REQUEST['pid'])));
		if($data->TotalRows > 0) {
			$product = new SupplierProduct($data->Row['Supplier_Product_ID']);
			$product->IsUnavailable = 'N';
			$product->Update();
		} else {
			$product = new SupplierProduct();
			$product->Product->ID = $_REQUEST['pid'];
			$product->Supplier->ID = $_REQUEST['supplierid'];
			$product->IsUnavailable = 'N';
			$product->Add();
		}
		$data->Disconnect();

		redirectTo(sprintf('?variation=%s&status=%s&warehouse=%d', $_REQUEST['variation'], $_REQUEST['status'], $_REQUEST['warehouse']));
	}

	redirectTo('product_search.php');
}

function unavailable() {	
	if(isset($_REQUEST['pid']) && isset($_REQUEST['supplierid'])) {
		$prices = array();
		$suppliers = array();

		$data = new DataQuery(sprintf("SELECT Supplier_ID, Quantity, Cost FROM supplier_product_price WHERE Product_ID=%d AND Supplier_ID=%d ORDER BY Quantity ASC, Supplier_Product_Price_ID ASC", mysql_real_escape_string($_REQUEST['pid']), mysql_real_escape_string($_REQUEST['supplierid'])));
		while($data->Row) {
			if(!isset($prices[$data->Row['Quantity']])) {
				$prices[$data->Row['Quantity']] = array();
			}

			$prices[$data->Row['Quantity']] = $data->Row['Cost'];

			$data->Next();
		}
		$data->Disconnect();

		$supplierProductId = 0;

		$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($_REQUEST['supplierid']), mysql_real_escape_string($_REQUEST['pid'])));
		if($data->TotalRows > 0) {
			$product = new SupplierProduct($data->Row['Supplier_Product_ID']);
			$product->IsUnavailable = 'Y';
			$product->Update();

			$supplierProductId = $product->ID;
		} else {
			$product = new SupplierProduct();
			$product->Product->ID = $_REQUEST['pid'];
			$product->Supplier->ID = $_REQUEST['supplierid'];
			$product->IsUnavailable = 'Y';
			$product->Add();

			$supplierProductId = $product->ID;
		}
		$data->Disconnect();

		foreach($prices as $quantity=>$cost) {
			if($quantity > 1) {
				$price = new SupplierProductPrice();
				$price->Product->ID = $_REQUEST['pid'];
				$price->Supplier->ID = $_REQUEST['supplierid'];
				$price->Quantity = $quantity;
				$price->Cost = 0;
				$price->Reason = 'Not available';
				$price->Add();
			} else {
				$product = new SupplierProduct($supplierProductId);
				$product->Cost = 0;
				$product->Reason = 'Not available';
				$product->Update();
			}
		}

		redirectTo(sprintf('?variation=%s&status=%s&warehouse=%d', $_REQUEST['variation'], $_REQUEST['status'], $_REQUEST['warehouse']));
	}

	redirectTo('product_search.php');
}

function view() {
	$varPendingOrders = (isset($_REQUEST['variation']) && ($_REQUEST['variation'] == 'pendingorders')) ? true : false;

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('variation', 'Variation', 'hidden', '', 'anything', 0, 64);

	$sqlSelect = 'SELECT p.Product_ID, p.Product_Title';
	$sqlFrom = 'FROM product AS p';
	$sqlWhere = '';
	$sqlMisc = 'ORDER BY p.Product_ID ASC';

	$lines = array();
	$suppliers = array();

	if ($varPendingOrders) {
		$arrWhere = array();

		$sqlSelect2 = 'SELECT ol.Product_ID ';
		$sqlFrom2 = 'FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID ';
		$sqlWhere2 = 'WHERE (o.Status LIKE \'Pending\' OR o.Status LIKE \'Partially Despatched\' OR o.Status LIKE \'Packing\') AND o.Is_Security_Risk=\'N\' AND ((o.Total=0) OR (o.Total>0 AND o.TotalTax>0)) AND o.Is_Bidding=\'N\' AND o.Is_Awaiting_Customer=\'N\' ';
		$sqlMisc2 = 'GROUP BY ol.Product_ID ORDER BY ol.Product_ID ASC ';

		if(isset($_REQUEST['status'])) {
			if(strlen($_REQUEST['status']) > 0) {
				switch($_REQUEST['status']) {
					case 'pending':
						$sqlWhere2 .= sprintf("AND o.Status LIKE 'Pending' ");
						break;
					case 'packing':
						$sqlWhere2 .= sprintf("AND o.Status LIKE 'Packing' ");
						break;
					case 'partial':
						$sqlWhere2 .= sprintf("AND o.Status LIKE 'Partially Despatched' ");
						break;
					case 'backordered':
						$sqlWhere2 .= sprintf("AND ol.Line_Status LIKE 'Backordered' ");
						break;
				}
			}
		}

		if(isset($_REQUEST['warehouse'])) {
			if($_REQUEST['warehouse'] > 0) {
				$sqlWhere2 .= sprintf("AND ol.Despatch_From_ID=%d ", mysql_real_escape_string($_REQUEST['warehouse']));
			}
		}

		$data = new DataQuery($sqlSelect2.$sqlFrom2.$sqlWhere2.$sqlMisc2);
		while ($data->Row) {
			$arrWhere[] = $data->Row['Product_ID'];
			
			$data->Next();
		}
		$data->Disconnect();
		
		if(count($arrWhere) > 0) {
			$sqlWhere .= sprintf('WHERE (p.Product_ID=%s) ', implode(' OR p.Product_ID=', $arrWhere));
		} else {
			$sqlWhere .= sprintf('WHERE 0=1 ');
		}
	}

	if(isset($_REQUEST['warehouse']) && ($_REQUEST['warehouse'] > 0)) {
		$warehouse = new Warehouse($_REQUEST['warehouse']);
	}

	$data = new DataQuery("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier, o.Org_Name, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact, w.Warehouse_ID FROM control_supplier AS cs INNER JOIN supplier AS s ON s.Supplier_ID=cs.Supplier_ID INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN warehouse AS w ON w.Type_Reference_ID=s.Supplier_ID AND w.Type='S'");
	if ($data->TotalRows > 0) {
		$i = 0;
		$arrWhere = array();
		
		while ($data->Row) {
			$supplierItem = $data->Row;
			$supplierItem['Index'] = $i;
			
			$suppliers[] = $supplierItem;
			
			$sqlSelect .= sprintf(", sp%d.Cost AS Cost_%d, sp%d.Is_Supplied AS Is_Supplied_%d", $i, $i, $i, $i);
			$sqlFrom .= sprintf(" LEFT JOIN supplier_product AS sp%d ON sp%d.Product_ID=p.Product_ID AND sp%d.Supplier_ID=%d", $i, $i, $i,  mysql_real_escape_string($data->Row['Supplier_ID']));
			
			$arrWhere[] = sprintf("(sp%d.Supplier_ID IS NULL OR sp%d.Cost=0.00 AND sp%d.Is_Supplied='Y')", $i, $i, $i);
			
			$data->Next();
			$i++;
		}
		
		$sqlWhere .= sprintf('%s (%s)', (strlen($sqlWhere) > 0) ? ' AND' : 'WHERE', implode(' OR ', $arrWhere));
	}

	$supplierCount = $data->TotalRows;
	$data->Disconnect();

	if($supplierCount > 0) {
		$data = new DataQuery(sprintf('%s %s %s %s', $sqlSelect, $sqlFrom, $sqlWhere, $sqlMisc));
		while ($data->Row) {
			$lines[] = $data->Row;
			
			foreach ($suppliers as $supplier) {
				$form->AddField(sprintf('product_%d_supplied_%d', $data->Row['Product_ID'], $supplier['Index']), sprintf('Is %s supplied for %s', strip_tags($data->Row['Product_Title']), $supplier['Supplier']), 'checkbox', ($data->Row[sprintf('Is_Supplied_%d', $supplier['Index'])] != 'N') ? 'Y' : 'N', 'boolean', 1, 1, false);
				$form->AddField(sprintf('product_%d_cost_%d', $data->Row['Product_ID'], $supplier['Index']), sprintf('Cost of %s for %s', strip_tags($data->Row['Product_Title']), $supplier['Supplier']), 'text', number_format($data->Row[sprintf('Cost_%d', $supplier['Index'])], 2, '.', ''), 'float', 1, 11, true, 'size="3"');
			}
			
			$data->Next();
		}
		$data->Disconnect();
		
		if (isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == "true")) {
			if ($form->Validate()) {
				foreach ($lines as $line) {
					$suppliersChanged = false;
					
					foreach ($suppliers as $supplier) {
						$newValue = (string) number_format(trim($form->GetValue(sprintf('product_%d_cost_%d', $line['Product_ID'], $supplier['Index']))), 2, '.', '');
						$oldValue = (string) number_format(trim((strlen($line[sprintf('Cost_%s', $supplier['Index'])]) > 0) ? $line[sprintf('Cost_%s', $supplier['Index'])] : '0.00'), 2, '.', '');
						
						if ($newValue != $oldValue) {
							$suppliersChanged = true;
							
							$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $supplier['Supplier_ID'], $line['Product_ID']));
							if ($data->TotalRows == 0) {
								$supplierProduct = new SupplierProduct();
								$supplierProduct->Cost = $newValue;
								$supplierProduct->IsSupplied = $form->GetValue(sprintf('product_%d_supplied_%d', $line['Product_ID'], $supplier['Index']));
								$supplierProduct->Supplier->ID = $supplier['Supplier_ID'];
								$supplierProduct->Product->ID = $line['Product_ID'];
								$supplierProduct->Add();
							} else {
								$supplierProduct = new SupplierProduct($data->Row['Supplier_Product_ID']);
								$supplierProduct->Cost = $newValue;
								$supplierProduct->IsSupplied = $form->GetValue(sprintf('product_%d_supplied_%d', $line['Product_ID'], $supplier['Index']));
								$supplierProduct->Update();
							}
							$data->Disconnect();
						}

						if ($form->GetValue(sprintf('product_%d_supplied_%d', $line['Product_ID'], $supplier['Index'])) != (($line[sprintf('Is_Supplied_%d', $supplier['Index'])] != 'N') ? 'Y' : 'N')) {
							$suppliersChanged = true;
							
							$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $supplier['Supplier_ID'], $line['Product_ID']));
							if ($data->TotalRows == 0) {
								$supplierProduct = new SupplierProduct();
								$supplierProduct->Cost = $newValue;
								$supplierProduct->IsSupplied = $form->GetValue(sprintf('product_%d_supplied_%d', $line['Product_ID'], $supplier['Index']));
								$supplierProduct->Supplier->ID = $supplier['Supplier_ID'];
								$supplierProduct->Product->ID = $line['Product_ID'];
								$supplierProduct->Add();
							} else {
								$supplierProduct = new SupplierProduct($data->Row['Supplier_Product_ID']);
								$supplierProduct->Cost = $newValue;
								$supplierProduct->IsSupplied = $form->GetValue(sprintf('product_%d_supplied_%d', $line['Product_ID'], $supplier['Index']));
								$supplierProduct->Update();
							}
							$data->Disconnect();
						}
					}
					
					if ($suppliersChanged) {
						$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Product_ID=%d AND Cost>0.00 AND Is_Supplied='Y' ORDER BY Cost ASC LIMIT 0, 1", $line['Product_ID']));
						if ($data->TotalRows > 0) {
							$supplierProduct = new SupplierProduct($data->Row['Supplier_Product_ID']);
							$supplierProduct->PreferredSup = 'Y';
							$supplierProduct->Update();
						}
						$data->Disconnect();
					}
				}
				
				if (strlen($form->GetValue('variation')) > 0) {
					redirect(sprintf("Location: %s?variation=%s", $_SERVER['PHP_SELF'], $form->GetValue('variation')));
				} else {
					redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
				}
			}
		}
	}

	$page = new Page('Supplier Control', 'Enter supplier details per products on mass here.');
	$page->Display('header');

	$window = new StandardWindow('Update supplier prices');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('variation');

	if (!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	if ($supplierCount == 0) {
		echo '<p>There are no suppliers set up for displaying product cost prices.</p>';
	} else {
		if (count($lines) > 0) {
			?>

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th nowrap>Product</th>
				<th class="dataHeadOrdered" nowrap align="center">Quickfind</th>
						
				<?php
				foreach ($suppliers as $supplier) {
					echo sprintf('<th nowrap colspan="2">%s</th>', (strlen($supplier['Org_Name']) > 0) ? $supplier['Org_Name'] : $supplier['Contact']);

					if(isset($_REQUEST['warehouse']) && ($_REQUEST['warehouse'] > 0)) {
						echo '<th width="1%"></th>';
					}
				}
				?>
			</tr>
		</thead>
		<tbody>
				
			<?php
			foreach ($lines as $line) {
				?>
						
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $line['Product_Title']; ?></td>
					<td class="dataOrdered" align="center"><a href="product_profile.php?pid=<?php echo $line['Product_ID']; ?>"><?php echo $line['Product_ID']; ?></a></td>
								
					<?php
					foreach ($suppliers as $supplier) {
						echo sprintf('<td align="left" nowrap="nowrap">Supplied %s</td>', $form->GetHTML(sprintf('product_%d_supplied_%d', $line['Product_ID'], $supplier['Index'])));
						echo sprintf('<td align="right" nowrap="nowrap">&pound;%s</td>', $form->GetHTML(sprintf('product_%d_cost_%d', $line['Product_ID'], $supplier['Index'])));

						if(isset($_REQUEST['warehouse']) && ($_REQUEST['warehouse'] > 0)) {
							if($supplier['Warehouse_ID'] > 0) {
								$supplierProduct = new SupplierProduct();
					
								if($supplierProduct->GetBySupplierProduct($supplier['Supplier_ID'], $line['Product_ID']) && $supplierProduct->IsUnavailable == 'Y') {
									echo sprintf('<td><a href="?action=available&variation=%s&status=%s&warehouse=%d&pid=%d&supplierid=%d"><img border="0" src="images/button-money.gif" /></a></td>', isset($_REQUEST['variation']) ? $_REQUEST['variation'] : '', isset($_REQUEST['status']) ? $_REQUEST['status'] : '', isset($_REQUEST['warehouse']) ? $_REQUEST['warehouse'] : '', $line['Product_ID'], $supplierProduct->Supplier->ID);
								} else {
									echo sprintf('<td><a href="?action=unavailable&variation=%s&status=%s&warehouse=%d&pid=%d&supplierid=%d"><img border="0" src="images/button-na.gif" /></a></td>', isset($_REQUEST['variation']) ? $_REQUEST['variation'] : '', isset($_REQUEST['status']) ? $_REQUEST['status'] : '', isset($_REQUEST['warehouse']) ? $_REQUEST['warehouse'] : '', $line['Product_ID'], $supplierProduct->Supplier->ID);
								}
							} else {
								echo '<td></td>';		
							}
						}
					}
					?>
				</tr>
				
				<?php
			}
			?>

		</tbody>
	</table>

	<br />

	<input type="submit" class="btn" value="update" name="report" />

	<?php
		} else {
			echo '<p>There are no products to display cost prices within pending orders.</p>';
		}
	}

	echo $form->Close();

	$page->Display('footer');
}