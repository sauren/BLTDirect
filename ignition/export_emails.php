<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
view();

/*
///////////////////////////////////////////
Function:	view()
Author:		Geoff Willings
Date:		07 Feb 2005
///////////////////////////////////////////
*/
function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);


	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){

			export();
			exit();
		}
	}
	$page = new Page('Export Emails to CSV', '');

	$page->Display('header');
	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Export the Email Addresses stored on the system");
	$webForm = new StandardForm;
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('','Exporting the email address will export the people, organisation and branch emails');
	echo $webForm->AddRow('&nbsp;','<input type="submit" name="export" value="export" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}


function export(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Person.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Organisation.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Branch.php');
	$fileDate = getDatetime();
	$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

	$filename = "ignition_email_export_" . $fileDate.".xls";

	// Set File Headers
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

	if((is_integer(strpos($userAgent, "msie"))) && (is_integer(strpos($userAgent, "win")))){
		header("Content-Disposition: filename=" . basename($filename) . ";");
	} else {
		header("Content-Disposition: attachment; filename=" . basename($filename) . ";");
	}
	header("Content-Transfer-Encoding: binary");

	getFolderContent();

}

function getFolderContent(){

	$sql = sprintf("select * FROM contact c INNER JOIN person p ON p.Person_ID = c.Person_ID WHERE c.Is_Active = 'Y' AND c.Is_Customer = 'Y' ");

	$products = new DataQuery($sql);
	while($products->Row){
		$line = array();
		$person = new Person($products->Row['Person_ID']);
		$line[] = $person->Email ;

		print(getCsv($line));
		unset($line);

		$products->Next();
	}
	$products->Disconnect();
}

function getCsv($row, $fd=',', $quot='"'){
	$str ='';
	foreach($row as $cell){
		$cell = str_replace($quot, $quot.$quot, $cell);

		if (strchr($cell, $fd) !== FALSE || strchr($cell, $quot) !== FALSE || strchr($cell, "\n") !== FALSE) {
			$str .= $quot.$cell.$quot.$fd;
		}
		else {
			$str .= $cell.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}

function outputColumns(){
	$line[] = 'Email';

	print(getCsv($line));
}
?>