<?php
ini_set('max_execution_time', '3000');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/UrlAlias.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$errors = array();

$urlAlias = new UrlAlias();
$urlAlias->Type = 'Product';

echo '<strong>' . $urlAlias->Type . '</strong><br />';

$data = new DataQuery(sprintf("SELECT Product_ID FROM product ORDER BY Product_ID ASC"));
while($data->Row) {
	echo $data->Row['Product_ID'].'<br />';

	$urlAlias->ReferenceID = $data->Row['Product_ID'];
	$urlAlias->Regenerate();

	for($i=0; $i<count($urlAlias->Error); $i++) {
		$errors[] = $urlAlias->Error[$i];
	}

	$data->Next();
}
$data->Disconnect();

echo '<br />';

$urlAlias->Type = 'Category';

echo '<strong>' . $urlAlias->Type . '</strong><br />';

$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories ORDER BY Category_ID ASC"));
while($data->Row) {
	echo $data->Row['Category_ID'].'<br />';

	$urlAlias->ReferenceID = $data->Row['Category_ID'];
	$urlAlias->Regenerate();

	for($i=0; $i<count($urlAlias->Error); $i++) {
		$errors[] = $urlAlias->Error[$i];
	}

	$data->Next();
}
$data->Disconnect();

echo '<br />';
echo '<strong>Errors</strong><br />';

for($i=0; $i<count($errors); $i++) {
	echo $errors[$i].'<br />';
}

echo '<br />';
echo '--FINISHED--';
?>