<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierInvoiceQuery.php');

$session->Secure(2);

$query = new SupplierInvoiceQuery();

if(!isset($_REQUEST['queryid']) || !$query->Get($_REQUEST['queryid'])) {
	redirectTo('supplier_supplier_invoice_queries_pending.php');
}

if($query->Supplier->ID != $session->Supplier->ID) {
	redirectTo('supplier_supplier_invoice_queries_pending.php');
}

$query->Supplier->Get();
$query->Supplier->Contact->Get();

$page = new Page(sprintf('[#%d] Supplier Invoice Query Details', $query->ID), 'Manage this invoice query here.');
$page->Display('header');
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
					<th>Status:</th>
					<td><?php echo $query->Status; ?></td>
				</tr>
				<tr>
					<th>Supplier:</th>
					<td><?php echo sprintf('%s %s%s', $query->Supplier->Contact->Person->Name, $query->Supplier->Contact->Person->LastName, ($query->Supplier->Contact->Parent->ID > 0) ? sprintf(' (%s)', $query->Supplier->Contact->Parent->Organisation->Name) : ''); ?></td>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<th>Created On:</th>
					<td><?php echo cDatetime($query->CreatedOn, 'shortdate'); ?></td>
				</tr>
			</table>
			<br />

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
				    <td nowrap="nowrap"><?php echo number_format($query->Quantity, 2, '.', ''); ?></td>
				    <td><?php echo stripslashes($query->Description); ?></td>
				    <td nowrap="nowrap"><?php echo (($query->Product->ID > 0) ? $query->Product->ID : ''); ?></td>
				    <td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($query->ChargeStandard, 2), 2, '.', ','); ?></td>
				    <td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($query->ChargeReceived, 2), 2, '.', ','); ?></td>
				    <td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($query->Cost, 2), 2, '.', ','); ?></td>
					<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($query->Cost * $query->Quantity, 2), 2, '.', ','); ?></td>
				</tr>
				<tr>
					<td colspan="6">&nbsp;</td>
				    <td nowrap="nowrap" align="right"><strong>&pound;<?php echo number_format(round($query->Total, 2), 2, '.', ','); ?></strong></td>
				</tr>
		    </table>
		    <br />

		</div>

    </td>
  </tr>
</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');