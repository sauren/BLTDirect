<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(2);

$accounts = array();
$accountManagers = array();
$accountStats = array();

$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Account_Manager FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN contact AS c ON c.Account_Manager_ID=u.User_ID ORDER BY Account_Manager ASC"));
while($data->Row) {
	$accountManagers[$data->Row['User_ID']] = $data->Row['Account_Manager'];

	$data->Next();
}
$data->Disconnect();

foreach($accountManagers as $accountManagerId=>$accountManager) {
	$data = new DataQuery(sprintf("SELECT COUNT(o.Order_ID) AS Orders FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID INNER JOIN contact AS c ON c.Contact_ID=cu.Contact_ID AND c.Account_Manager_ID=%d GROUP BY cu.Customer_ID", mysql_real_escape_string($accountManagerId)));
	while($data->Row) {
		if(!isset($accounts[$accountManagerId][$data->Row['Orders']])) {
			$accounts[$accountManagerId][$data->Row['Orders']] = 0;
		}

		$accounts[$accountManagerId][$data->Row['Orders']]++;

		$data->Next();
	}
	$data->Disconnect();
}

foreach($accountManagers as $accountManagerId=>$accountManager) {
	$accountStats[$accountManagerId] = array('White' => 0, 'Bronze' => 0, 'Silver' => 0, 'Gold' => 0);

	foreach($accounts[$accountManagerId] as $orderCount=>$frequency) {
		if($orderCount >= 50) {
			$accountStats[$accountManagerId]['Gold'] += $frequency;
		} elseif($orderCount >= 20) {
			$accountStats[$accountManagerId]['Silver'] += $frequency;
		} elseif($orderCount >= 10) {
			$accountStats[$accountManagerId]['Bronze'] += $frequency;
		} else {
			$accountStats[$accountManagerId]['White'] += $frequency;
		}
	}
}

$page = new Page('Accounts Breakdown Report', '');
$page->Display('header');
?>

<br />
<h3>Accounts Summary</h3>
<p>Listing account details for each account manager.</p>

<table width="100%" border="0" >
	<tr>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Account Manager</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>White (1-9)</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Bronze (10-19)</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Silver (20-49)</strong></td>
		<td style="border-bottom: 1px solid #aaaaaa;"><strong>Gold (50+)</strong></td>
	</tr>

	<?php
	foreach($accountManagers as $accountManagerId=>$accountManager) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo $accountManager; ?></td>
			<td><?php echo $accountStats[$accountManagerId]['White']; ?></td>
			<td><?php echo $accountStats[$accountManagerId]['Bronze']; ?></td>
			<td><?php echo $accountStats[$accountManagerId]['Silver']; ?></td>
			<td><?php echo $accountStats[$accountManagerId]['Gold']; ?></td>
		</tr>

		<?php
	}
	?>

</table><br />

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>