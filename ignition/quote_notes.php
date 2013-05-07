<?php
	require_once('lib/common/app_header.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/QuoteNote.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$session->secure(2);

	$quote = new Quote($_REQUEST['qid']);
	$quote->Customer->Get();
	$quote->Customer->Contact->Get();

	// Add Note
	if($action =='add'){
		$session->Secure(3);
		view();
		exit();
	} else {
		view();
		exit();
	}

	function view(){
		// Set Variables
		global $quote;
		$sql = sprintf("select * from quote_note where Quote_ID=%d order by Created_On desc", mysql_real_escape_string($quote->ID));

		// Setup the adding form
		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('qid', 'Quote ID', 'hidden', $quote->ID, 'numeric_unsigned', 1, 11);

		if(isset($_REQUEST['message'])){
			$_REQUEST['message'] = str_replace('\n', "\n", $_REQUEST['message']);
		}

		$form->AddField('message', 'Note', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:100%; height:200px"');
		$form->AddField('isPublic', 'Allow the Customer to see this Quote Note in their Online Account', 'checkbox', 'N', 'boolean', NULL, NULL, false);
		$form->AddField('sendEmail', 'Send an Email to the Customer containing this Quote Note', 'checkbox', 'N', 'boolean', NULL, NULL, false);
		$form->AddField('alertMe', 'Alert All users with this message the next time this quote is opened?', 'checkbox', 'N', 'boolean', NULL, NULL, false);

		// Check if the form was submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$note = new QuoteNote;
				$note->Message = $form->GetValue('message');
				$note->QuoteID = $form->GetValue('qid');
				$note->IsPublic = $form->GetValue('isPublic');
				$note->IsAlert = $form->GetValue('alertMe');
				$note->Add();

				// send to customer?
				if($form->GetValue('sendEmail') == 'Y'){
					$note->SendToCustomer($quote->Customer->Contact->Person->GetFullName(), $quote->Customer->GetEmail());
				}

				redirect("Location: quote_notes.php?qid=" . $quote->ID);
				exit;
			}
		}

		$page = new Page(sprintf('<a href="quote_details.php?quoteid=%s">Quote</a> &gt; Quote Notes', $quote->ID),'Quote Notes allow you to keep track of changes to quotes and important messages relating to quotes.');
		$page->Display('header');

		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br />";
		}

		echo '<table class="catProducts" cellspacing="0">';
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){
			while($data->Row){
				if(empty($data->Row['Created_By'])){
					$author = $quote->Customer->Contact->Person->GetFullName();
					$visible = 'Y';
				} else {
					$user = new User($data->Row['Created_By']);
					$author = $user->Person->GetFullName();
					$visible = $data->Row['Is_Public'];
				}
				$date = cDatetime($data->Row['Created_On']);
				if(!empty($data->Row['Type_Name'])) echo sprintf('<tr><th colspan="3">Subject: %s</th>', $data->Row['Type_Name']);
				echo sprintf('<tr><th>Date: %s</th><th>Author: %s</th><th>Visible to Customer: %s</th></tr>', $date, $author, $visible);
				echo sprintf('<tr><td colspan="3">%s</td></tr>', $data->Row['Quote_Note']);
				$data->Next();
			}
		} else {
			echo '<tr><td align="center">No Quote Notes have been entered</td></tr>';
		}
		$data->Disconnect();
		echo '</table><br />';

		// now do the addition form
		$window = new StandardWindow('Add an Quote Note');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('qid');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('message'), $form->GetHTML('message') . $form->GetIcon('message'));
		echo $webForm->AddRow($form->GetHTML('isPublic'), $form->GetLabel('isPublic') . $form->GetIcon('isPublic'));
		echo $webForm->AddRow($form->GetHTML('sendEmail'), $form->GetLabel('sendEmail') . $form->GetIcon('sendEmail'));
		echo $webForm->AddRow($form->GetHTML('alertMe'), $form->GetLabel('alertMe') . $form->GetIcon('alertMe'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();

		$page->Display('footer');
		require_once('lib/common/app_footer.php');
	}
?>
