<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Geozone.php');

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
	if(isset($_REQUEST['geo']) && is_numeric($_REQUEST['geo'])){
		$geozone = new Geozone;
		$geozone->Delete($_REQUEST['geo']);
	}

	redirect("Location: geozone.php");
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Geozone Title', 'text', '', 'alpha_numeric', 3, 60);
	$form->AddField('description', 'Description', 'textarea', '', 'paragraph', 3, 255, false, 'style="width:100%; height:100px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$geozone = new Geozone;
			$geozone->Name = $form->GetValue('name');
			$geozone->Description = $form->GetValue('description');
			$geozone->Add();
			redirect("Location: geozone.php");
		}
	}

	$page = new Page('<a href="geozone.php">Geozones</a> &gt; Add a New Geozone','Please name Geozones so they are immediately recognisable. For instance a UK Taxation Geozone could be written as &quot;Tax UK&quot; or a UK zoned Shipping could be &quot;Shipping UK Zone A&quot;.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Add Geozone');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'geozone.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('geo', 'Geozone ID', 'hidden', $geozone->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Geozone Title', 'text', $geozone->Name, 'alpha_numeric', 3, 60);
	$form->AddField('description', 'Description', 'textarea', $geozone->Description, 'paragraph', 3, 255, false, 'style="width:100%; height:100px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$geozone->Name = $form->GetValue('name');
			$geozone->Description = $form->GetValue('description');
			$geozone->Update();
			redirect("Location: geozone.php");
		}
	}

	$page = new Page('<a href="geozone.php">Geozones</a> &gt; Update a Geozone','Please name Geozones so they are immediately recognisable. For instance a UK Taxation Geozone could be written as &quot;Tax UK&quot; or a UK zoned Shipping could be &quot;Shipping UK Zone A&quot;.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Update Geozone');
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('geo');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'geozone.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Geozones','Geozones are used wherever country/regional zoning is useful or required. For instance, your Tax and Shipping settings will use Geozones to identify regions where tax or shipping areas apply.');

	$page->Display('header');
	$sql = sprintf("SELECT * from geozone");
	$table = new DataTable("geozone");
	$table->SetSQL($sql);
	$table->AddField('Geozone ID#', 'Geozone_ID', 'right');
	$table->AddField('Title', 'Geozone_Title', 'left');
	$table->AddLink("geozone_assoc.php?geo=%s",
							"<img src=\"./images/folderopen.gif\" alt=\"Update Geozone Associations\" border=\"0\">",
							"Geozone_ID");
	$table->AddLink("geozone.php?action=update&geo=%s",
							"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Geozone Settings\" border=\"0\">",
							"Geozone_ID");
	$table->AddLink("javascript:confirmRequest('geozone.php?action=remove&confirm=true&geo=%s','Are you sure you want to remove this Geozone?');",
							"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
							"Geozone_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Geozone_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add a new geozone" class="btn" onclick="window.location.href=\'geozone.php?action=add\'">';
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>