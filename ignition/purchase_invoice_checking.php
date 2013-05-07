<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

if($action == 'check') {
	$session->Secure(3);
	check();
} elseif($action == 'viewpurchase') {
	$session->Secure(2);
	viewpurchase();
} else {
	$session->Secure(2);
	view();
}

function check() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitLine.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseBatch.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseBatchLine.php');

	$batch = new PurchaseBatch($_REQUEST['id']);
	$batch->Purchase->Get();

	$script = '<script langauge="javascript">';
	$script .= 'var displayFaultyField = function(obj, id) {';
	$script .= 'if(obj && obj.value.length > 0) {';
	$script .= 'document.getElementById(id).style.display = "block"';
	$script .= '} else {';
	$script .= 'document.getElementById(id).style.display = "none"';
	$script .= '}';
	$script .= '}';
	$script .= '</script>';

	if($batch->Status == 'Unchecked') {
		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('action','Action','hidden','check','alpha',5,5);
		$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
		$form->AddField('id','','hidden',$_REQUEST['id'],'numeric_unsigned',1,11);

		for($i=0; $i < count($batch->Line); $i++) {
			$form->AddField('qty_faulty_'.$batch->Line[$i]->ID, 'Qty Faulty for '.$batch->Line[$i]->PurchaseLine->Product->Name, 'text', '', 'numeric_unsigned', 1, 9, false, 'size="3" onkeyup="displayFaultyField(this, \'qty_faulty_reason_row_'.$batch->Line[$i]->ID.'\');"');
			$form->AddField('qty_returned_'.$batch->Line[$i]->ID, 'Qty Returned for '.$batch->Line[$i]->PurchaseLine->Product->Name, 'text', '', 'numeric_unsigned', 1, 9, false, 'size="3"');
			$form->AddField('qty_short_'.$batch->Line[$i]->ID, 'Qty Short for '.$batch->Line[$i]->PurchaseLine->Product->Name, 'text', '', 'numeric_unsigned', 1, 9, false, 'size="3"');
			$form->AddField('price_diff_'.$batch->Line[$i]->ID, 'Price Difference for '.$batch->Line[$i]->PurchaseLine->Product->Name, 'text', '', 'float', 1, 11, false, 'size="3"');

			$form->AddField('qty_faulty_reason_'.$batch->Line[$i]->ID, 'Faulty Reason for '.$batch->Line[$i]->PurchaseLine->Product->Name, 'text', '', 'paragraph', 1, 200, false, 'style="width: 100%;"');
		}

		if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == true)) {
			if($form->Validate()) {
				for($i=0; $i < count($batch->Line); $i++) {
					$qty = 0;
					$itemError = false;

					if(strlen($form->GetValue('qty_faulty_'.$batch->Line[$i]->ID)) && ($form->GetValue('qty_faulty_'.$batch->Line[$i]->ID) > 0)) {
						$qty += $form->GetValue('qty_faulty_'.$batch->Line[$i]->ID);

						if($form->GetValue('qty_faulty_'.$batch->Line[$i]->ID) > $batch->Line[$i]->Quantity) {
							$form->AddError('Qty Faulty for '.$batch->Line[$i]->PurchaseLine->Product->Name.' cannot exceed the quantity received for this batch');
							$itemError = true;
						}
					}

					if(strlen($form->GetValue('qty_returned_'.$batch->Line[$i]->ID)) && ($form->GetValue('qty_returned_'.$batch->Line[$i]->ID) > 0)){
						$qty += $form->GetValue('qty_returned_'.$batch->Line[$i]->ID);

						if($form->GetValue('qty_returned_'.$batch->Line[$i]->ID) > $batch->Line[$i]->Quantity) {
							$form->AddError('Qty Returned for '.$batch->Line[$i]->PurchaseLine->Product->Name.' cannot exceed the quantity received for this batch');
							$itemError = true;
						}
					}

					if(strlen($form->GetValue('qty_short_'.$batch->Line[$i]->ID)) && ($form->GetValue('qty_short_'.$batch->Line[$i]->ID) > 0)) {
						$qty += $form->GetValue('qty_short_'.$batch->Line[$i]->ID);

						if($form->GetValue('qty_short_'.$batch->Line[$i]->ID) > $batch->Line[$i]->Quantity) {
							$form->AddError('Qty Short for '.$batch->Line[$i]->PurchaseLine->Product->Name.' cannot exceed the quantity received for this batch');
							$itemError = true;
						}
					}

					if(($qty > $batch->Line[$i]->Quantity) && (!$itemError)) {
						$form->AddError('Combined quantities for '.$batch->Line[$i]->PurchaseLine->Product->Name.' cannot exceed the quantity received for this batch');
					}
				}

				if($form->Valid) {
					$batch->Status = 'Checked';

					$debitLines = array();
					$debitTotal = 0;

					for($i=0; $i < count($batch->Line); $i++) {
						$qtyRemaining = $batch->Line[$i]->Quantity;

						if(strlen($form->GetValue('qty_faulty_'.$batch->Line[$i]->ID)) && ($form->GetValue('qty_faulty_'.$batch->Line[$i]->ID) > 0)) {
							$line = new DebitLine();
							$line->Description = $batch->Line[$i]->PurchaseLine->Product->Name;
							$line->Quantity = $form->GetValue('qty_faulty_'.$batch->Line[$i]->ID);
							$line->Product->ID = $batch->Line[$i]->PurchaseLine->Product->ID;

							if(strlen($form->GetValue('price_diff_'.$batch->Line[$i]->ID)) && ($form->GetValue('price_diff_'.$batch->Line[$i]->ID) > 0)) {
								$line->Cost = $form->GetValue('price_diff_'.$batch->Line[$i]->ID);
							} else {
								$line->Cost = $batch->Line[$i]->PurchaseLine->Cost;
							}

							$line->Total = number_format(($line->Cost * $form->GetValue('qty_faulty_'.$batch->Line[$i]->ID)), 2, '.', '');
							$line->Reason = 'Goods Faulty';

							if(strlen($form->GetValue('qty_faulty_reason_'.$batch->Line[$i]->ID)) > 0) {
								$line->Reason .= ': ' . $form->GetValue('qty_faulty_reason_'.$batch->Line[$i]->ID);
							}

							$debitLines[] = $line;

							$debitTotal += $line->Total;

							$qtyRemaining -= $line->Quantity;
						}

						if(strlen($form->GetValue('qty_returned_'.$batch->Line[$i]->ID)) && ($form->GetValue('qty_returned_'.$batch->Line[$i]->ID) > 0)) {
							$line = new DebitLine();
							$line->Description = $batch->Line[$i]->PurchaseLine->Product->Name;
							$line->Quantity = $form->GetValue('qty_returned_'.$batch->Line[$i]->ID);
							$line->Product->ID = $batch->Line[$i]->PurchaseLine->Product->ID;

							if(strlen($form->GetValue('price_diff_'.$batch->Line[$i]->ID)) && ($form->GetValue('price_diff_'.$batch->Line[$i]->ID) > 0)) {
								$line->Cost = $form->GetValue('price_diff_'.$batch->Line[$i]->ID);
							} else {
								$line->Cost = $batch->Line[$i]->PurchaseLine->Cost;
							}

							$line->Total = number_format(($batch->Line[$i]->PurchaseLine->Cost * $form->GetValue('qty_returned_'.$batch->Line[$i]->ID)), 2, '.', '');
							$line->Reason = 'Goods Returned';

							$debitLines[] = $line;

							$debitTotal += $line->Total;

							$qtyRemaining -= $line->Quantity;
						}

						if(strlen($form->GetValue('qty_short_'.$batch->Line[$i]->ID)) && ($form->GetValue('qty_short_'.$batch->Line[$i]->ID) > 0)) {
							$line = new DebitLine();
							$line->Description = $batch->Line[$i]->PurchaseLine->Product->Name;
							$line->Quantity = $form->GetValue('qty_short_'.$batch->Line[$i]->ID);
							$line->Product->ID = $batch->Line[$i]->PurchaseLine->Product->ID;

							if(strlen($form->GetValue('price_diff_'.$batch->Line[$i]->ID)) && ($form->GetValue('price_diff_'.$batch->Line[$i]->ID) > 0)) {
								$line->Cost = $form->GetValue('price_diff_'.$batch->Line[$i]->ID);
							} else {
								$line->Cost = $batch->Line[$i]->PurchaseLine->Cost;
							}

							$line->Total = number_format(($batch->Line[$i]->PurchaseLine->Cost * $form->GetValue('qty_short_'.$batch->Line[$i]->ID)), 2, '.', '');
							$line->Reason = 'Goods Shortage';

							$debitLines[] = $line;

							$debitTotal += $line->Total;

							$qtyRemaining -= $line->Quantity;
						}

						if(strlen($form->GetValue('price_diff_'.$batch->Line[$i]->ID)) && ($form->GetValue('price_diff_'.$batch->Line[$i]->ID) > 0)) {
							if($qtyRemaining > 0) {
								$line = new DebitLine();
								$line->Description = $batch->Line[$i]->PurchaseLine->Product->Name;
								$line->Quantity = $qtyRemaining;
								$line->Product->ID = $batch->Line[$i]->PurchaseLine->Product->ID;
								$line->Cost = ($form->GetValue('price_diff_'.$batch->Line[$i]->ID) - $batch->Line[$i]->PurchaseLine->Cost);
								$line->Total = number_format(($line->Cost * $qtyRemaining), 2, '.', '');
								$line->Reason = 'Price Discrepancy';

								$debitLines[] = $line;

								$debitTotal += $line->Total;
							}
						}
					}

					if(count($debitLines) > 0) {
						$debit = new Debit();
						$debit->Supplier->ID = $batch->Purchase->SupplierID;
						$debit->Total = $debitTotal;
						$debit->IsPaid = 'N';
						$debit->Person = $batch->Purchase->Supplier;
						$debit->Organisation = $batch->Purchase->SupOrg;
						$debit->Status = 'Active';
						$debit->Add();

						for($i = 0; $i < count($debitLines); $i++) {
							$debitLines[$i]->DebitID = $debit->ID;
							$debitLines[$i]->Add();
						}
					}

					$batch->Update();

					redirect(sprintf("Location: %s?action=viewpurchase&id=%d", $_SERVER['PHP_SELF'], $batch->Purchase->ID));
				}
			}
		}

		$page = new Page("<a href=\"".$_SERVER['PHP_SELF']."\">Invoice Checking</a> &gt; <a href=\"".$_SERVER['PHP_SELF']."?action=viewpurchase&id=".$batch->Purchase->ID."\">Invoice Checking Purchase Order [#".$batch->Purchase->ID."]</a> &gt; Invoice Checking Batch [#".$_REQUEST['id']."]",'Here you can invoice check incomplete purchase orders batches.');
		$page->AddToHead($script);
		$page->Display('header');

		if(!$form->Valid) {
			echo $form->GetError();
			echo "<br>";
		}
		?>

		<table width="100%"  border="0" cellspacing="0" cellpadding="0">
	      <tr>
		    <td>
	          <table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
	          <tr>
	            <td valign="top" class="billing"><p> <strong>Billing Address:</strong><br />
	            <?php echo $batch->Purchase->GetSupplierAddress(); ?>
	            <td valign="top" class="shipping"><p> <strong>Shipping Address:</strong><br />
	            <?php echo $batch->Purchase->GetBranchShip(); ?>
	          </tr>
	        </table>
	      </tr>
	      <tr>
	        <td colspan="2">
			  <br>
			   <br>

		        <table cellspacing="0" class="orderDetails">
		          <tr>
		            <th>Qty Received</th>
		            <th>Product</th>
		            <th>Quickfind</th>
		            <th>Cost</th>
		            <th>Line Cost</th>
		            <th>Qty Faulty</th>
		            <th>Qty Returned</th>
		            <th>Qty Short</th>
		            <th>Price Discrepancy *</th>
		          </tr>

		         <?php
					echo $form->Open();
					echo $form->GetHTML('action');
					echo $form->GetHTML('confirm');
					echo $form->GetHTML('id');

					$subtotal = 0;

		          	for($i=0; $i < count($batch->Line); $i++){
		          		$batch->Line[$i]->PurchaseLine->Product->Get();
		          		$subtotal += $batch->Line[$i]->PurchaseLine->Cost * $batch->Line[$i]->Quantity;
						?>

		                  <tr>
		                    <td><?php echo $batch->Line[$i]->Quantity; ?>x</td>
		                    <td><?php echo $batch->Line[$i]->PurchaseLine->Product->Name; ?><br><small>Part Number: <?php echo $batch->Line[$i]->PurchaseLine->Product->SKU;?></small></td>
							<td><a href='product_profile.php?pid=<?php echo $batch->Line[$i]->PurchaseLine->Product->ID;?>'><?php echo $batch->Line[$i]->PurchaseLine->Product->ID;?></a></td>
							<td>&pound;<?php echo number_format($batch->Line[$i]->PurchaseLine->Cost, 2, '.', ','); ?></td>
							<td>&pound;<?php echo number_format(($batch->Line[$i]->PurchaseLine->Cost * $batch->Line[$i]->Quantity), 2, '.', ','); ?></td>
							<td><?php print $form->GetHTML('qty_faulty_'.$batch->Line[$i]->ID); ?>x</td>
							<td><?php print $form->GetHTML('qty_returned_'.$batch->Line[$i]->ID); ?>x</td>
							<td><?php print $form->GetHTML('qty_short_'.$batch->Line[$i]->ID); ?>x</td>
							<td>&pound;<?php print $form->GetHTML('price_diff_'.$batch->Line[$i]->ID); ?></td>
		                  </tr>
		                  <tr id="qty_faulty_reason_row_<?php print $batch->Line[$i]->ID; ?>" style="display: none;">
		                  	<td>Reason for fault:</td>
		                  	<td colspan="8"><?php print $form->GetHTML('qty_faulty_reason_'.$batch->Line[$i]->ID); ?></td>
		                  </tr>

		              <?php
		              }
				  ?>

		          </table>
			<br />
			<table border="0" width="100%" cellpadding="0" cellspacing="0">
              <tr>
              	<td width="75%"></td>
                <td align="right">
                  <table border="0" cellspacing="0" class="orderDetails">
                    <tr>
                      <th colspan="2" align="left">Tax</th>
                    </tr>
                    <tr>
                      <td nowrap="nowrap">Sub Total:</td>
                      <td align="right">&pound;<?php print number_format($subtotal, 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                      <td nowrap="nowrap">VAT:</td>
                      <td align="right">&pound;<?php print number_format(($subtotal * ((strtotime($batch->Purchase->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($batch->Purchase->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))), 2, '.', ','); ?></td>
                    </tr>
                    <tr>
                      <td nowrap="nowrap">Total:</td>
                      <td align="right">&pound;<?php print number_format(($subtotal + ($subtotal * ((strtotime($batch->Purchase->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($batch->Purchase->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2)))), 2, '.', ','); ?></td>
                    </tr>
                  </table>
                 </td>
              </tr>
            </table>

		          <br>
		          <p>* enter invoiced price for a single item of this product if inconsitent with purchased cost.</p>
		          <br>

	         	 <input type="button" class="btn" value="back" onclick="window.self.location.href='purchase_invoice_checking.php?action=viewpurchase&id=<?php print $batch->Purchase->ID; ?>'" />&nbsp;
	         	 <input type="submit" class="btn" value="submit" name="check" />

	        </td>
	      </tr>
	   </table>

		<?php
		echo $form->Close();
	} else {
		?>

		<table width="100%"  border="0" cellspacing="0" cellpadding="0">
	      <tr>
		    <td>
	          <table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
	          <tr>
	            <td valign="top" class="billing"><p> <strong>Billing Address:</strong><br />
	            <?php echo $batch->Purchase->GetSupplierAddress(); ?>
	            <td valign="top" class="shipping"><p> <strong>Shipping Address:</strong><br />
	            <?php echo $batch->Purchase->GetBranchShip(); ?>
	          </tr>
	        </table>
	      </tr>
	      <tr>
	        <td colspan="2">
			  <br>
			  <br>

		        <table cellspacing="0" class="orderDetails">
		          <tr>
		            <th>Qty Received</th>
		            <th>Product</th>
		            <th>Quickfind</th>
		            <th>Cost</th>
		            <th>Line Cost</th>
		          </tr>

		         <?php
		         	$subtotal = 0;

		          	for($i=0; $i < count($batch->Line); $i++){
		          		$batch->Line[$i]->PurchaseLine->Product->Get();
		          		$subtotal += $batch->Line[$i]->PurchaseLine->Cost * $batch->Line[$i]->Quantity;
						?>

		                  <tr>
		                    <td><?php echo $batch->Line[$i]->Quantity; ?>x</td>
		                    <td><?php echo $batch->Line[$i]->PurchaseLine->Product->Name; ?><br><small>Part Number: <?php echo $batch->Line[$i]->PurchaseLine->Product->SKU;?></small></td>
							<td><a href='product_profile.php?pid=<?php echo $batch->Line[$i]->PurchaseLine->Product->ID;?>'><?php echo $batch->Line[$i]->PurchaseLine->Product->ID;?></a></td>
							<td align="right">&pound;<?php echo number_format($batch->Line[$i]->PurchaseLine->Cost, 2, '.', ','); ?></td>
							<td align="right">&pound;<?php echo number_format(($batch->Line[$i]->PurchaseLine->Cost * $batch->Line[$i]->Quantity), 2, '.', ','); ?></td>
		                  </tr>

		              <?php
		              }
				  ?>

		          </table>
		          <br><br>

	         	 <input type="button" class="btn" value="back" onclick="window.self.location.href='purchase_invoice_checking.php?action=viewpurchase&id=<?php print $batch->Purchase->ID; ?>'" />&nbsp;

	        </td>
	      </tr>
	   </table>

	   <br />
		<table border="0" width="100%" cellpadding="0" cellspacing="0">
          <tr>
          	<td width="75%"></td>
            <td align="right">
              <table border="0" cellspacing="0" class="orderDetails">
                <tr>
                  <th colspan="2" align="left">Tax</th>
                </tr>
                <tr>
                  <td nowrap="nowrap">Sub Total:</td>
                  <td align="right">&pound;<?php print number_format($subtotal, 2, '.', ','); ?></td>
                </tr>
                <tr>
                  <td nowrap="nowrap">VAT:</td>
                  <td align="right">&pound;<?php print number_format(($subtotal * ((strtotime($batch->Purchase->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($batch->Purchase->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))), 2, '.', ','); ?></td>
                </tr>
                <tr>
                  <td nowrap="nowrap">Total:</td>
                  <td align="right">&pound;<?php print number_format(($subtotal + ($subtotal * ((strtotime($batch->Purchase->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($batch->Purchase->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2)))), 2, '.', ','); ?></td>
                </tr>
              </table>
             </td>
          </tr>
        </table>

		<?php
	}
}

function viewpurchase(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page("<a href=\"".$_SERVER['PHP_SELF']."\">Invoice Checking</a> &gt; Invoice Checking Purchase Order [#".$_REQUEST['id']."]","Here you can invoice check incomplete purchase orders.");
	$page->Display('header');

	$table = new DataTable("batchLine");
	$table->SetSQL(sprintf("SELECT * FROM purchase_batch WHERE Purchase_ID=%d", $_REQUEST['id']));
	$table->AddField('Batch ID#','Purchase_Batch_ID');
	$table->AddField('Date Received','Created_On');
	$table->AddField('Status','Batch_Status');
	$table->AddLink('purchase_invoice_checking.php?action=check&id=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Invoice check this purchase order batch\" border=\"0\">",'Purchase_Batch_ID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Created_On');
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page("Invoice Checking","Here you can invoice check incomplete purchase orders.");
	$page->Display('header');

	$table = new DataTable("batch");
	$table->SetSQL("SELECT p.* FROM purchase AS p INNER JOIN purchase_batch AS b ON p.Purchase_ID=b.Purchase_ID WHERE (p.Purchase_Status LIKE 'Fulfilled' OR p.Purchase_Status LIKE 'Unfulfilled' OR p.Purchase_Status LIKE 'Partially Fulfilled') AND b.Batch_Status LIKE 'Unchecked' GROUP BY p.Purchase_ID");
	$table->AddField('ID#','Purchase_ID');
	$table->AddField('Date Ordered','Purchased_On');
	$table->AddField('Organisation','Supplier_Organisation_Name');
	$table->AddField('First Name','Supplier_First_Name');
	$table->AddField('Last Name','Supplier_Last_Name');
	$table->AddField('Custom Reference', 'Custom_Reference_Number');
	$table->AddField('Notes','Order_Note');
	$table->AddLink('purchase_invoice_checking.php?action=viewpurchase&id=%s',"<img src=\"./images/folderopen.gif\" alt=\"Invoice check batches for this purchase order\" border=\"0\">",'Purchase_ID');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Supplier_Organisation_Name, Purchased_On');
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
}

require_once('lib/common/app_footer.php');
?>