<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSearch.php');

$session->secure(2);

if($action == 'use'){
	useProd();
	exit;
} else {
	view();
	exit;
}

function useProd(){
	if(isset($_REQUEST['pid'])){
		$product = new Product($_REQUEST['pid']);
		$page = new Page();
		$page->DisableTitle();
		$page->Display('header');
		echo sprintf("<script>popFindProduct(%d, '%s');</script>", $product->ID, $product->Name);
		$page->Display('footer');
		require_once('lib/common/app_footer.php');
	} else {
		redirect("Location: product_search.php?serve=pop");
	}
}

function view(){
	$serve = (isset($_REQUEST['serve']))?$_REQUEST['serve']:'view';

	$page = new Page('Product Search','');
	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('serve', 'Serve', 'hidden', $serve, 'alpha', 1, 6);
	$form->AddField('option', 'Option', 'select', 'product', 'anything', 1, 255);
	$form->AddOption('option', 'product', 'Product');
	$form->AddOption('option', 'optionGroup', 'Product Option Group');
	$form->AddField('string', 'Search for...', 'text', '', 'paragraph', 1, 255);

	$window = new StandardWindow("Search for a Product.");
	$webForm = new StandardForm;

	if(isset($_REQUEST['string']) && !empty($_REQUEST['string'])){
		if($form->Validate()){

			$table = new DataTable('results');
			$table->AddField('ID#', 'Product_ID', 'left');
			$table->AddField('Title', 'Product_Title', 'left');
			$table->AddField('Discontinued', 'Discontinued', 'center');

			if(isset($_REQUEST['option']) && strtolower($_REQUEST['option']) == 'product'){
				$search = new ProductSearch($_REQUEST['string'],'./product_profile.php?pid=');
				$search->PrepareSQL();

				$table->SetSQL($search->Query);
				$table->OrderBy = 'score';
			} else if (isset($_REQUEST['option']) && strtolower($_REQUEST['option']) == 'optiongroup'){
				$sql = "select p.* from product as p
							inner join product_option_groups as pog on p.Product_ID=pog.Product_ID
							where pog.Group_Title like '%{$_REQUEST['string']}%' group by p.Product_ID";
				$table->SetSQL($sql);
				$table->OrderBy = 'Product_ID';
			}

			$table->SetMaxRows(10);
			$table->Order = 'DESC';
			$table->Finalise();
			$table->ExecuteSQL();
		}
	}

	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('serve');
	echo $window->Open();
	echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('string'), $form->GetHTML('option') . $form->GetHTML('string') . '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";

	if(isset($_REQUEST['string']) && !empty($_REQUEST['string'])){
		echo $table->GetTableHeader();
		while($table->Table->Row){
			$prod = new Product($table->Table->Row['Product_ID']);

			echo sprintf('<tr><td><img src="../images/products/%s" /></td>', $prod->DefaultImage->Thumb->FileName);
			echo sprintf('<td><strong><a href="product_profile.php?pid=%s">%s</a></strong><br />Quickfind: <strong>%s</strong>, SKU: %s, Price &pound;%s (Inc. VAT)</td>',$prod->ID, $prod->Name, $prod->ID, $prod->SKU, number_format($prod->PriceCurrentIncTax, 2));
			echo sprintf('<td align="center">%s</td>', $prod->Discontinued);

			if($serve == "pop"){
				echo sprintf('<td><a href="product_search.php?action=use&pid=%s">[USE]</a></td></tr>', $prod->ID);
			} else {
				echo sprintf('<td><a href="product_profile.php?pid=%s"><img src="./images/icon_edit_1.gif" alt="Update Settings" border="0"></a></td>', $prod->ID);
			}
			echo '</tr>';
			$table->Next();
		}

		echo '</table>';
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
	}
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}