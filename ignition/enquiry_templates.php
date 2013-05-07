<?php
require_once('lib/common/app_header.php');

if($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryTemplate.php');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == 'true'){
		if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
			$template = new EnquiryTemplate();
			$template->Delete($_REQUEST['id']);
		}
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryTemplate.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Import Template', 'hidden', 0, 'numeric_unsigned', 1, 11, false);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 255, true, 'style="width: 100%;"');
	$form->AddField('template', 'Template', 'textarea', '', 'anything', 1, 16384, true, 'style="width: 100%;" rows="15"');
	$form->AddField('type', 'Type', 'select', '0', 'numeric_unsigned', 1, 11, true);
	$form->AddOption('type', '0', '-- All --');

	$data = new DataQuery(sprintf("SELECT * FROM enquiry_type ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('type', $data->Row['Enquiry_Type_ID'], $data->Row['Name']);
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$template = new EnquiryTemplate();
			$template->Title = $form->GetValue('title');
			$template->Template = $form->GetValue('template');
			$template->TypeID = $form->GetValue('type');
			$template->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$script = '<script language="javascript" type="text/javascript">
		var getDocumentNodeCallback = function() {
			changeTemplate();
		}

		var templateResponse = function(response) {
			var items = response.split("{br}\n");

			tinyMCE.execInstanceCommand(\'mceFocus\', false, \'template\');
			tinyMCE.activeEditor.setContent(items[2]);
		}

		var request = new HttpRequest();
		request.setCaching(false);
		request.setHandlerResponse(templateResponse);

		var changeTemplate = function() {
			var id = document.getElementById(\'parent\').value;

			request.abort();
		    request.post(\'lib/util/getDocument.php\', "id="+id);
		}
		</script>';

	$page = new Page(sprintf('<a href="%s">Enquiry Templates</a> &gt; Add Template', $_SERVER['PHP_SELF']), 'Add a new enquiry template for quicker responses here.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead($script);
	$page->AddOnLoad("document.getElementById('title').focus();");
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Adding an Enquiry Template');
	$webForm = new StandardForm();

	echo '<span style="display: none;" id="parentCaption"></span>';

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Enter a title and standard template for this enquiry template.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	$tmpParentTxt = ' <a href="javascript:popUrl(\'documents.php?action=getnode&callback=true\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('template').$tmpParentTxt, $form->GetHTML('template'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'enquiry_templates.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryTemplate.php');

	$template = new EnquiryTemplate($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Import Template', 'hidden', 0, 'numeric_unsigned', 1, 11, false);
	$form->AddField('id', 'ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $template->Title, 'anything', 1, 255, true, 'style="width: 100%;"');
	$form->AddField('template', 'Template', 'textarea', $template->Template, 'anything', 1, 16384, true, 'style="width: 100%;" rows="15"');
	$form->AddField('type', 'Type', 'select', $template->TypeID, 'numeric_unsigned', 1, 11, true);
	$form->AddOption('type', '0', '-- All --');

	$data = new DataQuery(sprintf("SELECT * FROM enquiry_type ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('type', $data->Row['Enquiry_Type_ID'], $data->Row['Name']);
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$template->Title = $form->GetValue('title');
			$template->Template = $form->GetValue('template');
			$template->TypeID = $form->GetValue('type');
			$template->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$script = '<script language="javascript" type="text/javascript">
		var getDocumentNodeCallback = function() {
			changeTemplate();
		}

		var templateResponse = function(response) {
			var items = response.split("{br}\n");

			tinyMCE.execInstanceCommand(\'mceFocus\', false, \'template\');
			tinyMCE.activeEditor.setContent(items[2]);
		}

		var request = new HttpRequest();
		request.setCaching(false);
		request.setHandlerResponse(templateResponse);

		var changeTemplate = function() {
			var id = document.getElementById(\'parent\').value;

			request.abort();
		    request.post(\'lib/util/getDocument.php\', "id="+id);
		}
		</script>';

	$page = new Page(sprintf('<a href="%s">Enquiry Templates</a> &gt; Update Template', $_SERVER['PHP_SELF']), 'Edit this enquiry template for quicker responses here.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead($script);
	$page->AddOnLoad("document.getElementById('title').focus();");
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Editing an Enquiry Template');
	$webForm = new StandardForm();

	echo '<span style="display: none;" id="parentCaption"></span>';

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Enter a title and standard template for this enquiry template.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	$tmpParentTxt = ' <a href="javascript:popUrl(\'documents.php?action=getnode&callback=true\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('template').$tmpParentTxt, $form->GetHTML('template'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'enquiry_templates.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Enquiry Templates', 'Listing all available enquiry templates.');
	$page->Display('header');

	$table = new DataTable('templates');
	$table->SetSQL("SELECT et.*, ett.Name FROM enquiry_template AS et LEFT JOIN enquiry_type AS ett ON et.Enquiry_Type_ID=ett.Enquiry_Type_ID");
	$table->AddField("ID#", "Enquiry_Template_ID");
	$table->AddField("Type", "Name", "left");
	$table->AddField("Title", "Title", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->AddLink("enquiry_templates.php?action=update&id=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">","Enquiry_Template_ID");
	$table->AddLink("javascript:confirmRequest('enquiry_templates.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">","Enquiry_Template_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add new template" class="btn" onclick="window.location.href=\'enquiry_templates.php?action=add\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>