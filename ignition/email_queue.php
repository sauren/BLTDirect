<?php
require_once('lib/common/app_header.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'open'){
	$session->Secure(2);
	open();
	exit;
} elseif($action == 'openheader'){
	$session->Secure(2);
	openHeader();
	exit;
} elseif($action == 'openfooter'){
	$session->Secure(2);
	openFooter();
	exit;
} elseif($action == 'openbody'){
	$session->Secure(2);
	openBody();
	exit;
} elseif($action == 'process'){
	$session->Secure(2);
	process();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Email Queue', 'Listing emails currently or previously queued for delivery.');
	$page->Display('header');

	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('issent', 'Is Sent', 'select', 'N', 'alpha_numeric', 0, 1, false);
	$form->AddOption('issent', '', '-- All --');
	$form->AddOption('issent', 'Y', 'Yes');
	$form->AddOption('issent', 'N', 'No');

	$sqlStatement = '';

	if(strlen($form->GetValue('issent')) > 0) {
		$sqlStatement .= sprintf("WHERE eq.Is_Sent='%s'", $form->GetValue('issent'));
	}

	$window = new StandardWindow('Filter emails');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('issent'),$form->GetHTML('issent'));
	echo $webForm->AddRow('','<input type="submit" name="search" value="search" class="btn">');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";

	$table = new DataTable('emails');
	$table->SetSQL(sprintf("SELECT eq.*, IF(eq.Sent_On='0000-00-00 00:00:00', '', eq.Sent_On) AS Sent_Date, IF(eq.Send_After='0000-00-00 00:00:00', '', eq.Send_After) AS After_Date, em.Module FROM email_queue AS eq LEFT JOIN email_queue_module AS em ON em.Email_Queue_Module_ID=eq.Module_ID %s", $sqlStatement));
	$table->AddField('Type', 'Type', 'center');
	$table->AddField('Priority', 'Priority', 'center');
	$table->AddField('Module', 'Module', 'left');
	$table->AddField('Email Addresses', 'To_Address', 'left');
	$table->AddField('Subject', 'Subject', 'left');
	$table->AddField('Is Sent', 'Is_Sent', 'center');
	$table->AddField('Sent On', 'Sent_Date', 'left');
	$table->AddField('Send After', 'After_Date', 'left');
	$table->AddLink("javascript:popUrl('email_queue.php?action=open&id=%s', 800, 600);", "<img src=\"./images/folderopen.gif\" alt=\"Open Email\" border=\"0\">", "Email_Queue_ID");
	$table->AddLink("javascript:confirmRequest('email_queue.php?action=process&id=%s','Are you sure you want to process this email?');", "<img src=\"images/aztector_5.gif\" alt=\"Process\" border=\"0\">", "Email_Queue_ID");
	$table->AddLink("javascript:confirmRequest('email_queue.php?action=remove&id=%s','Are you sure you want to remove this email?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Email_Queue_ID", true, false, array('Is_Sent', '==', 'N'));
	$table->SetMaxRows(25);
	$table->SetOrderBy("Sent_On");
	$table->Order = "DESC";
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');

	if(isset($_REQUEST['id'])) {
		$email = new EmailQueue();
		$email->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function open() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');

	$email = new EmailQueue();

	if(isset($_REQUEST['id']) && $email->Get($_REQUEST['id'])) {
		?>
		<html>
			<head>
				<title><?php echo $email->Subject; ?></title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			</head>

			<frameset border="0" rows="60,*,100" framespacing="2" frameborder="yes" id="frameSet" bordercolor="#5C6B80">
				<frame src="email_queue.php?action=openheader&id=<?php print $_REQUEST['id']; ?>" name="frameHeader" frameborder="no" scrolling="auto" border="0"></frame>
				<frame src="email_queue.php?action=openbody&id=<?php print $_REQUEST['id']; ?>" name="frameBody" frameborder="no" scrolling="auto" border="0"></frame>
				<frame src="email_queue.php?action=openfooter&id=<?php print $_REQUEST['id']; ?>" name="frameFooter" frameborder="no" scrolling="auto" border="0"></frame>
			</frameset>

		</html>
		<?php
	} else {
		echo '<script language="javascript" type="text/javascript">window.self.close();</script>';
	}
}

function openHeader() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');

	$email = new EmailQueue();

	if($email->Get($_REQUEST['id'])) {
		?>
		<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<link href="css/i_import.css" rel="stylesheet" type="text/css">
		</head>
		<body style="padding-bottom: 0px;">
			<p style="padding: 0;"><span class="pageTitle"><?php echo $email->Subject; ?></span></p>
		</body>
		</html>
		<?php
	}

	require_once('lib/common/app_footer.php');
}

