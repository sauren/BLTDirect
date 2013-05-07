<?php
ini_set('max_execution_time', '1800');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$data = new DataQuery(sprintf("SELECT Product_ID, Product_Title FROM product ORDER BY Product_ID ASC"));
while($data->Row) {
	$requiredGroups = array(89, 121, 214);
	$foundGroups = array();

	$data2 = new DataQuery(sprintf("SELECT Value_ID FROM product_specification WHERE Product_ID=%d", $data->Row['Product_ID']));
	while($data2->Row) {
		if(count($requiredGroups) > 0) {
			$data3 = new DataQuery(sprintf("SELECT Group_ID, Value FROM product_specification_value WHERE Value_ID=%d %s", $data2->Row['Value_ID'], sprintf('AND (Group_ID=%s)', implode(' OR Group_ID=', $requiredGroups))));
			if($data3->TotalRows > 0) {
				$key = '';

				if(preg_match('/([0-9]*)mm/', $data3->Row['Value'], $matches)) {
					if(count($matches) >= 2) {
						$value = ($matches[1] + 3) / 1000;

						switch($data3->Row['Group_ID']) {
							case 89:
								$key = 'H';
								break;
							case 121:
								$key = 'W';
								break;
							case 214:
								$key = 'D';
								break;
						}

						if(!empty($key)) {
							$foundGroups[$key] = $value;
						}
					}
				}
			}
			$data3->Disconnect();

		} else {
			break;
		}

		$data2->Next();
	}
	$data2->Disconnect();

	if(count($foundGroups) == 3) {
		new DataQuery(sprintf("UPDATE product SET Shelf_Width=%f, Shelf_Height=%f, Shelf_Depth=%f WHERE Product_ID=%d", $foundGroups['W'], $foundGroups['H'], $foundGroups['D'], $data->Row['Product_ID']));
	} else {
		$requiredGroups = array(54, 121);
		$foundGroups = array();

		$data2 = new DataQuery(sprintf("SELECT Value_ID FROM product_specification WHERE Product_ID=%d", $data->Row['Product_ID']));
		while($data2->Row) {
			if(count($requiredGroups) > 0) {
				$data3 = new DataQuery(sprintf("SELECT Group_ID, Value FROM product_specification_value WHERE Value_ID=%d %s", $data2->Row['Value_ID'], sprintf('AND (Group_ID=%s)', implode(' OR Group_ID=', $requiredGroups))));
				if($data3->TotalRows > 0) {
					if(preg_match('/([0-9]*)mm/', $data3->Row['Value'], $matches)) {
						if(count($matches) >= 2) {
							$value = ($matches[1] + 3) / 1000;

							switch($data3->Row['Group_ID']) {
								case 54:
									$foundGroups['W'] = $value;
									$foundGroups['D'] = $value;
									break;
								case 121:
									$foundGroups['H'] = $value;
									break;
							}
						}
					}
				}
				$data3->Disconnect();

			} else {
				break;
			}

			$data2->Next();
		}
		$data2->Disconnect();

		if(count($foundGroups) == 3) {
			new DataQuery(sprintf("UPDATE product SET Shelf_Width=%f, Shelf_Height=%f, Shelf_Depth=%f WHERE Product_ID=%d", $foundGroups['W'], $foundGroups['H'], $foundGroups['D'], $data->Row['Product_ID']));
		}
	}

	$data->Next();
}
$data->Disconnect();

include('lib/common/appFooter.php');
?>