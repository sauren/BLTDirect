<?php
	require_once('lib/common/app_header.php');
	
	if(!isset($_REQUEST['band'])){
		redirect("Location: product_bands.php");
	}
		
	if($action == "add_product"){
		$session->Secure(3);
		addProduct();
		exit;
	} elseif($action == "add_category"){
		$session->Secure(3);
		addCategory();
		exit;
	} elseif($action == "remove_product"){
		$session->Secure(3);
		removeProduct();
		exit;
	} elseif($action == "remove_category"){
		$session->Secure(3);
		removeCategory();
		exit;
	} else {
		$session->Secure(2);
		view();
		exit;
	}
	
	
	function view(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
		
		$band = new ProductBand($_REQUEST['band']);
		
		$page = new Page(sprintf('<a href="product_bands.php">%s</a> &gt; Product Band Settings', $band->Name), sprintf('Edit Product Band Settings for Band Reference %s.', $band->Reference));
		$page->Display('header');
		
		
			$table = new DataTable('products');
			$table->SetSQL(sprintf("select p.Product_ID, p.Product_Title from product as p where p.Product_Band_ID=%d", $band->ID));
			$table->AddField('ID#', 'Product_ID', 'right');
			$table->AddField('Name', 'Product_Title', 'left');
	
			$table->AddLink("javascript:confirmRequest('product_band_settings.php?action=remove_product&confirm=true&pid=%s','Are you sure you want to remove this product from this band? IMPORTANT: removing this product may effect any active promotions or other settings using this product band.');", 
							"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", 
							"Product_ID");
							
			$table->SetMaxRows(25);
			$table->SetOrderBy("Product_ID");
			$table->Finalise();
			$table->DisplayTable();
			echo "<br>";
			$table->DisplayNavigation();
			echo "<br>";
			echo "<br>";
			echo sprintf('<input type="button" name="addProduct" value="add product to band" class="btn" onclick="window.location.href=\'product_band_settings.php?action=add_product&band=%d\'"> ', $band->ID);
			echo sprintf('<input type="button" name="addCategory" value="add category to band" class="btn" onclick="window.location.href=\'product_band_settings.php?action=add_category&band=%d\'"> ', $band->ID);
			echo sprintf('<input type="button" name="removeCategory" value="remove category from band" class="btn" onclick="window.location.href=\'product_band_settings.php?action=remove_category&band=%d\'"> ', $band->ID);

		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	function addProduct(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
		
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
		$band = new ProductBand($_REQUEST['band']);
		
		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('action', 'Action', 'hidden', 'add_product', 'alpha_numeric', 11, 11);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('band', 'Band ID', 'hidden', $_REQUEST['band'], 'numeric_unsigned', 1, 11);
		$form->AddField('product', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
		$form->AddField('name', 'Product', 'text', '', 'paragraph', 1, 60, false, 'onFocus="this.Blur();"');
		
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$band->AddProduct($form->GetValue('product'));
				 redirect(sprintf("Location: product_band_settings.php?band=%d", $form->GetValue('band')));
				exit;
			}
		}
		$page = new Page(sprintf('<a href="product_bands.php">%s</a> &gt; <a href="product_band_settings.php?band=%d">Product Band Settings</a> &gt; Add Product to Band', $band->Name, $band->ID), sprintf('Add another Product to Band Reference %s.', $band->Reference));
		
		$page->Display('header');
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		$window = new StandardWindow("Add a Band to Product.");
		$webForm = new StandardForm;
		echo $form->Open();
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('action');
		echo $form->GetHTML('band');
		echo $form->GetHTML('product');
		echo $window->Open();
		echo $window->AddHeader('Click the Magnifying Glass to search for a product from your catalogue.');
		echo $window->OpenContent();
		echo $webForm->Open();
		$temp_1 = '<a href="javascript:popUrl(\'product_search.php?serve=pop\', 500, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
		echo $webForm->AddRow($form->GetLabel('name') . $temp_1, $form->GetHTML('name') . '<input type="submit" name="add" value="add" class="btn" />');
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		echo "<br>";
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	function addCategory(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
		$band = new ProductBand($_REQUEST['band']);

		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('action', 'Action', 'hidden', 'add_category', 'alpha_numeric', 1, 20);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('band', 'Coupon ID', 'hidden', $_REQUEST['band'], 'numeric_unsigned', 1, 11);
		$form->AddField('parent', 'Category', 'hidden', '', 'numeric_unsigned', 1, 11);
		
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				$band->AddCategory($form->GetValue('parent'));
				 redirect(sprintf("Location: product_band_settings.php?band=%d", $form->GetValue('band')));
				exit;
			}
		}
		$page = new Page(sprintf('<a href="discount_coupons.php">%s</a> &gt; <a href="discount_coupon_settings.php?coupon=%d">Discount Coupon Settings</a> &gt; Add Category of Products to Coupon', $band->Name, $band->ID), sprintf('Add an entire Category of Products to Coupon Reference %s.', $band->Reference));
		
		$page->Display('header');
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		$window = new StandardWindow("Add a Category of Products to Coupon.");
		$webForm = new StandardForm;
		echo $form->Open();
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('action');
		echo $form->GetHTML('band');
		echo $form->GetHTML('parent');
		echo $window->Open();
		echo $window->AddHeader('Click on a the search icon to find a category.');
		echo $window->OpenContent();
		echo $webForm->Open();
		$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
		echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption"></span>&nbsp; &nbsp;<input type="submit" name="add" value="add" class="btn" />');
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		echo "<br>";
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	function removeProduct(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
		
		$band = new ProductBand;
		$band->ID = $_REQUEST['band'];
		
		if(isset($_REQUEST['confirm'])){
			$band->ResetProduct($_REQUEST['pid']);
		}
		redirect("Location: product_band_settings.php?band=" . $band->ID);
	}
	
	function removeCategory(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
		$band = new ProductBand($_REQUEST['band']);

		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('action', 'Action', 'hidden', 'remove_category', 'alpha_numeric', 1, 20);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('band', 'Band ID', 'hidden', $_REQUEST['band'], 'numeric_unsigned', 1, 11);
		$form->AddField('parent', 'Category', 'hidden', '', 'numeric_unsigned', 1, 11);
		
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				$band->ResetCategory($form->GetValue('parent'));
				 redirect(sprintf("Location: product_band_settings.php?band=%d", $form->GetValue('band')));
				 exit;
			}
		}
		$page = new Page(sprintf('<a href="product_bands.php">%s</a> &gt; <a href="product_band_settings.php?band=%d">Product Band Settings</a> &gt; Remove Category of Products from Band', $band->Name, $band->ID), sprintf('Remove an entire Category of Products from Band Reference %s.', $band->Reference));
		
		$page->Display('header');
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		$window = new StandardWindow("Remove a Category of Products from Band.");
		$webForm = new StandardForm;
		echo $form->Open();
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('action');
		echo $form->GetHTML('band');
		echo $form->GetHTML('parent');
		echo $window->Open();
		echo $window->AddHeader('Click on a the search icon to find a category.');
		echo $window->OpenContent();
		echo $webForm->Open();
		$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
		echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption"></span>&nbsp; &nbsp;<input type="submit" name="remove" value="remove" class="btn" />');
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		echo "<br>";
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>