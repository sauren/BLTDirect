<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
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

	$form->AddField('supplier', 'Supplier', 'select', 0, 'numeric_unsigned', 1, 11);
	$form->AddOption('supplier', 0, '-- All --');

	$sql = sprintf("SELECT s.Supplier_ID, p.Name_First,p.Name_Last, o.Org_Name FROM supplier s
					INNER JOIN contact c on s.Contact_ID =  c.Contact_ID
					INNER JOIN person p on c.Person_ID = p.Person_ID
					LEFT JOIN contact c2 on c2.Contact_ID = c.Parent_Contact_ID
					LEFT JOIN organisation o on c2.Org_ID = o.Org_ID");

	$data = new DataQuery($sql);
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], (strlen($data->Row['Org_Name']) > 0) ? $data->Row['Org_Name'] : sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last']));
		$data->Next();
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
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

			report($start, $end, $form->GetValue('supplier'));
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('supplier'));
				exit;
			}
		}
	}

	$page = new Page('Suppliers Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Report on Suppliers.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('range'), $form->GetHTML('range'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Select a supplier for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Or select the date range from below for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
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

function report($start, $end, $supplierId){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Suppliers Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	$orders = new DataQuery(sprintf("select count(Order_ID) as OrderCount, Order_Prefix, sum(SubTotal) as SubTotal, sum(TotalShipping) as TotalShipping, sum(TotalTax) as TotalTax, sum(Total) as Total from orders where Created_On between '%s' and '%s' AND Status<>'Unauthenticated' AND Status<>'Cancelled' group by Order_Prefix", mysql_real_escape_string($start), mysql_real_escape_string($end)));


	while($orders->Row){
		$totalSubTotal += $orders->Row['SubTotal'];
		$orders->Next();
	}
	$orders->Disconnect();

	$supplier = '';

	if($supplierId > 0) {
		$supplier = ' AND s.Supplier_ID=' . mysql_real_escape_string($supplierId);
	}

	$top25 = new DataQuery(sprintf("select sum(ol.Quantity) As OrderCount, sp.Supplier_ID,ol.Product_ID,ol.Product_Title,s.Contact_ID from order_line as ol
inner join orders as o on ol.Order_ID=o.Order_ID
INNER JOIN warehouse w ON ol.Despatch_From_ID = w.Warehouse_ID
INNER JOIN supplier s ON s.Supplier_ID = w.Type_Reference_ID
INNER JOIN supplier_product sp ON sp.Product_ID = ol.Product_ID AND s.Supplier_ID = sp.Supplier_ID
where o.Created_On Between '%s' AND '%s' AND Line_Status != 'Cancelled' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND w.`Type` = 'S'%s Group By Supplier_ID Order By OrderCount", mysql_real_escape_string($start), mysql_real_escape_string($end), $supplier));

	if($top25->TotalRows > 0) {

	  $overallTotalCost = 0;
	  $overallTotalPrice = 0;

	while($top25->Row){
		$contacter = new DataQuery(sprintf("Select * FROM contact c WHERE Contact_ID = %d",$top25->Row['Contact_ID']));
		if($contacter->Row['Parent_Contact_ID'] == 0){
			$personFinder = new DataQuery(sprintf("SELECT * FROM person p WHERE Person_ID = %d",$contacter->Row['Person_ID']));
			$name = $personFinder->Row['Name_Title'].' '.$personFinder->Row['Name_First'].' '.$personFinder->Row['Name_Initial'].' '.$personFinder->Row['Name_Last'];
			$personFinder->Disconnect();
		}else{
			$orgFinder = new DataQuery(sprintf("SELECT * FROM contact c
	  									INNER JOIN organisation o ON c.Org_ID = o.Org_ID
	  									WHERE c.Contact_ID = %d",$contacter->Row['Parent_Contact_ID']));
			$name = $orgFinder->Row['Org_Name'];
			$orgFinder->Disconnect();
		}
		$contacter->Disconnect();
	 	?>
	<br /><h3>Top Products for <?php echo $name;?></h3>
	<p>Top 100 products for this supplier.</p>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quantity Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Orders</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Cost Price</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Aaverage Sale Price</strong> </td>
	  </tr>
	  <?php
	  $totalOrders = 0;
	  $totalCost = 0;
	  $totalPrice = 0;

	  $supPrice = new DataQuery(sprintf("select sum(ol.Quantity) AS OrderCount, COUNT(DISTINCT ol.Order_ID) AS Orders, sp.Supplier_ID,ol.Product_ID,ol.Product_Title,avg(ol.Price) As Average_Price,sp.Cost from order_line as ol
											inner join orders as o on ol.Order_ID=o.Order_ID
											INNER JOIN warehouse w ON ol.Despatch_From_ID = w.Warehouse_ID
											INNER JOIN supplier s ON s.Supplier_ID = w.Type_Reference_ID
											INNER JOIN supplier_product sp ON sp.Product_ID = ol.Product_ID AND s.Supplier_ID = sp.Supplier_ID
											where o.Created_On Between '%s' AND '%s' AND Line_Status != 'Cancelled' AND w.`Type` = 'S'
											AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND sp.Supplier_ID=%d Group By ol.Product_ID Order By OrderCount desc limit 100",mysql_real_escape_string($start),mysql_real_escape_string($end),$top25->Row['Supplier_ID']));

	  while ($supPrice->Row){
	  		?>
	  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><a href="product_profile.php?pid=<?php echo $supPrice->Row['Product_ID']; ?>"><?php echo strip_tags($supPrice->Row['Product_Title']); ?></a></td>
		<td align="right"><?php echo $supPrice->Row['Product_ID']; ?></td>
		<td align="right"><?php echo $supPrice->Row['OrderCount']; ?></td>
		<td align="right"><?php echo $supPrice->Row['Orders']; ?></td>
		<td align="right"><?php echo "&pound;".number_format($supPrice->Row['Cost'],2,'.',','); ?></td>
		<td align="right"><?php echo "&pound;".number_format($supPrice->Row['Average_Price'],2,'.',','); ?></td>
	  </tr>
		<?php
		$totalOrders += $supPrice->Row['Orders'];
		$totalCost += $supPrice->Row['Cost'];
		$totalPrice += $supPrice->Row['Average_Price'];

		$overallTotalCost += $totalCost;
		$overallTotalPrice += $totalPrice;

		$supPrice->Next();
	  }
	  ?>
	  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
	  	<td>&nbsp;</td>
	  	<td>&nbsp;</td>
	  	<td align="right"><strong><?php echo $top25->Row['OrderCount']; ?></strong></td>
	  	<td align="right"><strong><?php echo $totalOrders; ?></strong></td>
	  	<td align="right"><strong>&pound;<?php echo number_format($totalCost,2,'.',','); ?></strong></td>
	  	<td align="right"><strong>&pound;<?php echo number_format($totalPrice,2,'.',','); ?></strong></td>
	  </tr>
	  </table>

	  <?php
	  $supPrice->Disconnect();


	  $top25->Next();
	}
	?>

	<br />
	<table width="100%" border="0">
	  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
	  	<td><strong>Overall Total Cost Price</strong></td>
	  	<td align="right"><strong>&pound;<?php echo number_format($overallTotalCost,2,'.',','); ?></strong></td>
	  	</tr>
	  <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
	  	<td><strong>Overall Total Average Sale Price</strong></td>
	  	<td align="right"><strong>&pound;<?php echo number_format($overallTotalPrice,2,'.',','); ?></strong></td>
	  </tr>
	</table>

	<?php
	} else {
		echo '<p><strong>There are no sales to report on for this supplier during the selected period.</strong></p>';
	}
	$top25->Disconnect();
	$page->Display('footer');
}?>