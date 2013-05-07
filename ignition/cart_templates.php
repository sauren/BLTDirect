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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartTemplate.php');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == 'true'){
		if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
			$template = new CartTemplate();
			$template->Delete($_REQUEST['id']);
		}
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartTemplate.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('title', 'Title', 'text', '', 'anything', 1, 255, true, 'style="width: 100%;"');
	$form->AddField('template', 'Template', 'textarea', '', 'anything', 1, 16384, true, 'style="width: 100%;" rows="15"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$template = new CartTemplate();
			$template->Title = $form->GetValue('title');
			$template->Template = $form->GetValue('template');
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

	$page = new Page(sprintf('<a href="%s">Abandoned Cart Templates</a> &gt; Add Template', $_SERVER['PHP_SELF']), 'Add a new abandoned cart template for quicker responses here.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead($script);
	$page->AddOnLoad("document.getElementById('title').focus();");
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Adding an Abandoned Cart Template');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Enter a title and standard template for this enquiry template.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));

	echo $webForm->AddRow($form->GetLabel('template'), $form->GetHTML('template'). $form->GetIcon('template'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'cart_templates.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartTemplate.php');

	$template = new CartTemplate($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $template->Title, 'anything', 1, 255, true, 'style="width: 100%;"');
	$form->AddField('template', 'Template', 'textarea', $template->Template, 'anything', 1, 16384, true, 'style="width: 100%;" rows="15"');


	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$template->Title = $form->GetValue('title');
			$template->Template = $form->GetValue('template');
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

	$page = new Page(sprintf('<a href="%s">Enquiry Templates</a> &gt; Update Template', $_SERVER['PHP_SELF']), 'Edit this abandoned cart template for quicker responses here.');

	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead($script);
	$page->AddOnLoad("document.getElementById('title').focus();");
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Editing an Abandoned Cart Template');
	$webForm = new StandardForm();

	echo '<span style="display: none;" id="parentCaption"></span>';

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Enter a title and standard template for this enquiry template.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('template'), $form->GetHTML('template'). $form->GetIcon('template'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'cart_templates.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Abandoned Cart Templates', 'Listing all available abandoned cart email templates.');
	$page->Display('header');

	$table = new DataTable('templates');
	$table->SetSQL("SELECT * FROM customer_basket_template");
	$table->AddField("ID#", "Customer_Basket_ID");
	$table->AddField("Title", "Title", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Title");


	$table->AddLink("cart_templates.php?action=update&id=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">","Customer_Basket_ID");

	$table->AddLink("javascript:confirmRequest('cart_templates.php?action=remove&confirm=true&id=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">","Customer_Basket_ID");
	
	$table->Finalise();
	$table->DisplayTable();
	
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo '<input type="button" name="add" value="add new template" class="btn" onclick="window.location.href=\'cart_templates.php?action=add\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>