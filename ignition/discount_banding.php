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
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DiscountBanding.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','add','alpha',3,3);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('name','Name','text','','anything',0,100);
	$form->AddField('discount','Discount','text','','numeric_unsigned',1, 11);
	$form->AddField('low','Low Trigger','text','','float',1,11);
	$form->AddField('high','High Trigger','text','','float',1,11);
	$form->AddField('threshold','Threshold','text','','float',1,11);
	$form->AddField('notes','Notes','textarea','','anything',1,2048, false, 'rows="5" style="width: 300px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()) {
			$banding = new DiscountBanding();
			$banding->Name = $form->GetValue('name');
			$banding->Discount = $form->GetValue('discount');
			$banding->TriggerLow = $form->GetValue('low');
			$banding->TriggerHigh = $form->GetValue('high');
			$banding->Threshold = $form->GetValue('threshold');
			$banding->Notes = $form->GetValue('notes');
			$banding->Add();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="'.$_SERVER['PHP_SELF'].'">Discount Banding</a> &gt; Add Band','Here you can add a banding');
	$page->Display('header');

	if(!$form->Valid)
	{
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please enter the band information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'),$form->GetHTML('name').$form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('discount'),$form->GetHTML('discount').$form->GetIcon('discount'));
	echo $webForm->AddRow($form->GetLabel('low'),$form->GetHTML('low').$form->GetIcon('low'));
	echo $webForm->AddRow($form->GetLabel('high'),$form->GetHTML('high').$form->GetIcon('high'));
	echo $webForm->AddRow($form->GetLabel('threshold'),$form->GetHTML('threshold').$form->GetIcon('threshold'));
	echo $webForm->AddRow($form->GetLabel('notes'),$form->GetHTML('notes').$form->GetIcon('notes'));
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
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DiscountBanding.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	if(!isset($_REQUEST['id'])) {
		redirect("Location: %s", $_SERVER['PHP_SELF']);
	}

	$banding = new DiscountBanding();
	$banding->Get($_REQUEST['id']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','update','alpha',6,6);
	$form->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$form->AddField('id','Banding ID','hidden',$banding->ID,'numeric_unsigned',0,11);
	$form->AddField('name','Name','text',$banding->Name,'anything',0,100);
	$form->AddField('discount','Discount','text',$banding->Discount,'numeric_unsigned',1, 11);
	$form->AddField('low','Low Trigger','text',$banding->TriggerLow,'float',1,11);
	$form->AddField('high','High Trigger','text',$banding->TriggerHigh,'float',1,11);
	$form->AddField('threshold','Threshold','text',$banding->Threshold,'float',1,11);
	$form->AddField('notes','Notes','textarea',$banding->Notes,'anything',1,2048, false, 'rows="5" style="width: 300px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()) {
			$banding->Name = $form->GetValue('name');
			$banding->Discount = $form->GetValue('discount');
			$banding->TriggerLow = $form->GetValue('low');
			$banding->TriggerHigh = $form->GetValue('high');
			$banding->Threshold = $form->GetValue('threshold');
			$banding->Notes = $form->GetValue('notes');
			$banding->Update();

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="'.$_SERVER['PHP_SELF'].'">Discount Banding</a> &gt; Update Band','Here you can edit the banding');
	$page->Display('header');

	if(!$form->Valid)
	{
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Please edit the band information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'),$form->GetHTML('name').$form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('discount'),$form->GetHTML('discount').$form->GetIcon('discount'));
	echo $webForm->AddRow($form->GetLabel('low'),$form->GetHTML('low').$form->GetIcon('low'));
	echo $webForm->AddRow($form->GetLabel('high'),$form->GetHTML('high').$form->GetIcon('high'));
	echo $webForm->AddRow($form->GetLabel('threshold'),$form->GetHTML('threshold').$form->GetIcon('threshold'));
	echo $webForm->AddRow($form->GetLabel('notes'),$form->GetHTML('notes').$form->GetIcon('notes'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s\';" />&nbsp;<input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
}

function remove()
{
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DiscountBanding.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))
	{
		$banding = new DiscountBanding();
		$banding->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view()
{
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

	$page = new Page("Discount Banding", "Here you can view your discount banding");
	$page->Display('header');

	$table = new DataTable("com");
	$table->SetSQL("SELECT * FROM discount_banding");
	$table->AddField("ID","Discount_Banding_ID");
	$table->AddField("Name","Name");
	$table->AddField("Markup Discount","Discount");
	$table->AddField("Low Trigger","Trigger_Low");
	$table->AddField("High Trigger","Trigger_High");
	$table->AddField("Threshold","Threshold");
	$table->AddLink("discount_banding.php?action=update&id=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",  "Discount_Banding_ID");
	$table->AddLink("javascript:confirmRequest('discount_banding.php?action=remove&id=%s','Are you sure you want to remove this link?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Discount_Banding_ID");
	$table->SetMaxRows(25);
	$table->Finalise();
	$table->DisplayTable();

	echo "<br />";
	$table->DisplayNavigation();

	echo '<br /><input name="add" type="submit" value="add new discount banding" class="btn" onclick="window.location.href=\'./discount_banding.php?action=add\'">';

	$page->Display('footer');
}

require_once('lib/common/app_header.php');
?>