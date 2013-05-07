<?php
/*
	product_option.php
	Version 1.0

	Ignition, eBusiness Solution
	http://www.deveus.com

	Copyright (c) Deveus Software, 2004
	All Rights Reserved.

	Notes:
*/
	require_once('lib/common/app_header.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductOption.php');

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
		if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['oid'])){
			$option = new ProductOption;
			$option->Delete($_REQUEST['oid']);
		}
		redirect(sprintf("Location: product_option.php?gid=%d&pid=%d", $_REQUEST['gid'], $_REQUEST['pid']));
		exit;
	}

	function view(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

		$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_option_group.php?pid=%s">Product Option Groups</a> &gt; Group Options', $_REQUEST['pid'], $_REQUEST['pid']),'You can add one or more options to this group. NOTE: Options using an existing product do not show the current price of the associated product in the table below.');

		$page->Display('header');
		$sql = sprintf("SELECT * from product_options where Product_Option_Group_ID=%d", $_REQUEST['gid']);
		$table = new DataTable("com");
		$table->SetSQL($sql);
		$table->AddField('Option Title', 'Option_Title', 'left');
		$table->AddField('Use Product', 'Use_Existing_Product_ID', 'left');
		$table->AddField('Price', 'Option_Price', 'right');
		$table->AddField('Active', 'Is_Active', 'center');
		$table->AddField('Quantity', 'Option_Quantity', 'center');
		$table->AddField('Selected', 'Is_Selected', 'center');
		$table->AddLink("product_option.php?action=update&oid=%s",
								"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Option Settings\" border=\"0\">",
								"Product_Option_ID");
		$table->AddLink("javascript:confirmRequest('product_option.php?action=remove&confirm=true&oid=%s','Are you sure you want to remove this option?');",
								"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
								"Product_Option_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Option_Title");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		echo "<br>";
		echo sprintf('<input type="button" name="add" value="add a new option" class="btn" onclick="window.location.href=\'product_option.php?action=add&pid=%d&gid=%d\'">', $_REQUEST['pid'], $_REQUEST['gid']);
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}

	function add(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
		$form->AddField('gid', 'Group ID', 'hidden', $_REQUEST['gid'], 'numeric_unsigned', 1, 11);
		$form->AddField('product', 'Use Product ID#', 'text', '', 'numeric_unsigned', 1, 11, false, 'onFocus="this.blur();" style="width:50px"');
		$form->AddField('name', 'Option Title', 'text', '', 'paragraph', 1, 60);
		$form->AddField('price', 'Price', 'text', '', 'float', 1, 11, false);
		$form->AddField('active', 'Active Option?', 'checkbox', 'Y', 'boolean', 1, 1, false);
		$form->AddField('quantity', 'Quantity', 'text', '1', 'numeric_unsigned', 1, 9, true, 'style="width:50px;"');
		$form->AddField('selected', 'Selected by Default', 'checkbox', 'Y', 'boolean', 1, 1, false);

		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				$option = new ProductOption;
				$option->Name = $form->GetValue('name');
				$option->Price = $form->GetValue('price');
				$option->IsActive = $form->GetValue('active');
				$option->ParentGroupID = $form->GetValue('gid');
				$option->IsSelected = $form->GetValue('selected');
				$option->Quantity = $form->GetValue('quantity');
				$option->UseProductID = $form->GetValue('product');
				$option->Add();

				redirect(sprintf("Location: product_option.php?gid=%d&pid=%d", $form->GetValue('gid'), $form->GetValue('pid')));
			}
		}

		$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_option_group.php?pid=%s">Product Option Groups</a> &gt; <a href="product_option.php?gid=%s&pid=%s">Group Options</a> &gt; Add Option to Group', $_REQUEST['pid'], $_REQUEST['pid'], $_REQUEST['gid'], $_REQUEST['pid']),'Click the search icon to use an existing product as an option. This will show up as an ID number and title. NOTE: if you are using an existing product as your option you do not need to set the price field. The price you set will be ignored if you do.');
		$page->Display('header');

		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		$window = new StandardWindow("Add a Product Option.");
		$webForm = new StandardForm;
		echo $form->Open();
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('action');
		echo $form->GetHTML('pid');
		echo $form->GetHTML('gid');
		echo $window->Open();
		echo $window->AddHeader('Required fields are marked with an asterisk (*)');
		echo $window->OpenContent();
		echo $webForm->Open();
		$temp_1 = '<a href="javascript:popUrl(\'product_search.php?serve=pop\', 500, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
		echo $webForm->AddRow($form->GetLabel('product') . $temp_1, $form->GetHTML('product') . $form->GetIcon('product') . "<input type=\"button\" name=\"clear\" value=\"clear\" onclick=\"clearField('product')\" />");
		echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
		echo $webForm->AddRow($form->GetLabel('price'), $form->GetHTML('price') . $form->GetIcon('price'));
		echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
		echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
		echo $webForm->AddRow($form->GetLabel('selected'), $form->GetHTML('selected') . $form->GetIcon('selected'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_option.php?gid=%s&pid=%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_REQUEST['gid'], $_REQUEST['pid'], $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		echo "<br>";
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}

	function update(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

		$form = new Form($_SERVER['PHP_SELF']);
		$option = new ProductOption($_REQUEST['oid']);

		$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
		$form->AddField('gid', 'Group ID', 'hidden', $_REQUEST['gid'], 'numeric_unsigned', 1, 11);
		$form->AddField('oid', 'Option ID', 'hidden', $_REQUEST['oid'], 'numeric_unsigned', 1, 11);
		$form->AddField('product', 'Use Product ID#', 'text', $option->UseProductID, 'numeric_unsigned', 1, 11, false, 'onFocus="this.blur();" style="width:50px"');
		$form->AddField('name', 'Option Title', 'text', $option->Name, 'paragraph', 1, 60);
		$form->AddField('price', 'Price', 'text', $option->Price, 'float', 1, 11, false);
		$form->AddField('active', 'Active Option?', 'checkbox', $option->IsActive, 'boolean', 1, 1, false);
		$form->AddField('quantity', 'Quantity', 'text', $option->Quantity, 'numeric_unsigned', 1, 9, true, 'style="width:50px;"');
		$form->AddField('selected', 'Selected by Default?', 'checkbox', $option->IsSelected, 'boolean', 1, 1, false);

		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			if($form->Validate()){
				$option->Name = $form->GetValue('name');
				$option->Price = $form->GetValue('price');
				$option->IsActive = $form->GetValue('active');
				$option->IsSelected = $form->GetValue('selected');
				$option->Quantity = $form->GetValue('quantity');
				$option->UseProductID = $form->GetValue('product');
				$option->Update();

				redirect(sprintf("Location: product_option.php?gid=%d&pid=%d", $form->GetValue('gid'), $form->GetValue('pid')));
			}
		}

		$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_option_group.php?pid=%s">Product Option Groups</a> &gt; <a href="product_option.php?gid=%s&pid=%s">Group Options</a> &gt; Update Option', $_REQUEST['pid'], $_REQUEST['pid'], $_REQUEST['gid'], $_REQUEST['pid']),'Click the search icon to use an existing product as an option. This will show up as an ID number and title. NOTE: if you are using an existing product as your option you do not need to set the price field. The price you set will be ignored if you do.');
		$page->Display('header');

		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		$window = new StandardWindow("Update a Product Option.");
		$webForm = new StandardForm;
		echo $form->Open();
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('action');
		echo $form->GetHTML('pid');
		echo $form->GetHTML('gid');
		echo $form->GetHTML('oid');
		echo $window->Open();
		echo $window->AddHeader('Required fields are marked with an asterisk (*)');
		echo $window->OpenContent();
		echo $webForm->Open();
		$temp_1 = '<a href="javascript:popUrl(\'product_search.php?serve=pop\', 500, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
		echo $webForm->AddRow($form->GetLabel('product') . $temp_1, $form->GetHTML('product') . $form->GetIcon('product') . "<input type=\"button\" name=\"clear\" value=\"clear\" onclick=\"clearField('product')\" />");
		echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
		echo $webForm->AddRow($form->GetLabel('price'), $form->GetHTML('price') . $form->GetIcon('price'));
		echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
		echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
		echo $webForm->AddRow($form->GetLabel('selected'), $form->GetHTML('selected') . $form->GetIcon('selected'));
		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_option.php?gid=%s&pid=%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_REQUEST['gid'], $_REQUEST['pid'], $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();
		echo "<br>";
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>
