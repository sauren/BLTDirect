<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseReserve.php');

if($action == 'remove') {
	$session->Secure(2);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['line'])) {
		$line = new CartLine();
		$line->Delete($_REQUEST['line']);

		redirect("Location: ?action=view");
	}
}

function view() {
	global $session;

	$cart = new Purchase(null, $session);
	$cart->PSID = $session->ID;

	if(!$cart->Exists()){
		$cart->SetDefaults();
		$cart->Add();
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	for($i=0; $i < count($cart->Line); $i++){
		$form->AddField('qty_'.$cart->Line[$i]->ID, 'Quantity of ' . $cart->Line[$i]->Product->Name, 'text', $cart->Line[$i]->Quantity, 'numeric_unsigned', 1, 11, true, 'size="3"');
		$form->AddField('supplier_'.$cart->Line[$i]->ID, 'Suppliers of '.$cart->Line[$i]->Product->Name, 'select', $cart->Line[$i]->SuppliedBy,'numeric_unsigned', 1, 11);
		$form->AddOption('supplier_'.$cart->Line[$i]->ID, 0, '');
		
		$suppliers = array();
		
		$data = new DataQuery(sprintf("SELECT spp.Supplier_ID, spp.Quantity, spp.Cost, c.Parent_Contact_ID FROM supplier_product_price AS spp INNER JOIN supplier AS s ON s.Supplier_ID=spp.Supplier_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID WHERE spp.Product_ID=%d AND spp.Quantity<=%d ORDER BY spp.Quantity DESC, spp.Created_On ASC", mysql_real_escape_string($cart->Line[$i]->Product->ID), mysql_real_escape_string($cart->Line[$i]->Quantity)));
		while($data->Row) {
			if(!isset($suppliers[$data->Row['Supplier_ID']])) {
				$suppliers[$data->Row['Supplier_ID']] = array('Parent_Contact_ID' => $data->Row['Parent_Contact_ID'], 'Person_ID' => $data->Row['Person_ID'], 'Prices' => array());	
			}
			
			$suppliers[$data->Row['Supplier_ID']]['Prices'][$data->Row['Quantity']] = $data->Row['Cost'];
			
			$data->Next();
		}
		$data->Disconnect();

		foreach($suppliers as $supplierId=>$supplierData) {
			if($supplierData['Parent_Contact_ID'] == 0) {
				$nameFinder = new DataQuery(sprintf("SELECT * FROM person p WHERE p.Person_ID = %d", mysql_real_escape_string($supplierData['Person_ID'])));
				$supName = sprintf('%s %s %s %s',$nameFinder->Row['Name_Title'],$nameFinder->Row['Name_First'],$nameFinder->Row['Name_Initial'],$nameFinder->Row['Name_Last']);
				$nameFinder->Disconnect();
			} else {
				$nameFinder = new DataQuery(sprintf("SELECT * FROM organisation o INNER JOIN contact c ON c.Org_ID = o.Org_ID WHERE c.Contact_ID = %d", mysql_real_escape_string($supplierData['Parent_Contact_ID'])));
				$supName = $nameFinder->Row['Org_Name'];
				$nameFinder->Disconnect();
			}
			
			$cost = 0;
			$quantity = 0;

			foreach($suppliers[$supplierId]['Prices'] as $productQuantity=>$productCost) {
				if($productCost > 0) {
					$cost = $productCost;
					$quantity = $productQuantity;
					
					break;
				}
			}
			
			$form->AddOption('supplier_'.$cart->Line[$i]->ID, $supplierId, $supName.' - &pound;'.$cost . ' (x'.$quantity.')');
		}
	}

	if(isset($_REQUEST['confirm'])) {
		if(isset($_REQUEST['reserve'])) {
			if($form->Validate()) {
				for($i=0; $i<count($cart->Line); $i++) {
					if($cart->Line[$i]->SuppliedBy > 0) {
						$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type='S' AND Type_Reference_ID=%d", mysql_real_escape_string($cart->Line[$i]->SuppliedBy)));
						if($data->TotalRows > 0) {
							$reserve = new WarehouseReserve();
							$reserve->warehouse->ID = $data->Row['Warehouse_ID'];
							$reserve->product->ID = $cart->Line[$i]->Product->ID;
							$reserve->quantity = $cart->Line[$i]->Quantity;
							$reserve->add();
						}
						$data->Disconnect();
					}
				}

				$cart->Delete();

				redirect("Location: ?action=view");
			}
		} else {
			if($form->Validate()) {
				for($i=0; $i < count($cart->Line); $i++) {
					$cart->Line[$i]->Quantity = $form->GetValue('qty_' . $cart->Line[$i]->ID);
					$cart->Line[$i]->QuantityDec = $form->GetValue('qty_'.$cart->Line[$i]->ID);
					$cart->Line[$i]->SuppliedBy = $form->GetValue('supplier_' . $cart->Line[$i]->ID);
					$cart->Line[$i]->Update();
				}
			
				redirect("Location: ?action=view");
			}
		}
	}

	$page = new Page('Create Warehouse Reserve', '');
	$page->Display('header');
	?>

	<table width="100%" border="0">
	  <tr>
	    <td width="250" valign="top"><?php include('warehouse_reserve_toolbox.php'); ?></td>
	    <td width="20" valign="top">&nbsp;</td>
	    <td valign="top">
	    
		    <?php
		    if(!$form->Valid){
		    	echo $form->GetError();
		    	echo '<br />';
		    }

		    echo $form->Open();
		    echo $form->GetHTML('confirm');
			?>

				<table cellspacing="0" class="catProducts">
					<tr>
						<th>&nbsp;</th>
						<th>Qty</th>
						<th>Product</th>
						<th>Supplier</th>
					</tr>
				<?php
				for($i=0; $i < count($cart->Line); $i++) {
				?>
					<tr>
						<td><a href="javascript:confirmRemove(<?php echo $cart->Line[$i]->ID; ?>);"><img src="images/icon_trash_1.gif" alt="Remove" width="16" height="16" border="0" /></a></td>
						<td><?php echo $form->GetHtml('qty_' . $cart->Line[$i]->ID); ?></td>
						<td>
							<a href="product_profile.php?pid=<?php echo $cart->Line[$i]->Product->ID;?>"><strong><?php echo $cart->Line[$i]->Product->Name; ?></strong></a><br />
							<span class="smallGreyText"><?php echo "Quickfind Code: " . $cart->Line[$i]->Product->ID; ?></span>
						</td>
						<td><?php echo $form->GetHTML('supplier_'.$cart->Line[$i]->ID)?></td>
					</tr>
				<?php
				}

				if(count($cart->Line) == 0){
					?>
					<tr>
						<td colspan="4" align="center">Your Shopping Cart is Empty</td>
					</tr>
					<?php
				}
				?>
					
				</table>
				<br />

				<table border="0" width="100%" cellpadding="0" cellspacing="0">
					<tr>
					  <td width="150" valign="top">
					  	<input name="update" type="submit" class="btn" value="update" />
					  </td>
					  <td align="right">
						<input name="reserve" type="submit" class="btn" value="reserve" />
					  </td>
					</tr>
				</table>

				<?php echo $form->Close(); ?>

		</td>
	  </tr>
	</table>
	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}