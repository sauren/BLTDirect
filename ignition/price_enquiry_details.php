<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquirySupplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiryQuantity.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCollection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCollectionAssoc.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

function getCsv($row, $fd=',', $quot='"') {
	$str ='';

	foreach($row as $cell) {
		$cell = str_replace($quot, $quot.$quot, $cell);

		if((strchr($cell, $fd) !== false) || (strchr($cell, $quot) !== false) || (strchr($cell, "\n") !== false)) {
			$str .= $quot.$cell.$quot.$fd;
		} else {
			$str .= $quot.$cell.$quot.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}

$session->Secure(3);

$priceEnquiry = new PriceEnquiry($_REQUEST['id']);
$priceEnquiry->GetLines();
$priceEnquiry->GetSuppliers();
$priceEnquiry->GetQuantities();

$isEditable = (strtolower($priceEnquiry->Status) != 'complete') ? true : false;

if($isEditable) {
	$priceEnquiry->Recalculate();
}

if($action == "remove") {
	if($isEditable) {
		if(isset($_REQUEST['line'])) {
			$line = new PriceEnquiryLine();
			$line->Delete($_REQUEST['line']);

			$priceEnquiry->LinesFetched = false;
			$priceEnquiry->Recalculate();

		} elseif(isset($_REQUEST['supplier'])) {
			$supplier = new PriceEnquirySupplier();
			$supplier->Delete($_REQUEST['supplier']);

			$priceEnquiry->SuppliersFetched = false;
			$priceEnquiry->Recalculate();

		} elseif(isset($_REQUEST['quantity'])) {
			$supplier = new PriceEnquiryQuantity();
			$supplier->Delete($_REQUEST['quantity']);

			$priceEnquiry->QuantitiesFetched = false;
		}
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID));

} elseif($action == "changestatus") {
	$priceEnquiry->Status = $_REQUEST['status'];
	$priceEnquiry->Update();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID));

} elseif($action == "complete") {
	$priceEnquiry->Status = 'Complete';
	$priceEnquiry->Update();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID));

} elseif($action == "delete") {
	$priceEnquiry->Delete();

	redirect(sprintf("Location: price_enquiries_pending.php"));

} elseif($action == "purchase") {
	$supplierProducts = array();
	$supplierStocked = true;

	for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
		$priceEnquiry->Supplier[$j]->GetCosts();
		$priceEnquiry->Supplier[$j]->GetLines();
	}

	for($i=0; $i<count($priceEnquiry->Line); $i++) {
		$cost = 0;
		$supplierId = 0;

		for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
			if($priceEnquiry->Supplier[$j]->Cost[$i]['Cost'] > 0) {
				if(($supplierId == 0) || ($priceEnquiry->Supplier[$j]->Cost[$i]['Cost'] < $cost)) {
					$cost = $priceEnquiry->Supplier[$j]->Cost[$i]['Cost'];
					$supplierId = $priceEnquiry->Supplier[$j]->Supplier->ID;
				}
			}
		}

		if($supplierId > 0) {
			if(!isset($supplierProducts[$supplierId])) {
				$supplierProducts[$supplierId] = array();
			}

			$supplierProducts[$supplierId][$priceEnquiry->Line[$i]->Product->ID] = array('Product' => $priceEnquiry->Line[$i]->Product, 'Quantity' => $priceEnquiry->Line[$i]->Quantity, 'Cost' => $cost);

			for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
				if($priceEnquiry->Supplier[$j]->Supplier->ID == $supplierId) {
					for($k=0; $k<count($priceEnquiry->Supplier[$j]->Line); $k++) {
						if($priceEnquiry->Supplier[$j]->Line[$k]->PriceEnquiryLineID == $priceEnquiry->Line[$i]->ID) {
							if($priceEnquiry->Supplier[$j]->Line[$k]->IsInStock == 'N') {
								$supplierStocked = false;
							}

							break;
						}
					}

					break;
				}
			}
		}
	}

	if(!$supplierStocked) {
		redirect(sprintf("Location: price_enquiry_purchase.php?id=%d", $priceEnquiry->ID));
	}

	$warehouseId = 0;
	$branchId = 0;

	$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, u.Branch_ID FROM warehouse AS w INNER JOIN users AS u ON u.Branch_ID=w.Type_Reference_ID WHERE w.Type='B' AND u.User_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	if($data->TotalRows > 0) {
		$warehouseId = $data->Row['Warehouse_ID'];
		$branchId = $data->Row['Branch_ID'];
	}
	$data->Disconnect();

	foreach($supplierProducts as $supplierId=>$products) {
		$supplier = new Supplier($supplierId);
		$supplier->Contact->Get();

		$branch = new Branch($branchId);

		$purchase = new Purchase();
		$purchase->PriceEnquiry->ID = $priceEnquiry->ID;
		$purchase->SupplierID = $supplierId;
		$purchase->Status = 'Unfulfilled';
		$purchase->PurchasedOn = date('Y-m-d H:i:s');
		$purchase->Warehouse->ID = $warehouseId;
		$purchase->Branch = $branchId;
		$purchase->Supplier = $supplier->Contact->Person;
		$purchase->SupOrg = ($supplier->Contact->HasParent) ? $supplier->Contact->Parent->Organisation->Name : '';

		$data = new DataQuery(sprintf("SELECT o.Fax FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.Person_ID=%d", mysql_real_escape_string($supplier->Contact->Person->ID)));
		$purchase->Supplier->Fax = $data->Row['Fax'];
		$data->Disconnect();

		$purchase->Person->Address = $branch->Address;
		$purchase->Organisation = $branch->Name;
		$purchase->Add();

		foreach($products as $productId=>$productData) {
			$line = new PurchaseLine();
			$line->Purchase = $purchase->ID;
			$line->Quantity = $productData['Quantity'];
			$line->QuantityDec = $productData['Quantity'];
			$line->Product = $productData['Product'];
			$line->Cost = $productData['Cost'];
			$line->SuppliedBy = $supplierId;

			$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplierId), mysql_real_escape_string($line->Product->ID)));
			if($data->TotalRows > 0) {
				$line->SKU = $data->Row['Supplier_SKU'];
			}
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($purchase->Warehouse->ID), mysql_real_escape_string($line->Product->ID)));
			if($data->TotalRows > 0) {
				$line->Location = $data->Row['Shelf_Location'];
			}
			$data->Disconnect();

			$line->Add();
		}
	}

	redirect(sprintf("Location: purchases_view.php"));

} elseif($action == 'export') {
	if(isset($_REQUEST['supplier'])) {
	    $fileDate = getDatetime();
		$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

		$fileName = sprintf('blt_price_enquiry_%d_%d_%s.csv', $priceEnquiry->ID, $_REQUEST['supplier'], $fileDate);

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename=" . basename($fileName) . ";");
		header("Content-Transfer-Encoding: binary");

		$line = array();
		$line[] = 'Product ID';
		$line[] = 'Product Name';
		$line[] = 'Orders';
		$line[] = 'Quantity';
		$line[] = 'Supplier SKU';
		$line[] = 'Current Price';
		$line[] = 'Last Priced';
		$line[] = 'Total Value';
		$line[] = 'Updated Price';

		echo getCsv($line);

		for($i=0; $i<count($priceEnquiry->Line); $i++) {
			$data = new DataQuery(sprintf("SELECT Supplier_SKU, Cost, DATE(Modified_On) AS Modified_On FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($_REQUEST['supplier']), mysql_real_escape_string($priceEnquiry->Line[$i]->Product->ID)));		

			$line = array();
			$line[] = $priceEnquiry->Line[$i]->Product->ID;
			$line[] = $priceEnquiry->Line[$i]->Product->Name;
			$line[] = $priceEnquiry->Line[$i]->Orders;
			$line[] = $priceEnquiry->Line[$i]->Quantity;
			$line[] = ($data->TotalRows > 0) ? $data->Row['Supplier_SKU'] : '';
			$line[] = ($data->TotalRows > 0) ? $data->Row['Cost'] : 0;
			$line[] = ($data->TotalRows > 0) ? $data->Row['Modified_On'] : '';
			$line[] = ($data->TotalRows > 0) ? $data->Row['Cost']*$priceEnquiry->Line[$i]->Quantity : 0;
			$line[] = '';

			$data->Disconnect();

			echo getCsv($line);
		}

		exit;
	}
}

if(isset($_REQUEST['removeselectedproducts'])) {
	if($isEditable) {
		$line = new PriceEnquiryLine();

		foreach($_REQUEST as $key=>$value) {
			if(preg_match('/^product_select_([\d]*)$/', $key, $matches)) {
				$line->Delete($matches[1]);
			}
		}

		$priceEnquiry->LinesFetched = false;
		$priceEnquiry->Recalculate();
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID));

} elseif(isset($_REQUEST['removeselectedsuppliers'])) {
	if($isEditable) {
		$supplier = new PriceEnquirySupplier();

		foreach($_REQUEST as $key=>$value) {
			if(preg_match('/^supplier_select_([\d]*)$/', $key, $matches)) {
				$supplier->Delete($matches[1]);
			}
		}

		$priceEnquiry->SuppliersFetched = false;
		$priceEnquiry->Recalculate();
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID));

} elseif(isset($_REQUEST['removeselectedquantities'])) {
	if($isEditable) {
		$quantity = new PriceEnquiryQuantity();

		foreach($_REQUEST as $key=>$value) {
			if(preg_match('/^quantity_select_([\d]*)$/', $key, $matches)) {
				$quantity->Delete($matches[1]);
			}
		}

		$priceEnquiry->QuantitiesFetched = false;
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Price Enquiry ID', 'hidden', $priceEnquiry->ID, 'numeric_unsigned', 1, 11);

if($isEditable) {
	for($i=0; $i<count($priceEnquiry->Line); $i++) {
		$form->AddField('product_select_'.$priceEnquiry->Line[$i]->ID, 'Selected Product', 'checkbox', 'N', 'boolean', 1, 1, false);
		$form->AddField('product_quantity_'.$priceEnquiry->Line[$i]->ID, sprintf('Quantity for \'%s\'', $priceEnquiry->Line[$i]->Product->Name), 'text', $priceEnquiry->Line[$i]->Quantity, 'numeric_unsigned', 1, 11, false, 'size="3"');
	}

	for($i=0; $i<count($priceEnquiry->Supplier); $i++) {
		$form->AddField('supplier_select_'.$priceEnquiry->Supplier[$i]->ID, 'Selected Supplier', 'checkbox', 'N', 'boolean', 1, 1, false);
	}

	for($i=0; $i<count($priceEnquiry->Quantity); $i++) {
		$form->AddField('quantity_select_'.$priceEnquiry->Quantity[$i]->ID, 'Selected Quantity', 'checkbox', 'N', 'boolean', 1, 1, false);
		$form->AddField('quantity_quantity_'.$priceEnquiry->Quantity[$i]->ID, sprintf('Quantity for \'%s\'', $priceEnquiry->Quantity[$i]->Quantity), 'text', $priceEnquiry->Quantity[$i]->Quantity, 'numeric_unsigned', 1, 11, false, 'size="3"');
	}
}

$form->AddField('collection', 'Product Collection', 'select', '0', 'numeric_unsigned', 1, 11);
$form->AddOption('collection', '0', '');

$data = new DataQuery(sprintf("SELECT * FROM product_collection ORDER BY Name ASC"));
while($data->Row) {
 	$form->AddOption('collection', $data->Row['ProductCollectionID'], $data->Row['Name']);

	$data->Next();
}
$data->Disconnect();

if(strtolower($priceEnquiry->Status) == 'complete') {
	$form->AddField('status', 'Status', 'select', $priceEnquiry->Status, 'anything', 1, 128, true, 'onchange="changeStatus(this);"');
	$form->AddOption('status', 'Complete', 'Complete');
	$form->AddOption('status', 'Pending', 'Pending');
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		if(isset($_REQUEST['exportproducts'])) {
            $fileDate = getDatetime();
			$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

			$fileName = sprintf('blt_price_enquiry_%d_%s.csv', $priceEnquiry->ID, $fileDate);

			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: application/force-download");
			header("Content-Disposition: attachment; filename=" . basename($fileName) . ";");
			header("Content-Transfer-Encoding: binary");

			$line = array();
			$line[] = 'Product ID';
			$line[] = 'Product Name';
			$line[] = 'Orders';
			$line[] = 'Quantity';

			echo getCsv($line);

			for($i=0; $i<count($priceEnquiry->Line); $i++) {
			$line = array();
				$line[] = $priceEnquiry->Line[$i]->Product->ID;
				$line[] = $priceEnquiry->Line[$i]->Product->Name;
				$line[] = $priceEnquiry->Line[$i]->Orders;
				$line[] = $priceEnquiry->Line[$i]->Quantity;

				echo getCsv($line);
			}

			exit;

		} elseif(isset($_REQUEST['feedcollection'])) {
			$collection = new ProductCollection();

			if($collection->Get($form->GetValue('collection'))) {
				$assoc = new ProductCollectionAssoc();
				$assoc->ProductCollectionID = $collection->ID;

				for($i=0; $i<count($priceEnquiry->Line); $i++) {
					$assoc->ProductID = $priceEnquiry->Line[$i]->Product->ID;
					$assoc->Add();
				}

				redirect(sprintf("Location: product_collection_assoc.php?cid=%d", $collection->ID));
			}

			redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID));

		} else {
			if(isset($_REQUEST['update']) || isset($_REQUEST['updateproducts'])) {
				if($isEditable) {
					for($i=0; $i<count($priceEnquiry->Line); $i++) {
						$priceEnquiry->Line[$i]->Quantity = $form->GetValue('product_quantity_'.$priceEnquiry->Line[$i]->ID);
						$priceEnquiry->Line[$i]->Update();
					}
				}
			}

			if(isset($_REQUEST['update']) || isset($_REQUEST['updatequantities'])) {
				if($isEditable) {
					for($i=0; $i<count($priceEnquiry->Quantity); $i++) {
						$priceEnquiry->Quantity[$i]->Quantity = $form->GetValue('quantity_quantity_'.$priceEnquiry->Quantity[$i]->ID);
						$priceEnquiry->Quantity[$i]->Update();
					}
				}
			}

			if($form->Valid) {
				$priceEnquiry->LinesFetched = false;
				$priceEnquiry->SuppliersFetched = false;
				$priceEnquiry->Recalculate();
				$priceEnquiry->Update();

				redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID));
			}
		}
	}
}

