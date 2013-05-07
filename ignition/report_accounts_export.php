<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('manager', 'Account Manager', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('manager', '', '');

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Person_Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN contact AS c ON c.Account_Manager_ID=u.User_ID GROUP BY u.User_ID ORDER BY Person_Name ASC"));
	while($data->Row) {
		$form->AddOption('manager', $data->Row['User_ID'], $data->Row['Person_Name']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			redirect(sprintf("Location: %s?action=report&accountmanagerid=%d&date=%s", $_SERVER['PHP_SELF'], $form->GetValue('manager'), $form->GetValue('date')));
		}
	}

	$page = new Page('Accounts Export Report', 'Please choose an account manager for your report');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Accounts.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select the account manager and date to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('manager'), $form->GetHTML('manager'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$accountManagerId = isset($_REQUEST['accountmanagerid']) ? $_REQUEST['accountmanagerid'] : 0;

	$fileName = sprintf('account_turnover_export_%s.csv', date('Ymd'));

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Disposition: filename=" . basename($fileName) . ";");
	header("Content-Transfer-Encoding: binary");

	echo getCsv(array('Customer ID', 'Customer Name', 'Average Turnover'));

	$data = new DataQuery(sprintf("SELECT cu.Customer_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', og.Org_Name, ')')) AS Customer_Name, SUM(o.Total) / COUNT(o.Order_ID) AS Average_Turnover FROM customer AS cu INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS og ON og.Org_ID=c2.Org_ID LEFT JOIN orders AS o ON cu.Customer_ID=o.Customer_ID AND o.Order_Prefix='T' WHERE c.Account_Manager_ID=%d GROUP BY cu.Customer_ID ORDER BY Customer_Name ASC", mysql_real_escape_string($accountManagerId)));
	while($data->Row) {
		echo getCsv(array($data->Row['Customer_ID'], $data->Row['Customer_Name'], number_format($data->Row['Average_Turnover'], 2, '.', '')));

		$data->Next();
	}
	$data->Disconnect();

	require_once('lib/common/app_footer.php');
}

function getCsv($row, $fd=',', $quot='"'){
	$str ='';

	foreach($row as $cell){
		$cell = str_replace($quot, $quot.$quot, $cell);

		if (strchr($cell, $fd) !== FALSE || strchr($cell, $quot) !== FALSE || strchr($cell, "\n") !== FALSE) {
			$str .= $quot.$cell.$quot.$fd;
		}
		else {
			$str .= $quot.$cell.$quot.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}
?>