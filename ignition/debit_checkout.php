<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Branch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BreadCrumb.php');

$session->Secure(2);

global $cart;
$cart = new Debit(null,$session);
$cart->DSID = $session->ID;

if(!$cart->Exists()) {
	redirect("Location: debit_create.php");
}

$cart->GetLines();

for($i = 0; $i<count($cart->Line);$i++) {
	if($cart->Line[$i]->SuppliedBy == 0) {
		$cart->Line[$i]->Delete();
	} else {
		$cart->Line[$i]->Total = number_format(($cart->Line[$i]->Cost * $cart->Line[$i]->Quantity), 2, '.', '');
		$cart->Line[$i]->Update();
	}
}

$cart->GetLines();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'confirm', 'alpha', 1, 11);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 1, 11);

for($i=0; $i < count($cart->Line); $i++){
	$form->AddField('reason_'.$cart->Line[$i]->ID, 'Reason', 'select', 'Faulty', 'anything', 1, 64);
	$form->AddOption('reason_'.$cart->Line[$i]->ID, 'Faulty goods', 'Faulty goods');
	$form->AddOption('reason_'.$cart->Line[$i]->ID, 'General return', 'General return');
	$form->AddOption('reason_'.$cart->Line[$i]->ID, 'Incorrectly supplied', 'Incorrectly supplied');
	$form->AddOption('reason_'.$cart->Line[$i]->ID, 'Received broken', 'Received broken');
	$form->AddOption('reason_'.$cart->Line[$i]->ID, 'Received late', 'Received late');
	$form->AddField('order_'.$cart->Line[$i]->ID, 'Order Ref', 'text', '', 'anything', 1, 64, false, 'style="width: 100px"');
	$form->AddField('date_'.$cart->Line[$i]->ID, 'Order Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'style="width: 100px" onclick="scwShow(this,this);" onfocus="scwShow(this,this);"');
}

if($action == 'confirm' && isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {

	$newPurchases = array();

	$total = array();

	for($j = 0; $j<count($cart->Line);$j++) {
		$usePurchase = null;

		if(!isset($newPurchases[$cart->Line[$j]->SuppliedBy])) {
			$newPurchase = new Debit();
			$newPurchase->Supplier->ID = $cart->Line[$j]->SuppliedBy;
			$newPurchase->IsPaid = 'N';
			$newPurchase->Status = 'Active';
			$newPurchase->DSID = '';

			$supplier = new Supplier($cart->Line[$j]->SuppliedBy);
			$supplier->Contact->Get();

			if(!$supplier->Contact->HasParent){
				$nameFinder = new DataQuery(sprintf("SELECT * FROM person p WHERE p.Person_ID=%d", mysql_real_escape_string($supplier->Contact->Person->ID)));
				$supName = sprintf('%s %s %s %s',$nameFinder->Row['Name_Title'],$nameFinder->Row['Name_First'],$nameFinder->Row['Name_Initial'],$nameFinder->Row['Name_Last']);
				$nameFinder->Disconnect();
			}

			$newPurchase->Person = $supplier->Contact->Person;
			$newPurchase->Organisation = ($supplier->Contact->HasParent)? $supplier->Contact->Parent->Organisation->Name : $supName;
			$newPurchase->Add();

			$newPurchases[$cart->Line[$j]->SuppliedBy] = $newPurchase;
			$usePurchase = $newPurchase;
		} else {
			$usePurchase = $newPurchases[$cart->Line[$j]->SuppliedBy];
		}

		$cart->Line[$j]->DebitID = $usePurchase->ID;
		$cart->Line[$j]->Reason = $form->GetValue('reason_'.$cart->Line[$j]->ID);
		$cart->Line[$j]->Custom = 'Order Ref: '.$form->GetValue('order_'.$cart->Line[$j]->ID).'<br />Order Date: '.$form->GetValue('date_'.$cart->Line[$j]->ID);
		$cart->Line[$j]->Update();

		if(!isset($total[$cart->Line[$j]->SuppliedBy])) {
			$total[$cart->Line[$j]->SuppliedBy] = array();
			$total[$cart->Line[$j]->SuppliedBy]['Total'] = 0;
			$total[$cart->Line[$j]->SuppliedBy]['Debit'] = $usePurchase;
		}

		$total[$cart->Line[$j]->SuppliedBy]['Total'] += $cart->Line[$j]->Total;
	}

	foreach($total as $totalDebit) {
		$totalDebit['Debit']->Total = number_format($totalDebit['Total'], 2, '.', '');
		$totalDebit['Debit']->Update();
	}

	$cart->Delete();

	redirect("Location: debit_awaiting_payment.php");
}

$page = new Page('Confirm Debit','Please confirm that these are the products you wish to debit.');
$page->AddToHead('<script language="javascript" src="js/scw.js"></script>');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
?>

<table cellspacing="0" class="catProducts">
		<tr>
			<th>Qty</th>
			<th>Product</th>
			<th>Supplier</th>
			<th>Reason</th>
			<th>Order Ref</th>
			<th>Order Date</th>
			<th style="text-align: right;">Cost Per Unit</th>
			<th style="text-align: right;">Line Cost</th>
		</tr>
	<?php
	for($i=0; $i < count($cart->Line); $i++){
	?>
		<tr>
			<td><?php echo $cart->Line[$i]->Quantity; ?>x</td>
			<td>
				<a href="./product_profile.php?pid=<?php echo $cart->Line[$i]->Product->ID;?>" title="Click to View <?php echo $cart->Line[$i]->Description; ?>"><strong><?php echo $cart->Line[$i]->Description; ?></strong></a><br />
				<span class="smallGreyText"><?php
				echo "Quickfind Code: " . $cart->Line[$i]->Product->ID;
				?> </span>
			</td>
			<td>
				<?php
				$supName = 'No Supplier Selected';

				$supplierFinder = new DataQuery(sprintf("SELECT sp.*,c.* FROM supplier_product sp
											INNER JOIN supplier s ON s.Supplier_ID = sp.Supplier_ID
											INNER JOIN contact c ON c.Contact_ID = s.Contact_ID WHERE sp.Product_ID=%d AND s.Supplier_ID=%d",$cart->Line[$i]->Product->ID, $cart->Line[$i]->SuppliedBy));
				if($supplierFinder->TotalRows > 0) {
					if($supplierFinder->Row['Parent_Contact_ID'] == 0){
						$nameFinder = new DataQuery(sprintf("SELECT * FROM person p WHERE p.Person_ID = %d", mysql_real_escape_string($supplierFinder->Row['Person_ID'])));
						$supName = sprintf('%s %s %s %s',$nameFinder->Row['Name_Title'],$nameFinder->Row['Name_First'],$nameFinder->Row['Name_Initial'],$nameFinder->Row['Name_Last']);
						$nameFinder->Disconnect();
					}else{
						$nameFinder = new DataQuery(sprintf("SELECT * FROM organisation o INNER JOIN contact c ON c.Org_ID = o.Org_ID WHERE c.Contact_ID = %d",mysql_real_escape_string($supplierFinder->Row['Parent_Contact_ID'])));
						$supName = $nameFinder->Row['Org_Name'];
						$nameFinder->Disconnect();
					}

					$unknownPrice = false;
				} else {
					$unknownPrice = true;
				}

				echo $supName;
				?>
			</td>
			<td>
				<?php
				echo $form->GetHTML('reason_'.$cart->Line[$i]->ID);
				?>
			</td>
			<td>
				<?php
				echo $form->GetHTML('order_'.$cart->Line[$i]->ID);
				?>
			</td>
			<td>
				<?php
				echo $form->GetHTML('date_'.$cart->Line[$i]->ID);
				?>
			</td>
			<td align="right">
				<?php
				if($unknownPrice) {
					echo '-';
				} else {
					echo '&pound;'.$supplierFinder->Row['Cost'];
				}
				?>
			</td>
			<td align="right">
				<?php
				if($unknownPrice) {
					echo '-';
				} else {
					echo '&pound;'.number_format(($supplierFinder->Row['Cost'] * $cart->Line[$i]->Quantity), 2, '.', '');
				}
				?>
			</td>
		</tr>
	<?php
	}

	if(count($cart->Line) == 0){
	?>
		<tr>
			<td colspan="8" align="center">Your Shopping Cart is Empty</td>
		</tr>
	<?php
	}
	?>
		<tr>
			<td colspan="5">&nbsp;</td>
			<td align="right"><strong>Sub Total:</strong></td>
			<?php
			$totalCost=0;
			for ($i = 0; $i < count($cart->Line);$i++){
				$costTot = new DataQuery(sprintf("SELECT * FROM supplier_product WHERE Product_ID = %d AND Supplier_ID = %d", $cart->Line[$i]->Product->ID,$cart->Line[$i]->SuppliedBy));
				$totalCost += $costTot->Row['Cost']*$cart->Line[$i]->Quantity;
				$costTot->Disconnect();
			}?>
			<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />

<?php
if(count($cart->Line) > 0){
	?>

	<form action="debit_checkout.php" method="post">
		<input type="submit" class="btn" name="debit" id="debit" value="Confirm Debit" />
	</form>

	<?php
}

echo $form->Close();

$page->Display('footer');
?>