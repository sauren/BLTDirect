<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$priceEnquiry = new PriceEnquiry($_REQUEST['id']);
		$priceEnquiry->Delete();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Pending Price Enquiries', 'Below is a list of all pending price enquiries.');
	$page->Display('header');

	$table = new DataTable("enquiries");
	$table->SetSQL(sprintf("SELECT Price_Enquiry_ID, Created_On FROM price_enquiry WHERE Status LIKE 'Pending'"));
	$table->AddField('Price Enquiry ID', 'Price_Enquiry_ID', 'left');
	$table->AddField('Price Enquiry Date', 'Created_On', 'left');
	$table->AddLink("price_enquiry_split.php?id=%s", "<img src=\"./images/icon_pages_1.gif\" alt=\"Split Products\" border=\"0\">", "Price_Enquiry_ID");
	$table->AddLink("price_enquiry_matrix.php?id=%s", "<img src=\"./images/icon_view_1.gif\" alt=\"Price Matrix\" border=\"0\">", "Price_Enquiry_ID");
	$table->AddLink("price_enquiry_details.php?id=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open\" border=\"0\">", "Price_Enquiry_ID");
	$table->AddLink("javascript:confirmRequest('price_enquiries_pending.php?action=remove&id=%s','Are you sure you want to remove this price enquiry?');", "<img src=\"./images/button-cross.gif\" alt=\"Remove\" border=\"0\">", "Price_Enquiry_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}