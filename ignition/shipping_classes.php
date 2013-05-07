<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingClass.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Shipping.php');

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
} elseif($action == "copy"){
	$session->Secure(3);
	copyClass();
	exit;
} elseif($action == "changer"){
	$session->Secure(3);
	changer();
	exit();
} elseif ($action == "viewunassigned"){
	$session->Secure(2);
	viewUnassigned();
	exit();
} else {
	$session->Secure(2);
	view();
	exit;
}

function add(){
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('class', 'Shipping Class Title', 'text', '', 'alpha_numeric', 1, 100);
	$form->AddField('description', 'Shipping Class Description', 'textarea', '', 'paragraph', 1, 255, false, 'style="width:100%; height:100px;"');
	$form->AddField('default', 'Is Default Shipping Class?', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('unavailabledescription', 'Unavailable Description', 'textarea', '', 'paragraph', 1, 255, false, 'style="width:100%; height:100px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$ship = new ShippingClass;
			$ship->Name = $form->GetValue('class');
			$ship->Description = $form->GetValue('description');
			$ship->IsDefault = $form->GetValue('default');
			$ship->UnavailableDescription = $form->GetValue('unavailabledescription');
			$ship->Add();

			redirect("Location: shipping_classes.php");
		}
	}

	$page = new Page('<a href="shipping_classes.php">Shipping Classes</a> &gt; Add Shipping Class','');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add a Shipping Class.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('class'), $form->GetHTML('class') . $form->GetIcon('class'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('default'), $form->GetHTML('default') . $form->GetIcon('default'));
	echo $webForm->AddRow($form->GetLabel('unavailabledescription'), $form->GetHTML('unavailabledescription') . $form->GetIcon('unavailabledescription'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'shipping_classes.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	$ship = new ShippingClass($_REQUEST['class']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('class', 'Shipping Class ID', 'hidden', $ship->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Shipping Class Title', 'text', $ship->Name, 'alpha_numeric', 1, 100);
	$form->AddField('description', 'Shipping Class Description', 'textarea', $ship->Description, 'paragraph', 1, 255, false, 'style="width:100%; height:100px;"');
	$form->AddField('default', 'Is Default Shipping Class?', 'checkbox', $ship->IsDefault, 'boolean', 1, 1, false);
	$form->AddField('unavailabledescription', 'Unavailable Description', 'textarea', $ship->UnavailableDescription, 'paragraph', 1, 255, false, 'style="width:100%; height:100px;"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$ship->Name = $form->GetValue('title');
			$ship->Description = $form->GetValue('description');
			$ship->IsDefault = $form->GetValue('default');
			$ship->UnavailableDescription = $form->GetValue('unavailabledescription');
			$ship->Update();

			redirect("Location: shipping_classes.php");
		}
	}
	$page = new Page('<a href="shipping_classes.php">Shipping Classes</a> &gt; Update Shipping Class','');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Update a Shipping Class.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('class');
	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('default'), $form->GetHTML('default') . $form->GetIcon('default'));
	echo $webForm->AddRow($form->GetLabel('unavailabledescription'), $form->GetHTML('unavailabledescription') . $form->GetIcon('unavailabledescription'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'shipping_classes.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ShippingClass.php');
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['class'])){
		$ship = new ShippingClass;
		$ship->Delete($_REQUEST['class']);
	}
	redirect("Location: shipping_classes.php");
}

