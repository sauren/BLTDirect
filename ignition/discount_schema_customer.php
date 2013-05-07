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
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

		// Set Customer
		$customer = new Customer($_REQUEST['customer']);
		$customer->Contact->Get();
		$tempHeader = "";

		// Set Form
		$form = new Form("discount_schema_customer.php");
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('customer', 'Customer ID', 'hidden', $customer->ID, 'numeric_unsigned', 1, 11, true);
		$form->AddField('schema', 'Discount Schema', 'select', '', 'numeric_unsigned', 1, 11, true);
		$form->AddOption('schema', 0, 'Select Discount Schema');
		// Get Discount Schema from Database and put them in the form
		$getSchema = new DataQuery("select * from discount_schema order by Discount_Title asc");
		while($getSchema->Row){
			$form->AddOption('schema', $getSchema->Row['Discount_Schema_ID'], $getSchema->Row['Discount_Title']);
			$getSchema->Next();
		}
		$getSchema->Disconnect();

		// Set Page Header Information
		if($customer->Contact->HasParent){
			$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s</a> &gt; ", $customer->Contact->Parent->ID, $customer->Contact->Parent->Organisation->Name);
		}
		$tempHeader .= sprintf("<a href=\"contact_profile.php?cid=%d\">%s %s</a> &gt;", $customer->Contact->ID, $customer->Contact->Person->Name, $customer->Contact->Person->LastName);

		$page = new Page(sprintf('%s Discount Schemas for %s', $tempHeader, $customer->Contact->Person->GetFullName()),
						sprintf('%s may have multiple discount schemas applied to their profile. If product discounts overlap the highest discount will be applied.', $customer->Contact->Person->GetFullName()));
		$page->Display('header');

		// Add Form
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('customer');
		echo "Add Discount to Customer's Profile ";
		echo $form->GetHTML('schema');
		echo sprintf('<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex());
		echo $form->Close();
		echo "<br /><br />";

		// Display Table
		$sql = sprintf("SELECT dc.Discount_Customer_ID, ds.Discount_Schema_ID, ds.Discount_Ref, ds.Is_On_Markup, ds.Discount_Title, ds.Discount_Amount, ds.Orders_Over, ds.Is_Active from discount_customer as dc
inner join discount_schema as ds
on ds.Discount_Schema_ID=dc.Discount_Schema_ID
where Customer_ID=%d", $customer->ID);
		$table = new DataTable("orders");
		$table->SetSQL($sql);
		$table->AddField('Reference', 'Discount_Ref', 'left');
		$table->AddField('Discount Title', 'Discount_Title', 'left');
		$table->AddField('Discount %', 'Discount_Amount', 'right');
		$table->AddField('Orders Over', 'Orders_Over', 'right');
		$table->AddField('Active', 'Is_Active', 'center');
		$table->AddField('Discount Markup', 'Is_On_Markup', 'center');
		$table->AddLink("discount_schema_settings.php?schema=%s",
								"<img src=\"./images/folderopen.gif\" alt=\"Open Schema\" border=\"0\">",
								"Discount_Schema_ID");
		$table->AddLink("javascript:confirmRequest('discount_schema_customer.php?action=remove&confirm=true&dcu=%s','Are you sure you want to remove this schema from this customer?');",
							"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
							"Discount_Customer_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Discount_Ref");
		$table->Order = "ASC";
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}

	function add(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountCustomer.php');

		$discount = new DiscountCustomer;
		$discount->CustomerID = $_REQUEST['customer'];
		$discount->DiscountID = $_REQUEST['schema'];
		if(!empty($discount->DiscountID) && !$discount->Exists()) $discount->Add();

		redirect(sprintf("Location: discount_schema_customer.php?customer=%d", $discount->CustomerID));
		exit;
	}

	function update(){
	}

	function remove(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DiscountCustomer.php');

		$discount = new DiscountCustomer;
		$discount->CustomerID = $_REQUEST['customer'];
		$discount->ID = $_REQUEST['dcu'];
		$discount->Delete();

		redirect(sprintf("Location: discount_schema_customer.php?customer=%d", $discount->CustomerID));
		exit;
	}
?>