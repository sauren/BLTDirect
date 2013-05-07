<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseRequestLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductReorder.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');

$session->Secure(3);

$purchaseRequest = new PurchaseRequest($_REQUEST['id']);
$purchaseRequest->GetLines();
$purchaseRequest->Supplier->Get();
$purchaseRequest->Supplier->Contact->Get();

$isEditable = (strtolower($purchaseRequest->Status) == 'confirmed') ? true : false;

if($action == "complete") {
	$purchaseRequest->Status = 'Completed';
	$purchaseRequest->Update();

	$purchaseCount = 0;

	for($k=0; $k<count($purchaseRequest->Line); $k++) {
		if($purchaseRequest->Line[$k]->IsPurchased == 'N') {
			$reorder = new ProductReorder();

			if($reorder->GetByProductID($purchaseRequest->Line[$k]->Product->ID)) {
				$reorder->IsHidden = 'N';
				$reorder->Update();
			}
		} else {
			$purchaseCount++;
		}
	}

	if($purchaseCount > 0) {
		$user = new User();
		$user->ID = $GLOBALS['SESSION_USER_ID'];
		$user->Get();

		$data = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM users AS u INNER JOIN warehouse AS w ON w.Type_Reference_ID=u.Branch_ID AND w.Type='B' WHERE u.User_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$warehouse = new Warehouse();
		$warehouse->ID = $data->Row['Warehouse_ID'];
		$warehouse->Get();
		$warehouse->Contact->Get();

		$data->Disconnect();

		$purchase = new Purchase();
		$purchase->SupplierID = $purchaseRequest->Supplier->ID;
		$purchase->PurchasedOn = date('Y-m-d H:i:s');
		$purchase->Person = $user->Person;
		$purchase->Person->Address = $warehouse->Contact->Address;
		$purchase->Organisation = $warehouse->Name;
		$purchase->Warehouse->ID = $warehouse->ID;
		$purchase->Status = 'Unfulfilled';
		$purchase->Branch = $warehouse->Contact->ID;
		$purchase->PSID = 0;
		$purchase->Supplier = $purchaseRequest->Supplier->Contact->Person;
		$purchase->SupOrg = ($purchaseRequest->Supplier->Contact->HasParent) ? $purchaseRequest->Supplier->Contact->Parent->Organisation->Name : '';

		$data = new DataQuery(sprintf("SELECT o.Fax FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.Person_ID=%d", mysql_real_escape_string($purchaseRequest->Supplier->Contact->Person->ID)));
		$purchase->Supplier->Fax = $data->Row['Fax'];
		$data->Disconnect();

		$purchase->Add();

		for($k=0; $k<count($purchaseRequest->Line); $k++) {
			if($purchaseRequest->Line[$k]->IsPurchased == 'Y') {
				$purchaseRequest->Line[$k]->Product->Get();

				$reorder = new ProductReorder();

				if($reorder->GetByProductID($purchaseRequest->Line[$k]->Product->ID)) {
					$purchaseLine = new PurchaseLine();
					$purchaseLine->Purchase = $purchase->ID;
					$purchaseLine->Quantity = ($purchaseRequest->Line[$k]->IsStocked == 'Y') ? $purchaseRequest->Line[$k]->Quantity : $purchaseRequest->Line[$k]->StockAvailable;
					$purchaseLine->Product = $purchaseRequest->Line[$k]->Product;
					$purchaseLine->SuppliedBy = $purchaseRequest->Supplier->ID;

					$data = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($purchaseRequest->Supplier->ID), mysql_real_escape_string($purchaseRequest->Line[$k]->Product->ID)));
					$purchaseLine->SKU = $data->Row['Supplier_SKU'];
					$data->Disconnect();

					$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($warehouse->ID), mysql_real_escape_string($purchaseRequest->Line[$k]->Product->ID)));
					$purchaseLine->Location = $data->Row['Shelf_Location'];
					$data->Disconnect();

					$prices = array();

					$data = new DataQuery(sprintf("SELECT * FROM supplier_product_price WHERE Supplier_ID=%d AND Product_ID=%d AND Quantity<=%d ORDER BY Created_On ASC", mysql_real_escape_string($purchaseRequest->Supplier->ID), mysql_real_escape_string($purchaseRequest->Line[$k]->Product->ID, $purchaseRequest->Line[$k]->Quantity)));
					while($data->Row) {
						if($data->Row['Cost'] > 0) {
							$prices[$data->Row['Quantity']] = $data->Row;
						} else {
							unset($prices[$data->Row['Quantity']]);
						}

						$data->Next();
					}
					$data->Disconnect();

					krsort($prices);

					if(count($prices) > 0) {
						foreach($prices as $price) {
							$purchaseLine->Cost = $price['Cost'];
							break;
						}
					}

					$purchaseLine->QuantityDec = $purchaseLine->Quantity;
					$purchaseLine->Add();

					if($purchaseRequest->Line[$k]->IsStocked == 'N') {
						$reorder->ReorderQuantity -= $purchaseRequest->Line[$k]->StockAvailable;
						$reorder->IsHidden = 'N';
						$reorder->Update();
					} else {
						$reorder->Delete();
					}
				}
			}
		}
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $purchaseRequest->ID));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Purchase Request ID', 'hidden', '', 'numeric_unsigned', 1, 11);