function openBody() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');

	$email = new EmailQueue();

	if($email->Get($_REQUEST['id'])) {
		echo $email->Body;
	}

	require_once('lib/common/app_footer.php');
}

function openFooter() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');

	$email = new EmailQueue();

	if($email->Get($_REQUEST['id'])) {
		$email->GetAttachments();
		?>
		<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<link href="css/i_import.css" rel="stylesheet" type="text/css">
		</head>
		<body style="padding-bottom: 0px;">
			<table width="100%">
				<tr>
					<td valign="top" width="50%" aligh="left">
						<p style="padding: 0;"><strong>Recipients:</strong></p>

						<?php
						$emailAddresses = explode('; ', $email->ToAddress);

						if(count($emailAddresses) > 0) {
							echo '<p style="padding: 0;">';

							for($i=0; $i<count($emailAddresses); $i++) {
								echo sprintf('&nbsp;&raquo; %s<br />', $emailAddresses[$i]);
							}

							echo '</p>';
						} else {
							echo '<p style="padding: 0;"><em>No recipients have been assigned to this email.</em></p>';
						}
						?>
					</td>
					<td valign="top" width="50%" aligh="left">
						<p style="padding: 0;"><strong>Attachments:</strong></p>

						<?php
						if(count($email->Attachments) > 0) {
							echo '<p style="padding: 0;">';

							for($i=0; $i<count($email->Attachments); $i++) {
								if(!empty($email->Attachments[$i]->FilePath) && file_exists($email->Attachments[$i]->FilePath)) {
									echo sprintf('&nbsp;&raquo; <a href="%s" target="_blank">%s</a><br />', $email->Attachments[$i]->WebPath, basename($email->Attachments[$i]->FilePath));
								} elseif(!empty($email->Attachments[$i]->FilePath)) {
									echo sprintf('&nbsp;&raquo; %s<br />', basename($email->Attachments[$i]->FilePath));
								}
							}

							echo '</p>';
						} else {
							echo '<p style="padding: 0;"><em>No files have been attached to this email.</em></p>';
						}
						?>

					</td>
				</tr>
			</table>
		</body>
		</html>
		<?php
	}

	require_once('lib/common/app_footer.php');
}

function process() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/htmlMimeMail5.php");
ini_set('display_errors', true);
	$form = new Form($_SERVER['PHP_SELF']);
	
	$email = new EmailQueue();

	if($email->Get($_REQUEST['id'])) {
		$email->GetAttachments();

		$mail = new htmlMimeMail5();
		$mail->setFrom($email->FromAddress);
		$mail->setSubject($email->Subject);
		$mail->setReturnPath($email->ReturnPath);

		if($email->Receipt == 'Y') {
			$mail->setReceipt($email->FromAddress);
		}

		switch($email->Type) {
			case 'T':
				$mail->setText($email->Body);
				break;
			case 'H':
				$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
				$mail->setHTML($email->Body);
				break;
		}

		for($i=0; $i<count($email->Attachments); $i++) {
			if(!empty($email->Attachments[$i]->FilePath) && file_exists($email->Attachments[$i]->FilePath)) {
				$mail->addAttachment(new fileAttachment($email->Attachments[$i]->FilePath));
			}
		}

		if($email->IsBcc == 'N') {
			$emailAddresses = explode(';', $email->ToAddress);

			foreach($emailAddresses as $emailAddress) {
				$emailAddress = trim($emailAddress);

				if((strlen($emailAddress) > 0)  && preg_match(sprintf("/%s/", $form->RegularExp['email']), $emailAddress)) {
					$mail->send(array($emailAddress), 'smtp');
				}
			}
		} else {
			$tempEmailAddresses = array();
			$emailAddresses = explode(';', $email->ToAddress);

			foreach($emailAddresses as $emailAddress) {
				$emailAddress = trim($emailAddress);

				if((strlen($emailAddress) > 0)  && preg_match(sprintf("/%s/", $form->RegularExp['email']), $emailAddress)) {
					$tempEmailAddresses[$emailAddress] = $emailAddress;
				}
			}

			if(count($tempEmailAddresses) > 0) {
				$bcc = implode('; ', $tempEmailAddresses);

				$mail->setBcc(str_replace(';', ',', $bcc));
				$mail->send(array(), 'smtp');
			}
		}

		$email->SetSent();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}