<?php
/*
	product_basic.php
	Version 1.0
	
	Ignition, eBusiness Solution
	http://www.deveus.com
	
	Copyright (c) Deveus Software, 2004
	All Rights Reserved.
	
	Notes:
*/
	require_once('lib/common/app_header.php');
	
	if($action == 'add'){
	} elseif($action == 'update'){
		if(isset($_REQUEST['pid'])){
			$session->Secure(3);
			update();
			exit;
		} else {
			view();
			exit;
		}
	} else {
		view();
		exit;
	}
	
	function add(){
	}
	
	function update(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		
		$product = new Product($_REQUEST['pid']);
		
		$form = new Form("product_meta.php");
		$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('pid', 'ID', 'hidden', $product->ID, 'numeric_unsigned', 1, 11);
		$form->AddField('title', 'Meta Title', 'text', $product->MetaTitle, 'paragraph', 1, 255, false);
		$form->AddField('description', 'Meta Description', 'textarea', $product->MetaDescription, 'paragraph', 1, 255, false, 'style="width:90%"');
		$form->AddField('keywords', 'Meta Keywords', 'textarea', $product->MetaKeywords, 'paragraph', 1, 255, false, 'style="width:90%"');
		
		
		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$product->MetaTitle = $form->GetValue('title');
				$product->MetaDescription = $form->GetValue('description');
				$product->MetaKeywords = $form->GetValue('keywords');
				$product->Update();
				redirect(sprintf("Location: product_profile.php?pid=%d", $product->ID));
				exit;
			}
		}
		
		$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> > Meta Information', $_REQUEST['pid']),'The more information you supply the better your system will become');
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
		echo $form->GetHTML('pid');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
		echo $webForm->AddRow($form->GetLabel('keywords'), $form->GetHTML('keywords') . $form->GetIcon('keywords'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_profile.php?pid=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $product->ID, $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		
		
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	function view(){
		echo "Error: An error occured. Either the action or id was not supplied.";
	}
?>
