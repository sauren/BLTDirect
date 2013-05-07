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
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Affiliate.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','add','alpha',3,3);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('affiliate','Title','text','','anything',0,100, true, 'style="width=400px;"');
	$form->AddField('url','URL','text','','link',0,255, true, 'style="width=400px;"');
	$form->AddField('desc','Description','textarea','','paragraph',0,2000, false,'style="width=400px; font-family: arial, sans-serif;" rows="5"');
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true")
	{
		$form->Validate();

		$affiliate = new Affiliate();
		$affiliate->Title = $form->GetValue('affiliate');
		$affiliate->URL = $form->GetValue('url');
		$affiliate->Description = $form->GetValue('desc');

		if($form->Valid)
		{
			$affiliate->Add('image');

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="'.$_SERVER['PHP_SELF'].'">Affiliates</a> &gt; Add Affiliate', 'Here you can add a new affiliate');
	$page->Display('header');

	if(!$form->Valid)
	{
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the affiliate information');
	$webForm = new StandardForm();
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('affiliate'),$form->GetHTML('affiliate').$form->GetIcon('affiliate'));
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
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Affiliate.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	if(!isset($_REQUEST['aid']))
	{
		require_once('lib/common/app_footer.php');
		redirect("Location: %s", $_SERVER['PHP_SELF']);
	}

	$affiliate = new Affiliate();
	$affiliate->Get($_REQUEST['aid']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','update','alpha',6,6);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('aid','Affiliate ID','hidden',$affiliate->ID,'numeric_unsigned',0,11);
	$form->AddField('affiliate','Title','text',$affiliate->Title,'anything',0,100, true, 'style="width=400px;"');
	$form->AddField('url','URL','text',$affiliate->URL,'link',0,255, true,'style="width=400px;"');
	$form->AddField('desc','Description','textarea',$affiliate->Description,'paragraph',0,2000, false,'style="width=400px; font-family: arial, sans-serif;" rows="5"');
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true")
	{
		$form->Validate();

		$affiliate->Title = $form->GetValue('affiliate');
		$affiliate->URL = $form->GetValue('url');
		$affiliate->Description = $form->GetValue('desc');

		if($form->Valid)
		{
			$affiliate->Update('image');

			redirect("Location: affiliates.php");
		}
	}

	$page = new Page('<a href="'.$_SERVER['PHP_SELF'].'">Affiliates</a> &gt; Update Affiliate','Here you can edit the affiliate');
	$page->Display('header');

	if(!$form->Valid)
	{
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please edit the affiliate information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('aid');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('affiliate'),$form->GetHTML('affiliate').$form->GetIcon('affiliate'));
	echo $webForm->AddRow($form->GetLabel('url'),$form->GetHTML('url').$form->GetIcon('url'));
	echo $webForm->AddRow($form->GetLabel('desc'),$form->GetHTML('desc').$form->GetIcon('desc'));
	echo $webForm->AddRow($form->GetLabel('image'),$form->GetHTML('image').$form->GetIcon('image'));
	if((strlen($affiliate->Image->FileName) > 0) && (file_exists('../images/affiliates/'.$affiliate->Image->FileName))) {
		echo $webForm->AddRow('Current Image','<img src="../images/affiliates/'.$affiliate->Image->FileName.'" />');
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
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Affiliate.php');

	if(isset($_REQUEST['aid']) && is_numeric($_REQUEST['aid']))
	{
		$affiliate = new Affiliate();
		$affiliate->Remove($_REQUEST['aid']);
	}

	redirect("Location: affiliates.php");
}

function view()
{
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

	$page = new Page("Affiliates", "Here you can view the affiliates.");
	$page->Display('header');

	$table = new DataTable("com");
	$table->SetSQL("SELECT Affiliate_ID, Title, URL FROM affiliate");
	$table->AddField("ID","Affiliate_ID",'right');
	$table->AddField("Affiliate","Title");
	$table->AddField("Location","URL");
	$table->AddLink("affiliates.php?action=update&aid=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update this affiliate\" border=\"0\">",  "Affiliate_ID");
	$table->AddLink("javascript:confirmRequest('affiliates.php?action=remove&aid=%s','Are you sure you want to remove this affiliate?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove this affiliate\" border=\"0\">", "Affiliate_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();

	echo "<br />";
	$table->DisplayNavigation();

	echo '<br /><input name="add" type="submit" value="add a new affiliate" class="btn" onclick="window.location.href=\'./affiliates.php?action=add\'">';

	$page->Display('footer');
}

require_once('lib/common/app_header.php');
?>