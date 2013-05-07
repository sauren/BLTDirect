<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Branch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BreadCrumb.php');

if($action == 'confirm'){
	$session->Secure(2);

	global $purchases;

	$html == '';

	$purchase = new Purchase($_REQUEST['purstr']);
	$purchase->GetLines();

	$userDetails = new DataQuery(sprintf("SELECT * FROM users u INNER JOIN branch b ON u.Branch_ID = b.Branch_ID WHERE User_ID = %d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	
	if(isset($_REQUEST['branchId'])){
		$branch = new Branch($_REQUEST['branchId']);
	} else {
		$branch = new Branch($userDetails->Row['Branch_ID']);
	}
	$userDetails->Disconnect();

	$newPurchases = array();

	for($j = 0; $j<count($purchase->Line);$j++) {
		$usePurchase = null;

		if(!isset($newPurchases[$purchase->Line[$j]->SuppliedBy])) {
			$newPurchase = new Purchase();
			$newPurchase->SupplierID = $purchase->Line[$j]->SuppliedBy;
			$newPurchase->PurchasedOn = getDatetime();
			$newPurchase->Person->Title = $purchase->Person->Title;
			$newPurchase->Person->Name = $purchase->Person->Name;
			$newPurchase->Person->Initial = $purchase->Person->Initial;
			$newPurchase->Person->LastName = $purchase->Person->LastName;
			$newPurchase->Organisation = $purchase->Organisation;
			$newPurchase->Person->Address->Line1 = $purchase->Person->Address->Line1;
			$newPurchase->Person->Address->Line2 = $purchase->Person->Address->Line2;
			$newPurchase->Person->Address->Line3 = $purchase->Person->Address->Line3;
			$newPurchase->Person->Address->City = $purchase->Person->Address->City;
			$newPurchase->Person->Address->Region->Name = $purchase->Person->Address->Region->Name;
			$newPurchase->Person->Address->Country->Name = $purchase->Person->Address->Country->Name;
			$newPurchase->Person->Address->Zip = $purchase->Person->Address->Zip;
			$newPurchase->Warehouse->ID = $purchase->Warehouse->ID;
			$newPurchase->Postage = $purchase->Postage;

			$data = new DataQuery(sprintf("SELECT * FROM users WHERE User_ID = %d",$GLOBALS['SESSION_USER_ID']));
			$newPurchase->Status = 'Unfulfilled';
			$newPurchase->Branch = $data->Row['Branch_ID'];
			$newPurchase->PSID = 0;
			$data->Disconnect();

			$supplier = new Supplier($purchase->Line[$j]->SuppliedBy);
			$supplier->Contact->Get();

			$newPurchase->Supplier = $supplier->Contact->Person;
			$newPurchase->SupOrg = ($supplier->Contact->HasParent)? $supplier->Contact->Parent->Organisation->Name: '';

			$data = new DataQuery(sprintf("SELECT o.Fax FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.Person_ID=%d", mysql_real_escape_string($supplier->Contact->Person->ID)));
			$newPurchase->Supplier->Fax = $data->Row['Fax'];
			$data->Disconnect();

			$newPurchase->Person->Address = $branch->Address;
			$newPurchase->Organisation = $branch->Name;
			$newPurchase->Add();

			$newPurchases[$purchase->Line[$j]->SuppliedBy] = $newPurchase;
			$usePurchase = $newPurchase;
		} else {
			$usePurchase = $newPurchases[$purchase->Line[$j]->SuppliedBy];
		}

		$purchase->Line[$j]->Purchase = $usePurchase->ID;
		$purchase->Line[$j]->Update();
	}

	$purString = '';

	foreach($newPurchases as $v) {
		$purString .= $v->ID . ',';
	}
	$purString = substr($purString, 0, -1);

	redirect("Location: purchase_checkout.php?purchases=".$purString);

} elseif($action == 'email'){
	$session->Secure(2);
	global $purchases;
	$purchases = explode(',',$_REQUEST['purstr']);
	$html == '';

	for($i = 0; $i<count($purchases)-1;$i++){
		$purchase = new Purchase($purchases[$i]);

		if($action=='email'){
			$purchase->GetLines();
			$tempSup = new Supplier($purchase->Line[0]->SuppliedBy);
			$purchase->EmailToBuy($tempSup->GetEmail());
		}

		$html.=$purchase->GetDocToBuy();
		$html.='<br><br><br>';
	}
	echo "<p>The following purchase orders were sent successfully.<p>";
	?>
			<script language="text/javascript" type="text/javascript">
			function printme(){

				var doc2 = document.getElementById('print');

				doc2.style.display="none";
				window.self.print();

				doc2.style.display="inline";
			}
			</script>
	<?php
	echo $html;
	echo "&nbsp;<input type='button' class='btn' name='print' id='print' value='Print These Orders' onclick='printme()'>";//\"window.location.href='purchase_checkout.php?action=print'\">";

}
else{
	// Secure this section
	$session->Secure(2);
	global $purchases;
	$purchases = array();
	// Start Cart
	global $cart;
	$cart = new Purchase(null,$session);
	$cart->PSID = $session->ID;

	if($cart->Exists()==false){
		$cart->SetDefaults();
		$cart->Add();
	}

	for($i = 0; $i<count($cart->Line);$i++){
		if($cart->Line[$i]->SuppliedBy ==0){
			$cart->Line[$i]->Delete();
		}
	}
	$cart->GetLines();

	if(!isset($_REQUEST['purchases'])) {

		$page = new Page('Confirm Purchase Orders','Please confirm that these are the products you wish to purchase.');
		$page->Display('header');		
?>

<form action="purchase_checkout.php" method="post">
	<input type="hidden" name="action" value="confirm" />
	<input type="hidden" name="purstr" value="<?php echo $cart->ID; ?>" />
<?php
		$userDetails = new DataQuery(sprintf("SELECT * FROM users u INNER JOIN branch b ON u.Branch_ID = b.Branch_ID WHERE User_ID = %d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$branch = new Branch($userDetails->Row['Branch_ID']);
		$userDetails->Disconnect();
		
		$isAdmin = false;
		if(stristr($branch->Name, 'blt')){
			$isAdmin = true;
			$branches = new DataQuery("select * from warehouse where Type='B'");
		?>
<h2>Delivery to...</h2>
<p>Select a warehouse for delivery:</p>
<div style="overflow:hidden; overflow-x:auto; display:block;">
	<table>
		<tr>
			<?php
				while($branches->Row){
					$item = new Branch($branches->Row['Type_Reference_ID']);
					echo sprintf('<td style="vertical-align:top"><input type="radio" name="branchId" value="%s" /></td><td style="vertical-align:top; padding:0 20px;"><strong>%s</strong><br />%s<br />%s</td>', $item->ID, $branches->Row['Warehouse_Name'], $item->Name, $item->Address->GetFormatted('<br />'));
					$branches->Next();
				}
				$branches->Disconnect();
			?>
		</tr>
	</table>
</div>
<br />
<?php } ?>
		<table cellspacing="0" class="catProducts">
				<tr>
					<th>Qty</th>
					<th>Product</th>
					<th>Supplier</th>
					<th style="text-align: right;">Cost Per Unit</th>
				</tr>
			<?php
				for($i=0; $i < count($cart->Line); $i++){
			?>
				<tr>
					<td><?php echo $cart->Line[$i]->Quantity; ?>x</td>
					<td>
						<a href="./product_profile.php?pid=<?php echo $cart->Line[$i]->Product->ID;?>" title="Click to View <?php echo $cart->Line[$i]->Product->Name; ?>"><strong><?php echo $cart->Line[$i]->Product->Name; ?></strong></a><br />
						<span class="smallGreyText"><?php
							echo "Quickfind Code: " . $cart->Line[$i]->Product->ID;
						?> </span>
					</td>
					<td>
					<?php
						$supplierFinder = new DataQuery(sprintf("SELECT sp.*,c.* FROM supplier_product sp
												INNER JOIN supplier s ON s.Supplier_ID = sp.Supplier_ID
												INNER JOIN contact c ON c.Contact_ID = s.Contact_ID WHERE sp.Product_ID=%d AND s.Supplier_ID=%d",mysql_real_escape_string($cart->Line[$i]->Product->ID), mysql_real_escape_string($cart->Line[$i]->SuppliedBy)));

							if($supplierFinder->Row['Parent_Contact_ID'] == 0){
								$nameFinder = new DataQuery(sprintf("SELECT * FROM person p WHERE p.Person_ID = %d", mysql_real_escape_string($supplierFinder->Row['Person_ID'])));
								$supName = sprintf('%s %s %s %s',$nameFinder->Row['Name_Title'],$nameFinder->Row['Name_First'],$nameFinder->Row['Name_Initial'],$nameFinder->Row['Name_Last']);
								$nameFinder->Disconnect();
							}else{
								$nameFinder = new DataQuery(sprintf("SELECT * FROM organisation o INNER JOIN contact c ON c.Org_ID = o.Org_ID WHERE c.Contact_ID = %d", mysql_real_escape_string($supplierFinder->Row['Parent_Contact_ID'])));
								$supName = $nameFinder->Row['Org_Name'];
								$nameFinder->Disconnect();
							}

							echo $supName;
					?>
					</td>
					<td align="right">
						&pound;<?php echo number_format($cart->Line[$i]->Cost, 2, '.', ','); ?>
					</td>
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
				<tr>
					<td colspan="2">&nbsp;</td>
					<td align="right"><strong>Sub Total:</strong></td>
					<?php
					$totalCost=0;
					 for ($i = 0; $i < count($cart->Line);$i++){
						$totalCost += $cart->Line[$i]->Cost*$cart->Line[$i]->Quantity;
					}?>
					<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
				</tr>
			</table>
			<br />

		<?php
		if(count($cart->Line) > 0){
		?>
			<input type='submit' class='btn' name='Confirm These Orders' id='confirm' value='Confirm These Orders' />
		</form>
		<?php
		}
		$page->Display('footer');

	} else {

		//The purchase has been obtained, It now needs to be split Into sperate purchase orders for each supplier

		//first remove the lines without a suppliers as they are ignored.

		//Find the suppliers
		/*$supCount = new DataQuery(sprintf("SELECT Distinct(Supplied_By) as Supplier FROM purchase_line WHERE Purchase_ID = %d Order By Supplied_By",$cart->ID));
		$suppliers = array();
		while ($supCount->Row) {
			$suppliers[] = $supCount->Row['Supplier'];
			$supCount->Next();
		}
		$supCount->Disconnect();

		//Create the company as a person
		$userDetails = new DataQuery(sprintf("SELECT * FROM users u INNER JOIN branch b ON u.Branch_ID = b.Branch_ID WHERE User_ID = %d",$GLOBALS['SESSION_USER_ID']));
		$branch = new Branch($userDetails->Row['Branch_ID']);
		$userDetails->Disconnect();

		if(!isset($_REQUEST['purchases'])) {
			//Create the purchase order for all the new suppliers
			for($i = 0; $i<count($suppliers);$i++){
				//Get Supplier Data
				$supplier = new Supplier($suppliers[$i]);

				$cart->PurchasedOn = getDatetime();
				$cart->Person->Address = $branch->Address;
				$cart->Organisation = $branch->Name;

				$supplier->Contact->Get();
				$cart->Supplier = $supplier->Contact->Person;
				$cart->SupOrg = ($supplier->Contact->HasParent)? $supplier->Contact->Parent->Organisation->Name: '';

				$data = new DataQuery(sprintf("SELECT o.Fax FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID INNER JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.Person_ID=%d", $supplier->Contact->Person->ID));
				$cart->Supplier->Fax = $data->Row['Fax'];
				$data->Disconnect();

				$cart->Update();

				$purchases[] = $cart->ID;
			}
		} else {
			$purchases = array();
			$purchases = explode(',',$_REQUEST['purchases']);
		}*/

		$purchases = array();
		$purchases = explode(',',$_REQUEST['purchases']);

		//with the purchases created preview them
		$html = '';

		//Get the list of purchases to call in email;
		$purStr = '';
		for($i = 0; $i<count($purchases);$i++){
			if(strlen($purchases[$i]) > 0) {
				$purStr  .= $purchases[$i].',';
				$purchase = new Purchase($purchases[$i]);
				$html.=$purchase->GetDocToBuy();
				$html.='<br><br><br>';
			}
		}


			?>
				<script language="text/javascript" type="text/javascript">
				function printme(){
					var doc = document.getElementById('email');
					var doc2 = document.getElementById('print');
					doc.style.display= "none";
					doc2.style.display="none";
					window.self.print();
					doc.style.display="inline";
					doc2.style.display="inline";
				}
				</script>
			<?php
			echo $html;
			echo "<p><strong>WARNING: When emailing the orders it may take some time to calculate and send them, DO NOT click the email button more than once as it will send off more than one order</strong></p>";
			echo "<input type='button' class='btn' name='email' id='email' value='Email These Orders' onclick=\"window.location.href='purchase_checkout.php?action=email&purstr=".$purStr."'\">";
			echo "&nbsp;<input type='button' class='btn' name='print' id='print' value='Print These Orders' onclick='printme()'>";//\"window.location.href='purchase_checkout.php?action=print'\">";
	}
}
?>