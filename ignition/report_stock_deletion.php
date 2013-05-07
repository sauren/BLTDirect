<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/DataTable.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/Product.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/Form.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/StandardWindow.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/StandardForm.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'report') {
	$session->Secure(3);
	report();
	exit;
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'Y', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			redirect(sprintf("Location: %s?action=report&cat=%d&sub=%s", $_SERVER['PHP_SELF'], $form->GetValue('parent'), $form->GetValue('subfolders')));
		}
	}

	$page = new Page('Stock Deletion Report');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Select category");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow($form->GetLabel('subfolders'), $form->GetHtml('subfolders'));
	echo $webForm->AddRow('','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove() {
	$form = new Form($_SERVER['PHP_SELF']);
	$sub = ($form->GetValue('sub') == 'Y') ? true : false;
	$cat = $form->GetValue('cat');
	
	if(isset($_REQUEST['pid'])) {
		$warehouses = array();

		$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type='B'"));
		while($data->Row) {
			$warehouses[] = $data->Row['Warehouse_ID'];

			$data->Next();
		}
		$data->Disconnect();

		if(count($warehouses) > 0) {
			$data = new DataQuery(sprintf("SELECT Stock_ID, Is_Writtenoff
FROM warehouse_stock
WHERE Product_ID=%d AND (Warehouse_ID=%s)", mysql_real_escape_string($_REQUEST['pid']), mysql_real_escape_string(implode(' OR Warehouse_ID=', $warehouses))));
			if($data->Row['Is_Writtenoff'] == 'Y'){
				echo 'This stock has been written off, and cannot be deleted.<br />';
				echo '<input type="button" type="submit" value="return" class="btn" onclick="window.location.href=\'report_stock_deletion.php?action=report">';
				exit;
			} else {
				new DataQuery(sprintf("DELETE FROM warehouse_stock WHERE Stock_ID=%d", mysql_real_escape_string($data->Row['Stock_ID'])));
			}
		}

		$product = new Product();

		if($product->Get($_REQUEST['pid'])) {
			$product->Stocked = 'N';
			$product->Update();
		}
	}

	redirect(sprintf('Location: %s?action=report&cat=%d&sub=Y', $_SERVER['PHP_SELF'], $form->GetValue('cat')));
}

function report() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cat', 'Category ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Include Sub Categories', 'hidden', 'Y', 'boolean', 1, 1, false);

	$sub = ($form->GetValue('sub') == 'Y') ? true : false;
	$cat = $form->GetValue('cat');

	$clientString = '';

	if($cat != 0) {
		if($sub) {
			$clientString = sprintf("AND (c.Category_ID=%d %s)", $cat, GetChildIDS($cat));
		} else {
			$clientString = sprintf("AND (c.Category_ID=%d)", $cat);
		}
	} else {
		if(!$sub) {
			$clientString = sprintf("AND (c.Category_ID=%d)", $cat);
		}
	}

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$warehouses = array();

			$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type='B'"));
			while($data->Row) {
				$warehouses[] = $data->Row['Warehouse_ID'];

				$data->Next();
			}
			$data->Disconnect();

			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/delete_([0-9]*)/', $key, $matches)) {
					$deleted = false;
					if(count($warehouses) > 0) {
						$data = new DataQuery(sprintf("SELECT Stock_ID, Is_Writtenoff
							FROM warehouse_stock
							WHERE Product_ID=%d AND (Warehouse_ID=%s)", mysql_real_escape_string($matches[1]), mysql_real_escape_string(implode(' OR Warehouse_ID=', $warehouses))));
						if($data->Row['Is_Writtenoff'] != 'Y'){
							new DataQuery(sprintf("DELETE FROM warehouse_stock WHERE Stock_ID=%d", mysql_real_escape_string($data->Row['Stock_ID'])));
							$deleted = true;
						}
					}

					$product = new Product();

					if($product->Get($matches[1]) && $deleted) {
						$product->Stocked = 'N';
						$product->Update();
					}
				}
			}

			redirect(sprintf('Location: %s?action=report&cat=%d&sub=%s', $_SERVER['PHP_SELF'], $form->GetValue('cat'), $form->GetValue('sub')));
		}
	}

	$page = new Page('Stock Deletion Report', 'View products which are currently stocked by your company.');
	$page->Display('header');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cat');
	echo $form->GetHTML('sub');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_product SELECT p.Product_ID FROM product AS p INNER JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID AND w.`Type`='B' %s GROUP BY p.Product_ID", mysql_real_escape_string($clientString)));
	new DataQuery(sprintf("ALTER TABLE temp_product ADD INDEX Product_ID (Product_ID)"));

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order SELECT p.Product_ID, SUM(ol.Quantity) AS Quantity_Sold, COUNT(DISTINCT o.Order_ID) AS Orders FROM temp_product AS p INNER JOIN order_line AS ol ON ol.Product_ID=p.Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -3 MONTH) GROUP BY p.Product_ID"));
	new DataQuery(sprintf("ALTER TABLE temp_order ADD INDEX Product_ID (Product_ID)"));

	$table = new DataTable('stock');
	$table->SetSQL(sprintf("SELECT p.*, o.Quantity_Sold, o.Orders, SUM(ws.Quantity_In_Stock) AS Quantity_Stocked, ws.Is_Writtenoff FROM product AS p INNER JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID INNER JOIN warehouse_stock AS ws ON ws.Product_ID=p.Product_ID INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID AND w.`Type`='B' LEFT JOIN temp_order AS o ON p.Product_ID=o.Product_ID WHERE TRUE %s GROUP BY p.Product_ID", mysql_real_escape_string($clientString)));
	$table->AddField('ID#', 'Product_ID', 'left');
	$table->AddField('SKU', 'SKU', 'left');
	$table->AddField('Product Title', 'Product_Title', 'left');
	$table->AddField('Stocked', 'Quantity_Stocked', 'left');
	$table->AddField('Orders', 'Orders', 'left');
	$table->AddField('Quantity', 'Quantity_Sold', 'left');
	$table->AddField('Stock Profile', 'Is_Stocked', 'center');
	$table->AddField('Written Off', 'Is_Writtenoff', 'center');
	$table->AddInput('', 'N', 'Y', 'delete', 'Product_ID', 'checkbox');
	$table->AddLink("product_profile.php?pid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Product_ID");
	$table->AddLink(sprintf("javascript:confirmRequest('%s?action=remove&pid=%%s&cat=%d&sub=%s', 'Are you sure you want to remove all warehouse information for this item?');", $_SERVER['PHP_SELF'], $form->GetValue('cat'), $form->GetValue('sub')), "<img src=\"./images/aztector_6.gif\" alt=\"Remove Stock\" border=\"0\">", "Product_ID");
	$table->SetMaxRows(99999);
	$table->SetOrderBy('Product_Title');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input type="submit" name="remove" value="remove selected" class="btn" />';

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function GetChildIDS($cat) {
	$string = "";
	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($cat)));
	while($children->Row) {
		$string .= "OR c.Category_ID=".$children->Row['Category_ID']." ";
		$string .= GetChildIDS($children->Row['Category_ID']);
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}