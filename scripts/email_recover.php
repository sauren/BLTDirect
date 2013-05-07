<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/EnquiryLine.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

/*$data = new DataQuery(sprintf("SELECT * FROM email_queue WHERE Email_Queue_ID>=14858 AND Email_Queue_ID<=14962 AND Subject LIKE '%%Enquiry Response%%' ORDER BY Email_Queue_ID DESC"));
while($data->Row) {
	if(preg_match('/\[#E[A-Z]([0-9]*)\]/', $data->Row['Subject'], $matches)) {
		$enquiryId = $matches[1];

		$body = $data->Row['Body'];

		if($pos = strpos($body, '<p>Dear')) {
			$body = substr($body, $pos, strlen($body));

			$pos = strpos($body, '</p>');
			$body = trim(substr($body, $pos+4, strlen($body)));

			$pos = strpos($body, '<p>Please log into your account and your');
			$body = trim(substr($body, 0, $pos));

			$line = new EnquiryLine();
			$line->Enquiry->ID = $enquiryId;
			$line->Message = $body;
			$line->Add();

			echo $enquiryId.'<br />';
		}
	}

	$data->Next();
}
$data->Disconnect();*/

/*$data = new DataQuery(sprintf("SELECT * FROM email_queue WHERE Email_Queue_ID>=14858 AND Email_Queue_ID<=14967 AND Subject LIKE '%%Enquiry Closed%%' ORDER BY Email_Queue_ID DESC"));
while($data->Row) {
	if(preg_match('/\[#E[A-Z]([0-9]*)\]/', $data->Row['Subject'], $matches)) {
		$enquiryId = $matches[1];

		new DataQuery(sprintf("UPDATE enquiry SET Status='Closed' WHERE Enquiry_ID=%d", $enquiryId));

		echo $enquiryId.'<br />';
	}

	$data->Next();
}
$data->Disconnect();*/
?>