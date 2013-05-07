<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductComponent.php');

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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['cid'])){
		$component = new ProductComponent;
		$component->Delete($_REQUEST['cid']);
	}
	redirect(sprintf("Location: product_component.php?pid=%d", $_REQUEST['pid']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Product Components', $_REQUEST['pid']),'The more information you supply the better your system will become');

	$page->Display('header');
	$sql = sprintf("SELECT pc.Product_Component_ID, p.Product_ID, p.Product_Title, p.SKU, pc.Component_Quantity, pc.Is_Active
					FROM product_components as pc
					inner join product as p
					on pc.Product_ID=p.Product_ID
					where pc.Component_Of_Product_ID=%d", $_REQUEST['pid']);
					
	$table = new DataTable("com");
	$table->SetSQL($sql);
	$table->AddField('ID#', 'Product_ID', 'left');
	$table->AddField('SKU#', 'SKU', 'left');
	$table->AddField('Title', 'Product_Title', 'left');
	$table->AddField('Quantity', 'Component_Quantity', 'center');
	$table->AddField('Active', 'Is_Active', 'center');
	$table->AddLink("product_component.php?action=update&cid=%s",
							"<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">",
							"Product_Component_ID");
	$table->AddLink("javascript:confirmRequest('product_component.php?action=remove&confirm=true&cid=%s','Are you sure you want to remove this component product? Note: this operation removes the relationship only.');",
							"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
							"Product_Component_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new product component" class="btn" onclick="window.location.href=\'product_component.php?action=add&pid=%d\'">', $_REQUEST['pid']);
	
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
	$form->AddField('product', 'Product ID', 'text', '', 'numeric_unsigned', 1, 11);
	$form->AddField('quantity', 'Quantity', 'text', '1', 'numeric_unsigned', 1, 9, true, 'style="width:50px;"');
	$form->AddField('active', 'Active Component', 'checkbox', 'Y', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$component = new ProductComponent;
			$component->Parent->ID = $form->GetValue('pid');
			$component->Product->ID = $form->GetValue('product');
			$component->Quantity = $form->GetValue('quantity');
			$component->IsActive = $form->GetValue('active');
			$component->Add();
			
			redirect(sprintf("Location: product_component.php?pid=%d", $form->GetValue('pid')));
		}
	}
	
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_component.php?pid=%s">Product Component</a> &gt; Add Product Component', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow("Add a Product Component.");
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $window->Open();
	echo $window->AddHeader('Please choose a product using the search tool and enter the quantity to required.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('product'), $form->GetHTML('product'));
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow("&nbsp;", '<input type="submit" name="add" value="add" class="btn" />');
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$component = new ProductComponent($_REQUEST['cid']);
	$component->Product->Get();

	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('cid', 'Component ID', 'hidden', $component->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('product', 'Product ID', 'hidden', $component->Product->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Product', 'text', $component->Product->Name, 'paragraph', 1, 60, false, 'onFocus="this.Blur();"');
	$form->AddField('quantity', 'Quantity', 'text', $component->Quantity, 'numeric_unsigned', 1, 9, true, 'style="width:50px;"');
	$form->AddField('active', 'Active Component', 'checkbox', $component->IsActive, 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$component->Product->ID = $form->GetValue('product');
			$component->Quantity = $form->GetValue('quantity');
			$component->IsActive = $form->GetValue('active');
			$component->Update();
			
			redirect(sprintf("Location: product_component.php?pid=%d", $form->GetValue('pid')));
		}
	}
	
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_component.php?pid=%s">Product Component</a> &gt; Update Product Component', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow("Update a Product Component.");
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $form->GetHTML('cid');
	echo $form->GetHTML('product');
	echo $window->Open();
	echo $window->AddHeader('Please choose a product using the search tool and enter the quantity to required.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_search.php?serve=pop\', 500, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('name') . $temp_1, $form->GetHTML('name'));
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity') . $form->GetIcon('quantity'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow("&nbsp;", '<input type="submit" name="update" value="update" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}