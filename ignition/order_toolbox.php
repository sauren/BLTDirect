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
<?php if($cart->isAdopted){ ?>
<div class="info">
	<form action="order_adopt.php">
		<h1>Cart Adopted</h1>
		<p>Ref. <?php echo $cart->ID; ?></p>
		<p>You have adopted a customer's shopping cart. To get your normal session's cart press the release button below:</p>
		<input type="hidden" name="ref" value="<?php echo $cart->ID; ?>" />
		<input type="submit" name="release" value="release" class="btn" />
	</form>
</div>
<br />
<?php } ?>

<?php
$toolsWindow = new StandardWindow("Order Tools");
echo '<div style="width:300px;">';
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
echo $toolsWindow->AddHeader('Add new Demo Product');
echo $toolsWindow->OpenContent();

$form78 = new Form('order_new_demo.php');
$form78->TabIndex = 100;
$form78->AddField('action', 'Action', 'hidden', 'update', 'alpha', 1, 12);
$form78->SetValue('action', 'update');
$form78->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form78->AddField('productName', 'Product Name', 'text', '', 'anything', 0, 60, true, 'style="width: 98%;"');
$form78->AddField('productDescription', 'Product Description', 'textarea', '', 'anything', 0, 1000, true, 'style="width: 98%;" rows="3"');
$form78->AddField('productPrice', 'Price', 'text', '', 'float', 1, 11, true, 'style="width: 98%;"');
$form78->AddField('productSupplier', 'Supplier', 'select', '', 'numeric_unsigned', 1, 11, true, 'style="width: 100%;"');
$form78->AddField('shippingClass', 'Shipping Class', 'select', '', 'numeric_unsigned', 1, 11, true, 'style="width: 100%;"');
$form78->AddField('demoNotes', 'Demo Notes', 'textarea', '', 'anything', null, null, true, 'style="width: 98%;" rows="3"');
$form78->AddField('productCost', 'Cost', 'text', '', 'float', 1, 11, true, 'style="width: 98%;"');
$form78->AddField('file', 'File', 'file', '', 'file', null, null, false);

$data = new DataQuery(sprintf("SELECT s.Supplier_ID, p.Name_First, p.Name_Last, o.Org_Name FROM supplier s INNER JOIN contact c on s.Contact_ID =  c.Contact_ID INNER JOIN person p on c.Person_ID = p.Person_ID LEFT JOIN contact c2 on c2.Contact_ID = c.Parent_Contact_ID LEFT JOIN organisation o on c2.Org_ID = o.Org_ID"));
while($data->Row) {
	$form78->AddOption('productSupplier', $data->Row['Supplier_ID'], (strlen($data->Row['Org_Name']) > 0) ?  sprintf('%s', $data->Row['Org_Name']) : sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last']));

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery("select * from shipping_class");
do{
	$form78->AddOption('shippingClass',
	$data->Row['Shipping_Class_ID'],
	$data->Row['Shipping_Class_Title']);
	$data->Next();
} while($data->Row);
$data->Disconnect();
$data = NULL;

echo $form78->Open();
echo $form78->GetHTML('action');
echo $form78->GetHTML('confirm');
?>

  <table width="100%" border="0" class="manualOrderToolbox">
    <tr>
      <td class="quickfind" colspan="2">Product Name<br /><?php echo $form78->GetHTML('productName'); ?></td>
    </tr>
    <tr>
      <td class="quickfind" colspan="2">Product Description<br /><?php echo $form78->GetHTML('productDescription'); ?></td>
    </tr>
    <tr>
      <td class="quickfind" colspan="2">Download File<br /><?php echo $form78->GetHTML('file'); ?></td>
    </tr>
    <tr>
      <td class="quickfind">Price (&pound;)<br /><?php echo $form78->GetHTML('productPrice'); ?></td>
      <td class="quickfind">Cost (&pound;)<br /><?php echo $form78->GetHTML('productCost'); ?></td>
    </tr>
     <tr>
      <td class="quickfind" colspan="2">Supplier<br /><?php echo $form78->GetHTML('productSupplier'); ?></td>
    </tr>
	<tr>
      <td class="quickfind" colspan="2">Shipping Class<br /><?php echo $form78->GetHTML('shippingClass'); ?></td>
    </tr>
    <tr>
      <td class="quickfind" colspan="2">Demo Notes<br /><?php echo $form78->GetHTML('demoNotes'); ?></td>
    </tr>
    <tr>
	  <td class="add" colspan="2" valign="bottom" align="left"><input type="submit" name="add" value="add" class="btn" /></td>
    </tr>
  </table>

<?php
echo $form78->Close();

echo $toolsWindow->CloseContent();
echo $toolsWindow->AddHeader('Cart Adoption');
echo $toolsWindow->OpenContent();
?>
	<form action="order_adopt.php">
		<label for="adoptRef">
			Adopt Cart Ref.<br />
			Ask the customer for their cart reference.
		</label>

		<input type="text" name="ref" id="adoptRef" />
		<input type="submit" name="adopt" value="adopt" class="btn" />
	</form>
<?php

echo $toolsWindow->CloseContent();
echo $toolsWindow->Close();
echo '</div>';
?>
