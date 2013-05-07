<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

if(!isset($cart)){
	global $cart;
	global $session;

	$cart = new Cart($session, true);
	$cart->Calculate();
}
?>
<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>
<script type="text/javascript">
<!--
var ajax = new HttpRequest();
var quantityText = null;
var quantitySelect = null;
var quickfind = null;

var responseHandler = function(s) {
	quantityText.removeAttribute("disabled");
	if(s.search("min") == 0){
		var val = s.split("=");
		quantityText.value = val[1];
		quantityText.style.display = "inline";
		quantitySelect.style.display = "none";
		document.getElementById("loading").style.display = "none";
		document.getElementById("add").removeAttribute("disabled");
	} else {
		s = s.split(",");
		var option = null;
		while(quantitySelect.firstChild)
		quantitySelect.removeChild(quantitySelect.firstChild);
		for(var i=0; i<s.length; i++){
			option = document.createElement("option");
			option.setAttribute("value", s[i]);
			option.appendChild(document.createTextNode(s[i]));
			quantitySelect.appendChild(option);
		}
		quantityText.style.display = "none";
		document.getElementById("loading").style.display = "none";
		quantitySelect.style.display = "inline";
		s = null;
		document.getElementById("add").removeAttribute("disabled");
	}
}

var errorHandler = function(er) {
	document.getElementById("loading").style.display = "none";
	quantityText.style.display = "inline";
	if(confirm("The search has timed out. Please try again.")) {
		ajax.get("lib/util/loadOrderToolbox.php?id=" + quickfind.value);
		quantityText.style.display = quantitySelect.style.display = "none";
		document.getElementById("loading").style.display = "inline";
	}
	else {
		quickfind.value = '';
		quickfind.focus();
	}
}

ajax.setHandlerResponse(responseHandler);
ajax.setHandlerError(errorHandler)
ajax.setTimeout(5000);
ajax.setRetryStatus(HttpRequest.RETRY_ON_TIMEOUT);
ajax.setCaching(false);

var timer = null;

window.onload = function() {
	quantityText = document.getElementById("quantityText");
	quantitySelect = document.getElementById("quantitySelect");
	quantitySelect.style.display = "none";
	quickfind = document.getElementById("quickfind");
	quantityText.setAttribute("disabled", "disabled");
	quickfind.onkeyup = function(e) {
		clearTimeout(timer);
		timer = setTimeout(function() {
			if(!e) e = window.event;
			if(quickfind.value.length > 0 && parseInt(quickfind.value)){
				ajax.get("lib/util/loadOrderToolbox.php?id=" + quickfind.value);
				quantityText.style.display = quantitySelect.style.display = "none";
				document.getElementById("loading").style.display = "inline";
			} else {
				quantityText.setAttribute("disabled", "disabled");
			}
		}, 500);
	}

	quickfind.onkeydowm = function(e) {
		clearTimeout(timer);
	}
}
//-->
</script>

<?php
$toolsWindow = new StandardWindow("Order Tools");

echo '<div style="width:240px;">';
echo $toolsWindow->Open();
echo $toolsWindow->AddHeader('Add by Quickfind Code');
echo $toolsWindow->OpenContent();
?>
<form id="addByQuickfind" name="addByQuickfind" method="post" action="order_customise.php" autocomplete="off">
  <input type="hidden" name="action" value="customise" />
  <table width="100%" border="0" class="manualOrderToolbox">
    <tr>
      <td class="quickfind">Quickfind <br />
        <input name="product" type="text" id="quickfind" size="3" />
      </td>
      <td align="center" class="multiplier">x</td>
      <td class="quantity">Quantity<br />
        <input name="quantityText" type="text" id="quantityText" size="1" value="1" />
        <select id="quantitySelect" name="quantitySelect" style="display: none;" >
        </select>
        <img style="display: none" id="loading" src="images/loading.gif" alt="loading" />
	  </td>
	  <td class="add" valign="bottom"><input type="submit" name="add" value="add" id="add" class="btn" disabled="disabled" /></td>
    </tr>
  </table>
