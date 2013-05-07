<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/QuoteNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/QuoteLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$session->Secure(3);

$quote = new Quote($_REQUEST['quoteid']);
$quote->SetShippingAddress();
$quote->SetInvoiceAddress();
$quote->GetLines();
$quote->Customer->Get();
$quote->Customer->Contact->Get();

UserRecent::Record(sprintf('[#%d] Quote Details (%s)', $quote->ID, $quote->Customer->Contact->Person->GetFullName()), sprintf('quote_details.php?quoteid=%d', $quote->ID));

$referrer = new Referrer($quote->Referrer);

for($i = 0; $i<count($quote->Line); $i++){
	$quote->Line[$i]->Product = new Product($quote->Line[$i]->Product->ID);
}

if(isset($_REQUEST['changePostage']) && is_numeric($_REQUEST['changePostage']) && $_REQUEST['changePostage'] > 0){
	$quote->Postage->ID = $_REQUEST['changePostage'];
	$quote->Recalculate();
	$quote->Update();

	redirect("Location: quote_details.php?quoteid=" . $_REQUEST['quoteid']);
}

if(isset($_REQUEST['shipping'])){
	if($_REQUEST['shipping'] == 'custom'){
		$quote->IsCustomShipping = 'Y';
		$quote->Update();
	} elseif($_REQUEST['shipping'] == 'standard'){
		$quote->IsCustomShipping = 'N';
		$quote->Recalculate();
	}
}

$warehouseEditable = false;
$isEditable = false;

if((strtolower($quote->Status) != 'ordered') && (strtolower($quote->Status) != 'cancelled')){
	$isEditable = true;
	$warehouseEditable = true;
}

