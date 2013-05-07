<?php
/*
	regions.php
	Version 1.0
	
	Ignition, eBusiness Solution
	http://www.deveus.com
	
	Copyright (c) Deveus Software, 2004
	All Rights Reserved.
	
	Notes:
*/
	require_once('lib/common/app_header.php');
	
	if($action == 'add'){
		$session->Secure(3);
		if(isset($_REQUEST['ctry'])){
			add();
		} else {
			redirect("Location: countries.php");
		}
		exit;
	} elseif($action == 'remove'){
		$session->Secure(3);
		if(isset($_REQUEST['ctry'])){
			remove();
		} else {
			redirect("Location: countries.php");
		}
		exit;
	} elseif($action == 'update'){
		$session->Secure(3);
		if(isset($_REQUEST['ctry'])){
			update();
		} else {
			redirect("Location: countries.php");
		}
		exit;
	} else {
		if(isset($_REQUEST['ctry'])){
			view();
		} else {
			redirect("Location: countries.php");
		}
		exit;
	}
	
/*
	///////////////////////////////////////////
	Function:	remove()
	Author:		Geoff Willings
	Date:		22 Mar 2005
	///////////////////////////////////////////
*/
	function remove(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == 'true'){
			if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
				$region = new Region;
				$region->Remove($_REQUEST['id']);
			}
		}
		redirect(sprintf("Location: regions.php?action=view%s", extractVars("action,id,confirm")));
	}
/*
	///////////////////////////////////////////
	Function:	add()
	Author:		Geoff Willings
	Date:		22 Mar 2005
	///////////////////////////////////////////
*/
	function add(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
		
		/* Get Country Information */
		$country = new Country($_REQUEST['ctry']);
		
		/* Initiate the Form */
		$form = new Form("regions.php");
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('ctry', 'Country ID', 'hidden', $country->ID, 'numeric_unsigned', 1, 11);
		$form->AddField('name', 'Country Subdivision (County/State/Province)', 'text', '', 'paragraph', 1, 150);
		$form->AddField('code', 'Subdivision Code (i.e. TX for Texas)', 'text', '', 'alpha_numeric', 1, 4);
		
		
		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$region = new Region;
				$region->Name = $form->GetValue('name');
				$region->CountryID = $form->GetValue('ctry');
				$region->Code = $form->GetValue('code');
				$region->Add();
													
				redirect(sprintf("Location: regions.php?ctry=%d", $country->ID));
				exit;
			}
		}
		
		
		/* Start Page */
		$page = new Page(sprintf("Adding Regions for %s", $country->Name),
								"You are about to add a new Region to a Country.");
		$page->SetFocus("name");
		$page->Display('header');
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		
		$window = new StandardWindow(sprintf('Add Region to %s', $country->Name));
		
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('ctry');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow("Country", $country->Name);
		echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
		echo $webForm->AddRow($form->GetLabel('code'), $form->GetHTML('code') . $form->GetIcon('code'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back to regions" value="back to regions" class="btn" onclick="window.location.href=\'regions.php?action=view%s\'"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', extractVars('action'), $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
/*
	///////////////////////////////////////////
	Function:	update()
	Author:		Geoff Willings
	Date:		22 Mar 2005
	///////////////////////////////////////////
*/
	function update(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
		
		/* Get Country Information */
		$country = new Country($_REQUEST['ctry']);
		$region = new Region($_REQUEST['id']);
		
		/* Initiate the Form */
		$form = new Form("regions.php");
		$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('ctry', 'Country ID', 'hidden', $country->ID, 'numeric_unsigned', 1, 11);
		$form->AddField('id', 'Region ID', 'hidden', $region->ID, 'numeric_unsigned', 1, 11);
		$form->AddField('name', 'Country Subdivision (County/State/Province)', 'text', $region->Name, 'paragraph', 1, 150);
		$form->AddField('code', 'Subdivision Code (i.e. TX for Texas)', 'text', $region->Code, 'alpha_numeric', 1, 4);
		
		
		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$region->Name = $form->GetValue('name');
				$region->CountryID = $form->GetValue('ctry');
				$region->Code = $form->GetValue('code');
				$region->Update();
				redirect(sprintf("Location: regions.php?ctry=%d", $country->ID));
				exit;
			}
		}
		
		
		/* Start Page */
		$page = new Page(sprintf("Updating %s in %s", $region->Name, $country->Name),
								"You are updating a Region of a Country. This will be reflected where regions are applied i.e. address book entries will be updated.");
		$page->SetFocus("name");
		$page->Display('header');
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		
		$window = new StandardWindow(sprintf('Update Region in %s', $country->Name));
		
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('ctry');
		echo $form->GetHTML('id');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow("Country", $country->Name);
		echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
		echo $webForm->AddRow($form->GetLabel('code'), $form->GetHTML('code') . $form->GetIcon('code'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back to regions" value="back to regions" class="btn" onclick="window.location.href=\'regions.php?action=view%s\'"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', extractVars('action'), $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
/*
	///////////////////////////////////////////
	Function:	view()
	Author:		Geoff Willings
	Date:		22 Mar 2005
	///////////////////////////////////////////
*/
	function view(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
		
		/* Get Access Level Name */
		$country = new country($_REQUEST['ctry']);
		
		/* Start Page */
		$page = new Page(sprintf("Regions for %s", $country->Name),
								"Use the navigation bar at the bottom of the list below to navigate to more regions.");
								
		$page->Display('header');
				
		$table = new DataTable('regions');
		$table->SetSQL(sprintf("SELECT * from regions WHERE Country_ID = %d", $country->ID));
						
		$table->AddField('ID#', 'Region_ID', 'right');
		$table->AddField('Region Name', 'Region_Name', 'left');
		$table->AddField('Code', 'Region_Code', 'left');
		$table->AddLink("regions.php?action=update&id=%s", 
						"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">", 
						"Region_ID");
		$table->AddLink("javascript:confirmRequest('regions.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this region from this country? If you are unsure please contact your administrator.');", 
						"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", 
						"Region_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Region_Name");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		echo '<input type="button" name="back to countries" value="back to countries" class="btn" onclick="window.location.href=\'countries.php\'"> ';
		echo sprintf('<input type="button" name="add a region" value="add a region" class="btn" onclick="window.location.href=\'regions.php?action=add%s\'">', extractVars('action'));
		
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>