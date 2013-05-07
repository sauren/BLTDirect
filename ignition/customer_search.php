<?php
/*
	contact_search.php
	Version 1.0

	Ignition, eBusiness Solution
	http://www.deveus.com

	Copyright (c) Deveus Software, 2004
	All Rights Reserved.

	Notes:
*/
	require_once('lib/common/app_header.php');

	$session->secure(2);

	view();
	exit;

	function view(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

		$page = new Page('Customer Address Book Search','');
		$page->SetFocus('string');
		$form = new Form($_SERVER['PHP_SELF'], 'get');
		$form->AddField('search', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('string', 'Search for...', 'text', '', 'paragraph', 1, 255);

		$window = new StandardWindow("Search for a Customer.");
		$webForm = new StandardForm;
		$page->Display('header');

		$sql = "";
		if(isset($_REQUEST['search']) && strtolower($_REQUEST['search']) == "true"){
			if($form->Validate()){
				$string = addslashes(html_entity_decode($form->GetValue('string')));
				$sql = "select c.Contact_ID, c.Is_Customer, c.Is_Supplier, p.Name_First, p.Name_Initial, p.Name_Last, o.Org_Name
					from contact as c
					left join person as p
					on c.Person_ID=p.Person_ID
					left join organisation as o
					on c.Org_ID=o.Org_ID
					where";
                $sql .= " Is_Customer = 'Y' AND (";
                $pieces = explode(" ", $string);
                for($i=0; $i < count($pieces); $i++){
                    if($i > 0) $sql .= " or";
                    $sql .= " o.Org_Name like '%{$pieces[$i]}%' or
                        p.Name_First like '%{$pieces[$i]}%' or
                        p.Name_Last like '%{$pieces[$i]}%' or
                        c.Contact_ID LIKE '" . $pieces[$i] . "')";
                }
			} else {
				echo $form->GetError();
				echo "<br>";
			}
		}

		echo $form->Open();
		echo $form->GetHTML('search');
		echo $window->Open();
		echo $window->AddHeader('Search for contacts by first name, last name or organisation name.');
		echo $window->OpenContent();
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('string'), $form->GetHTML('string') . '<input type="submit" name="searchButton" value="search" class="btn" />');
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		echo "<br>";


		if(!empty($sql)){
			$table = new DataTable("cl");
			$table->SetSQL($sql);
			$table->AddField('ID#', 'Contact_ID', 'left');
			$table->AddField('Organisation', 'Org_Name', 'left');
			$table->AddField('First Name', 'Name_First', 'left');
			$table->AddField('Last Name', 'Name_Last', 'left');
			$table->AddLink("contact_profile.php?cid=%s",
							"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Contact\" border=\"0\">",
							"Contact_ID");
			$table->AddLink("javascript:confirmRequest('contact_profile.php?action=remove&confirm=true&cid=%s','Are you sure you want to remove this contact? IMPORTANT: If a contact is being used by other areas of Ignition the contact may not be removed, but will instead be deactivated.');",
						"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
						"Contact_ID");
			$table->Order = "desc";
			$table->SetMaxRows(25);
			$table->SetOrderBy("Contact_ID");
			$table->Finalise();
			$table->DisplayTable();
			echo "<br>";
			$table->DisplayNavigation();
			echo "<br>";
		}
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>
