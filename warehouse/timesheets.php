<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Timesheet.php');

function recordPackingHours($subTime, $userId, $type, $hours) {
	if(!empty($hours)) {
		$data = new DataQuery(sprintf("SELECT Timesheet_ID FROM timesheet WHERE User_ID=%d AND Date='%s' AND Type LIKE '%s'", mysql_real_escape_string($userId), date('Y-m-d H:i:s', $subTime), mysql_real_escape_string($type)));

		if($data->TotalRows > 1) {
			new DataQuery(sprintf("DELETE FROM timesheet WHERE User_ID=%d AND Date='%s' AND Type LIKE '%s'", mysql_real_escape_string($userId), date('Y-m-d H:i:s', $subTime), mysql_real_escape_string($type)));

			$timesheet = new Timesheet();
			$timesheet->Date = date('Y-m-d H:i:s', $subTime);
			$timesheet->Hours = $hours;
			$timesheet->User->ID = $userId;
			$timesheet->Type = $type;
			$timesheet->Add();

		} elseif($data->TotalRows == 1) {
			$timesheet = new Timesheet($data->Row['Timesheet_ID']);
			$timesheet->Hours = $hours;
			$timesheet->Update();

		} else {
			$timesheet = new Timesheet();
			$timesheet->Date = date('Y-m-d H:i:s', $subTime);
			$timesheet->Hours = $hours;
			$timesheet->User->ID = $userId;
			$timesheet->Type = $type;
			$timesheet->Add();
	}
		$data->Disconnect();
	} else {
		new DataQuery(sprintf("DELETE FROM timesheet WHERE User_ID=%d AND Date='%s' AND Type LIKE '%s'", mysql_real_escape_string($userId), date('Y-m-d H:i:s', $subTime), mysql_real_escape_string($type)));
	}
}

$session->Secure();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('packer', 'Packer', 'select', '0', 'numeric_unsigned', 1, 11, true, 'onchange="reloadPage();"');
$form->AddOption('packer', '0', '');

