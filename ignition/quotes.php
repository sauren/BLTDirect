<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('status','Filter by status','select','Pending','alpha_numeric',0,40,false);
	$form->AddOption('status','','-- All --');
	$form->AddOption('status','Pending', 'Pending');
	$form->AddOption('status','Ordered', 'Ordered');
	$form->AddOption('status','Cancelled', 'Cancelled');
	$form->AddField('followedup','Followed Up','select','N','alpha_numeric',0,40,false);
	$form->AddOption('followedup','','-- All --');
	$form->AddOption('followedup','N','No');
	$form->AddOption('followedup','Y','Yes');
	$form->AddField('owner','Owner','select','','numeric_unsigned', 1, 11, false);
	$form->AddOption('owner','','-- All --');

	$data = new DataQuery(sprintf("SELECT u.User_ID, p.Name_First, p.Name_Last FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY p.Name_First, p.Name_Last ASC"));
	while($data->Row) {
		$form->AddOption('owner', $data->Row['User_ID'], trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])));

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true')) {
		if($form->Validate()) {
			redirect(sprintf("Location: %s?status=%s&followedup=%s&owner=%s", $_SERVER['PHP_SELF'], $form->GetValue('status'), $form->GetValue('followedup'), $form->GetValue('owner')));
		}
	}

    $page = new Page('Quotes', 'Below is a list of quotes available for filtering.');
	$page->Display('header');

	$window = new StandardWindow('Filter quotes');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('status'),$form->GetHTML('status'));
	echo $webForm->AddRow($form->GetLabel('followedup'),$form->GetHTML('followedup'));
	echo $webForm->AddRow($form->GetLabel('owner'),$form->GetHTML('owner'));
	echo $webForm->AddRow('', '<input type="submit" name="search" value="Search" class="btn">');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	echo '<br />';

	$sqlFilter = '';

	if(strlen($form->GetValue('status')) > 0) {
		$sqlFilter .= sprintf("q.Status LIKE '%s' ", $form->GetValue('status'));
	}

	if(strlen($form->GetValue('followedup')) > 0) {
		if(strlen($sqlFilter) > 0) {
			$sqlFilter .= 'AND ';
		}

		$sqlFilter .= sprintf("q.Followed_Up='%s' ", $form->GetValue('followedup'));
	}

	if(strlen($form->GetValue('owner')) > 0) {
		if(strlen($sqlFilter) > 0) {
			$sqlFilter .= 'AND ';
		}

		$sqlFilter .= sprintf("q.Created_By=%d ", $form->GetValue('owner'));
	}

	if(strlen(trim($sqlFilter)) > 0) {
		$sqlFilter = sprintf('WHERE %s', $sqlFilter);
	}

	$table = new DataTable("quotes");
	$table->SetSQL(sprintf("SELECT COUNT(e.Enquiry_ID) AS Enquiry_Count, q.*, CONCAT_WS(' ', q.Billing_First_Name, q.Billing_Last_Name) AS Billing_Name, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Owner FROM quote AS q LEFT JOIN users AS u ON u.User_ID=q.Created_By LEFT JOIN person AS p ON p.Person_ID=u.Person_ID LEFT JOIN enquiry AS e ON e.Customer_ID=q.Customer_ID AND ((e.Status LIKE 'Closed' AND q.Created_On BETWEEN e.Created_On AND e.Closed_On) OR (e.Status NOT LIKE 'Closed' AND q.Created_On BETWEEN e.Created_On AND NOW())) %s GROUP BY q.Quote_ID", $sqlFilter));
	$table->AddField('Quote Date', 'Quoted_On', 'left');
	$table->AddField('Name', 'Billing_Name', 'left');
	$table->AddField('Prefix', 'Quote_Prefix', 'center');
	$table->AddField('Quote ID', 'Quote_ID', 'center');
	$table->AddField('Quote Total', 'Total', 'right');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Followed Up', 'Followed_Up', 'center');
	$table->AddField('Owner Name', 'Owner', 'left');
	$table->AddField('Review On', 'Review_On', 'left');
	$table->AddField('Enquiries', 'Enquiry_Count', 'center');
	$table->AddLink("quote_details.php?quoteid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Quote Details\" border=\"0\">", "Quote_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Quoted_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add(){
}

function update(){
}

function remove(){
}
?>