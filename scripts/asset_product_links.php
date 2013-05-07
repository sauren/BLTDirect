<?php
ini_set('max_execution_time', '3600');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Asset.php');

$data = new DataQuery(sprintf("SELECT * FROM product_link WHERE assetId=0 ORDER BY id ASC"));
while($data->Row) {
	if(file_exists($GLOBALS['PRODUCT_LINK_IMAGES_DIR_FS'] . $data->Row['image'])) {
		$asset = new Asset();
		$asset->name = $data->Row['image'];
		$asset->data = file_get_contents($GLOBALS['PRODUCT_LINK_IMAGES_DIR_FS'] . $data->Row['image']);
		$asset->add();
		
		new DataQuery(sprintf("UPDATE product_link SET assetId=%d WHERE id=%d", $asset->id, $data->Row['id']));
	}
	
	echo sprintf('%s<br />', $data->Row['id']);
	
	$data->Next();
}
$data->Disconnect();