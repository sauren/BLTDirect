<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$page = new Page('Pro Forma\'s',
'Below is a list of all new pro forma\'s, which require action.');
$page->Display('header');
//Add filter dialogue
$filter = new Form('proformas.php','GET');
$filter->AddField('date','Filter by date:','select','N','alpha_numeric',0,40,false);
$filter->AddOption('date','ALL','All');
$filter->AddOption('date','TODAY','Today\'s');
$filter->AddOption('date','WEEK','This Week\'s');
$filter->AddOption('date','MONTH','This Month\'s');
$filter->AddOption('date','YEAR','This Year\'s');
$filter->AddField('status','Filter by status:','select','PENDING','alpha_numeric',0,40,false);
$filter->AddOption('status','ALL','All');
$filter->AddOption('status','PENDING','Pending Action');
$filter->AddOption('status','CANCELLED','Cancelled');
$window = new StandardWindow('Filter pro forma\'s');
$webForm = new StandardForm();
echo $filter->Open();
echo $window->Open();
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($filter->GetLabel('date'),$filter->GetHTML('date'));
echo $webForm->AddRow($filter->GetLabel('status'),$filter->GetHTML('status').'<input type="submit" name="search" value="Search" class="btn">');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $filter->Close();
echo "<br>";
//Populate table based on filter results
$sqlFilterStatus = "";
$sqlFilterDate = "";
$sqlFilter = "";
switch($filter->GetValue('status')){
	case 'ALL':
		$sqlFilterStatus = "Status LIKE '%' ";
		break;
	case 'PENDING':
		$sqlFilterStatus = "Status LIKE 'pending' ";
		break;
	case 'ORDERED':
		$sqlFilterStatus = "Status LIKE 'ordered' ";
		break;
	case 'CANCELLED':
		$sqlFilterStatus = "Status LIKE 'cancelled' ";
		break;
	default:
		$sqlFilterStatus = "Status LIKE 'pending' ";
		break;
}
switch($filter->GetValue('date')){
	case 'ALL':
		$sqlFilterDate = "Created_On LIKE '%' ";
		break;
	case 'TODAY':
		$sqlFilterDate = sprintf("Created_On LIKE '%s%%' ", date("Y-m-d"));
		break;
	case 'WEEK':
		//TODO: Select date range of current week
		$weekStart = time() - ((date('w')-1) *24*60*60);
		$weekStart = date('Y-m-d 00:00:00', $weekStart);
		$weekEnd = time() + ((7 - date('w')) * 24*60*60);
		$weekEnd = date('Y-m-d 23:59:59', $weekEnd);
		$sqlFilterDate = sprintf("Created_On > '%s' AND Created_On < '%s' ",
		$weekStart, $weekEnd);
		break;
	case 'MONTH':
		$sqlFilterDate = sprintf("Created_On > '%s' AND Created_On < '%s' ",
		date("Y-m-01 00:00:00"), date("Y-m-t 23:59:59"));
		break;
	case 'YEAR':
		$sqlFilterDate = sprintf("Created_On > '%s' AND Created_On < '%s' ",
		date("Y-01-01 00:00:00"),
		date("Y-12-31 23:59:59"));
	default:
		$sqlFilterDate = "Created_On LIKE '%' ";
}
$sqlFilter = "WHERE $sqlFilterStatus AND $sqlFilterDate";
$sql = "SELECT * from proforma ".$sqlFilter;
$table = new DataTable("proformas");
$table->SetSQL($sql);
$table->AddField('Pro Forma Date', 'Formed_On', 'left');
$table->AddField('Name', 'Billing_First_Name', 'left');
$table->AddField('Surname', 'Billing_Last_Name', 'left');
$table->AddField('Pro Forma Prefix', 'ProForma_Prefix', 'left');
$table->AddField('Pro Forma Number', 'ProForma_ID', 'right');
$table->AddField('Pro Forma Total', 'Total', 'right');
$table->AddField('Status', 'Status', 'right');
$table->AddLink("proforma_details.php?proformaid=%s",
"<img src=\"./images/folderopen.gif\" alt=\"Open Pro Forma Details\" border=\"0\">",
"ProForma_ID");
$table->SetMaxRows(25);
$table->SetOrderBy("Formed_On");
$table->Order = "DESC";
$table->Finalise();
$table->DisplayTable();
echo "<br>";
$table->DisplayNavigation();
echo "<br>";

$page->Display('footer');
require_once('lib/common/app_footer.php');