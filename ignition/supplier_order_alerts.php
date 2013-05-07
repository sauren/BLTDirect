<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderWarehouseNote.php');

$session->Secure(2);

$order = new Order($_REQUEST['oid']);

$customerNotes = array();
$warehouseNotes = array();

$data = new DataQuery(sprintf("SELECT Order_Note_ID FROM order_note WHERE Order_ID=%d AND Is_Alert='Y' AND Is_Public='Y'", mysql_real_escape_string($order->ID)));
while($data->Row){
	$customerNotes[] = new OrderNote($data->Row['Order_Note_ID']);

	$data->Next();
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT own.Order_Warehouse_Note_ID FROM order_warehouse_note AS own INNER JOIN warehouse AS w ON w.Warehouse_ID=own.Warehouse_ID WHERE own.Order_ID=%d AND own.Is_Alert='Y' AND w.Type_Reference_ID=%d AND w.Type='S'", mysql_real_escape_string($order->ID), mysql_real_escape_string($session->Supplier->ID)));
while($data->Row){
	$warehouseNotes[] = new OrderWarehouseNote($data->Row['Order_Warehouse_Note_ID']);

	$data->Next();
}
$data->Disconnect();

if($action == 'dismiss') {
	foreach($customerNotes as $note) {
		$id = 'dismiss_customer_' . $note->ID;

		if(isset($_REQUEST[$id]) && ($_REQUEST[$id] == 'Y')){
			new DataQuery(sprintf("UPDATE order_note SET Is_Alert='N' WHERE Order_Note_ID=%d", mysql_real_escape_string($note->ID)));
		}
	}

	foreach($warehouseNotes as $note) {
		$id = 'dismiss_warehouse_' . $note->ID;

		if(isset($_REQUEST[$id]) && ($_REQUEST[$id] == 'Y')){
			new DataQuery(sprintf("UPDATE order_warehouse_note SET Is_Alert='N' WHERE Order_Warehouse_Note_ID=%d", mysql_real_escape_string($note->ID)));
		}
	}

	echo "<html><head><script>window.self.close();</script></head><body></body></html>";
	require_once('lib/common/app_footer.php');
	exit;
}

$script = sprintf('<script language="javascript" type="text/javascript">
	var registrations = new Array();
	registrations.push("notesCustomer");
	registrations.push("notesWarehouse");

	var showSection = function(section) {
		var e = null;

		for(var i=0; i<registrations.length; i++) {
			e = document.getElementById(registrations[i]);
			if(e) {
				e.style.display = "none";
			}
		}

		e = document.getElementById(section);
		if(e) {
			e.style.display = "block";
		}
	}
	</script>');

$page = new Page('Order Alerts', '');
$page->AddToHead($script);
$page->Display('header');

echo '<p><img src="./images/icon_alert_1.gif" align="absmiddle" /> <strong>Important!</strong> Please read the important notes below. You can remove this alert the next time you open this order by dismissing all alerts.</p>';
?>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="action" value="dismiss" />
	<input type="hidden" name="confirm" value="true" />
	<input type="hidden" name="oid" value="<?php echo $order->ID; ?>" />

	<br />
	<ul style="margin: 0; padding: 0;">

		<?php
		if(count($customerNotes) > 0) {
			?>
			<li style="display: inline; margin: 0 1px 1px 0; padding: 5px 10px 5px 10px; background-color: #ddd;"><a href="javascript:showSection('notesCustomer');">Customer Notes</a></li>
			<?php
		}

		if(count($warehouseNotes) > 0) {
			?>
			<li style="display: inline; margin: 0 1px 1px 0; padding: 5px 10px 5px 10px; background-color: #ddd;"><a href="javascript:showSection('notesWarehouse');">Warehouse Notes</a></li>
			<?php
		}
		?>
	</ul>
	<br />

	<table id="notesCustomer" class="catProducts" cellspacing="0" style="display: none;">
		<tr>
			<th width="50">Dismiss</th>
			<th>Message</th>
		</tr>

		<?php
		foreach($customerNotes as $note) {
			?>

			<tr>
				<td align="center"><input type="checkbox" name="dismiss_customer_<?php echo $note->ID; ?>" value="Y" /></td>
				<td>
					<p>
						<strong>Subject:</strong> <?php echo $note->Subject; ?>
					</p>
					<?php echo $note->Message; ?>
				</td>
			</tr>

			<?php
		}
		?>

	</table>

	<table id="notesWarehouse" class="catProducts" cellspacing="0" style="display: none;">
		<tr>
			<th width="50">Dismiss</th>
			<th>Message</th>
		</tr>

		<?php
		foreach($warehouseNotes as $note) {
			?>

			<tr>
				<td align="center"><input type="checkbox" name="dismiss_warehouse_<?php echo $note->ID;?>" value="Y" /></td>
				<td>
					<p>
						<strong>Warehouse:</strong> <?php echo $note->Warehouse->Name; ?><br />
						<strong>Subject:</strong> <?php echo $note->Type->Name; ?>
					</p>
					<?php echo $note->Note; ?>
				</td>
			</tr>

			<?php
		}
		?>

	</table>

	<div align="center">
	<br />
		<input type="submit" name="OK" value="OK" class="btn" />
	</div>
</form>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');