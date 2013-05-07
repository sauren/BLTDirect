<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

$groups = array();
$groups[] = array(42, 257);

foreach($groups as $groupData) {
	new DataQuery(sprintf("UPDATE product_specification_value SET Group_ID=%d WHERE Group_ID=%d", $groupData[0], $groupData[1]));

	$group = new ProductSpecGroup($groupData[1]);
	$group->Delete();
}

$GLOBALS['DBCONNECTION']->Close();