<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderWarehouseNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$session->Secure(2);

$order = new Order($_REQUEST['orderid']);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('warehouse', 'Warehouse', 'select', '0', 'numeric_unsigned', 1, 11, false);
$form->AddOption('warehouse', '0', '');

$data = new DataQuery(sprintf("SELECT * FROM warehouse WHERE Type='S' ORDER BY Warehouse_Name ASC"));
while($data->Row) {
	$form->AddOption('warehouse', $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('type', 'Type', 'select', '0', 'numeric_unsigned', 1, 11, false);
$form->AddOption('type', '0', '');

$data = new DataQuery(sprintf("SELECT * FROM order_warehouse_note_type ORDER BY Name ASC"));
while($data->Row) {
	$form->AddOption('type', $data->Row['Order_Warehouse_Note_Type_ID'], $data->Row['Name']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('note', 'Note', 'textarea', '', 'anything', 1, 2000, false, 'style="width:100%; height:200px"');

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if($form->Validate()) {
		if($form->GetValue('warehouse') > 0) {
			$form->InputFields['type']->Required = true;
			$form->InputFields['note']->Required = true;

			$form->Validate('type');
			$form->Validate('note');

			if($form->Valid) {
				$note = new OrderWarehouseNote();
				$note->Type->ID = $form->GetValue('type');
				$note->Order->ID = $form->GetValue('orderid');
				$note->Warehouse->ID = $form->GetValue('warehouse');
				$note->IsAlert = 'Y';
				$note->Note = $form->GetValue('note');
				$note->Add();
			}
		}

		if($form->Valid) {
			$order->IsWarehouseDeclined = 'N';
			$order->IsWarehouseUndeclined = 'Y';
			$order->IsWarehouseDeclinedRead = 'N';
			$order->Update();

			redirect(sprintf("Location: order_details.php?orderid=%d", $order->ID));
		}
	}
}

$page = new Page(sprintf('<a href="order_details.php?orderid=%s">Order</a> &gt; Undecline Order', $order->ID), 'Undecline this order so that it may be despatched.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Undecline order');
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('orderid');

echo $window->Open();
echo $window->AddHeader('Add an optional note for a warehouse.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('warehouse'), $form->GetHTML('warehouse') . $form->GetIcon('warehouse'));
echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
echo $webForm->AddRow($form->GetLabel('note'), $form->GetHTML('note') . $form->GetIcon('note'));
echo $webForm->Close();
echo $window->CloseContent();

echo $window->AddHeader('Undecline this order.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow("&nbsp;", sprintf('<input type="submit" name="undecline" value="undecline" class="btn" tabindex="%s">', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>