function copyClass(){
	$class = new ShippingClass($_REQUEST['class']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'copy', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('class', 'Shipping Class ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('geozone', 'Geo Zone', 'selectmultiple', '', 'numeric_unsigned', 1, 11);

	$data = new DataQuery(sprintf("SELECT g.Geozone_ID, g.Geozone_Title FROM shipping AS s INNER JOIN geozone AS g ON g.Geozone_ID=s.Geozone_ID WHERE s.Shipping_Class_ID=%d GROUP BY g.Geozone_ID ORDER BY g.Geozone_Title ASC", mysql_real_escape_string($class->ID)));
	while($data->Row){
		$form->AddOption('geozone', $data->Row['Geozone_ID'], $data->Row['Geozone_Title']);

		$data->Next();
	}
	$data->Disconnect();
	
	$form->AddField('shipping', 'Shipping Class', 'selectmultiple', '', 'numeric_unsigned', 1, 11);

	$data = new DataQuery(sprintf("SELECT Shipping_Class_ID, Shipping_Class_Title FROM shipping_class WHERE Shipping_Class_ID<>%d ORDER BY Shipping_Class_Title ASC", mysql_real_escape_string($class->ID)));
	while($data->Row){
		$form->AddOption('shipping', $data->Row['Shipping_Class_ID'], $data->Row['Shipping_Class_Title']);

		$data->Next();
	}
	$data->Disconnect();
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			foreach($_REQUEST['shipping'] as $shippingId) {
				$shippingClass = new ShippingClass($shippingId);
				
				foreach($_REQUEST['geozone'] as $geozoneId) {
					$data = new DataQuery(sprintf("SELECT Shipping_ID FROM shipping WHERE Shipping_Class_ID=%d AND Geozone_ID=%d", mysql_real_escape_string($class->ID), mysql_real_escape_string($geozoneId)));
					while($data->Row) {
						$shipping = new Shipping($data->Row['Shipping_ID']);
						$shipping->ClassID = $shippingClass->ID;
						$shipping->Add();
						
						$data->Next();
					}
					$data->Disconnect();
				}	
			}

			redirect("Location: shipping_classes.php");
		}
	}
	
	$page = new Page('<a href="shipping_classes.php">Shipping Classes</a> &gt; Copy Shipping Class Settings','');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Copy Shipping Class Settings.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('class');
	
	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('geozone'), $form->GetHTML('geozone') . $form->GetIcon('geozone'));
	echo $webForm->AddRow($form->GetLabel('shipping'), $form->GetHTML('shipping') . $form->GetIcon('shipping'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'shipping_classes.php\';"> <input type="submit" name="copy" value="copy" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('Shipping Class Settings','You can create many different Shipping Classes, which can be used on any product.');
	$page->Display('header');

	$table = new DataTable('shipClass');
	$table->SetSQL("select * from shipping_class");
	$table->AddField('ID#', 'Shipping_Class_ID', 'right');
	$table->AddField('Shipping Class', 'Shipping_Class_Title', 'left');
	$table->AddField('Default', 'Is_Default', 'left');
	$table->AddLink("shipping_classes.php?action=copy&class=%s",
							"<img src=\"./images/icon_pages_1.gif\" alt=\"Copy Settings\" border=\"0\">",
							"Shipping_Class_ID");
	$table->AddLink("shipping.php?class=%s",
							"<img src=\"./images/folderopen.gif\" alt=\"View Options for this Class\" border=\"0\">",
							"Shipping_Class_ID");
	$table->AddLink("shipping_classes.php?action=update&class=%s",
							"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",
							"Shipping_Class_ID");
	$table->AddLink("javascript:confirmRequest('shipping_classes.php?action=remove&confirm=true&class=%s','Are you sure you want to remove this Shipping Class? Note: this operation will remove all related information and products using this shipping class will no longer have one.');",
							"<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">",
							"Shipping_Class_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Shipping_Class_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo "<br>";
	echo '<input type="button" name="add" value="add a new class" class="btn" onclick="window.location.href=\'shipping_classes.php?action=add\'">';
	echo ' <input type="button" name="changer" value="global shipping changer" class="btn" onclick="window.location.href=\'shipping_classes.php?action=changer\'">';
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function changer(){

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'changer', 'alpha', 7, 7);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('classFrom', 'Shipping Class ID', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('classFrom', '0', 'All Products with Unassigned Shipping');
	$form->AddField('classTo', 'Shipping Class ID', 'select', '', 'numeric_unsigned', 1, 11);
	$getClasses = new DataQuery("select * from shipping_class order by Shipping_Class_Title");
	while($getClasses->Row){
		$form->AddOption('classFrom', $getClasses->Row['Shipping_Class_ID'], $getClasses->Row['Shipping_Class_Title']);
		$form->AddOption('classTo', $getClasses->Row['Shipping_Class_ID'], $getClasses->Row['Shipping_Class_Title']);
		$getClasses->Next();
	}
	$getClasses->Disconnect();

	$updated = 0;
	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$data = new DataQuery(sprintf("update product set Shipping_Class_ID=%d where Shipping_Class_ID=%d",
											mysql_real_escape_string($form->GetValue('classTo')),
											mysql_real_escape_string($form->GetValue('classFrom'))));
			$updated = $data->AffectedRows;
		}
	}

	$page = new Page('<a href="shipping_classes.php">Shipping Class Settings</a> &gt; Global Changes',
	'Do you need to swap one shipping class for another amongst all products? Or, do you need set all products with unassigned shipping classes to a known class? You can do it here.');
	$page->Display('header');
	if(!empty($updated)){
		echo "<p class=\"alert\">" . $updated . " Products were updated</p>";
	}
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	echo $form->Open();
	echo $form->GetHtml('action');
	echo $form->GetHtml('confirm');
	echo "Change ";
	echo $form->GetHtml('classFrom');
	echo " to ";
	echo $form->GetHtml('classTo');
	echo '<p><a href="./shipping_classes.php?action=viewunassigned">Or, View all Products with Unassigned Shipping Classes</a></p>';
	echo '<input type="submit" name="submit" value="submit" class="btn" />';
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function viewUnassigned(){
	$page = new Page('<a href="shipping_classes.php">Shipping Class Settings</a> &gt; <a href="shipping_classes.php?action=changer">Global Changes</a> &gt; View Unassigned Products',
	'Find out which of your products do not have a Shipping Class assigned to them.');
	$page->Display('header');

	$table = new DataTable('shipClass');
	$table->SetSQL("select * from product where Shipping_Class_ID=0 or Shipping_Class_ID Is Null");
	$table->AddField('ID#', 'Product_ID', 'right');
	$table->AddField('Product', 'Product_Title', 'left');
	$table->AddLink("product_commerce.php?action=update&pid=%s",
							"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",
							"Product_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	echo "<br>";
	echo "<br>";


	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>