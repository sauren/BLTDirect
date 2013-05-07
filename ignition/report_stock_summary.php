<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/DataTable.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/Product.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/Form.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/StandardWindow.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');

$warehouses = array();
	
$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, w.Warehouse_Name FROM warehouse AS w INNER JOIN warehouse_stock AS ws ON ws.Warehouse_ID=w.Warehouse_ID AND ws.Quantity_In_Stock>0 WHERE w.Type='B' GROUP BY w.Warehouse_ID ORDER BY w.Warehouse_Name ASC"));
while($data->Row) {
	$warehouses[] = $data->Row;
	
	$data->Next();	
}
$data->Disconnect();
		
$products = array();

$data = new DataQuery(sprintf("SELECT w.Warehouse_ID, p.Product_ID, p.Product_Title, p.LockedSupplierID, p.Is_Stocked, p.Is_Stocked_Temporarily FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID AND w.Type='B' INNER JOIN product AS p ON ws.Product_ID=p.Product_ID WHERE p.Product_Type<>'G' GROUP BY p.Product_ID, w.Warehouse_ID"));
while($data->Row) {
	$products[] = $data->Row;
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT p.Product_ID) AS Count FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID AND w.Type='B' INNER JOIN product AS p ON ws.Product_ID=p.Product_ID WHERE p.Product_Type<>'G'"));
$totalProducts = $data->Row['Count'];
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT p.Product_ID) AS Count FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID AND w.Type='B' INNER JOIN product AS p ON ws.Product_ID=p.Product_ID WHERE p.Product_Type<>'G' AND p.Is_Stocked='Y'"));
$totalStocked = $data->Row['Count'];
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT p.Product_ID) AS Count FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID AND w.Type='B' INNER JOIN product AS p ON ws.Product_ID=p.Product_ID WHERE p.Product_Type<>'G' AND p.Is_Stocked_Temporarily='Y'"));
$totalStockedTemporary = $data->Row['Count'];
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT p.Product_ID) AS Count FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID AND w.Type='B' INNER JOIN product AS p ON ws.Product_ID=p.Product_ID WHERE p.Product_Type<>'G' AND p.LockedSupplierID>0"));
$totalLocked = $data->Row['Count'];
$data->Disconnect();

$summaryStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));
$summaryEndDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m')+1, 1, date('Y')));
if(isset($_REQUEST['start']) && strlen($_REQUEST['start']) > 0 && isset($_REQUEST['end']) && strlen($_REQUEST['end']) > 0){
	$summaryStartDate = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['start'], 6, 4), substr($_REQUEST['start'], 3, 2), substr($_REQUEST['start'], 0, 2));
	$summaryEndDate = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['end'], 6, 4), substr($_REQUEST['end'], 3, 2), substr($_REQUEST['end'], 0, 2));
} elseif(isset($_REQUEST['start']) && strlen($_REQUEST['start']) > 0 && ((isset($_REQUEST['end']) && strlen($_REQUEST['end']) == 0) || !isset($_REQUEST['start']))){
	$summaryStartDate = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['start'], 6, 4), substr($_REQUEST['start'], 3, 2), substr($_REQUEST['start'], 0, 2));
} elseif(((isset($_REQUEST['start']) && strlen($_REQUEST['start']) == 0) || !isset($_REQUEST['start'])) && isset($_REQUEST['end']) && strlen($_REQUEST['end']) > 0){
	$summaryEndDate = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['end'], 6, 4), substr($_REQUEST['end'], 3, 2), substr($_REQUEST['end'], 0, 2));
}

if(!isset($_REQUEST['confirm']) || (isset($_REQUEST['stocked']) && $_REQUEST['stocked'] == 'true')){
	$suppliersStocked = array();

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT p.Purchase_ID) AS Purchases, SUM(pl.Cost*pl.Quantity) AS Value, s.Supplier_ID, CONCAT_WS(' ', pr.Name_First, pr.Name_Last) AS Name, o.Org_Name FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID INNER JOIN product AS pd ON pd.Product_ID=pl.Product_ID AND pd.Is_Stocked='Y' INNER JOIN supplier AS s ON s.Supplier_ID=p.Supplier_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS pr ON pr.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.For_Branch>0 AND p.Purchased_On>='%s' AND p.Purchased_On<'%s' GROUP BY s.Supplier_ID ORDER BY Org_Name ASC", mysql_real_escape_string($summaryStartDate), mysql_real_escape_string($summaryEndDate)));
	while($data->Row) {
		$suppliersStocked[] = $data->Row;
			
		$data->Next();
	}
	$data->Disconnect();
}

