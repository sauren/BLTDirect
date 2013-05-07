<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WorkLog.php');

$session->Secure(3);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('type', 'Type', 'select', '', 'paragraph', 1, 120);
$form->AddOption('type', '', '');
$form->AddOption('type', 'Hazard Log', 'Hazard Log');
$form->AddOption('type', 'Near Miss', 'Near Miss');
$form->AddField('log', 'Log', 'textarea', '', 'anything', null, null, true, 'rows="10" style="width: 100%;"');

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()){
		$task = new WorkLog();
		$task->type = $form->GetValue('type');
		$task->log = $form->GetValue('log');
		$task->add();

		redirect('Location: work_logs.php');
	}
}

$page = new Page('<a href="work_logs.php">Health &amp; Safety Logs</a> &gt; Add Health &amp; Safety Log', 'Add a new health &amp; safety log.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Adding a health &amp; safety log');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Enter health &amp; safety log details.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
echo $webForm->AddRow($form->GetLabel('log'), $form->GetHTML('log') . $form->GetIcon('log'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'work_logs.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');