<?php
require_once('../classes/ApplicationHeader.php');

$data = new DataQuery(sprintf("SELECT Enquiry_Template_ID, Title FROM enquiry_template WHERE Enquiry_Type_ID=0 OR Enquiry_Type_ID=%d ORDER BY Title ASC", mysql_real_escape_string($_REQUEST['id'])));
while($data->Row) {
	echo sprintf("%s{br}\n", $data->Row['Enquiry_Template_ID']);
	echo sprintf("%s{br}\n", $data->Row['Title']);
	echo "{br}{br}\n";

	$data->Next();
}
$data->Disconnect();

$GLOBALS['DBCONNECTION']->Close();
?>