if(!isset($_REQUEST['confirm']) || (isset($_REQUEST['notstocked']) && $_REQUEST['notstocked'] == 'true')){
	$suppliersNotStocked = array();

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT p.Purchase_ID) AS Purchases, SUM(pl.Cost*pl.Quantity) AS Value, s.Supplier_ID, CONCAT_WS(' ', pr.Name_First, pr.Name_Last) AS Name, o.Org_Name FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID INNER JOIN product AS pd ON pd.Product_ID=pl.Product_ID AND pd.Is_Stocked='N' INNER JOIN supplier AS s ON s.Supplier_ID=p.Supplier_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS pr ON pr.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.For_Branch>0 AND p.Purchased_On>='%s' AND p.Purchased_On<'%s' GROUP BY s.Supplier_ID ORDER BY Org_Name ASC", mysql_real_escape_string($summaryStartDate), mysql_real_escape_string($summaryEndDate)));
	while($data->Row) {
		$suppliersNotStocked[] = $data->Row;
			
		$data->Next();
	}
	$data->Disconnect();
}

if(!isset($_REQUEST['confirm']) || (isset($_REQUEST['dropped']) && $_REQUEST['dropped'] == 'true')){
	$suppliersDropped = array();

	$data = new DataQuery(sprintf("SELECT COUNT(DISTINCT p.Purchase_ID) AS Purchases, SUM(pl.Cost*pl.Quantity) AS Value, s.Supplier_ID, CONCAT_WS(' ', pr.Name_First, pr.Name_Last) AS Name, o.Org_Name FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID INNER JOIN product AS pd ON pd.Product_ID=pl.Product_ID INNER JOIN supplier AS s ON s.Supplier_ID=p.Supplier_ID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS pr ON pr.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID WHERE p.For_Branch=0 AND p.Order_ID>0 AND p.Purchased_On>='%s' AND p.Purchased_On<'%s' GROUP BY s.Supplier_ID ORDER BY Org_Name ASC", mysql_real_escape_string($summaryStartDate), mysql_real_escape_string($summaryEndDate)));
	while($data->Row) {
		$suppliersDropped[] = $data->Row;
			
		$data->Next();
	}
	$data->Disconnect();
}

$chartFileName = sprintf('%s_%s', $GLOBALS['SESSION_USER_ID'], rand(0, 99999));
$chartWidth = 900;
$chartHeight = 600;
$chartTitle = 'Cost of purchase orders.';
$chartReference = sprintf('../ignition/temp/charts/chart_%s.png', $chartFileName);

$chart = new VerticalChart($chartWidth, $chartHeight, array('Stocked Cost', 'Not Stocked Cost', 'Dropped Cost'));

$startDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y') - 2));
$endDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));
if(isset($_REQUEST['start']) && strlen($_REQUEST['start']) > 0 && isset($_REQUEST['end']) && strlen($_REQUEST['end']) > 0){
	$startDate = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['start'], 6, 4), substr($_REQUEST['start'], 3, 2), substr($_REQUEST['start'], 0, 2));
	$endDate = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['end'], 6, 4), substr($_REQUEST['end'], 3, 2), substr($_REQUEST['end'], 0, 2));
} elseif(isset($_REQUEST['start']) && strlen($_REQUEST['start']) > 0 && ((isset($_REQUEST['end']) && strlen($_REQUEST['end']) == 0) || !isset($_REQUEST['start']))){
	$startDate = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['start'], 6, 4), substr($_REQUEST['start'], 3, 2), substr($_REQUEST['start'], 0, 2));
} elseif(((isset($_REQUEST['start']) && strlen($_REQUEST['start']) == 0) || !isset($_REQUEST['start'])) && isset($_REQUEST['end']) && strlen($_REQUEST['end']) > 0){
	$endDate = sprintf('%s-%s-%s 00:00:00', substr($_REQUEST['end'], 6, 4), substr($_REQUEST['end'], 3, 2), substr($_REQUEST['end'], 0, 2));
}

$tempDate = $startDate;