</form>
<?php
echo $toolsWindow->CloseContent();
echo $toolsWindow->AddHeader('Shopping Cart');
echo $toolsWindow->OpenContent();
?>

<table class="miniCart" cellspacing="0">
<?php
if(count($cart->Line) > 0){
	for($i=0; $i < count($cart->Line); $i++){ ?>
		<tr>
            <td class="product" colspan="2">
                <a href="./order_product.php?pid=<?php echo $cart->Line[$i]->Product->ID; ?>"
                title="Click to View Product"><?php echo $cart->Line[$i]->Product->Name; ?></a>
            </td>
		</tr>
		<tr>
            <td class="qty"><a href="./order_cart.php" title="Click to Edit Cart">Qty
                <?php echo $cart->Line[$i]->Quantity; ?> x</a>
            </td>
			<td class="price">&pound;<?php echo number_format($cart->Line[$i]->Total,  2, '.', ','); ?></td>
		</tr>
<?php
	}
} else {
?>
		<tr>
			<td class="qty" colspan="2">Your Cart is Empty</td>
		</tr>
<?php } ?>
		<tr>
			<td class="subTotal">Subtotal</td>
			<td class="subTotalPrice">&pound;<?php echo number_format($cart->SubTotal, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td class="shipping">Shipping</td>
			<td class="shippingPrice">&pound;<?php echo number_format($cart->ShippingTotal, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td class="shipping">Discount</td>
			<td class="shippingPrice">-&pound;<?php echo number_format($cart->Discount, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td class="tax">VAT</td>
			<td class="taxPrice">&pound;<?php echo number_format($cart->TaxTotal, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td class="total">Total</td>
			<td class="totalPrice">&pound;<?php echo number_format($cart->Total, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td class="buttons" colspan="2" align="center">
				<form method="post" action="order_cart.php">
                <input type="submit" name="view" value="view" class="btn"
                title="Click to View or Edit your Shopping Cart" />
				</form>

				<form method="post" action="order_shipping.php">
                <input type="submit" name="checkout" value="checkout"class="btn"
                title="Click to Purchase Items in Your Shopping Cart" />
				</form>
			</td>
		</tr>
</table>

<?php
echo $toolsWindow->CloseContent();
echo $toolsWindow->AddHeader('Manage Customer');
echo $toolsWindow->OpenContent();
?>

<div style="text-align: center;">
	<?php
	if($cart->Customer->ID == 0) {
		?>

		<form method="post" action="order_checkout.php">
			<input type="submit" name="selectcustomer" value="select customer" class="btn" />
		</form>

		<?php
	} else {
		?>

		<form method="post" action="order_create.php">
			<input type="hidden" name="action" value="removecustomer" />
			<input type="submit" name="removecustomer" value="remove customer" class="btn" />
		</form>

		<?php
	}
	?>

</div>

<?php
echo $toolsWindow->CloseContent();
echo $toolsWindow->AddHeader('Search Products');
echo $toolsWindow->OpenContent();
?>
<form id="orderSearch" name="orderSearch" method="post" action="order_productSearch.php">
  <label for="searchString" style="font-size:10px">Search Term</label><br />
  <input type="text" name="searchString" id="searchString" size="17" />
  <input type="submit" name="search" value="go" id="search" class="btn" />
</form>
<?php
echo $toolsWindow->CloseContent();
echo $toolsWindow->AddHeader('Browse Products');
echo $toolsWindow->OpenContent();

/*
Left Navigation
*/
$data = new DataQuery("select * from product_categories where Category_Parent_ID=1 and Is_Active='Y'");

echo "\t<ul class=\"rootCat\">\n";
while($data->Row){
	echo sprintf("\t\t<li><a href=\"order_products.php?cat=%d\">%s</a></li>\n", $data->Row['Category_ID'], $data->Row['Category_Title']);
	$data->Next();
}
echo "\t</ul>\n";

$data->Disconnect();
?>


<?php

echo $toolsWindow->CloseContent();
echo $toolsWindow->Close();
echo '</div>';