<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Email.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

if($action == 'select') {
	$session->Secure(3);
	select();
	exit;
} elseif($action == 'duplicate') {
	$session->Secure(3);
	duplicate();
	exit;
} elseif($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;	
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function select() {
	$email = new Email();

	if(isset($_REQUEST['id']) && !$email->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: emails.php"));
	}

	if(isset($_REQUEST['templateid'])) {
		$email->EmailTemplateID = $_REQUEST['templateid'];
		$email->Update();
	}

	redirect(sprintf("Location: email_profile.php?id=%d", $email->ID));
}

function remove() {
	$template = new EmailTemplate();

	if(isset($_REQUEST['templateid'])) {
		$template->Delete($_REQUEST['templateid']);
	}
	
	if(isset($_REQUEST['id'])) {
		redirect(sprintf('Location: ?id=%d', $_REQUEST['id']));
	}
	
	redirect('Location: ?action=view');
}

function duplicate() {
	$template = new EmailTemplate();

	if(isset($_REQUEST['templateid']) && $template->Get($_REQUEST['templateid'])) {
		$template->Name = sprintf('Copy of %s', $template->Name);
		$template->Add();
	}
	
	if(isset($_REQUEST['id'])) {
		redirect(sprintf('Location: ?id=%d', $_REQUEST['id']));
	}
	
	redirect('Location: ?action=view');
}

function update() {
	$email = new Email();

	if(!$email->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: emails.php"));
	}

	$template = new EmailTemplate();

	if(!$template->Get($_REQUEST['templateid'])) {
		redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $email->ID));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Email ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('templateid', 'Email Template ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $template->Name, 'anything', 1, 64);
	$form->AddField('template', 'Template', 'textarea', $template->Template, 'anything', 1, 4192, false, 'style="width: 100%;" rows="30" wrap="off"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$template->Name = $form->GetValue('name');
			$template->Template = $form->GetValue('template');
			$template->Update();

			redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $email->ID));
		}
	}

	$page = new Page(sprintf('<a href="email_profile.php?id=%d">Email Profile</a> &gt; <a href="%s?id=%d">Edit Template</a> &gt; Update Template', $email->ID, $_SERVER['PHP_SELF'], $email->ID), 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update Email Template');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $form->GetHTML('templateid');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('template'), $form->GetHTML('template') . $form->GetIcon('template'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'%s?id=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $email->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$email = new Email();

	if(!$email->Get($_REQUEST['id'])) {
		redirect('Location: emails.php');
	}

	$page = new Page(sprintf('<a href="email_profile.php?id=%d">Email Profile</a> &gt; Edit Template', $email->ID), 'Here you can select the template for this email.');
	$page->Display('header');
	?>

	<table class="DataTable">
		<thead>
			<tr>
				<th>&nbsp;</th>
				<th width="99%">Template</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody>

			<?php
			$data = new DataQuery(sprintf("SELECT * FROM email_template ORDER BY Name ASC"));
			if($data->TotalRows > 0) {
				while($data->Row) {
					?>

					<tr <?php echo ($email->EmailTemplateID == $data->Row['EmailTemplateID']) ? 'style="background-color: #9f9;"' : ''; ?>>
						<td><a href="?action=select&id=<?php echo $email->ID; ?>&templateid=<?php echo $data->Row['EmailTemplateID']; ?>"><img src="images/aztector_5.gif" alt="Select Template" border="0" /></a></td>
						<td><?php echo $data->Row['Name']; ?></td>
						<td nowrap="nowrap">
							<a href="javascript:confirmRequest('?action=duplicate&id=<?php echo $email->ID; ?>&templateid=<?php echo $data->Row['EmailTemplateID']; ?>', 'Are you sure you wish to duplicate this template?');"><img src="images/icon_pages_1.gif" alt="Duplicate" border="0" /></a>
							<a href="?action=update&id=<?php echo $email->ID; ?>&templateid=<?php echo $data->Row['EmailTemplateID']; ?>"><img src="images/icon_edit_1.gif" alt="Update" border="0" /></a>
							<a href="javascript:confirmRequest('?action=remove&id=<?php echo $email->ID; ?>&templateid=<?php echo $data->Row['EmailTemplateID']; ?>', 'Are you sure you wish remove this template?');"><img src="images/button-cross.gif" alt="Remove" border="0" /></a>
						</td>
					</tr>

					<?php
					$data->Next();
				}
			} else {
				?>

				<tr>
					<td align="center" colspan="2">There are no templates available for viewing.</td>
				</tr>

				<?php
			}
			$data->Disconnect();
			?>

		</tbody>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}