if(!isset($_REQUEST['confirm']) || (isset($_REQUEST['stocked']) && $_REQUEST['stocked'] == 'true')){
	$purchasesStocked = array();

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(p.Purchased_On, '%%Y-%%m') AS PurchaseDate, SUM(pl.Cost*pl.Quantity) AS Value FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID INNER JOIN product AS pd ON pd.Product_ID=pl.Product_ID AND pd.Is_Stocked='Y' WHERE p.For_Branch>0 AND p.Purchased_On>='%s' GROUP BY PurchaseDate", mysql_real_escape_string($startDate)));
	while($data->Row) {
		$purchasesStocked[$data->Row['PurchaseDate']] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();
}

if(!isset($_REQUEST['confirm']) || (isset($_REQUEST['notstocked']) && $_REQUEST['notstocked'] == 'true')){
	$purchasesNotStocked = array();

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(p.Purchased_On, '%%Y-%%m') AS PurchaseDate, SUM(pl.Cost*pl.Quantity) AS Value FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID INNER JOIN product AS pd ON pd.Product_ID=pl.Product_ID AND pd.Is_Stocked='N' WHERE p.For_Branch>0 AND p.Purchased_On>='%s' GROUP BY PurchaseDate", mysql_real_escape_string($startDate)));
	while($data->Row) {
		$purchasesNotStocked[$data->Row['PurchaseDate']] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();
}

if(!isset($_REQUEST['confirm']) || (isset($_REQUEST['dropped']) && $_REQUEST['dropped'] == 'true')){
	$purchasesDropped = array();

	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(p.Purchased_On, '%%Y-%%m') AS PurchaseDate, SUM(pl.Cost*pl.Quantity) AS Value FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID INNER JOIN product AS pd ON pd.Product_ID=pl.Product_ID WHERE p.For_Branch=0 AND p.Order_ID>0 AND p.Purchased_On>='%s' GROUP BY PurchaseDate", mysql_real_escape_string($startDate)));
	while($data->Row) {
		$purchasesDropped[$data->Row['PurchaseDate']] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();
}

while(strtotime($tempDate) <= strtotime($endDate)) {
	$key = substr($tempDate, 0, 7);

	$chart->addPoint(new Point($key, array(isset($purchasesStocked[$key]) ? $purchasesStocked[$key]['Value'] : 0, isset($purchasesNotStocked[$key]) ? $purchasesNotStocked[$key]['Value'] : 0, isset($purchasesDropped[$key]) ? $purchasesDropped[$key]['Value'] : 0)));

	$tempDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($tempDate)) + 1, 1, date('Y', strtotime($tempDate))));
}

$chart->SetTitle($chartTitle);
$chart->SetLabelY('Purchase Costs');
$chart->ShowText = false;
$chart->ShortenValues = false;
$chart->render($chartReference);

$page = new Page('Stock Summary Report', '');
$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
?>

<form action="report_stock_summary.php" method="GET">
	<fieldset>
		<label for="start">Start Date</label>
		<input type="text" maxlength="10" tabindex="5" onfocus="scwShow(this, this);" onclick="scwShow(this, this);" id="start" value="<?php echo date('d/m/Y', strtotime($startDate)); ?>" name="start">
		<br /><br />
		<label for="end">End Date</label>
		<input type="text" maxlength="10" tabindex="5" onfocus="scwShow(this, this);" onclick="scwShow(this, this);" id="end" value="<?php echo date('d/m/Y', strtotime($endDate)); ?>" name="end">
	</fieldset><br />
	<fieldset>
		<label for="stocked">Stocked</label>
		<?php if(!isset($_REQUEST['confirm']) || (isset($_REQUEST['stocked']) && $_REQUEST['stocked'] == 'true')){ ?>
			<input type="checkbox" name="stocked" checked="checked" value="true" id="stocked" />
		<?php } else { ?>
			<input type="checkbox" name="stocked" value="true" id="stocked" />
		<?php } ?>
		&nbsp;
		<label for="notstocked">Not Stocked</label>
		<?php if(!isset($_REQUEST['confirm']) || (isset($_REQUEST['notstocked']) && $_REQUEST['notstocked'] == 'true')){ ?>
			<input type="checkbox" name="notstocked" checked="checked" value="true" id="notstocked" />
		<?php } else { ?>
			<input type="checkbox" name="notstocked" value="true" id="notstocked" />
		<?php } ?>
		&nbsp;
		<label for="dropped">Dropped</label>
		<?php if(!isset($_REQUEST['confirm']) || (isset($_REQUEST['dropped']) && $_REQUEST['dropped'] == 'true')){ ?>
			<input type="checkbox" name="dropped" checked="checked" value="true" id="dropped" />
		<?php } else { ?>
			<input type="checkbox" name="dropped" value="true" id="dropped" />
		<?php } ?>
	</fieldset><br />
	<input type="submit" name="confirm" value="Submit" />
