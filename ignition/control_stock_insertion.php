<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');

$session->Secure(3);

$fields = 10;

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('warehouse', 'Warehouse', 'select', '', 'numeric_unsigned', 1, 11);
$form->AddOption('warehouse', '', '');
$form->AddGroup('warehouse', 'B', 'Branches');
$form->AddGroup('warehouse', 'S', 'Suppliers');

$data = new DataQuery("SELECT Warehouse_ID, Warehouse_Name, Type FROM warehouse ORDER BY Warehouse_Name ASC");
while ($data->Row) {
	$form->AddOption('warehouse', $data->Row['Warehouse_ID'], $data->Row['Warehouse_Name'], $data->Row['Type']);
	
	$data->Next();
}
$data->Disconnect();

$form->AddField('location', 'Location', 'text', '', 'anything', 0, 30, false);

for($i=0; $i<$fields; $i++) {
	$form->AddField('product_'.$i, 'Product ('.($i+1).')', 'text', '', 'numeric_unsigned', 1, 11, false, 'size="5"');
	$form->AddField('quantity_'.$i, 'Quantity ('.($i+1).')', 'text', '', 'numeric_unsigned', 1, 11, false, 'size="5"');
	$form->AddField('cost_'.$i, 'Cost ('.($i+1).')', 'text', '', 'float', 1, 11, false, 'size="5"');
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		for($i=0; $i<$fields; $i++) {
			if((strlen($form->GetValue('product_'.$i)) > 0) && (strlen($form->GetValue('quantity_'.$i) > 0))) {
				$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product WHERE Product_ID=%d", mysql_real_escape_string($form->GetValue('product_'.$i))));
				if($data->Row['Count'] == 0) {
					$form->AddError(sprintf('Product quickfind \'%s\' could not be found.', $form->GetValue('product_'.$i)), 'product_'.$i);
				}
				$data->Disconnect();
			}
		}

		if($form->Valid) {
			for($i=0; $i<$fields; $i++) {
				if((strlen($form->GetValue('product_'.$i)) > 0) && (strlen($form->GetValue('quantity_'.$i) > 0))) {
					$cost = $form->GetValue('cost_'.$i);
					
					if(empty($cost)) {
						$data = new DataQuery(sprintf("SELECT MIN(Cost) AS Cost FROM supplier_product WHERE Cost>0 AND Product_ID=%d", mysql_real_escape_string($form->GetValue('product_'.$i))));
						if($data->TotalRows > 0) {
							$cost = $data->Row['Cost'];	
						}
						$data->Disconnect();
					}
					
					$stock = new WarehouseStock();
					$stock->Product->ID = $form->GetValue('product_'.$i);
					$stock->Warehouse->ID = $form->GetValue('warehouse');
					$stock->Location = $form->GetValue('location');
					$stock->QuantityInStock = $form->GetValue('quantity_'.$i);
					$stock->Cost = $cost;
					$stock->Add();
				}
			}

			redirect(sprintf("Location: %s?status=complete", $_SERVER['PHP_SELF']));
		}
	}
}

$page = new Page('Product Stock Insertion Control', 'Create a new stock entry for an existing product for your warehouse.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

if(isset($_REQUEST['status'])) {
	$bubble = new Bubble('Success', 'Stock insertion was successfully handled for the specified products.');
	
	echo $bubble->GetHTML();
	echo '<br />';
}

$window = new StandardWindow("Create new stock for an existing product.");
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('warehouse'), $form->GetHTML('warehouse') . $form->GetIcon('warehouse'));
echo $webForm->AddRow($form->GetLabel('location'), $form->GetHTML('location') . $form->GetIcon('location'));
echo $webForm->Close();
echo $window->CloseContent();

echo $window->AddHeader('Enter your product stock quantities');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow('', '<strong><div style="float: left; width: 100px;">Product</div><div style="float: left; width: 100px;">Quantity</div><div style="float: left; width: 100px;">Cost</div></strong>');

for($i=0; $i<$fields; $i++) {
	$html = '<div style="float: left; width: 100px;">'.$form->GetHTML('product_'.$i).'</div>';
	$html .= '<div style="float: left; width: 100px;">'.$form->GetHTML('quantity_'.$i).'</div>';
	$html .= '<div style="float: left; width: 100px;">'.$form->GetHTML('cost_'.$i).'</div>';
	
	echo $webForm->AddRow($i + 1, $html);
}

echo $webForm->AddRow('', '<input type="submit" name="add" value="add" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');