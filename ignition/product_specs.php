<?php
require_once('lib/common/app_header.php');

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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');

	if(isset($_REQUEST['spec']) && is_numeric($_REQUEST['spec'])){
		$spec = new ProductSpec();
		$spec->Delete($_REQUEST['spec']);
	}

	redirect(sprintf("Location: product_specs.php?pid=%d", $_REQUEST['pid']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$product = new Product();
	$product->Get($_REQUEST['pid']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('group', 'Group', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('group', '', '');
	$form->AddField('value', 'Value', 'text', '', 'paragraph', 1, 255);
	$form->AddField('existinggroup', 'Existing Group', 'select', '', 'numeric_unsigned', 1, 11, true, 'onchange="populateSpecs(this);"');
	$form->AddOption('existinggroup', '', '');
	$form->AddField('existingvalue', 'Existing Value', 'select', '', 'numeric_unsigned', 1, 11, true);
	$form->AddOption('existingvalue', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_group ORDER BY Reference ASC"));
	while($data->Row) {
		$form->AddOption('group', $data->Row['Group_ID'], $data->Row['Reference']);
		$form->AddOption('existinggroup', $data->Row['Group_ID'], $data->Row['Reference']);

		$data->Next();
	}
	$data->Disconnect();

	if($form->GetValue('existinggroup') > 0) {
		$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=%d ORDER BY Value ASC", mysql_real_escape_string($form->GetValue('existinggroup'))));
		while($data->Row) {
			$form->AddOption('existingvalue', $data->Row['Value_ID'], $data->Row['Value']);
			$data->Next();
		}
		$data->Disconnect();
	}
	
	$form->AddField('isprimary', 'Is Primary', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(isset($_REQUEST['addnew'])){
			$form->Validate('group');
			$form->Validate('value');

			if($form->Valid){
				$valueID = 0;

				$data = new DataQuery(sprintf("SELECT Value_ID FROM product_specification_group AS sg INNER JOIN product_specification_value AS sv ON sv.Group_ID=sg.Group_ID WHERE sg.Group_ID=%d AND sv.Value LIKE '%s' LIMIT 0, 1", mysql_real_escape_string($form->GetValue('group')), mysql_real_escape_string($form->GetValue('value'))));
				if($data->TotalRows > 0) {
					$valueID = $data->Row['Value_ID'];
				}
				$data->Disconnect();

				if($valueID == 0) {
					$value = new ProductSpecValue();
					$value->Group->ID = $form->GetValue('group');
					$value->Value = $form->GetValue('value');
					$value->Add();

					$valueID = $value->ID;
				}

				$product->AddSpec($valueID, $form->GetValue('isprimary'));

				redirect(sprintf("Location: %s?pid=%d", $_SERVER['PHP_SELF'], $form->GetValue('pid')));
			}
		} elseif(isset($_REQUEST['addexisting'])){
			$form->Validate('existinggroup');
			$form->Validate('existingvalue');

			if($form->Valid){
				$product->AddSpec($form->GetValue('existingvalue'), $form->GetValue('isprimary'));

				redirect(sprintf("Location: %s?pid=%d", $_SERVER['PHP_SELF'], $form->GetValue('pid')));
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var req = new HttpRequest();
		var valueField = null;

		var respHandler = function(resp) {
			var values = resp.split(\'{br}{br}\');
			var value = null;
			var count = 0;

			valueField.length = 0;
			valueField.options[count++] = new Option(\'\', \'\');

			for(var i = 0; i < values.length; i++) {
				value = values[i].split(\'{br}\');

				if(value[0] && value[1]) {
					valueField.options[count++] = new Option(value[1], value[0]);
				}
			}

			valueField.removeAttribute(\'disabled\');
		}

		var errorHandler = function(resp) {
			valueField.removeAttribute(\'disabled\');

			alert("An error occurred whilst populating the specification values.");
		}

		var populateSpecs = function(formElement) {
			valueField = document.getElementById(\'existingvalue\');

			if(valueField) {
				valueField.length = 0;
				valueField.options[0] = new Option(\'Loading...\', \'\');
				valueField.setAttribute(\'disabled\', \'disabled\');

				req.get(\'lib/util/getSpecValuesByGroup.php\', \'id=\' + formElement.value);
			} else {
				alert("An error occurred whilst populating the specification values.");
			}
		}

		req.setRetryStatus(HttpRequest.RETRY_ON_TIMEOUT);
		req.setRetryAttempts(2);
		req.setCaching(false);
		req.setTimeout(10000);
		req.setDelay(0);
		req.setHandlerResponse(respHandler);
		req.setHandlerError(errorHandler);
		</script>');

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_specs.php?pid=%s">Product Specifications</a> &gt; Add Product Specification', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->AddToHead('<script language="javascript" src="js/HttpRequest.js" type="text/javascript"></script>');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Add Product Specification.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('pid');

	echo $window->Open();
	echo $window->AddHeader('Mark as primary specification');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('isprimary'), $form->GetHTML('isprimary') . $form->GetIcon('isprimary'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Add existing specification');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('existinggroup'), $form->GetHTML('existinggroup') . $form->GetIcon('existinggroup'));
	echo $webForm->AddRow($form->GetLabel('existingvalue'), $form->GetHTML('existingvalue') . $form->GetIcon('existingvalue'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_specs.php?pid=%s\';"> <input type="submit" name="addexisting" value="add" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Add new specification');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('group'), $form->GetHTML('group') . $form->GetIcon('group'));
	echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $form->GetIcon('value'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_specs.php?pid=%s\';"> <input type="submit" name="addnew" value="add" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$spec = new ProductSpec($_REQUEST['spec']);
	$spec->Value->Get();
	$spec->Value->Group->Get();

	$product = new Product();
	$product->Get($_REQUEST['pid']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('spec', 'Specification ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('pid', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('group', 'Group', 'select', $spec->Value->Group->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('group', '', '');
	$form->AddField('value', 'Value', 'text', '', 'paragraph', 1, 255);
	$form->AddField('existinggroup', 'Existing Group', 'select', $spec->Value->Group->ID, 'numeric_unsigned', 1, 11, true, 'onchange="populateSpecs(this);"');
	$form->AddOption('existinggroup', '', '');
	$form->AddField('existingvalue', 'Existing Value', 'select', $spec->Value->ID, 'numeric_unsigned', 1, 11, true);
	$form->AddOption('existingvalue', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_group ORDER BY Reference ASC"));
	while($data->Row) {
		$form->AddOption('group', $data->Row['Group_ID'], $data->Row['Reference']);
		$form->AddOption('existinggroup', $data->Row['Group_ID'], $data->Row['Reference']);

		$data->Next();
	}
	$data->Disconnect();

	if($form->GetValue('existinggroup') > 0) {
		$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=%d ORDER BY Value ASC", mysql_real_escape_string($form->GetValue('existinggroup'))));
		while($data->Row) {
			$form->AddOption('existingvalue', $data->Row['Value_ID'], $data->Row['Value']);
			$data->Next();
		}
		$data->Disconnect();
	}
	
	$form->AddField('isprimary', 'Is Primary', 'checkbox', $spec->IsPrimary, 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if(isset($_REQUEST['addnew'])){
			$form->Validate('group');
			$form->Validate('value');

			if($form->Valid){
				$valueID = 0;

				$data = new DataQuery(sprintf("SELECT Value_ID FROM product_specification_group AS sg INNER JOIN product_specification_value AS sv ON sv.Group_ID=sg.Group_ID WHERE sg.Group_ID=%d AND sv.Value LIKE '%s' LIMIT 0, 1", mysql_real_escape_string($form->GetValue('group')), mysql_real_escape_string($form->GetValue('value'))));
				if($data->TotalRows > 0) {
					$valueID = $data->Row['Value_ID'];
				}
				$data->Disconnect();

				if($valueID == 0) {
					$value = new ProductSpecValue();
					$value->Group->ID = $form->GetValue('group');
					$value->Value = $form->GetValue('value');
					$value->Add();

					$valueID = $value->ID;
				}

				if($form->GetValue('existinggroup') != $spec->Value->Group->ID) {
					$spec->Delete();

					$product->AddSpec($valueID, $form->GetValue('isprimary'));
				} else {
					new DataQuery(sprintf("UPDATE product_specification SET Value_ID=%d, Is_Primary='%s' WHERE Specification_ID=%d", mysql_real_escape_string($valueID), mysql_real_escape_string($form->GetValue('isprimary')), mysql_real_escape_string($spec->ID)));

					$product->UpdateSpecCache();
				}

				redirect(sprintf("Location: %s?pid=%d", $_SERVER['PHP_SELF'], $form->GetValue('pid')));
			}
		} elseif(isset($_REQUEST['updateexisting'])){
			$form->Validate('existinggroup');
			$form->Validate('existingvalue');

			if($form->Valid){
				if($form->GetValue('existinggroup') != $spec->Value->Group->ID) {
					$spec->Delete();

					$product->AddSpec($form->GetValue('existingvalue'), $form->GetValue('isprimary'));
				} else {
					new DataQuery(sprintf("UPDATE product_specification SET Value_ID=%d, Is_Primary='%s' WHERE Specification_ID=%d", mysql_real_escape_string($form->GetValue('existingvalue')), mysql_real_escape_string($form->GetValue('isprimary')), mysql_real_escape_string($spec->ID)));

					$product->UpdateSpecCache();
				}

				redirect(sprintf("Location: %s?pid=%d", $_SERVER['PHP_SELF'], $form->GetValue('pid')));
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var req = new HttpRequest();
		var valueField = null;

		var respHandler = function(resp) {
			var values = resp.split(\'{br}{br}\');
			var value = null;
			var count = 0;

			valueField.length = 0;
			valueField.options[count++] = new Option(\'\', \'\');

			for(var i = 0; i < values.length; i++) {
				value = values[i].split(\'{br}\');

				if(value[0] && value[1]) {
					valueField.options[count++] = new Option(value[1], value[0]);
				}
			}

			valueField.removeAttribute(\'disabled\');
		}

		var errorHandler = function(resp) {
			valueField.removeAttribute(\'disabled\');

			alert("An error occurred whilst populating the specification values.");
		}

		var populateSpecs = function(formElement) {
			valueField = document.getElementById(\'existingvalue\');

			if(valueField) {
				valueField.length = 0;
				valueField.options[0] = new Option(\'Loading...\', \'\');
				valueField.setAttribute(\'disabled\', \'disabled\');

				req.get(\'lib/util/getSpecValuesByGroup.php\', \'id=\' + formElement.value);
			} else {
				alert("An error occurred whilst populating the specification values.");
			}
		}

		req.setRetryStatus(HttpRequest.RETRY_ON_TIMEOUT);
		req.setRetryAttempts(2);
		req.setCaching(false);
		req.setTimeout(10000);
		req.setDelay(0);
		req.setHandlerResponse(respHandler);
		req.setHandlerError(errorHandler);
		</script>');

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_specs.php?pid=%s">Product Specifications</a> &gt; Update Product Specification', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->AddToHead('<script language="javascript" src="js/HttpRequest.js" type="text/javascript"></script>');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Update Product Specification.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('pid');
	echo $form->GetHTML('spec');

	echo $window->Open();
	echo $window->AddHeader('Mark as primary specification');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('isprimary'), $form->GetHTML('isprimary') . $form->GetIcon('isprimary'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Update existing specification');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('existinggroup'), $form->GetHTML('existinggroup') . $form->GetIcon('existinggroup'));
	echo $webForm->AddRow($form->GetLabel('existingvalue'), $form->GetHTML('existingvalue') . $form->GetIcon('existingvalue'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_specs.php?pid=%s\';"> <input type="submit" name="updateexisting" value="update" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->AddHeader('Add new specification');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('group'), $form->GetHTML('group') . $form->GetIcon('group'));
	echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $form->GetIcon('value'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_specs.php?pid=%s\';"> <input type="submit" name="addnew" value="add" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Product Specifications', $_REQUEST['pid']),'Your customers appreciate information. Your customer may base their decision to purchase on the information you provide. Try to make your life and their easier by giving them the information they need within the product specifications. You can add as many as you like.');
	$page->Display('header');

	$table = new DataTable('specs');
	$table->SetSQL(sprintf("SELECT s.Specification_ID, s.Is_Primary, sv.Value, sg.Reference FROM product_specification AS s INNER JOIN product_specification_value AS sv ON sv.Value_ID=s.Value_ID INNER JOIN product_specification_group AS sg ON sg.Group_ID=sv.Group_ID WHERE s.Product_ID=%s", mysql_real_escape_string($_REQUEST['pid'])));
	$table->AddField('Group Reference', 'Reference', ',left');
	$table->AddField('Value', 'Value', 'left');
	$table->AddField('Is Primary', 'Is_Primary', 'center');
	$table->AddLink("product_specs.php?action=update&spec=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">", "Specification_ID");
	$table->AddLink("javascript:confirmRequest('product_specs.php?action=remove&spec=%s','Are you sure you want to remove this product specification title/value pair?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Specification_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Reference");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add product specification" class="btn" onclick="window.location.href=\'product_specs.php?action=add&pid=%d\'">', $_REQUEST['pid']);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}