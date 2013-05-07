<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CacheFile.php');

error_reporting(E_ALL);
ini_set("display_errors", true);
//var_dump(ini_get("memory_limit")); //,"56mb");
ini_set('memory_limit', '256M');

$session->secure(2);

view();
exit;

function view(){
	$serve = (isset($_REQUEST['serve']))?$_REQUEST['serve']:'view';

	$page = new Page('Manual Cache Expire','');
	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	
	$form->AddField('group', '', 'select', '', 'paragraph', 1, 255);

	foreach (CacheFile::findFileTypes() as $type) {
		$form->AddOption('group', $type, ucwords(str_replace("_", " ", $type)));
	}

	$form->AddField('days', '', 'select', '', 'numeric_unsigned', 1, 3);

	foreach (range(0,100) as $day) {
		$form->AddOption('days', $day, $day);
	}


	$window = new StandardWindow("Search for a Product.");
	$webForm = new StandardForm;

	if(isset($_REQUEST['confirm']) && !empty($_REQUEST['confirm']) && $form->Validate()) {
		CacheFile::expire($form->GetValue("group"), $form->GetValue("days"));
		redirect(sprintf("Location: cache_expire.php"));
	}

	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow("Expire...", $form->GetHTML('group') . " more than " . $form->GetHTML('days') . 'days old &nbsp; <input type="submit" value="expire now" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}