</form>
<br />
<br />

<h3>Stock Summary</h3>
<br />

<table width="100%" border="0" cellpadding="3" cellspacing="0">
	<tr style="background-color: #eeeeee;">
		<td style="border-bottom: 1px solid #dddddd;"><strong>Item</strong></td>
		
		<?php
		foreach($warehouses as $warehouse) {
			?>
			
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong><?php echo $warehouse['Warehouse_Name']; ?></strong></td>
			
			<?php
		}
		?>

		<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Total</strong></td>
	</tr>
	<tr>
		<td style="border-top:1px solid #dddddd;">Products</td>
		
		<?php
		$countAll = 0;

		foreach($warehouses as $warehouse) {
			$count = 0;
			
			foreach($products as $product) {
				if($product['Warehouse_ID'] == $warehouse['Warehouse_ID']) {
					$count++;
				}
			}
			?>
			
			<td style="border-top:1px solid #dddddd;" align="right"><?php echo $count; ?></td>
			
			<?php
			$countAll += $count;
		}
		?>

		<td style="border-top:1px solid #dddddd;" align="right"><?php echo $totalProducts; ?> (<?php echo $countAll-$totalProducts; ?> Duplicates)</td>
	</tr>
	<tr>
		<td style="border-top:1px solid #dddddd;">Stocked</td>
		
		<?php
		$countAll = 0;

		foreach($warehouses as $warehouse) {
			$count = 0;
				
			foreach($products as $product) {
				if($product['Warehouse_ID'] == $warehouse['Warehouse_ID']) {
					if($product['Is_Stocked'] == 'Y') {
						$count++;
					}
				}
			}
			?>
				
			<td style="border-top:1px solid #dddddd;" align="right"><?php echo $count; ?></td>
				
			<?php
			$countAll += $count;
		}
		?>

		<td style="border-top:1px solid #dddddd;" align="right"><?php echo $totalStocked; ?> (<?php echo $countAll-$totalStocked; ?> Duplicates)</td>
	</tr>
	<tr>
		<td style="border-top:1px solid #dddddd;">Stocked Temporarily</td>
			
		<?php
		$countAll = 0;

		foreach($warehouses as $warehouse) {
			$count = 0;
				
			foreach($products as $product) {
				if($product['Warehouse_ID'] == $warehouse['Warehouse_ID']) {
					if($product['Is_Stocked_Temporarily'] == 'Y') {
						$count++;
					}
				}
			}
			?>
				
			<td style="border-top:1px solid #dddddd;" align="right"><?php echo $count; ?></td>
				
			<?php
			$countAll += $count;
		}
		?>

		<td style="border-top:1px solid #dddddd;" align="right"><?php echo $totalStockedTemporary; ?> (<?php echo $countAll-$totalStockedTemporary; ?> Duplicates)</td>
	</tr>
	<tr>
		<td style="border-top:1px solid #dddddd;">Locked</td>
			
		<?php
		$countAll = 0;

		foreach($warehouses as $warehouse) {
			$count = 0;
				
			foreach($products as $product) {
				if($product['Warehouse_ID'] == $warehouse['Warehouse_ID']) {
					if($product['LockedSupplierID'] > 0) {
						$count++;
					}
				}
			}
			?>
				
			<td style="border-top:1px solid #dddddd;" align="right"><?php echo $count; ?></td>
				
			<?php
			$countAll += $count;
		}
		?>

		<td style="border-top:1px solid #dddddd;" align="right"><?php echo $totalLocked; ?> (<?php echo $countAll-$totalLocked; ?> Duplicates)</td>
	</tr>
</table>
<br />

