<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');

if($action == "regeneratepassword"){
	$session->Secure(3);
	regeneratePassword();
	exit();
} else {
	$session->Secure(2);
	view();
	exit();
}

function regeneratePassword() {
	$customer = new Customer();

	if(isset($_REQUEST['id']) && $customer->Get($_REQUEST['id'])) {
		$customer->Contact->Get();
		$customer->ResendEmail();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[EMAIL\]/', $customer->Username);
		$findReplace->Add('/\[PASSWORD\]/', $customer->tempPassword);

		$templateHtml = $findReplace->Execute(Template::GetContent('email_customer_regenerated'));

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $templateHtml);
		$findReplace->Add('/\[NAME\]/', sprintf('%s %s', $customer->Contact->Person->Name, $customer->Contact->Person->LastName));

		$templateHtml = $findReplace->Execute(Template::GetContent('email_template_standard'));

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf('%s - Password Regenerated', $GLOBALS['COMPANY']));
		$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($templateHtml);
		$mail->send(array($customer->Contact->Person->Email));
	}

	redirect(sprintf("Location: contact_profile.php?cid=%d", $customer->Contact->ID));
}


function view() {
	$customer = new Customer($_REQUEST['id']);
	$customer->Contact->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', $_REQUEST['id'], 'numeric_unsigned', 0, 11);
	$form->AddField('username','Username','text',$customer->Username,'username',1,100);
	$form->AddField('password','Password','password','','password',PASSWORD_LENGTH_CUSTOMER,100,false);
	$form->AddField('cpassword','Confirm Password','password','','password',PASSWORD_LENGTH_CUSTOMER,100,false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if($form->GetValue('password') != $form->GetValue('cpassword')){
				$form->AddError('Confirm Password is not the same as Password.', 'cpassword');
				$confirmPassError = "Is not the same as Password.";
			}
			if($customer->GetPassword() == sha1($form->GetValue('password'))){
				$form->AddError('This Password cannot be the same as the Current Password', 'password');
			}
			if($form->Valid){
				$customer->Username = $form->GetValue('username');
				$customer->SetPassword($form->GetValue('password'));
				$customer->Update();
			 
				redirect(sprintf("Location: contact_profile.php?cid=%d", $customer->Contact->ID));
			}
		}
	}


	$page = new Page(sprintf("<a href=contact_profile.php?cid=%d>%s %s</a> &gt Customer Security", $customer->Contact->ID, $customer->Contact->Person->Name, $customer->Contact->Person->LastName), "View the settings of this supplier.");
	$page->Display('header');


	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}


	$window = new StandardWindow('Edit the security details');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('Regenerate', sprintf('<a href="?action=regeneratepassword&id=%d">Click here</a> to regenerate and email a password to this customer.', $customer->ID));
	echo $webForm->AddRow($form->GetLabel('username'),$form->GetHTML('username').$form->GetIcon('username'));
	echo $webForm->AddRow($form->GetLabel('password'),$form->GetHTML('password').$form->GetIcon('password'));
	echo $webForm->AddRow($form->GetLabel('cpassword'),$form->GetHTML('cpassword').$form->GetIcon('cpassword'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'contact_profile.php?cid=%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $customer->Contact->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}