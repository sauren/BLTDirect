<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

function getWarehouses($warehouseId) {
	$warehouses = array($warehouseId);

	$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Parent_Warehouse_ID=%d", mysql_real_escape_string($warehouseId)));
	while($data->Row) {
		$warehouses = array_merge($warehouses, getWarehouses($data->Row['Warehouse_ID']));

		$data->Next();
	}
	$data->Disconnect();

	return $warehouses;
}

$branchFinder = new DataQuery(sprintf("SELECT * FROM users WHERE User_ID=%d ", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
$branchID = $branchFinder->Row['Branch_ID'];
$branchFinder->Disconnect();
if ($branchID == 0){
	$branchFinder = new DataQuery("SELECT * FROM branch WHERE Is_HQ='Y'");
	$branchID = $branchFinder->Row['Branch_ID'];
	$branchFinder->Disconnect();
}

$warehouseFinder = new DataQuery(sprintf("SELECT * FROM warehouse WHERE Type_Reference_ID = %d AND Type='B'", mysql_real_escape_string($branchID)));
$warehouseId = $warehouseFinder->Row['Warehouse_ID'];
$warehouseFinder->Disconnect();

$warehouses = getWarehouses($warehouseId);

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'text', '', 'numeric_unsigned', 1, 11, false);
$form->AddField('start', 'Despatched After', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('end', 'Despatched Before', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

$sqlSelect = '';
$sqlFrom = '';
$sqlWhere = '';

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$sqlSelect = sprintf("SELECT o.Order_ID, o.Order_Prefix, d.Despatch_ID, d.Despatched_On, d.Consignment, d.Courier_ID, c.Courier_Name ");
		$sqlFrom = sprintf("FROM despatch AS d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID INNER JOIN courier AS c ON c.Courier_ID=d.Courier_ID ");
		$sqlWhere = sprintf("WHERE (d.Despatch_From_ID=%s) ", mysql_real_escape_string(implode(' OR d.Despatch_From_ID=', $warehouses)));

		if(strlen($form->GetValue('start')) > 0) {
			$sqlWhere .= sprintf("AND d.Despatched_On>='%s' ", sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)));
		}

		if(strlen($form->GetValue('end')) > 0) {
			$sqlWhere .= sprintf("AND d.Despatched_On<='%s' ", sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)));
		}

		if(strlen($form->GetValue('orderid')) > 0) {
			$sqlWhere .= sprintf("AND d.Order_ID=%d ", mysql_real_escape_string($form->GetValue('orderid')));
		}
	}
}

if(isset($_REQUEST['update'])) {
	$despatches = array();

	foreach($_REQUEST as $key=>$value) {
		if(preg_match('/(consignment|courier)_([\d]*)/', $key, $matches)) {
			$despatches[$matches[2]] = $matches[2];			
		}
	}

	foreach($despatches as $despatchId) {
		$data = new DataQuery(sprintf("SELECT Consignment, Courier_ID FROM despatch WHERE Despatch_ID=%d", mysql_real_escape_string($despatchId)));
		if($data->TotalRows > 0) {
			$consignmentUpdated = false;

			$key = 'courier_' . $despatchId;
			
			if(isset($_REQUEST[$key])) {
				$value = $_REQUEST[$key];
				
				if($data->Row['Courier_ID'] != $value) {
					if($value > 0) {
						$consignmentUpdated = true;
					}

					new DataQuery(sprintf("UPDATE despatch SET Courier_ID=%d WHERE Despatch_ID=%d", mysql_real_escape_string($value), mysql_real_escape_string($despatchId)));
				}
			}
			
			$key = 'consignment_' . $despatchId;

			if(isset($_REQUEST[$key])) {
				$value = $_REQUEST[$key];
				
				if(strtolower(trim($data->Row['Consignment'])) != strtolower(trim($value))) {
					if(strlen(trim($value)) > 0) {
						$consignmentUpdated = true;
					}

					new DataQuery(sprintf("UPDATE despatch SET Consignment='%s' WHERE Despatch_ID=%d", addslashes(stripslashes($value)), mysql_real_escape_string($despatchId)));
				}
			}
			
			if($consignmentUpdated) {
				$despatch = new Despatch($despatchId);
				$despatch->EmailConsignment();
				$despatch->SmsConsignment();
			}
		}
		$data->Disconnect();
	}
}

$page = new Page('Despatch Track', '');
$page->LinkScript('js/scw.js');
$page->Display('header');

if(!$form->Valid) {
    echo $form->GetError();
    echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');

$window = new StandardWindow('Search for despatched consignments');
$webForm = new StandardForm();

echo $window->Open();
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('orderid'), $form->GetHTML('orderid'));
echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn">');
echo $webForm->Close();
echo $window->CloseContent();

echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn">');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

if(isset($_REQUEST['confirm'])) {
	echo '<br />';

	$table = new DataTable('results');
	$table->SetExtractVars();
	$table->SetSQL(sprintf('%s%s%s', $sqlSelect, $sqlFrom, $sqlWhere));
	$table->AddField('Despatch Date', 'Despatched_On', 'left');
	$table->AddField('Despatch ID#', 'Despatch_ID', 'left');
	$table->AddField('Order ID#', 'Order_ID', 'left');
	$table->AddInput('Courier', 'Y', 'Courier_ID', 'courier', 'Despatch_ID', 'select');
	$table->AddInputOption('Courier', '0', '');

	$data = new DataQuery(sprintf("SELECT Courier_ID, Courier_Name FROM courier ORDER BY Courier_Name ASC"));
	while($data->Row) {
		$table->AddInputOption('Courier', $data->Row['Courier_ID'], $data->Row['Courier_Name']);

		$data->Next();
	}
	$data->Disconnect();

	$table->AddInput('Consignment', 'Y', 'Consignment', 'consignment', 'Despatch_ID', 'text');
	$table->SetMaxRows(25);
	$table->SetOrderBy('Despatched_On');
	$table->Order = 'ASC';
	$table->Finalise();
	$table->DisplayTable();

	echo '<br />';

	$table->DisplayNavigation();

	echo '<br />';
	echo '<input type="submit" name="update" value="update" class="btn" />';
}

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');