<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');

$session->Secure(2);

$debit = new Debit();

if(!isset($_REQUEST['debitid']) || !$debit->Get($_REQUEST['debitid'])) {
	redirectTo('supplier_debits_pending.php');
}

if($debit->Supplier->ID != $session->Supplier->ID) {
	redirectTo('supplier_debits_pending.php');
}

$debit->Supplier->Get();
$debit->Supplier->Contact->Get();
$debit->GetLines();

$page = new Page(sprintf('[#%d] Debit Details', $debit->ID), 'Manage this debit here.');
$page->Display('header');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td align="left" valign="top"></td>
		<td align="right" valign="top">

			<table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
				<tr>
					<th>Debit:</th>
					<td>#<?php echo $debit->ID; ?></td>
				</tr>
				<tr>
					<th>Supplier:</th>
					<td><?php echo sprintf('%s %s%s', $debit->Supplier->Contact->Person->Name, $debit->Supplier->Contact->Person->LastName, ($debit->Supplier->Contact->Parent->ID > 0) ? sprintf(' (%s)', $debit->Supplier->Contact->Parent->Organisation->Name) : ''); ?></td>
				</tr>
				<tr>
					<th>Paid:</th>
					<td><?php echo ($debit->IsPaid == 'Y') ? 'Yes' : 'No'; ?></td>
				</tr>
				<tr>
					<th>&nbsp;</th>
					<td>&nbsp;</td>
				</tr>
				<tr>
					<th>Created On:</th>
					<td><?php echo cDatetime($debit->CreatedOn, 'shortdate'); ?></td>
				</tr>
			</table>
			<br />

		</td>
	</tr>
	<tr>
		<td colspan="2">
			<br />

			<div style="background-color: #eee; padding: 10px 0 10px 0;">
				<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing products for this debit.</span></p>

				<table cellspacing="0" class="orderDetails">
					<tr>
						<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Name</th>
						<th nowrap="nowrap" style="padding-right: 5px;">Description</th>
						<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Cost</th>
						<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">Total</th>
					</tr>

					<?php
					if(count($debit->Line) > 0) {
						for($k=0; $k<count($debit->Line); $k++) {
							$debit->Line[$k]->Product->Get();
							?>

							<tr>
								<td nowrap="nowrap"><?php echo $debit->Line[$k]->Quantity; ?></td>
								<td nowrap="nowrap"><?php echo $debit->Line[$k]->Product->ID; ?></td>
								<td nowrap="nowrap"><?php echo stripslashes($debit->Line[$k]->Product->Name); ?></td>
								<td nowrap="nowrap"><?php echo stripslashes($debit->Line[$k]->Description); ?></td>
								<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($debit->Line[$k]->Cost, 2), 2, '.', ','); ?></td>
								<td nowrap="nowrap" align="right">&pound;<?php echo number_format(round($debit->Line[$k]->Cost * $debit->Line[$k]->Quantity, 2), 2, '.', ','); ?></td>
							</tr>

							<?php
						}
						?>

						<tr>
							<td nowrap="nowrap" colspan="5"></td>
							<td nowrap="nowrap" align="right"><strong>&pound;<?php echo number_format(round($debit->Total, 2), 2, '.', ','); ?></strong></td>
						</tr>

						<?php
					} else {
						?>

						<tr>
							<td colspan="6" align="center">There are no items available for viewing.</td>
						</tr>

						<?php
					}
					?>
					
				</table>
				<br />

			</div>

		</td>
	</tr>
</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');