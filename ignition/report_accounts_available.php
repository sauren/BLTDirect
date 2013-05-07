<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Contact.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ContactSchedule.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Order.php');

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
		$data = new DataQuery(sprintf("SELECT MAX(o.Order_ID) AS Order_ID, COUNT(o.Order_ID) AS Orders FROM orders AS o INNER JOIN customer AS cu ON cu.Customer_ID=o.Customer_ID AND cu.Contact_ID=%d GROUP BY cu.Contact_ID", mysql_real_escape_string($contactItem)));
		if($data->TotalRows > 0) {
			$order = new Order($data->Row['Order_ID']);

			$schedule = new ContactSchedule();
			$schedule->ContactID = $contactItem;
			$schedule->Type->GetByReference('account');
			$schedule->ScheduledOn = date('Y-m-d H:i:s');
			$schedule->Note = sprintf('This available contact last ordered on %s and has %d orders to date.', cDatetime($order->CreatedOn, 'shortdate'), $data->Row['Orders']);
			$schedule->OwnedBy = $GLOBALS['SESSION_USER_ID'];
			$schedule->Add();

			$contact = new Contact($contactItem);
			$contact->AccountManager->ID = $GLOBALS['SESSION_USER_ID'];
			$contact->Update();
			$contact->UpdateAccountManager();
		}
	}

	redirect(sprintf("Location: account_schedules.php"));
}

function view() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'schedule', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('type', 'Show Type', 'select', 'O', 'alpha', 1, 1, false, 'onchange="toggleType(this);"');
	$form->AddOption('type', 'O', 'Organisation');
	$form->AddOption('type', 'I', 'Individual');

	$script = sprintf('<script language="javascript" type="text/javascript">
		function toggleType(obj) {
			window.location.href = \'?type=\' + obj.value;
		}
		</script>');
	
	$page = new Page('Available Accounts Report', '');
	$page->AddToHead($script);
	$page->Display('header');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo sprintf('<br /><h3>Available Accounts</h3>');
	echo '<p>Listing available contact accounts.</p>';
	
	echo '<strong>' . $form->GetLabel('type') . '</strong><br />';
	echo $form->GetHTML('type');
	
	echo '<br />';
	echo '<br />';
	
	if($form->GetValue('type') == 'O') {
		$table = new DataTable('organisation');
		$table->SetSQL(sprintf("SELECT c.Contact_ID, o.Org_Name AS Contact_Name, MAX(od.Created_On) AS Last_Ordered_On, COUNT(DISTINCT od.Order_ID) AS Order_Count, SUM(od.Total) AS Total_Turnover, FORMAT(AVG(od.Total), 2) AS Average_Turnover FROM contact AS c2 INNER JOIN (SELECT Contact_ID, Parent_Contact_ID FROM contact WHERE Account_Manager_ID=0 GROUP BY Parent_Contact_ID) AS c ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN contact AS c3 ON c3.Parent_Contact_ID=c2.Contact_ID INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID LEFT JOIN customer AS cu ON cu.Contact_ID=c3.Contact_ID LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID GROUP BY c.Contact_ID HAVING Order_Count>0"));
		$table->SetTotalRowSQL(sprintf("SELECT COUNT(DISTINCT c.Contact_ID) AS TotalRows FROM contact AS c2 INNER JOIN (SELECT Contact_ID, Parent_Contact_ID FROM contact WHERE Account_Manager_ID=0 AND Parent_Contact_ID>0 GROUP BY Parent_Contact_ID) AS c ON c.Parent_Contact_ID=c2.Contact_ID INNER JOIN contact AS c3 ON c3.Parent_Contact_ID=c.Parent_Contact_ID INNER JOIN customer AS cu ON cu.Contact_ID=c3.Contact_ID INNER JOIN orders AS od ON cu.Customer_ID=od.Customer_ID"));
		$table->AddField('Contact ID', 'Contact_ID', 'left');
		$table->AddField('Organisation', 'Contact_Name', 'left');
		$table->AddField('Last Ordered', 'Last_Ordered_On', 'left');
		$table->AddField('Orders', 'Order_Count', 'right');
		$table->AddField('Total Turnover', 'Total_Turnover', 'right');
		$table->AddField('Average Turnover', 'Average_Turnover', 'right');
		$table->AddInput('', 'N', 'N', 'schedule', 'Contact_ID', 'checkbox');
		$table->AddLink("contact_profile.php?cid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
		$table->SetMaxRows(15);
		$table->SetOrderBy("Order_Count");
		$table->Order = "DESC";
		$table->Finalise();
		$table->DisplayTable();
		echo '<br />';
		$table->DisplayNavigation();
		
	} elseif($form->GetValue('type') == 'I') {
		$table = new DataTable('individual');
		$table->SetSQL(sprintf("SELECT c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name, MAX(od.Created_On) AS Last_Ordered_On, COUNT(DISTINCT od.Order_ID) AS Order_Count, SUM(od.Total) AS Total_Turnover, FORMAT(AVG(od.Total), 2) AS Average_Turnover FROM contact AS c INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID WHERE c.Account_Manager_ID=0 AND c.Parent_Contact_ID=0 GROUP BY c.Contact_ID HAVING Order_Count>0"));
		$table->SetTotalRowSQL(sprintf("SELECT COUNT(DISTINCT c.Contact_ID) AS TotalRows FROM contact AS c LEFT JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID WHERE c.Account_Manager_ID=0 AND c.Parent_Contact_ID=0"));
		$table->AddField('Contact ID', 'Contact_ID', 'left');
		$table->AddField('Individual', 'Contact_Name', 'left');
		$table->AddField('Last Ordered', 'Last_Ordered_On', 'left');
		$table->AddField('Orders', 'Order_Count', 'right');
		$table->AddField('Total Turnover', 'Total_Turnover', 'right');
		$table->AddField('Average Turnover', 'Average_Turnover', 'right');
		$table->AddInput('', 'N', 'N', 'schedule', 'Contact_ID', 'checkbox');
		$table->AddLink("contact_profile.php?cid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
		$table->SetMaxRows(15);
		$table->SetOrderBy("Order_Count");
		$table->Order = "DESC";
		$table->Finalise();
		$table->DisplayTable();
		echo '<br />';
		$table->DisplayNavigation();
	}

	echo '<br />';
	echo '<input type="submit" class="btn" name="schedule" value="schedule" />';
	
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}