$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS User FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID WHERE u.Is_Packer='Y' ORDER BY User ASC"));
while($data->Row) {
	$form->AddOption('packer', $data->Row['User_ID'], $data->Row['User']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('month', 'Month', 'select', date('m'), 'anything', 1, 2, true, 'onchange="reloadPage();"');
$form->AddField('year', 'Year', 'select', date('Y'), 'anything', 4, 4, true, 'onchange="reloadPage();"');
$form->AddField('start', 'Start', 'hidden', date('Y-m-d H:i:s', mktime(0, 0, 0, $form->GetValue('month'), 1,  $form->GetValue('year'))), 'anything', 19, 19);
$form->AddField('end', 'End', 'hidden', date('Y-m-d H:i:s', mktime(0, 0, 0,  $form->GetValue('month') + 1, 1,  $form->GetValue('year'))), 'anything', 19, 19);
$form->AddField('type', 'Type', 'select', 'Packing', 'anything', 1, 30, true, 'onchange="reloadPage();"');
$form->AddOption('type', 'Packing', 'Packing');
$form->AddOption('type', 'Holiday', 'Holiday');
$form->AddOption('type', 'Sick', 'Sick');

for($i=1; $i<=12; $i++) {
	$time = mktime(0, 0, 0, $i, 1, date('Y'));

	$form->AddOption('month', date('m', $time), date('M', $time));
}

for($i=date('Y')-1; $i<=date('Y'); $i++) {
	$form->AddOption('year', $i, $i);
}

$hours = array();

$data = new DataQuery(sprintf("SELECT t.Date, t.User_ID, SUM(t.Hours) AS Hours FROM timesheet AS t WHERE t.Date>='%s' AND t.Date<'%s' AND t.Type LIKE '%s' AND t.User_ID=%d GROUP BY t.Date, t.User_ID", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end')), mysql_real_escape_string($form->GetValue('type')), mysql_real_escape_string($form->GetValue('packer'))));
while($data->Row) {
	$hours[$data->Row['Date']][$data->Row['User_ID']] = $data->Row['Hours'];

	$data->Next();
}
$data->Disconnect();

$startTime = strtotime($form->GetValue('start'));
$endTime = strtotime($form->GetValue('end'));

$subTime = $startTime;
$index = 0;

while($subTime < $endTime) {
	if(!isset($hours[date('Y-m-d H:i:s', $subTime)])) {
		$form->AddField('start_hours_' . $index, 'Start Time (Hours)', 'select', '', 'numeric_unsigned', 0, 2, false);
		$form->AddOption('start_hours_' . $index, '', '');
		$form->AddField('start_minutes_' . $index, 'Start Time (Minutes)', 'select', '', 'numeric_unsigned', 0, 2, false);
		$form->AddOption('start_minutes_' . $index, '', '');
		$form->AddField('end_hours_' . $index, 'End Time (Hours)', 'select', '', 'numeric_unsigned', 0, 2, false);
		$form->AddOption('end_hours_' . $index, '', '');
		$form->AddField('end_minutes_' . $index, 'End Time (Minutes)', 'select', '', 'numeric_unsigned', 0, 2, false);
		$form->AddOption('end_minutes_' . $index, '', '');

		for($i=0; $i<24; $i++) {
			$value = sprintf('%s%d', ($i < 10) ? '0' : '', $i);

			if(($i>=7) && ($i<=17)) {
				$form->AddOption('start_hours_' . $index, $value, $value);
			}

			if(($i>=7) && ($i<=17)) {
				$form->AddOption('end_hours_' . $index, $value, $value);
			}
		}

		for($i=0; $i<60; $i=$i+15) {
			$value = sprintf('%s%d', ($i < 10) ? '0' : '', $i);

			$form->AddOption('start_minutes_' . $index, $value, $value);
			$form->AddOption('end_minutes_' . $index, $value, $value);
		}
	}

	$index++;
	$subTime = mktime(0, 0, 0, date('m', $startTime), date('d', $startTime)+$index, date('Y', $startTime));
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$subTime = $startTime;
		$index = 0;

		while($subTime < $endTime) {
			if(!isset($hours[date('Y-m-d H:i:s', $subTime)])) {
				$subStartTimeHours = $form->GetValue('start_hours_' . $index);
				$subStartTimeMinutes = $form->GetValue('start_minutes_' . $index);
				$subEndTimeHours = $form->GetValue('end_hours_' . $index);
				$subEndTimeMinutes = $form->GetValue('end_minutes_' . $index);

				if(!empty($subStartTimeHours) && !empty($subStartTimeMinutes) && !empty($subEndTimeHours) && !empty($subEndTimeMinutes)) {
					$hourCount = floatval($subEndTimeHours);
					$hourCount += floatval($subEndTimeMinutes / 60);
					$hourCount -= floatval($subStartTimeHours);
					$hourCount -= floatval($subStartTimeMinutes / 60);

					if($hourCount <= 0) {
						$form->AddError('Please ensure that all end dates come after their start dates.');
						break;
					}
				}
			}

			$index++;
			$subTime = mktime(0, 0, 0, date('m', $startTime), date('d', $startTime)+$index, date('Y', $startTime));
		}

		if($form->Valid) {
			$subTime = $startTime;
			$index = 0;

			while($subTime < $endTime) {
				if(!isset($hours[date('Y-m-d H:i:s', $subTime)])) {
					$subStartTimeHours = $form->GetValue('start_hours_' . $index);
					$subStartTimeMinutes = $form->GetValue('start_minutes_' . $index);
					$subEndTimeHours = $form->GetValue('end_hours_' . $index);
					$subEndTimeMinutes = $form->GetValue('end_minutes_' . $index);

					if(!empty($subStartTimeHours) && !empty($subStartTimeMinutes) && !empty($subEndTimeHours) && !empty($subEndTimeMinutes)) {
						$hourCount = floatval($subEndTimeHours);
						$hourCount += floatval($subEndTimeMinutes / 60);
						$hourCount -= floatval($subStartTimeHours);
						$hourCount -= floatval($subStartTimeMinutes / 60);

						recordPackingHours($subTime, $form->GetValue('packer'), $form->GetValue('type'), $hourCount);
					}
				}

				$index++;
				$subTime = mktime(0, 0, 0, date('m', $startTime), date('d', $startTime)+$index, date('Y', $startTime));
			}

			redirect(sprintf("Location: %s?packer=%d&year=%s&month=%s&type=%s", $_SERVER['PHP_SELF'], $form->GetValue('packer'), $form->GetValue('year'), $form->GetValue('month'), $form->GetValue('type')));
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/templates/portal-warehouse.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Warehouse Portal</title>
	<!-- InstanceEndEditable -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="/ignition/css/i_content.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="/warehouse/css/lightbulbs.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="/warehouse/css/lightbulbs_print.css" rel="stylesheet" type="text/css" media="print" />
	<link href="/warehouse/css/default.css" rel="stylesheet" type="text/css" media="screen" />
	<script language="javascript" src="/warehouse/js/generic_1.js" type="text/javascript"></script>
    <script language="javascript" type="text/javascript">
	var toggleGroup = function(group) {
		var e = document.getElementById(group);
		if(e) {
			if(e.style.display == 'none') {
				e.style.display = 'block';
			} else {
				e.style.display = 'none';
			}
		}
	}
	</script>
    <!-- InstanceBeginEditable name="head" -->
    <script language="javascript" type="text/javascript">
    var packer = null;
    var month = null;
    var year = null;
    var type = null;

    window.onload = function() {
		packer = document.getElementById('packer');
		month = document.getElementById('month');
		year = document.getElementById('year');
		type = document.getElementById('type');
    }

    var getQueryString = function() {
		return 'packer=' + packer.value + '&month=' + month.value + '&year=' + year.value + '&type=' + type.value;
    }

    var reloadPage = function() {
    	window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?' + getQueryString();
    }
    </script>
	<!-- InstanceEndEditable -->
</head>
<body>
<div id="Wrapper">
	<div id="Header">
		<a href="/warehouse" title="Back to Home Page"><img src="/images/template/logo_blt_1.jpg" width="185" height="70" border="0" class="logo" alt="BLT Direct Logo" /></a>
		<div id="NavBar" class="warehouse">Warehouse Portal</div>
		<div id="CapTop" class="warehouse">
			<div class="curveLeft"></div>
		</div>
		<ul id="NavTop" class="nav warehouse">
			<?php if($session->IsLoggedIn){
				echo sprintf('<li class="login"><a href="%s?action=logout" title="Logout">Logout</a></li>', $_SERVER['PHP_SELF']);
			} else {
				echo '<li class="login"><a href="/index.php" title="Login as a BLT Direct supplier or warehouse">Login</a></li>';
			}?>
			<li class="account"><a href="/warehouse/account_settings.php" title="Your BLT Direct Account">My Account</a></li>
			<li class="contact"><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
			<li class="help"><a href="/support.php" title="Light Bulb, Lamp and Tube Help">Help</a></li>
		</ul>
	</div>

<div id="PageWrapper">
	<div id="Page">
		<div id="PageContent"><!-- InstanceBeginEditable name="pageContent" -->
				<h1>Timesheets</h1>
				<br />

				<?php
				if(!$form->Valid) {
					echo $form->GetError();
					echo '<br />';
				}

				echo $form->Open();
				echo $form->GetHTML('action');
				echo $form->GetHTML('confirm');
				echo $form->GetHTML('start');
				echo $form->GetHTML('end');

				$window = new StandardWindow("Timesheets");
				$webForm = new StandardForm;

				echo $window->Open();
				echo $window->AddHeader('Alter the criteria for your timesheets.');
				echo $window->OpenContent();
				echo $webForm->Open();
				echo $webForm->AddRow($form->GetLabel('packer'), $form->GetHTML('packer'));
				echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type'));
				echo $webForm->AddRow('Date', $form->GetHTML('month') . $form->GetHTML('year'));
				echo $webForm->Close();
				echo $window->CloseContent();
				echo $window->Close();

				if($form->GetValue('packer') > 0) {
					?>

					<br />

					<table width="100%" border="0" >
						<tr>
							<td style="border-bottom:1px solid #aaaaaa"><strong>Date</strong></td>
							<td style="border-bottom:1px solid #aaaaaa"><strong>Day</strong></td>
							<td style="border-bottom:1px solid #aaaaaa"><strong>Start Time</strong></td>
							<td style="border-bottom:1px solid #aaaaaa"><strong>End Time</strong></td>
							<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Hours</strong></td>
						</tr>

						<?php
						$subTime = $startTime;
						$index = 0;

						while($subTime < $endTime) {
							$style = (date('N', $subTime) >= 6) ? 'background-color: #ccc;' : '';
							?>

							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td style="<?php echo $style; ?>"><?php echo date('jS F', $subTime); ?></td>
								<td style="<?php echo $style; ?>"><?php echo date('D', $subTime); ?></td>
								<td style="<?php echo $style; ?>"><?php echo !isset($hours[date('Y-m-d H:i:s', $subTime)][$form->GetValue('packer')]) ? sprintf('%s : %s', $form->GetHTML('start_hours_' . $index), $form->GetHTML('start_minutes_' . $index)) : ''; ?></td>
								<td style="<?php echo $style; ?>"><?php echo !isset($hours[date('Y-m-d H:i:s', $subTime)][$form->GetValue('packer')]) ? sprintf('%s : %s', $form->GetHTML('end_hours_' . $index), $form->GetHTML('end_minutes_' . $index)) : ''; ?></td>
								<td style="<?php echo $style; ?>" align="right"><?php echo isset($hours[date('Y-m-d H:i:s', $subTime)][$form->GetValue('packer')]) ? $hours[date('Y-m-d H:i:s', $subTime)][$form->GetValue('packer')] : ''; ?></td>
							</tr>

							<?php
							$index++;
							$subTime = mktime(0, 0, 0, date('m', $startTime), date('d', $startTime)+$index, date('Y', $startTime));
						}
						?>

					</table>
					<br />

					<input type="submit" class="greySubmit" name="submit" value="submit" />

					<?php
				}

				echo $form->Close();
				?>

				<!-- InstanceEndEditable -->
		</div>
  	</div>

	<div id="PageFooter">
		<ul class="links">
			<li><a href="/privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
			<li><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
		</ul>
		<p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
	</div>
</div>

	<div id="LeftNav">
		<div id="CatalogueNav" class="greyNavLeft">
			<div id="NavLeftItems" class="warehouse">
			<p class="title"><strong>Warehouse Options </strong> </p>

			<ul class="rootCat">
				<?php
				if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N'))) {
					?>
					<li><a href="/warehouse/orders_pending.php">Pending Orders</a></li>
                    
                    <?php
					if($session->Warehouse->Type == 'B') {
						echo '<li><a href="/warehouse/orders_pending_tax_free.php">Pending Orders<br />(Tax Free)</a></li>';
					}
					?>
                    
					<li><a href="/warehouse/orders_collections.php">Collection Orders</a></li>
					<li><a href="/warehouse/orders_backordered.php">Backordered Orders</a></li>
                    
                    <?php
					if(($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsBidder == 'Y')) {
						echo '<li><a href="/warehouse/orders_bidding.php">Bidding Orders</a></li>';
					}
					
					if($session->Warehouse->Type == 'S') {
						echo '<li><a href="/warehouse/orders_warehouse_declined.php">Warehouse Declined Orders</a></li>';
					}
					?>
                    
					<li><a href="/warehouse/orders_despatched.php">Despatched Orders</a></li>
					<li><a href="/warehouse/orders_search.php">Search Orders</a></li>
					<?php
				}

				if($session->Warehouse->Type == 'B') {
					?>
					<li><a href="/warehouse/orders_auto_despatch.php">Auto Despatch Orders</a></li>
					<?php
				}

				if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N'))) {
					?>

					<li><a href="/warehouse/despatches_track.php">Track Consignments</a></li>
					<li><a href="/warehouse/products_stocked.php">Stocked Products</a></li>

					<?php
				}
				?>
                
                <li><a href="/warehouse/products_backordered.php">Products Backordered</a></li>
                
                <?php
				if(($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N')) {
					?>

					<li><a href="/warehouse/products_held.php">Products Held</a></li>
					<li><a href="/warehouse/products_supplied.php">Products Supplied</a></li>

					<?php
					$supplier = new Supplier($session->Warehouse->Contact->ID);

					if($supplier->IsComparable == 'Y') {
						?>

						<li><a href="/warehouse/products_unsupplied.php">Unsupplied Products</a></li>

						<?php
					}
				}

				if($session->Warehouse->Type == 'B') {
					?>

					<li><a href="/warehouse/timesheets.php">Timesheets</a></li>

					<li><a href="javascript:toggleGroup('navLabels');" target="_self">Labels</a></li>
                    <ul id="navLabels" class="subCat" style="display: none;">
                        <li><a href="/warehouse/downloads/2nd-class-stamp.pdf" target="_blank">2<sup>nd</sup> Class Stamps</a></li>
                    </ul>
                    
					<?php
				}

				if($session->Warehouse->Type == 'S') {
					?>
					
					<li><a href="javascript:toggleGroup('navReserves');" target="_self">Reserves</a></li>
					<ul id="navReserves" class="subCat" style="display: none;">
						<li><a href="/warehouse/reserves_pending.php">Pending</a></li>
						<li><a href="/warehouse/reserves_completed.php">Completed</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navPriceEnquiries');" target="_self">Price Enquiries</a></li>
					<ul id="navPriceEnquiries" class="subCat" style="display: none;">
						<li><a href="/warehouse/price_enquiries_pending.php">Pending</a></li>
						<li><a href="/warehouse/price_enquiries_completed.php">Completed</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navPurchaseRequests');" target="_self">Purchase Requests</a></li>
					<ul id="navPurchaseRequests" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/purchase_requests_pending.php">Pending</a></li>
                    	<li><a href="/warehouse/purchase_requests_confirmed.php">Confirmed</a></li>
                    	<li><a href="/warehouse/purchase_requests_completed.php">Completed</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navPurchaseOrders');" target="_self">Purchase Orders</a></li>
					<ul id="navPurchaseOrders" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/purchase_orders_unfulfilled.php">Unfulfilled</a></li>
                    	<li><a href="/warehouse/purchase_orders_fulfilled.php">Fulfilled</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navReturnRequests');" target="_self">Return Requests</a></li>
					<ul id="navReturnRequests" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/supplier_return_requests_pending.php">Pending</a></li>
                    	<li><a href="/warehouse/supplier_return_requests_confirmed.php">Confirmed</a></li>
                    	<li><a href="/warehouse/supplier_return_requests_completed.php">Completed</a></li>
					</ul>
                    
					<li><a href="/warehouse/supplier_return_requests_pending_purchase.php">Damages</a></li>
                    
                    <li><a href="javascript:toggleGroup('navInvoiceQueries');" target="_self">Invoice Queries</a></li>
                    <ul id="navInvoiceQueries" class="subCat" style="display: none;">
                        <li><a href="/warehouse/supplier_invoice_queries_pending.php">Pending</a></li>
                        <li><a href="/warehouse/supplier_invoice_queries_resolved.php">Resolved</a></li>
                    </ul>
                        
                    <li><a href="javascript:toggleGroup('navDebits');" target="_self">Debits</a></li>
					<ul id="navDebits" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/debits_pending.php">Pending</a></li>
                    	<li><a href="/warehouse/debits_completed.php">Completed</a></li>
					</ul>
                    
                    <li><a href="javascript:toggleGroup('navReports');" target="_self">Reports</a></li>
					<ul id="navReports" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/report_orders_despatched.php">Orders Despatched</a></li>
						<li><a href="/warehouse/report_reserved_stock.php">Reserved Stock</a></li>
						<li><a href="/warehouse/report_stock_dropped.php">Stock Dropped</a></li>
					</ul>

					<?php
				}
				?>
				
				<li><a href="/warehouse/account_settings.php">Account Settings</a></li>
			</ul>
			</div>
			<div class="cap"></div>
			<div class="shadow"></div>
		</div>
	</div>
</div>
</body>
<!-- InstanceEnd --></html>