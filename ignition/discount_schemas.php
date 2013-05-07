<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
}else {
	$session->Secure(2);
	view();
	exit;
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$discount = new DiscountSchema;

	$form = new Form("discount_schemas.php");
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('reference', 'Discount Schema Reference', 'text', '', 'alpha_numeric', 1, 15, false);
	$form->AddField('title', 'Discount Schema Title', 'text', '', 'paragraph', 1, 45, true);
	$form->AddField('description', 'Discount Schema Description', 'textarea', '', 'paragraph', 1, 255, true, 'style="width:90%;height:50px;"');
	$form->AddField('discount', 'Discount Percentage', 'text', '0', 'float', 1, 11, true);
	$form->AddField('ordersOver', 'Discount On Orders Over', 'text', '0.00', 'float', 1, 11, true);
	$form->AddField('products', 'Product Option', 'radio', 'Y', 'alpha', 1, 1, true);
	$form->AddOption('products', 'Y', 'This Schema applies to all products.');
	$form->AddOption('products', 'B', 'This Schema applies to band');
	$form->AddOption('products', 'N', 'I would like this schema to apply to specific products.');

	$form->AddField('band', 'Product Band', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('band', 0, 'Select...');
	$bands = new DataQuery('SELECT * FROM product_band ORDER BY Product_Band_ID ASC');
	while($bands->Row) {
		$form->AddOption('band', $bands->Row['Product_Band_ID'], $bands->Row['Band_Ref']. ' - ' . $bands->Row['Band_Title']);
		$bands->Next();
	}
	$bands->Disconnect();
	unset($bands);

	$form->AddField('active', 'Activate Discount Schema', 'checkbox', 'Y', 'boolean', NULL, NULL, false);
	$form->AddField('markup', 'Discount From Markup', 'checkbox', 'N', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		$form->Validate();

		$discount->Reference = strtoupper($form->GetValue('reference'));
		$discount->Name = $form->GetValue('title');
		$discount->Description = $form->GetValue('description');
		$discount->Discount = $form->GetValue('discount');
		$discount->IsFixed = 'N';
		$discount->OrdersOver = $form->GetValue('ordersOver');
		$discount->IsAllProducts = $form->GetValue('products');
		$discount->IsActive = $form->GetValue('active');
		$discount->UseBand = $form->GetValue('band');
		$discount->IsOnMarkup = $form->GetValue('markup');

		if($discount->IsAllProducts == 'B' && empty($discount->UseBand)) {
			$form->AddError('You must select a band from the drop down list.', 'band');
		}

		if($discount->Exists()){
			$form->AddError('The Discount Reference Already exists. Please try a different Reference.', 'reference');
		}

		if($form->Valid){
			$discount->Add();
			redirect(sprintf("Location: discount_schema_settings.php?schema=%d", $discount->ID));
		}
	}

	$page = new Page('Add Discount Schema','This schema will be available to specific customers. You can apply discounts to customer accounts within contact profiles.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference') . $form->GetIcon('reference'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('discount'), $form->GetHTML('discount') . '% '  . $form->GetIcon('discount'));
	echo $webForm->AddRow($form->GetLabel('ordersOver'), $form->GetHTML('ordersOver') . $form->GetIcon('ordersOver'));
	echo $webForm->AddRow($form->GetLabel('products'), '');
	echo $webForm->AddRow($form->GetHTML('products', 1), $form->GetLabel('products', 1));
	echo $webForm->AddRow($form->GetHTML('products', 2), $form->GetLabel('products', 2) . $form->GetHTML('band'));
	echo $webForm->AddRow($form->GetHTML('products', 3), $form->GetLabel('products', 3));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow($form->GetLabel('markup'), $form->GetHTML('markup') . $form->GetIcon('markup'));
	echo $webForm->AddRow("", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'discount_schemas.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();


	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$discount = new DiscountSchema($_REQUEST['schema']);

	$form = new Form("discount_schemas.php");
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('schema', 'Discount Schema ID', 'hidden', $discount->ID, 'numeric_unsigned', 1, 3, true);
	$form->AddField('reference', 'Discount Schema Reference', 'text', $discount->Reference, 'alpha_numeric', 1, 15, true);
	$form->AddField('title', 'Discount Schema Title', 'text', $discount->Name, 'paragraph', 1, 45, true);
	$form->AddField('description', 'Discount Schema Description', 'textarea', $discount->Description, 'paragraph', 1, 255, true, 'style="width:90%;height:50px;"');
	$form->AddField('discount', 'Discount Percentage', 'text', $discount->Discount, 'float', 1, 11, true);
	$form->AddField('ordersOver', 'Discount On Orders Over', 'text', $discount->OrdersOver, 'float', 1, 11, true);
	$form->AddField('products', 'Product Option', 'radio', $discount->IsAllProducts, 'alpha', 1, 1, true);
	$form->AddOption('products', 'Y', 'This Coupon applies to all products.');
	$form->AddOption('products', 'B', 'This Schema applies to band');
	$form->AddOption('products', 'N', 'I would like this coupon to apply to specific products.');
	$form->AddField('active', 'Activate Coupon', 'checkbox', $discount->IsActive, 'boolean', NULL, NULL, false);
	$form->AddField('markup', 'Discount From Markup', 'checkbox', $discount->IsOnMarkup, 'boolean', NULL, NULL, false);

	$form->AddField('band', 'Product Band', 'select', $discount->UseBand, 'numeric_unsigned', 1, 11);
	$form->AddOption('band', 0, 'Select...');
	$bands = new DataQuery('SELECT * FROM product_band ORDER BY Product_Band_ID ASC');
	while($bands->Row) {
		$form->AddOption('band', $bands->Row['Product_Band_ID'], $bands->Row['Band_Ref']. ' - ' . $bands->Row['Band_Title']);
		$bands->Next();
	}
	$bands->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		$form->Validate();

		$discount->Reference = strtoupper($form->GetValue('reference'));
		$discount->Name = $form->GetValue('title');
		$discount->Description = $form->GetValue('description');
		$discount->Discount = $form->GetValue('discount');
		$discount->IsFixed = 'N';
		$discount->OrdersOver = $form->GetValue('ordersOver');
		$discount->IsAllProducts = $form->GetValue('products');
		$discount->IsActive = $form->GetValue('active');
		$discount->IsOnMarkup = $form->GetValue('markup');
		$discount->UseBand = $form->GetValue('band');

		if($discount->IsAllProducts == 'B' && empty($discount->UseBand)) {
			$form->AddError('You must select a band from the drop down list.', 'band');
		}

		if($form->Valid){
			$discount->Update();
			redirect("Location: discount_schemas.php");
			exit;
		}
	}

	$page = new Page('Edit Discount Schema','This schema will be available to specific customers.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Update');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('schema');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference') . $form->GetIcon('reference'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('discount'), $form->GetHTML('discount') . '% ' . $form->GetIcon('discount'));
	echo $webForm->AddRow($form->GetLabel('ordersOver'), $form->GetHTML('ordersOver') . $form->GetIcon('ordersOver'));
	echo $webForm->AddRow($form->GetLabel('products'), '');
	echo $webForm->AddRow($form->GetHTML('products', 1), $form->GetLabel('products', 1));
	echo $webForm->AddRow($form->GetHTML('products', 2), $form->GetLabel('products', 2) . $form->GetHTML('band'));
	echo $webForm->AddRow($form->GetHTML('products', 3), $form->GetLabel('products', 3));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow($form->GetLabel('markup'), $form->GetHTML('markup') . $form->GetIcon('markup'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'discount_schemas.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	$discount = new DiscountSchema;
	$discount->Delete($_REQUEST['schema']);
	redirect("Location: discount_schemas.php");
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Discount Schema Settings','Give customers a reason to continue to purchase with you. Give them a discount!');
	$page->Display('header');

	echo "<br>";
	echo '<input type="button" name="add" value="add a new schema" class="btn" onclick="window.location.href=\'discount_schemas.php?action=add\'">';
	echo "<br><br>";

	$table = new DataTable('discounts');
	$table->SetSQL("select * from discount_schema");
	$table->AddField('ID#', 'Discount_Schema_ID', 'right');
	$table->AddField('Reference', 'Discount_Ref', 'left');
	$table->AddField('Name', 'Discount_Title', 'left');
	$table->AddField('Orders Over', 'Orders_Over', 'right');
	$table->AddField('Discount %', 'Discount_Amount', 'right');
	$table->AddField('Active', 'Is_Active', 'center');
	$table->AddField('Discount Markup', 'Is_On_Markup', 'center');
	$table->AddLink("discount_schema_settings.php?schema=%s",
	"<img src=\"./images/folderopen.gif\" alt=\"Open Schema\" border=\"0\">",
	"Discount_Schema_ID");
	$table->AddLink("discount_schemas.php?action=update&schema=%s",
	"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Schema\" border=\"0\">",
	"Discount_Schema_ID");
	$table->AddLink("javascript:confirmRequest('discount_schemas.php?action=remove&schema=%s','Are you sure you want to remove this item?');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Discount_Schema_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Discount_Schema_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add a new schema" class="btn" onclick="window.location.href=\'discount_schemas.php?action=add\'">';
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}