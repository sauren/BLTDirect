<?php
ini_set('max_execution_time', '120');

require_once('lib/common/app_header.php');

$session->Secure(2);
report();
exit();

function report(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page('Card Account Report', '');
	$page->Display('header');

	$bronze = 0;
	$silver = 0;
	$gold = 0;

	$now = date('Y-m-d H:i:s');
	$spentAccounts = array();

	new DataQuery("CREATE TEMPORARY TABLE card_orders SELECT c.Customer_ID, n.Contact_ID, p.Name_First, p.Name_Last, p.Phone_1, org.Org_Name, o.Ordered_On, o.Total
									FROM customer AS c
									INNER JOIN contact AS n ON c.Contact_ID=n.Contact_ID
									INNER JOIN person AS p ON p.Person_ID=n.Person_ID
									LEFT JOIN contact AS corg ON corg.Contact_ID=n.Parent_Contact_ID
									LEFT JOIN organisation AS org ON corg.Org_ID=org.Org_ID
									INNER JOIN orders AS o ON o.Customer_ID=c.Customer_ID
									INNER JOIN payment_method AS pm ON pm.Payment_Method_ID=o.Payment_Method_ID AND pm.Reference LIKE 'card'
									WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated')
									AND o.Order_Prefix IN ('W', 'U', 'L', 'M')");


	new DataQuery("CREATE TEMPORARY TABLE card_customers SELECT COUNT(*) AS	OrderCount, SUM(co.Total) AS OrderTotal,
									co.Customer_ID
									FROM card_orders AS co
									GROUP BY co.Customer_ID");

	new DataQuery(sprintf("ALTER TABLE card_customers ADD INDEX Customer_ID (Customer_ID)"));
	new DataQuery(sprintf("ALTER TABLE card_orders ADD INDEX Customer_ID (Customer_ID)"));
	?>

	<br />
	<h3>Customers ordered in the last 0-30 days (Live & Kicking)</h3>
	<p>The following customers have made web orders using cards and ordered within the last 0-30 days.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
		$data = new DataQuery(sprintf("SELECT cc.*, co.* FROM card_customers AS cc INNER JOIN card_orders AS co ON cc.Customer_ID=co.Customer_ID WHERE co.Ordered_On BETWEEN ADDDATE('%s', -30) AND '%s' AND cc.OrderCount>=3 ORDER BY co.Ordered_On DESC", $now, $now));

		while($data->Row) {
			if(!isset($spentAccounts[$data->Row['Customer_ID']])) {
				$spentAccounts[$data->Row['Customer_ID']] = true;

				if($data->Row['OrderCount'] >= 10) {
			  		$gold++;
			  		$style = 'background-color: #F9F5BB;';
			  	} elseif(($data->Row['OrderCount'] >= 5)) {
			  		$silver++;
			  		$style = 'background-color: #D4D6DC;';
			  	} elseif(($data->Row['OrderCount'] >= 3)) {
			  		$bronze++;
			  		$style = 'background-color: #EBE0BD;';
			  	}
			  	?>

				  <tr>
					<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($data->Row['Ordered_On'], 'shortdate'); ?></td>
				  	<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php print (empty($data->Row['Org_Name']))? $data->Row['Name_First'] . ' ' . $data->Row['Name_Last'] : $data->Row['Org_Name']; ?></a></td>
					<td style="border-bottom: 1px solid #eee;" align="right">&nbsp;<?php echo $data->Row['Phone_1']; ?></td>
					<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $data->Row['OrderTotal']; ?></td>
					<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $data->Row['OrderCount']; ?></td>
				  </tr>

				<?php
			}
			$data->Next();
		}

		$data->Disconnect();
	?>

	</table>

	<br />
	<h3>Customers ordered in the last 30-60 days (Dosey)</h3>
	<p>The following customers have  made web orders using cards and ordered within the last 30-60 days that are not present in the above tables.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
		$data = new DataQuery(sprintf("SELECT cc.*, co.* FROM card_customers AS cc INNER JOIN card_orders AS co ON cc.Customer_ID=co.Customer_ID WHERE co.Ordered_On BETWEEN ADDDATE('%s', -60) AND ADDDATE('%s', -30) AND cc.OrderCount>=3 ORDER BY co.Ordered_On DESC", $now, $now));

		while($data->Row) {
			if(!isset($spentAccounts[$data->Row['Customer_ID']])) {
				$spentAccounts[$data->Row['Customer_ID']] = true;

				if($data->Row['OrderCount'] >= 10) {
			  		$gold++;
			  		$style = 'background-color: #F9F5BB;';
			  	} elseif(($data->Row['OrderCount'] >= 5)) {
			  		$silver++;
			  		$style = 'background-color: #D4D6DC;';
			  	} elseif(($data->Row['OrderCount'] >= 3)) {
			  		$bronze++;
			  		$style = 'background-color: #EBE0BD;';
			  	}
			  	?>

				  <tr>
					<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($data->Row['Ordered_On'], 'shortdate'); ?></td>
				  	<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php print (empty($data->Row['Org_Name']))? $data->Row['Name_First'] . ' ' . $data->Row['Name_Last'] : $data->Row['Org_Name']; ?></a></td>
					<td style="border-bottom: 1px solid #eee;" align="right">&nbsp;<?php echo $data->Row['Phone_1']; ?></td>
					<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $data->Row['OrderTotal']; ?></td>
					<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $data->Row['OrderCount']; ?></td>
				  </tr>

				<?php
			}
			$data->Next();
		}

		$data->Disconnect();
	?>

	</table>

	<br />
	<h3>Customers ordered in the last 60-90 days (Sleeping)</h3>
	<p>The following customers have  made web orders using cards and ordered within the last 60-90 days that are not present in the above tables.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
		$data = new DataQuery(sprintf("SELECT cc.*, co.* FROM card_customers AS cc INNER JOIN card_orders AS co ON cc.Customer_ID=co.Customer_ID WHERE co.Ordered_On BETWEEN ADDDATE('%s', -90) AND ADDDATE('%s', -60) AND cc.OrderCount>=3 ORDER BY co.Ordered_On DESC", $now, $now));

		while($data->Row) {
			if(!isset($spentAccounts[$data->Row['Customer_ID']])) {
				$spentAccounts[$data->Row['Customer_ID']] = true;

				if($data->Row['OrderCount'] >= 10) {
			  		$gold++;
			  		$style = 'background-color: #F9F5BB;';
			  	} elseif(($data->Row['OrderCount'] >= 5)) {
			  		$silver++;
			  		$style = 'background-color: #D4D6DC;';
			  	} elseif(($data->Row['OrderCount'] >= 3)) {
			  		$bronze++;
			  		$style = 'background-color: #EBE0BD;';
			  	}
			  	?>

				  <tr>
					<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($data->Row['Ordered_On'], 'shortdate'); ?></td>
				  	<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php print (empty($data->Row['Org_Name']))? $data->Row['Name_First'] . ' ' . $data->Row['Name_Last'] : $data->Row['Org_Name']; ?></a></td>
					<td style="border-bottom: 1px solid #eee;" align="right">&nbsp;<?php echo $data->Row['Phone_1']; ?></td>
					<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $data->Row['OrderTotal']; ?></td>
					<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $data->Row['OrderCount']; ?></td>
				  </tr>

				<?php
			}
			$data->Next();
		}

		$data->Disconnect();
	?>

	</table>

	<br />
	<h3>Customers ordered in the last 90-120 days (Barely Alive)</h3>
	<p>The following customers have  made web orders using cards and ordered within the last 90-120 days that are not present in the above tables.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
		$data = new DataQuery(sprintf("SELECT cc.*, co.* FROM card_customers AS cc INNER JOIN card_orders AS co ON cc.Customer_ID=co.Customer_ID WHERE co.Ordered_On BETWEEN ADDDATE('%s', -120) AND ADDDATE('%s', -90) AND cc.OrderCount>=3 ORDER BY co.Ordered_On DESC", $now, $now));

		while($data->Row) {
			if(!isset($spentAccounts[$data->Row['Customer_ID']])) {
				$spentAccounts[$data->Row['Customer_ID']] = true;

				if($data->Row['OrderCount'] >= 10) {
			  		$gold++;
			  		$style = 'background-color: #F9F5BB;';
			  	} elseif(($data->Row['OrderCount'] >= 5)) {
			  		$silver++;
			  		$style = 'background-color: #D4D6DC;';
			  	} elseif(($data->Row['OrderCount'] >= 3)) {
			  		$bronze++;
			  		$style = 'background-color: #EBE0BD;';
			  	}
			  	?>

				  <tr>
					<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($data->Row['Ordered_On'], 'shortdate'); ?></td>
				  	<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php print (empty($data->Row['Org_Name']))? $data->Row['Name_First'] . ' ' . $data->Row['Name_Last'] : $data->Row['Org_Name']; ?></a></td>
					<td style="border-bottom: 1px solid #eee;" align="right">&nbsp;<?php echo $data->Row['Phone_1']; ?></td>
					<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $data->Row['OrderTotal']; ?></td>
					<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $data->Row['OrderCount']; ?></td>
				  </tr>

				<?php
			}
			$data->Next();
		}

		$data->Disconnect();
	?>

	</table>

	<br />
	<h3>Customers ordered in the last 120 days and over (Crematorium)</h3>
	<p>The following customers have  made web orders using cards and ordered within the last 120 days and over that are not present in the above tables.</p>
	<table width="100%" border="0" >
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Last Ordered</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Customer</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="left"><strong>Contact Number</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Spend Ever</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Total Orders</strong></td>
	  </tr>

	  <?php
		$data = new DataQuery(sprintf("SELECT cc.*, co.* FROM card_customers AS cc INNER JOIN card_orders AS co ON cc.Customer_ID=co.Customer_ID WHERE co.Ordered_On BETWEEN '0000-00-00 00:00:00' AND ADDDATE('%s', -120) AND cc.OrderCount>=3 ORDER BY co.Ordered_On DESC", $now));

		while($data->Row) {
			if(!isset($spentAccounts[$data->Row['Customer_ID']])) {
				$spentAccounts[$data->Row['Customer_ID']] = true;

				if($data->Row['OrderCount'] >= 10) {
			  		$gold++;
			  		$style = 'background-color: #F9F5BB;';
			  	} elseif(($data->Row['OrderCount'] >= 5)) {
			  		$silver++;
			  		$style = 'background-color: #D4D6DC;';
			  	} elseif(($data->Row['OrderCount'] >= 3)) {
			  		$bronze++;
			  		$style = 'background-color: #EBE0BD;';
			  	}
			  	?>

				  <tr>
					<td style="border-bottom: 1px solid #eee;<?php print $style; ?>" align="last"><?php echo cDatetime($data->Row['Ordered_On'], 'shortdate'); ?></td>
				  	<td style="border-bottom: 1px solid #eee;"><a target="_blank" href="contact_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>"><?php print (empty($data->Row['Org_Name']))? $data->Row['Name_First'] . ' ' . $data->Row['Name_Last'] : $data->Row['Org_Name']; ?></a></td>
					<td style="border-bottom: 1px solid #eee;" align="right">&nbsp;<?php echo $data->Row['Phone_1']; ?></td>
					<td style="border-bottom: 1px solid #eee;" align="right">&pound;<?php echo $data->Row['OrderTotal']; ?></td>
					<td style="border-bottom: 1px solid #eee;" align="right"><?php echo $data->Row['OrderCount']; ?></td>
				  </tr>

				<?php
			}
			$data->Next();
		}

		$data->Disconnect();
	?>

	</table>

	<?php
	$thisMonthBronze = 0;
	$thisMonthSilver = 0;
	$thisMonthGold = 0;

	$data = new DataQuery(sprintf("SELECT SUM(co.Total) AS PeriodTotal, cc.OrderCount FROM card_orders AS co INNER JOIN card_customers AS cc ON cc.Customer_ID=co.Customer_ID WHERE co.Ordered_On BETWEEN '%s' AND Now() GROUP BY co.Customer_ID", date('Y-m-01 00:00:00', strtotime($now))));
	while($data->Row) {
		if($data->Row['OrderCount'] >= 10) {
	  		$thisMonthGold += $data->Row['PeriodTotal'];
	  	} elseif(($data->Row['OrderCount'] >= 5)) {
	  		$thisMonthSilver += $data->Row['PeriodTotal'];
	  	} elseif(($data->Row['OrderCount'] >= 3)) {
	  		$thisMonthBronze += $data->Row['PeriodTotal'];
	  	}

		$data->Next();
	}
	$data->Disconnect();

	$lastMonthBronze = 0;
	$lastMonthSilver = 0;
	$lastMonthGold = 0;

	$data = new DataQuery(sprintf("SELECT SUM(co.Total) AS PeriodTotal, cc.OrderCount FROM card_orders AS co INNER JOIN card_customers AS cc ON cc.Customer_ID=co.Customer_ID WHERE co.Ordered_On BETWEEN ADDDATE(Now(), -30) AND Now() GROUP BY co.Customer_ID"));
	while($data->Row) {
		if($data->Row['OrderCount'] >= 10) {
	  		$lastMonthGold += $data->Row['PeriodTotal'];
	  	} elseif(($data->Row['OrderCount'] >= 5)) {
	  		$lastMonthSilver += $data->Row['PeriodTotal'];
	  	} elseif(($data->Row['OrderCount'] >= 3)) {
	  		$lastMonthBronze += $data->Row['PeriodTotal'];
	  	}

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery("DROP TABLE card_orders");
	$data->Disconnect();

	$data = new DataQuery("DROP TABLE card_customers");
	$data->Disconnect();
	?>

	<br />
	<h3>Customers order frequency</h3>
	<p>The following table lists customer order frequencies for all of the above tables.</p>
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
		<td style="border-bottom:1px solid #aaaaaa; background-color: #F9F5BB;" align="right">&pound;<?php print number_format($thisMonthGold, 2, '.', ','); ?> / &pound;<?php print number_format(($thisMonthGold/$gold), 2, '.', ','); ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #F9F5BB;" align="right">&pound;<?php print number_format($lastMonthGold, 2, '.', ','); ?> / &pound;<?php print number_format(($lastMonthGold/$gold), 2, '.', ','); ?></td>
	  </tr>
	  <tr>
		<th style="border-bottom:1px solid #aaaaaa; background-color: #D4D6DC;" align="left"><strong>Silver Frequency</strong></th>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #D4D6DC;" align="left">5-9</td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #D4D6DC;" align="right"><?php print $silver; ?></td>
	  	<td style="border-bottom:1px solid #aaaaaa; background-color: #D4D6DC;" align="right">&pound;<?php print number_format($thisMonthSilver, 2, '.', ','); ?> / &pound;<?php print number_format(($thisMonthSilver/$silver), 2, '.', ','); ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #D4D6DC;" align="right">&pound;<?php print number_format($lastMonthSilver, 2, '.', ','); ?> / &pound;<?php print number_format(($lastMonthSilver/$silver), 2, '.', ','); ?></td>
	  </tr>
	  <tr>
		<th style="border-bottom:1px solid #aaaaaa; background-color: #EBE0BD;" align="left"><strong>Bronze Frequency</strong></th>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #EBE0BD;" align="left">3-4</td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #EBE0BD;" align="right"><?php print $bronze; ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #EBE0BD;" align="right">&pound;<?php print number_format($thisMonthBronze, 2, '.', ','); ?> / &pound;<?php print number_format(($thisMonthBronze/$bronze), 2, '.', ','); ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #EBE0BD;" align="right">&pound;<?php print number_format($lastMonthBronze, 2, '.', ','); ?> / &pound;<?php print number_format(($lastMonthBronze/$bronze), 2, '.', ','); ?></td>
	  </tr>
	 <tr>
		<th style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="left"><strong>All</strong></th>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="left">3+</td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right"><?php print $bronze+$silver+$gold; ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right">&pound;<?php print number_format($thisMonthGold+$thisMonthSilver+$thisMonthBronze, 2, '.', ','); ?> / &pound;<?php print number_format((($thisMonthGold+$thisMonthSilver+$thisMonthBronze)/($gold+$silver+$bronze)), 2, '.', ','); ?></td>
		<td style="border-bottom:1px solid #aaaaaa; background-color: #fff;" align="right">&pound;<?php print number_format($lastMonthGold+$lastMonthSilver+$lastMonthBronze, 2, '.', ','); ?> / &pound;<?php print number_format((($lastMonthGold+$lastMonthSilver+$lastMonthBronze)/($gold+$silver+$bronze)), 2, '.', ','); ?></td>
	  </tr>
	</table>

	<?php
	require_once('lib/common/app_footer.php');
}
?>