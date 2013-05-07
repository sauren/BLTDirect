<?php
ini_set('max_execution_time', '3600');
ini_set('display_errors','on');
ini_set('memory_limit', '1024M');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CsvImport.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();
$GLOBALS['SITE_LIVE'] = true;

$records = array();

$csv = new CsvImport('data/sage_accounts.csv', '"', ',');
$csv->HasFieldNames = true;

$form = new Form($_SERVER['PHP_SELF']);

if($csv->Open()) {
	while($csv->Data) {
		$records[] = explode(',', $csv->Data[0]);

		$csv->Next();
	}

	$csv->Close();
}

$count = array();

foreach($records as $record) {
	if(!empty($record[6])) {
        $key = trim(strtoupper(str_replace(' ', '', $record[6])));

		if(!isset($count[$key])) {
			$count[$key] = 0;
		}

		$count[$key]++;
	}
}

echo '<h1>Duplicates Removed</h1>';

for($i=count($records)-1; $i>=0; $i--) {
	if(!empty($records[$i][6])) {
		$key = trim(strtoupper(str_replace(' ', '', $records[$i][6])));

		if(isset($count[$key]) && ($count[$key] > 1)) {
			echo implode(', ', $records[$i]), '<br />';

			unset($records[$i]);
		}
	}
}

$contacts = array();

$data = new DataQuery(sprintf("SELECT a.Zip, c.Contact_ID, c.Parent_Contact_ID, c.Integration_Reference, c2.Integration_Reference AS Parent_Integration_Reference, o.Org_Name, CONCAT_WS(' ', p.Name_First, p.Name_last) AS Person FROM address AS a INNER JOIN person AS p ON p.Address_ID=a.Address_ID INNER JOIN contact AS c ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE a.Zip<>''"));
while($data->Row) {
	$key = trim(strtoupper(str_replace(' ', '', $data->Row['Zip'])));

	if(!isset($contacts[$key])) {
		$contacts[$key] = array();
	}

	$contacts[$key][] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$notMatched = array();

echo '<h1>Matches</h1>';

foreach($records as $record) {
	$matched = false;

	if(!empty($record[6])) {
		$key = trim(str_replace(' ', '', $record[6]));

		if(isset($contacts[$key])) {
			echo '<strong>', implode(', ', $record), '</strong><br />';

			foreach($contacts[$key] as $contact) {
				$positive = false;

				$query = array();
				$query[] = trim(strtolower(str_replace(' ', '', $record[1])));
				$query[] = trim(strtolower(str_replace(' ', '', $record[7])));

                $cross = array();
				$cross[] = trim(strtolower(str_replace(' ', '', $contact['Person'])));
				$cross[] = trim(strtolower(str_replace(' ', '', $contact['Org_Name'])));

				foreach($query as $queryItem) {
					foreach($cross as $crossItem) {
						if(stristr($queryItem, $crossItem)) {
							$positive = true;
							$matched = true;
						}
					}
				}

				echo sprintf('<span%s>', ($positive) ? ' style="color: #f00;"' : ''), $contact['Org_Name'], ' - ', $contact['Person'], ' [', $contact['Integration_Reference'], ' - ', $contact['Parent_Integration_Reference'], ']</span><br />';

				if($positive) {
					if($contact['Parent_Contact_ID'] > 0) {
						new DataQuery(sprintf("UPDATE contact SET Integration_Reference='%s' WHERE Contact_ID=%d", strtoupper($record[0]), $contact['Parent_Contact_ID']));
					} else {
						new DataQuery(sprintf("UPDATE contact SET Integration_Reference='%s' WHERE Contact_ID=%d", strtoupper($record[0]), $contact['Contact_ID']));
					}
				}
			}

			echo '<br />';
		}
	}

	if(!$matched) {
		$notMatched[] = $record;
	}
}

echo '<h1>Not Matched</h1>';

foreach($notMatched as $record) {
	echo implode(', ', $record), '<br />';
}

$GLOBALS['DBCONNECTION']->Close();