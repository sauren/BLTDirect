<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequest.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierReturnRequestLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

$returnRequest = new SupplierReturnRequest($_REQUEST['requestid']);
$returnRequest->GetLines();
$returnRequest->Supplier->Get();
$returnRequest->Supplier->Contact->Get();
$returnRequest->Courier->Get();

if($returnRequest->Order->ID > 0) {
	$returnRequest->Order->GetLines();

} elseif($returnRequest->Purchase->ID > 0) {
	for($k=0; $k<count($returnRequest->Line); $k++) {
		$returnRequest->Line[$k]->PurchaseLine->Get();
	}
}

$isEditable = (strtolower($returnRequest->Status) != 'completed') ? true : false;

$types = array();

$data = new DataQuery(sprintf("SELECT SupplierReturnRequestLineTypeID, Name FROM supplier_return_request_line_type ORDER BY Name ASC"));
while($data->Row) {
	$types[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('requestid', 'Supplier Return Request ID', 'hidden', '', 'numeric_unsigned', 1, 11);

if($isEditable) {
	$form->AddField('courier', 'Courier', 'select', $returnRequest->Courier->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('courier', '0', '');

	$data = new DataQuery("SELECT Courier_ID, Courier_Name FROM courier ORDER BY Courier_Name ASC");
	while($data->Row) {
		$form->AddOption('courier', $data->Row['Courier_ID'], $data->Row['Courier_Name']);

		$data->Next();
	}
	$data->Disconnect();
}

if($returnRequest->Order->ID > 0) {
	for($k=0; $k<count($returnRequest->Order->Line); $k++) {
		$returnRequest->Order->Line[$k]->DespatchedFrom->Get();
	}
}

for($k=0; $k<count($returnRequest->Line); $k++) {
	$returnRequest->Line[$k]->Type->Get();
	$returnRequest->Line[$k]->Product->Get();

	if($returnRequest->Line[$k]->RelatedProduct->ID > 0) {
		$returnRequest->Line[$k]->RelatedProduct->Get();
	}
}

if($isEditable) {
	if($returnRequest->Order->ID > 0) {
		for($k=0; $k<count($returnRequest->Order->Line); $k++) {
			if(($returnRequest->Order->Line[$k]->DespatchedFrom->Type == 'S') && ($returnRequest->Order->Line[$k]->DespatchedFrom->Contact->ID == $returnRequest->Supplier->ID)) {
				$form->AddField(sprintf('available_product_%d', $returnRequest->Order->Line[$k]->ID), sprintf('Available Product for \'%s\'', $returnRequest->Order->Line[$k]->Product->Name), 'checkbox', 'N', 'boolean', 1, 1, false);
			}
		}
	}

	for($k=0; $k<count($returnRequest->Line); $k++) {
		$form->AddField(sprintf('quantity_%d', $returnRequest->Line[$k]->ID), sprintf('Quantity for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'text', $returnRequest->Line[$k]->Quantity, 'numeric_unsigned', 1, 11, true, 'size="3"');
		$form->AddField(sprintf('type_%d', $returnRequest->Line[$k]->ID), sprintf('Type for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'select', $returnRequest->Line[$k]->Type->ID, 'numeric_unsigned', 1, 11, true);
		$form->AddOption(sprintf('type_%d', $returnRequest->Line[$k]->ID), '0', '');

		foreach($types as $type) {
			$form->AddOption(sprintf('type_%d', $returnRequest->Line[$k]->ID), $type['SupplierReturnRequestLineTypeID'], $type['Name']);
		}

		$form->AddField(sprintf('reason_%d', $returnRequest->Line[$k]->ID), sprintf('Reason for \'%s\'', $returnRequest->Line[$k]->Product->Name), 'textarea', $returnRequest->Line[$k]->Reason, 'anything', 0, 240, false, 'rows="2" style="font-family: arial, sans-serif; width: 100%;"');
	}
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['debit'])) {
		$debit = new Debit();
        $debit->Supplier->ID = $returnRequest->Supplier->ID;
	    $debit->IsPaid = 'N';
		$debit->Status = 'Active';
		$debit->Person = $returnRequest->Supplier->Contact->Person;
		$debit->Organisation = $returnRequest->Supplier->Contact->Parent->Organisation->Name;
		$debit->Add();

		for($k=0; $k<count($returnRequest->Line); $k++) {
			if($returnRequest->Line[$k]->IsRejected == 'N') {
				$cost = $returnRequest->Line[$k]->Cost;

				switch($returnRequest->Line[$k]->HandlingMethod) {
					case 'R':
						$cost -= ($cost / 100) * $returnRequest->Line[$k]->HandlingCharge;
						break;
					case 'F':
						$cost -= $returnRequest->Line[$k]->HandlingCharge;
						break;
				}
				
				$line = new DebitLine();
			    $line->DebitID = $debit->ID;
				$line->Description = sprintf('Authorised return of \'%s\' (Auth No. %s)', $returnRequest->Line[$k]->Product->Name, $returnRequest->AuthorisationNumber);
				$line->Quantity = $returnRequest->Line[$k]->Quantity;
				$line->Product->ID = $returnRequest->Line[$k]->Product->ID;
				$line->SuppliedBy = $returnRequest->Supplier->ID;
				$line->Cost = $cost;
				$line->Total = $line->Cost * $line->Quantity;
				$line->Add();

				$debit->Total += $line->Total;
			}
		}
						
        $debit->Update();
            
		$returnRequest->Status = 'Completed';
		$returnRequest->Update();
		
	} elseif(isset($_REQUEST['addselectedproducts'])) {
		if($returnRequest->Order->ID > 0) {
			for($k=0; $k<count($returnRequest->Order->Line); $k++) {
				if(($returnRequest->Order->Line[$k]->DespatchedFrom->Type == 'S') && ($returnRequest->Order->Line[$k]->DespatchedFrom->Contact->ID == $returnRequest->Supplier->ID)) {
					if($form->GetValue(sprintf('available_product_%d', $returnRequest->Order->Line[$k]->ID)) == 'Y') {
						$requestLine = new SupplierReturnRequestLine();
						$requestLine->SupplierReturnRequestID = $returnRequest->ID;
						$requestLine->Product->ID = $returnRequest->Order->Line[$k]->Product->ID;
						$requestLine->Quantity = $returnRequest->Order->Line[$k]->Quantity;
						$requestLine->Add();

						$returnRequest->LinesFetched = false;
					}
				}
			}
		}
	} else {
		if(isset($_REQUEST['update']) || isset($_REQUEST['updateproducts'])) {
			for($k=0; $k<count($returnRequest->Line); $k++) {
				$form->Validate(sprintf('quantity_%d', $returnRequest->Line[$k]->ID));
				$form->Validate(sprintf('type_%d', $returnRequest->Line[$k]->ID));
				$form->Validate(sprintf('reason_%d', $returnRequest->Line[$k]->ID));

				if($form->Valid) {
					$returnRequest->Line[$k]->Quantity = $form->GetValue(sprintf('quantity_%d', $returnRequest->Line[$k]->ID));
					$returnRequest->Line[$k]->Type->ID = $form->GetValue(sprintf('type_%d', $returnRequest->Line[$k]->ID));
					$returnRequest->Line[$k]->Reason = $form->GetValue(sprintf('reason_%d', $returnRequest->Line[$k]->ID));
					$returnRequest->Line[$k]->Update();
				}
			}
		}

		if(isset($_REQUEST['update'])) {
			$form->Validate('courier');

			if($form->Valid) {
				$returnRequest->Courier->ID = $form->GetValue('courier');
			}

		}
	}

	if($form->Valid) {
		$returnRequest->Recalculate();

		redirect(sprintf('Location: %s?requestid=%d', $_SERVER['PHP_SELF'], $form->GetValue('requestid')));
	}
}

$page = new Page(sprintf('[#%d] Supplier Return Request Details', $returnRequest->ID), 'Manage this return request here.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('requestid');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left" valign="top"></td>
    <td align="right" valign="top">

	    <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
	      <tr>
	        <th>Supplier Return Request:</th>
	        <td>#<?php echo $returnRequest->ID; ?></td>
	      </tr>
	      <tr>
	        <th>Status:</th>
	        <td><?php echo $returnRequest->Status; ?></td>
	      </tr>
	      <tr>
	        <th>Supplier:</th>
	        <td>
	        	<?php
				$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE s.Supplier_ID=%d", mysql_real_escape_string($returnRequest->Supplier->ID)));
				echo ($data->TotalRows > 0) ? $data->Row['Supplier_Name'] : '&nbsp;';
				$data->Disconnect();
	        	?>
	        </td>
	      </tr>

	      <?php
	      if($returnRequest->Order->ID > 0) {
	      	?>

		      <tr>
		        <th>Order:</th>
		        <td><a href="order_details.php?orderid=<?php echo $returnRequest->Order->ID; ?>"><?php echo $returnRequest->Order->ID; ?></a></td>
		      </tr>

		     <?php
	      }

	      if($returnRequest->Purchase->ID > 0) {
	      	?>

		      <tr>
		        <th>Purchase:</th>
		        <td><a href="purchase_edit.php?pid=<?php echo $returnRequest->Purchase->ID; ?>"><?php echo $returnRequest->Purchase->ID; ?></a></td>
		      </tr>

		     <?php
	      }
	      ?>

	      <tr>
	        <th>Courier:</th>
	        <td><?php echo ($isEditable) ? $form->GetHTML('courier') : $returnRequest->Courier->Name; ?></td>
	      </tr>
	      <tr>
	        <th>Authorisation Number:</th>
	        <td><?php echo $returnRequest->AuthorisationNumber; ?></td>
	      </tr>
	      <tr>
	        <th>&nbsp;</th>
	        <td>&nbsp;</td>
	      </tr>
	      <tr>
	        <th>Created On:</th>
	        <td><?php echo cDatetime($returnRequest->CreatedOn, 'shortdate'); ?></td>
	      </tr>
	      <tr>
	        <th>Created By:</th>
	        <td>
	        	<?php
	        	$user = new User();
	        	$user->ID = $returnRequest->CreatedBy;

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
  	<td align="left" valign="top">
		<input name="printlabel" type="button" value="print label" class="btn" onclick="popUrl('supplier_return_request_print.php?requestid=<?php echo $returnRequest->ID; ?>', 800, 600);" />
		
		<?php
		if(strtolower($returnRequest->Status) == 'confirmed') {
			echo '<input name="debit" type="submit" value="debit" class="btn" />';
		}
		?>
		
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

		<?php
		if($returnRequest->Order->ID > 0) {
			?>

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
			 	<p><span class="pageSubTitle">Available Products</span><br /><span class="pageDescription">Listing available products despatched from this supplier for the original order.</span></p>

			 	<table cellspacing="0" class="orderDetails">
					<tr>

						<?php
						if($isEditable) {
							echo '<th nowrap="nowrap" style="padding-right: 5px;">&nbsp;</th>';
						}
						?>

						<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
			      		<th nowrap="nowrap" style="padding-right: 5px;">Name</th>
			      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>
			      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total</th>
			      	</tr>

					<?php
					if(count($returnRequest->Order->Line) > 0) {
						for($k=0; $k<count($returnRequest->Order->Line); $k++) {
							if(($returnRequest->Order->Line[$k]->DespatchedFrom->Type == 'S') && ($returnRequest->Order->Line[$k]->DespatchedFrom->Contact->ID == $returnRequest->Supplier->ID)) {
								?>

								<tr>

									<?php
									if($isEditable) {
										echo sprintf('<td nowrap="nowrap" width="1%%">%s</td>', $form->GetHTML('available_product_'.$returnRequest->Order->Line[$k]->ID));
									}
									?>

						      		<td nowrap="nowrap"><?php echo number_format($returnRequest->Order->Line[$k]->Quantity, 2, '.', ''); ?></td>
						      		<td nowrap="nowrap"><?php echo $returnRequest->Order->Line[$k]->Product->ID; ?></td>
						      		<td nowrap="nowrap"><?php echo $returnRequest->Order->Line[$k]->Product->Name; ?></td>
						      		<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($returnRequest->Order->Line[$k]->Cost, 2), 2, '.', ','); ?></td>
						      		<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($returnRequest->Order->Line[$k]->Cost * $returnRequest->Order->Line[$k]->Quantity, 2), 2, '.', ','); ?></td>
								</tr>

								<?php
							}
						}
					} else {
				      	?>

				      	<tr>
							<td colspan="<?php echo ($isEditable) ? 6 : 5; ?>" align="center">No products available for viewing.</td>
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
								<input type="submit" name="addselectedproducts" value="add selected products" class="btn" />
							</td>
							<td align="right"></td>
						</tr>
					</table>

					<?php
				}
				?>

			</div>
			<br />

			<?php
		}
		?>

		<div style="background-color: #eee; padding: 10px 0 10px 0;">
		 	<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing products selected for return to this supplier.</span></p>

		 	<table cellspacing="0" class="orderDetails">
				<tr>
					<th nowrap="nowrap" style="padding-right: 5px;">Quantity<br />&nbsp;</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Quickfind<br />&nbsp;</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Name<br />&nbsp;</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Type<br />&nbsp;</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Related<br />Product</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Reason<br />&nbsp;</th>

		      		<?php
		      		if($returnRequest->Purchase->ID > 0) {
		      			echo '<th nowrap="nowrap" style="padding-right: 5px;">Advice<br />Note</th>';
		      		}
		      		?>

					<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost<br />&nbsp;</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Handling<br />&nbsp;</th>
		      		<th nowrap="nowrap" style="padding-right: 5px; text-align: center;">Is Rejected<br />&nbsp;</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Rejected<br />Reason</th>
		      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total<br />&nbsp;</th>
		      	</tr>

				<?php
				if(count($returnRequest->Line) > 0) {
					for($k=0; $k<count($returnRequest->Line); $k++) {
						$cost = 0;

						if($returnRequest->Line[$k]->IsRejected == 'N') {
							$cost += $returnRequest->Line[$k]->Cost * $returnRequest->Line[$k]->Quantity;

							switch($returnRequest->Line[$k]->HandlingMethod) {
								case 'R':
									$cost -= ($cost / 100) * $returnRequest->Line[$k]->HandlingCharge;
									break;
								case 'F':
									$cost -= $returnRequest->Line[$k]->HandlingCharge;
									break;
							}
						}

						$handlingCharge = number_format($returnRequest->Line[$k]->HandlingCharge, 2, '.', '');

						switch($returnRequest->Line[$k]->HandlingMethod) {
							case 'R':
								$handlingText = sprintf('%s%%', $handlingCharge);
								break;
							case 'F':
								$handlingText = sprintf('&pound;%s', $handlingCharge);
								break;
							default:
								$handlingText = '';
								break;
						}
						?>

						<tr>
				      		<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('quantity_%d', $returnRequest->Line[$k]->ID)) : number_format($returnRequest->Line[$k]->Quantity, 2, '.', ''); ?></td>
				      		<td nowrap="nowrap"><?php echo $returnRequest->Line[$k]->Product->ID; ?></td>
				      		<td><?php echo $returnRequest->Line[$k]->Product->Name; ?></td>
				      		<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('type_%d', $returnRequest->Line[$k]->ID)) : $returnRequest->Line[$k]->Type->Name; ?></td>
				      		<td><?php echo ($isEditable) ? sprintf('<a href="supplier_return_request_product.php?requestid=%d&lineid=%d"><img src="images/icon_search_1.gif" align="absmiddle" /></a> ', $returnRequest->ID, $returnRequest->Line[$k]->ID) : ''; ?><?php echo ($returnRequest->Line[$k]->RelatedProduct->ID > 0) ? sprintf('%d: %s', $returnRequest->Line[$k]->RelatedProduct->ID, $returnRequest->Line[$k]->RelatedProduct->Name) : 'None'; ?></td>
				      		<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('reason_%d', $returnRequest->Line[$k]->ID)) : $returnRequest->Line[$k]->Reason; ?></td>

				      		<?php
				      		if($returnRequest->Purchase->ID > 0) {
				      			echo sprintf('<td nowrap="nowrap">%s</td>', $returnRequest->Line[$k]->PurchaseLine->AdviceNote);
				      		}
				      		?>

				      		<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($returnRequest->Line[$k]->Cost, 2), 2, '.', ','); ?></td>
				      		<td nowrap="nowrap"><?php echo $handlingText; ?></td>
							<td nowrap="nowrap" align="center"><?php echo $returnRequest->Line[$k]->IsRejected; ?></td>
							<td nowrap="nowrap"><?php echo $returnRequest->Line[$k]->RejectedReason; ?></td>
					      	<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($cost, 2), 2, '.', ','); ?></td>
						</tr>

						<?php
					}
					?>

					<tr>
						<td colspan="<?php echo ($returnRequest->Purchase->ID > 0) ? 11 : 10; ?>">&nbsp;</td>
				      	<td nowrap="nowrap" align="right"><strong>&pound;<?php echo number_format(round($returnRequest->Total, 2), 2, '.', ','); ?></strong></td>
					</tr>

					<?php
				} else {
			      	?>

			      	<tr>
						<td colspan="<?php echo ($returnRequest->Purchase->ID > 0) ? 12 : 11; ?>" align="center">No products available for viewing.</td>
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