<?php
ini_set('max_execution_time', '90000');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

function getNode($nodeId, $items = array()) {
	$data = new DataQuery(sprintf("SELECT Node_ID, Url, Target FROM treemenu WHERE Parent_ID=%d", $nodeId));
	while($data->Row) {
		if($data->Row['Target'] == 'i_content_display') {
			$url = $data->Row['Url'];

			if(($pos = strpos($url, '?')) !== false) {
				$url = substr($url, 0, $pos);
			}

			if(!empty($url)) {
				$items[$url] = true;
			}
		}

		$items = getNode($data->Row['Node_ID'], $items);
			
		$data->Next();
	}
	$data->Disconnect();

	return $items;
}

$menus = array();

$data = new DataQuery(sprintf("SELECT Node_ID, Caption FROM treemenu WHERE Parent_ID=0"));
while($data->Row) {
	$item = array();
	$item['Name'] = $data->Row['Caption'];
	$item['Options'] = getNode($data->Row['Node_ID']);

	$menus[] = $item;
		
	$data->Next();
}
$data->Disconnect();

foreach($menus as $menu) {
	if(!empty($menu['Options'])) {
		$data = new DataQuery(sprintf("INSERT INTO access_levels (Access_Level) VALUES ('%s')", mysql_real_escape_string($menu['Name'])));

		$accessId = $data->InsertID;

		foreach($menu['Options'] as $option=>$value) {
			$data = new DataQuery(sprintf("SELECT Registry_ID FROM registry WHERE Script_File LIKE '%s'", mysql_real_escape_string($option)));
			if($data->TotalRows) {
				$data = new DataQuery(sprintf("INSERT INTO registry_permissions (Access_ID, Permission_ID, Registry_ID) VALUES (%d, 3, %d)", $accessId, $data->Row['Registry_ID']));
			}
			$data->Disconnect();
		}
	}
}