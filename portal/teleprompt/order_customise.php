<?php
	require_once('lib/common/app_header.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BreadCrumb.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');

	$session->Secure(2);

	global $cart;

	$cart = new Cart($session, true);
	$cart->Calculate();

	$referrer = 'None (Manual Order)';

	// Check Product ID has been sent
	// ------------------------------------------
	if(isset($_REQUEST['product']) && is_numeric($_REQUEST['product'])){
		$product = new Product($_REQUEST['product']);
	} else {
		echo "No Product ID was received";
		exit();
	}

	if(isset($_REQUEST['quantityText']) && is_numeric($_REQUEST['quantityText']))
        $productQty = $_REQUEST['quantityText'];
    elseif(isset($_REQUEST['quantity']) && is_numeric($_REQUEST['quantity']))
        $productQty = $_REQUEST['quantity'];
    else
        $productQty = 1;

    $productCat = (isset($_REQUEST['category']) && is_numeric($_REQUEST['category']))? $_REQUEST['category']:0;
	$breadCrumb = new BreadCrumb();
	$breadCrumb->Get($productCat, true);

	$product->GetOptions();

	if(isset($_REQUEST['action']) && strtolower($_REQUEST['action']) == 'customise'){
        if($cart->AddLine($product->ID, $productQty) && count($product->Options->Group) > 0){
            redirect(sprintf("Location: %s?quantity=%d&category=%d&product=%d", $_SERVER['PHP_SELF'], $productQty, $productCat, $product->ID));
		} else {
			redirect("Location: order_cart.php");
		}
	}

	// htmlBuffer will store our form and options for output
	// ------------------------------------------
	$htmlBuffer = "";

	// Create Form
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 6);
	$form->SetValue('action', 'add');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
    $form->AddField('product', 'Product', 'hidden', $product->ID, 'numeric_unsigned', 1, 11);
    $form->AddField('category', 'Category', 'hidden', $productCat, 'numeric_unsigned', 1, 11);
    $form->AddField('quantity', 'Quantity', 'hidden', $productQty, 'numeric_unsigned', 1, 9);

	// Get Option Groups
	//----------------------------------
	// All Product Option Groups are POG
	for($i=0; $i < count($product->Options->Group); $i++){
		// Set Group Header
		$htmlBuffer .= sprintf("<h3 class=\"productOptionGroup\">%s</h3><br />%s<p>\n",
							$product->Options->Group[$i]->Name,
							$product->Options->Group[$i]->Description
							);
		// Check the type of Group
		// e = radio
		// s = radio
		// m = checkbox
		$pog = "pog_" . $product->Options->Group[$i]->ID;
		if(strtolower($product->Options->Group[$i]->Type) == 's'){
			$form->AddField($pog, $product->Options->Group[$i]->Name, 'radio', '', 'numeric_unsigned', 1, 11, false);
		}

		// Get Options
		//--------------------------------
		for($j=0; $j < count($product->Options->Group[$i]->Item); $j++){
			// Create Html & Label vars
			$optionInput = "";
			$optionLabel = "";
			$optionPrice = 0;
			$optionGroup = "";
			$optionId = "";

			// Get Html & Label vars
			switch(strtolower($product->Options->Group[$i]->Type)){
				case 'm':
					$form->AddField($pog . "_" . $product->Options->Group[$i]->Item[$j]->ID, $product->Options->Group[$i]->Item[$j]->Name, 'checkbox', $product->Options->Group[$i]->Item[$j]->IsSelected, 'boolean', NULL, NULL, false);
					$optionGroup = $pog . "_" . $product->Options->Group[$i]->Item[$j]->ID;
					$optionId = NULL;
					// Check to see if it was selected
					if(isset($_REQUEST['action']) && strtolower($_REQUEST['action']) == 'add' && isset($_REQUEST['confirm'])){
						if(strtolower($form->GetValue($optionGroup)) == 'y' && $product->Options->Group[$i]->Item[$j]->UseProductID > 0){
							$cart->AddLine($product->Options->Group[$i]->Item[$j]->UseProductID, $product->Options->Group[$i]->Item[$j]->Quantity * $productQty);
						}
					}
					break;
				case 's':
					$form->AddOption($pog, $product->Options->Group[$i]->Item[$j]->ID, $product->Options->Group[$i]->Item[$j]->Name);
					if(strtolower($product->Options->Group[$i]->Item[$j]->IsSelected) == 'y' && (!isset($_REQUEST['action']) || (isset($_REQUEST['action']) && (strtolower($_REQUEST['action']) != 'add')))) $form->SetValue($pog, $product->Options->Group[$i]->Item[$j]->ID);
					$optionGroup = $pog;
					$optionId = $j+1;
					// Check to see if it was selected
					if(isset($_REQUEST['action']) && strtolower($_REQUEST['action']) == 'add' && isset($_REQUEST['confirm'])){
						if($form->GetValue($pog) == $product->Options->Group[$i]->Item[$j]->ID && $product->Options->Group[$i]->Item[$j]->UseProductID > 0){
							$cart->AddLine($product->Options->Group[$i]->Item[$j]->UseProductID, $product->Options->Group[$i]->Item[$j]->Quantity * $productQty);
						}
					}
					break;
			}

			$optionInput = $form->GetHtml($optionGroup, $optionId);
			$optionLabel = $form->GetLabel($optionGroup, $optionId);

			// Get Quantity
			//-------------------------------
			if($product->Options->Group[$i]->Item[$j]->Quantity > 1){
				$optionLabel = $product->Options->Group[$i]->Item[$j]->Quantity . " x " . $optionLabel;
			}

			// Get Options Price
			//-------------------------------
			if($product->Options->Group[$i]->Item[$j]->UseProductID > 0){
				// Get Product Price
				$tempProduct = new Product($product->Options->Group[$i]->Item[$j]->UseProductID);
				$optionPrice = $tempProduct->PriceCurrent * $product->Options->Group[$i]->Item[$j]->Quantity;
			} else {
				// Get Option Price
				$optionPrice = $product->Options->Group[$i]->Item[$j]->Price * $product->Options->Group[$i]->Item[$j]->Quantity;
			}

			// Evaluate Price
			//--------------------------------
			if($optionPrice == 0){
				$optionPrice = "";
			} else {
				$optionPrice = "(+&pound;" . number_format($optionPrice, 2, '.', ',') . ")";
			}

			// Set Buffer
			//--------------------------------
			$htmlBuffer .= sprintf("%s %s %s<br />", $optionInput, $optionLabel, $optionPrice);
		}
	}

	if(isset($_REQUEST['action']) && strtolower($_REQUEST['action']) == 'add' && isset($_REQUEST['confirm'])){
		redirect("Location: order_cart.php");
		exit;
	}


	// Initiate the Page
	$page = new Page('Create a New Order Manually', '');
	$page->Display('header');
?>
<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top"><strong>Added to Cart</strong> <br />
The following item was added to your shopping cart.
<table border="0" cellpadding="5" cellspacing="0">
				<tr>
					<td>
						<strong><?php echo $productQty . " x " . $product->Name; ?></strong><br />
						<span class="currentPrice">&pound;<?php echo number_format($productQty * $product->PriceCurrent, 2, '.', ','); ?></span><br />
						<span class="smallGreyText">Excludes VAT &amp; Shipping</span>					</td>
				</tr>
			</table>
			<br />
			<br />
	        <strong>Additional Options<br />
      </strong>The product added to your shopping cart has additional options. Please select the options you would like from below.<br />
			<?php
				echo $form->Open();
				echo $form->GetHTML('action');
				echo $form->GetHTML('confirm');
				echo $form->GetHTML('product');
				echo $form->GetHTML('category');
				echo $form->GetHTML('quantity');
				echo $htmlBuffer;
			?>
			<br />
			<input type="submit" name="Continue" value="continue" class="submit" />
	<?php echo $form->Close(); ?></td>
  </tr>
</table>
<?php
	$page->Display('footer');
require_once('lib/common/app_footer.php');
?>
