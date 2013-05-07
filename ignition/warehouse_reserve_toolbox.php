<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(3);

$cart = new Purchase(null, $session);
$cart->PSID = $session->ID;

if(!$cart->Exists()){
	$cart->SetDefaults();
	$cart->Add();
}

$window = new StandardWindow('Tools');

echo '<div style="width:240px;">';

echo $window->Open();
echo $window->AddHeader('Add by Quickfind');
echo $window->OpenContent();
?>
<form id="addByQuickfind" name="addByQuickfind" method="post" action="warehouse_reserve_customise.php">
	<input type="hidden" name="action" value="customise" />

	<table width="100%" border="0" class="manualOrderToolbox">
		<tr>
			<td class="quickfind">Quickfind<br /><input name="product" type="text" id="quickfind" size="3" /></td>
			<td align="center" class="multiplier">x</td>
			<td class="quantity">Quantity<br /><input name="quantity" type="text" id="quantity" size="1" value="1" /></td>
			<td class="add"><input type="submit" name="add" value="add" id="add" class="btn" /></td>
		</tr>
	</table>

</form>

<?php
echo $window->CloseContent();
echo $window->Close();

echo '</div>';