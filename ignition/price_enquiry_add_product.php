<?php
function getCategories($categoryId) {
	$string = '';

	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row){
		$string .= sprintf("OR c.Category_ID=%d %s ", mysql_real_escape_string($data->Row['Category_ID']), mysql_real_escape_string(getCategories($data->Row['Category_ID'])));

		$data->Next();
	}
	$data->Disconnect();

	return $string;
}

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');

$session->Secure(3);

$priceEnquiry = new PriceEnquiry($_REQUEST['id']);

if(strtolower($priceEnquiry->Status) == 'complete') {
	redirect(sprintf("Location: price_enquiry_details.php?id=%d", $priceEnquiry->ID));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'addfly', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Price Enquiry ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
$form->AddField('products', 'Top Products', 'select', '', 'numeric_unsigned', 1, 11);
$form->AddOption('products', '', '');
$form->AddOption('products', '10', '10');
$form->AddOption('products', '20', '20');
$form->AddOption('products', '30', '30');
$form->AddOption('products', '40', '40');
$form->AddOption('products', '50', '50');
$form->AddOption('products', '100', '100');
$form->AddOption('products', '200', '200');
$form->AddOption('products', '300', '300');
$form->AddOption('products', '400', '400');
$form->AddOption('products', '500', '500');
$form->AddOption('products', '600', '600');
$form->AddOption('products', '700', '700');
$form->AddOption('products', '800', '800');
$form->AddOption('products', '900', '900');
$form->AddOption('products', '1000', '1000');
$form->AddOption('products', '1100', '1100');
$form->AddOption('products', '1200', '1200');
$form->AddOption('products', '1300', '1300');
$form->AddOption('products', '1400', '1400');
$form->AddOption('products', '1500', '1500');
$form->AddOption('products', '2000', '2000');
$form->AddOption('products', '2500', '2500');
$form->AddOption('products', '3000', '3000');
$form->AddField('period', 'Period (Days)', 'select', '', 'numeric_unsigned', 1, 11);
$form->AddOption('period', '', '');
$form->AddOption('period', '30', '30');
$form->AddOption('period', '60', '60');
$form->AddOption('period', '90', '90');
$form->AddOption('period', '180', '180');
$form->AddField('minimumorders', 'Minimum Orders', 'select', '1', 'numeric_unsigned', 1, 11);

for($i=1; $i<10; $i++) {
	$form->AddOption('minimumorders', $i, $i);
}

for($i=10; $i<=25; $i=$i+5) {
	$form->AddOption('minimumorders', $i, $i);
}

for($i=30; $i<=100; $i=$i+10) {
	$form->AddOption('minimumorders', $i, $i);
}

$form->AddField('excludestocked', 'Exclude Stocked', 'checkbox', 'N', 'boolean', 1, 1, false);
$form->AddField('order', 'Order By', 'select', 'Quantity', 'anything', 1, 64);
$form->AddOption('order', 'Quantity', 'Quantity');
$form->AddOption('order', 'Orders', 'Orders');
$form->AddField('pricedbefore', 'Priced Before', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('supplier', 'Supplier', 'select', '', 'numeric_unsigned', 1, 11, false);
$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
$form->AddGroup('supplier', 'N', 'Standard Suppliers');
$form->AddOption('supplier', '', '');

$data = new DataQuery(sprintf("SELECT s.Supplier_ID, s.Is_Favourite, CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')')) AS Supplier FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY Supplier ASC"));
while($data->Row) {
	$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier'], $data->Row['Is_Favourite']);

	$data->Next();
}
$data->Disconnect();

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$sqlSelect = sprintf("CREATE TEMPORARY TABLE temp_product SELECT ol.Product_ID, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantity ");
		$sqlFrom = sprintf("FROM order_line AS ol INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID INNER JOIN product AS p ON p.Product_ID=ol.Product_ID ");
		$sqlWhere = sprintf("WHERE o.Created_On>=ADDDATE(NOW(), INTERVAL -%d DAY) AND ol.Product_ID>0 AND o.Status LIKE 'Despatched' ", mysql_real_escape_string($form->GetValue('period')));
		$sqlGroup = sprintf("GROUP BY ol.Product_ID ");
		$sqlHaving = "";


		if((strlen($form->GetValue('pricedbefore')) > 0) && ($form->GetValue('supplier') > 0)) {
			$sqlFrom .= sprintf("LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Supplier_ID=%d ", mysql_real_escape_string($form->GetValue('supplier')));
			$sqlWhere .= sprintf("AND (sp.Modified_On<'%s' OR ISNULL(sp.Supplier_Product_ID)) ", sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('pricedbefore'), 6, 4), substr($form->GetValue('pricedbefore'), 3, 2), substr($form->GetValue('pricedbefore'), 0, 2)));
		}

		if($form->GetValue('parent') > 0) {
			$sqlFrom .= sprintf("INNER JOIN product_in_categories AS c ON c.Product_ID=ol.Product_ID AND (c.Category_ID=%d %s) ", mysql_real_escape_string($form->GetValue('parent')), mysql_real_escape_string(getCategories($form->GetValue('parent'))));
		}

		if($form->GetValue('minimumorders') > 1) {
			$sqlHaving .= sprintf("HAVING Orders>=%d ", $form->GetValue('minimumorders'));
		}

		new DataQuery(sprintf("%s%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup, $sqlHaving));
		new DataQuery("ALTER TABLE temp_product ADD INDEX Product_ID (Product_ID)");

		$exclude = array();
		
		if($form->GetValue('excludestocked') == 'Y') {
			$data = new DataQuery(sprintf("SELECT tp.Product_ID FROM temp_product AS tp INNER JOIN warehouse_stock AS ws ON ws.Product_ID=tp.Product_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' GROUP BY tp.Product_ID"));
			while($data->Row) {
				$exclude[$data->Row['Product_ID']] = $data->Row['Product_ID'];

				$data->Next();
			}
			$data->Disconnect();
		}

		$data = new DataQuery(sprintf("SELECT * FROM temp_product ORDER BY %s DESC LIMIT 0, %d", mysql_real_escape_string($form->GetValue('order')), mysql_real_escape_string($form->GetValue('products'))));
		while($data->Row) {
			if(!isset($exclude[$data->Row['Product_ID']])) {
				$quantity = (round($data->Row['Quantity'] / 10) * 10);

				if($quantity > 0) {
					$priceEnquiry->AddLine($data->Row['Product_ID'], $quantity, $data->Row['Orders']);
				}
			}

			$data->Next();
		}
		$data->Disconnect();

		$priceEnquiry->SetSuppliersIncomplete();
		$priceEnquiry->Recalculate();

		redirect(sprintf("Location: price_enquiry_details.php?id=%d", $priceEnquiry->ID));
	}
}

$page = new Page(sprintf('<a href="price_enquiry_details.php?id=%d">[#%d] Price Enquiry Details</a> &gt; Add Products', $priceEnquiry->ID, $priceEnquiry->ID), 'Add products for this price enquiry.');
$page->LinkScript('js/scw.js');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Product selection list');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');
echo $form->GetHTML('parent');

echo $window->Open();
echo $window->AddHeader('Select product criteria for adding to this price enquiry');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
echo $webForm->AddRow($form->GetLabel('products'), $form->GetHTML('products'));
echo $webForm->AddRow($form->GetLabel('period'), $form->GetHTML('period'));
echo $webForm->AddRow($form->GetLabel('minimumorders'), $form->GetHTML('minimumorders'));
echo $webForm->AddRow($form->GetLabel('excludestocked'), $form->GetHTML('excludestocked'));
echo $webForm->AddRow($form->GetLabel('order'), $form->GetHTML('order'));
echo $webForm->Close();
echo $window->CloseContent();

echo $window->AddHeader('Optionally specify a date and supplier for excluding recently cost prices');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('pricedbefore'), $form->GetHTML('pricedbefore'));
echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href=\'price_enquiry_details.php?id=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $priceEnquiry->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');