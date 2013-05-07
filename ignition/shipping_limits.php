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

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingLimit.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('postage', 'Postage', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('postage', '', '');

	$data = new DataQuery("SELECT Postage_ID, Postage_Title FROM postage ORDER BY Postage_Title ASC");
	while($data->Row) {
		$form->AddOption('postage', $data->Row['Postage_ID'], $data->Row['Postage_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('geozone', 'Zone', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('geozone', '', '');

	$data = new DataQuery("SELECT Geozone_ID, Geozone_Title FROM geozone ORDER BY Geozone_Title ASC");
	while($data->Row){
		$form->AddOption('geozone', $data->Row['Geozone_ID'], $data->Row['Geozone_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('weight', 'Weight Limit (Kg)', 'text', '0.00', 'float', 1, 11);
	$form->AddField('message', 'Message', 'textarea', '', 'anything', 0, 2048, false, 'rows="5" style="width: 300px; font-family: arial, sans-serif;"');
	$form->AddField('isprevented', 'Is Shipping Prevented', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$limit = new ShippingLimit();
			$limit->Postage->ID = $form->GetValue('postage');
			$limit->Geozone->ID = $form->GetValue('geozone');
			$limit->Weight = $form->GetValue('weight');
			$limit->Message = $form->GetValue('message');
			$limit->IsShippingPrevented = $form->GetValue('isprevented');
			$limit->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="shipping_limits.php">Shipping Limits</a> &gt; Add Limit', '');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add Shipping Limit.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');

	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('postage'), $form->GetHTML('postage') . $form->GetIcon('postage'));
	echo $webForm->AddRow($form->GetLabel('geozone'), $form->GetHTML('geozone') . $form->GetIcon('geozone'));
	echo $webForm->AddRow($form->GetLabel('weight'), $form->GetHTML('weight') . $form->GetIcon('weight'));
	echo $webForm->AddRow($form->GetLabel('message'), $form->GetHTML('message') . $form->GetIcon('message'));
	echo $webForm->AddRow($form->GetLabel('isprevented'), $form->GetHTML('isprevented') . $form->GetIcon('isprevented'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingLimit.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

	$limit = new ShippingLimit($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Shipping Limit ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('postage', 'Postage', 'select', $limit->Postage->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('postage', '', '');

	$data = new DataQuery("SELECT Postage_ID, Postage_Title FROM postage ORDER BY Postage_Title ASC");
	while($data->Row) {
		$form->AddOption('postage', $data->Row['Postage_ID'], $data->Row['Postage_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('geozone', 'Zone', 'select', $limit->Geozone->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('geozone', '', '');

	$data = new DataQuery("SELECT Geozone_ID, Geozone_Title FROM geozone ORDER BY Geozone_Title ASC");
	while($data->Row){
		$form->AddOption('geozone', $data->Row['Geozone_ID'], $data->Row['Geozone_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('weight', 'Weight Limit (Kg)', 'text', $limit->Weight, 'float', 1, 11);
	$form->AddField('message', 'Message', 'textarea', $limit->Message, 'anything', 0, 2048, false, 'rows="5" style="width: 300px; font-family: arial, sans-serif;"');
	$form->AddField('isprevented', 'Is Shipping Prevented', 'checkbox', $limit->IsShippingPrevented, 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$limit->Postage->ID = $form->GetValue('postage');
			$limit->Geozone->ID = $form->GetValue('geozone');
			$limit->Weight = $form->GetValue('weight');
			$limit->Message = $form->GetValue('message');
			$limit->IsShippingPrevented = $form->GetValue('isprevented');
			$limit->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="shipping_limits.php">Shipping Limits</a> &gt; Update Limit', '');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Edit Shipping Limit.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('postage'), $form->GetHTML('postage') . $form->GetIcon('postage'));
	echo $webForm->AddRow($form->GetLabel('geozone'), $form->GetHTML('geozone') . $form->GetIcon('geozone'));
	echo $webForm->AddRow($form->GetLabel('weight'), $form->GetHTML('weight') . $form->GetIcon('weight'));
	echo $webForm->AddRow($form->GetLabel('message'), $form->GetHTML('message') . $form->GetIcon('message'));
	echo $webForm->AddRow($form->GetLabel('isprevented'), $form->GetHTML('isprevented') . $form->GetIcon('isprevented'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingLimit.php');

	if(isset($_REQUEST['id'])) {
		$limit = new ShippingLimit();
		$limit->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: shipping_limits.php"));
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Shipping Limits');
	$page->Display('header');

	$table = new DataTable('limits');
	$table->SetSQL(sprintf("SELECT sl.Shipping_Limit_ID, p.Postage_Title, p.Postage_Days, g.Geozone_Title, sl.Weight, sl.Is_Shipping_Prevented FROM shipping_limit AS sl LEFT JOIN geozone AS g ON g.Geozone_ID=sl.Geozone_ID LEFT JOIN postage AS p ON p.Postage_ID=sl.Postage_ID"));
	$table->AddField('Postage', 'Postage_Title', 'left');
	$table->AddField('Shipping To', 'Geozone_Title', 'left');
	$table->AddField('Weight Limit (Kg)', 'Weight', 'right');
	$table->AddField('Prevents Shipping', 'Is_Shipping_Prevented', 'center');
	$table->AddLink("shipping_limits.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Shipping_Limit_ID");
	$table->AddLink("javascript:confirmRequest('shipping_limits.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Shipping_Limit_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Postage_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add new limit" class="btn" onclick="window.location.href = \'shipping_limits.php?action=add\'" />');

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>