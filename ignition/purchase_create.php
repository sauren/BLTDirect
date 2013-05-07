<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Postage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');

$session->Secure(2);

$cart = new Purchase(null, $session);
$cart->PSID = $session->ID;

if(!$cart->Exists()){
	$cart->SetDefaults();
	$cart->Add();
}

if($cart->Warehouse->ID == 0){
	$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse w INNER JOIN users u ON u.Branch_ID=w.Type_Reference_ID WHERE w.Type='B' AND u.User_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	if($data->TotalRows > 0) {
		$cart->Warehouse->ID = $data->Row['Warehouse_ID'];
		$cart->Update();
	}
	$data->Disconnect();
}

if($action == 'remove') {
	if((strtolower($_REQUEST['confirm']) == "true") && isset($_REQUEST['line']) && is_numeric($_REQUEST['line'])){
		$line = new PurchaseLine();
		$line->Delete($_REQUEST['line']);

		redirect("Location: purchase_create.php");
	}
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('coupon', 'Coupon Code', 'text', '', 'alpha_numeric', 1, 15, false);

for($i=0; $i < count($cart->Line); $i++){
	$form->AddField('qty_' . $cart->Line[$i]->ID, 'Quantity of ' . $cart->Line[$i]->Product->Name, 'text', $cart->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
	$form->AddField('supplier_'.$cart->Line[$i]->ID,'Suppliers For '.$cart->Line[$i]->Product->Name,'select',$cart->Line[$i]->SuppliedBy,'numeric_unsigned',1,11);
	$form->AddOption('supplier_'.$cart->Line[$i]->ID,0,'Please Select a Supplier');
	
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

if($action == 'update' && (strtolower($_REQUEST['confirm']) == 'true')){
	if($form->Validate()){
		$quantitiesUpdated = false;

		for($i=0; $i < count($cart->Line); $i++){
			if(is_numeric($form->GetValue('qty_' . $cart->Line[$i]->ID)) &&
			($cart->Line[$i]->Quantity != $form->GetValue('qty_' . $cart->Line[$i]->ID))
			&& $form->GetValue('qty_' . $cart->Line[$i]->ID) > 0)
			{
				$cart->Line[$i]->Quantity = $form->GetValue('qty_' . $cart->Line[$i]->ID);
				$cart->Line[$i]->QuantityDec = $form->GetValue('qty_'.$cart->Line[$i]->ID);
				$cart->Line[$i]->Update();
				$quantitiesUpdated = true;
			}
			if($form->GetValue('supplier_'.$cart->Line[$i]->ID)!=0){
				$prices = array();
	
				$data = new DataQuery(sprintf("SELECT spp.Supplier_ID, spp.Quantity, spp.Cost FROM supplier_product_price AS spp WHERE spp.Product_ID=%d AND spp.Supplier_ID=%d AND spp.Quantity<=%d ORDER BY spp.Quantity DESC, spp.Created_On ASC", mysql_real_escape_string($cart->Line[$i]->Product->ID), $form->GetValue('supplier_'.$cart->Line[$i]->ID), mysql_real_escape_string($cart->Line[$i]->Quantity)));
				while($data->Row) {
					$prices[$data->Row['Quantity']] = $data->Row['Cost'];
					
					$data->Next();
				}
				$data->Disconnect();
				
				$cost = 0;
				
				foreach($prices as $productQuantity=>$productCost) {
					if($productCost > 0) {
						$cost = $productCost;
						
						break;
					}
				}

				$cart->Line[$i]->SuppliedBy = $form->GetValue('supplier_'.$cart->Line[$i]->ID);
				$cart->Line[$i]->Cost = $cost;
				$costFinder2 = new DataQuery(sprintf("SELECT Supplier_SKU FROM supplier_product WHERE Supplier_ID = %d AND Product_ID = %d",$form->GetValue('supplier_'.$cart->Line[$i]->ID),mysql_real_escape_string($cart->Line[$i]->Product->ID)));
				$cart->Line[$i]->SKU = $costFinder2->Row['Supplier_SKU'];
				$costFinder2->Disconnect();

				$cart->Line[$i]->Update();
			}else{
				$cart->Line[$i]->SuppliedBy = 0;
				$cart->Line[$i]->Cost = 0;
				$cart->Line[$i]->Update();
			}
		}
	}

	if($form->Valid){
		redirect("Location: purchase_create.php");
	}
}

if($action == 'checkout'){
	redirect("Location: purchase_checkout.php");
}

$page = new Page('Create a New Order Manually', '');
$page->Display('header');
?>
<script language="javascript">
function confirmRemove(id){
    if(confirm('Are you sure you would like to remove this product from your cart?')) {
        window.location.href = 'purchase_create.php?action=remove&confirm=true&line=' + id;
    }
}
</script>
<table width="100%" border="0">
  <tr>
    <td width="250" valign="top"><?php include('./purchase_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top">
    <p><strong>Shopping Cart</strong><br />Click the Checkout button to continue with your order.</p>

	    <?php
	    if(!$form->Valid){
	    	echo $form->GetError();
	    	echo "<br>";
	    }

	    echo $form->Open();
	    echo $form->GetHtml('confirm');
		?>

			<table cellspacing="0" class="catProducts">
				<tr>
					<th>&nbsp;</th>
					<th>Qty</th>
					<th>Product</th>
					<th>Supplier + Cost Per Unit</th>
					<th>&nbsp;</th>
				</tr>
			<?php
			for($i=0; $i < count($cart->Line); $i++){
			?>
				<tr>
					<td><a href="javascript:confirmRemove(<?php echo $cart->Line[$i]->ID; ?>);" onmouseover="MM_displayStatusMsg('Remove <?php echo $cart->Line[$i]->Product->Name; ?>');return document.MM_returnValue"  onmouseout="MM_displayStatusMsg('');return document.MM_returnValue"><img src="images/icon_trash_1.gif" alt="Remove <?php echo $cart->Line[$i]->Product->Name; ?>" width="16" height="16" border="0" /></a></td>
					<td><?php echo $form->GetHtml('qty_' . $cart->Line[$i]->ID); ?></td>
					<td>
						<a href="./purchase_product.php?pid=<?php echo $cart->Line[$i]->Product->ID;?>" title="Click to View <?php echo $cart->Line[$i]->Product->Name; ?>"><strong><?php echo $cart->Line[$i]->Product->Name; ?></strong></a><br />
						<span class="smallGreyText"><?php
						echo "Quickfind Code: " . $cart->Line[$i]->Product->ID;
						?> </span>
					</td>
					<td colspan="2"><?php echo $form->GetHTML('supplier_'.$cart->Line[$i]->ID)?></td>

					<!--td align="right">&pound;<?php //echo number_format($cart->Line[$i]->Cost, 2, '.', ','); ?></td-->
				</tr>
			<?php
			}

			if(count($cart->Line) == 0){
			?>
				<tr>
					<td colspan="5" align="center">Your Shopping Cart is Empty</td>
				</tr>
			<?php
			}
			?>
				<tr>
					<td colspan="3"><img src="images/icon_trash_1.gif" width="16" height="16" border="0" align="absmiddle" /> = Remove</td>
					<td align="right">Sub Total: </td>
					<?php
					$totalCost=0;
					
					for ($i = 0; $i < count($cart->Line);$i++){
						$totalCost += $cart->Line[$i]->Cost*$cart->Line[$i]->Quantity;
					}?>
					<td align="right">&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></td>
				</tr>
			</table>
			<br />
			<table border="0" width="100%" cellpadding="0" cellspacing="0">
				<tr>
				  <td width="150" valign="top">
				  <?php if(count($cart->Line)> 0){ ?>
				  <p><input name="action" type="submit" class="btn" id="action" value="update" /></p>
				  <?php } ?>
				  </td><td align="right">
				    <p><input name="action" type="submit" class="btn" id="action" value="checkout" /><p>
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
?>