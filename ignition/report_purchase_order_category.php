<?php
// Required Indicies:
//
// supplier_product.Product_ID
// product_in_categories.Product_ID

require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Purchase Orders by Category Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
	$form->AddOption('range', 'all', '-- All --');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'thisminute', 'This Minute');
	$form->AddOption('range', 'thishour', 'This Hour');
	$form->AddOption('range', 'thisday', 'This Day');
	$form->AddOption('range', 'thismonth', 'This Month');
	$form->AddOption('range', 'thisyear', 'This Year');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lasthour', 'Last Hour');
	$form->AddOption('range', 'last3hours', 'Last 3 Hours');
	$form->AddOption('range', 'last6hours', 'Last 6 Hours');
	$form->AddOption('range', 'last12hours', 'Last 12 Hours');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastday', 'Last Day');
	$form->AddOption('range', 'last2days', 'Last 2 Days');
	$form->AddOption('range', 'last3days', 'Last 3 Days');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');

	$form->AddField('months', 'Split by Month?', 'checkbox', 'N', 'boolean', NULL, NULL, false);

	// Add suppliers
	$suppliers = new RowSet("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name, o.Org_Name FROM supplier AS s
					INNER JOIN contact AS c on s.Contact_ID=c.Contact_ID
					INNER JOIN person AS p on c.Person_ID=p.Person_ID
					LEFT JOIN contact AS c2 on c2.Contact_ID=c.Parent_Contact_ID
					LEFT JOIN organisation AS o on c2.Org_ID=o.Org_ID ORDER BY o.Org_Name ASC, Name ASC");
	$supplierIds = array();

	foreach ($suppliers as $supplier) {
		$form->AddField("supplier{$supplier->Supplier_ID}", $supplier->Org_Name ? $supplier->Org_Name : $supplier->Name, 'checkbox', 'N', 'boolean', 1, 1, false);
	}


	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		foreach ($suppliers as $supplier) {
			if ($form->GetValue("supplier{$supplier->Supplier_ID}") == "Y") {
				$supplierIds[] = $supplier->Supplier_ID;
			}
		}

		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			switch($form->GetValue('range')) {
				case 'all': 		$start = date('Y-m-d H:i:s', 0);
				$end = date('Y-m-d H:i:s');
				break;

				case 'thisminute': 	$start = date('Y-m-d H:i:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thishour': 	$start = date('Y-m-d H:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisday': 	$start = date('Y-m-d 00:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thismonth': 	$start = date('Y-m-01 00:00:00');
				$end = date('Y-m-d H:i:s');
				break;
				case 'thisyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")));
				$end = date('Y-m-d H:i:s');
				break;

				case 'lasthour': 	$start = date('Y-m-d H:00:00', mktime(date("H")-1, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last3hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-3, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last6hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-6, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last12hours': $start = date('Y-m-d H:00:00', mktime(date("H")-12, 0, 0, date("m"), date("d"),  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastday': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last2days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;
				case 'last3days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
				break;

				case 'lastmonth': 	$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last3months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;
				case 'last6months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1,  date("Y")));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
				break;

				case 'lastyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-1));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
				case 'last2years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-2));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
				case 'last3years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-3));
				$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
				break;
			}

			report($form->GetValue('schema'), $form->GetValue('parent'), ($form->GetValue('subfolders') =='Y') ? true : false, $start, $end, $supplierIds, $form->GetValue('months') == "Y");
			exit;
		} else {

			if($form->Validate()){
				report($form->GetValue('schema'), $form->GetValue('parent'), ($form->GetValue('subfolders') =='Y') ? true : false, sqlDate(ukstrtotime($form->GetValue('start'))), sqlDate(ukstrtotime($form->GetValue('end'))), $supplierIds, $form->GetValue('months') == "Y");
				exit;
			}
		}
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Purchase orders.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('range'), $form->GetHTML('range'));
	echo $webForm->AddRow($form->GetLabel('months'), $form->GetHTML('months'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Or select the date range from below for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Select the suppliers you wish to include in the report.');
	echo $window->OpenContent();
	echo $webForm->Open();

	$temp_1 = "";

	foreach ($suppliers as $supplier) {
		$temp_1 .= $form->GetHTML("supplier{$supplier->Supplier_ID}") . $form->GetLabel("supplier{$supplier->Supplier_ID}") . "<br />";
	}

	echo $webForm->AddRow("Suppliers", $temp_1);
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function supplierOutput($purchase){
	$tempPurchases = array();
	foreach($purchase as $purchaseLine){
		$tempPurchases[$purchaseLine->type][] = $purchaseLine;
	}?>

	<h4><?php echo $purchase->firstValue("Supplier_Organisation_Name") ?></h4>
	<?php if(count($tempPurchases['Stock'])){ ?>
		<p>Orders Generated Manually</p>
		<table width="100%" border="0">
			<tr>
				<td valign="top" style="border-bottom:1px solid #aaaaaa"><strong>Title</strong></td>
				<td valign="top" align="right" style="border-bottom:1px solid #aaaaaa"><strong>Purchase Order #</strong></td>
				<td valign="top" align="right" style="border-bottom:1px solid #aaaaaa; width: 8em;"><strong>Cost</strong></td>
				<td nowrap="nowrap" valign="top" align="right" style="border-bottom:1px solid #aaaaaa; white-space: no-wrap; width: 6em;"><strong>Qty</strong></td>
				<td nowrap="nowrap" valign="top" align="right" style="border-bottom:1px solid #aaaaaa; white-space: no-wrap; width: 6em;"><strong>Total</strong></td>
			</tr>

			<?php foreach ($tempPurchases['Stock'] as $purchaseLine) { ?>
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $purchaseLine->Description ?></td>
					<td align="right"><?php echo $purchaseLine->Purchase_ID ?></td>
					<td align="right"><?php echo number_format($purchaseLine->Cost, 2) ?></td>
					<td align="right"><?php echo $purchaseLine->Quantity ?></td>
					<td align="right"><?php echo number_format($purchaseLine->Total, 2) ?></td>
				</tr>
			<?php } ?>

			<tr class="dataRow total">
				<td><strong>Total:</strong></td>
				<td align="right"></td>
				<td align="right"></td>
				<td align="right"><?php echo arraySumInner($purchase, "Quantity") ?></td>
				<td align="right"><?php echo number_format(arraySumInner($purchase, "Total"),2) ?></td>
			</tr>
		</table>
	<?php } elseif(count($tempPurchases['Turnaround'])){ ?>

		<p>Orders Generated by Dropship</p>
		<table width="100%" border="0">
			<tr>
				<td valign="top" style="border-bottom:1px solid #aaaaaa"><strong>Title</strong></td>
				<td valign="top" align="right" style="border-bottom:1px solid #aaaaaa"><strong>Purchase Order #</strong></td>
				<td valign="top" align="right" style="border-bottom:1px solid #aaaaaa; width: 8em;"><strong>Cost</strong></td>
				<td nowrap="nowrap" valign="top" align="right" style="border-bottom:1px solid #aaaaaa; white-space: no-wrap; width: 6em;"><strong>Qty</strong></td>
				<td nowrap="nowrap" valign="top" align="right" style="border-bottom:1px solid #aaaaaa; white-space: no-wrap; width: 6em;"><strong>Total</strong></td>
			</tr>

			<?php foreach ($tempPurchases['Turnaround'] as $purchaseLine) { ?>
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $purchaseLine->Description ?></td>
					<td align="right"><?php echo $purchaseLine->Purchase_ID ?></td>
					<td align="right"><?php echo number_format($purchaseLine->Cost, 2) ?></td>
					<td align="right"><?php echo $purchaseLine->Quantity ?></td>
					<td align="right"><?php echo number_format($purchaseLine->Total, 2) ?></td>
				</tr>
			<?php } ?>

			<tr class="dataRow total">
				<td><strong>Total:</strong></td>
				<td align="right"></td>
				<td align="right"></td>
				<td align="right"><?php echo arraySumInner($purchase, "Quantity") ?></td>
				<td align="right"><?php echo number_format(arraySumInner($purchase, "Total"),2) ?></td>
			</tr>
		</table>
	<?php } ?>
<?php
}

function report($schema, $cat, $sub, $start, $end, $suppliers, $splitMonth){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductBand.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountSchema.php');

	$page = new Page('Purchase Orders: ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->Display('header');

	$sqlCategories = '';

	if($cat != 0) {
		if($sub) {
			$sqlCategories = sprintf("WHERE (pic.Category_ID=%d %s) ", mysql_real_escape_string($cat), mysql_real_escape_string(GetChildIDS($cat)));
		} else {
			$sqlCategories = sprintf("WHERE pic.Category_ID=%d ", mysql_real_escape_string($cat));
		}
	} else {
		if(!$sub) {
			$sqlCategories = sprintf("WHERE (pic.Category_ID IS NULL OR pic.Category_ID=%d) ", mysql_real_escape_string($cat));
		}
	}

	$suppliers = "and p.Supplier_ID in (0," . join(",", $suppliers) . ")";
	$splitMonth = $splitMonth ? "stamp, " : "";

	$data = new RowSet(sprintf(<<<SQL
select
	concat_ws('-', year(p.Purchased_On), month(p.Purchased_On)) stamp,
	group_concat(distinct p.Purchase_ID separator ', ') Purchase_ID,
	p.type,
	p.Supplier_Organisation_Name,
	p.Purchased_On,
	sum(pl.Quantity) Quantity,
	pl.Description,
	pl.Cost,
	pl.Cost * sum(pl.Quantity) Total
from purchase p
join purchase_line pl on pl.Purchase_ID = p.Purchase_ID
join product_in_categories pic on pic.Product_ID = pl.Product_ID
{$sqlCategories} {$suppliers} and p.Purchased_On BETWEEN '%s' AND '%s'
group by {$splitMonth} pl.Product_ID
order by p.Purchased_On, p.Supplier_Organisation_Name, pl.Product_ID
SQL
	, $start, $end));

?>

<h3 id="per-supplier">Purchase Order Products per Supplier</h3>
<?php
	if ($splitMonth) {
		foreach ($data->byGroup("stamp") as $month) {
			?>
			<h3><br /><?php echo $month->firstValue("stamp") ?></h3>
			<?php

			foreach($month->byGroup("Supplier_Organisation_Name") as $purchase) {
				supplierOutput($purchase);
			}
		}		
	} else {
		foreach($data->byGroup("Supplier_Organisation_Name") as $purchase) {
			supplierOutput($purchase);
		}
	}

	$page->Display('footer');
}

function GetChildIDS($cat) {
	$string = "";

	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($cat)));
	while($children->Row) {
		$string .= sprintf(' OR pic.Category_ID=%d', mysql_real_escape_string($children->Row['Category_ID']));
		$string .= GetChildIDS($children->Row['Category_ID']);

		$children->Next();
	}
	$children->Disconnect();

	return $string;
}
?>