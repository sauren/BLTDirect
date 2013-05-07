<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ContactSchedule.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Contact.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/User.php');

if($action == 'schedule') {
	$session->Secure(3);
	schedule();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function schedule() {
	$contact = array();

	if(isset($_REQUEST['confirm'])) {
		foreach($_REQUEST as $key=>$value) {
			if(preg_match('/schedule_([0-9]*)/', $key, $matches)) {
				$contact[] = $matches[1];
			}
		}
	}

	foreach($contact as $contactItem) {
		$data = new DataQuery(sprintf("SELECT MAX(o.Order_ID) AS Order_ID FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID AND cu.Contact_ID=%d GROUP BY cu.Contact_ID", mysql_real_escape_string($contactItem)));
		if($data->TotalRows > 0) {
			$order = new Order($data->Row['Order_ID']);

			$schedule = new ContactSchedule();
			$schedule->ContactID = $contactItem;
			$schedule->Type->GetByReference('despatched');
			$schedule->ScheduledOn = date('Y-m-d H:i:s');
			$schedule->Note = sprintf('This contacts recent despatched order (#<a href="order_details.php?orderid=%d">%s%s</a>) was ordered on %s and requires following up.', $order->ID, $order->Prefix, $order->ID, cDatetime($order->CreatedOn, 'shortdate'));
			$schedule->OwnedBy = $GLOBALS['SESSION_USER_ID'];
			$schedule->Add();
		}
	}

	redirect(sprintf("Location: account_schedules.php"));
}

function view() {
	$contact = array();

	new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_contact SELECT cu.Customer_ID, c.Contact_ID, c.Account_Manager_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Contact_Name, MAX(od.Created_On) AS Last_Ordered_On, COUNT(DISTINCT od.Order_ID) AS Order_Count FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c.Parent_Contact_ID=c2.Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN customer AS cu ON c.Contact_ID=cu.Contact_ID INNER JOIN orders AS od ON cu.Customer_ID=od.Customer_ID WHERE c.Account_Manager_ID=0 OR c.Account_Manager_ID=%d GROUP BY c.Contact_ID", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	new DataQuery(sprintf("ALTER TABLE temp_contact ADD INDEX Last_Ordered_On (Last_Ordered_On)"));

	$data = new DataQuery(sprintf("SELECT * FROM temp_contact WHERE ADDDATE(Last_Ordered_On, INTERVAL 14 DAY)<NOW() ORDER BY Last_Ordered_On DESC LIMIT 0, 100"));
	while($data->Row) {
		$contact[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'schedule', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	foreach($contact as $contactItem) {
		$form->AddField('schedule_' . $contactItem['Contact_ID'], 'Schedule', 'checkbox', 'N', 'boolean', 1, 1, false);
	}

	$page = new Page('Despatched Contacts Report', '');
	$page->Display('header');

	$green = '#25D70E';
	$yellow = '#D7D50C';
	$red = '#D7550D';

	$user = new User($GLOBALS['SESSION_USER_ID']);
	$user->Get();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo sprintf('<br /><h3>Recently Despatched Contacts</h3>');
	echo sprintf('<p>Listing %d available contact accounts which have recently dispatched orders.</p>', $data->TotalRows);
	?>

	<table width="100%" border="0">
		<tr>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Contact ID</strong></td>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Contact</strong></td>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Account Manager</strong></td>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Orders</strong></td>
			<td valign="top" style="border-bottom:1px solid #aaaaaa;"><strong>Last Ordered</strong></td>
			<td valign="top" style="border-bottom:1px solid #aaaaaa; width: 1%;">&nbsp;</td>
		</tr>

		<?php
		foreach($contact as $contactItem) {
			$dates = array();
			$date = '';
			$month = 2629800;
			$styleLastOrdered = '';

			if(preg_match('/^[\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2}$/', $contactItem['Last_Ordered_On'])) {
				$date = strtotime($contactItem['Last_Ordered_On']);
				$now = time();
				$period = $now - $date;

				if($period < $month) {
					$styleLastOrdered = sprintf('style="background-color: %s;"', $green);
				} elseif($period < ($month * 2)) {
					$styleLastOrdered = sprintf('style="background-color: %s;"', $yellow);
				} else {
					$styleLastOrdered = sprintf('style="background-color: %s;"', $red);
				}
			}
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><a href="contact_profile.php?cid=<?php echo $contactItem['Contact_ID']; ?>"><?php echo $contactItem['Contact_ID']; ?></a></td>
				<td><?php echo $contactItem['Contact_Name']; ?></td>
				<td><?php echo ($user->ID == $contactItem['Account_Manager_ID']) ? trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)) : ''; ?>&nbsp;</td>
				<td><?php echo $contactItem['Order_Count']; ?></td>
				<td <?php echo $styleLastOrdered; ?>><?php echo cDatetime($contactItem['Last_Ordered_On'], 'shortdatetime'); ?></td>
				<td align="center"><?php echo $form->GetHTML('schedule_' . $contactItem['Contact_ID']); ?></td>
			</tr>

			<?php
		}
		?>

	</table><br />

	<input type="submit" class="btn" name="createschedules" value="create schedules" />

	<?php
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
?>