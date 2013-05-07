<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
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
	$supplier = new Supplier();

	if(isset($_REQUEST['id']) && $supplier->Get($_REQUEST['id'])) {
		$password = new Password(PASSWORD_LENGTH_SUPPLIER);

		$supplier->Contact->Get();
		$supplier->SetPassword($password->Value);
		$supplier->Update();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[EMAIL\]/', $supplier->Username);
		$findReplace->Add('/\[PASSWORD\]/', $password->Value);

		$templateHtml = $findReplace->Execute(Template::GetContent('email_supplier_regenerated'));

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $templateHtml);
		$findReplace->Add('/\[NAME\]/', sprintf('%s %s', $supplier->Contact->Person->Name, $supplier->Contact->Person->LastName));

		$templateHtml = $findReplace->Execute(Template::GetContent('email_template_standard'));

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf('%s - Password Regenerated', $GLOBALS['COMPANY']));
		$mail->setText('This is an HTML email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($templateHtml);
		$mail->send(array($supplier->Contact->Person->Email));
	}

	redirect(sprintf("Location: contact_profile.php?cid=%d", $supplier->Contact->ID));
}

function view() {
	$supplier = new Supplier($_REQUEST['id']);
	$supplier->Contact->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', $_REQUEST['id'], 'numeric_unsigned', 0, 11);
	$form->AddField('username','Username','text',$supplier->Username,'username',1,100);
	$form->AddField('password','Password','password','','password',PASSWORD_LENGTH_SUPPLIER,100,false);
	$form->AddField('cpassword','Confirm Password','password','','password',PASSWORD_LENGTH_SUPPLIER,100,false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if($form->GetValue('password') != '') {
				$supplier->SetPassword($form->GetValue('password'));
			}

			if($form->GetValue('password') != $form->GetValue('cpassword')){
				$form->AddError('Passwords must match.', 'cpassword');
			}

			if($form->Valid){
				$supplier->Username = $form->GetValue('username');
				$supplier->Update();

				redirect(sprintf("Location: contact_profile.php?cid=%d", $supplier->Contact->ID));
			}
		}
	}

	$page = new Page(sprintf("<a href=contact_profile.php?cid=%d>%s %s</a> &gt Supplier Security", $supplier->Contact->ID, $supplier->Contact->Person->Name, $supplier->Contact->Person->LastName), "View the settings of this supplier.");
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
	echo $webForm->AddRow('Regenerate', sprintf('<a href="?action=regeneratepassword&id=%d">Click here</a> to regenerate and email a password to this supplier.', $supplier->ID));
	echo $webForm->AddRow($form->GetLabel('username'),$form->GetHTML('username').$form->GetIcon('username'));
	echo $webForm->AddRow($form->GetLabel('password'),$form->GetHTML('password').$form->GetIcon('password'));
	echo $webForm->AddRow($form->GetLabel('cpassword'),$form->GetHTML('cpassword').$form->GetIcon('cpassword'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'contact_profile.php?cid=%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $supplier->Contact->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}