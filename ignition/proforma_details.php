<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProForma.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProFormaLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$session->Secure(3);

$proForma = new ProForma($_REQUEST['proformaid']);
$proForma->GetLines();
$proForma->Customer->Get();
$proForma->Customer->Contact->Get();
$proForma->DiscountCollection->Get();

if($action == 'requestcatalogue'){
    $proForma->Customer->Contact->IsCatalogueRequested = 'Y';
    $proForma->Customer->Contact->Update();
    redirectTo('?proformaid=' . $proForma->ID);
}

if($action == 'cancelrequestcatalogue'){
    $proForma->Customer->Contact->IsCatalogueRequested = 'N';
    $proForma->Customer->Contact->Update();
    redirectTo('?proformaid=' . $proForma->ID);
}

for($i = 0; $i<count($proForma->Line); $i++){
    $proForma->Line[$i]->Product = new Product($proForma->Line[$i]->Product->ID);
}

if(isset($_REQUEST['changePostage'])
&& is_numeric($_REQUEST['changePostage'])
&& $_REQUEST['changePostage'] > 0){
	$proForma->Postage->ID = $_REQUEST['changePostage'];
	$proForma->Recalculate();
	$proForma->Update();
	redirect("Location: proforma_details.php?proformaid=" . $_REQUEST['proformaid']);
}

if(isset($_REQUEST['shipping'])){
	if($_REQUEST['shipping'] == 'custom'){
		$proForma->IsCustomShipping = 'Y';
		$proForma->Update();
	} elseif($_REQUEST['shipping'] == 'standard'){
		$proForma->IsCustomShipping = 'N';
		$proForma->Recalculate();
	}
}

//  Check whether the pro forma is editable
$warehouseEditable = false;
$isEditable = false;
if((strtolower($proForma->Status) != 'ordered')
&& (strtolower($proForma->Status) != 'cancelled')){
    $isEditable = true;
    $warehouseEditable = true;
}

