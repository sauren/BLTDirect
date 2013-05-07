<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCollection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductCollectionAssoc.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "removeall"){
	$session->Secure(3);
	removeAll();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(!isset($_REQUEST['cid'])) {
		redirect(sprintf("Location: product_collection.php"));
	}

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$assoc = new ProductCollectionAssoc();
		$assoc->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $_REQUEST['cid']));
}

function removeAll() {
	if(!isset($_REQUEST['cid'])) {
		redirect(sprintf("Location: product_collection.php"));
	}

	new DataQuery(sprintf("DELETE FROM product_collection_assoc WHERE ProductCollectionID=%d", mysql_real_escape_string($_REQUEST['cid'])));

	redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $_REQUEST['cid']));
}

function add(){
	if(!isset($_REQUEST['cid'])) {
		redirect(sprintf("Location: product_collection.php"));
	}

	$collection = new ProductCollection($_REQUEST['cid']);
	
	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Collection ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('string', 'Deep search', 'text', '', 'anything', 1, 255, false);
	
	if(isset($_REQUEST['addselected'])) {
		$assoc = new ProductCollectionAssoc();
		$assoc->ProductCollectionID = $collection->ID;
		
		foreach($_REQUEST as $key=>$product) {
			if(preg_match('/add_([0-9]+)/', $key, $matches)) {
				$assoc->ProductID = $matches[1];
				$assoc->Add();
			}
		}

		redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $collection->ID));
	}

	$sqlSelect = '';
	$sqlFrom = '';
	$sqlWhere = '';

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$sqlSelect .= sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU ");
			$sqlFrom .= sprintf("FROM product AS p LEFT JOIN product_collection_assoc AS pca ON pca.ProductID=p.Product_ID AND pca.ProductCollectionID=%d ", mysql_real_escape_string($collection->ID));
			$sqlWhere .= sprintf("WHERE pca.ProductCollectionAssocID IS NULL ");
			$sqlWhere .= parseSearchString($form->GetValue('string'), array('p.Product_ID', 'p.Product_Title', 'p.SKU'));
		}
	}

	$page = new Page(sprintf('<a href="product_collection.php">Product Collections</a> &gt; <a href="product_collection_assoc.php?cid=%d">%s</a> &gt; Add Products', $collection->ID, $collection->Name), 'This area allows you to maintain products for your collection.');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Search for products.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cid');

	echo $window->Open();
	echo $window->AddHeader('Search for products by any of the below fields.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('string'), $form->GetHTML('string'));
	echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	if(strlen(sprintf('%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere)) > 0) {
		echo '<br />';

		$table = new DataTable("products");
		$table->SetSQL(sprintf('%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere));
		$table->AddField('ID#', 'Product_ID', 'left');
		$table->AddField('Name', 'Product_Title', 'left');
		$table->AddField('SKU', 'SKU', 'left');
		$table->AddInput('', 'N', '', 'add', 'Product_ID', 'checkbox');
		$table->AddLink("product_profile.php?productid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Product_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Product_Title");
		$table->Finalise();
		$table->DisplayTable();
		echo '<br />';
		$table->DisplayNavigation();
		?>

		<br />

		<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td align="right">
					<input type="submit" name="addselected" value="add selected products" class="btn" />
				</td>
			</tr>
		</table>
		<br />

		<?php
	}
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	if(!isset($_REQUEST['cid'])) {
		redirect(sprintf("Location: product_collection_assoc.php"));
	}

	$collection = new ProductCollection($_REQUEST['cid']);

	$page = new Page(sprintf('<a href="product_collection.php">Product Collections</a> &gt; %s', $collection->Name), 'This area allows you to maintain products for your collection.');
	$page->Display('header');

	$table = new DataTable('product');
	$table->SetSQL(sprintf("SELECT pca.ProductCollectionAssocID, p.Product_ID, p.Product_Title, p.SKU FROM product_collection_assoc AS pca INNER JOIN product AS p ON p.Product_ID=pca.ProductID WHERE pca.ProductCollectionID=%d", mysql_real_escape_string($collection->ID)));
	$table->AddField('ID#', 'Product_ID', 'left');
	$table->AddField('Name', 'Product_Title', 'left');
	$table->AddField('SKU', 'SKU', 'left');
	$table->AddLink("product_profile.php?productid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Product_ID");
	$table->AddLink("javascript:confirmRequest('product_collection_assoc.php?action=remove&id=%s', 'Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "ProductCollectionAssocID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Product_Title');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add products" class="btn" onclick="window.location.href=\'product_collection_assoc.php?action=add&cid=%d\'" /> ', $collection->ID);
	echo sprintf('<input type="button" name="removeall" value="remove all" class="btn" onclick="window.location.href=\'product_collection_assoc.php?action=removeall&cid=%d\'" /> ', $collection->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function parseSearchString($value, $fields = array()) {
	$sqlWhere = '';

	if(count($fields) > 0) {
		parse_search_string(stripslashes($value), $keywords);

		if(count($keywords) > 0) {
			$sqlWhere .= ' AND (';

			for($i=0; $i < count($keywords); $i++){
				switch(strtoupper($keywords[$i])) {
					case '(':
					case ')':
					case 'AND':
					case 'OR':
						$sqlWhere .= sprintf(" %s ", $keywords[$i]);
						break;
					default:
						$sqlWhere .= " (";

						foreach($fields as $field) {
							$sqlWhere .= sprintf("%s LIKE '%%%s%%' OR ", $field, addslashes(stripslashes($keywords[$i])));
						}

						$sqlWhere = substr($sqlWhere, 0, -4);
						$sqlWhere .= ")";
						break;
				}
			}

			$sqlWhere .= ') ';
		}
	}

	return $sqlWhere;
}