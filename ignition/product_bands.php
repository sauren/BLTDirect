<?php
	require_once('lib/common/app_header.php');
	
	if($action == "add"){
		$session->Secure(3);
		add();
		exit;
	} elseif($action == "update"){
		$session->Secure(3);
		update();
		exit;
	} elseif($action == "remove"){
		$session->Secure(3);
		remove();
		exit;
	}else {
		$session->Secure(2);
		view();
		exit;
	}
	
	function add(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		
		// Generate Random
		
		$band = new ProductBand;
		
		$form = new Form("product_bands.php");
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('reference', 'Band Reference', 'text', '', 'alpha_numeric', 1, 16, true);
		$form->AddField('title', 'Band Title', 'text', '', 'paragraph', 1, 45, true);
		$form->AddField('description', 'Band Description', 'textarea', '', 'paragraph', 1, 255, true, 'style="width:90%;height:50px;"');
		
		
		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			$form->Validate();
			// Hurrah! Create a new entry.
			$band->Reference = strtoupper($form->GetValue('reference'));
			$band->Name = $form->GetValue('title');
			$band->Description = $form->GetValue('description');
			
			if($band->Exists()){
				$form->AddError('The Product Band Reference Already exists. Please try a different Reference.', 'reference');
			}
			
			if($form->Valid){
				$band->Add();				
				redirect(sprintf("Location: product_band_settings.php?band=%d", $band->ID));
				exit;
			}
		}
		
		$page = new Page('Add a Product Band','Product Bands are optional. You don\' have to use them however they offer the ability to quickly identify products by markup, type or another important factor in your business. Note: Only one band can be applied to a product.');
		$page->Display('header');
		
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		
		$window = new StandardWindow('Add');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference') . $form->GetIcon('reference'));
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
		
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_bands.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		
		
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	function update(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
				
		$band = new ProductBand();

		if(!$band->Get($_REQUEST['band'])) {
			redirectTo('?action=view');
		}
		
		$form = new Form("product_bands.php");
		$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('band', 'Band ID', 'hidden', $band->ID, 'numeric_unsigned', 1, 3, true);
		$form->AddField('reference', 'Band Reference', 'text', $band->Reference, 'alpha_numeric', 1, 15, true);
		$form->AddField('title', 'Band Title', 'text', $band->Name, 'paragraph', 1, 45, true);
		$form->AddField('description', 'Band Description', 'textarea', $band->Description, 'paragraph', 1, 255, true, 'style="width:90%;height:50px;"');
		
		
		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			$form->Validate();
			// Hurrah! Create a new entry.
			$band->Reference = strtoupper($form->GetValue('reference'));
			$band->Name = $form->GetValue('title');
			$band->Description = $form->GetValue('description');
						
			if($form->Valid){
				$band->Update();				
				redirect(sprintf("Location: product_bands.php?band=%d", $band->ID));
			}
		}
		
		$page = new Page('Edit Product Band','This Product Band allows you to allocate prducts to a type, or link to important common information.');
		$page->Display('header');
		
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		
		$window = new StandardWindow('Update');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('band');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference') . $form->GetIcon('reference'));
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));		
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'products_bands.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		
		
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	function remove(){
	}
	
	function view(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
		
		$page = new Page('Product Bands','Add Product Bands with your own reference numbers to categorise products by common important information or markup types.');
		$page->Display('header');
		
		echo "<br>";
		echo '<input type="button" name="add" value="add a new band" class="btn" onclick="window.location.href=\'product_bands.php?action=add\'">';
		echo "<br><br>";

		$table = new DataTable('bands');
		$table->SetSQL("select * from product_band");
		$table->AddField('ID#', 'Product_Band_ID', 'right');
		$table->AddField('Reference', 'Band_Ref', 'left');
		$table->AddField('Name', 'Band_Title', 'left');
		
		$table->AddLink("product_band_settings.php?band=%s", 
						"<img src=\"./images/folderopen.gif\" alt=\"Open Band\" border=\"0\">", 
						"Product_Band_ID");
		$table->AddLink("product_bands.php?action=update&band=%s", 
						"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Band\" border=\"0\">", 
						"Product_Band_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Product_Band_ID");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		echo "<br>";
		echo '<input type="button" name="add" value="add a new band" class="btn" onclick="window.location.href=\'product_bands.php?action=add\'">';
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>