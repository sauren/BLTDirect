<?php
require_once('lib/common/app_header.php');

if($action == 'report' || id_param('user', 0) > 0) {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

    $form = new Form($_SERVER['PHP_SELF'],'GET');
    $form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
    $form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
    $form->AddField('users', 'Account Manager', 'select', '', 'numeric_unsigned', 1, 11);
    $form->AddOption('users', '', '');

    $data = new DataQuery(sprintf("SELECT c.Account_Manager_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Account_Manager FROM users AS u INNER JOIN contact AS c ON c.Account_Manager_ID=u.User_ID INNER JOIN person AS p ON p.Person_ID=u.Person_ID GROUP BY c.Account_Manager_ID"));
	while($data->Row) {
		$form->AddOption('users', $data->Row['Account_Manager_ID'], $data->Row['Account_Manager']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			redirect(sprintf("Location: %s?action=report&user=%d", $_SERVER['PHP_SELF'], $form->GetValue('users')));
		}
	}

    $page = new Page('Managed Accounts Report', '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

    if(!$form->Valid){
        echo $form->GetError();
        echo "<br>";
    }

	$window = new StandardWindow("Report on Account Managers.");
	$webForm = new StandardForm;

    echo $form->Open();
    echo $form->GetHTML('action');
    echo $form->GetHTML('confirm');

    echo $window->Open();
	echo $window->AddHeader('Select the account manager for this report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('users'), $form->GetHTML('users'));
	echo $webForm->AddRow('', '<input class="btn" name="submit" value="submit" type="submit" />');
	echo $webForm->Close();
    echo $window->CloseContent();
    echo $window->Close();
    echo $form->Close();

    $page->Display('footer');
    require_once('lib/common/app_footer.php');
}

function report(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	if(!isset($_REQUEST['user'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$page = new Page('Managed Accounts Report', '');
	$page->Display('header');

	$user = new User($_REQUEST['user']);
	$user->Get();

	echo sprintf('<br /><h3>%s</h3>', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)));
	echo '<p>Listing contacts managed by this user.</p>';

	$creditedOrganisationTotals = array(
		'orders' => 0,
		'total' => 0,
		'average' => 0
	);

	$creditedOrganisationSql = new DataQuery(sprintf("SELECT SUM(totals.Order_Count) as Order_Count, SUM(totals.Total_Turnover) as Total_Turnover, SUM(totals.Average_Turnover) as Average_Turnover
		FROM (
			SELECT COUNT(DISTINCT od.Order_ID) AS Order_Count, SUM(od.Total) AS Total_Turnover, FORMAT(AVG(od.Total), 2) AS Average_Turnover
			FROM contact AS c2
			INNER JOIN (
				SELECT Contact_ID, Parent_Contact_ID
				FROM contact
				WHERE Account_Manager_ID=%d
				GROUP BY Parent_Contact_ID
			) AS c ON c.Parent_Contact_ID=c2.Contact_ID
			INNER JOIN contact_credit_account AS cca ON cca.contactId=c.Contact_ID
			INNER JOIN contact AS c3 ON c3.Parent_Contact_ID=c2.Contact_ID
			INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID
			INNER JOIN customer AS cu ON cu.Contact_ID=c3.Contact_ID
			LEFT JOIN (
				SELECT Contact_ID, MAX(Completed_On) AS Last_Contacted_On
				FROM contact_schedule
				WHERE Is_Complete='Y'
				GROUP BY Contact_ID
			) AS cs ON c.Contact_ID=cs.Contact_ID
			LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID
			GROUP BY c.Contact_ID
		) as totals", mysql_real_escape_string($user->ID)));
	while($creditedOrganisationSql->Row){
		$creditedOrganisationTotals['orders'] =+ $creditedOrganisationSql->Row['Order_Count'];
		$creditedOrganisationTotals['total'] =+ $creditedOrganisationSql->Row['Total_Turnover'];
		$creditedOrganisationTotals['average'] =+ $creditedOrganisationSql->Row['Average_Turnover'];
		$creditedOrganisationSql->Next();
	}

	$table1 = new DataTable('credited_organisations');
	$table1->SetSQL(sprintf("SELECT c.Contact_ID, o.Org_Name AS Contact_Name, cs.Last_Contacted_On, MAX(od.Created_On) AS Last_Ordered_On, COUNT(DISTINCT od.Order_ID) AS Order_Count, SUM(od.Total) AS Total_Turnover, FORMAT(AVG(od.Total), 2) AS Average_Turnover
FROM contact AS c2
INNER JOIN (
	SELECT Contact_ID, Parent_Contact_ID
	FROM contact
	WHERE Account_Manager_ID=%d
	GROUP BY Parent_Contact_ID
) AS c ON c.Parent_Contact_ID=c2.Contact_ID
INNER JOIN contact_credit_account AS cca ON cca.contactId=c.Contact_ID
INNER JOIN contact AS c3 ON c3.Parent_Contact_ID=c2.Contact_ID
INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID
INNER JOIN customer AS cu ON cu.Contact_ID=c3.Contact_ID
LEFT JOIN (
	SELECT Contact_ID, MAX(Completed_On) AS Last_Contacted_On
	FROM contact_schedule
	WHERE Is_Complete='Y'
	GROUP BY Contact_ID
) AS cs ON c.Contact_ID=cs.Contact_ID
LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID
GROUP BY c.Contact_ID", mysql_real_escape_string($user->ID)));
	$table1->AddField('Contact ID', 'Contact_ID', 'left');
	$table1->AddField('Organisation', 'Contact_Name', 'left');
	$table1->AddField('Last Ordered', 'Last_Ordered_On', 'left');
	$table1->AddField('Last Contacted', 'Last_Contacted_On', 'left');
	$table1->AddField('Orders', 'Order_Count', 'right');
	$table1->AddField('Total Turnover', 'Total_Turnover', 'right');
	$table1->AddField('Average Turnover', 'Average_Turnover', 'right');
	$table1->AddLink("contact_profile.php?cid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
	$table1->SetMaxRows(15);
	$table1->SetOrderBy("Contact_Name");
	$table1->Order = "ASC";
	$table1->Finalise();

	echo sprintf('<p style="text-decoration:underline;">Credit Account Organisations: %s</p>', $table1->TotalRows);
	echo '<p>';
	echo sprintf('Total Orders: %s<br />', $creditedOrganisationTotals['orders']);
	echo sprintf('Total Turnover: %s<br />', $creditedOrganisationTotals['total']);
	echo sprintf('Total Average Turnover: %s<br />', $creditedOrganisationTotals['average']);
	echo '</p>';

	$table1->DisplayTable();
	echo '<br />';
	$table1->DisplayNavigation();
	echo '<br />';
	echo '<br />';

	$creditedIndividualTotals = array(
		'orders' => 0,
		'total' => 0,
		'average' => 0
	);

	$creditedIndividualSql = new DataQuery(sprintf("SELECT SUM(totals.Order_Count) as Order_Count, SUM(totals.Total_Turnover) as Total_Turnover, SUM(totals.Average_Turnover) as Average_Turnover
		FROM (
			SELECT COUNT(DISTINCT od.Order_ID) AS Order_Count, SUM(od.Total) AS Total_Turnover, FORMAT(AVG(od.Total), 2) AS Average_Turnover
			FROM contact AS c
			INNER JOIN contact_credit_account AS cca ON cca.contactId=c.Contact_ID
			INNER JOIN person AS p ON p.Person_ID=c.Person_ID
			INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID
			LEFT JOIN (
				SELECT Contact_ID, MAX(Completed_On) AS Last_Contacted_On
				FROM contact_schedule
				WHERE Is_Complete='Y'
				GROUP BY Contact_ID
			) AS cs ON c.Contact_ID=cs.Contact_ID
			LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID
			WHERE c.Account_Manager_ID=%d AND c.Parent_Contact_ID=0
			GROUP BY c.Contact_ID
		) as totals", mysql_real_escape_string($user->ID)));
	while($creditedIndividualSql->Row){
		$creditedIndividualTotals['orders'] =+ $creditedIndividualSql->Row['Order_Count'];
		$creditedIndividualTotals['total'] =+ $creditedIndividualSql->Row['Total_Turnover'];
		$creditedIndividualTotals['average'] =+ $creditedIndividualSql->Row['Average_Turnover'];
		$creditedIndividualSql->Next();
	}

	$table2 = new DataTable('credited_individuals');
	$table2->SetSQL(sprintf("SELECT c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name, cs.Last_Contacted_On, MAX(od.Created_On) AS Last_Ordered_On, COUNT(DISTINCT od.Order_ID) AS Order_Count, SUM(od.Total) AS Total_Turnover, FORMAT(AVG(od.Total), 2) AS Average_Turnover
FROM contact AS c
INNER JOIN contact_credit_account AS cca ON cca.contactId=c.Contact_ID
INNER JOIN person AS p ON p.Person_ID=c.Person_ID
INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID
LEFT JOIN (
	SELECT Contact_ID, MAX(Completed_On) AS Last_Contacted_On
	FROM contact_schedule
	WHERE Is_Complete='Y'
	GROUP BY Contact_ID
) AS cs ON c.Contact_ID=cs.Contact_ID
LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID
WHERE c.Account_Manager_ID=%d AND c.Parent_Contact_ID=0
GROUP BY c.Contact_ID", mysql_real_escape_string($user->ID)));
	$table2->AddField('Contact ID', 'Contact_ID', 'left');
	$table2->AddField('Individual', 'Contact_Name', 'left');
	$table2->AddField('Last Ordered', 'Last_Ordered_On', 'left');
	$table2->AddField('Last Contacted', 'Last_Contacted_On', 'left');
	$table2->AddField('Orders', 'Order_Count', 'right');
	$table2->AddField('Total Turnover', 'Total_Turnover', 'right');
	$table2->AddField('Average Turnover', 'Average_Turnover', 'right');
	$table2->AddLink("contact_profile.php?cid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
	$table2->SetMaxRows(15);
	$table2->SetOrderBy("Contact_Name");
	$table2->Order = "ASC";
	$table2->Finalise();

	echo sprintf('<p style="text-decoration:underline;">Credit Account Individuals: %s</p>', $table2->TotalRows);
	echo '<p>';
	echo sprintf('Total Orders: %s<br />', $creditedIndividualTotals['orders']);
	echo sprintf('Total Turnover: %s<br />', $creditedIndividualTotals['total']);
	echo sprintf('Total Average Turnover: %s<br />', $creditedIndividualTotals['average']);
	echo '</p>';

	$table2->DisplayTable();
	echo '<br />';
	$table2->DisplayNavigation();
	echo '<br />';
	echo '<br />';

	$organisationTotals = array(
		'orders' => 0,
		'total' => 0,
		'average' => 0
	);

	$organisationSql = new DataQuery(sprintf("SELECT SUM(totals.Order_Count) as Order_Count, SUM(totals.Total_Turnover) as Total_Turnover, SUM(totals.Average_Turnover) as Average_Turnover
		FROM (
			SELECT COUNT(DISTINCT od.Order_ID) AS Order_Count, SUM(od.Total) AS Total_Turnover, FORMAT(AVG(od.Total), 2) AS Average_Turnover
			FROM contact AS c2
			INNER JOIN (
				SELECT Contact_ID, Parent_Contact_ID
				FROM contact
				WHERE Account_Manager_ID=%d
				GROUP BY Parent_Contact_ID
			) AS c ON c.Parent_Contact_ID=c2.Contact_ID
			LEFT JOIN contact_credit_account AS cca ON cca.contactId=c.Contact_ID
			INNER JOIN contact AS c3 ON c3.Parent_Contact_ID=c2.Contact_ID
			INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID
			INNER JOIN customer AS cu ON cu.Contact_ID=c3.Contact_ID
			LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID
			WHERE ISNULL(cca.id)
			GROUP BY c.Contact_ID
		) as totals", mysql_real_escape_string($user->ID)));
	while($organisationSql->Row){
		$organisationTotals['orders'] =+ $organisationSql->Row['Order_Count'];
		$organisationTotals['total'] =+ $organisationSql->Row['Total_Turnover'];
		$organisationTotals['average'] =+ $organisationSql->Row['Average_Turnover'];
		$organisationSql->Next();
	}

	$table3 = new DataTable('organisation');
	$table3->SetSQL(sprintf("SELECT c.Contact_ID, o.Org_Name AS Contact_Name, cs.Last_Contacted_On, MAX(od.Created_On) AS Last_Ordered_On, COUNT(DISTINCT od.Order_ID) AS Order_Count, SUM(od.Total) AS Total_Turnover, FORMAT(AVG(od.Total), 2) AS Average_Turnover
FROM contact AS c2
INNER JOIN (
	SELECT Contact_ID, Parent_Contact_ID
	FROM contact
	WHERE Account_Manager_ID=%d
	GROUP BY Parent_Contact_ID
) AS c ON c.Parent_Contact_ID=c2.Contact_ID
LEFT JOIN contact_credit_account AS cca ON cca.contactId=c.Contact_ID
INNER JOIN contact AS c3 ON c3.Parent_Contact_ID=c2.Contact_ID
INNER JOIN organisation AS o ON o.Org_ID=c2.Org_ID
INNER JOIN customer AS cu ON cu.Contact_ID=c3.Contact_ID
LEFT JOIN (
	SELECT Contact_ID, MAX(Completed_On) AS Last_Contacted_On
	FROM contact_schedule
	WHERE Is_Complete='Y'
	GROUP BY Contact_ID
) AS cs ON c.Contact_ID=cs.Contact_ID
LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID
WHERE ISNULL(cca.id)
GROUP BY c.Contact_ID", mysql_real_escape_string($user->ID)));
	$table3->AddField('Contact ID', 'Contact_ID', 'left');
	$table3->AddField('Organisation', 'Contact_Name', 'left');
	$table3->AddField('Last Ordered', 'Last_Ordered_On', 'left');
	$table3->AddField('Last Contacted', 'Last_Contacted_On', 'left');
	$table3->AddField('Orders', 'Order_Count', 'right');
	$table3->AddField('Total Turnover', 'Total_Turnover', 'right');
	$table3->AddField('Average Turnover', 'Average_Turnover', 'right');
	$table3->AddLink("contact_profile.php?cid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
	$table3->SetMaxRows(15);
	$table3->SetOrderBy("Contact_Name");
	$table3->Order = "ASC";
	$table3->Finalise();

	echo sprintf('<p style="text-decoration:underline;">Non-Credit Account Organisations: %s</p>', $table3->TotalRows);
	echo '<p>';
	echo sprintf('Total Orders: %s<br />', $organisationTotals['orders']);
	echo sprintf('Total Turnover: %s<br />', $organisationTotals['total']);
	echo sprintf('Total Average Turnover: %s<br />', $organisationTotals['average']);
	echo '</p>';

	$table3->DisplayTable();
	echo '<br />';
	$table3->DisplayNavigation();
	echo '<br />';
	echo '<br />';

	$individualTotals = array(
		'orders' => 0,
		'total' => 0,
		'average' => 0
	);

	$individualSql = new DataQuery(sprintf("SELECT SUM(totals.Order_Count) as Order_Count, SUM(totals.Total_Turnover) as Total_Turnover, SUM(totals.Average_Turnover) as Average_Turnover
FROM (
	SELECT COUNT(DISTINCT od.Order_ID) AS Order_Count, SUM(od.Total) AS Total_Turnover, FORMAT(AVG(od.Total), 2) AS Average_Turnover
	FROM contact AS c
	LEFT JOIN contact_credit_account AS cca ON cca.contactId=c.Contact_ID
	INNER JOIN person AS p ON p.Person_ID=c.Person_ID
	INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID
	LEFT JOIN (
		SELECT Contact_ID, MAX(Completed_On) AS Last_Contacted_On
		FROM contact_schedule
		WHERE Is_Complete='Y'
		GROUP BY Contact_ID
	) AS cs ON c.Contact_ID=cs.Contact_ID
	LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID
	WHERE c.Account_Manager_ID=%d AND c.Parent_Contact_ID=0 AND ISNULL(cca.id)
	GROUP BY c.Contact_ID
) AS totals", mysql_real_escape_string($user->ID)));
	while($individualSql->Row){
		$individualTotals['orders'] =+ $individualSql->Row['Order_Count'];
		$individualTotals['total'] =+ $individualSql->Row['Total_Turnover'];
		$individualTotals['average'] =+ $individualSql->Row['Average_Turnover'];
		$individualSql->Next();
	}

	$table4 = new DataTable('individual');
	$table4->SetSQL(sprintf("SELECT c.Contact_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name, cs.Last_Contacted_On, MAX(od.Created_On) AS Last_Ordered_On, COUNT(DISTINCT od.Order_ID) AS Order_Count, SUM(od.Total) AS Total_Turnover, FORMAT(AVG(od.Total), 2) AS Average_Turnover
FROM contact AS c
LEFT JOIN contact_credit_account AS cca ON cca.contactId=c.Contact_ID
INNER JOIN person AS p ON p.Person_ID=c.Person_ID
INNER JOIN customer AS cu ON cu.Contact_ID=c.Contact_ID
LEFT JOIN (
	SELECT Contact_ID, MAX(Completed_On) AS Last_Contacted_On
	FROM contact_schedule
	WHERE Is_Complete='Y'
	GROUP BY Contact_ID
) AS cs ON c.Contact_ID=cs.Contact_ID
LEFT JOIN orders AS od ON cu.Customer_ID=od.Customer_ID
WHERE c.Account_Manager_ID=%d AND c.Parent_Contact_ID=0 AND ISNULL(cca.id)
GROUP BY c.Contact_ID", mysql_real_escape_string($user->ID)));
	$table4->AddField('Contact ID', 'Contact_ID', 'left');
	$table4->AddField('Individual', 'Contact_Name', 'left');
	$table4->AddField('Last Ordered', 'Last_Ordered_On', 'left');
	$table4->AddField('Last Contacted', 'Last_Contacted_On', 'left');
	$table4->AddField('Orders', 'Order_Count', 'right');
	$table4->AddField('Total Turnover', 'Total_Turnover', 'right');
	$table4->AddField('Average Turnover', 'Average_Turnover', 'right');
	$table4->AddLink("contact_profile.php?cid=%s", "<img src=\"images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Contact_ID");
	$table4->SetMaxRows(15);
	$table4->SetOrderBy("Contact_Name");
	$table4->Order = "ASC";
	$table4->Finalise();

	echo sprintf('<p style="text-decoration:underline;">Non-Credit Account Individuals: %s</p>', $table4->TotalRows);
	echo '<p>';
	echo sprintf('Total Orders: %s<br />', $individualTotals['orders']);
	echo sprintf('Total Turnover: %s<br />', $individualTotals['total']);
	echo sprintf('Total Average Turnover: %s<br />', $individualTotals['average']);
	echo '</p>';

	$table4->DisplayTable();
	echo '<br />';
	$table4->DisplayNavigation();
	echo '<br />';

	$page->Display('footer');
}

require_once('lib/common/app_footer.php');
?>