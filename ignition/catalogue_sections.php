<?php
require_once('lib/common/app_header.php');

if($action == "add"){
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update"){
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove"){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "moveup") {
	$session->Secure(3);
	moveup();
	exit;
} elseif($action == "movedown") {
	$session->Secure(3);
	movedown();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function moveup() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/CatalogueSection.php');

	$section = new CatalogueSection($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT Catalogue_Section_ID, Sequence_Number FROM catalogue_section WHERE Sequence_Number<%d ORDER BY Sequence_Number DESC LIMIT 0, 1", mysql_real_escape_string($section->SequenceNumber)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE catalogue_section SET Sequence_Number=%d WHERE Catalogue_Section_ID=%d", $data->Row['Sequence_Number'], mysql_real_escape_string($section->ID)));
		new DataQuery(sprintf("UPDATE catalogue_section SET Sequence_Number=%d WHERE Catalogue_Section_ID=%d", mysql_real_escape_string($section->SequenceNumber), $data->Row['Catalogue_Section_ID']));
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $section->CatalogueID));
}

function movedown() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/CatalogueSection.php');

	$section = new CatalogueSection($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT Catalogue_Section_ID, Sequence_Number FROM catalogue_section WHERE Sequence_Number>%d ORDER BY Sequence_Number ASC LIMIT 0, 1", mysql_real_escape_string($section->SequenceNumber)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE catalogue_section SET Sequence_Number=%d WHERE Catalogue_Section_ID=%d", $data->Row['Sequence_Number'], mysql_real_escape_string($section->ID)));
		new DataQuery(sprintf("UPDATE catalogue_section SET Sequence_Number=%d WHERE Catalogue_Section_ID=%d", mysql_real_escape_string($section->SequenceNumber), $data->Row['Catalogue_Section_ID']));
	}
	$data->Disconnect();

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $section->CatalogueID));
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSection.php');

	if(isset($_REQUEST['id'])) {
		$section = new CatalogueSection($_REQUEST['id']);
		$section->Delete();

		redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $section->CatalogueID));
	}

	redirect(sprintf("Location: catalogues.php"));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSection.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$catalogue = new Catalogue();

	if(!$catalogue->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: catalogues.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Catalogue ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 120, true, 'style="width: 300px;"');
	$form->AddField('description', 'Description', 'textarea', '', 'anything', 1, 1024, false, 'style="width: 100%;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$section = new CatalogueSection();
			$section->CatalogueID = $catalogue->ID;
			$section->Title = $form->GetValue('title');
			$section->Description = $form->GetValue('description');
			$section->Add();

			redirect(sprintf("Location: catalogue_sections.php?id=%d", $catalogue->ID));
		}
	}

	$page = new Page(sprintf('<a href="catalogue_profile.php?id=%d">Catalogue Profile</a> &gt; <a href="%s?id=%d">Edit Sections</a> &gt; Add Section', mysql_real_escape_string($catalogue->ID), mysql_real_escape_string($_SERVER['PHP_SELF']), mysql_real_escape_string($catalogue->ID)), 'Here you can add a section for this catalogue.');
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Section');
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
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'catalogue_sections.php?id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $catalogue->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CatalogueSection.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$section = new CatalogueSection();

	if(!$section->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: catalogues.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Catalogue Section ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $section->Title, 'anything', 1, 120, true, 'style="width: 300px;"');
	$form->AddField('description', 'Description', 'textarea', $section->Description, 'anything', 1, 1024, false, 'style="width: 100%;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$section->Title = $form->GetValue('title');
			$section->Description = $form->GetValue('description');
			$section->Update();

			redirect(sprintf("Location: catalogue_sections.php?id=%d", $section->CatalogueID));
		}
	}

	$page = new Page(sprintf('<a href="catalogue_profile.php?id=%d">Catalogue Profile</a> &gt; <a href="%s?id=%d">Edit Sections</a> &gt; Update Section', mysql_real_escape_string($section->CatalogueID), mysql_real_escape_string($_SERVER['PHP_SELF']), mysql_real_escape_string($section->CatalogueID)), 'Here you can update a section for this catalogue.');
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update Section');
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
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'catalogue_sections.php?id=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $section->CatalogueID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');

	$catalogue = new Catalogue();

	if(!$catalogue->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: catalogues.php"));
	}

	$page = new Page(sprintf('<a href="catalogue_profile.php?id=%d">Catalogue Profile</a> &gt; Edit Sections', $catalogue->ID), 'Here you can manage sections for this catalogue.');
	$page->Display('header');

	$table = new DataTable('sections');
	$table->SetSQL(sprintf("SELECT * FROM catalogue_section AS cs WHERE cs.Catalogue_ID=%d", mysql_real_escape_string($catalogue->ID)));
	$table->AddField("ID#", "Catalogue_Section_ID");
	$table->AddField("Title", "Title", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Sequence_Number");
	$table->AddLink("catalogue_categories.php?id=%s", "<img src=\"./images/folderopen.gif\" alt=\"Open Section Categories\" border=\"0\">", "Catalogue_Section_ID");
	$table->AddLink("catalogue_sections.php?action=moveup&id=%s", "<img src=\"images/aztector_3.gif\" alt=\"Move item up\" border=\"0\">", "Catalogue_Section_ID");
	$table->AddLink("catalogue_sections.php?action=movedown&id=%s", "<img src=\"images/aztector_4.gif\" alt=\"Move item down\" border=\"0\">", "Catalogue_Section_ID");
	$table->AddLink("catalogue_sections.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Catalogue_Section_ID");
	$table->AddLink("javascript:confirmRequest('catalogue_sections.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Catalogue_Section_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add section" class="btn" onclick="window.location.href=\'catalogue_sections.php?action=add&id=%d\'" />', $catalogue->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}