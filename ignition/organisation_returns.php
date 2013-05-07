<?php
	require_once('lib/common/app_header.php');

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
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

		$contact = new Contact($_REQUEST['ocid']);

		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Return History for %s contacts', $contact->ID, $contact->Organisation->Name, $contact->Organisation->Name), sprintf('Below is the return history for all contacts of %s.', $contact->Organisation->Name));
		$page->Display('header');

		$sql = sprintf("SELECT r.*, p2.Name_First, p2.Name_Last from `return` AS r INNER JOIN customer AS c ON c.Customer_ID=r.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE n.Parent_Contact_ID=%d", $contact->ID);
		$table = new DataTable("quotes");
		$table->SetSQL($sql);
		$table->AddField('Return', 'Return_ID', 'right');
		$table->AddField('First Name', 'Name_First', 'left');
		$table->AddField('Last Name', 'Name_Last', 'left');
		$table->AddField('Request Date', 'Requested_On', 'left');
		$table->AddField('Status', 'Status', 'right');
		$table->AddLink("return_details.php?id=%s",
								"<img src=\"./images/folderopen.gif\" alt=\"Open Return Details\" border=\"0\">",
								"Return_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Requested_On");
		$table->Order = "DESC";
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";

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