for($k=0; $k<count($purchaseRequest->Line); $k++) {
	$purchaseRequest->Line[$k]->Product->Get();

	if($isEditable) {
		$form->AddField(sprintf('purchase_%d', $purchaseRequest->Line[$k]->ID), sprintf('Purchase for \'%s\'', $purchaseRequest->Line[$k]->Product->Name), 'checkbox', $purchaseRequest->Line[$k]->IsPurchased, 'boolean', 1, 1, false);
	}
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		if(isset($_REQUEST['update']) || isset($_REQUEST['updateproducts'])) {
			if($isEditable) {
				for($i=0; $i<count($purchaseRequest->Line); $i++) {
					$purchaseRequest->Line[$i]->IsPurchased = $form->GetValue(sprintf('purchase_%d', $purchaseRequest->Line[$i]->ID));
					$purchaseRequest->Line[$i]->Update();
				}
			}
		}

		if($form->Valid) {
			redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $purchaseRequest->ID));
		}
	}
}

$page = new Page(sprintf('[#%d] Purchase Request Details', $purchaseRequest->ID), 'Manage this purchase request here.');
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
	        <th>Purchase Request:</th>
	        <td>#<?php echo $purchaseRequest->ID; ?></td>
	      </tr>
	      <tr>
	        <th>Status:</th>
	        <td><?php echo $purchaseRequest->Status; ?></td>
	      </tr>
	      <tr>
	        <th>Supplier:</th>
	        <td>
	        	<?php
				$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE s.Supplier_ID=%d", mysql_real_escape_string($purchaseRequest->Supplier->ID)));
				echo ($data->TotalRows > 0) ? $data->Row['Supplier_Name'] : '&nbsp;';
				$data->Disconnect();
	        	?>
	        </td>
	      </tr>
	      <tr>
	        <th>&nbsp;</th>
	        <td>&nbsp;</td>
	      </tr>
	      <tr>
	        <th>Created On:</th>
	        <td><?php echo cDatetime($purchaseRequest->CreatedOn, 'shortdate'); ?></td>
	      </tr>
	      <tr>
	        <th>Created By:</th>
	        <td>
	        	<?php
	        	$user = new User();
	        	$user->ID = $purchaseRequest->CreatedBy;

	        	if($user->Get()) {
	        		echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName));
	        	}
	        	?>
	        	&nbsp;
	        </td>
	      </tr>
	    </table>
	    <br />

   </td>
  </tr>
  <tr>
  	<td valign="top">

		<?php
		if($isEditable) {
			echo sprintf('<input name="complete" type="button" value="complete" class="btn" onclick="confirmRequest(\'%s?action=complete&id=%d\', \'Please confirm you wish to complete this purchase request?\');" />', $_SERVER['PHP_SELF'], $purchaseRequest->ID);
		}
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
		 	<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing stock requested for a purchase order.</span></p>

		 	<table cellspacing="0" class="orderDetails">
				<tr>
					<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Name</th>
		      		<th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Is Stocked</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Stock Arrival (Days)</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Stock Available</th>
		      		<th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Purchase?</th>
		      	</tr>

				<?php
				if(count($purchaseRequest->Line) > 0) {
					for($k=0; $k<count($purchaseRequest->Line); $k++) {
						?>

						<tr>
				      		<td nowrap="nowrap"><?php echo $purchaseRequest->Line[$k]->Quantity; ?></td>
				      		<td nowrap="nowrap"><?php echo $purchaseRequest->Line[$k]->Product->ID; ?></td>
				      		<td nowrap="nowrap"><?php echo $purchaseRequest->Line[$k]->Product->Name; ?></td>
				      		<td nowrap="nowrap" align="center"><?php echo $purchaseRequest->Line[$k]->IsStocked; ?></td>
				      		<td nowrap="nowrap"><?php echo ($purchaseRequest->Line[$k]->IsStocked == 'N') ? $purchaseRequest->Line[$k]->StockArrivalDays : '-'; ?></td>
				      		<td nowrap="nowrap"><?php echo ($purchaseRequest->Line[$k]->IsStocked == 'N') ? $purchaseRequest->Line[$k]->StockAvailable : '-'; ?></td>
				      		<td nowrap="nowrap" align="center"><?php echo ($isEditable) ? $form->GetHTML(sprintf('purchase_%d', $purchaseRequest->Line[$k]->ID)) : $purchaseRequest->Line[$k]->IsPurchased; ?></td>
						</tr>

						<?php
					}
				} else {
			      	?>

			      	<tr>
						<td colspan="7" align="center">No products available for viewing.</td>
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
							<input type="submit" name="updateproducts" value="update" class="btn" />
						</td>
						<td align="right"></td>
					</tr>
				</table>

				<?php
			}
			?>

		</div>

    </td>
  </tr>
</table>

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');