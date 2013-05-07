<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Warehouse.php");

$session->Secure(3);

$fields = 10;

$page = new Page('Product Stock Deduction Control');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'deduct', 'alpha', 5, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

for($i = 0; $i < $fields; $i++) {
	$form->AddField('product_'.$i, 'Product Deduction <strong>'.($i+1).'</strong>', 'text', '', 'numeric_unsigned', 1, 11, false, 'size="3"');
	$form->AddField('quantity_'.$i, 'Quantity Deduction <strong>'.($i+1).'</strong>', 'text', '', 'numeric_unsigned', 1, 9, false, 'size="3"');
}

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()) {
		$stockCount = 0;

		for($i = 0; $i < $fields; $i++) {
			if((strlen($form->GetValue('product_'.$i)) > 0) && (strlen($form->GetValue('quantity_'.$i) > 0))) {
				$data = new DataQuery(sprintf("SELECT p.Product_ID, ws.Is_Writtenoff FROM product AS p INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID INNER JOIN users AS u ON u.Branch_ID=w.Type_Reference_ID WHERE p.Product_ID=%d AND w.`Type`='B' AND u.User_ID=%d", mysql_real_escape_string($form->GetValue('product_'.$i)), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
				if($data->TotalRows == 0) {
					$form->AddError(sprintf('A product with quickfind %s could not be found with stock settings.', $form->GetValue('product_'.$i)), 'product_'.$i);
				}
				while($data->Row){
					if($data->Row['Is_Writtenoff'] == 'Y'){
						$form->AddError(sprintf('A product with quickfind %s has been written off.', $form->GetValue('product_'.$i)), 'product_'.$i);
					}
					$data->Next();
				}
				$data->Disconnect();

				$stockCount++;
			}
		}

		if($form->Valid) {
			for($i = 0; $i < $fields; $i++) {
				if((strlen($form->GetValue('product_'.$i)) > 0) && (strlen($form->GetValue('quantity_'.$i) > 0))) {
					$warehouseFinder = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM warehouse AS w INNER JOIN users AS u ON u.Branch_ID=w.Type_Reference_ID WHERE w.`Type`='B' AND u.User_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

					$warehouse = new Warehouse();
					$warehouse->ID = $warehouseFinder->Row['Warehouse_ID'];
					$warehouse->ChangeQuantity($form->GetValue('product_'.$i), $form->GetValue('quantity_'.$i));

					$warehouseFinder->Disconnect();
				}
			}

			if($stockCount > 0) {
				redirect(sprintf("Location: %s?status=done", $_SERVER['PHP_SELF']));
			}
		}
	}
}

$page->Display('header');

$bubble = new Bubble('Usage Warning' , 'This facility is out dated and required updating.');

echo $bubble->GetHTML();
echo '<br />';

if(!$form->Valid){
	echo $form->GetError();
	echo "<br />";
}

if(isset($_REQUEST['status']) && ($_REQUEST['status'] == 'done')) {
	echo '<p style="color: #00cc00;"><strong>Stock deduction was successfully handled for the specified products.</strong></p>';
}

$window = new StandardWindow("Stock deduction control for products.");
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $window->Open();
echo $window->AddHeader('Click submit once you are done');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow('', '<strong>Example Usage</strong>');
echo $webForm->AddRow('Deduct product #', '<input size="3" type="text" disabled="disabled" name="example" value="160" /> by <input type="text" disabled="disabled" name="example" size="3" value="5" /> x');
echo $webForm->AddRow('', 'The above fields demonstrate the required usage of this control form.<br />Enter the product quickfind code followed by the unsigned integer by<br />which you wish to deduct the stock quantity of this product by.<br />&nbsp;');

for($i = 0; $i < $fields; $i++) {
	echo $webForm->AddRow('['.($i+1).'] Deduct product #', $form->GetHTML('product_'.$i).' by ' . $form->GetHTML('quantity_'.$i) . 'x');
}

echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');
?>