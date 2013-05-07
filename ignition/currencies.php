<?php
/*
	currencies.php
	Version 1.0
	
	Ignition, eBusiness Solution
	http://www.deveus.com
	
	Copyright (c) Deveus Software, 2004
	All Rights Reserved.
	
	Notes:
*/
	require_once('lib/common/app_header.php');
	
	if($action == "add"){
		$session->Secure(3);
		addCurrencies();
		exit;
	} elseif($action == "update"){
		$session->Secure(3);
		updateCurrencies();
		exit;
	} elseif($action == "remove"){
		$session->Secure(3);
		removeCurrencies();
		exit;
	} else {
		$session->Secure(2);
		viewCurrencies();
		exit;
	}

/*
	///////////////////////////////////////////
	Function:	removeCurrencies()
	Author:		Geoff Willings
	Date:		05 Feb 2005
	///////////////////////////////////////////
*/
	function removeCurrencies(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Currency.php');
		if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
			$currency = new Currency;
			$currency->ID = $_REQUEST['id'];
			$currency->Remove();
			redirect("Location: currencies.php");
			exit;
		} else {
			viewCurrencies();
		}
	}

/*
	///////////////////////////////////////////
	Function:	addCurrencies()
	Author:		Geoff Willings
	Date:		05 Feb 2005
	///////////////////////////////////////////
*/
	function addCurrencies(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Currency.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		
		$form = new Form("currencies.php");
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('currency', 'Currency Name', 'text', '', 'alpha_numeric', 3, 32);
		$form->AddField('code', 'ISO Code', 'text', '', 'alpha', 3, 3);
		$form->AddField('symbolLeft', 'Left Symbol', 'text', '', 'paragraph', 1, 12, false);
		$form->AddField('symbolRight', 'Right Symbol', 'text', '', 'paragraph', 1, 12, false);
		$form->AddField('decimalSymbol', 'Decimal Symbol', 'text', '.', 'paragraph', 1, 1);
		$form->AddField('thousandsPoint', 'Thousands Point', 'text', ',', 'paragraph', 1, 1);
		$form->AddField('decimalPlaces', 'Decimal Places', 'text', '2', 'numeric_unsigned', 1, 1);
		$form->AddField('value', 'Exchange Rate', 'text', '', 'float', 1, 13);
			
		// Check if the form has been submitted
		
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$currency = new Currency;
				$currency->Name = $form->GetValue('currency');
				$currency->Code = $form->GetValue('code');
				$currency->SymbolLeft = $form->GetValue('symbolLeft');
				$currency->SymbolRight = $form->GetValue('symbolRight');
				$currency->DecimalPoint = $form->GetValue('decimalSymbol');
				$currency->ThousandsPoint = $form->GetValue('thousandsPoint');
				$currency->DecimalPlaces = $form->GetValue('decimalPlaces');
				$currency->Value = $form->GetValue('value');
				$currency->Add();
				redirect("Location: currencies.php");
				exit;
			}
		}
		
		$page = new Page('Add a New Currency','Please complete the form below.');
		$page->Display('header');
		
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		
		$window = new StandardWindow('Update Currency');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('currency'), $form->GetHTML('currency') . $form->GetIcon('currency'));
		echo $webForm->AddRow($form->GetLabel('code'), $form->GetHTML('code') . $form->GetIcon('code'));
		echo $webForm->AddRow($form->GetLabel('symbolLeft'), $form->GetHTML('symbolLeft') . $form->GetIcon('symbolLeft'));
		echo $webForm->AddRow($form->GetLabel('symbolRight'), $form->GetHTML('symbolRight') . $form->GetIcon('symbolRight'));
		echo $webForm->AddRow($form->GetLabel('decimalSymbol'), $form->GetHTML('decimalSymbol') . $form->GetIcon('decimalSymbol'));
		echo $webForm->AddRow($form->GetLabel('thousandsPoint'), $form->GetHTML('thousandsPoint') . $form->GetIcon('thousandsPoint'));
		echo $webForm->AddRow($form->GetLabel('decimalPlaces'), $form->GetHTML('decimalPlaces') . $form->GetIcon('decimalPlaces'));
		echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $form->GetIcon('value'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'currencies.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		$page->Display('footer');
require_once('lib/common/app_footer.php');
		
	}

