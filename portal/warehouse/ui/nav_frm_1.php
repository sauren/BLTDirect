<?php
require_once('../../../ignition/lib/classes/ApplicationHeader.php');
require_once('../lib/common/config.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserSession.php');

$session = new UserSession($GLOBALS['PORTAL_NAME'], $GLOBALS['PORTAL_URL']);
$session->Start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Warehouse Portal</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<base target="_top" />
	<link href="../css/default.css" rel="stylesheet" type="text/css" media="screen" />
	<script language="javascript" type="text/javascript">
		function toggleCat(cat) {
			var e = document.getElementById(cat);
			if(e) {
				if(e.style.display == 'none') {
					e.style.display = 'block';
				} else {
					e.style.display = 'none';
				}
			}
		}
	</script>
</head>
<body>
	<div id="Wrapper">
		<div id="LeftNav">
			<p class="title"><strong>Navigation</strong></p>

			<ul class="rootCat">
				<li><a href="javascript:toggleCat('navOrders');" target="_self">Orders</a></li>
				<ul id="navOrders" class="subCat" style="display: none;">
					<li><a href="../orders_packing.php" target="i_content">Orders Packing</a></li>
					<li><a href="../orders_packing_tax_free.php" target="i_content">Orders Packing (Tax Free)</a></li>
					<li><a href="../orders_collections.php" target="i_content">Collection Orders</a></li>
					<li><a href="../orders_despatched.php" target="i_content">Despatched Orders</a></li>
					<li><a href="../orders_auto_despatch.php" target="i_content">Auto Despatch Orders</a></li>
					<li><a href="../order_search.php" target="i_content">Search Orders</a></li>
				</ul>
				
				<li><a href="../returns_common.php" target="i_content">Common Returns</a></li>
				
				<li><a href="javascript:toggleCat('navDespatches');" target="_self">Despatches</a></li>
				<ul id="navDespatches" class="subCat" style="display: none;">
					<li><a href="../despatch_track.php" target="i_content">Track Despatch</a></li>
				</ul>
				
				<li><a href="javascript:toggleCat('navPurchases');" target="_self">Purchases</a></li>
				<ul id="navPurchases" class="subCat" style="display: none;">
					<li><a href="../purchases_unfulfilled.php" target="i_content">Unfulfilled Purchases</a></li>
					<li><a href="../purchase_barcode_stock.php" target="i_content">Barcode Stock Insert</a></li>
				</ul>
				
				<li><a href="javascript:toggleCat('navWorkTasks');" target="_self">Health &amp; Safety</a></li>
				<ul id="navWorkTasks" class="subCat" style="display: none;">
					<li><a href="../work_log_add.php" target="i_content">Report Incident</a></li>
					<li><a href="../work_tasks.php" target="i_content">Tasks</a></li>
					<li><a href="../work_task_schedules.php" target="i_content">Tasks Schedule</a></li>
				</ul>

				<li><a href="javascript:toggleCat('navTimesheets');" target="_self">Timesheets</a></li>
				<ul id="navTimesheets" class="subCat" style="display: none;">
					<li><a href="../timesheet_create.php" target="i_content">Create New Timesheet</a></li>
					<li><a href="../timesheets.php" target="i_content">Timesheets</a></li>
				</ul>
				
				<li><a href="javascript:toggleCat('navLabels');" target="_self">Labels</a></li>
				<ul id="navLabels" class="subCat" style="display: none;">
					<li><a href="../downloads/2nd-class-stamp.pdf" target="_blank">2<sup>nd</sup> Class Stamps</a></li>
				</ul>
			</ul>
		</div>

		<div id="LeftNav">
			<p class="title"><strong>Quick Links</strong></p>

			<ul class="rootCat">
				<li><a href="../orders_packing.php" target="i_content">Orders Packing</a></li>
				<li><a href="../returns_common.php" target="i_content">Common Returns</a></li>
				<li><a href="../work_log_add.php" target="i_content">Report Incident</a></li>
				<li><a href="../order_search.php" target="i_content">Search Orders</a></li>
				<li><a href="../user_security.php" target="i_content">Security Settings</a></li>
			</ul>
			<div class="cap"></div>
			<div class="shadow"></div>
		</div>
	</div>
</body>
</html>