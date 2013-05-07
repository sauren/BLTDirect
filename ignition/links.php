<?php
require_once('lib/common/app_header.php');

if($action == "add")
{
	$session->Secure(3);
	add();
}
elseif($action == "update")
{
	$session->Secure(3);
	update();
}
elseif($action == "remove")
{
	$session->Secure(3);
	remove();
}
else
{
	$session->Secure(2);
	view();
}

function add()
{
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Link.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','add','alpha',3,3);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('link','Title','text','','anything',0,100, true, 'style="width=400px;"');
	$form->AddField('url','URL','text','','link',0,255, true, 'style="width=400px;"');
	$form->AddField('desc','Description','textarea','','paragraph',0,2000, false,'style="width=400px; font-family: arial, sans-serif;" rows="5"');
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true")
	{
		$form->Validate();

		$link = new Link();
		$link->Title = $form->GetValue('link');
		$link->URL = $form->GetValue('url');
		$link->Description = $form->GetValue('desc');

		if($form->Valid)
		{
			$link->Add('image');

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="'.$_SERVER['PHP_SELF'].'">Links</a> &gt; Add Link', 'Here you can add a new link');
	$page->Display('header');

	if(!$form->Valid)
	{
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the link information');
	$webForm = new StandardForm();
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('link'),$form->GetHTML('link').$form->GetIcon('link'));
	echo $webForm->AddRow($form->GetLabel('url'),$form->GetHTML('url').$form->GetIcon('url'));
	echo $webForm->AddRow($form->GetLabel('desc'),$form->GetHTML('desc').$form->GetIcon('desc'));
	echo $webForm->AddRow($form->GetLabel('image'),$form->GetHTML('image').$form->GetIcon('image'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';" />&nbsp;<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function update()
{
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Link.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	if(!isset($_REQUEST['lid']))
	{
		require_once('lib/common/app_footer.php');
		redirect("Location: %s", $_SERVER['PHP_SELF']);
	}

	$link = new Link();
	$link->Get($_REQUEST['lid']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','update','alpha',6,6);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('lid','Link ID','hidden',$link->ID,'numeric_unsigned',0,11);
	$form->AddField('link','Title','text',$link->Title,'anything',0,100, true, 'style="width=400px;"');
	$form->AddField('url','URL','text',$link->URL,'link',0,255, true,'style="width=400px;"');
	$form->AddField('desc','Description','textarea',$link->Description,'paragraph',0,2000, false,'style="width=400px; font-family: arial, sans-serif;" rows="5"');
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true")
	{
		$form->Validate();

		$link->Title = $form->GetValue('link');
		$link->URL = $form->GetValue('url');
		$link->Description = $form->GetValue('desc');

		if($form->Valid)
		{
			$link->Update('image');

			redirect("Location: links.php");
		}
	}

	$page = new Page('<a href="'.$_SERVER['PHP_SELF'].'">Links</a> &gt; Update Link','Here you can edit the link');
	$page->Display('header');

	if(!$form->Valid)
	{
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please edit the link information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('lid');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('link'),$form->GetHTML('link').$form->GetIcon('link'));
	echo $webForm->AddRow($form->GetLabel('url'),$form->GetHTML('url').$form->GetIcon('url'));
	echo $webForm->AddRow($form->GetLabel('desc'),$form->GetHTML('desc').$form->GetIcon('desc'));
	echo $webForm->AddRow($form->GetLabel('image'),$form->GetHTML('image').$form->GetIcon('image'));
	if((strlen($link->Image->FileName) > 0) && (file_exists('../images/links/'.$link->Image->FileName))) {
		echo $webForm->AddRow('Current Image','<img src="../images/links/'.$link->Image->FileName.'" />');
	}
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';" />&nbsp;<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function remove()
{
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Link.php');

	if(isset($_REQUEST['lid']) && is_numeric($_REQUEST['lid']))
	{
		$link = new Link();
		$link->Remove($_REQUEST['lid']);
	}

	redirect("Location: links.php");
	exit;
}

function view()
{
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

	$page = new Page("Links", "Here you can view the links");
	$page->Display('header');

	$table = new DataTable("com");
	$table->SetSQL("SELECT Link_ID, Title, URL FROM link");
	$table->AddField("ID","Link_ID",'right');
	$table->AddField("Link","Title");
	$table->AddField("Location","URL");
	$table->AddLink("links.php?action=update&lid=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update this link\" border=\"0\">",  "Link_ID");
	$table->AddLink("javascript:confirmRequest('links.php?action=remove&lid=%s','Are you sure you want to remove this link?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove this link\" border=\"0\">", "Link_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();

	echo "<br />";
	$table->DisplayNavigation();

	echo '<br /><input name="add" type="submit" value="add a new link" class="btn" onclick="window.location.href=\'./links.php?action=add\'">';

	$page->Display('footer');
}

require_once('lib/common/app_header.php');
?>