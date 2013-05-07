<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSearch.php');

$session->Secure(2);

if($action == 'use'){
	useProd();
	exit;
} else {
	view();
	exit;
}

function useProd(){
	if(isset($_REQUEST['pid'])){
		$page = new Page();
		$page->DisableTitle();
		$page->Display('header');

		echo sprintf("<script>popFindProductAssociation(%d, '%s');</script>", $_REQUEST['pid'], $_REQUEST['field']);

		$page->Display('footer');

		require_once('lib/common/app_footer.php');
	} else {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
}

function view(){
	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('field', 'Field', 'hidden', '', 'anything', 0, 100);
	$form->AddField('string', 'Search for...', 'text', '', 'paragraph', 1, 255);

	$window = new StandardWindow("Search for a Product");
	$webForm = new StandardForm;

	if(isset($_REQUEST['string']) && !empty($_REQUEST['string'])){
		if($form->Validate()){
			$search = new ProductSearch($_REQUEST['string']);
			$search->PrepareSQL();

			$table = new DataTable('results');
			$table->AddField('Image', 'Product_ID', 'left');
			$table->AddField('Title', 'Product_Title', 'left');
			$table->AddLink('', '', '');
			$table->SetSQL($search->Query);
			$table->OrderBy = 'score';
			$table->SetMaxRows(10);
			$table->Order = 'DESC';
			$table->Finalise();
			$table->ExecuteSQL();
		}
	}

	$page = new Page('Product Search','');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('field');

	echo $window->Open();
	echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('string'), $form->GetHTML('string') . '<input type="submit" name="search" value="search" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	if(isset($_REQUEST['string']) && !empty($_REQUEST['string'])){
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		echo $table->GetTableHeader();

		while($table->Table->Row){
			$prod = new Product($table->Table->Row['Product_ID']);

			if(!empty($prod->DefaultImage->Thumb->FileName) && file_exists('../images/products/'.$prod->DefaultImage->Thumb->FileName)) {
				echo sprintf('<tr><td><img src="../images/products/%s" /></td>', $prod->DefaultImage->Thumb->FileName);
			} else {
				echo '<tr><td>&nbsp;</td>';
			}

			echo sprintf('<td><strong><a href="product_profile.php?pid=%s">%s</a></strong><br />Quickfind: <strong>%s</strong>, SKU: %s, Price &pound;%s (Inc. VAT)</td>',$prod->ID, $prod->Name, $prod->ID, $prod->SKU, number_format($prod->PriceCurrentIncTax, 2));
			echo sprintf('<td><a href="%s?action=use&pid=%s&field=%s">[USE]</a></td></tr>', $_SERVER['PHP_SELF'], $prod->ID, $form->GetValue('field'));
			echo '</tr>';

			$table->Next();
		}

		echo '</table>';
		echo "<br>";
		$table->DisplayNavigation();
	}

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>