<?php if(isset($suppliersStocked) && count($suppliersStocked)){ ?>
	<h3>Purchase (Stocked) Summary</h3>
	<p>Statistics between <?php echo date('d/m/Y', strtotime($summaryStartDate)); ?> and <?php echo date('d/m/Y', strtotime($summaryEndDate)); ?></p>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color: #eeeeee;">
			<td style="border-bottom: 1px solid #dddddd;"><strong>Supplier</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Purchases</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Value</strong></td>
		</tr>

		<?php
		$totalPurchases = 0;
		$totalValue = 0;

		foreach($suppliersStocked as $supplier) {
			$totalPurchases += $supplier['Purchases'];
			$totalValue += $supplier['Value'];
			?>

			<tr>
				<td style="border-top:1px solid #dddddd;"><?php echo $supplier['Org_Name']; ?></td>
				<td style="border-top:1px solid #dddddd;" align="right"><?php echo $supplier['Purchases']; ?></td>
				<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($supplier['Value'], 2), 2, '.', ','); ?></td>
			</tr>

			<?php
		}
		?>

		<tr>
			<td style="border-top:1px solid #dddddd;">&nbsp;</td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo $totalPurchases; ?></strong></td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong>&pound;<?php echo number_format(round($totalValue, 2), 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />
<?php } ?>

<?php if(isset($suppliersNotStocked) && count($suppliersNotStocked)){ ?>
	<h3>Purchase (Not Stocked) Summary</h3>
	<p>Statistics between <?php echo date('d/m/Y', strtotime($summaryStartDate)); ?> and <?php echo date('d/m/Y', strtotime($summaryEndDate)); ?></p>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color: #eeeeee;">
			<td style="border-bottom: 1px solid #dddddd;"><strong>Supplier</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Purchases</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Value</strong></td>
		</tr>

		<?php
		$totalPurchases = 0;
		$totalValue = 0;

		foreach($suppliersNotStocked as $supplier) {
			$totalPurchases += $supplier['Purchases'];
			$totalValue += $supplier['Value'];
			?>

			<tr>
				<td style="border-top:1px solid #dddddd;"><?php echo $supplier['Org_Name']; ?></td>
				<td style="border-top:1px solid #dddddd;" align="right"><?php echo $supplier['Purchases']; ?></td>
				<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($supplier['Value'], 2), 2, '.', ','); ?></td>
			</tr>

			<?php
		}
		?>

		<tr>
			<td style="border-top:1px solid #dddddd;">&nbsp;</td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo $totalPurchases; ?></strong></td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong>&pound;<?php echo number_format(round($totalValue, 2), 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />
<?php } ?>

<?php if(isset($suppliersDropped) && count($suppliersDropped)){ ?>
	<h3>Purchase (Dropped) Summary</h3>
	<p>Statistics between <?php echo date('d/m/Y', strtotime($summaryStartDate)); ?> and <?php echo date('d/m/Y', strtotime($summaryEndDate)); ?></p>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color: #eeeeee;">
			<td style="border-bottom: 1px solid #dddddd;"><strong>Supplier</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Purchases</strong></td>
			<td style="border-bottom: 1px solid #dddddd;" align="right" width="15%"><strong>Value</strong></td>
		</tr>

		<?php
		$totalPurchases = 0;
		$totalValue = 0;

		foreach($suppliersDropped as $supplier) {
			$totalPurchases += $supplier['Purchases'];
			$totalValue += $supplier['Value'];
			?>

			<tr>
				<td style="border-top:1px solid #dddddd;"><?php echo $supplier['Org_Name']; ?></td>
				<td style="border-top:1px solid #dddddd;" align="right"><?php echo $supplier['Purchases']; ?></td>
				<td style="border-top:1px solid #dddddd;" align="right">&pound;<?php echo number_format(round($supplier['Value'], 2), 2, '.', ','); ?></td>
			</tr>

			<?php
		}
		?>

		<tr>
			<td style="border-top:1px solid #dddddd;">&nbsp;</td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong><?php echo $totalPurchases; ?></strong></td>
			<td style="border-top:1px solid #dddddd;" align="right"><strong>&pound;<?php echo number_format(round($totalValue, 2), 2, '.', ','); ?></strong></td>
		</tr>
	</table>
	<br />
<?php } ?>

<h2>Purchase Costs</h2>
<br />

<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
	<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
