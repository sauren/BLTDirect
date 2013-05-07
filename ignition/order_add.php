<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

$session->Secure(3);

$order = new Order($_REQUEST['orderid']);

$product = new Product();

if(isset($_REQUEST['product'])) {
	$product->Get($_REQUEST['product']);
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 1, 12);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
$form->AddField('quantity', 'Quantity', 'text', '1', 'numeric_unsigned', 1, 9);
$form->AddField('product', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('name', 'Product', 'text', '', 'anything', null, null, false, 'onFocus="this.Blur();"');
$form->AddField('complementary', 'Is Complementary', 'checkbox', 'N', 'boolean', 1, 1, false);

if(isset($_REQUEST['complementary']) && ($_REQUEST['complementary'] == 'true')) {
	$form->SetValue('product', $product->ID);
	$form->SetValue('name', $product->Name);
	$form->SetValue('complementary', 'Y');
}

if(isset($_REQUEST['find'])){
	if(is_numeric($_REQUEST['name'])){
		$product = new Product();
		if(($product->Get($_REQUEST['name']))){
			$form->SetValue('product', $product->ID);
			$form->SetValue('name', $product->Name);
		} else
		$extra = 'search';
	} else {
		$extra = 'search';
	}

	view($form, $extra);
	exit;
} elseif(isset($_REQUEST['add'])){
	if($form->Validate()){
		$order->AddLine($form->GetValue('quantity'), $form->GetValue('product'), $form->GetValue('complementary'));

		$order->Recalculate();
		//Check if the quantity has been increased to a high amount
		$paymentLookupSql = sprintf("select * from payment where Order_ID=%d && `Transaction_Type` = 'Authenticate' ORDER BY Created_ON DESC",mysql_real_escape_string($order->ID));
		$paymentLookup = new DataQuery($paymentLookupSql);
		$orderAmount = $paymentAmount = $order->Total;
		if($paymentLookup->TotalRows > 0){
			$paymentAmount = $paymentLookup->Row['Amount'];
		}
		if(($orderAmount - $paymentAmount  > ($paymentAmount * ((strtotime($order->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($order->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))))){
			redirect("Location: order_takePayment.php?orderid=".$order->ID);
			exit;
		}else{
			redirect(sprintf("Location: order_details.php?orderid=%d", $order->ID));
		}
	}
} else {
	view($form);
	exit;
}
view($form, 'add', $extra);

function view($form, $extra=NULL){
	global $product;
	global $order;

	$page = new Page(sprintf('Add Product to Order Ref: %s%s', $order->Prefix, $order->ID),'Use the search box to add a product to this order.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add a Product.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('orderid');
	echo $form->GetHTML('product');
	echo $window->Open();
	echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('',"<strong>IMPORTANT:</strong> If you add an item to the order you may be asked for the credit card details again. If this is the case then you will
								be redirected to a page that will allow you to enter them in. As such it is advised that you have the credit card details to hand.");
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity').$form->GetIcon('quantity'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . "<input type=\"submit\" name=\"find\" value=\"find\" class=\"btn\" />\n");
	echo $webForm->AddRow($form->GetLabel('complementary'), $form->GetHTML('complementary').$form->GetIcon('complementary'));

	if(!empty($product->ID)){
		echo $webForm->AddRow('', '<input type="submit" name="add" value="add" class="btn" />');
	}

	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	echo "<br>";

	if(strtolower($extra) == 'search'){
		require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ProductSearch.php');
		require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataTable.php');

		$search = new ProductSearch($_REQUEST['name'],'./order_add.php?name=');
		$search->PrepareSQL();

		$table = new DataTable('results');
		$table->AddField('ID', 'Product_ID', 'left');
		$table->AddField('Title', 'Product_Title', 'left');
		$table->SetSQL($search->Query);
		$table->SetMaxRows(10);
		$table->Order = 'DESC';
		$table->OrderBy = 'score';
		$table->Finalise();
		$table->ExecuteSQL();

		$table->DisplayNavigation();
		echo $table->GetTableHeader();
		while($table->Table->Row){
			$prod = new Product($table->Table->Row['Product_ID']);

			echo '<tr>';
			echo sprintf('<td><img src="../images/products/%s" /></td>', $prod->DefaultImage->Thumb->FileName);
			echo sprintf('<td><strong><a href="product_profile.php?pid=%s">%s</a></strong><br />Quickfind: <strong>%s</strong>, SKU: %s, Price &pound;%s (Inc. VAT)</td>',$prod->ID, $prod->Name, $prod->ID, $prod->SKU, number_format($prod->PriceCurrentIncTax, 2));
			echo sprintf('<td><a href="order_add.php?find=true&name=%s&orderid=%s">[USE]</a></td></tr>', $prod->ID, $_REQUEST['orderid']);
			echo '</tr>';

			$table->Next();
		}
		echo '</table>';

		echo "<br>";
		$table->DisplayNavigation();
	} else {
		$table = new DataTable('prod');
		$table->SetSQL(sprintf("SELECT Product_ID, SKU, Product_Title FROM product WHERE Is_Complementary='Y'"));
		$table->AddField('Auto ID#', 'Product_ID', 'right');
		$table->AddField('SKU', 'SKU', 'left');
		$table->AddField('Product Title', 'Product_Title', 'left');
		$table->AddLink(sprintf("order_add.php?complementary=true&orderid=%d&product=%%s", $order->ID), "<img src=\"./images/aztector_3.gif\" alt=\"Add Complementary Product\" border=\"0\">", "Product_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy('Product_Title');
		$table->Finalise();
		$table->DisplayTable();

		echo "<br>";
		$table->DisplayNavigation();
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}