<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TradeBanding.php');

if($action == 'add') {
	$session->Secure(3);
	add();
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

function remove() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$banding = new TradeBanding();
		$banding->delete($_REQUEST['id']);
	}

	redirect('Location: ?action=view');
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('costfrom', 'Cost From', 'text', '', 'float', 1, 11);
	$form->AddField('costto', 'Cost To', 'text', '', 'float', 1, 11);
	$form->AddField('markup', 'Markup', 'text', '', 'float', 1, 11);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$banding = new TradeBanding();
			$banding->costFrom = $form->GetValue('costfrom');
			$banding->costTo = $form->GetValue('costto');
			$banding->markup = $form->GetValue('markup');
			$banding->add();
			
			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Trade Banding</a> &gt; Add Markup', 'Add a new markup for trade banding.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding a trade banding markup');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	
	echo $window->Open();
	echo $window->AddHeader('Enter banding details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('costfrom'), $form->GetHTML('costfrom') . $form->GetIcon('costfrom'));
	echo $webForm->AddRow($form->GetLabel('costto'), $form->GetHTML('costto') . $form->GetIcon('costto'));
	echo $webForm->AddRow($form->GetLabel('markup'), $form->GetHTML('markup') . $form->GetIcon('markup'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$banding = new TradeBanding();
	
	if(!isset($_REQUEST['id']) || !$banding->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('costfrom', 'Cost From', 'text', $banding->costFrom, 'float', 1, 11);
	$form->AddField('costto', 'Cost To', 'text', $banding->costTo, 'float', 1, 11);
	$form->AddField('markup', 'Markup', 'text', $banding->markup, 'float', 1, 11);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$banding->costFrom = $form->GetValue('costfrom');
			$banding->costTo = $form->GetValue('costto');
			$banding->markup = $form->GetValue('markup');
			$banding->update();

			redirect('Location: ?action=view');
		}
	}

	$page = new Page('<a href="?action=view">Trade Banding</a> &gt; Update Markup', 'Edit an existing markup for trade banding.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating a trade banding markup');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	echo $window->AddHeader('Enter banding details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('costfrom'), $form->GetHTML('costfrom') . $form->GetIcon('costfrom'));
	echo $webForm->AddRow($form->GetLabel('costto'), $form->GetHTML('costto') . $form->GetIcon('costto'));
	echo $webForm->AddRow($form->GetLabel('markup'), $form->GetHTML('markup') . $form->GetIcon('markup'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Trade Banding', 'Listing all markups for trade accounts.');
	$page->Display('header');

	$table = new DataTable('tradebanding');
	$table->SetSQL("SELECT * FROM trade_banding");
	$table->AddField("ID#", "id");
	$table->AddField("Cost From (&pound;)", "costFrom", "right");
	$table->AddField("Cost To (&pound;)", "costTo", "right");
	$table->AddField("Markup (%)", "markup", "right");
	$table->AddLink("?action=update&id=%s","<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s','Are you sure you want to remove this item?');",  "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",  "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("markup");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add markup" class="btn" onclick="window.location.href=\'?action=add\'" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}