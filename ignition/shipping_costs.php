<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingClass.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingCost.php');
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
}else {
	$session->Secure(2);
	view();
	exit;
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('class', 'Shipping Class', 'selectmultiple', '0', 'numeric_unsigned', 1, 11, false);

	$data = new DataQuery("SELECT * FROM shipping_class ORDER BY Shipping_Class_Title ASC");
	while($data->Row){
		$form->AddOption('class', $data->Row['Shipping_Class_ID'], $data->Row['Shipping_Class_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('postage', 'Postage', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('postage', '', '');

	$postage = new DataQuery("select * from postage order by Postage_Title asc");
	while($postage->Row){
		$form->AddOption('postage', $postage->Row['Postage_ID'], $postage->Row['Postage_Title']);
		$postage->Next();
	}
	$postage->Disconnect();

	$form->AddField('geozone', 'Zone', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('geozone', '', '');

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
		if($form->Validate()) {
            $ship = new ShippingCost();
			$ship->Postage->ID = $form->GetValue('postage');
			$ship->Geozone->ID = $form->GetValue('geozone');
			$ship->PerItem = $form->GetValue('perItem');
			$ship->PerDelivery = $form->GetValue('perDelivery');
			$ship->PerAdditionalKilo = $form->GetValue('perKilo');
			$ship->OverOrderAmount = $form->GetValue('ordersOver');
			$ship->WeightThreshold = $form->GetValue('weight');

			$classes = $form->GetValue('class');

			foreach($classes as $class) {
				$ship->ClassID = $class;
				$ship->Add();
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Shipping Costs</a> &gt; Add Shipping Setting', $_SERVER['PHP_SELF']), '');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add Shipping Setting");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');

	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('postage'), $form->GetHTML('postage') . $form->GetIcon('postage'));
	echo $webForm->AddRow($form->GetLabel('class'), $form->GetHTML('class') . $form->GetIcon('class'));
	echo $webForm->AddRow($form->GetLabel('geozone'), $form->GetHTML('geozone') . $form->GetIcon('geozone'));
	echo $webForm->AddRow($form->GetLabel('ordersOver'), $form->GetHTML('ordersOver') . $form->GetIcon('ordersOver'));
	echo $webForm->AddRow($form->GetLabel('weight'), $form->GetHTML('weight') . $form->GetIcon('weight'));
	echo $webForm->AddRow($form->GetLabel('perItem'), $form->GetHTML('perItem') . $form->GetIcon('perItem'));
	echo $webForm->AddRow($form->GetLabel('perDelivery'), $form->GetHTML('perDelivery') . $form->GetIcon('perDelivery'));
	echo $webForm->AddRow($form->GetLabel('perKilo'), $form->GetHTML('perKilo') . $form->GetIcon('perKilo'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	$ship = new ShippingCost($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('class', 'Shipping Class', 'select', $ship->ClassID, 'numeric_unsigned', 1, 11, false);
	$form->AddOption('class', '0', '');

	$data = new DataQuery("SELECT * FROM shipping_class ORDER BY Shipping_Class_Title ASC");
	while($data->Row){
		$form->AddOption('class', $data->Row['Shipping_Class_ID'], $data->Row['Shipping_Class_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('postage', 'Postage', 'select', $ship->Postage->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('postage', '', '');

	$postage = new DataQuery("select * from postage order by Postage_Title asc");
	while($postage->Row){
		$form->AddOption('postage', $postage->Row['Postage_ID'], $postage->Row['Postage_Title']);
		$postage->Next();
	}
	$postage->Disconnect();

	$form->AddField('geozone', 'Zone', 'select', $ship->Geozone->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('geozone', '', '');

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

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page(sprintf('<a href="%s">Shipping Costs</a> &gt; Update Shipping Setting', $_SERVER['PHP_SELF']), '');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update Shipping Setting");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('postage'), $form->GetHTML('postage') . $form->GetIcon('postage'));
	echo $webForm->AddRow($form->GetLabel('class'), $form->GetHTML('class') . $form->GetIcon('class'));
	echo $webForm->AddRow($form->GetLabel('geozone'), $form->GetHTML('geozone') . $form->GetIcon('geozone'));
	echo $webForm->AddRow($form->GetLabel('ordersOver'), $form->GetHTML('ordersOver') . $form->GetIcon('ordersOver'));
	echo $webForm->AddRow($form->GetLabel('weight'), $form->GetHTML('weight') . $form->GetIcon('weight'));
	echo $webForm->AddRow($form->GetLabel('perItem'), $form->GetHTML('perItem') . $form->GetIcon('perItem'));
	echo $webForm->AddRow($form->GetLabel('perDelivery'), $form->GetHTML('perDelivery') . $form->GetIcon('perDelivery'));
	echo $webForm->AddRow($form->GetLabel('perKilo'), $form->GetHTML('perKilo') . $form->GetIcon('perKilo'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$ship = new ShippingCost();
		$ship->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->EncType = '';
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$settings = array();

			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/([\w]*)_([\d]*)/', $key, $matches)) {
					if(!isset($settings[$matches[2]])) {
						$settings[$matches[2]] = new ShippingCost($matches[2]);
					}

					switch(strtolower($matches[1])) {
						case 'orders':
							$settings[$matches[2]]->OverOrderAmount = $value;
							break;

						case 'weight':
							$settings[$matches[2]]->WeightThreshold = $value;
							break;

						case 'item':
							$settings[$matches[2]]->PerItem = $value;
							break;

						case 'delivery':
							$settings[$matches[2]]->PerDelivery = $value;
							break;

						case 'kilo':
							$settings[$matches[2]]->PerAdditionalKilo = $value;
							break;
					}
				}
			}

			foreach($settings as $shippingCost) {
				$shippingCost->Update();
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('Shipping Costs', 'Create different shipping settings for calculating shipping costs.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('confirm');

	$table = new DataTable('shipping');
	$table->SetSQL(sprintf("SELECT sc.Shipping_Class_Title, s.Shipping_Cost_ID, po.Postage_Title, po.Postage_Days, g.Geozone_Title, s.Over_Order_Amount, s.Weight_Threshold, s.Per_Additional_Kilo, s.Per_Item, s.Per_Delivery FROM shipping_cost AS s LEFT JOIN shipping_class AS sc ON s.Shipping_Class_ID=sc.Shipping_Class_ID LEFT JOIN geozone AS g ON g.Geozone_ID=s.Geozone_ID LEFT JOIN postage AS po ON po.Postage_ID=s.Postage_ID"));
	$table->AddField('Postage', 'Postage_Title', 'left');
	$table->AddField('Class', 'Shipping_Class_Title', 'left');
	$table->AddField('Shipping To', 'Geozone_Title', 'left');
	$table->AddInput('Orders Over', 'Y', 'Over_Order_Amount', 'orders', 'Shipping_Cost_ID', 'text', 'size="3"');
	$table->AddInput('Weight Threshold (Kg)', 'Y', 'Weight_Threshold', 'weight', 'Shipping_Cost_ID', 'text', 'size="3"');
	$table->AddInput('Item', 'Y', 'Per_Item', 'item', 'Shipping_Cost_ID', 'text', 'size="3"');
	$table->AddInput('Delivery', 'Y', 'Per_Delivery', 'delivery', 'Shipping_Cost_ID', 'text', 'size="3"');
	$table->AddInput('Kilo', 'Y', 'Per_Additional_Kilo', 'kilo', 'Shipping_Cost_ID', 'text', 'size="3"');
	$table->AddLink("?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Shipping_Cost_ID");
	$table->AddLink("?action=remove&id=%s", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Shipping_Cost_ID");
	$table->SetMaxRows(500);
	$table->SetOrderBy("Postage_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="submit" name="update" value="update" class="btn" /> ');
	echo sprintf('<input type="button" name="add" value="add new shipping cost" class="btn" onclick="window.location.href=\'?action=add\';" /> ');

	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}