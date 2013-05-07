<?php
/*
	product_option_group.php
	Version 1.0
	
	Ignition, eBusiness Solution
	http://www.deveus.com
	
	Copyright (c) Deveus Software, 2004
	All Rights Reserved.
	
	Notes:
*/
	require_once('lib/common/app_header.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductOptionGroup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	
	if($action == 'remove'){
		$session->Secure(3);
		remove();
		exit;
	} elseif($action == 'add'){
		$session->Secure(3);
		add();
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
		if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['gid'])){
			$group = new ProductOptionGroup;
			$group->Delete($_REQUEST['gid']);
		} 
		redirect(sprintf("Location: product_option_group.php?pid=%d", $_REQUEST['pid']));
		exit;
	}
	
	function view(){
		
		$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Product Option Groups', $_REQUEST['pid']),'You can add one or more option groups.');
		
		$page->Display('header');
		$sql = sprintf("SELECT * from product_option_groups where Product_ID=%d", $_REQUEST['pid']);
		$table = new DataTable("com");
		$table->SetSQL($sql);
		$table->AddField('Group Name', 'Group_Title', 'left');
		$table->AddField('Group Type Code', 'Group_Type', 'center');
		$table->AddField('Active', 'Is_Active', 'center');
		$table->AddLink("product_option.php?gid=%s", 
								"<img src=\"./images/folderopen.gif\" alt=\"View Options for this Group\" border=\"0\">", 
								"Product_Option_Group_ID");
		$table->AddLink("product_option_group.php?action=update&gid=%s", 
								"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Group Settings\" border=\"0\">", 
								"Product_Option_Group_ID");
		$table->AddLink("javascript:confirmRequest('product_option_group.php?action=remove&confirm=true&gid=%s','Are you sure you want to remove this option group? Note: this operation will remove all related options.');", 
								"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", 
								"Product_Option_Group_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Group_Title");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		echo sprintf('<input type="button" name="add" value="add a new group" class="btn" onclick="window.location.href=\'product_option_group.php?action=add&pid=%d\'">', $_REQUEST['pid']);
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	function add(){
		
		
		
		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
		$form->AddField('title', 'Group Title', 'text', '', 'alpha_numeric', 1, 60);
		$form->AddField('description', 'Group Description', 'textarea', '', 'paragraph', 1, 255, false, 'style="width:100%, height:100px"');
		$form->AddField('active', 'Is an Active Group?', 'checkbox', 'Y', 'boolean', 1, 1, false);
		
		$form->AddField('type', 'Group Type', 'radio', 'S', 'alpha', 1, 1, false);
		$form->AddOption('type','S', 'Single Additional Product');
		$form->AddOption('type','M', 'Multiple Additional Products');
		
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$group = new ProductOptionGroup;
				$group->Name = $form->GetValue('title');
				$group->Description = $form->GetValue('description');
				$group->ProductID = $form->GetValue('pid');
				$group->Type = $form->GetValue('type');
				$group->IsActive = $form->GetValue('active');
				$group->Add();

				 redirect(sprintf("Location: product_option_group.php?pid=%d", $form->GetValue('pid')));
				exit;
			}
		}
		$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_option_group.php?pid=%s">Product Option Groups</a> &gt; Add Option Group', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
		
		$page->Display('header');
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		$window = new StandardWindow("Add a Product Option Group.");
		$webForm = new StandardForm;
		echo $form->Open();
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('action');
		echo $form->GetHTML('pid');
		echo $window->Open();
		echo $window->AddHeader('Required fields are marked with an asterisk (*)');
		echo $window->OpenContent();
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
		echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
		
		echo sprintf("<tr><td align=\"right\">%s %s</td><td>%s %s<br />Only one option within the group can be purchased in ADDITION to this Primary Product profile and/or other option groups. We recommened that existing products be used for options other for &quot;Not Required&quot; options. It is important to use existing products where possible to maintain stock control over your inventory.</td></tr>",
						$form->GetLabel('type'),
						$form->GetIcon('type'),
						$form->GetHTML('type', 1),
						$form->GetLabel('type',1)
					);
		echo sprintf("<tr><td>&nbsp;</td><td>%s %s<br />More than one option within the group may be purchased in ADDITION to this Primary Product profile. Only existing products should be used as options.</td></tr>",
						$form->GetHTML('type', 2),
						$form->GetLabel('type',2)
					);
		
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_option_group.php?pid=%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		echo "<br>";
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
	
	
	function update(){
		
		$group = new ProductOptionGroup($_REQUEST['gid']);
		
		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('gid', 'Product Option Group ID', 'hidden', $group->ID, 'numeric_unsigned', 1, 11);
		$form->AddField('pid', 'Product ID', 'hidden', $group->ProductID, 'numeric_unsigned', 1, 11);
		$form->AddField('title', 'Group Title', 'text', $group->Name, 'alpha_numeric', 1, 60);
		$form->AddField('description', 'Group Description', 'textarea', $group->Description, 'paragraph', 1, 255, false, 'style="width:100%, height:100px"');
		$form->AddField('active', 'Is an Active Group?', 'checkbox', $group->IsActive, 'boolean', 1, 1, false);
		
		$form->AddField('type', 'Group Type', 'radio', $group->Type, 'alpha', 1, 1, false);
		$form->AddOption('type','S', 'Single Additional Product');
		$form->AddOption('type','M', 'Multiple Additional Products');
		
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				// Hurrah! Create a new entry.
				$group->ProductID = $form->GetValue('pid');
				$group->Name = $form->GetValue('title');
				$group->Description = $form->GetValue('description');
				$group->Type = $form->GetValue('type');
				$group->IsActive = $form->GetValue('active');
				$group->Update();
				
				 redirect(sprintf("Location: product_option_group.php?pid=%d", $form->GetValue('pid')));
				exit;
			}
		}
		$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_option_group.php?pid=%s">Product Option Groups</a> &gt; Update Option Group', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
		
		$page->Display('header');
		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		$window = new StandardWindow("Update a Product Option Group.");
		$webForm = new StandardForm;
		echo $form->Open();
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('action');
		echo $form->GetHTML('gid');
		echo $form->GetHTML('pid');
		echo $window->Open();
		echo $window->AddHeader('Required fields are marked with an asterisk (*)');
		echo $window->OpenContent();
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
		echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
		
		echo sprintf("<tr><td align=\"right\">%s %s</td><td>%s %s<br />Only one option within the group can be purchased in ADDITION to this Primary Product profile and/or other option groups. We recommened that existing products be used for options other for &quot;Not Required&quot; options. It is important to use existing products where possible to maintain stock control over your inventory.</td></tr>",
						$form->GetLabel('type'),
						$form->GetIcon('type'),
						$form->GetHTML('type', 1),
						$form->GetLabel('type',1)
					);
		echo sprintf("<tr><td>&nbsp;</td><td>%s %s<br />More than one option within the group may be purchased in ADDITION to this Primary Product profile. Only existing products should be used as options.</td></tr>",
						$form->GetHTML('type', 2),
						$form->GetLabel('type',2)
					);
		
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_option_group.php?pid=%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		echo "<br>";
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>
