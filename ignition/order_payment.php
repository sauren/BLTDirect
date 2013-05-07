<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

$session->Secure(3);

$order = new Order($_REQUEST['orderid']);
$order->PaymentMethod->Get();
$order->GetLines();
$order->Customer->Get();
$order->Customer->Contact->Get();
$order->Get();

if(!is_null($order->QuoteID)){
	$query = new DataQuery("UPDATE quote SET Status='Ordered' WHERE Quote_ID = {$order->QuoteID}");
	$query->Disconnect();
}

$page = new Page(sprintf('%s%s Order Payment Details for %s', $order->Prefix, $order->ID, $order->Customer->Contact->Person->GetFullName()),'');
$page->Display('header');
?>
	<p><a href="order_details.php?orderid=<?php echo $order->ID; ?>">Back to Order Details</a> | <a href="javascript:window.history.go(-1);">Go Back to Previous Page</a></p>
	<table width="100%"  border="0" cellspacing="0" cellpadding="0" class="invoicePaymentDetails">
	  <tr>
		<th>Order Reference </th>
		<td><?php echo $order->Prefix . $order->ID; ?></td>
	  </tr>
	  <tr>
	    <th>Order Date </th>
	    <td><?php echo cDatetime($order->OrderedOn, 'longdate'); ?></td>
      </tr>
	  <tr>
	    <th>Customer</th>
	    <td><a href="contact_profile.php?cid=<?php echo $order->Customer->Contact->ID; ?>"><?php echo $order->Customer->Contact->Person->GetFullName(); ?></a></td>
      </tr>
	  <tr>
	    <th>&nbsp;</th>
	    <td>&nbsp;</td>
      </tr>
	  <tr>
	    <th>Payment Method </th>
	    <td><?php echo $order->PaymentMethod->Method; ?>&nbsp;</td>
      </tr>
	  <tr>
	    <th>Credit Card </th>
	    <td><?php echo $order->Card->GetNumber(); ?>&nbsp;</td>
      </tr>
      <?php
      if($order->PaymentMethod->Reference != 'google') {
    	  ?>
		  <tr>
		    <th>Name on Card </th>
		    <td><?php echo $order->Card->Title; ?>
			<?php echo $order->Card->Initial; ?>
			<?php echo $order->Card->Surname; ?>&nbsp;</td>
	      </tr>
		  <tr>
		    <th>Expires (MMYY)</th>
		    <td><?php echo $order->Card->Expires; ?>&nbsp;</td>
	      </tr>

	      <?php
      }
      ?>
      <tr>
		    <th>&nbsp;</th>
		    <td>&nbsp;</td>
	      </tr>
	  <tr>
	    <th>Amount Payable/Paid on this card:</th>
	    <td>&pound;<?php echo number_format($order->Total, 2, '.', ','); ?></td>
      </tr>
	</table>

	<?php
	if($order->PaymentMethod->Reference != 'google') {
		?>
		<br />
		<input type="button" name="change" value="change payment details" class="btn" onclick="window.location.href='order_takePayment.php?orderid=<?php echo $order->ID; ?>';" />
		<?php
	}
	?>

<?php
$page->Display('footer');

require_once('lib/common/app_footer.php');