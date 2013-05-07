<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['rid'])){
		$data = new DataQuery(sprintf("delete from product_related where Product_Related_ID=%d", mysql_real_escape_string($_REQUEST['rid'])));
	}

	redirect(sprintf("Location: product_related.php?pid=%d", $_REQUEST['pid']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> > Related Products', $_REQUEST['pid']),'The more information you supply the better your system will become');

	$page->Display('header');
	$sql = sprintf("SELECT pr.*, p.Product_Title, p.SKU
					FROM product_related as pr
					inner join product as p
					on pr.Product_ID=p.Product_ID
					where pr.Related_To_Product_ID=%d", mysql_real_escape_string($_REQUEST['pid']));
	$table = new DataTable("rel");
	$table->SetSQL($sql);
	$table->AddField('ID#', 'Product_ID', 'left');
	$table->AddField('SKU#', 'SKU', 'left');
	$table->AddField('Title', 'Product_Title', 'left');
	$table->AddField('Type', 'Type', 'left');
	$table->AddLink("javascript:confirmRequest('product_related.php?action=remove&confirm=true&rid=%s','Are you sure you want to remove this related product? Note: this operation removes the relationship only.');",
							"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
							"Product_Related_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new product relationship" class="btn" onclick="window.location.href=\'product_related.php?action=add&pid=%d\'">', $_REQUEST['pid']);
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductRelated.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);

	$products = 10;

	for($i=0; $i<$products; $i++) {
		$form->AddField('product_'.$i, 'Product ID #'.($i+1), 'text', '', 'numeric_unsigned', 1, 11, false);
		$form->AddField('type_'.$i, 'Type', 'select', '', 'paragraph', 0, 240, false);
		$form->AddOption('type_'.$i, '', '');
		$form->AddOption('type_'.$i, 'Energy Saving Alternative', 'Energy Saving Alternative');
	}

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			for($i=0; $i<$products; $i++) {
				$productId = $form->GetValue('product_'.$i);

				if(!empty($productId)) {
					$product = new Product();

					if(!$product->Get($productId)) {
						$form->AddError(sprintf('Product ID #%d (%d) does not exist.', ($i+1), $productId), 'product_'.$i);
					}
				}
			}

			if($form->Valid) {
				for($i=0; $i<$products; $i++) {
					if(strlen($form->GetValue('product_'.$i)) > 0) {
						$related = new ProductRelated();
						$related->Product->ID = $form->GetValue('product_'.$i);
						$related->Parent->ID = $form->GetValue('pid');
						$related->Type = $form->GetValue('type_'.$i);
						$related->IsActive = 'Y';
						$related->Add();
					}
				}

				redirect(sprintf("Location: ?pid=%d", $form->GetValue('pid')));
			}
		}
	}

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="?pid=%s">Related Products</a> &gt; Add Related Product', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add a Related Product.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');

	echo $window->Open();
	echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
	echo $window->OpenContent();
	echo $webForm->Open();

	for($i=0; $i<$products; $i++) {
		echo $webForm->AddRow($form->GetLabel('product_'.$i), $form->GetHTML('product_'.$i) . $form->GetHTML('type_'.$i));
	}

	echo $webForm->AddRow('', '<input type="submit" name="add" value="add" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}