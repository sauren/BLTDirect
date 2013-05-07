<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$session->Secure(2);

global $cart;

$cart = new Debit(null,$session);
$cart->DSID = $session->ID;

if(!isset($cart) && !$cart->Exists()){
	$cart->Add();
}

$cart->GetLines();

switch(strtolower($action)){
	case 'remove':
		remove();
		break;
}

function remove(){
	if(isset($_REQUEST['confirm']) && isset($_REQUEST['line'])  && is_numeric($_REQUEST['line'])){
		$line = new DebitLine();
		$line->Delete($_REQUEST['line']);

		redirect("Location: debit_create.php");
	}
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

for($i=0; $i < count($cart->Line); $i++){
	$form->AddField('qty_' . $cart->Line[$i]->ID, 'Quantity of ' . $cart->Line[$i]->Product->Name, 'text',  $cart->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
	$form->AddField('supplier_'.$cart->Line[$i]->ID,'Suppliers For '.$cart->Line[$i]->Product->Name,'select',$cart->Line[$i]->SuppliedBy,'numeric_unsigned',1,11);
	$form->AddOption('supplier_'.$cart->Line[$i]->ID,0,'Please Select a Supplier');
	$supplierFinder = new DataQuery(sprintf("SELECT sp.*,c.* FROM supplier_product sp
											INNER JOIN supplier s ON s.Supplier_ID = sp.Supplier_ID
											INNER JOIN contact c ON c.Contact_ID = s.Contact_ID WHERE sp.Product_ID=%d", mysql_real_escape_string($cart->Line[$i]->Product->ID)));
	while($supplierFinder->Row){
		if($supplierFinder->Row['Parent_Contact_ID'] == 0){
			$nameFinder = new DataQuery(sprintf("SELECT * FROM person p WHERE p.Person_ID = %d", mysql_real_escape_string($supplierFinder->Row['Person_ID'])));
			$supName = sprintf('%s %s %s %s',$nameFinder->Row['Name_Title'],$nameFinder->Row['Name_First'],$nameFinder->Row['Name_Initial'],$nameFinder->Row['Name_Last']);
			$nameFinder->Disconnect();
		}else{
			$nameFinder = new DataQuery(sprintf("SELECT * FROM organisation o INNER JOIN contact c ON c.Org_ID = o.Org_ID WHERE c.Contact_ID = %d", mysql_real_escape_string($supplierFinder->Row['Parent_Contact_ID'])));
			$supName = $nameFinder->Row['Org_Name'];
			$nameFinder->Disconnect();
		}
		$form->AddOption('supplier_'.$cart->Line[$i]->ID,$supplierFinder->Row['Supplier_ID'],$supName.' - &pound;'.$supplierFinder->Row['Cost']);
		$supplierFinder->Next();
	}
}

if($action == 'update' && isset($_REQUEST['confirm'])) {
	if($form->Validate()){
		$quantitiesUpdated = false;
		for($i=0; $i < count($cart->Line); $i++){
			if(is_numeric($form->GetValue('qty_' . $cart->Line[$i]->ID)) &&
				($cart->Line[$i]->Quantity != $form->GetValue('qty_' . $cart->Line[$i]->ID))
				&& $form->GetValue('qty_' . $cart->Line[$i]->ID) > 0)
			{
				 $cart->Line[$i]->Quantity = $form->GetValue('qty_' . $cart->Line[$i]->ID);
				 $quantitiesUpdated = true;
			}
			if($form->GetValue('supplier_'.$cart->Line[$i]->ID)!=0){
				$cart->Line[$i]->SuppliedBy = $form->GetValue('supplier_'.$cart->Line[$i]->ID);
				$costFinder2 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Supplier_ID = %d AND Product_ID = %d", mysql_real_escape_string($form->GetValue('supplier_'.$cart->Line[$i]->ID)), mysql_real_escape_string($cart->Line[$i]->Product->ID)));
				if($costFinder2->TotalRows > 0) {
					$cart->Line[$i]->Cost = $costFinder2->Row['Cost'];
					$cart->Line[$i]->Total = number_format(($costFinder2->Row['Cost'] * $cart->Line[$i]->Quantity), 2, '.', '');
				}
				$costFinder2->Disconnect();
			}else{
				$cart->Line[$i]->SuppliedBy = 0;
				$cart->Line[$i]->Cost = 0;
			}

			$cart->Line[$i]->Update();
		}
	}

	if($form->Valid){
		redirect("Location: debit_create.php");
	}
}

if($action == 'checkout'){
	redirect("Location: debit_checkout.php");
}

$page = new Page('Create a New Debit Manually', '');
$page->Display('header');
?>
<script language="javascript">
	function confirmRemove(id){
		var url = './debit_create.php?action=remove&confirm=true&line=' + id;
		var remove = confirm('Are you sure you would like to remove this product from your cart?');
		if(remove){
			window.location.href = url;
		}
	}
</script>
<table width="100%" border="0">
<tr>
<td width="250" valign="top"><?php include('./debit_toolbox.php'); ?></td>
<td width="20" valign="top">&nbsp;</td>
<td valign="top"><strong>Debit Cart</strong>

<p>Click the Checkout button to continue with your order.</p>
	<?php
		if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	echo $form->Open();
	echo $form->GetHtml('confirm');


	?>
	<br />
</p>
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
					<a href="./debit_product.php?pid=<?php echo $cart->Line[$i]->Product->ID;?>" title="Click to View <?php echo $cart->Line[$i]->Description; ?>"><strong><?php echo $cart->Line[$i]->Description; ?></strong></a><br />
					<span class="smallGreyText"><?php
						echo "Quickfind Code: " . $cart->Line[$i]->Product->ID;
					?> </span>
				</td>
				<td colspan="2"><?php echo $form->GetHTML('supplier_'.$cart->Line[$i]->ID)?></td>
			</tr>
		<?php
			}

			if(count($cart->Line) == 0){
		?>
			<tr>
				<td colspan="5" align="center">Your Debit Cart is Empty</td>
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
					$costTot = new DataQuery(sprintf("SELECT * FROM supplier_product WHERE Product_ID = %d AND Supplier_ID = %d", mysql_real_escape_string($cart->Line[$i]->Product->ID), mysql_real_escape_string($form->GetValue('supplier_'.$cart->Line[$i]->ID))));
					$totalCost += $costTot->Row['Cost']*$cart->Line[$i]->Quantity;
					$costTot->Disconnect();
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