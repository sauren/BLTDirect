<?php
/*
	product_search.php
	Version 1.0

	Ignition, eBusiness Solution
	http://www.deveus.com

	Copyright (c) Deveus Software, 2004
	All Rights Reserved.

	Notes:
	(*) TODO: substantial changes required to improve search results and implement multi-lingual results
*/
	require_once('lib/common/app_header.php');


	if($action == 'remove'){
		$session->Secure(3);
		remove();
		exit;
	} else {
		$session->secure(2);
		view();
		exit;
	}

	function view(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

		$serve = (isset($_REQUEST['serve']))?$_REQUEST['serve']:'view';

		$page = new Page('Return Search','When searching by order number please do not include the prefix character (i.e. W in web order W101)');
		$form = new Form($_SERVER['PHP_SELF'], 'get');
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('serve', 'Serve', 'hidden', $serve, 'alpha', 1, 6);
		$form->AddField('string', 'Search for...', 'text', isset($_REQUEST['string']) ? $_REQUEST['string'] : '', 'paragraph', 1, 255);
		$form->AddField('type', 'Search Type..', 'select', (isset($_REQUEST['type']) ? $_REQUEST['type'] : 'Return_ID'), 'alpha_numeric', 1, 60);
		$form->AddOption('type', 'Return_ID', 'Return Number');
		$form->AddOption('type', 'Billing_Name', 'Name');
		$form->AddOption('type', 'Shipping_Name', 'Shipping Name');
		$form->AddOption('type', 'Billing_Zip', 'Postcode/Zip');
		$form->AddOption('type', 'Shipping_Zip', 'Shipping Postcode/Zip');

		$window = new StandardWindow("Search for a Return.");
		$webForm = new StandardForm;

		$sql = "";
		if(isset($_REQUEST['string']) && !empty($_REQUEST['string'])){
			if($form->Validate()){
				switch($form->GetValue('type')){
					case 'Return_ID':
						buildByReturnId($form->GetValue('string'));
						break;
					case 'Billing_Name':
						$sql = buildByBillingName($form->GetValue('string'));
						break;
					case 'Billing_Zip':
						$sql = buildByBillingZip($form->GetValue('string'));
						break;
					case 'Shipping_Name':
						$sql = buildByShippingName($form->GetValue('string'));
						break;
					case 'Shipping_Zip':
						$sql = buildByShippingZip($form->GetValue('string'));
						break;
				}
			}
		}

		$page->Display('header');
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br />";
		}
		echo $form->Open();
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('serve');
		echo $window->Open();
		echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
		echo $window->OpenContent();
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('string'), $form->GetHTML('string') . $form->GetHtml('type')  . '<input type="submit" name="search" value="search" class="btn" />');
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		echo "<br>";


		if(!empty($sql)){
			//echo $sql;
			$table = new DataTable("pl");
			$table->SetSQL($sql);
			$table->AddField('Return', 'Return_ID', 'left');
			$table->AddField('Status', 'Status', 'left');
			$table->AddField('Organisation', 'Shipping_Organisation_Name', 'left');
			$table->AddField('Billing Name', 'Billing_First_Name', 'left');
			$table->AddField('Billing Surname', 'Billing_Last_Name', 'left');
			$table->AddField('Billing Zip', 'Shipping_Zip', 'left');
			$table->AddField('Shipping Zip', 'Shipping_Zip', 'left');
			$table->AddField('Reason', 'Reason_Title');

			$table->AddLink("return_details.php?id=%s",
							"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",
							"Return_ID");
			$table->AddLink("javascript:confirmRequest('order_search.php?action=remove&confirm=true&id=%s&string=".$_REQUEST['string']."&type=".$_REQUEST['type']."','Are you sure you want to remove this order?');",
						"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
						"Return_ID");
			$table->Order = "asc";
			$table->SetMaxRows(25);
			$table->SetOrderBy("r.Return_ID");
			$table->Finalise();
			$table->DisplayTable();
			echo "<br>";
			$table->DisplayNavigation();
		}
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}

	// Build SQL by Return ID
	function buildByReturnId($string){
		if(is_numeric($string)){
			$data = new DataQuery(sprintf("select r.*, rr.Reason_Title from `return` AS r INNER JOIN return_reason AS rr ON r.Reason_ID = rr.Reason_ID where r.Return_ID=%d", $string));
			if($data->TotalRows > 0){
				redirect("Location: return_details.php?id=" . $string);
				exit;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	// Buld SQL by Billing Name
	function buildByBillingName($string){
		$string = stripslashes($string);
		parse_search_string($string, $keywords);

		$sql = "SELECT r.*, o.*, rr.Reason_Title
                FROM `return` r
                INNER JOIN order_line ol ON r.Order_Line_ID = ol.Order_Line_ID
                INNER JOIN orders o ON ol.Order_ID = o.Order_ID
                INNER JOIN return_reason AS rr ON r.Reason_ID = rr.Reason_ID
                WHERE";

		// It's a normal search
		for($i=0; $i < count($keywords); $i++){
			switch($keywords[$i]){
				case '(':
				case ')':
				case 'and':
				case 'or':
					$sql .= " " . $keywords[$i] . " ";
					break;
				default:
					$sql .= " (Billing_First_Name like '%" . addslashes($keywords[$i]) . "%'
							or Billing_Last_Name like '%" . addslashes($keywords[$i]) . "%')";
					break;
			}
		}
        $sql .= "GROUP BY o.Order_ID";
		return $sql;
	}

	// Buld SQL by Shipping Name
	function buildByShippingName($string){
		$string = stripslashes($string);
		parse_search_string($string, $keywords);

		$sql = "SELECT o.*, r.*, rr.Reason_Title
                FROM `return` r
                INNER JOIN order_line ol ON r.Order_Line_ID = ol.Order_Line_ID
                INNER JOIN orders o ON ol.Order_ID = o.Order_ID
                INNER JOIN return_reason AS rr ON r.Reason_ID = rr.Reason_ID
                WHERE ";

		// It's a normal search
		for($i=0; $i < count($keywords); $i++){
			switch($keywords[$i]){
				case '(':
				case ')':
				case 'and':
				case 'or':
					$sql .= " {$keywords[$i]} ";
					break;
				default:
					$sql .= "(Shipping_First_Name like '%" . addslashes($keywords[$i]) . "%'
							or Shipping_Last_Name like '%" . addslashes($keywords[$i]) . "%')";
					break;
			}
		}
        $sql .= "GROUP BY o.Order_ID";
		return $sql;
	}

	function buildByShippingZip($string){
		$sql = "SELECT r.*, o.*, rr.Reason_Title
                FROM `return` r
                INNER JOIN order_line ol ON r.Order_Line_ID = ol.Order_Line_ID
                INNER JOIN orders o ON ol.Order_ID = o.Order_ID
                INNER JOIN return_reason AS rr ON r.Reason_ID = rr.Reason_ID
                WHERE o.Shipping_Zip LIKE '%$string%'
                GROUP BY o.Order_ID";
		return $sql;
	}
	function buildByBillingZip($string){
		$sql = "SELECT r.*, o.*, rr.Reason_Title
                FROM `return` r
                INNER JOIN order_line ol ON r.Order_Line_ID = ol.Order_Line_ID
                INNER JOIN orders o ON ol.Order_ID = o.Order_ID
                INNER JOIN return_reason AS rr ON r.Reason_ID = rr.Reason_ID
                WHERE o.Billing_Zip LIKE '%$string%'
                GROUP BY o.Order_ID";
		return $sql;
	}

	function remove() {
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');
		if(isset($_REQUEST['id']) && isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
			$return = new ProductReturn($_REQUEST['id']);
			$return->Delete();
		}
		redirect(sprintf("Location: %s?string=%s&type=%s", $_SERVER['PHP_SELF'], $_REQUEST['string'], $_REQUESt['type']));
	}