</div>
<br />
<table width="100%" cellspacing="0" cellpadding="3" border="0">
	<thead>
		<tr style="background-color: #eeeeee;">
			<td style="border-bottom: 1px solid #dddddd;"><strong>Date</strong></td>
			<?php if(isset($purchasesStocked) && count($purchasesStocked)){ ?>
			<td align="right" style="border-bottom: 1px solid #dddddd;"><strong>Stocked Cost</strong></td>
			<?php } ?>
			<?php if(isset($purchasesNotStocked) && count($purchasesNotStocked)){ ?>
			<td align="right" style="border-bottom: 1px solid #dddddd;"><strong>Not Stocked Cost</strong></td>
			<?php } ?>
			<?php if(isset($purchasesDropped) && count($purchasesDropped)){ ?>
			<td align="right" style="border-bottom: 1px solid #dddddd;"><strong>Dropped Cost</strong></td>
			<?php } ?>
			<td align="right" style="border-bottom: 1px solid #dddddd;"><strong>Total</strong></td>
		</tr>
	</thead>
	<tbody>
<?php 
	$tempDate = $startDate;
	$purchasesStockedTotal = 0;
	$purchasesNotStockedTotal = 0;
	$purchasesDroppedTotal = 0;
	while(strtotime($tempDate) <= strtotime($endDate)) {
		$key = substr($tempDate, 0, 7);

		$rowTotal = 0;

		$purchasesStockedTotal += isset($purchasesStocked[$key]) ? $purchasesStocked[$key]['Value'] : 0;
		$purchasesNotStockedTotal += isset($purchasesNotStocked[$key]) ? $purchasesNotStocked[$key]['Value'] : 0;
		$purchasesDroppedTotal += isset($purchasesDropped[$key]) ? $purchasesDropped[$key]['Value'] : 0;
?>
		<tr>
			<td style="border-bottom: 1px solid #dddddd;"><?php echo $key; ?></td>
			<?php 
				$currentValue = (isset($purchasesStocked[$key]))? $purchasesStocked[$key]['Value'] : 0;
				$rowTotal += $currentValue;
			?>
				<td align="right" style="border-bottom: 1px solid #dddddd;">&pound;<?php echo number_format(round($currentValue, 2), 2, '.', ','); ?></td>

			<?php
				$currentValue = (isset($purchasesNotStocked[$key]))? $purchasesNotStocked[$key]['Value'] : 0;
				$rowTotal += $currentValue;
			?>
				<td align="right" style="border-bottom: 1px solid #dddddd;">&pound;<?php echo number_format(round($currentValue, 2), 2, '.', ','); ?></td>
			<?php 
				$currentValue = (isset($purchasesDropped[$key]))? $purchasesDropped[$key]['Value'] : 0;
				$rowTotal += $currentValue;
			?>
				<td align="right" style="border-bottom: 1px solid #dddddd;">&pound;<?php echo number_format(round($currentValue, 2), 2, '.', ','); ?></td>

			<td align="right" style="border-bottom: 1px solid #dddddd;">&pound;<?php echo number_format(round($rowTotal, 2), 2, '.', ','); ?></td>
		</tr>
<?php
		$tempDate = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime($tempDate)) + 1, 1, date('Y', strtotime($tempDate))));
	}
	$rowTotal = 0;
?>
		<tr>
			<td style="border-bottom: 1px solid #dddddd;">&nbsp;</td>
			<?php 
				$rowTotal += $purchasesStockedTotal;
			?>
				<td align="right" style="border-bottom: 1px solid #dddddd;"><strong>&pound;<?php echo number_format(round($purchasesStockedTotal, 2), 2, '.', ','); ?></strong></td>

			<?php
				$rowTotal += $purchasesNotStockedTotal;
			?>
				<td align="right" style="border-bottom: 1px solid #dddddd;"><strong>&pound;<?php echo number_format(round($purchasesNotStockedTotal, 2), 2, '.', ','); ?></strong></td>

			<?php
				$rowTotal += $purchasesDroppedTotal;
			?>
				<td align="right" style="border-bottom: 1px solid #dddddd;"><strong>&pound;<?php echo number_format(round($purchasesDroppedTotal, 2), 2, '.', ','); ?></strong></td>

			<td align="right" style="border-bottom: 1px solid #dddddd;"><strong>&pound;<?php echo number_format(round($rowTotal, 2), 2, '.', ','); ?></strong></td>
		</tr>
	</tbody>
</table>
<br />
<br />	
<script language="javascript">
window.onload = function() {
	var httpRequest = new HttpRequest();
	httpRequest.post('../ignition/lib/util/removeChart.php', 'chart=<?php echo $chartReference; ?>');
}
</script>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');