<?php
	require_once('lib/common/app_header.php');

	if($action == "add"){
		$session->Secure(3);
		add();
		exit;
	} elseif($action == "update"){
		$session->Secure(3);
		update();
		exit;
	} elseif($action == "remove"){
		$session->Secure(3);
		remove();
		exit;
	}else {
		$session->Secure(2);
		view();
		exit;
	}

	function add(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnReason.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

		$reason = new ReturnReason;

		$form = new Form("return_reasons.php");
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('title', 'Reason Title', 'text', '', 'paragraph', 1, 255, true);
		$form->AddField('description', 'Reason Description', 'textarea', '', 'paragraph', 1, 512, true, 'style="width:90%;height:50px;"');


		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			$form->Validate();
			// Hurrah! Create a new entry.
			$reason->Title = $form->GetValue('title');
			$reason->Description = $form->GetValue('description');

			if($form->Valid){
				$reason->Add();
				redirect(sprintf("Location: return_reasons.php?id=%d", $reason->ID));
				exit;
			}
		}

		$page = new Page('Add Return Reason','You can add or edit th list of reasons presented to a customer when he is returning an order.');
		$page->Display('header');

		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}

		$window = new StandardWindow('Add');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'return_reasons.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();


		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}

	function update(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnReason.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

		$reason = new ReturnReason($_REQUEST['id']);

		$form = new Form("return_reasons.php");
		$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('id', 'Return Reason ID', 'hidden', $reason->ID, 'numeric_unsigned', 1, 3, true);
		$form->AddField('title', 'Reason Title', 'text', $reason->Title, 'paragraph', 1, 45, true);
		$form->AddField('description', 'Reason Description', 'textarea', $reason->Description, 'paragraph', 1, 255, true, 'style="width:90%;height:50px;"');

		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			$form->Validate();
			// Hurrah! Create a new entry.
			$reason->Title = $form->GetValue('title');
			$reason->Description = $form->GetValue('description');

			if($form->Valid){
				$reason->Update();
				redirect("Location: return_reasons.php");
				exit;
			}
		}

		$page = new Page('Edit Return Reasons','This reason will be presented as part of a list of reasons for returning a product.');
		$page->Display('header');

		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}

		$window = new StandardWindow('Update');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('id');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'return_reasons.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();


		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}

	function remove(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnReason.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		$reason = new ReturnReason;
		$reason->Delete($_REQUEST['id']);
		redirect("Location: return_reasons.php");
	}

	function view(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

		$page = new Page('Return Reasons','List of reasons that are offered to customers who are returning a product.');
		$page->Display('header');

		echo "<br>";
		echo '<input type="button" name="add" value="add a new reason" class="btn" onclick="window.location.href=\'return_reasons.php?action=add\'">';
		echo "<br><br>";

		$table = new DataTable('reasons');
		$table->SetSQL("select * from return_reason");
		$table->AddField('Name', 'Reason_Title', 'left');
		$table->AddField('Description', 'Reason_Desc', 'left');

		$table->AddLink("return_reasons.php?action=update&id=%s",
						"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Schema\" border=\"0\">",
						"Reason_ID");
		$table->AddLink("javascript:confirmRequest('return_reasons.php?action=remove&id=%s','Are you sure you want to remove this item?');",
						"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
						"Reason_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Reason_ID");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		echo "<br>";
		echo '<input type="button" name="add" value="add a new reason" class="btn" onclick="window.location.href=\'return_reasons.php?action=add\'">';
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>
