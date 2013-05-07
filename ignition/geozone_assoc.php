<?php
/*
geozone_assoc.php
Version 1.0

Ignition, eBusiness Solution
http://www.deveus.com

Copyright (c) Deveus Software, 2004
All Rights Reserved.

Notes:
*/
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/GeozoneAssoc.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	if(isset($_REQUEST['assoc']) && is_numeric($_REQUEST['assoc'])){
		$assoc = new GeozoneAssoc;
		$assoc->Delete($_REQUEST['assoc']);
		redirect(sprintf("Location: geozone_assoc.php?geo=%d", $_REQUEST['geo']));
		exit;
	} else {
		view();
	}
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$geozone = new Geozone($_REQUEST['geo']);

	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('geo', 'Geozone', 'hidden', $_REQUEST['geo'], 'numeric_unsigned', 1, 11);

	// Add country options
	$form->AddField('country', 'Country', 'select', $GLOBALS['SYSTEM_COUNTRY'], 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
	$form->AddOption('country', '0', '');
	$form->AddOption('country', '222', 'United Kingdom');

	$country = new DataQuery(sprintf("select Country_ID, Country from countries order by Country"));
	while($country->Row){
		$form->AddOption('country', $country->Row['Country_ID'], $country->Row['Country']);
		$country->Next();
	}
	$country->Disconnect();
	unset($country);

	$form->AddField('region', 'Region', 'select', '0', 'numeric_unsigned', 1, 11);
	$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($form->GetValue('country'))));
	$form->AddOption('region', 0, 'All Regions/Subdivisions');
	while($region->Row){
		$form->AddOption('region', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
	$region->Disconnect();
	unset($region);

	// Check if the form has been submitted

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			// Hurrah! Create a new entry.
			$assoc = new GeozoneAssoc;
			$assoc->Parent->ID = $form->GetValue('geo');
			$assoc->Country->ID = $form->GetValue('country');
			$assoc->Region->ID = $form->GetValue('region');
			$assoc->Add();
			redirect(sprintf("Location: geozone_assoc.php?geo=%d", $form->GetValue('geo')));
			exit;
		}
	}

	$page = new Page(sprintf('<a href="geozone.php">Geozones</a> &gt; <a href="geozone_assoc.php?geo=%d">%s</a> &gt; Add Association', $form->GetValue('geo'), $geozone->Name),'Please name select a Country and a Region or the &quot;All Regions&quot; option available at the top of the regions list. Regions will display according to the Country selected. Regions for Countries you do not allow sales to will not be shown for efficiency.');
	$page->LinkScript('./js/regions.php');
	$page->Display('header');

	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Geozone Association');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('geo');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('country'), $form->GetHTML('country') . $form->GetIcon('country'));
	echo $webForm->AddRow($form->GetLabel('region'), $form->GetHTML('region') . $form->GetIcon('region'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'geozone_assoc.php?geo=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetValue('geo'), $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$geozone = new Geozone($_REQUEST['geo']);
	$assoc = new GeozoneAssoc($_REQUEST['assoc']);

	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('geo', 'Geozone', 'hidden', $geozone->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('assoc', 'Geozone Association', 'hidden', $assoc->ID, 'numeric_unsigned', 1, 11);

	$form->AddField('country', 'Country', 'select', $assoc->Country->ID, 'numeric_unsigned', 1, 11, true, 'onChange="propogateRegions(\'region\', this);"');
	$country = new DataQuery("select Country_ID, Country from countries order by Country");
	$form->AddOption('country', '0', 'All Countries');
	while($country->Row){
		$form->AddOption('country', $country->Row['Country_ID'], $country->Row['Country']);
		$country->Next();
	}
	$country->Disconnect();
	unset($country);

	$form->AddField('region', 'Region', 'select', $assoc->Region->ID, 'numeric_unsigned', 1, 11);
	$region = new DataQuery(sprintf("select Region_ID, Region_Name from regions where Country_ID=%d order by Region_Name asc", mysql_real_escape_string($form->GetValue('country'))));
	$form->AddOption('region', 0, 'All Regions/Subdivisions');
	while($region->Row){
		$form->AddOption('region', $region->Row['Region_ID'], $region->Row['Region_Name']);
		$region->Next();
	}
	$region->Disconnect();
	unset($region);

	// Check if the form has been submitted

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$assoc->Country->ID = $form->GetValue('country');
			$assoc->Region->ID = $form->GetValue('region');
			$assoc->Update();
			redirect(sprintf("Location: geozone_assoc.php?geo=%d", $form->GetValue('geo')));
			exit;
		}
	}

	$page = new Page(sprintf('<a href="geozone.php">Geozones</a> &gt; <a href="geozone_assoc.php?geo=%d">%s</a> &gt; Update Association', $form->GetValue('geo'), $geozone->Name),'Please name select a Country and a Region or the &quot;All Regions&quot; option available at the top of the regions list. Regions will display according to the Country selected. Regions for Countries you do not allow sales to will not be shown for efficiency.');
	$page->LinkScript('./js/regions.php');
	$page->Display('header');

	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Update Geozone Association');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('geo');
	echo $form->GetHTML('assoc');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('country'), $form->GetHTML('country') . $form->GetIcon('country'));
	echo $webForm->AddRow($form->GetLabel('region'), $form->GetHTML('region') . $form->GetIcon('region'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'geozone_assoc.php?geo=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetValue('geo'), $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	$geozone = new Geozone($_REQUEST['geo']);

	$page = new Page(sprintf('<a href="geozone.php">Geozones</a> &gt; %s', $geozone->Name),'Geozones are used wherever country/regional zoning is useful or required.');

	$page->Display('header');
	$sql = sprintf("SELECT g.Geozone_Assoc_ID, g.Geozone_ID, c.Country, r.Region_Name
						FROM geozone_assoc as g
						left join countries as c on
						g.Country_ID=c.Country_ID
						left join regions as r on
						g.Region_ID=r.Region_ID
						where g.Geozone_ID=%d", mysql_real_escape_string($geozone->ID));
	$table = new DataTable("assoc");
	$table->SetSQL($sql);
	$table->AddField('ID#', 'Geozone_Assoc_ID', 'right');
	$table->AddField('Country', 'Country', 'left');
	$table->AddField('Region', 'Region_Name', 'left');
	$table->AddLink("geozone_assoc.php?action=update&assoc=%s",
	"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",
	"Geozone_Assoc_ID");
	$table->AddLink("javascript:confirmRequest('geozone_assoc.php?action=remove&confirm=true&assoc=%s','Are you sure you want to remove this Geozone Association?');",
	"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
	"Geozone_Assoc_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Country");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new association" class="btn" onclick="window.location.href=\'geozone_assoc.php?geo=%d&action=add\'">',  $geozone->ID);
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>