<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SearchFailure.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SearchSubstitute.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

if($action == "addsubstitute") {
	$session->Secure(3);
	addsubstitute();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function addsubstitute() {
	$failure = new SearchFailure();

	if(!isset($_REQUEST['id']) || !$failure->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addsubstitute', 'alpha', 13, 13);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('replacement', 'Replacement', 'text', '', 'anything', 1, 240);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$search = new SearchSubstitute();
			$search->term = $failure->term;
			$search->replacement = $form->GetValue('replacement');
			$search->add();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Search Failures</a> &gt; Add Substitute', 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Substitute');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('Term', $failure->term);
	echo $webForm->AddRow($form->GetLabel('replacement'), $form->GetHTML('replacement') . $form->GetIcon('replacement'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'view', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'From Date', 'text', date('d/m/Y', mktime(0, 0, 0, date('m') - 1, date('d') + 1, date('Y'))), 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'To Date', 'text', date('d/m/Y', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'))), 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');

	$page = new Page('Search Failures', 'Listing all search failures.');
	$page->LinkScript('js/scw.js');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Substitute');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start') . $form->GetIcon('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end') . $form->GetIcon('end'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="filter" value="filter" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	echo '<br />';

	$table = new DataTable('failures');
	$table->SetSQL(sprintf('SELECT sf.*, COUNT(ss.id) AS substitutes FROM search_failure AS sf LEFT JOIN search_substitute AS ss ON ss.term LIKE sf.term WHERE sf.`date` BETWEEN \'%s\' AND \'%s\' GROUP BY sf.id', sprintf('%s-%s-%s', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), sprintf('%s-%s-%s', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2))));
	$table->AddField('ID#', 'id', 'left');
	$table->AddField('Term', 'term', 'left');
	$table->AddField('Date', 'date', 'left');
	$table->AddField('Frequency', 'frequency', 'right');
	$table->AddField('Substitutes', 'substitutes', 'right');
	$table->AddLink("?action=addsubstitute&id=%s", "<img src=\"images/button-plus.gif\" alt=\"Add substitute\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("frequency");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}