// Event Handlers
if($action == "delete"){
	$proForma->Delete();
	redirect("Location: proformas.php");
} elseif($action == "cancel"){
  	$proForma->Cancel();
	redirect("Location: proformas.php");
} elseif($action == "convert"){
  	$conversionID = $proForma->Convert();
	if($conversionID == false){
	  	echo "ERROR: Failed to query database. Aborting...";
	}
	redirect("Location: order_takePayment.php?orderid=$conversionID");
} elseif($action == "remove" && isset($_REQUEST['line'])){
	$line = new ProFormaLine;
	$line->Delete($_REQUEST['line']);
	$proForma->Recalculate();
	redirect("Location: proforma_details.php?proformaid=". $proForma->ID);
} elseif($action == "resend"){
	$proForma->SendEmail();
	redirect("Location: proforma_details.php?proformaid=". $proForma->ID);
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('proformaid', 'Pro Forma ID', 'hidden', $proForma->ID, 'numeric_unsigned', 1, 11);
$form->AddField('email', 'Email Address', 'text', $proForma->Customer->Contact->Person->Email, 'email', null, null, true);

if($isEditable) {
	$form->AddField('taxexemptcode', 'Tax Exempt Code', 'text', $proForma->TaxExemptCode, 'anything', 0, 20, false);
}

for($i=0; $i < count($proForma->Line); $i++){
	$proForma->Line[$i]->Product->GetDownloads();
	
	$discountVal = '';

	if(!empty($proForma->Line[$i]->DiscountInformation)) {
		$discountCustom = explode(':', $proForma->Line[$i]->DiscountInformation);

		if(trim($discountCustom[0]) == 'azxcustom') {
			$discountVal = $discountCustom[1];
		}
	}

	if($proForma->Line[$i]->Product->DiscountLimit != '' && $discountVal > $proForma->Line[$i]->Product->DiscountLimit){
		$discountVal = $proForma->Line[$i]->Product->DiscountLimit;
	}

	$form->AddField('discount_'.$proForma->Line[$i]->ID,'Discount for '. $proForma->Line[$i]->Product->Name,'text',$discountVal,'float',1,11,false, 'size="1"');
	$form->AddField('handling_'.$proForma->Line[$i]->ID,'Handling for '. $proForma->Line[$i]->Product->Name,'text',$proForma->Line[$i]->HandlingCharge,'float',1,11,false, 'size="1"');
    $form->AddField('qty_' . $proForma->Line[$i]->ID, 'Quantity of ' . $proForma->Line[$i]->Product->Name, 'text',  $proForma->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="3"');
    
    if(!empty($proForma->Line[$i]->Product->Download)) {
		$form->AddField(sprintf('downloads_%d', $proForma->Line[$i]->ID), 'Spec Sheets', 'checkbox', $proForma->Line[$i]->IncludeDownloads, 'boolean', 1, 11, false);
	}
}

if(($action == 'pack') && isset($_REQUEST['confirm'])) {
	//a bit of code similar to the update procedure, to set the values of the warehouses chosen to if update hasnt been pressed.

	if($form->Validate()){
		$quantitiesUpdated = false;
		for($i=0; $i < count($proForma->Line); $i++){
            if(is_numeric($form->GetValue('qty_' . $proForma->Line[$i]->ID))
            && ($proForma->Line[$i]->Quantity != $form->GetValue('qty_' . $proForma->Line[$i]->ID))
			&& $form->GetValue('qty_' . $proForma->Line[$i]->ID) > 0)
			{
				$proForma->Line[$i]->Quantity = $form->GetValue('qty_' . $proForma->Line[$i]->ID);
				$quantitiesUpdated = true;
			}
		}
		for($i=0; $i < count($proForma->Line); $i++){
			$proForma->Line[$i]->DespatchedFrom->ID = $form->GetValue('despatchfrom_'.$proForma->Line[$i]->ID);
			$proForma->Line[$i]->Update();
		}

		if(isset($_REQUEST['setShipping'])){
			$quantitiesUpdated = true;
			$proForma->TotalShipping = $_REQUEST['setShipping'];
			$proForma->Update();
		}
		if($quantitiesUpdated){
			$proForma->Recalculate();
		}
	}

	if($form->Valid){
		$proForma->Status = 'Packing';
		$proForma->Update();
		
		redirect("Location: proforma_details.php?proformaid=". $proForma->ID);
	}
}
elseif($action == 'update' && isset($_REQUEST['confirm'])){
	if($form->Validate()){
		if(($proForma->Status != 'Cancelled')) {
			for($i=0; $i < count($proForma->Line); $i++){
				$discountVal = $form->GetValue('discount_'.$proForma->Line[$i]->ID);
				if(strlen($discountVal) > 0) {
					if(($discountVal > 100) || ($discountVal < 0)) {
						$form->AddError('Discount for '.$proForma->Line[$i]->Product->Name.' must be in the range of 0-100%.');
					}
				}
			}
		}
	}

	if($form->Valid){
		for($i=0; $i < count($proForma->Line); $i++){
            if(is_numeric($form->GetValue('qty_' . $proForma->Line[$i]->ID))
            && $proForma->Line[$i]->Quantity
                != $form->GetValue('qty_' .  $proForma->Line[$i]->ID)
            && $form->GetValue('qty_' . $proForma->Line[$i]->ID) > 0)
			{
                $proForma->Line[$i]->Quantity = $form->GetValue('qty_' .
                                                             $proForma->Line[$i]->ID);
            }
            
            if(!empty($proForma->Line[$i]->Product->Download)) {
				$proForma->Line[$i]->IncludeDownloads = $form->GetValue(sprintf('downloads_%d', $proForma->Line[$i]->ID));
			}
            
            $proForma->Line[$i]->HandlingCharge = $form->GetValue('handling_' . $proForma->Line[$i]->ID);

			$discountVal = $form->GetValue('discount_'.$proForma->Line[$i]->ID);

			if(strlen($discountVal) > 0) {
				$proForma->Line[$i]->DiscountInformation = 'azxcustom:'.$discountVal;
			} else {
				$proForma->Line[$i]->DiscountInformation = '';
			}
			
			$proForma->Line[$i]->Update();
		}

		$proForma->Customer->Contact->Person->Email = $form->GetValue('email');
		$proForma->Customer->Contact->Person->Update();
		$proForma->TaxExemptCode = isset($_REQUEST['taxexemptcode']) ? $_REQUEST['taxexemptcode'] : '';

		if(isset($_REQUEST['setShipping'])){
			$proForma->TotalShipping = $_REQUEST['setShipping'];
		}

		$proForma->Recalculate();
		
		redirect("Location: proforma_details.php?proformaid=". $proForma->ID);
	}
}

$page = new Page(sprintf('%s%s Pro Forma Details for %s', $proForma->Prefix, $proForma->ID, $proForma->Customer->Contact->Person->GetFullName()),
'');
$page->Display('header');
if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('proformaid');
?>
<script language="javascript" type="text/javascript">
function changeDelivery(num){
	if(num == ''){
		alert('Please Select a Delivery Option');
	} else {
		window.location.href = 'proforma_details.php?proformaid=<?php echo $proForma->ID; ?>&changePostage=' + num;
	}
}
</script>

<?php
if(isset($_REQUEST['postage']) && $_REQUEST['postage'] == 'error'){
	$proForma->CalculateShipping();

	if($proForma->Error){
?>
<table class="error" cellspacing="0">
  <tr>
    <td valign="top"><img src="/ignition_1/ignition/images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Shipping Information Not Found:</strong><br>
<?php //TODO: proforma_changeAddress.php needed? ?>
	Sorry could not find any shipping settings for this location. Please change shipping location. <a href="order_changeAddress.php?orderid=<?php echo $order->ID; ?>&type=shipping">Click Here</a>
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
	Please select an Appropriate Shipping Option: <?php echo $proForma->PostageOptions; ?>
    </td>
  </tr>
</table>
<br />
<?php
	}
}

if($proForma->Postage->Days == 1 && $proForma->Status != 'Despatched' && $proForma->Status != 'Cancelled'){
?>
<table class="bubbleinfo" cellspacing="0">
  <tr>
    <td valign="top"><img src="/ignition_1/ignition/images/icon_alert_2.gif" width="16" height="16" align="absmiddle">	<strong>Next Day Delivery:</strong><br>
	This Customer has requested Next Day Delivery.
    </td>
  </tr>
</table>
<br />
<?php
}
?>

<table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
	<td>
      <table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
      <tr>
		<td valign="top" class="billing">
			<p> <strong>Organisation/Individual:</strong><br />
			<?php echo (empty($proForma->BillingOrg))?
					$proForma->Billing->GetFullName():
					$proForma->BillingOrg;  ?> <br />
                <?php echo $proForma->Customer->Contact->Person->Address->GetFormatted('<br />');  ?></p></td>
        <td valign="top" class="shipping"><p> <strong>Shipping Address:</strong><br />
                <?php echo $proForma->Shipping->GetFullName();  ?> <br />
                <?php echo $proForma->Shipping->Address->GetFormatted('<br />');  ?></p></td>
      </tr>
	  <?php if($isEditable) { ?>
	  <tr>
		<td class="billing"><input type="button" name="change" value="change" class="btn" onclick="window.location.href='proforma_changeAddress.php?proformaid=<?php echo $proForma->ID; ?>&type=billing'" /></td>
		<td class="shipping"><input type="button" name="change" value="change" class="btn" onclick="window.location.href='proforma_changeAddress.php?proformaid=<?php echo $proForma->ID; ?>&type=shipping'" /></td>
	  </tr>
	  <?php } ?>
    </table>
      <p><br />
      <br>
	  <?php
	  if((strtolower($proForma->Status) != 'ordered') && (strtolower($proForma->Status) != 'cancelled')){
		 ?>
        <input name="Cancel Pro Forma" type="button" id="Cancel Pro Forma" value="Cancel" class="btn" onclick="popUrl('./proforma_cancel.php?proformaid=<?php echo $proForma->ID; ?>$action=cancel', 650, 450);" />
        <?php } ?>
	  <?php
	  if((strtolower($proForma->Status) != 'ordered')) {
	   ?>
        <input name="Delete Pro Forma" type="button" id="Delete Pro Forma" value="Delete" class="btn" onclick="confirmRequest('./proforma_details.php?proformaid=<?php echo $proForma->ID; ?>&action=delete', 'Are you sure you would like to delete this pro forma permanently?');" />
        <?php } ?>
	  <?php
	  if((strtolower($proForma->Status) != 'ordered') && (strtolower($proForma->Status) != 'cancelled')){
		 ?>
        <input name="Convert" type="button" id="Convert Pro Forma" value="Convert" class="btn" onclick="window.location.href='./proforma_details.php?proformaid=<?php echo $proForma->ID; ?>&action=convert';" />

        <?php } ?>
        </p>
	</td><td align="right" valign="middle"><table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
      <tr>
        <th>Pro Forma Ref:</th>
        <td><?php echo $proForma->Prefix . $proForma->ID; ?></td>
      </tr>
      
      <?php
      if($proForma->Quote->ID > 0) {
          $proForma->Quote->Get();
          ?>
          
	      <tr>
	        <th>Quote ID:</th>
	        <td><a href="quote_details.php?quoteid=<?php echo $proForma->Quote->ID; ?>"><?php echo $proForma->Quote->Prefix . $proForma->Quote->ID; ?></a></td>
	      </tr>
	      
	      <?php
	  }
	  ?>
	  
	  <tr>
        <th>Customer Ref: </th>
        <td><?php echo $proForma->CustomID; ?> &nbsp;</td>
      </tr>
      <tr>
        <th>Customer: </th>
        <td><a href="contact_profile.php?cid=<?php echo $proForma->Customer->Contact->ID; ?>"><?php echo $proForma->Customer->Contact->Person->GetFullName(); ?></a></td>
      </tr>
      <tr>
        <th>Pro Forma Status:</th>
        <td><?php echo $proForma->Status; ?></td>
      </tr>
      <tr>
        <th>&nbsp;</th>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <th>Pro Forma Date: </th>
        <td><?php echo cDatetime($proForma->FormedOn, 'shortdate'); ?></td>
      </tr>
      <tr>
		<th>Email Address:</th>
		<td><?php echo ($isEditable) ? $form->GetHTML('email') : $proForma->Customer->Contact->Person->Email; ?></td>
	  </tr>
      <tr>
        <th>Emailed On: </th>
        <td><?php echo (!empty($proForma->EmailedOn))?cDatetime($proForma->EmailedOn, 'shortdate'):'';  ?>&nbsp;</td>
      </tr>
      <tr>
        <th>Emailed To: </th>
        <td><?php echo $proForma->EmailedTo; ?>&nbsp; <a href="proforma_details.php?proformaid=<?php echo $proForma->ID; ?>&amp;action=resend" title="Click to Resend Pro Forma Confirmation to Customer">(resend)</a></td>
      </tr>
      <tr>
        <th>Received On: </th>
        <td><?php echo cDatetime($proForma->ReceivedOn, 'shortdate'); ?></td>
      </tr>
      <tr>
        <th>Catalogue Sent</th>
        <td><?php
	if($proForma->Customer->Contact->CatalogueSentOn != '0000-00-00 00:00:00'){
	    echo "Catalogue sent on " . cDatetime($proForma->Customer->Contact->CatalogueSentOn, 'shortdate');
	    if($proForma->Customer->Contact->IsCatalogueRequested == "Y"){
		echo " (New One Requested - <span>". sprintf('<a href="?action=cancelrequestcatalogue&proformaid=%1$d">Cancel Request</a>', $proForma->ID) . "</span>)";
	    }else{
		echo sprintf(' - <span><a href="?action=requestcatalogue&proformaid=%1$d">Request New Catalogue</a>', $proForma->ID) . "</span>";
	    }
	}else if($proForma->Customer->Contact->IsCatalogueRequested == "Y"){
	    echo "Never sent but requested &nbsp;<span> " . sprintf('<a href="?action=cancelrequestcatalogue&proformaid=%1$d">Cancel Request</a>', $proForma->ID) . "</span>";
	}else{

	    echo '<em>&lt;Never&gt;</em>&nbsp;<span>' . sprintf('<a href="?action=requestcatalogue&proformaid=%1$d">Request Catalogue</a>', $proForma->ID) . "</span>";
	}?></td>
      </tr>
      
      
      
    </table>                </td>
  </tr>
  <tr>
    <td colspan="2"><br>                  <br>
      <table cellspacing="0" class="orderDetails">
      <tr>
        <th>Qty</th>
        <th>Product</th>
        <th style="text-align: center;">Spec Sheets</th>
       	<th>Discount</th>
       	<th>Handling</th>
        <th>Quickfind</th>
        <th style="text-align: right;">Price</th>
        <th style="text-align: right;">Line Total</th>
      </tr>
      <?php
      for($i=0; $i < count($proForma->Line); $i++){
?>
      <tr>
        <td>
		<?php if ($isEditable){ ?>
        <a href="proforma_details.php?proformaid=
            <?php echo $proForma->ID; ?>&action=remove&line=
            <?php echo $proForma->Line[$i]->ID; ?>">
            <img src="images/icon_trash_1.gif" alt="Remove" border="0" /></a>
		<?php } ?>
        <?php
        echo ($isEditable)? $form->GetHTML('qty_'. $proForma->Line[$i]->ID):
                                $proForma->Line[$i]->Quantity;
        ?>x</td>
        <td>
			<?php echo $proForma->Line[$i]->Product->Name; ?>
			<?php
			if(!empty($proForma->Line[$i]->Discount)){
				$discountVal = explode(':', $proForma->Line[$i]->DiscountInformation);
				if(trim($discountVal[0]) == 'azxcustom') {
					$showDiscount = 'Custom Discount';
				} else {
					$showDiscount = $proForma->Line[$i]->DiscountInformation;
				}
				if(!empty($showDiscount)) {
					echo sprintf("<br />(%s -&pound;%s)", $showDiscount, number_format($proForma->Line[$i]->Discount, 2, '.',','));
				} else {
					echo sprintf("<br />(%s -&pound;%s)",$proForma->Line[$i]->DiscountInformation, number_format($proForma->Line[$i]->Discount, 2, '.',','));
				}
			}
			?>
		</td>
		<td align="center"><?php echo ($isEditable) ? $form->GetHTML(sprintf('downloads_%d', $proForma->Line[$i]->ID)) : $proForma->Line[$i]->IncludeDownloads; ?></td>
		<td nowrap="nowrap">
		<?php
		print $form->GetHTML('discount_'.$proForma->Line[$i]->ID); ?>%					</td>
		<td nowrap="nowrap">
		<?php
		print $form->GetHTML('handling_'.$proForma->Line[$i]->ID); ?>%					</td>
        <td><a href="product_profile.php?pid=<?php echo $proForma->Line[$i]->Product->ID; ?>"><?php echo $proForma->Line[$i]->Product->ID; ?></a></td>
        <td align="right">&pound;<?php echo number_format($proForma->Line[$i]->Price, 2, '.', ','); ?></td>
        <td align="right">&pound;<?php echo number_format($proForma->Line[$i]->Total, 2, '.', ','); ?></td>
      </tr>
      <?php
      }
?>
      <tr>
        <td colspan="6" align="left">Cart Weight: ~<?php echo $proForma->Weight; ?>Kg</td>
        <td align="right">Sub Total:</td>
		<td align="right">&pound;
			<?php echo number_format($proForma->SubTotal, 2, '.', ',');
			?>
		</td>
      </tr>
    </table>
    <br></td>
  </tr>
  <tr>
    <td align="left" valign="top">
    <?php if($warehouseEditable && $proForma->Status != 'ordered' && $proFormad->Status != 'cancelled'){?>
	<input type="submit" name="action" value="update" class="btn" />
	<?php }
	if ($isEditable && $proForma->Status != 'ordered' && $proForma->Status != 'cancelled'){ ?>
	<input type="button" name="add product" value="add" class="btn" onclick="window.location.href='proforma_add.php?proformaid=<?php echo $proForma->ID; ?>';" />
	<?php } ?>
	<br />
	<br />
      <p><span class="smallGreyText">
      </span></p></td>
    <td align="right"><table border="0" cellpadding="5" cellspacing="0" class="orderTotals">
      <tr>
        <th colspan="2">Tax &amp; Shipping</th>
      </tr>
      <tr>
        <td>Delivery Option:</td>
        <td align="right">
          <?php
          if (!$isEditable){
            $proForma->Postage->Get();
            echo $proForma->Postage->Name;
          } else {
            $proForma->Recalculate();
            echo $proForma->PostageOptions;
          }
			?>
        </td>
      </tr>
      <tr>
        <td>
			Shipping
			<?php if($isEditable){
				if($proForma->IsCustomShipping == 'N'){
			?>
			<a href="<?php echo $_SERVER['PHP_SELF']; ?>?proformaid=<?php echo $proForma->ID; ?>&shipping=custom">(customise)</a>
			<?php } else { ?>
			<a href="<?php echo $_SERVER['PHP_SELF']; ?>?proformaid=<?php echo $proForma->ID; ?>&shipping=standard">(standardise)</a>
			<?php }} ?>
			:
		</td>
        <td align="right">

		&pound;
		<?php if($isEditable){
			if($proForma->IsCustomShipping == 'N'){
				echo number_format($proForma->TotalShipping, 2, ".", ",");
			} else {
		?>
			<input type="text" name="setShipping" value="<?php echo number_format($proForma->TotalShipping, 2, ".", ",");  ?>" size="10" />
		<?php }} else {
			echo number_format($proForma->TotalShipping, 2, ".", ",");
		}
		 ?>
		</td>
      </tr>
	  <?php
	  if(!empty($proForma->TotalDiscount)) {
				?>
					<tr>
						<td>
							Discount:
						<?php
						if(!empty($proForma->Coupon->ID)){
							$proForma->Coupon->Get();
							echo sprintf('<br /><span class="smallGreyText">%s (%s)</span>', $proForma->Coupon->Name, $proForma->Coupon->Reference);
						}
						?>
						</td>
						<td align="right">-&pound;<?php echo number_format($proForma->TotalDiscount, 2, ".", ","); ?></td>
					</tr>
				<?php
	  }
				?>
      <tr>
        <td>Net:</td>
		<td align="right">&pound;
			<?php echo number_format($proForma->TotalNet, 2, ".", ",");
			?>
		</td>
      </tr>
	  <?php
		if($isEditable){
			?>
			<tr><td>Tax Exemption Code:</td><td align="right"><?php echo $form->GetHTML('taxexemptcode'); ?></td></tr>
			<?php
		} elseif(!empty($proForma->TaxExemptCode)) {
			?>
			<tr><td>Tax Exemption Code:</td><td align="right"><?php echo $proForma->TaxExemptCode; ?></td></tr>
			<?php
		}
	  ?>
	  <tr>
		<td>Tax:</td>
		<td align="right">&pound;<?php echo number_format($proForma->TotalTax, 2, ".", ","); ?></td>
      </tr>
      <tr>
        <td><strong>Total:</strong></td>
		<td align="right"><strong>&pound;<?php echo number_format($proForma->Total, 2, ".", ","); ?></strong></td>
      </tr>
    </table></td>
  </tr>
</table>
<?php
echo $form->Close();
$page->Display('footer');
require_once('lib/common/app_footer.php');