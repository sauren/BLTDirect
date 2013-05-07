<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

if($action == "remove"){
	$session->Secure(3);

	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['sid'])){
		$supplier = new Supplier($_REQUEST['sid']);
		$supplier->Delete();
	}

	redirect("Location: supplier_view.php");
}
else{
	$session->Secure(2);
	view();
	exit();
}

function view(){
	$page = new Page("Suppliers","Here you can edit the details of the suppliers used as well as add a new supplier");
	$page->Display('header');

	$table = new DataTable("suppliers");
	$table->SetSQL(sprintf("SELECT s.*, p.Name_First, p.Name_Last, o.Org_Name FROM supplier AS s INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID"));
	$table->AddField('ID','Supplier_ID','left');
	$table->AddField('Reference','Reference','left');
	$table->AddField('First Name','Name_First');
	$table->AddField('Last Name','Name_Last');
	$table->AddField('Organisation','Org_Name');
	$table->AddField('Is Costs Comparable','Is_Comparable', 'center');
	$table->AddField('Is Drop Shipper','Is_Drop_Shipper', 'center');
	$table->AddField('Is Favourite','Is_Favourite', 'center');
	$table->AddField('Is Bidder','Is_Bidder', 'center');
	$table->AddLink('contact_profile.php?cid=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Update the suppliers settings\" border=\"0\">",'Contact_ID');
	$table->AddLink("javascript:confirmRequest('supplier_view.php?action=remove&confirm=true&sid=%s','Are you sure you want to remove this supplier?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove this supplier\" border=\"0\">","Supplier_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Org_Name');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" type="submit" value="add a new supplier" class="btn" onclick="window.location.href=\'supplier_register.php\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}