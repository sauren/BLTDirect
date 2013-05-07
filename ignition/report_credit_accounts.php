<?php
ini_set('max_execution_time', '120');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$page = new Page('Credit Accounts Report', '');
$page->Display('header');

$data = new DataQuery(sprintf("SELECT c.Customer_ID, c.Contact_ID, c.Credit_Limit, c.Credit_Period, p.Name_First, p.Name_Last, p.Phone_1, org.Org_Name FROM customer AS c INNER JOIN contact AS n ON c.Contact_ID=n.Contact_ID INNER JOIN person AS p ON p.Person_ID=n.Person_ID LEFT JOIN contact AS corg ON corg.Contact_ID=n.Parent_Contact_ID LEFT JOIN organisation AS org ON corg.Org_ID=org.Org_ID WHERE c.Is_Credit_Active='Y' AND c.Credit_Period>0"));
if($data->TotalRows > 0) {
	$dataArray = array();
	$customersUsed = array();

	$pink = 0;
	$bronze = 0;
	$silver = 0;
	$gold = 0;
	$last30['pink'] = 0;
	$last30['bronze'] = 0;
	$last30['silver'] = 0;
	$last30['gold'] = 0;
	$thisMonth['pink'] = 0;
	$thisMonth['bronze'] = 0;
	$thisMonth['silver'] = 0;
	$thisMonth['gold'] = 0;

	while($data->Row) {
		$arr = array();
		$arr['Customer_ID'] = $data->Row['Customer_ID'];
		$arr['Contact_ID'] = $data->Row['Contact_ID'];
		$arr['Credit_Limit'] = $data->Row['Credit_Limit'];
		$arr['Credit_Period'] = $data->Row['Credit_Period'];
		$arr['Name_First'] = $data->Row['Name_First'];
		$arr['Name_Last'] = $data->Row['Name_Last'];
		$arr['Org_Name'] = $data->Row['Org_Name'];
		$arr['Phone_1'] = $data->Row['Phone_1'];

		$dataArray[] = $arr;

		$data->Next();
	}
	
	$now = date('Y-m-d H:i:s');

	// 0 - 30 days
	$spentAccounts = array();

	foreach($dataArray as $dataArr) {

		$data3 = new DataQuery(sprintf("SELECT o.Ordered_On FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Ordered_On BETWEEN ADDDATE('%s', -30) AND '%s' AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') ORDER BY o.Ordered_On DESC", mysql_real_escape_string($dataArr['Customer_ID']), $now, $now));

		if($data3->TotalRows > 0) {
			$data2 = new DataQuery(sprintf("SELECT SUM(o.Total) AS Total, COUNT(o.Total) AS Count FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Customer_ID", mysql_real_escape_string($dataArr['Customer_ID'])));

			$data4 = new DataQuery(sprintf("SELECT SUM(o.Total) AS Total FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Ordered_On BETWEEN '%s' AND '%s' AND o.Customer_ID=%d AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Customer_ID", date('Y-m-01 00:00:00', strtotime($now)), $now, mysql_real_escape_string($dataArr['Customer_ID'])));
			$data5 = new DataQuery(sprintf("SELECT SUM(o.Total) AS Total FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Ordered_On BETWEEN ADDDATE('%s', -30) AND '%s' AND o.Customer_ID=%d AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Customer_ID", $now, $now, mysql_real_escape_string($dataArr['Customer_ID'])));

			$account = array();
			$account['Last_Ordered'] = $data3->Row['Ordered_On'];
			$account['Contact_ID'] = $dataArr['Contact_ID'];
			$account['Customer'] = (empty($dataArr['Org_Name']))? $dataArr['Name_First'] . ' ' . $dataArr['Name_Last'] : $dataArr['Org_Name'];
			$account['Credit_Period'] = $dataArr['Credit_Period'];
			$account['Credit_Limit'] = number_format($dataArr['Credit_Limit'], 2, '.',',');
			$account['Total_On_Credit'] = $data2->Row['Total'];
			$account['Total_Count'] = $data2->Row['Count'];
			$account['Phone'] = $dataArr['Phone_1'];
			$account['Total_This_Month'] = $data4->Row['Total'];
			$account['Total_Last_30'] = $data5->Row['Total'];

			$spentAccounts[] = $account;
			$customersUsed[$dataArr['Contact_ID']] = true;

			$data2->Disconnect();
			$data4->Disconnect();
			$data5->Disconnect();
		}

		$data3->Disconnect();
	}

	arsort($spentAccounts);
	?>

	<br />
	<h3>Customers ordered in the last 0-30 days</h3>
	<p>The following customers have active credit accounts and ordered within the last 0-30 days.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Period</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Limit</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
	  foreach($spentAccounts as $account) {
		$style = '';

		if(($account['Total_Count'] > 0) && ($account['Total_Count'] <= 2)) {
		  	$pink++;
		  	$style = 'background-color: #FFC7E4;';
		  	$last30['pink'] += $account['Total_Last_30'];
		  	$thisMonth['pink'] += $account['Total_This_Month'];
		} elseif(($account['Total_Count'] >= 3) && ($account['Total_Count'] <= 4)) {
		  	$bronze++;
		  	$style = 'background-color: #EBE0BD;';
		  	$last30['bronze'] += $account['Total_Last_30'];
		  	$thisMonth['bronze'] += $account['Total_This_Month'];
		} elseif(($account['Total_Count'] >= 5) && ($account['Total_Count'] <= 9)) {
		  	$silver++;
		  	$style = 'background-color: #D4D6DC;';
		  	$last30['silver'] += $account['Total_Last_30'];
		  	$thisMonth['silver'] += $account['Total_This_Month'];
		} elseif($account['Total_Count'] >= 10) {
		  	$gold++;
		  	$style = 'background-color: #F9F5BB;';
		  	$last30['gold'] += $account['Total_Last_30'];
		  	$thisMonth['gold'] += $account['Total_This_Month'];
		}
		?>

		  <tr>
			<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($account['Last_Ordered'], 'shortdate'); ?></td>
			<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $account['Contact_ID']; ?>"><?php print $account['Customer']; ?></a></td>
			<td style="border-bottom: 1px solid #eee;">&nbsp;<?php echo $account['Phone']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Credit_Period']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Credit_Limit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Total_On_Credit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Total_Count']; ?></td>
		  </tr>

		<?php
	  }
	  ?>

	</table>

	<?php
	// 30 - 60 days
	$spentAccounts = array();

	foreach($dataArray as $dataArr) {
		$data3 = new DataQuery(sprintf("SELECT o.Ordered_On FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Ordered_On BETWEEN ADDDATE('%s', -60) AND ADDDATE('%s', -30) AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') ORDER BY o.Ordered_On DESC LIMIT 0, 1", mysql_real_escape_string($dataArr['Customer_ID']), $now, $now));
		$data2 = new DataQuery(sprintf("SELECT SUM(o.Total) AS Total, COUNT(o.Total) AS Count FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND  o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Customer_ID", mysql_real_escape_string($dataArr['Customer_ID'])));

		$orderedOn = $data3->Row['Ordered_On'];

		$account = array();
		$account['Last_Ordered'] = $orderedOn;
		$account['Contact_ID'] = $dataArr['Contact_ID'];
		$account['Customer'] = (empty($dataArr['Org_Name']))? $dataArr['Name_First'] . ' ' . $dataArr['Name_Last'] : $dataArr['Org_Name'];
		$account['Credit_Period'] = $dataArr['Credit_Period'];
		$account['Credit_Limit'] = number_format($dataArr['Credit_Limit'], 2, '.',',');
		$account['Total_On_Credit'] = $data2->Row['Total'];
		$account['Total_Count'] = $data2->Row['Count'];
		$account['Phone'] = $dataArr['Phone_1'];

		if($data3->TotalRows > 0) {
			if(!isset($customersUsed[$dataArr['Contact_ID']])) {
				$spentAccounts[] = $account;
				$customersUsed[$dataArr['Contact_ID']] = true;
			}
		}

		$data2->Disconnect();
		$data3->Disconnect();
	}

	arsort($spentAccounts);
	?>

	<br />
	<h3>Customers ordered in the last 30-60 days</h3>
	<p>The following customers have active credit accounts and ordered within the last 30-60 days that are not present in the above tables.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Period</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Limit</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
	  foreach($spentAccounts as $account) {
		$style = '';

		if(($account['Total_Count'] > 0) && ($account['Total_Count'] <= 2)) {
		  	$pink++;
		  	$style = 'background-color: #FFC7E4;';
		} elseif(($account['Total_Count'] >= 3) && ($account['Total_Count'] <= 4)) {
		  	$bronze++;
		  	$style = 'background-color: #EBE0BD;';
		} elseif(($account['Total_Count'] >= 5) && ($account['Total_Count'] <= 9)) {
		  	$silver++;
		  	$style = 'background-color: #D4D6DC;';
		} elseif($account['Total_Count'] >= 10) {
		  	$gold++;
		  	$style = 'background-color: #F9F5BB;';
		}
		?>

		  <tr>
			<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($account['Last_Ordered'], 'shortdate'); ?></td>
			<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $account['Contact_ID']; ?>"><?php print $account['Customer']; ?></a></td>
			<td style="border-bottom: 1px solid #eee;">&nbsp;<?php echo $account['Phone']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Credit_Period']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Credit_Limit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Total_On_Credit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Total_Count']; ?></td>
		  </tr>

		<?php
	  }
	  ?>

	</table>

	<?php
	// 60 - 90 days
	$spentAccounts = array();

	foreach($dataArray as $dataArr) {
		$data3 = new DataQuery(sprintf("SELECT o.Ordered_On FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Ordered_On BETWEEN ADDDATE('%s', -90) AND ADDDATE('%s', -60) AND o.Status<>'Unauthenticated' AND o.Status<>'Cancelled' ORDER BY o.Ordered_On DESC LIMIT 0, 1", $dataArr['Customer_ID'], $now, $now));
		$data2 = new DataQuery(sprintf("SELECT SUM(o.Total) AS Total, COUNT(o.Total) AS Count FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Customer_ID", mysql_real_escape_string($dataArr['Customer_ID'])));

		$orderedOn = $data3->Row['Ordered_On'];

		$account = array();
		$account['Last_Ordered'] = $orderedOn;
		$account['Contact_ID'] = $dataArr['Contact_ID'];
		$account['Customer'] = (empty($dataArr['Org_Name']))? $dataArr['Name_First'] . ' ' . $dataArr['Name_Last'] : $dataArr['Org_Name'];
		$account['Credit_Period'] = $dataArr['Credit_Period'];
		$account['Credit_Limit'] = number_format($dataArr['Credit_Limit'], 2, '.',',');
		$account['Total_On_Credit'] = $data2->Row['Total'];
		$account['Total_Count'] = $data2->Row['Count'];
		$account['Phone'] = $dataArr['Phone_1'];

		if($data3->TotalRows > 0) {
			if(!isset($customersUsed[$dataArr['Contact_ID']])) {
				$spentAccounts[] = $account;
				$customersUsed[$dataArr['Contact_ID']] = true;
			}
		}

		$data2->Disconnect();
		$data3->Disconnect();
	}

	arsort($spentAccounts);
	?>

	<br />
	<h3>Customers ordered in the last 60-90 days</h3>
	<p>The following customers have active credit accounts and ordered within the last 60-90 days that are not present in the above tables.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Period</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Limit</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
	  foreach($spentAccounts as $account) {
		$style = '';

		if(($account['Total_Count'] > 0) && ($account['Total_Count'] <= 2)) {
		  	$pink++;
		  	$style = 'background-color: #FFC7E4;';
		} elseif(($account['Total_Count'] >= 3) && ($account['Total_Count'] <= 4)) {
		  	$bronze++;
		  	$style = 'background-color: #EBE0BD;';
		} elseif(($account['Total_Count'] >= 5) && ($account['Total_Count'] <= 9)) {
		  	$silver++;
		  	$style = 'background-color: #D4D6DC;';
		} elseif($account['Total_Count'] >= 10) {
		  	$gold++;
		  	$style = 'background-color: #F9F5BB;';
		}
		?>

		  <tr>
			<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($account['Last_Ordered'], 'shortdate'); ?></td>
			<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $account['Contact_ID']; ?>"><?php print $account['Customer']; ?></a></td>
			<td style="border-bottom: 1px solid #eee;">&nbsp;<?php echo $account['Phone']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Credit_Period']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Credit_Limit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Total_On_Credit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Total_Count']; ?></td>
		  </tr>

		<?php
	  }
	  ?>

	</table>

	<?php
	// 90 - 120 days
	$spentAccounts = array();

	foreach($dataArray as $dataArr) {
		$data3 = new DataQuery(sprintf("SELECT o.Ordered_On FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Ordered_On BETWEEN ADDDATE('%s', -120) AND ADDDATE('%s', -90) AND o.Status<>'Unauthenticated' AND o.Status<>'Cancelled' ORDER BY o.Ordered_On DESC LIMIT 0, 1", mysql_real_escape_string($dataArr['Customer_ID']), $now, $now));
		$data2 = new DataQuery(sprintf("SELECT SUM(o.Total) AS Total, COUNT(o.Total) AS Count FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Customer_ID", mysql_real_escape_string($dataArr['Customer_ID'])));

		$orderedOn = $data3->Row['Ordered_On'];

		$account = array();
		$account['Last_Ordered'] = $orderedOn;
		$account['Contact_ID'] = $dataArr['Contact_ID'];
		$account['Customer'] = (empty($dataArr['Org_Name']))? $dataArr['Name_First'] . ' ' . $dataArr['Name_Last'] : $dataArr['Org_Name'];
		$account['Credit_Period'] = $dataArr['Credit_Period'];
		$account['Credit_Limit'] = number_format($dataArr['Credit_Limit'], 2, '.',',');
		$account['Total_On_Credit'] = $data2->Row['Total'];
		$account['Total_Count'] = $data2->Row['Count'];
		$account['Phone'] = $dataArr['Phone_1'];

		if($data3->TotalRows > 0) {
			if(!isset($customersUsed[$dataArr['Contact_ID']])) {
				$spentAccounts[] = $account;
				$customersUsed[$dataArr['Contact_ID']] = true;
			}
		}

		$data2->Disconnect();
		$data3->Disconnect();
	}

	arsort($spentAccounts);
	?>

	<br />
	<h3>Customers ordered in the last 90-120 days</h3>
	<p>The following customers have active credit accounts and ordered within the last 90-120 days that are not present in the above tables.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Period</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Limit</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
	  foreach($spentAccounts as $account) {
		$style = '';

		if(($account['Total_Count'] > 0) && ($account['Total_Count'] <= 2)) {
		  	$pink++;
		  	$style = 'background-color: #FFC7E4;';
		} elseif(($account['Total_Count'] >= 3) && ($account['Total_Count'] <= 4)) {
		  	$bronze++;
		  	$style = 'background-color: #EBE0BD;';
		} elseif(($account['Total_Count'] >= 5) && ($account['Total_Count'] <= 9)) {
		  	$silver++;
		  	$style = 'background-color: #D4D6DC;';
		} elseif($account['Total_Count'] >= 10) {
		  	$gold++;
		  	$style = 'background-color: #F9F5BB;';
		}
		?>

		 <tr>
			<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($account['Last_Ordered'], 'shortdate'); ?></td>
			<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $account['Contact_ID']; ?>"><?php print $account['Customer']; ?></a></td>
			<td style="border-bottom: 1px solid #eee;">&nbsp;<?php echo $account['Phone']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Credit_Period']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Credit_Limit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Total_On_Credit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Total_Count']; ?></td>
		  </tr>

		<?php
	  }
	  ?>

	</table>
	
	<?php
	// 120 - 180 days
	$spentAccounts = array();

	foreach($dataArray as $dataArr) {
		$data3 = new DataQuery(sprintf("SELECT o.Ordered_On FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Ordered_On BETWEEN ADDDATE('%s', -180) AND ADDDATE('%s', -120) AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') ORDER BY o.Ordered_On DESC LIMIT 0, 1", mysql_real_escape_string($dataArr['Customer_ID']), $now, $now));
		$data2 = new DataQuery(sprintf("SELECT SUM(o.Total) AS Total, COUNT(o.Total) AS Count FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Customer_ID", mysql_real_escape_string($dataArr['Customer_ID'])));

		$orderedOn = $data3->Row['Ordered_On'];

		$account = array();
		$account['Last_Ordered'] = $orderedOn;
		$account['Contact_ID'] = $dataArr['Contact_ID'];
		$account['Customer'] = (empty($dataArr['Org_Name']))? $dataArr['Name_First'] . ' ' . $dataArr['Name_Last'] : $dataArr['Org_Name'];
		$account['Credit_Period'] = $dataArr['Credit_Period'];
		$account['Credit_Limit'] = number_format($dataArr['Credit_Limit'], 2, '.',',');
		$account['Total_On_Credit'] = $data2->Row['Total'];
		$account['Total_Count'] = $data2->Row['Count'];
		$account['Phone'] = $dataArr['Phone_1'];

		if($data3->TotalRows > 0) {
			if(!isset($customersUsed[$dataArr['Contact_ID']])) {
				$spentAccounts[] = $account;
				$customersUsed[$dataArr['Contact_ID']] = true;
			}
		}

		$data2->Disconnect();
		$data3->Disconnect();
	}

	arsort($spentAccounts);
	?>

	<br />
	<h3>Customers ordered in the last 120-180 days</h3>
	<p>The following customers have active credit accounts and ordered within the last 120-180 days that are not present in the above tables.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Period</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Limit</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
	  foreach($spentAccounts as $account) {
		$style = '';

		if(($account['Total_Count'] > 0) && ($account['Total_Count'] <= 2)) {
		  	$pink++;
		  	$style = 'background-color: #FFC7E4;';
		} elseif(($account['Total_Count'] >= 3) && ($account['Total_Count'] <= 4)) {
		  	$bronze++;
		  	$style = 'background-color: #EBE0BD;';
		} elseif(($account['Total_Count'] >= 5) && ($account['Total_Count'] <= 9)) {
		  	$silver++;
		  	$style = 'background-color: #D4D6DC;';
		} elseif($account['Total_Count'] >= 10) {
		  	$gold++;
		  	$style = 'background-color: #F9F5BB;';
		}
		?>

		 <tr>
			<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($account['Last_Ordered'], 'shortdate'); ?></td>
			<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $account['Contact_ID']; ?>"><?php print $account['Customer']; ?></a></td>
			<td style="border-bottom: 1px solid #eee;">&nbsp;<?php echo $account['Phone']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Credit_Period']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Credit_Limit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Total_On_Credit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Total_Count']; ?></td>
		  </tr>

		<?php
	  }
	  ?>

	</table>
	
	<?php
	// 180 - 360 days
	$spentAccounts = array();

	foreach($dataArray as $dataArr) {
		$data3 = new DataQuery(sprintf("SELECT o.Ordered_On FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Ordered_On BETWEEN ADDDATE('%s', -360) AND ADDDATE('%s', -180) AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') ORDER BY o.Ordered_On DESC LIMIT 0, 1", mysql_real_escape_string($dataArr['Customer_ID']), $now, $now));
		$data2 = new DataQuery(sprintf("SELECT SUM(o.Total) AS Total, COUNT(o.Total) AS Count FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Customer_ID", mysql_real_escape_string($dataArr['Customer_ID'])));

		$orderedOn = $data3->Row['Ordered_On'];

		$account = array();
		$account['Last_Ordered'] = $orderedOn;
		$account['Contact_ID'] = $dataArr['Contact_ID'];
		$account['Customer'] = (empty($dataArr['Org_Name']))? $dataArr['Name_First'] . ' ' . $dataArr['Name_Last'] : $dataArr['Org_Name'];
		$account['Credit_Period'] = $dataArr['Credit_Period'];
		$account['Credit_Limit'] = number_format($dataArr['Credit_Limit'], 2, '.',',');
		$account['Total_On_Credit'] = $data2->Row['Total'];
		$account['Total_Count'] = $data2->Row['Count'];
		$account['Phone'] = $dataArr['Phone_1'];

		if($data3->TotalRows > 0) {
			if(!isset($customersUsed[$dataArr['Contact_ID']])) {
				$spentAccounts[] = $account;
				$customersUsed[$dataArr['Contact_ID']] = true;
			}
		}

		$data2->Disconnect();
		$data3->Disconnect();
	}

	arsort($spentAccounts);
	?>

	<br />
	<h3>Customers ordered in the last 180-360 days</h3>
	<p>The following customers have active credit accounts and ordered within the last 180-360 days that are not present in the above tables.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Period</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Limit</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
	  foreach($spentAccounts as $account) {
		$style = '';

		if(($account['Total_Count'] > 0) && ($account['Total_Count'] <= 2)) {
		  	$pink++;
		  	$style = 'background-color: #FFC7E4;';
		} elseif(($account['Total_Count'] >= 3) && ($account['Total_Count'] <= 4)) {
		  	$bronze++;
		  	$style = 'background-color: #EBE0BD;';
		} elseif(($account['Total_Count'] >= 5) && ($account['Total_Count'] <= 9)) {
		  	$silver++;
		  	$style = 'background-color: #D4D6DC;';
		} elseif($account['Total_Count'] >= 10) {
		  	$gold++;
		  	$style = 'background-color: #F9F5BB;';
		}
		?>

		 <tr>
			<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($account['Last_Ordered'], 'shortdate'); ?></td>
			<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $account['Contact_ID']; ?>"><?php print $account['Customer']; ?></a></td>
			<td style="border-bottom: 1px solid #eee;">&nbsp;<?php echo $account['Phone']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Credit_Period']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Credit_Limit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Total_On_Credit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Total_Count']; ?></td>
		  </tr>

		<?php
	  }
	  ?>

	</table>

	<?php
	// 360+ days
	$spentAccounts = array();

	foreach($dataArray as $dataArr) {
		$data3 = new DataQuery(sprintf("SELECT o.Ordered_On FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Ordered_On BETWEEN '0000-00-00 00:00:00' AND ADDDATE('%s', -360) AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') ORDER BY o.Ordered_On DESC LIMIT 0, 1", mysql_real_escape_string($dataArr['Customer_ID']), $now));
		$data2 = new DataQuery(sprintf("SELECT SUM(o.Total) AS Total, COUNT(o.Total) AS Count FROM orders AS o INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'credit' WHERE o.Customer_ID=%d AND o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') GROUP BY o.Customer_ID", mysql_real_escape_string($dataArr['Customer_ID'])));

		$orderedOn = $data3->Row['Ordered_On'];

		$account = array();
		$account['Last_Ordered'] = $orderedOn;
		$account['Contact_ID'] = $dataArr['Contact_ID'];
		$account['Customer'] = (empty($dataArr['Org_Name']))? $dataArr['Name_First'] . ' ' . $dataArr['Name_Last'] : $dataArr['Org_Name'];
		$account['Credit_Period'] = $dataArr['Credit_Period'];
		$account['Credit_Limit'] = number_format($dataArr['Credit_Limit'], 2, '.',',');
		$account['Total_On_Credit'] = $data2->Row['Total'];
		$account['Total_Count'] = $data2->Row['Count'];
		$account['Phone'] = $dataArr['Phone_1'];

		if($data3->TotalRows > 0) {
			if(!isset($customersUsed[$dataArr['Contact_ID']])) {
				$spentAccounts[] = $account;
				$customersUsed[$dataArr['Contact_ID']] = true;
			}
		}

		$data2->Disconnect();
		$data3->Disconnect();
	}

	arsort($spentAccounts);
	?>

	<br />
	<h3>All remaining customers</h3>
	<p>The following customers have active credit accounts but have not ordered during any of the above periods.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Period</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Credit Limit</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
	  foreach($spentAccounts as $account) {
		$style = '';

		if(($account['Total_Count'] > 0) && ($account['Total_Count'] <= 2)) {
			$pink++;
		  	$style = 'background-color: #FFC7E4;';
		  	$thisMonth['pink'] += $account['Total_This_Month'];
		} elseif(($account['Total_Count'] >= 3) && ($account['Total_Count'] <= 4)) {
			$bronze++;
		  	$style = 'background-color: #EBE0BD;';
		  	$thisMonth['bronze'] += $account['Total_This_Month'];
		} elseif(($account['Total_Count'] >= 5) && ($account['Total_Count'] <= 9)) {
			$silver++;
		  	$style = 'background-color: #D4D6DC;';
		  	$thisMonth['silver'] += $account['Total_This_Month'];
		} elseif($account['Total_Count'] >= 10) {
			$gold++;
		  	$style = 'background-color: #F9F5BB;';
		  	$thisMonth['gold'] += $account['Total_This_Month'];
		}
		?>

		 <tr>
			<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($account['Last_Ordered'], 'shortdate'); ?></td>
			<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $account['Contact_ID']; ?>"><?php print $account['Customer']; ?></a></td>
			<td style="border-bottom: 1px solid #eee;">&nbsp;<?php echo $account['Phone']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Credit_Period']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Credit_Limit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $account['Total_On_Credit']; ?></td>
			<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $account['Total_Count']; ?></td>
		  </tr>

		<?php
	  }
	  ?>

	</table>

	<br />
	<h3>Customers order frequency</h3>
	<p>The following table lists customer order frequencies for credit accounts.</p>
	
	<table width="100%" border="0" >
	  <tr>
		<th style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="left">&nbsp;</th>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="left"><strong>Number of Orders</strong></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right"><strong>Customers</strong></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right"><strong>Spend This Month / Average</strong></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right"><strong>Spend Last 30 Days / Average</strong></td>
	 </tr>
	 <tr>
		<th style="border-bottom:1px solid #aaaaaa; background-color: #F9F5BB;" align="left"><strong>Gold Frequency</strong></th>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #F9F5BB;" align="left">10+</td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #F9F5BB;" align="right"><?php print $gold; ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #F9F5BB;" align="right">&pound;<?php print number_format($thisMonth['gold'], 2, '.', ','); ?> / &pound;<?php print number_format(($thisMonth['gold']/$gold), 2, '.', ','); ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #F9F5BB;" align="right">&pound;<?php print number_format($last30['gold'], 2, '.', ','); ?> / &pound;<?php print number_format(($last30['gold']/$gold), 2, '.', ','); ?></td>
	 </tr>
	  <tr>
		<th style="border-bottom:1px solid #aaaaaa; background-color: #D4D6DC;" align="left"><strong>Silver Frequency</strong></th>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #D4D6DC;" align="left">5-9</td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #D4D6DC;" align="right"><?php print $silver; ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #D4D6DC;" align="right">&pound;<?php print number_format($thisMonth['silver'], 2, '.', ','); ?> / &pound;<?php print number_format(($thisMonth['silver']/$silver), 2, '.', ','); ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #D4D6DC;" align="right">&pound;<?php print number_format($last30['silver'], 2, '.', ','); ?> / &pound;<?php print number_format(($last30['silver']/$silver), 2, '.', ','); ?></td>
	 </tr>
	  <tr>
		<th style="border-bottom:1px solid #aaaaaa; background-color: #EBE0BD;" align="left"><strong>Bronze Frequency</strong></th>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #EBE0BD;" align="left">3-4</td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #EBE0BD;" align="right"><?php print $bronze; ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #EBE0BD;" align="right">&pound;<?php print number_format($thisMonth['bronze'], 2, '.', ','); ?> / &pound;<?php print number_format(($thisMonth['bronze']/$bronze), 2, '.', ','); ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #EBE0BD;" align="right">&pound;<?php print number_format($last30['bronze'], 2, '.', ','); ?> / &pound;<?php print number_format(($last30['bronze']/$bronze), 2, '.', ','); ?></td>
	 </tr>
	  <tr>
		<th style="border-bottom:1px solid #aaaaaa; background-color: #FFC7E4;" align="left"><strong>Pink Frequency</strong></th>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #FFC7E4;" align="left">1-2</td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #FFC7E4;" align="right"><?php print $pink; ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #FFC7E4;" align="right">&pound;<?php print number_format($thisMonth['pink'], 2, '.', ','); ?> / &pound;<?php print number_format(($thisMonth['pink']/$pink), 2, '.', ','); ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #FFC7E4;" align="right">&pound;<?php print number_format($last30['pink'], 2, '.', ','); ?> / &pound;<?php print number_format(($last30['pink']/$pink), 2, '.', ','); ?></td>
	 </tr>
	 
	 <?php
	 $data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer WHERE Is_Credit_Active='Y' AND Credit_Period>0"));
	 $none = $data->Row['Count'] - ($pink+$bronze+$silver+$gold);
	 $data->Disconnect();
	 ?>
	 
	 <tr>
		<th style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="left"><strong>Not Ordered</strong></th>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="left">&nbsp;</td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right"><?php print $none; ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right">&nbsp;</td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right">&nbsp;</td>
	 </tr>
	 <tr>
		<th style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="left"><strong>All</strong></th>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="left">&nbsp;</td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right"><?php print $pink+$bronze+$silver+$gold+$none; ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right">&pound;<?php print number_format($thisMonth['gold']+$thisMonth['silver']+$thisMonth['bronze']+$thisMonth['pink'], 2, '.', ','); ?> / &pound;<?php print number_format((($thisMonth['gold']+$thisMonth['silver']+$thisMonth['bronze']+$thisMonth['pink'])/($pink+$bronze+$silver+$gold)), 2, '.', ','); ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right">&pound;<?php print number_format($last30['gold']+$last30['silver']+$last30['bronze']+$last30['pink'], 2, '.', ','); ?> / &pound;<?php print number_format((($last30['gold']+$last30['silver']+$last30['bronze']+$last30['pink'])/($pink+$bronze+$silver+$gold)), 2, '.', ','); ?></td>
	 </tr>
	</table>

	<?php
}
$data->Disconnect();

require_once('lib/common/app_footer.php');