<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerSessionArchive.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BlacklistUserAgent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BlacklistIPAddress.php');

$reportSession = new CustomerSessionArchive();
$isBlacklistedIPAddress = false;
$isBlacklistedUserAgent = false;
$blacklistIPAddress = new BlacklistIPAddress();
$blacklistUserAgent = new BlacklistUserAgent();
$sessionItems = array();
$hostname = '';
$avgPageTime = 0;
$timeConnected = 0;

if(!isset($_REQUEST['id']) || !$reportSession->GetByID($_REQUEST['id'])) {
	redirect(sprintf("Location: stat_session_overview.php"));
}

$reportSession->UserAgent->Get();

$hostname = gethostbyaddr($reportSession->IPAddress);

if($hostname == $reportSession->IPAddress) {
	$hostname = '&nbsp;';
}

if($blacklistIPAddress->IsBlacklisted($reportSession->IPAddress)) {
	$isBlacklistedIPAddress = true;
}

if($blacklistUserAgent->IsBlacklisted($reportSession->UserAgent->String)) {
	$isBlacklistedUserAgent = true;
}

if($action == 'blacklistipaddress') {
	$blacklistIPAddress->SetIPAddress($reportSession->IPAddress);
	$blacklistIPAddress->Reason = 'No reason given.';
	$blacklistIPAddress->Add();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $reportSession->ID));

} elseif($action == 'blacklistuseragent') {
	$blacklistUserAgent->UserAgent = $reportSession->UserAgent->String;
	$blacklistUserAgent->Reason = 'No reason given.';
	$blacklistUserAgent->Add();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $reportSession->ID));
}

$page = new Page('Session Details', sprintf('Statistics for the session %s.', $reportSession->PHPSessionID));
$page->Display('header');

if($isBlacklistedIPAddress) {
	$bubble = new Bubble('IP Address Blacklisted', 'The most recent IP address this session used to access the server has been blacklisted.');

	echo $bubble->GetHTML();
	echo '<br />';
}

if($isBlacklistedUserAgent) {
	$bubble = new Bubble('User Agent Blacklisted', 'The most recent user agent string this session used to access the server has been blacklisted.');

	echo $bubble->GetHTML();
	echo '<br />';
}

$data = new DataQuery(sprintf("SELECT csi.Page_Request, csi.Created_On, csi.Customer_ID, cu.Contact_ID FROM customer_session_item AS csi LEFT JOIN customer AS cu ON cu.Customer_ID=csi.Customer_ID WHERE csi.Session_ID=%d ORDER BY csi.Created_On ASC", mysql_real_escape_string($reportSession->ID)));
while($data->Row) {
	$sessionItems[] = array(
		'PageRequest' => $data->Row['Page_Request'],
		'CreatedOn' => $data->Row['Created_On'],
		'CustomerID' => $data->Row['Customer_ID'],
		'ContactID' => $data->Row['Contact_ID']);

	$data->Next();
}
$data->Disconnect();

$timeConnected = strtotime($sessionItems[count($sessionItems) - 1]['CreatedOn']) - strtotime($sessionItems[0]['CreatedOn']);
$avgPageTime = number_format($timeConnected / count($sessionItems), 1, '.', '');
?>

<h3>Session Details</h3>
<br />

<table width="100%" border="0">
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td width="20%"><strong>Session ID</strong></td>
		<td><?php echo $reportSession->ID; ?></td>
	</tr>
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><strong>PHP Session ID</strong></td>
		<td><?php echo $reportSession->PHPSessionID; ?></td>
	</tr>
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><strong>Is Active</strong></td>
		<td><?php echo ($reportSession->IsActive == 'Y') ? 'Yes' : 'No'; ?></td>
	</tr>
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><strong>Referrer</strong></td>
		<td><?php echo !empty($reportSession->Referrer) ? sprintf('<a href="%s">%s</a>', $reportSession->Referrer, $reportSession->Referrer) : ''; ?></td>
	</tr>
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><strong>Search Term</strong></td>
		<td><?php echo $reportSession->ReferrerSearchTerm; ?></td>
	</tr>
</table><br />

<h3>Connection Details</h3>
<br />

<table width="100%" border="0">
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td width="20%"><strong>IP Address</strong></td>
		<td>
			<?php
			echo $reportSession->IPAddress;

			if(!$isBlacklistedIPAddress) {
				echo sprintf(' (<a href="%s?action=blacklistipaddress&id=%d">Blacklist</a>)', $_SERVER['PHP_SELF'], $reportSession->ID);
			}
			?>
		</td>
	</tr>
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><strong>Hostname</strong></td>
		<td><?php echo $hostname; ?></td>
	</tr>
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><strong>User Agent</strong></td>
		<td>
			<?php
			echo $reportSession->UserAgent->String;

			if(!$isBlacklistedUserAgent) {
				echo sprintf(' (<a href="%s?action=blacklistuseragent&id=%d">Blacklist</a>)', $_SERVER['PHP_SELF'], $reportSession->ID);
			}
			?>
	</tr>
</table><br />

<h3>Request Details</h3>
<br />

<table width="100%" border="0">
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td width="20%"><strong>Page Requests</strong></td>
		<td><?php echo count($sessionItems); ?></td>
	</tr>
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><strong>Time Connected</strong></td>
		<td><?php echo number_format($timeConnected/60, 1, '.', ''); ?> minutes</td>
	</tr>
	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		<td><strong>Average Page Time</strong></td>
		<td><?php echo $avgPageTime; ?> seconds</td>
	</tr>
</table><br />

<h3>Page Requests</h3>
<br />

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>#</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Page Request</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Time Requested</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Customer</strong></td>
	</tr>

	<?php
	for($i=0; $i<count($sessionItems); $i++) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td><?php echo $i+1; ?></td>
			<td><?php echo $sessionItems[$i]['PageRequest']; ?></td>
			<td nowrap="nowrap"><?php echo cDatetime($sessionItems[$i]['CreatedOn'], 'shortdatetime'); ?></td>
			<td>
				<?php
				if($sessionItems[$i]['CustomerID'] > 0) {
					if($sessionItems[$i]['ContactID'] > 0) {
						echo sprintf('<a href="contact_profile.php?cid=%d">%d</a>', $sessionItems[$i]['ContactID'], $sessionItems[$i]['CustomerID']);
					} else {
						echo $sessionItems[$i]['CustomerID'];
					}
				}
				?>
			</td>
		</tr>

		<?php
	}
	?>

</table>

<?php
require_once('lib/common/app_footer.php');
?>