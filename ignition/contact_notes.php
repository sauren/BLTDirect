<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

if($action == 'remove') {
	$note = new ContactNote();
	$note->Delete($_REQUEST['nid']);

	redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $_REQUEST['cid']));

} elseif($action == 'read') {
	$note = new ContactNote($_REQUEST['nid']);
	$note->IsUnread = 'N';
	$note->Update();

	redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $note->ContactID));

} else {
	$contact = new Contact($_REQUEST['cid']);

	$data = new DataQuery(sprintf("SELECT Customer_ID FROM customer WHERE Contact_ID=%d", mysql_real_escape_string($contact->ID)));
	if($data->TotalRows > 0) {
		$customer = new Customer($data->Row['Customer_ID']);
		$customer->Contact->Get();
	}
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Contact ID', 'hidden', $contact->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('description', 'Description', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:100%; height:200px"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$note = new ContactNote();
			$note->Description = $form->GetValue('description');
			$note->ContactID = $form->GetValue('cid');
			$note->Add();

			redirect(sprintf("Location: contact_notes.php?cid=%d", $contact->ID));
		}
	}

	if($contact->Type == "O"){
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; Contact Notes', $contact->ID, $contact->Organisation->Name), 'Contact notes allow you to keep track of important messages relating to contacts.');
		$page->Display('header');
	} else {
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Contact Notes', $contact->ID, $contact->Person->Name, $contact->Person->LastName), 'Contact notes allow you to keep track of important messages relating to contacts.');
		$page->Display('header');
	}

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	echo '<table class="catProducts" cellspacing="0">';

	$data = new DataQuery(sprintf("SELECT * FROM contact_note WHERE Contact_ID=%d ORDER BY Created_On DESC", mysql_real_escape_string($contact->ID)));
	if($data->TotalRows > 0){
		while($data->Row){
			$author = 'Unknown';

			if(!empty($data->Row['Created_By'])){
				$user = new User($data->Row['Created_By']);
				$author = trim(sprintf("%s %s", $user->Person->Name, $user->Person->LastName));
			}

			echo sprintf('<tr><th width="45%%">Date: %s</th><th width="45%%">Author: %s</th><th width="10%%" style="text-align: right;"><a href="javascript:confirmRequest(\'contact_notes.php?action=remove&cid=%d&nid=%d\', \'Are you sure you wish to remove this note?\');"><img align="absmiddle" src="images/icon_trash_1.gif" alt="Remove" border="0" /></a></th></tr>', cDatetime($data->Row['Created_On']), $author, $contact->ID, $data->Row['Contact_Note_ID']);
			echo sprintf('<tr><td colspan="3">%s', nl2br($data->Row['Description']));

			if($data->Row['Is_Unread'] == 'Y') {
				echo sprintf('<br /><br /><a href="%s?action=read&nid=%d">Mark as read</a>', $_SERVER['PHP_SELF'], $data->Row['Contact_Note_ID']);
			}

			echo sprintf('</td></tr>');

			$data->Next();
		}
	} else {
		echo '<tr><td align="center">No contact notes have been entered</td></tr>';
	}
	$data->Disconnect();

	echo '</table><br />';

	$window = new StandardWindow('Add a Contact Note');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cid');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
}

require_once('lib/common/app_footer.php');