<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Customer Report', 'Please choose a start and end date for your report');
	$year = cDatetime(getDatetime(), 'y');
	$form = new Form($_SERVER['PHP_SELF'], 'get');
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

			report($start, $end, $form->GetValue('parent'), ($form->GetValue('subfolders') =='Y') ? true : false);
			exit;
		} else {

			if($form->Validate()){
				report(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))),$form->GetValue('parent'),($form->GetValue('subfolders') =='Y')?true:false);
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

	$window = new StandardWindow("Report on Customers.");
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

function report($start, $end){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$orderTypes = array();
	$orderTypes['W'] = "Website (bltdirect.com)";
	$orderTypes['U'] = "Website (bltdirect.co.uk)";
	$orderTypes['L'] = "Website (lightbulbsuk.co.uk)";
	$orderTypes['M'] = "Mobile";
	$orderTypes['T'] = "Telesales";
	$orderTypes['F'] = "Fax";
	$orderTypes['E'] = "Email";

	$referrersArray = array();

	$page = new Page('Customer Report : ' . cDatetime($start, 'longdatetime') . ' to ' . cDatetime($end, 'longdatetime'), '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

	/*
	What are we going to report on?
	*	Total Visits to your site.
	*	Average Session time on site.
	*	Average Purchase Amount
	*	Repeat Customers
	*/

	$totalVisits = 0;
	$averagePurchase = 0;

	$sql = sprintf("SELECT count(Session_ID) as total
				FROM customer_session
				WHERE Created_On
				BETWEEN '%s'
				AND '%s'", $start, $end);

	$data = new DataQuery($sql);

	$totalVisits = $data->Row['total'];
	$data->Disconnect();

	$sql = sprintf("select avg(SubTotal - TotalDiscount) as SubTotal from orders WHERE Created_On
				BETWEEN '%s'
				AND '%s'
				AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')", $start, $end);
	$data = new DataQuery($sql);
	$averagePurchase = $data->Row['SubTotal'];
	$data->Disconnect();
	?>
	<br />
<h3>Average Session Stats</h3>
<table width="100%" border="0" >
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td>Total Visits:</td>
		<td align="right"><?php echo $totalVisits; ?></td>
	</tr>
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td>Average Purchase:</td>
		<td align="right">&pound;<?php echo number_format($averagePurchase, 2, '.', ','); ?></td>
	</tr>
</table>
<br />
	<h3>Top 200 Most Frequent Customers During Period</h3>
	<p>The following are the most frequent customers to your site who have purchased during this period.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Total Orders</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Total</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Average Spend</strong></td>
	  </tr>
<?php
/*
Modified to include company
*/
$sql = sprintf("select count(o.Customer_ID) as counted,
					c.Customer_ID,
					c.Contact_ID,
					p.Name_First,
					p.Name_Last,
					org.Org_Name,
					COUNT(o.Order_ID) AS Count,
					SUM(o.SubTotal) AS SubTotal,
					SUM(o.TotalDiscount) AS TotalDiscount
					from orders as o
					inner join customer as c on o.Customer_ID=c.Customer_ID
					inner join contact as con on c.Contact_ID=con.Contact_ID
					inner join person as p on con.Person_ID=p.Person_ID
					left join contact as ccon on ccon.Contact_ID=con.Parent_Contact_ID
					left join organisation as org on ccon.Org_ID=org.Org_ID
					where
					o.Created_On BETWEEN '%s' AND '%s'
					and
					o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
					group by o.Customer_ID order by counted desc, total desc limit 200", $start, $end);
$data = new DataQuery($sql);
while($data->Row){
?>
	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><?php echo $data->Row['counted']; ?></td>
		<td><a href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php echo (empty($data->Row['Org_Name']))? $data->Row['Name_First'] . ' ' . $data->Row['Name_Last'] : $data->Row['Org_Name']; ?></a></td>
		<td align="right">&pound;<?php echo number_format(($data->Row['SubTotal']-$data->Row['TotalDiscount']), 2, '.',','); ?></td>
		<td align="right">&pound;<?php echo number_format((($data->Row['SubTotal']-$data->Row['TotalDiscount'])/$data->Row['Count']), 2, '.',','); ?></td>
	  </tr>
<?php
$data->Next();
}
$data->Disconnect();
?>
</table>
<br />

	<h3>Top 200 Value Customers</h3>
	<p>Below are your top value customers with the highest purchase power.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Total Orders</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Total</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Average Spend</strong></td>
	  </tr>
<?php
// top value customers
$sql = sprintf("select count(o.Customer_ID) as counted,
					c.Customer_ID,
					c.Contact_ID,
					p.Name_First,
					p.Name_Last,
					org.Org_Name,
					COUNT(o.Order_ID) AS Count,
					SUM(o.SubTotal) AS SubTotal,
					SUM(o.TotalDiscount) AS TotalDiscount,
					SUM(o.SubTotal - o.TotalDiscount) AS total
					from orders as o
					inner join customer as c on o.Customer_ID=c.Customer_ID
					inner join contact as con on c.Contact_ID=con.Contact_ID
					inner join person as p on con.Person_ID=p.Person_ID
					left join contact as ccon on ccon.Contact_ID=con.Parent_Contact_ID
					left join organisation as org on ccon.Org_ID=org.Org_ID
					where
					o.Created_On BETWEEN '%s' AND '%s' AND
					o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
					group by o.Customer_ID order by total desc limit 200", $start, $end);
$data = new DataQuery($sql);
while($data->Row){
?>
	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><?php echo $data->Row['counted']; ?></td>
		<td><a href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php echo (empty($data->Row['Org_Name']))? $data->Row['Name_First'] . ' ' . $data->Row['Name_Last'] : $data->Row['Org_Name']; ?></a></td>
		<td align="right">&pound;<?php echo number_format(($data->Row['SubTotal']-$data->Row['TotalDiscount']), 2, '.',','); ?></td>
		<td align="right">&pound;<?php echo number_format((($data->Row['SubTotal']-$data->Row['TotalDiscount'])/$data->Row['Count']), 2, '.',','); ?></td>
	  </tr>
<?php
$data->Next();
}
$data->Disconnect();
?>
	</table>
	<br />
	</table>
	<br />
	<h3>Top 200 buyers</h3>
	<p>Below are the customers who spend the most on average.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Total Orders</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Order Total</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Average Spend</strong></td>
	  </tr>
<?php
// top value customers
$sql = sprintf("select count(o.Customer_ID) as counted,
					c.Customer_ID,
					c.Contact_ID,
					p.Name_First,
					p.Name_Last,
					org.Org_Name,
					COUNT(o.Order_ID) AS Count,
					SUM(o.SubTotal) AS SubTotal,
					SUM(o.TotalDiscount) AS TotalDiscount,
					SUM(o.SubTotal - TotalDiscount) / count(o.Customer_ID) AS average
					from orders as o
					inner join customer as c on o.Customer_ID=c.Customer_ID
					inner join contact as con on c.Contact_ID=con.Contact_ID
					inner join person as p on con.Person_ID=p.Person_ID
					left join contact as ccon on ccon.Contact_ID=con.Parent_Contact_ID
					left join organisation as org on ccon.Org_ID=org.Org_ID
					where
					o.Created_On BETWEEN '%s' AND '%s'
					and
					o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
					group by o.Customer_ID order by average desc limit 200", $start, $end);
$data = new DataQuery($sql);
while($data->Row){
?>
	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><?php echo $data->Row['counted']; ?></td>
		<td><a href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php echo (empty($data->Row['Org_Name']))? $data->Row['Name_First'] . ' ' . $data->Row['Name_Last'] : $data->Row['Org_Name']; ?></a></td>
		<td align="right">&pound;<?php echo number_format(($data->Row['SubTotal']-$data->Row['TotalDiscount']), 2, '.',','); ?></td>
		<td align="right">&pound;<?php echo number_format((($data->Row['SubTotal']-$data->Row['TotalDiscount'])/$data->Row['Count']), 2, '.',','); ?></td>
	  </tr>
<?php
$data->Next();
}
$data->Disconnect();
?>
	</table>
	<br />



<?php
new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_order_count SELECT COUNT(Customer_ID) AS Order_Count, Customer_ID AS Customer FROM orders WHERE Created_On BETWEEN '%s' AND '%s' AND Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY Customer_ID", $start, $end));
?>

	<h3>Order count frequency</h3>
	<p>The below table lists the customer order count frequencies within the defined period.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Order Count</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Frequency</strong></td>
	  </tr>
<?php
$data = new DataQuery("SELECT COUNT(Order_Count) AS Frequency, Order_Count, Customer FROM temp_order_count GROUP BY Order_Count ORDER BY Frequency DESC, Order_Count DESC");
while($data->Row) {
?>
	 <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><?php print $data->Row['Order_Count']; ?></td>
		<?php
		$data2 = new DataQuery(sprintf("SELECT Contact_ID FROM customer WHERE Customer_ID=%d", mysql_real_escape_string($data->Row['Customer'])));
		?>

		<td><?php if($data->Row['Frequency'] == 1) {?><a href="contact_profile.php?cid=<?php print $data2->Row['Contact_ID']; ?>"><?php } ?><?php print $data->Row['Frequency'] . (($data->Row['Frequency'] > 1) ? ' occurrences' : ' occurrence') ?><?php if($data->Row['Frequency'] == 1) {?></a><?php } ?></td>

		<?php
		$data2->Disconnect();
		?>
	</tr>
<?php
$data->Next();
}
$data->Disconnect();
?>
	</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
}
?>