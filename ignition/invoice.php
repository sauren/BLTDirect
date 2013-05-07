<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Payment.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(3);

$invoice = new Invoice($_REQUEST['invoiceid']);
$payment = new Payment;

if($action == 'markaspaid'){
	$invoice->IsPaid = 'Y';
	$invoice->Update();
	redirect("Location: invoice.php?invoiceid=" . $invoice->ID);
} elseif($action == 'email'){
	$invoice->EmailCustomer();
	redirect("Location: invoice.php?invoiceid=". $invoice->ID);
}

$sql = sprintf("select * from payment where (Transaction_Type='PAYMENT' OR Transaction_Type='AUTHORISE') and Status='OK' and Invoice_ID=%d", mysql_real_escape_string($invoice->ID));
$data = new DataQuery($sql);
if($data->TotalRows > 0) $payment->Get($data->Row['Payment_ID']);
$data->Disconnect();

$page = new Page('Invoice #'.$invoice->ID, '');
$page->Display('header');
?>

<table border="0" cellspacing="0" width="100%">
  <tr>
    <td valign="top">
		<?php
		$win = new StandardWindow("Invoice Options");
		echo $win->Open();
		echo $win->AddHeader('Please make a selection.');
		echo $win->OpenContent();
		?>
		<ul>
			<li><?php echo sprintf('<a href="order_details.php?orderid=%s">&laquo; Back to Order</a>', $invoice->Order->ID); ?><br /><br /></li>
			<li><a href="invoice_view.php?invoiceid=<?php echo $invoice->ID; ?>" target="_blank">Printable Version</a><br /><br /></li>
			<?php
			if(!empty($payment->ID)){
			?>
				<li><a href="order_refund.php?orderid=<?php echo $invoice->Order->ID; ?>&paymentid=<?php echo $payment->ID; ?>">Refund Card</a><br /><br /></li>
			<?php
			} else {
			?>
				<li><a href="order_refund.php?orderid=<?php echo $invoice->Order->ID; ?>&paymentid=onaccount">Issue Credit Note</a><br /><br /></li>
			<?php
			}
			if($invoice->IsPaid == 'N'){
				echo sprintf('<li><a href="javascript:confirmRequest(\'invoice.php?invoiceid=%s&action=markaspaid\',\'Are you sure you want to mark this invoice as invoiced?\');">Mark as Invoiced</a></li><br /><br />', $invoice->ID);
			}
			?>
			<li><a href="javascript:confirmRequest('invoice.php?invoiceid=<?php echo $invoice->ID; ?>&action=email','Are you sure you want to email this invoice to the customer?');">Email Invoice</a><br /><br /></li>
		</ul>

		<?php
		echo $win->CloseContent();
		echo $win->Close();

		if(strlen(trim($invoice->IntegrationID)) > 0) {
			$win = new StandardWindow("Invoice Details");

			echo '<br />';
			echo $win->Open();
			echo $win->AddHeader('Additional Information');
			echo $win->OpenContent();

			echo sprintf('<ul><li><strong>Integration ID</strong><br /> %s</li></ul>', $invoice->IntegrationID);

			echo $win->CloseContent();
			echo $win->Close();
		}
		?>

	</td>
	<td style="width:20px;" valign="top"></td>
	<td>
<div style="width:100%; height:100%; overflow:auto;">
<?php
echo $invoice->GetDocument();
?>
</div>
</td></tr></table>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>
