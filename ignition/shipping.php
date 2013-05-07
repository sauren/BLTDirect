<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingClass.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Shipping.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

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
} elseif($action == "removezone"){
	$session->Secure(3);
	removezone();
	exit;
} elseif($action == "removeall"){
	$session->Secure(3);
	removeall();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function add() {
	$class = new ShippingClass($_REQUEST['class']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('class', 'Shipping Class ID', 'hidden', $class->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('geozonefilter', 'Zone', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('postage', 'Postage (e.g. Next Day Delivery)', 'select', '', 'numeric_unsigned', 1, 11);

	$postage = new DataQuery("select * from postage order by Postage_Title asc");
	while($postage->Row){
		$form->AddOption('postage', $postage->Row['Postage_ID'], $postage->Row['Postage_Title']);
		$postage->Next();
	}
	$postage->Disconnect();

	$form->AddField('geozone', 'Zone', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('geozone', '', '[Select Zone]');

	$zones = new DataQuery("select * from geozone order by Geozone_Title asc");
	while($zones->Row){
		$form->AddOption('geozone', $zones->Row['Geozone_ID'], $zones->Row['Geozone_Title']);
		$zones->Next();
	}
	$zones->Disconnect();

	$form->AddField('ordersOver', 'For Orders Over', 'text', '0.00', 'float', 1, 11);
	$form->AddField('weight', 'Weight Threshold (Kg)', 'text', '0.00', 'float', 1, 11);
	$form->AddField('perItem', 'Cost Per Item', 'text', '0.00', 'float', 1, 11);
	$form->AddField('perDelivery', 'Cost Per Delivery', 'text', '0.00', 'float', 1, 11);
	$form->AddField('perKilo', 'Cost per Additional Kilo', 'text', '0.00', 'float', 1, 11);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$ship = new Shipping;
			$ship->Postage->ID = $form->GetValue('postage');
			$ship->ClassID = $form->GetValue('class');
			$ship->Geozone->ID = $form->GetValue('geozone');
			$ship->PerItem = $form->GetValue('perItem');
			$ship->PerDelivery = $form->GetValue('perDelivery');
			$ship->PerAdditionalKilo = $form->GetValue('perKilo');
			$ship->OverOrderAmount = $form->GetValue('ordersOver');
			$ship->WeightThreshold = $form->GetValue('weight');
			$ship->Add();

			redirect(sprintf("Location: shipping.php?class=%d&geozonefilter=%d", $class->ID, $form->GetValue('geozonefilter')));
		}
	}

	$page = new Page(sprintf('<a href="shipping_classes.php">Shipping Classes</a> &gt; <a href="shipping.php?class=%d">%s</a> &gt; Add Shipping', $class->ID, $class->Name),'');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add Shipping.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('class');
	echo $form->GetHTML('geozonefilter');
	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('postage'), $form->GetHTML('postage') . $form->GetIcon('postage'));
	echo $webForm->AddRow($form->GetLabel('geozone'), $form->GetHTML('geozone') . $form->GetIcon('geozone'));
	echo $webForm->AddRow($form->GetLabel('ordersOver'), $form->GetHTML('ordersOver') . $form->GetIcon('ordersOver'));
	echo $webForm->AddRow($form->GetLabel('weight'), $form->GetHTML('weight') . $form->GetIcon('weight'));
	echo $webForm->AddRow($form->GetLabel('perItem'), $form->GetHTML('perItem') . $form->GetIcon('perItem'));
	echo $webForm->AddRow($form->GetLabel('perDelivery'), $form->GetHTML('perDelivery') . $form->GetIcon('perDelivery'));
	echo $webForm->AddRow($form->GetLabel('perKilo'), $form->GetHTML('perKilo') . $form->GetIcon('perKilo'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'shipping.php?class=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $class->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	$class = new ShippingClass($_REQUEST['class']);
	$ship = new Shipping($_REQUEST['ship']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('class', 'Shipping Class ID', 'hidden', $class->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('geozonefilter', 'Zone', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('ship', 'Shipping ID', 'hidden', $ship->ID, 'numeric_unsigned', 1, 11);

	$form->AddField('postage', 'Postage (e.g. Next Day Delivery)', 'select', $ship->Postage->ID, 'numeric_unsigned', 1, 11);
	$postage = new DataQuery("select * from postage order by Postage_Title asc");
	while($postage->Row){
		$form->AddOption('postage', $postage->Row['Postage_ID'], $postage->Row['Postage_Title']);
		$postage->Next();
	}
	$postage->Disconnect();

	$form->AddField('geozone', 'Zone', 'select', $ship->Geozone->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('geozone', '', '[Select Zone]');

	$zones = new DataQuery("select * from geozone order by Geozone_Title asc");
	while($zones->Row){
		$form->AddOption('geozone', $zones->Row['Geozone_ID'], $zones->Row['Geozone_Title']);
		$zones->Next();
	}
	$zones->Disconnect();

	$form->AddField('ordersOver', 'For Orders Over', 'text', $ship->OverOrderAmount, 'float', 1, 11);
	$form->AddField('weight', 'Weight Threshold (Kg)', 'text', $ship->WeightThreshold, 'float', 1, 11);
	$form->AddField('perItem', 'Cost Per Item', 'text', $ship->PerItem, 'float', 1, 11);
	$form->AddField('perDelivery', 'Cost Per Delivery', 'text', $ship->PerDelivery, 'float', 1, 11);
	$form->AddField('perKilo', 'Cost per Additional Kilo', 'text', $ship->PerAdditionalKilo, 'float', 1, 11);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$ship->Postage->ID = $form->GetValue('postage');
			$ship->ClassID = $form->GetValue('class');
			$ship->Geozone->ID = $form->GetValue('geozone');
			$ship->PerItem = $form->GetValue('perItem');
			$ship->PerDelivery = $form->GetValue('perDelivery');
			$ship->PerAdditionalKilo = $form->GetValue('perKilo');
			$ship->OverOrderAmount = $form->GetValue('ordersOver');
			$ship->WeightThreshold = $form->GetValue('weight');
			$ship->Update();

			redirect(sprintf("Location: shipping.php?class=%d&geozonefilter=%d", $class->ID, $form->GetValue('geozonefilter')));
		}
	}
	$page = new Page(sprintf('<a href="shipping_classes.php">Shipping Classes</a> &gt; <a href="shipping.php?class=%d">%s</a> &gt; Update Shipping', $class->ID, $class->Name),'');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Update Shipping.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('class');
	echo $form->GetHTML('geozonefilter');
	echo $form->GetHTML('ship');

	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('postage'), $form->GetHTML('postage') . $form->GetIcon('postage'));
	echo $webForm->AddRow($form->GetLabel('geozone'), $form->GetHTML('geozone') . $form->GetIcon('geozone'));
	echo $webForm->AddRow($form->GetLabel('ordersOver'), $form->GetHTML('ordersOver') . $form->GetIcon('ordersOver'));
	echo $webForm->AddRow($form->GetLabel('weight'), $form->GetHTML('weight') . $form->GetIcon('weight'));
	echo $webForm->AddRow($form->GetLabel('perItem'), $form->GetHTML('perItem') . $form->GetIcon('perItem'));
	echo $webForm->AddRow($form->GetLabel('perDelivery'), $form->GetHTML('perDelivery') . $form->GetIcon('perDelivery'));
	echo $webForm->AddRow($form->GetLabel('perKilo'), $form->GetHTML('perKilo') . $form->GetIcon('perKilo'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'shipping.php?class=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $class->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove() {
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['class'])){
		$ship = new Shipping();
		$ship->Delete($_REQUEST['ship']);
	}

	redirect(sprintf("Location: shipping.php?class=%d&geozonefilter=%d", $_REQUEST['class'], isset($_REQUEST['geozonefilter']) ? $_REQUEST['geozonefilter'] : 0));
}

function removezone() {
	new DataQuery(sprintf("DELETE FROM shipping WHERE Geozone_ID=%d AND Shipping_Class_ID=%d", mysql_real_escape_string($_REQUEST['zone']), mysql_real_escape_string($_REQUEST['class'])));

    redirect(sprintf("Location: shipping.php?class=%d", $_REQUEST['class']));
}

function removeall() {
	new DataQuery(sprintf("DELETE FROM shipping WHERE Shipping_Class_ID=%d", mysql_real_escape_string($_REQUEST['class'])));

    redirect(sprintf("Location: shipping.php?class=%d", $_REQUEST['class']));
}

function view(){
	$class = new ShippingClass($_REQUEST['class']);

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'view', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('class', 'Shipping Class ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('geozonefilter', 'Zone', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('geozonefilter', '0', '');

	$data = new DataQuery("SELECT Geozone_ID, Geozone_Title FROM geozone ORDER BY Geozone_Title ASC");
	while($data->Row){
		$form->AddOption('geozonefilter', $data->Row['Geozone_ID'], $data->Row['Geozone_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$page = new Page(sprintf('<a href="shipping_classes.php">Shipping Class Settings</a> &gt; %s', $class->Name),'You can create different Shipping Bands to accommodate customers from different locations.');
	$page->Display('header');

	$window = new StandardWindow("Update Shipping.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('class');

	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('geozonefilter'), $form->GetHTML('geozonefilter') . $form->GetIcon('geozonefilter'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="filter" value="filter" class="btn" tabindex="%s" /> <input type="button" name="removezone" value="remove" class="btn" onclick="confirmRequest(\'%s?action=removezone&class=%d&zone=\' + document.getElementById(\'geozonefilter\').value, \'Are you sure you wish to remove all entries for this geozone?\');" /> <input type="button" name="removeall" value="remove all" class="btn" onclick="confirmRequest(\'%s?action=removeall&class=%d\', \'Are you sure you wish to remove all entries?\');" />', $form->GetTabIndex(), $_SERVER['PHP_SELF'], $form->GetValue('class'), $_SERVER['PHP_SELF'], $form->GetValue('class')));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	echo '<br />';

	$table = new DataTable('shipping');
	$table->SetSQL(sprintf("select s.Shipping_ID, p.Postage_Title, p.Postage_Days, g.Geozone_Title, s.Over_Order_Amount,
					s.Weight_Threshold, s.Per_Additional_Kilo,
					s.Per_Item, s.Per_Delivery from shipping as s
					left join geozone as g
					on g.Geozone_ID = s.Geozone_ID
					left join postage as p
					on p.Postage_ID = s.Postage_ID
					where Shipping_Class_ID=%d %s", $class->ID, ($form->GetValue('geozonefilter') > 0) ? sprintf('AND g.Geozone_ID=%d', $form->GetValue('geozonefilter')) : ''));
	$table->AddField('Postage', 'Postage_Title', 'left');
	$table->AddField('Shipping To', 'Geozone_Title', 'left');
	$table->AddField('Orders Over', 'Over_Order_Amount', 'right');
	$table->AddField('Weight Threshold (Kg)', 'Weight_Threshold', 'right');
	$table->AddField('Item', 'Per_Item', 'right');
	$table->AddField('Delivery', 'Per_Delivery', 'right');
	$table->AddField('Kilo', 'Per_Additional_Kilo', 'right');

	$table->AddLink("shipping.php?action=update&ship=%s",
							"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",
							"Shipping_ID");
	$table->AddLink("javascript:confirmRequest('shipping.php?action=remove&confirm=true&ship=%s','Are you sure you want to remove this Shipping Class? Note: this operation will remove all related information and products using this shipping class will no longer have one.');",
							"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
							"Shipping_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Postage_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new shipping band" class="btn" onclick="window.location.href=\'shipping.php?action=add&class=%d&geozonefilter=%d\'">', $class->ID, $form->GetValue('geozonefilter'));

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}