$script = sprintf('<script language="javascript" type="text/javascript">
	var changeStatus = function(obj) {
		window.self.location.href = \'%s?id=%d&action=changestatus&status=\' + obj.value;
	}
	</script>', $_SERVER['PHP_SELF'], $priceEnquiry->ID);

$page = new Page(sprintf('[#%d] Price Enquiry Details', $priceEnquiry->ID), 'Manage this price enquiry here.');
$page->AddToHead($script);
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left" valign="top"></td>
    <td align="right" valign="top">

	    <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
	      <tr>
	        <th>Price Enquiry:</th>
	        <td>#<?php echo $priceEnquiry->ID; ?></td>
	      </tr>
	      <tr>
	        <th>Status:</th>
	        <td><?php echo (strtolower($priceEnquiry->Status) == 'complete') ? $form->GetHTML('status') : $priceEnquiry->Status; ?></td>
	      </tr>
	      <tr>
	        <th>&nbsp;</th>
	        <td>&nbsp;</td>
	      </tr>
	      <tr>
	        <th>Created On:</th>
	        <td><?php echo cDatetime($priceEnquiry->CreatedOn, 'shortdate'); ?></td>
	      </tr>
	      <tr>
	        <th>Created By:</th>
	        <td>
	        	<?php
	        	$user = new User();
	        	$user->ID = $priceEnquiry->CreatedBy;

	        	if($user->Get()) {
	        		echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName));
	        	}
	        	?>
	        	&nbsp;
	        </td>
	      </tr>
	    </table><br />

   </td>
  </tr>
  <tr>
  	<td valign="top">

		<?php
		if($priceEnquiry->Status != 'Complete') {
			echo sprintf('<input name="complete" type="button" value="complete" class="btn" onclick="confirmRequest(\'price_enquiry_details.php?id=%d&action=complete\', \'Please confirm you wish to complete this price enquiry?\');" /> ', $priceEnquiry->ID);
		}

		echo sprintf('<input name="delete" type="button" value="delete" class="btn" onclick="confirmRequest(\'price_enquiry_details.php?id=%d&action=delete\', \'Are you sure you would like to delete this price enquiry permanently?\');" /> ', $priceEnquiry->ID);
		?>

		<br />

  	</td>
  	<td align="right" valign="top">
	  	<?php
		if($isEditable) {
			?>

			<input name="update" type="submit" value="update" class="btn" />

			<?php
		}
		?>
	</td>
  </tr>
  <tr>
    <td colspan="2">
		<br />

		<div style="background-color: #eee; padding: 10px 0 10px 0;">
		 	<p><span class="pageSubTitle">Suppliers</span><br /><span class="pageDescription">Listing suppliers price enquiry is visible for.</span></p>

			<table cellspacing="0" class="orderDetails">
				<tr>

					<?php
					if($isEditable) {
						echo '<th nowrap="nowrap" style="padding-right: 5px;">&nbsp;</th>';
						echo '<th nowrap="nowrap" style="padding-right: 5px;">&nbsp;</th>';
					}
					?>

					<th nowrap="nowrap" style="padding-right: 5px;" width="10%">Position</th>
					<th nowrap="nowrap" style="padding-right: 5px;" width="20%">Supplier</th>
					<th nowrap="nowrap" style="padding-right: 5px;" width="25%">Organisation</th>
					<th nowrap="nowrap" style="padding-right: 5px;" width="10%">Products</th>
					<th nowrap="nowrap" style="padding-right: 5px; text-align: center;" width="10%">Complete</th>
					<th nowrap="nowrap" style="padding-right: 5px; text-align: right;" width="10%">Cost</th>
					<th nowrap="nowrap" style="padding-right: 5px; text-align: right;" width="15%">Difference</th>
					<th nowrap="nowrap" style="width: 1%;">&nbsp;</th>
					<th nowrap="nowrap" style="width: 1%;">&nbsp;</th>
					<th nowrap="nowrap" style="width: 1%;">&nbsp;</th>
				</tr>

				<?php
				$suppliers = array();

				if(count($priceEnquiry->Supplier) > 0) {
					$supplierCosts = array();

		        	for($i=0; $i<count($priceEnquiry->Supplier); $i++) {
		        		$priceEnquiry->Supplier[$i]->Supplier->Get();
		        		$priceEnquiry->Supplier[$i]->Supplier->Contact->Get();
		        		$priceEnquiry->Supplier[$i]->GetCosts();

		        		$cost = $priceEnquiry->Supplier[$i]->GetTotalCost();

		        		$key = number_format($cost, 2, '.', '');
						$key *= 10000;
						$key .= sprintf('%05d', $priceEnquiry->Supplier[$i]->Supplier->ID);

						$supplierCosts[$key] = array();
						$supplierCosts[$key]['Supplier_ID'] = $priceEnquiry->Supplier[$i]->Supplier->ID;
						$supplierCosts[$key]['Total_Cost'] = $cost;
						$supplierCosts[$key]['Object'] = $priceEnquiry->Supplier[$i];
		        	}

		        	ksort($supplierCosts);

					$position = 1;
					$firstCost = 0;

					foreach($supplierCosts as $supplier) {
						for($i=0; $i<count($priceEnquiry->Supplier); $i++) {
							if($priceEnquiry->Supplier[$i]->Supplier->ID == $supplier['Supplier_ID']) {
								$suppliers[] = $supplier['Object'];

								$productsPriced = 0;

								for($j=0; $j<count($supplier['Object']->Cost); $j++) {
									if($supplier['Object']->Cost[$j]['Costed']) {
										$productsPriced++;
									}
								}
								?>

								<tr>
									<?php
									if($isEditable) {
										echo sprintf('<td nowrap="nowrap" width="1%%">%s</td>', $form->GetHTML('supplier_select_'.$priceEnquiry->Supplier[$i]->ID));
										echo sprintf('<td nowrap="nowrap" width="1%%"><a href="javascript:confirmRequest(\'price_enquiry_details.php?id=%d&action=remove&supplier=%d\', \'Are you sure you wish to remove this supplier? This will not affect any existing prices submitted by this supplier.\');"><img align="absmiddle" src="images/icon_trash_1.gif" alt="Remove" border="0" /></a></td>', $priceEnquiry->ID, $priceEnquiry->Supplier[$i]->ID);
									}
									?>

									<td nowrap="nowrap">#<?php echo $position; ?></td>
									<td nowrap="nowrap"><?php echo trim(sprintf('%s %s', $priceEnquiry->Supplier[$i]->Supplier->Contact->Person->Name, $priceEnquiry->Supplier[$i]->Supplier->Contact->Person->LastName)); ?>&nbsp;</td>
									<td nowrap="nowrap"><?php echo $priceEnquiry->Supplier[$i]->Supplier->Contact->Parent->Organisation->Name; ?>&nbsp;</td>
									<td nowrap="nowrap"><?php echo $productsPriced; ?>/<?php echo count($priceEnquiry->Line); ?> (<?php echo (count($priceEnquiry->Line) > 0) ? round(($productsPriced/count($priceEnquiry->Line))*100) : 0; ?>%)</td>
									<td nowrap="nowrap" align="center"><img src="images/<?php echo ($priceEnquiry->Supplier[$i]->IsComplete == 'N') ? 'icon_cross_3.gif' : 'icon_tick_3.gif'; ?>" alt="<?php echo ($priceEnquiry->Supplier[$i]->IsComplete == 'N') ? 'Incomplete' : 'Complete'; ?>" /></td>
									<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($supplier['Total_Cost'], 2), 2, '.', ','); ?></td>
									<td nowrap="nowrap" align="right">+<?php echo number_format(round((($firstCost > 0) ? $supplier['Total_Cost']-$firstCost : 0), 2), 2, '.', ','); ?></td>
									<td nowrap="nowrap" align="right">&nbsp;</td>
									<td nowrap="nowrap" align="right"><a href="supplier_price_enquiry_details.php?id=<?php echo $priceEnquiry->ID; ?>&supplier=<?php echo $priceEnquiry->Supplier[$i]->Supplier->ID; ?>"><img align="absmiddle" src="images/folderopen.gif" alt="View Details" border="0" /></a></td>
									<td nowrap="nowrap" align="right"><a href="price_enquiry_details.php?action=export&id=<?php echo $priceEnquiry->ID; ?>&supplier=<?php echo $priceEnquiry->Supplier[$i]->Supplier->ID; ?>"><img align="absmiddle" src="images/icon_info_1.gif" alt="Export Products" border="0" /></a></td>
								</tr>

								<?php
								$position++;

								if($firstCost == 0) {
									$firstCost = $supplier['Total_Cost'];
								}

								break;
							}
						}
					}

					$totalCost = 0;

					for($i=0; $i<count($priceEnquiry->Line); $i++) {
						$cost = 0;
						$supplierId = 0;

						for($j=0; $j<count($priceEnquiry->Supplier); $j++) {
							if($priceEnquiry->Supplier[$j]->Cost[$i]['Total'] > 0) {
								if(($supplierId == 0) || ($priceEnquiry->Supplier[$j]->Cost[$i]['Total'] < $cost)) {
									$cost = $priceEnquiry->Supplier[$j]->Cost[$i]['Total'];
									$supplierId = $priceEnquiry->Supplier[$j]->Supplier->ID;
								}
							}
						}

						if($supplierId > 0) {
							$totalCost += $cost;
						}
					}
					?>

					<tr>
						<?php
						if($isEditable) {
							echo '<td nowrap="nowrap">&nbsp;</td>';
							echo '<td nowrap="nowrap">&nbsp;</td>';
						}
						?>

						<td nowrap="nowrap">&nbsp;</td>
						<td nowrap="nowrap" colspan="4"><strong>Best Buy</strong></td>
						<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($totalCost, 2), 2, '.', ','); ?></td>
						<td nowrap="nowrap">&nbsp;</td>
						<td nowrap="nowrap" align="right"><a href="javascript:confirmRequest('<?php echo $_SERVER['PHP_SELF']; ?>?action=purchase&id=<?php echo $priceEnquiry->ID; ?>', 'Are you sure you wish to place a purchase for best buy suppliers?');"><img align="absmiddle" src="images/icon_stock.gif" alt="Place Purchase" border="0" /></a></td>
						<td nowrap="nowrap">&nbsp;</td>
						<td nowrap="nowrap">&nbsp;</td>
					</tr>

					<?php
				} else {
			      	?>

			      	<tr>
			      		<td colspan="<?php echo ($isEditable) ? 14 : 12; ?>" align="center">No suppliers available for viewing.</td>
			      	</tr>

			      	<?php
				}
				?>

			</table><br />

			<?php
			if($isEditable) {
				?>

				<table cellspacing="0" cellpadding="0" border="0" width="100%">
					<tr>
						<td align="left">
							<input type="submit" name="removeselectedsuppliers" value="remove selected" class="btn" />
						</td>
						<td align="right">
							<input type="button" name="add suppliers" value="add suppliers" class="btn" onclick="window.location.href='price_enquiry_add_supplier.php?id=<?php echo $priceEnquiry->ID; ?>';" />
						</td>
					</tr>
				</table>

				<?php
			}
			?>

		</div>
		<br />

		<div style="background-color: #eee; padding: 10px 0 10px 0;">
		 	<p><span class="pageSubTitle">Supplier Comparison</span><br /><span class="pageDescription">Comparing suppliers price enquiries where matched products are costed.</span></p>

		 	<?php
		 	$globalCosts = array();

		 	for($k=0; $k<count($priceEnquiry->Line); $k++) {
		 		$hasCost = true;

		 		for($i=0; $i<count($priceEnquiry->Supplier); $i++) {
		 			if(empty($priceEnquiry->Supplier[$i]->Cost[$k]['Items'])) {
		 				$hasCost = false;
		 				break;
		 			}
		 		}

		 		if($hasCost) {
		 			$globalCosts[$k] = true;
		 		}
		 	}
			?>

			<table cellspacing="0" class="orderDetails">
				<tr>

					<?php
					if($isEditable) {
						echo '<th nowrap="nowrap" style="padding-right: 5px;">&nbsp;</th>';
						echo '<th nowrap="nowrap" style="padding-right: 5px;">&nbsp;</th>';
					}
					?>

					<th nowrap="nowrap" style="padding-right: 5px;" width="10%">Position</th>
					<th nowrap="nowrap" style="padding-right: 5px;" width="20%">Supplier</th>
					<th nowrap="nowrap" style="padding-right: 5px;" width="25%">Organisation</th>
					<th nowrap="nowrap" style="padding-right: 5px;" width="10%">Products</th>
					<th nowrap="nowrap" style="padding-right: 5px; text-align: center;" width="10%">Complete</th>
					<th nowrap="nowrap" style="padding-right: 5px; text-align: right;" width="10%">Cost</th>
					<th nowrap="nowrap" style="padding-right: 5px; text-align: right;" width="15%">Difference</th>
					<th nowrap="nowrap" style="width: 1%;">&nbsp;</th>
					<th nowrap="nowrap" style="width: 1%;">&nbsp;</th>
					<th nowrap="nowrap" style="width: 1%;">&nbsp;</th>
				</tr>

				<?php
				if(count($priceEnquiry->Supplier) > 0) {
					$supplierCosts = array();

		        	for($i=0; $i<count($priceEnquiry->Supplier); $i++) {
		        		$cost = 0;

		        		for($k=0; $k<count($priceEnquiry->Line); $k++) {
		        			if(isset($globalCosts[$k])) {
		        				$cost += $priceEnquiry->Supplier[$i]->Cost[$k]['Total'];
		        			}
		        		}

		        		$key = number_format($cost, 2, '.', '');
						$key *= 10000;
						$key .= sprintf('%05d', $priceEnquiry->Supplier[$i]->Supplier->ID);

						$supplierCosts[$key] = array();
						$supplierCosts[$key]['Supplier_ID'] = $priceEnquiry->Supplier[$i]->Supplier->ID;
						$supplierCosts[$key]['Total_Cost'] = $cost;
						$supplierCosts[$key]['Object'] = $priceEnquiry->Supplier[$i];
		        	}

		        	ksort($supplierCosts);

					$position = 1;
					$firstCost = 0;

					foreach($supplierCosts as $supplier) {
						for($i=0; $i<count($priceEnquiry->Supplier); $i++) {
							if($priceEnquiry->Supplier[$i]->Supplier->ID == $supplier['Supplier_ID']) {
								$suppliers[] = $supplier['Object'];
								?>

								<tr>
									<?php
									if($isEditable) {
										echo sprintf('<td nowrap="nowrap" width="1%%">%s</td>', $form->GetHTML('supplier_select_'.$priceEnquiry->Supplier[$i]->ID));
										echo sprintf('<td nowrap="nowrap" width="1%%"><a href="javascript:confirmRequest(\'price_enquiry_details.php?id=%d&action=remove&supplier=%d\', \'Are you sure you wish to remove this supplier? This will not affect any existing prices submitted by this supplier.\');"><img align="absmiddle" src="images/icon_trash_1.gif" alt="Remove" border="0" /></a></td>', $priceEnquiry->ID, $priceEnquiry->Supplier[$i]->ID);
									}
									?>

									<td nowrap="nowrap">#<?php echo $position; ?></td>
									<td nowrap="nowrap"><?php echo trim(sprintf('%s %s', $priceEnquiry->Supplier[$i]->Supplier->Contact->Person->Name, $priceEnquiry->Supplier[$i]->Supplier->Contact->Person->LastName)); ?>&nbsp;</td>
									<td nowrap="nowrap"><?php echo $priceEnquiry->Supplier[$i]->Supplier->Contact->Parent->Organisation->Name; ?>&nbsp;</td>
									<td nowrap="nowrap"><?php echo count($globalCosts); ?>/<?php echo count($priceEnquiry->Line); ?> (<?php echo (count($priceEnquiry->Line) > 0) ? round((count($globalCosts)/count($priceEnquiry->Line))*100) : 0; ?>%)</td>
									<td nowrap="nowrap" align="center"><img src="images/<?php echo ($priceEnquiry->Supplier[$i]->IsComplete == 'N') ? 'icon_cross_3.gif' : 'icon_tick_3.gif'; ?>" alt="<?php echo ($priceEnquiry->Supplier[$i]->IsComplete == 'N') ? 'Incomplete' : 'Complete'; ?>" /></td>
									<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($supplier['Total_Cost'], 2), 2, '.', ','); ?></td>
									<td nowrap="nowrap" align="right">+<?php echo number_format(round((($firstCost > 0) ? $supplier['Total_Cost']-$firstCost : 0), 2), 2, '.', ','); ?></td>
									<td nowrap="nowrap" align="right">&nbsp;</td>
									<td nowrap="nowrap" align="right"><a href="supplier_price_enquiry_details.php?id=<?php echo $priceEnquiry->ID; ?>&supplier=<?php echo $priceEnquiry->Supplier[$i]->Supplier->ID; ?>"><img align="absmiddle" src="images/folderopen.gif" alt="View Details" border="0" /></a></td>
									<td nowrap="nowrap" align="right"><a href="price_enquiry_details.php?action=export&id=<?php echo $priceEnquiry->ID; ?>&supplier=<?php echo $priceEnquiry->Supplier[$i]->Supplier->ID; ?>"><img align="absmiddle" src="images/icon_info_1.gif" alt="Export Products" border="0" /></a></td>
								</tr>

								<?php
								$position++;

								if($firstCost == 0) {
									$firstCost = $supplier['Total_Cost'];
								}

								break;
							}
						}
					}
				} else {
			      	?>

			      	<tr>
			      		<td colspan="<?php echo ($isEditable) ? 14 : 12; ?>" align="center">No suppliers available for viewing.</td>
			      	</tr>

			      	<?php
				}
				?>

			</table><br />

		</div>
		<br />

		<div style="background-color: #eee; padding: 10px 0 10px 0;">
		 	<p><span class="pageSubTitle">Quantities</span><br /><span class="pageDescription">Listing quantities for which suppliers may price against.</span></p>

		 	<table cellspacing="0" class="orderDetails">
				<tr>
			        <?php
					if($isEditable) {
						echo '<th nowrap="nowrap">&nbsp;</th>';
						echo '<th nowrap="nowrap">&nbsp;</th>';
					}
					?>

					<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
				</tr>

				<?php
				if(count($priceEnquiry->Quantity) > 0) {
					for($i=0; $i<count($priceEnquiry->Quantity); $i++) {
						?>

						<tr>
							<?php
							if($isEditable) {
								echo sprintf('<td nowrap="nowrap" width="1%%">%s</td>', $form->GetHTML('quantity_select_'.$priceEnquiry->Quantity[$i]->ID));
								echo sprintf('<td nowrap="nowrap" width="1%%"><a href="javascript:confirmRequest(\'price_enquiry_details.php?id=%d&action=remove&quantity=%d\', \'Are you sure you wish to remove this product? This will not affect any approved prices.\');"><img align="absmiddle" src="images/icon_trash_1.gif" alt="Remove" border="0" /></a></td>', $priceEnquiry->ID, $priceEnquiry->Quantity[$i]->ID);
							}

							if($isEditable) {
								echo sprintf('<td nowrap="nowrap">%s</td>', $form->GetHTML('quantity_quantity_'.$priceEnquiry->Quantity[$i]->ID));
							} else {
								echo sprintf('<td nowrap="nowrap">%s</td>', number_format(round($priceEnquiry->Quantity[$i]->Quantity, 2), 2, '.', ''));
							}
							?>

						</tr>

						<?php
					}
				} else {
			      	?>

			      	<tr>
			      		<td colspan="<?php echo ($isEditable) ? 3 : 1; ?>" align="center">No quantities available for viewing.</td>
			      	</tr>

			      	<?php
				}
			  	?>
		    </table><br />

			<?php
			if($isEditable) {
				?>

				<table cellspacing="0" cellpadding="0" border="0" width="100%">
					<tr>
						<td align="left">
							<input type="submit" name="removeselectedquantities" value="remove selected" class="btn" />
							<input type="submit" name="updatequantities" value="update" class="btn" />
						</td>
						<td align="right">
							<input type="button" name="add quantity" value="add quantity" class="btn" onclick="window.location.href='price_enquiry_add_quantity.php?id=<?php echo $priceEnquiry->ID; ?>';" />
						</td>
					</tr>
				</table>

				<?php
			}
			?>

		</div>
		<br />

		<div style="background-color: #eee; padding: 10px 0 10px 0;">
		 	<p><span class="pageSubTitle">Products [<?php echo count($priceEnquiry->Line); ?>]</span><br /><span class="pageDescription">Listing product quantities requesting price lists for.</span></p>

		 	<table cellspacing="0" class="orderDetails">
				<tr>
			        <?php
					if($isEditable) {
						echo '<th nowrap="nowrap">&nbsp;</th>';
						echo '<th nowrap="nowrap">&nbsp;</th>';
					}
					?>

					<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
			        <th nowrap="nowrap" style="padding-right: 5px;">Name</th>
			        <th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Orders</th>
				</tr>

				<?php
				if(count($priceEnquiry->Line) > 0) {
					for($i=0; $i<count($priceEnquiry->Line); $i++) {
						?>

						<tr>
							<?php
							if($isEditable) {
								echo sprintf('<td nowrap="nowrap" width="1%%">%s</td>', $form->GetHTML('product_select_'.$priceEnquiry->Line[$i]->ID));
								echo sprintf('<td nowrap="nowrap" width="1%%"><a href="javascript:confirmRequest(\'price_enquiry_details.php?id=%d&action=remove&line=%d\', \'Are you sure you wish to remove this product? This will not affect any approved prices.\');"><img align="absmiddle" src="images/icon_trash_1.gif" alt="Remove" border="0" /></a></td>', $priceEnquiry->ID, $priceEnquiry->Line[$i]->ID);
							}

							if($isEditable) {
								echo sprintf('<td nowrap="nowrap">%s</td>', $form->GetHTML('product_quantity_'.$priceEnquiry->Line[$i]->ID));
							} else {
								echo sprintf('<td nowrap="nowrap">%s</td>', number_format(round($priceEnquiry->Line[$i]->Quantity, 2), 2, '.', ''));
							}
							?>

							<td nowrap="nowrap"><?php print $priceEnquiry->Line[$i]->Product->ID; ?></td>
							<td nowrap="nowrap"><a href="product_profile.php?pid=<?php print $priceEnquiry->Line[$i]->Product->ID; ?>"><?php echo $priceEnquiry->Line[$i]->Product->Name; ?></a>&nbsp;</td>
							<td nowrap="nowrap" align="right"><?php print $priceEnquiry->Line[$i]->Orders; ?></td>
						</tr>

						<?php
					}
				} else {
			      	?>

			      	<tr>
			      		<td colspan="<?php echo ($isEditable) ? 6 : 4; ?>" align="center">No products available for viewing.</td>
			      	</tr>

			      	<?php
				}
			  	?>
		    </table><br />

			<?php
			if($isEditable) {
				?>

				<table cellspacing="0" cellpadding="0" border="0" width="100%">
					<tr>
						<td align="left">
							<input type="submit" name="removeselectedproducts" value="remove selected" class="btn" />
							<input type="submit" name="updateproducts" value="update" class="btn" />
						</td>
						<td align="right">
							<input type="submit" name="exportproducts" value="export" class="btn" />
							<input type="button" name="addproducts" value="add products" class="btn" onclick="window.location.href='price_enquiry_add_product.php?id=<?php echo $priceEnquiry->ID; ?>';" />
						</td>
					</tr>
				</table>

				<?php
			}
			?>

		</div>
		<br />

		<div style="background-color: #eee; padding: 10px 0 10px 0;">
		 	<p><span class="pageSubTitle">Collections</span><br /><span class="pageDescription">Feed products from this enquiry into a collection.</span></p>

		 	<table cellspacing="0" class="orderDetails">
				<tr>
					<th nowrap="nowrap" style="padding-right: 5px;"><?php echo $form->GetLabel('collection'); ?></th>
				</tr>
				<tr>
					<td><?php echo $form->GetHTML('collection'); ?></td>
			 	</tr>
			</table><br />

			<table cellspacing="0" cellpadding="0" border="0" width="100%">
				<tr>
					<td align="left">
						<input type="submit" name="feedcollection" value="submit" class="btn" />
					</td>
					<td align="right">
					</td>
				</tr>
			</table>

		</div>

    </td>
  </tr>
</table>

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');