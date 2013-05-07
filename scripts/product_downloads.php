<?php
ini_set('max_execution_time', '90000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');

$data = new DataQuery(sprintf("SELECT pd.id, pd.file, p.Product_Title AS name FROM product_download AS pd INNER JOIN product AS p ON p.Product_ID=pd.productId ORDER BY pd.id ASC"));
while($data->Row) {
	$file = new IFile();
	$file->OnConflict = 'makeunique';
	$file->SetDirectory($GLOBALS['PRODUCT_DOWNLOAD_DIR_FS']);
	$file->Extensions = '';
	$file->SetName($data->Row['file']);

	$file->Rename(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $data->Row['name'])) . '.' . $file->Extension);

	new DataQuery(sprintf("UPDATE product_download SET file='%s' WHERE id=%d", $file->FileName, $data->Row['id']));
		
	$data->Next();
}
$data->Disconnect();