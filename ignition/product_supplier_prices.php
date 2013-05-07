<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPrice.php');

if($action == 'add') {
	$session->Secure(3);
	add();
	exit();
} elseif($action == 'available') {
	$session->Secure(3);
	available();
	exit();
} elseif($action == 'unavailable') {
	$session->Secure(3);
	unavailable();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function available() {
	if(isset($_REQUEST['pid']) && isset($_REQUEST['supplierid'])) {
		$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($_REQUEST['supplierid']), mysql_real_escape_string($_REQUEST['pid'])));
		if($data->TotalRows > 0) {
			$product = new SupplierProduct($data->Row['Supplier_Product_ID']);
			$product->IsUnavailable = 'N';
			$product->Update();
		} else {
			$product = new SupplierProduct();
			$product->Product->ID = $_REQUEST['pid'];
			$product->Supplier->ID = $_REQUEST['supplierid'];
			$product->IsUnavailable = 'N';
			$product->Add();
		}
		$data->Disconnect();

		redirectTo('?pid=' . $_REQUEST['pid']);
	}

	redirectTo('product_search.php');
}

function unavailable() {	
	if(isset($_REQUEST['pid']) && isset($_REQUEST['supplierid'])) {
		$prices = array();
		$suppliers = array();

		$data = new DataQuery(sprintf("SELECT Supplier_ID, Quantity, Cost FROM supplier_product_price WHERE Product_ID=%d AND Supplier_ID=%d ORDER BY Quantity ASC, Supplier_Product_Price_ID ASC", mysql_real_escape_string($_REQUEST['pid']), mysql_real_escape_string($_REQUEST['supplierid'])));
		while($data->Row) {
			if(!isset($prices[$data->Row['Quantity']])) {
				$prices[$data->Row['Quantity']] = array();
			}

			$prices[$data->Row['Quantity']] = $data->Row['Cost'];

			$data->Next();
		}
		$data->Disconnect();

		$supplierProductId = 0;

		$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($_REQUEST['supplierid']), mysql_real_escape_string($_REQUEST['pid'])));
		if($data->TotalRows > 0) {
			$product = new SupplierProduct($data->Row['Supplier_Product_ID']);
			$product->IsUnavailable = 'Y';
			$product->Update();

			$supplierProductId = $product->ID;
		} else {
			$product = new SupplierProduct();
			$product->Product->ID = $_REQUEST['pid'];
			$product->Supplier->ID = $_REQUEST['supplierid'];
			$product->IsUnavailable = 'Y';
			$product->Add();

			$supplierProductId = $product->ID;
		}
		$data->Disconnect();

		foreach($prices as $quantity=>$cost) {
			if($quantity > 1) {
				$price = new SupplierProductPrice();
				$price->Product->ID = $_REQUEST['pid'];
				$price->Supplier->ID = $_REQUEST['supplierid'];
				$price->Quantity = $quantity;
				$price->Cost = 0;
				$price->Reason = 'Not available';
				$price->Add();
			} else {
				$product = new SupplierProduct($supplierProductId);
				$product->Cost = 0;
				$product->Reason = 'Not available';
				$product->Update();
			}
		}

		redirectTo('?pid=' . $_REQUEST['pid']);
	}

	redirectTo('product_search.php');
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('supplier', 'Supplier', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
	$form->AddGroup('supplier', 'N', 'Standard Suppliers');
	$form->AddOption('supplier', '', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, s.Is_Favourite, o.Org_Name, TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)) AS Person_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID ORDER BY o.Org_Name ASC, Person_Name ASC"));
	while($data->Row) {
		if(!empty($data->Row['Org_Name']) && !empty($data->Row['Person_Name'])) {
			$supplierName = sprintf('%s (%s)', $data->Row['Org_Name'], $data->Row['Person_Name']);
		} elseif(!empty($data->Row['Org_Name'])) {
			$supplierName = $data->Row['Org_Name'];
		} else {
			$supplierName = $data->Row['Person_Name'];
		}

		$form->AddOption('supplier', $data->Row['Supplier_ID'], $supplierName, $data->Row['Is_Favourite']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('quantity', 'Quantity', 'text', '', 'numeric_unsigned', 1, 11);
	$form->AddField('cost', 'Cost', 'text', '', 'float', 1, 11);
	$form->AddField('reason', 'Reason', 'text', '', 'paragraph', 1, 240);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if($form->GetValue('quantity') > 1) {
				$price = new SupplierProductPrice();
				$price->Product->ID = $form->GetValue('pid');
				$price->Supplier->ID = $form->GetValue('supplier');
				$price->Quantity = $form->GetValue('quantity');
				$price->Cost = $form->GetValue('cost');
				$price->Reason = $form->GetValue('reason');
				$price->Add();
			} else {
				$product = new SupplierProduct();
				$product->Product->ID = $form->GetValue('pid');
				$product->Supplier->ID = $form->GetValue('supplier');
				$product->Cost = $form->GetValue('cost');
				$product->Reason = $form->GetValue('reason');
				$product->Add();
			}

			redirect(sprintf("Location: %s?pid=%d", $_SERVER['PHP_SELF'], $form->GetValue('pid')));
		}
	}

	$page = new Page(sprintf("<a href=product_profile.php?pid=%d>Product Profile</a> &gt <a href=supplier_product.php?pid=%d> Supplier Information </a> &gt Add New Supplier", $_REQUEST['pid'], $_REQUEST['pid']), "Add a new supplier who will supply this product");
	$page->Display('header');

	if (!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add a supplier of the product');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier') . $form->GetIcon('supplier'));
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
	echo $webForm->AddRow($form->GetLabel('cost'), $form->GetHTML('cost') . $form->GetIcon('cost') . ' (per unit at the above quantity)');
	echo $webForm->AddRow($form->GetLabel('reason'), $form->GetHTML('reason') . $form->GetIcon('reason'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s?pid=%s\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $_SERVER['PHP_SELF'], $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}

function view() {
	$prices = array();
	$suppliers = array();
	$minimal = array(3, 4, 5, 22);

	foreach($minimal as $supplierId) {
		if(!isset($prices[1])) {
			$prices[1] = array();
		}

		$prices[1][$supplierId] = 0;
	}

	$data = new DataQuery(sprintf("SELECT Supplier_ID, Cost FROM supplier_product WHERE Product_ID=%d AND Supplier_ID IN (%s)", mysql_real_escape_string($_REQUEST['pid']), implode(', ', $minimal)));
	while($data->Row) {
		if(!isset($prices[1])) {
			$prices[1] = array();
		}

		$prices[1][$data->Row['Supplier_ID']] = $data->Row['Cost'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT Supplier_ID, Quantity, Cost FROM supplier_product_price WHERE Product_ID=%d ORDER BY Quantity ASC, Supplier_Product_Price_ID ASC", mysql_real_escape_string($_REQUEST['pid'])));
	while($data->Row) {
		if(!isset($prices[$data->Row['Quantity']])) {
			$prices[$data->Row['Quantity']] = array();
		}

		$prices[$data->Row['Quantity']][$data->Row['Supplier_ID']] = $data->Row['Cost'];

		$data->Next();
	}
	$data->Disconnect();

	foreach($prices as $quantity=>$item) {
		foreach($item as $supplierId=>$cost) {
			$suppliers[$supplierId] = new Supplier($supplierId);
			$suppliers[$supplierId]->Contact->Get();

			if($suppliers[$supplierId]->Contact->Parent->ID > 0) {
				$suppliers[$supplierId]->Contact->Parent->Get();
			}
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%d">Product Profile</a> &gt; Supplier Price History', $_REQUEST['pid']), 'Track supplier price change history here.');
	$page->Display('header');
	?>

	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
		<thead>
			<tr>
				<th>Current Prices</th>

				<?php
				foreach($prices as $quantity=>$item) {
					echo sprintf('<th style="text-align: right;">%sx</th>', $quantity);
				}
				?>

				<th width="1%">&nbsp;</th>
			</tr>
		</thead>
		<tbody>

			<?php
			foreach($suppliers as $supplierId=>$supplier) {
				$supplierName = trim(sprintf('%s &lt;%s&gt;', $supplier->Contact->Parent->Organisation->Name, trim(sprintf('%s %s', $supplier->Contact->Person->Name, $supplier->Contact->Person->LastName))));

				$data = new DataQuery(sprintf("SELECT IsUnavailable FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($_REQUEST['pid'])));
				$style = (($data->TotalRows > 0) && ($data->Row['IsUnavailable'] == 'Y')) ? 'style="background-color: #FF9D9D;"' : '';
				$isUnavailable = (($data->TotalRows > 0) && ($data->Row['IsUnavailable'] == 'Y'));
				$data->Disconnect();
				?>

				<tr>
					<td <?php echo $style; ?>><?php echo $supplierName; ?></td>

					<?php
					foreach($prices as $quantity=>$item) {
						echo sprintf('<td align="right" %s>%s</td>', $style, (isset($item[$supplierId]) && ($item[$supplierId] > 0)) ? $item[$supplierId] : '-');
					}
					?>

					<td <?php echo $style; ?>>
						<?php
						if($isUnavailable) {
							echo sprintf('<a href="?action=available&pid=%d&supplierid=%d"><img border="0" src="images/button-money.gif" /></a>', $_REQUEST['pid'], $supplierId);
						} else {
							echo sprintf('<a href="?action=unavailable&pid=%d&supplierid=%d"><img border="0" src="images/button-na.gif" /></a>', $_REQUEST['pid'], $supplierId);
						}
						?>
					</td>
				</tr>

				<?php
			}
			?>

		</tbody>
	</table>
	<br />

	<?php
	$table = new DataTable('prices');
	$table->SetSQL(sprintf("SELECT spp.*, CONCAT_WS(' ', o.Org_Name, CONCAT('&lt;', CONCAT_WS(' ', p.Name_First, p.Name_Last), '&gt;')) AS Supplier, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS Created_Name FROM supplier_product_price AS spp INNER JOIN supplier AS s ON spp.Supplier_ID=s.Supplier_ID INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID LEFT JOIN users AS u ON u.User_ID=spp.Created_By LEFT JOIN person AS p2 ON p2.Person_ID=u.Person_ID WHERE spp.Product_ID=%d", mysql_real_escape_string($_REQUEST['pid'])));
	$table->AddField('Price Date', 'Created_On', 'left');
	$table->AddField('Supplier', 'Supplier');
	$table->AddField('Quantity', 'Quantity', 'left');
	$table->AddField('Cost', 'Cost', 'right');
	$table->AddField('Reason', 'Reason', 'left');
	$table->AddField('Created By', 'Created_Name', 'left');
	$table->SetMaxRows(10);
	$table->SetOrderBy('Created_On');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add new supplier price" class="btn" onclick="window.location.href=\'%s?action=add&pid=%d\'" /> ', $_SERVER['PHP_SELF'], $_REQUEST['pid']);

	echo '<br /><br />';

	echo '<h3>Purchases</h3>';
	echo '<p>Listing outstanding purchases which contain incoming quantities for this product.</p>';

	$table = new DataTable("purchases");
	$table->SetExtractVars(array('pid'));
	$table->SetSQL(sprintf("SELECT p.*, SUM(pl.Quantity_Decremental) AS Quantity_Incoming, pl.Cost FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID INNER JOIN users u ON u.Branch_ID=p.For_Branch WHERE p.Purchase_Status NOT LIKE 'Cancelled' AND u.User_ID=%d AND pl.Quantity_Decremental>0 AND pl.Product_ID=%d GROUP BY p.Purchase_ID", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($_REQUEST['pid'])));
	$table->AddField('Purchase ID','Purchase_ID');
	$table->AddField('Date Ordered','Purchased_On');
	$table->AddField('Organisation','Supplier_Organisation_Name');
	$table->AddField('First Name','Supplier_First_Name');
	$table->AddField('Last Name','Supplier_Last_Name');
	$table->AddField('Status','Purchase_Status');
	$table->AddField('Quantity Incoming','Quantity_Incoming', 'right');
	$table->AddField('Cost','Cost', 'right');
	$table->AddLink('purchase_edit.php?pid=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Update the purchase settings\" border=\"0\">",'Purchase_ID');
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once ('lib/common/app_footer.php');
}