if($action == 'delete'){
	$quote->Delete();
	redirect("Location: quotes.php");
} elseif($action == "cancel"){
	$quote->Cancel();
	redirect("Location: quotes.php");
} elseif($action == "convert"){
	$id = $quote->Convert();
	
	redirect("Location: order_takePayment.php?orderid=" . $id);
	
} elseif($action == "converttoproforma"){
	$id = $quote->ConvertToProforma();
	
	redirect("Location: proforma_details.php?proformaid=" . $id);
	
} elseif($action == "remove" && isset($_REQUEST['line'])){
	$line = new QuoteLine();
	$line->Delete($_REQUEST['line']);

	$newQuote = new Quote($quote->ID);
	$newQuote->Recalculate();
	$newQuote->Update();

	redirect("Location: quote_details.php?quoteid=". $quote->ID);
} elseif($action == "replace"){
	if(isset($_REQUEST['address'])) {
		if($_REQUEST['address'] == 'billing') {
			$quote->Billing = $quote->Customer->Contact->Person;

			if($quote->Customer->Contact->HasParent){
				$quote->BillingOrg = $quote->Customer->Contact->Parent->Organisation->Name;
			}
		} elseif($_REQUEST['address'] == 'shipping') {
			$quote->Shipping = $quote->Customer->Contact->Person;

			if($quote->Customer->Contact->HasParent){
				$quote->ShippingOrg = $quote->Customer->Contact->Parent->Organisation->Name;
			}
		} elseif($_REQUEST['address'] == 'invoice') {
			$quote->Invoice->Address->Zip = '';
			$quote->SetInvoiceAddress();
		}

		$quote->Recalculate();

		redirect(sprintf("Location: %s?quoteid=%d", $_SERVER['PHP_SELF'], $quote->ID));
	}

} elseif($action == "resend"){
	$quote->SendEmail();
	redirect("Location: quote_details.php?quoteid=". $quote->ID);
} elseif($action == "followup"){
	$quote->FollowUp();
	redirect("Location: quote_details.php?quoteid=". $quote->ID);
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('quoteid', 'Quote ID', 'hidden', $quote->ID, 'numeric_unsigned', 1, 11);
$form->AddField('email', 'Email Address', 'text', $quote->Customer->Contact->Person->Email, 'email', null, null, true);

if($isEditable) {
	$form->AddField('customreference', 'Custom Reference', 'text', $quote->CustomID, 'anything', 1, 32, false);
	$form->AddField('reviewdate', 'Review Date', 'text', ($quote->ReviewOn > '0000-00-00 00:00:00') ? date('d/m/Y', strtotime($quote->ReviewOn)) : '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('taxexemptcode', 'Tax Exempt Code', 'text', $quote->TaxExemptCode, 'anything', 0, 20, false);
}

for($i=0; $i < count($quote->Line); $i++){
	$quote->Line[$i]->Product->GetDownloads();
	
	$discountVal = '';

	if(!empty($quote->Line[$i]->DiscountInformation)) {
		$discountCustom = explode(':', $quote->Line[$i]->DiscountInformation);

		if(trim($discountCustom[0]) == 'azxcustom') {
			$discountVal = $discountCustom[1];
		}
	}

	if($quote->Line[$i]->Product->DiscountLimit != '' && $discountVal > $quote->Line[$i]->Product->DiscountLimit){
		$discountVal = $quote->Line[$i]->Product->DiscountLimit;
	}
		
	$form->AddField('qty_' . $quote->Line[$i]->ID, 'Quantity of ' . $quote->Line[$i]->Product->Name, 'text',  $quote->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
	$form->AddField('handling_'.$quote->Line[$i]->ID, 'Handling Charge for ' . $quote->Line[$i]->Product->Name, 'text', $quote->Line[$i]->HandlingCharge, 'float', 0, 6, false, 'size="1"');
	$form->AddField('discount_'.$quote->Line[$i]->ID, 'Discount for '. $quote->Line[$i]->Product->Name, 'text', $discountVal,'float',0,6,false, 'size="1"');
	
	if(!empty($quote->Line[$i]->Product->Download)) {
		$form->AddField(sprintf('downloads_%d', $quote->Line[$i]->ID), 'Spec Sheets', 'checkbox', $quote->Line[$i]->IncludeDownloads, 'boolean', 1, 11, false);	
	}
}

if(isset($_REQUEST['confirm'])) {
	if($action == 'update') {
		$form->Validate();

		if($form->Valid){
			for($i=0; $i < count($quote->Line); $i++) {
				if($form->GetValue('handling_'.$quote->Line[$i]->ID)) {
					if(($form->GetValue('handling_'.$quote->Line[$i]->ID) < 0) || ($form->GetValue('handling_'.$quote->Line[$i]->ID) > 100)) {
						$form->AddError(sprintf('Handling Charge for %s must be between 0 and 100%%', ($quote->Line[$i]->Product->ID > 0) ? $quote->Line[$i]->Product->Name : $quote->Line[$i]->AssociativeProductTitle), 'handling_'.$quote->Line[$i]->ID);
					}
				}
			}
		}

		if($form->Valid){
			if($isEditable) {
				$quote->CustomID = $form->GetValue('customreference');	
			}
			
			for($i=0; $i < count($quote->Line); $i++){
				if(is_numeric($form->GetValue('qty_' . $quote->Line[$i]->ID)) && $quote->Line[$i]->Quantity != $form->GetValue('qty_' .  $quote->Line[$i]->ID) && $form->GetValue('qty_' . $quote->Line[$i]->ID) > 0) {
					$quote->Line[$i]->Quantity = $form->GetValue('qty_' . $quote->Line[$i]->ID);
				}
				
				$discountVal = $form->GetValue('discount_'.$quote->Line[$i]->ID);

				if(strlen($discountVal) > 0) {
					$quote->Line[$i]->DiscountInformation = 'azxcustom:'.$discountVal;
				} else {
					$quote->Line[$i]->DiscountInformation = '';
				}
				
				if(!empty($quote->Line[$i]->Product->Download)) {
					$quote->Line[$i]->IncludeDownloads = $form->GetValue(sprintf('downloads_%d', $quote->Line[$i]->ID));
				}

				$quote->Line[$i]->HandlingCharge = $form->GetValue('handling_'.$quote->Line[$i]->ID);
				$quote->Line[$i]->DespatchedFrom->ID = $form->GetValue('despatchfrom_'.$quote->Line[$i]->ID);
				$quote->Line[$i]->Update();
			}

			if(isset($_REQUEST['setShipping'])){
				$quote->TotalShipping = $_REQUEST['setShipping'];
			}
			
			$quote->Customer->Contact->Person->Email = $form->GetValue('email');
			$quote->Customer->Contact->Person->Update();
			$quote->TaxExemptCode = isset($_REQUEST['taxexemptcode']) ? $_REQUEST['taxexemptcode'] : '';

			if(strlen($form->GetValue('reviewdate')) > 0) {
				$reviewDates = explode('/', $form->GetValue('reviewdate'));
				
				$quote->ReviewOn = date('Y-m-d H:i:s', mktime(0, 0, 0, $reviewDates[1], $reviewDates[0], $reviewDates[2]));
			} else {
				$quote->ReviewOn = '0000-00-00 00:00:00';
			}

			$quote->Recalculate();

			redirect("Location: quote_details.php?quoteid=". $quote->ID);
		}
	}
}

$page = new Page(sprintf('%s%s Quote Details for %s', $quote->Prefix, $quote->ID, $quote->Customer->Contact->Person->GetFullName()), '');
$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('quoteid');

$quoteNoteAlert = 'no';
if($quote->HasAlerts()){
	$quoteNoteAlert = 'yes';
}
?>
<script language="javascript" type="text/javascript">
var isPrompt  = '<?php echo $quoteNoteAlert; ?>';
if(isPrompt == 'yes'){
	popUrl('./quote_alerts.php?qid=<?php echo $quote->ID; ?>', 500, 400);
}

function changeDelivery(num){
	if(num == ''){
		alert('Please Select a Delivery Option');
	} else {
		window.location.href = 'quote_details.php?quoteid=<?php echo $quote->ID; ?>&changePostage=' + num;
	}
}
</script>

<?php
if(isset($_REQUEST['postage']) && $_REQUEST['postage'] == 'error'){
	$quote->CalculateShipping();

	if($quote->Error){
?>
<table class="error" cellspacing="0">
  <tr>
    <td valign="top"><img src="/ignition_1/ignition/images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Shipping Information Not Found:</strong><br>
	Sorry could not find any shipping settings for this location. Please change shipping location. <a href="quote_changeAddress.php?quoteid=<?php echo $quote->ID; ?>&type=shipping">Click Here</a>
    </td>
  </tr>
</table>
<br />
<?php
	} else {
?>
<table class="error" cellspacing="0">
  <tr>
    <td valign="top"><img src="/ignition_1/ignition/images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Shipping Information Needed:</strong><br>
	Please select an Appropriate Shipping Option: <?php echo $quote->PostageOptions; ?>
    </td>
  </tr>
</table>
<br />
<?php
	}
}
?>

<table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
	<td>
      <table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
      <tr>
			<td valign="top" class="billing"><p><strong>Organisation/Individual:</strong><br />
				<?php echo $quote->GetBillingAddress();  ?><br /><br />
				<?php echo $quote->Customer->Contact->Person->GetPhone('<br />');  ?></p>
			</td>
			<td valign="top" class="shipping"><p><strong>Shipping Address:</strong><br />
				<?php echo $quote->GetShippingAddress();  ?><br /><br />
				<?php echo $quote->Customer->Contact->Person->GetPhone('<br />');  ?></p></td>
			<td valign="top" class="billing"><p><strong>Invoice Address:</strong><br />
				<?php echo $quote->GetInvoiceAddress();  ?><br /><br />
				<?php echo $quote->Customer->Contact->Person->GetPhone('<br />');  ?></p>
			</td>
      </tr>
	  <?php if($isEditable) { ?>
	  <tr>
		<td class="billing"><input type="button" name="change" value="change" class="btn" onclick="window.location.href='quote_changeAddress.php?quoteid=<?php echo $quote->ID; ?>&type=billing'" /> <input type="button" name="default" value="use default" class="btn" onclick="confirmRequest('quote_details.php?quoteid=<?php echo $quote->ID; ?>&action=replace&address=billing', 'Are you sure you wish to replace this billing address with the customers default address?');" /></td>
		<td class="shipping"><input type="button" name="change" value="change" class="btn" onclick="window.location.href='quote_changeAddress.php?quoteid=<?php echo $quote->ID; ?>&type=shipping'" /> <input type="button" name="default" value="use default" class="btn" onclick="confirmRequest('quote_details.php?quoteid=<?php echo $quote->ID; ?>&action=replace&address=shipping', 'Are you sure you wish to replace this shipping address with the customers default address?');" /></td>
		<td class="billing"><input type="button" name="change" value="change" class="btn" onclick="window.location.href='quote_changeAddress.php?quoteid=<?php echo $quote->ID; ?>&type=invoice'" /> <input type="button" name="default" value="use default" class="btn" onclick="confirmRequest('quote_details.php?quoteid=<?php echo $quote->ID; ?>&action=replace&address=invoice', 'Are you sure you wish to replace this invoice address with the customers default address?');" /></td>
	  </tr>
	  <?php } ?>
    </table>
      <p><br />
      <br>
	  <?php
	  if($isEditable){
		 ?>
        <input name="Cancel Quote" type="button" id="Cancel Quote" value="Cancel" class="btn" onclick="popUrl('./quote_cancel.php?quoteid=<?php echo $quote->ID; ?>$action=cancel', 650, 450);" />
        <input name="Delete Quote" type="button" id="Delete Quote" value="Delete" class="btn" onclick="confirmRequest('./quote_details.php?quoteid=<?php echo $quote->ID; ?>&action=delete', 'Are you sure you would like to delete this quote permanently?');" />
        <input name="Convert" type="button" value="Convert To Order" class="btn" onclick="window.location.href='quote_details.php?quoteid=<?php echo $quote->ID; ?>&action=convert';" />
        <input name="Convert" type="button" value="Convert To Proforma" class="btn" onclick="window.location.href='quote_details.php?quoteid=<?php echo $quote->ID; ?>&action=converttoproforma';" />
        <?php } ?>

        <?php
        if(($quote->FollowedUp == 'N') && ($quote->Status != 'Cancelled')) {
            ?>
            <input name="Follow Up" type="button" id="Follow Up" value="Follow Up" class="btn" onclick="window.location.href='./quote_details.php?quoteid=<?php echo $quote->ID; ?>&action=followup';" />
            <?php
        }
        ?>
        </p>
	</td><td align="right" valign="middle"><table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
      <tr>
        <th>Quote Reference:</th>
        <td><?php echo $quote->Prefix . $quote->ID; ?></td>
      </tr>
	  <tr>
        <th>Custom Reference:</th>
        <td><?php echo ($isEditable) ? $form->GetHTML('customreference') : $quote->CustomID; ?></td>
      </tr>
      <tr>
        <th>Customer:</th>
        <td><a href="contact_profile.php?cid=<?php echo $quote->Customer->Contact->ID; ?>"><?php echo $quote->Customer->Contact->Person->GetFullName(); ?></a></td>
      </tr>
      <tr>
        <th>Quote Status:</th>
        <td><?php echo $quote->Status; ?></td>
      </tr>
      <tr>
        <th>&nbsp;</th>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <th>Quote Date:</th>
        <td><?php echo cDatetime($quote->QuotedOn, 'shortdate'); ?></td>
      </tr>
      <tr>
		<th>Email Address:</th>
		<td><?php echo ($isEditable) ? $form->GetHTML('email') : $quote->Customer->Contact->Person->Email; ?></td>
	  </tr>
      <tr>
        <th>Emailed On:</th>
        <td><?php echo !empty($quote->EmailedOn) ? cDatetime($quote->EmailedOn, 'shortdate') : ''; ?></td>
      </tr>
      <tr>
        <th>Emailed To:</th>
        <td><?php echo $quote->EmailedTo; ?>&nbsp; <a href="quote_details.php?quoteid=<?php echo $quote->ID; ?>&amp;action=resend" title="Click to Resend Quote Confirmation to Customer">(resend)</a></td>
      </tr>
	  <tr>
	    <th>Review On:</th>
	    <td><?php echo ($isEditable) ? $form->GetHTML('reviewdate') : cDatetime($quote->ReviewOn, 'shortdate'); ?></td>
	  </tr>
    </table>

    </td>
  </tr>
  <tr>
    <td colspan="2"><br>

<div style="background-color: #f6f6f6; padding: 10px 0 10px 0;">
<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing product quantities and preferred supplier cost prices for this Quote.</span></p>

      <table cellspacing="0" class="orderDetails">
      <tr>
        <th>Qty</th>
        <th>Product</th>
        <th style="text-align: center;">Spec Sheets</th>
        <th>Discount</th>
        <th>Handling</th>
		<th>Status</th>
        <th>Quickfind</th>
        <th style="text-align: right;">Price</th>
        <th style="text-align: right;">Discount</th>
        <th style="text-align: right;">Your Price</th>
        <th style="text-align: right;">Line Total</th>
      </tr>
      <?php
      for($i=0; $i < count($quote->Line); $i++){
?>
      <tr>
        <td>
		<?php if ($isEditable){ ?>
        <a href="quote_details.php?quoteid=<?php echo $quote->ID; ?>&action=remove&line=<?php echo $quote->Line[$i]->ID; ?>">
            <img src="images/icon_trash_1.gif" alt="Remove" border="0" /></a>
		<?php } ?>
        <?php
        echo ($isEditable) ? $form->GetHTML('qty_'. $quote->Line[$i]->ID) : $quote->Line[$i]->Quantity;
        ?>x</td>
        <td>
			<a href="product_profile.php?pid=<?php echo $quote->Line[$i]->Product->ID; ?>"><?php echo $quote->Line[$i]->Product->Name; ?></a>
			<?php
			if($quote->Line[$i]->Discount > 0) {
				$discountVal = explode(':', $quote->Line[$i]->DiscountInformation);
				if(trim($discountVal[0]) == 'azxcustom') {
					$showDiscount = 'Custom Discount';
				} else {
					$showDiscount = $quote->Line[$i]->DiscountInformation;
				}
				if(!empty($showDiscount)) {
					echo sprintf("<br />(%s - &pound;%s)",$showDiscount, number_format($quote->Line[$i]->Discount, 2, '.',','));
				} else {
					echo sprintf("<br />(&pound;%s)",number_format($quote->Line[$i]->Discount, 2, '.',','));
				}
			} else {
				echo sprintf("<br />(-&pound;%s)",number_format($quote->Line[$i]->Discount, 2, '.',','));
			}
			?>
		</td>
		<td align="center"><?php echo ($isEditable) ? $form->GetHTML(sprintf('downloads_%d', $quote->Line[$i]->ID)) : $quote->Line[$i]->IncludeDownloads; ?></td>
		<td nowrap="nowrap">
			<?php
			if($isEditable){
				echo $form->GetHTML('discount_'.$quote->Line[$i]->ID);
				echo '%';
			} else {
				$discountVal = explode(':', $quote->Line[$i]->DiscountInformation);
				if(trim($discountVal[0]) == 'azxcustom') {
					echo $discountVal[1];
					echo '%';
				}
			}
			?>&nbsp;
		</td>
		<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML('handling_'.$quote->Line[$i]->ID).'%' : ''; ?>&nbsp;</td>
		<td align="center"><?php echo ucfirst($quote->Status); ?>&nbsp;</td>
        <td><a href="product_profile.php?pid=<?php echo $quote->Line[$i]->Product->ID; ?>"><?php echo $quote->Line[$i]->Product->ID; ?></a></td>
        <td align="right">&pound;<?php echo number_format($quote->Line[$i]->Price, 2, '.', ','); ?></td>
        <td align="right">&pound;<?php echo number_format($quote->Line[$i]->Discount / $quote->Line[$i]->Quantity, 2, '.', ','); ?></td>
        <td align="right">&pound;<?php echo number_format($quote->Line[$i]->Price - ($quote->Line[$i]->Discount / $quote->Line[$i]->Quantity), 2, '.', ','); ?></td>
        <td align="right">&pound;<?php echo number_format($quote->Line[$i]->Total - $quote->Line[$i]->Discount, 2, '.', ','); ?></td>
      </tr>
      <?php
      }
?>
      <tr>
        <td colspan="9" align="left">Cart Weight: ~<?php echo $quote->Weight; ?>Kg</td>
        <td align="right">Sub Total:</td>
		<td align="right">&pound;<?php echo number_format($quote->SubTotal - $quote->TotalDiscount, 2, '.', ','); ?></td>
      </tr>
    </table>

</div><br />

    </td>
  </tr>
  <tr>
    <td align="left" valign="top">
    <?php if($warehouseEditable && $quote->Status != 'ordered' && $quoted->Status != 'cancelled'){?>
	<input type="submit" name="action" value="update" class="btn" />
	<?php }
	if ($isEditable && $quote->Status != 'ordered' && $quote->Status != 'cancelled'){ ?>
	<input type="button" name="add product" value="add" class="btn" onclick="window.location.href='quote_add.php?quoteid=<?php echo $quote->ID; ?>';" />
	<?php } ?>
	<br />
	<br />
	<strong>Additional Information: </strong><br />
      <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
		<tr>
		  <th valign="top"> <a href="./quote_notes.php?qid=<?php echo $quote->ID; ?>">Quote Notes:</a> </th>
		  <td valign="top">
		  <a href="./quote_notes.php?qid=<?php echo $quote->ID; ?>">
		  <?php
		  $notes = new DataQuery(sprintf("select Quote_Note_ID from quote_note where Quote_ID=%d", mysql_real_escape_string($quote->ID)));
		  echo $notes->TotalRows;
		  $notes->Disconnect();
		  unset($notes);
		  ?></a>
		  </td>
		</tr>
		<tr>
          <th valign="top">Referrer:</th>
          <td valign="top"><?php echo $referrer->Url; ?></td>
        </tr>
        <tr>
          <th valign="top">Search String:</th>
          <td valign="top"><?php echo $referrer->SearchString; ?></td>
        </tr>
      </table>
      <p><span class="smallGreyText">
      </span></p></td>
    <td align="right">

<div style="background-color: #f6f6f6; padding: 10px 0 10px 0;">
<p style="text-align: left;"><span class="pageSubTitle">Summary</span><br /><span class="pageDescription">Quote cost and sale pricing information.</span></p>

   <table cellspacing="0" class="orderDetails">
      <tr>
        <th colspan="2">Tax &amp; Shipping</th>
      </tr>
      <tr>
        <td>Delivery Option:</td>
        <td align="right">
          <?php
          if (!$isEditable){
            $quote->Postage->Get();
            echo $quote->Postage->Name;
          } else {
            $quote->Recalculate();
            echo $quote->PostageOptions;
          }
			?>
        </td>
      </tr>
      <tr>
        <td>
			Shipping
			<?php if($isEditable){
				if($quote->IsCustomShipping == 'N'){
			?>
			<a href="<?php echo $_SERVER['PHP_SELF']; ?>?quoteid=<?php echo $quote->ID; ?>&shipping=custom">(customise)</a>
			<?php } else { ?>
			<a href="<?php echo $_SERVER['PHP_SELF']; ?>?quoteid=<?php echo $quote->ID; ?>&shipping=standard">(standardise)</a>
			<?php }} ?>
			:
		</td>
        <td align="right">

		&pound;
		<?php if($isEditable){
			if($quote->IsCustomShipping == 'N'){
				echo number_format($quote->TotalShipping, 2, ".", ",");
			} else {
		?>
			<input type="text" name="setShipping" value="<?php echo number_format($quote->TotalShipping, 2, ".", ",");  ?>" size="10" />
		<?php }} else {
			echo number_format($quote->TotalShipping, 2, ".", ",");
		}
		 ?>
		</td>
      </tr>
	  <?php
	  if(!empty($quote->TotalDiscount)) {
		?>
			<tr>
				<td>
					<span style="color: #666;">Discount Deducted:
						<?php
						if(!empty($quote->Coupon->ID)){
							$quote->Coupon->Get();
							echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $quote->Coupon->Name, $quote->Coupon->Reference);
						}
						?>
					</span>
				</td>
				<td align="right"><span style="color: #666;">&pound;<?php echo number_format($quote->TotalDiscount, 2, ".", ","); ?></span></td>
			</tr>
		<?php
	  }
	  ?>
      <tr>
        <td>Net:</td>
		<td align="right">&pound;
			<?php echo number_format($quote->TotalNet, 2, ".", ",");
			?>
		</td>
      </tr>
	  <?php
		if($isEditable){
			?>
			<tr><td>Tax Exemption Code:</td><td align="right"><?php echo $form->GetHTML('taxexemptcode'); ?></td></tr>
			<?php
		} elseif(!empty($quote->TaxExemptCode)) {
			?>
			<tr><td>Tax Exemption Code:</td><td align="right"><?php echo $quote->TaxExemptCode; ?></td></tr>
			<?php
		}
	  ?>
	  <tr>
		<td>Tax:</td>
		<td align="right">&pound;<?php echo number_format($quote->TotalTax, 2, ".", ","); ?></td>
      </tr>
      <tr>
        <td><strong>Total:</strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($quote->Total, 2, ".", ","); ?></strong></td>
      </tr>
    </table>

</div><br />

    </td>
  </tr>
</table>
<?php
echo $form->Close();
$page->Display('footer');
require_once('lib/common/app_footer.php');