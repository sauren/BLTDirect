<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BreadCrumb.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

if($action == 'update') {
	$session->Secure(2);
	update($session->Supplier->ID);
	exit;
} else {
	$session->Secure(2);
	view($session->Supplier->ID);
	exit;
}

function update($supplierId) {
	$supplierProduct = new SupplierProduct();

	if(!isset($_REQUEST['sid']) || !$supplierProduct->Get($_REQUEST['sid'])) {
		redirectTo('?action=view');
	}

	if($supplierProduct->Supplier->ID != $supplierId) {
		redirectTo('?action=view');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('sid','sid','hidden', '','numeric_unsigned',0,11);
	$form->AddField('cat','cat','hidden', '','numeric_unsigned',0,11);
	$form->AddField('sku','SKU','text', $supplierProduct->SKU, 'paragraph',0,30);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$supplierProduct->SKU = $form->GetValue('sku');
			$supplierProduct->Update();

			redirect(sprintf('Location: ?cat=%d', $form->GetValue('cat')));
		}
	}

	$category = new Category();
	$category->ID = (isset($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])) ? $_REQUEST['cat'] : 1;
	$category->Get();

	$page = new Page(sprintf('<a href="?cat=%d">%s Categorised Products</a> &gt; Update Product', $category->ID, $category->Name, $category->ID), 'Update supplier details here.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update stock.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('sid');
	echo $form->GetHTML('cat');

	echo $window->Open();
	echo $window->AddHeader('Edit stock details');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('sku'),$form->GetHTML('sku').$form->GetIcon('sku'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'?cat=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetValue('cat'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();
}

function view($supplierId) {
	$category = new Category();
	$category->ID = (isset($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])) ? $_REQUEST['cat'] : 1;
	$category->Get();

	$breadCrumb = new BreadCrumb();
	$breadCrumb->LinkCode = '?cat=%d';
	$breadCrumb->Separator = '&gt;';
	$breadCrumb->Get($category->ID);

	$page = new Page($category->Name . ' Categorised Products', 'Listing products arranged by categories.');
	$page->Display('header');
	?>

	<div>
		<a href="?action=view">Categorised Products</a>
		<?php echo $breadCrumb->Text; ?>
	</div>

	<?php
	$data = new DataQuery(sprintf('SELECT * FROM product_categories WHERE Category_Parent_ID=%d AND Is_Active=\'Y\' ORDER BY %s', mysql_real_escape_string($category->ID), !empty($category->CategoryOrder) ? mysql_real_escape_string($category->CategoryOrder) : 'Category_Title'));
	if($data->TotalRows > 0) {
		echo '<br />';

		if($category->ShowImages == 'Y') {
			$productColumns = 3;
			echo "<table class=\"productCategories clear\">";
			$tempColumn = 0;
			$rows = 0;
			while($data->Row){
				++$tempColumn;
				++$rows;
				if($tempColumn == 1) echo "<tr>";

				$tempStr = sprintf('<td width="%f%%%%">%%s<br /><p>%%s</p></td>', (100 / $productColumns));

				if(!empty($data->Row['Category_Thumb'])){
					$image =  sprintf("<a href=\"?cat=%s\"><img src=\"../../images/categories/%s\" alt=\"%s\" /></a>", $data->Row['Category_ID'], $data->Row['Category_Thumb'], $data->Row['Meta_Title']);
				} else {
					$image =  sprintf("<a href=\"?cat=%s\"><img src=\"../../images/template/image_coming_soon_2.jpg\" alt=\"%s\" /></a>", $data->Row['Category_ID'], $data->Row['Meta_Title']);
				}
				$link = sprintf('<a href="?cat=%s">%s</a>', $data->Row['Category_ID'], $data->Row['Category_Title']);
				echo sprintf($tempStr, $image, $link);

				if(($tempColumn == $productColumns) || ($rows == $data->TotalRows)){
					echo "</tr>";
					$tempColumn = 0;
				}
				$data->Next();
			}
			echo "</table>";
		} else {
			$productColumns = 2;

			$dataArr = array();

			while($data->Row){
				$dataArr[] = sprintf('<a href="?cat=%s">%s</a>', $data->Row['Category_ID'], $data->Row['Category_Title']);

				$data->Next();
			}

			$tempColumn = 0;
			$rows = 0;
			$columnArr = array();
			$col = 0;
			$count = 0;

			for($i=0;$i < count($dataArr); $i++) {
				if($count >= (count($dataArr) / $productColumns)) {
					$col++;
					$count = 0;
				}

				$columnArr[$col][] = $dataArr[$i];
				$count++;
			}

			echo "<table class=\"productCategories clear\">";

			for($i=0;$i < count($columnArr[0]); $i++) {

				echo "<tr>";

				for($j=0;$j < $productColumns; $j++) {
					if(isset($columnArr[$j][$i])) {
						$link = $columnArr[$j][$i];
					} else {
						$link = '&nbsp;';
					}

					echo sprintf("<td style=\"text-align: left;\">%s</td>", $link);
				}

				echo "</tr>";
			}

			echo "</table>";
		}
	}
	$data->Disconnect();

	echo '<br />';

	$table = new DataTable('products');
	$table->SetSQL(sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU, sp.Supplier_Product_ID, sp.Supplier_SKU, sp.Cost FROM supplier_product AS sp INNER JOIN product AS p ON p.Product_ID=sp.Product_ID INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID WHERE sp.Supplier_ID=%d AND pic.Category_ID=%d", mysql_real_escape_string($supplierId), mysql_real_escape_string($category->ID)));
	$table->AddField('ID', 'Product_ID');
	$table->AddField('Product','Product_Title');
	$table->AddField('SKU','SKU');
	$table->AddField('Your SKU', 'Supplier_SKU');
	$table->AddField('Cost', 'Cost', 'right');
	$table->AddLink('?action=update&sid=%s',"<img src=\"images/icon_edit_1.gif\" alt=\"Update \" border=\"0\">",'Supplier_Product_ID');
	$table->AddLink("../../product.php?pid=%s", "<img src=\"images/folderopen.gif\" alt=\"View Product\" border=\"0\">", "Product_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Product_Title');
	$table->Order = 'ASC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}