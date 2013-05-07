<?php
	require_once('lib/common/app_header.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnNote.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	
	$session->secure(2);
	
	$return = new ProductReturn($_REQUEST['id']);
	$return->Customer->Get();
	$return->Customer->Contact->Get();
	
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
		global $return;
        $sql = sprintf("SELECT note.*, ot.Type_Name
                        FROM return_note note
                        LEFT JOIN return_note_type ot
                        ON note.Return_Note_Type_ID=ot.Return_Note_Type_ID
                        WHERE note.Return_ID=%d
                        ORDER BY note.Created_On DESC", $return->ID);
		// Setup the adding form
		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('id', 'Return ID', 'hidden', mysql_real_escape_string($return->ID), 'numeric_unsigned', 1, 11);
		
		$subject = '';
		if(isset($_REQUEST['type'])){
            $data = new DataQuery("SELECT * FROM return_note_type
                                  WHERE Type_Name like '%".mysql_real_escape_string($_REQUEST['type'])."%'");
			$subject = $data->Row['Return_Note_Type_ID']; 
		}
		
		if(isset($_REQUEST['message'])){
			$_REQUEST['message'] = str_replace('\n', "\n", $_REQUEST['message']);
		}
		
		$form->AddField('subject', 'Subject', 'select', $subject, 'numeric_unsigned', 1, 11);
		$form->AddOption('subject', '', 'Select a Subject');
		// get options
		$data = new DataQuery('SELECT * FROM return_note_type ORDER BY Type_Name ASC');
		while($data->Row){
			$form->AddOption('subject', $data->Row['Return_Note_Type_ID'], $data->Row['Type_Name']);
			$data->Next();
		}
		$data->Disconnect();
		unset($data);
		
		$form->AddField('message', 'Note', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:100%; height:200px"');
		$form->AddField('isPublic', 'Allow the Customer to see this Return Note in their Online Account', 'checkbox', 'N', 'boolean', NULL, NULL, false);
		$form->AddField('sendEmail', 'Send an Email to the Customer containing this Return Note', 'checkbox', 'N', 'boolean', NULL, NULL, false);
		$form->AddField('alertMe', 'Alert All users with this message the next time this return is opened?', 'checkbox', 'N', 'boolean', NULL, NULL, false);
		
		// Check if the form was submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$note = new ReturnNote;
				$note->Message = $form->GetValue('message');
				$note->TypeID = $form->GetValue('subject');
				$note->ReturnID = $form->GetValue('id');
				$note->IsPublic = $form->GetValue('isPublic');
				$note->IsAlert = $form->GetValue('alertMe');
				$note->Add();
				
				// send to customer?
				if($form->GetValue('sendEmail') == 'Y'){
					$note->SendToCustomer($return->Customer->Contact->Person->GetFullName(), $return->Customer->GetEmail());
				}
				
				redirect("Location: return_notes.php?id=" . $return->ID);
				exit;
			}
		}
		
		$page = new Page(sprintf('<a href="return_details.php?id=%s">Return</a> &gt; Return Notes', $return->ID),'Return Notes allow you to keep track of changes to returns and important messages relating to returns.');
		$page->Display('header');
		
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		
		echo '<table class="catProducts" cellspacing="0">';
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){
			while($data->Row){
				if(empty($data->Row['Created_By'])){
					$author = $return->Customer->Contact->Person->GetFullName();
					$visible = 'Y';
				} else {
					$user = new User($data->Row['Created_By']);
					$author = $user->Person->GetFullName();
					$visible = $data->Row['Is_Public'];
				}
				$date = cDatetime($data->Row['Created_On']);
				if(!empty($data->Row['Type_Name'])) echo sprintf('<tr><th colspan="3">Subject: %s</th>', $data->Row['Type_Name']);
				echo sprintf('<tr><th>Date: %s</th><th>Author: %s</th><th>Visible to Customer: %s</th></tr>', $date, $author, $visible);
				echo sprintf('<tr><td colspan="3">%s</td></tr>', $data->Row['Return_Note']);
				$data->Next();
			}
		} else {
			echo '<tr><td align="center">No Return Notes have been entered</td></tr>';
		}
		$data->Disconnect();
		echo '</table><br />';
		
		// now do the addition form
		$window = new StandardWindow('Add an Return Note');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('id');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('subject'), $form->GetHTML('subject') . $form->GetIcon('subject'));
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
