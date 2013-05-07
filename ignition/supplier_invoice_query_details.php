<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DebitLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierInvoiceQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

$query = new SupplierInvoiceQuery($_REQUEST['queryid']);
$query->Supplier->Get();
$query->Supplier->Contact->Get();

$isEditable = (strtolower($query->Status) == 'pending') ? true : false;

if($action == "changestatus") {
	$query->Status = $_REQUEST['status'];
	$query->Update();

	redirect(sprintf('Location: ?queryid=%d', $query->ID));

} elseif($action == "email") {
	$query->EmailSupplier();

	redirect(sprintf('Location: ?queryid=%d', $query->ID));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('queryid', 'Supplier Invoice Query ID', 'hidden', '', 'numeric_unsigned', 1, 11);

if($isEditable) {
	$form->AddField('invoiceamount', 'Invoice Amount', 'text', $query->InvoiceAmount, 'float', 1, 11);
	$form->AddField('quantity', 'Quantity', 'text', $query->Quantity, 'numeric_unsigned', 1, 11, true, 'size="3"');
	$form->AddField('description', 'Description', 'textarea', $query->Description, 'anything', 1, 240, true, 'rows="3" style="width: 100%; font-family: arial, sans-serif;"');
	$form->AddField('pid', 'Product ID', 'text', ($query->Product->ID > 0) ? $query->Product->ID : '', 'numeric_unsigned', 1, 11, false, 'size="3"');
	$form->AddField('chargestandard', 'CPO Price', 'text', $query->ChargeStandard, 'float', 1, 11, true, 'size="3"');
	$form->AddField('chargereceived', 'Charge Received', 'text', $query->ChargeReceived, 'float', 1, 11, true, 'size="3"');
	$form->AddField('cost', 'Cost', 'text', $query->Cost, 'float', 1, 11, true, 'size="3"');
}

if(isset($_REQUEST['confirm'])) {
	if(isset($_REQUEST['debit'])) {
        $debit = new Debit();
        $debit->Supplier->ID = $query->Supplier->ID;
	    $debit->IsPaid = 'N';
		$debit->Status = 'Active';
		$debit->Person = $query->Supplier->Contact->Person;
		$debit->Organisation = $query->Supplier->Contact->Parent->Organisation->Name;
		$debit->Add();

        $line = new DebitLine();
		$line->DebitID = $debit->ID;
		$line->Description = $query->Description;
		$line->Quantity = $query->Quantity;
		$line->Product->ID = $query->Product->ID;
		$line->SuppliedBy = $query->Supplier->ID;
		$line->Cost = $query->Cost;
		$line->Total = $line->Cost * $line->Quantity;
		$line->Add();

		$debit->Total += $line->Total;
		$debit->Update();

		$query->DebitID = $debit->ID;
        $query->Status = 'Resolved';
		$query->Update();

		redirect(sprintf('Location: ?queryid=%d', $form->GetValue('queryid')));
	} else {
		if(isset($_REQUEST['update'])) {
			$form->Validate('invoiceamount');
			$form->Validate('quantity');
			$form->Validate('description');
			$form->Validate('pid');
			$form->Validate('chargestandard');
			$form->Validate('chargereceived');
			$form->Validate('cost');

			if($form->Valid) {
				if($form->GetValue('pid') > 0) {
					$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product WHERE Product_ID=%d", mysql_real_escape_string($form->GetValue('pid'))));
					if($data->Row['Count'] == 0) {
						$form->AddError(sprintf('Product ID \'%d\'could not be found.', $form->GetValue('pid')), 'pid');
					}
					$data->Disconnect();
				}

				if($form->Valid) {
					$query->InvoiceAmount = $form->GetValue('invoiceamount');
					$query->Quantity = $form->GetValue('quantity');
					$query->Description = $form->GetValue('description');
					$query->Product->ID = $form->GetValue('pid');
					$query->ChargeStandard = $form->GetValue('chargestandard');
					$query->ChargeReceived = $form->GetValue('chargereceived');
					$query->Cost = $form->GetValue('cost');
				}
			}
		}

		if($form->Valid) {
			$query->Recalculate();

			redirect(sprintf('Location: ?queryid=%d', $form->GetValue('queryid')));
		}
	}
}

$page = new Page(sprintf('[#%d] Supplier Invoice Query Details', $query->ID), 'Manage this invoice query here.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('queryid');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left" valign="top"></td>
    <td align="right" valign="top">

	    <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
	      <tr>
	        <th>Supplier Invoice Query:</th>
	        <td>#<?php echo $query->ID; ?></td>
	      </tr>
          <tr>
	        <th>Invoice Reference:</th>
	        <td><?php echo $query->InvoiceReference; ?></td>
	      </tr>
          <tr>
	        <th>Invoice Date:</th>
	        <td><?php echo ($query->InvoiceDate != '0000-00-00 00:00:00') ? cDatetime($query->InvoiceDate, 'shortdate') : ''; ?></td>
	      </tr>
	      <tr>
	        <th>Invoice Amount:</th>
	        <td><?php echo ($isEditable) ? $form->GetHTML('invoiceamount') : $query->InvoiceAmount; ?></td>
	      </tr>
	      <tr>
	        <th>Status:</th>
	        <td><?php echo $query->Status; ?></td>
	      </tr>
	      <tr>
	        <th>Supplier:</th>
	        <td>
	        	<?php
				$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE s.Supplier_ID=%d", mysql_real_escape_string($query->Supplier->ID)));
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
	        <td><?php echo cDatetime($query->CreatedOn, 'shortdate'); ?></td>
	      </tr>
	      <tr>
	        <th>Created By:</th>
	        <td>
	        	<?php
	        	$user = new User();
	        	$user->ID = $query->CreatedBy;

	        	if($user->Get()) {
	        		echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName));
	        	}
	        	?>
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
			?>

			<input name="debit" type="submit" value="debit" class="btn" />
			<input name="resolved" type="button" value="resolved" class="btn" onclick="window.self.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?queryid=<?php echo $form->GetValue('queryid'); ?>&action=changestatus&status=Resolved';" />
			<input name="email" type="button" value="email" class="btn" onclick="window.self.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?queryid=<?php echo $form->GetValue('queryid'); ?>&action=email';" />

			<?php
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

		<div style="background-color: #eee; padding: 10px 0 10px 0;">
			<p><span class="pageSubTitle">Line</span><br /><span class="pageDescription">The invoice query line for this supplier.</span></p>

		 	<table cellspacing="0" class="orderDetails">
				<tr>
					<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Description</th>
		      		<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
		      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">PO Price</th>
		      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Charge Received</th>
					<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Difference</th>
		      		<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total</th>
		      	</tr>
				<tr>
				    <td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML('quantity') : number_format($query->Quantity, 2, '.', ''); ?></td>
				    <td><?php echo ($isEditable) ? $form->GetHTML('description') : $query->Description; ?></td>
				    <td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML('pid') : (($query->Product->ID > 0) ? $query->Product->ID : ''); ?></td>
				    <td nowrap="nowrap" align="right">&pound;<?php echo ($isEditable) ? $form->GetHTML('chargestandard') : number_format(round($query->ChargeStandard, 2), 2, '.', ','); ?></td>
				    <td nowrap="nowrap" align="right">&pound;<?php echo ($isEditable) ? $form->GetHTML('chargereceived') : number_format(round($query->ChargeReceived, 2), 2, '.', ','); ?></td>
				    <td nowrap="nowrap" align="right">&pound;<?php echo ($isEditable) ? $form->GetHTML('cost') : number_format(round($query->Cost, 2), 2, '.', ','); ?></td>
					<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($query->Cost * $query->Quantity, 2), 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td colspan="6">&nbsp;</td>
				    <td nowrap="nowrap" align="right"><strong>&pound;<?php echo number_format(round($query->Total, 2), 2, '.', ','); ?></strong></td>
				</tr>
		    </table><br />

			<?php
			if($isEditable) {
				?>

				<table cellspacing="0" cellpadding="0" border="0" width="100%">
					<tr>
						<td align="left">
							<input type="submit" name="update" value="update" class="btn" />
						</td>
						<td align="right">
						</td>
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