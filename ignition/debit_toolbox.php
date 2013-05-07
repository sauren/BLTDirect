<?php
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Debit.php');

	$session->Secure(2);

	if(!isset($cart)){
		global $cart;
		global $session;

		$cart = new Debit(null,$session);
		$cart->DSID = $session->ID;
		if($cart->Exists()==false){
			$cart->Add();
		}

	}

	$toolsWindow = new StandardWindow("Debit Tools");
	echo '<div style="width:240px;">';
	echo $toolsWindow->Open();
    echo $toolsWindow->AddHeader('Add by Quickfind Code');
	echo $toolsWindow->OpenContent();
?>
<form id="addByQuickfind" name="addByQuickfind" method="post" action="debit_customise.php">
  <input type="hidden" name="action" value="customise" />
  <table width="100%" border="0" class="manualOrderToolbox">
    <tr>
      <td class="quickfind">Quickfind <br />
        <input name="product" type="text" id="quickfind" size="3" />
      </td>
      <td align="center" class="multiplier">x</td>
      <td class="quantity">Quantity<br />
      <input name="quantity" type="text" id="quantity" size="1" value="1" />
	  </td>
	  <td class="add" valign="bottom"><input type="submit" name="add" value="add" id="add" class="btn" /></td>
    </tr>
  </table>
</form>
<?php
	echo $toolsWindow->CloseContent();
	echo $toolsWindow->AddHeader('Debit Cart');
	echo $toolsWindow->OpenContent();
?>


<table class="miniCart" cellspacing="0">
<?php
	$totalAmount = 0;
	$cart->GetLines();
	if(count($cart->Line) > 0){
	for($i=0; $i < count($cart->Line); $i++){
		$totalAmount += $cart->Line[$i]->Quantity * $cart->Line[$i]->Cost;
?>
		<tr>
			<td class="product" colspan="2"><a href="./debit_product.php?pid=<?php echo $cart->Line[$i]->Product->ID; ?>" title="Click to View Product"><?php echo $cart->Line[$i]->Description; ?></a></td>
		</tr>
		<tr>
			<td class="qty"><a href="./debit_create.php" title="Click to Edit Cart">Qty <?php echo $cart->Line[$i]->Quantity; ?> x</a></td>
			<td class="price">&pound;<?php echo number_format($cart->Line[$i]->Quantity * $cart->Line[$i]->Cost, 2, '.', ','); ?></td>
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
			<td class="total">Total</td>
			<td class="totalPrice">&pound;<?php echo number_format($totalAmount, 2, '.', ','); ?></td>
		</tr>
		<tr>
			<td class="buttons" colspan="2" align="center">
				<form method="post" action="debit_create.php">
					<input type="submit" name="View" value="View" class="btn" title="Click to view or edit your debit cart" />
				</form>

				<form method="post" action="debit_checkout.php">
					<input type="submit" name="Checkout" value="Checkout" class="btn" title="Click to add items to your debit cart" />
				</form>
			</td>
		</tr>
</table>

<?php

	echo $toolsWindow->CloseContent();
	echo $toolsWindow->AddHeader('Search Products');
	echo $toolsWindow->OpenContent();
?>
<form id="orderSearch" name="orderSearch" method="post" action="debit_productSearch.php">
  <label for="searchString" style="font-size:10px">Search Term</label><br />
  <input type="text" name="searchString" id="searchString" size="17" value="<?php print isset($_REQUEST['searchString']) ? $_REQUEST['searchString'] : ''; ?>" />
  <input type="submit" name="search" value="go" id="search" class="btn" />
</form>
<?php
	echo $toolsWindow->CloseContent();
	echo $toolsWindow->Close();
	echo '</div>';
?>