/*
	///////////////////////////////////////////
	Function:	updateCurrencies()
	Author:		Geoff Willings
	Date:		04 Feb 2005
	///////////////////////////////////////////
*/
	function updateCurrencies(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Currency.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		
		$currency = new Currency($_REQUEST['id']);
		$form = new Form("currencies.php");
		$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('id', 'ID', 'hidden', $currency->ID, 'numeric_unsigned', 1, 11);
		$form->AddField('currency', 'Currency Name', 'text', $currency->Name, 'alpha_numeric', 3, 32);
		$form->AddField('code', 'ISO Code', 'text', $currency->Code, 'alpha', 3, 3);
		$form->AddField('symbolLeft', 'Left Symbol', 'text', $currency->SymbolLeft, 'paragraph', 1, 12, false);
		$form->AddField('symbolRight', 'Right Symbol', 'text', $currency->SymbolRight, 'paragraph', 1, 12, false);
		$form->AddField('decimalSymbol', 'Decimal Symbol', 'text', $currency->DecimalPoint, 'paragraph', 1, 1);
		$form->AddField('thousandsPoint', 'Thousands Point', 'text', $currency->ThousandsPoint, 'paragraph', 1, 1);
		$form->AddField('decimalPlaces', 'Decimal Places', 'text', $currency->DecimalPlaces, 'numeric_unsigned', 1, 1);
		$form->AddField('value', 'Exchange Rate', 'text', $currency->Value, 'float', 1, 13);
			
		// Check if the form has been submitted
		
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$currency->Name = $form->GetValue('currency');
				$currency->Code = $form->GetValue('code');
				$currency->SymbolLeft = $form->GetValue('symbolLeft');
				$currency->SymbolRight = $form->GetValue('symbolRight');
				$currency->DecimalPoint = $form->GetValue('decimalSymbol');
				$currency->ThousandsPoint = $form->GetValue('thousandsPoint');
				$currency->DecimalPlaces = $form->GetValue('decimalPlaces');
				$currency->Value = $form->GetValue('value');
				$currency->Update();
				redirect("Location: currencies.php");
				exit;
			}
		}
		
		
		
		$page = new Page('Updating Currency Settings','Be aware that changes to regional and country settings may affect other areas of your system. If you are unsure please always contact your administrator before making changes.');
		$page->Display('header');
		
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		
		$window = new StandardWindow('Update Currency');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('id');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('currency'), $form->GetHTML('currency') . $form->GetIcon('currency'));
		echo $webForm->AddRow($form->GetLabel('code'), $form->GetHTML('code') . $form->GetIcon('code'));
		echo $webForm->AddRow($form->GetLabel('symbolLeft'), $form->GetHTML('symbolLeft') . $form->GetIcon('symbolLeft'));
		echo $webForm->AddRow($form->GetLabel('symbolRight'), $form->GetHTML('symbolRight') . $form->GetIcon('symbolRight'));
		echo $webForm->AddRow($form->GetLabel('decimalSymbol'), $form->GetHTML('decimalSymbol') . $form->GetIcon('decimalSymbol'));
		echo $webForm->AddRow($form->GetLabel('thousandsPoint'), $form->GetHTML('thousandsPoint') . $form->GetIcon('thousandsPoint'));
		echo $webForm->AddRow($form->GetLabel('decimalPlaces'), $form->GetHTML('decimalPlaces') . $form->GetIcon('decimalPlaces'));
		echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $form->GetIcon('value'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'currencies.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		$page->Display('footer');
require_once('lib/common/app_footer.php');
		
	}

/*
	///////////////////////////////////////////
	Function:	viewCurrencies()
	Author:		Geoff Willings
	Date:		04 Feb 2005
	///////////////////////////////////////////
*/
	function viewCurrencies(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
		
		$page = new Page('Currency Settings','This area allows you to maintain multiple currencies for your system. By default UK Pounds is the system currency and has a value of 1.00.');
		$page->Display('header');
		$table = new DataTable('currencies');
		$table->SetSQL("select * from currencies");
		$table->AddField('ID#', 'Currency_ID', 'right');
		$table->AddField('ISO Code', 'Code', 'left');
		$table->AddField('Currency', 'Currency', 'left');
		$table->AddField('Symbol', 'Symbol_Left', 'center');
		$table->AddField('Exchange Rate', 'Value', 'right');
		$table->AddLink("currencies.php?action=update&id=%s", 
						"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">", 
						"Currency_ID");
		$table->AddLink("javascript:confirmRequest('currencies.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this currency? IMPORTANT: removing a currency may affect other site settings. If you are unsure please contact your administrator.');", 
						"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", 
						"Currency_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Currency");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		echo "<br>";
		echo '<input type="button" name="add" value="add a new currency" class="btn" onclick="window.location.href=\'currencies.php?action=add\'">';
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>
