<?php
require_once('lib/common/app_header.php');

if($action == 'update'){
	$session->Secure(3);
	update();
	exit;
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');

	$catalogue = new Catalogue();

	if(!$catalogue->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: catalogues.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Catalogue ID', 'hidden', $catalogue->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $catalogue->Title, 'anything', 1, 120, true, 'style="width: 300px;"');
	$form->AddField('description', 'Description', 'textarea', $catalogue->Description, 'anything', 1, 1024, false, 'style="width: 100%;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$catalogue->Title = $form->GetValue('title');
			$catalogue->Description = $form->GetValue('description');
			$catalogue->Update();

			redirect(sprintf("Location: catalogue_profile.php?id=%d", $catalogue->ID));
		}
	}

	$page = new Page(sprintf('<a href="catalogue_profile.php?id=%d">Catalogue Profile</a> &gt; Edit Description', $catalogue->ID),'Please complete the form below.');
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Edit Description');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'catalogue_profile.php?id=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $catalogue->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>