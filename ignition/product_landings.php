<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductLanding.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductLandingDirectory.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductLandingProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

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
} elseif($action == 'open'){
	$session->Secure(3);
	open();
	exit;
} elseif($action == 'purgecache'){
	$session->Secure(3);
	purgeCache();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$item = new ProductLanding($_REQUEST['id']);
		$item->Delete();
		
		redirect('Location: product_landing_directory.php?id=' . $item->directoryId);
	}

	redirect('Location: product_landing_directory.php');
}

function add(){
	$item = new ProductLanding();
	$item->category->Get();
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('did', 'Product Landing Directory ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', '', 'paragraph', 1, 120, true);
	$form->AddField('description', 'Description', 'textarea', '', 'anything', null, null, true, 'rows="10" style="width: 300px;"');
	$form->AddField('group', 'Primary Group', 'select', '', 'numeric_unsigned', 1, 11, true);
	$form->AddOption('group', '', '');
	$form->AddField('group2', 'Secondary Group', 'select', '', 'numeric_unsigned', 1, 11, true, 'onchange="populateSpecs(this);"');
	$form->AddOption('group2', '', '');
	$form->AddField('value2', ' Secondary Value', 'select', '', 'numeric_unsigned', 1, 11, true);
	$form->AddOption('value2', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_group ORDER BY Reference ASC"));
	while($data->Row) {
		$form->AddOption('group', $data->Row['Group_ID'], $data->Row['Reference']);
		$form->AddOption('group2', $data->Row['Group_ID'], $data->Row['Reference']);

		$data->Next();
	}
	$data->Disconnect();

	if($form->GetValue('group2') > 0) {
		$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=%d ORDER BY Value ASC", mysql_real_escape_string($form->GetValue('group2'))));
		while($data->Row) {
			$form->AddOption('value2', $data->Row['Value_ID'], $data->Row['Value']);
			
			$data->Next();
		}
		$data->Disconnect();
	}
	
	$form->AddField('parent', 'Parent', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('imagereference', 'Image Reference', 'text', '', 'paragraph', 0, 60, false);
	$form->AddField('hidefilter', 'Hide Filter', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('popularalignment', 'Popular Alignment', 'select', 'Left', 'alpha', 1, 10);
	$form->AddOption('popularalignment', 'Left', 'Left');
	$form->AddOption('popularalignment', 'Center', 'Center');
	$form->AddOption('popularalignment', 'Right', 'Right');
	$form->AddField('popularimagesize', 'Popular Image Size', 'select', '1.00', 'float', 1, 11);
	$form->AddOption('popularimagesize', '1.00', 'Large');
	$form->AddOption('popularimagesize', '0.75', 'Medium');
	$form->AddOption('popularimagesize', '0.50', 'Small');
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$item->directoryId = $form->GetValue('did');
			$item->name = $form->GetValue('name');
			$item->description = $form->GetValue('description');
			$item->specGroup->ID = $form->GetValue('group');
			$item->specValue->ID = $form->GetValue('value2');
			$item->category->ID = $form->GetValue('parent');
			$item->imageReference = $form->GetValue('imagereference');
			$item->hideFilter = $form->GetValue('hidefilter');
			$item->popularAlignment = $form->GetValue('popularalignment');
			$item->popularImageSize = $form->GetValue('popularimagesize');
			$item->add();

			redirectTo('product_landing_directory.php?id=' . $item->directoryId);
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
				valueField = document.getElementById(\'value2\');

				if(valueField) {
					valueField.length = 0;
					valueField.options[0] = new Option(\'Loading...\', \'\');
					valueField.setAttribute(\'disabled\', \'disabled\');

					req.get(\'lib/util/getSpecValuesByGroup.php\', \'id=\' + formElement.value);
				} else {
					alert(\'An error occurred whilst populating the specification values.\');
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

	$page = new Page(sprintf('<a href="product_landing_directory.php?id=%d">Product Landings</a> &gt; Add Landing', $form->GetValue('did')), 'Add new product landing page.');
	$page->AddToHead('<script language="javascript" src="js/HttpRequest.js" type="text/javascript"></script>');
	$page->AddToHead($script);
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Adding new record');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('did');
		
	echo $window->Open();
	echo $window->AddHeader('Enter details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('group'), $form->GetHTML('group') . $form->GetIcon('group'));
	echo $webForm->AddRow($form->GetLabel('group2'), $form->GetHTML('group2') . $form->GetIcon('group2'));
	echo $webForm->AddRow($form->GetLabel('value2'), $form->GetHTML('value2') . $form->GetIcon('value2'));
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search" /></a>', sprintf("<span id=\"parentCaption\">%s</span>", $item->category->Name) . $form->GetHTML('parent') . $form->GetIcon('parent'));
	echo $webForm->AddRow($form->GetLabel('imagereference'), $form->GetHTML('imagereference') . $form->GetIcon('imagereference'));
	echo $webForm->AddRow($form->GetLabel('hidefilter'), $form->GetHTML('hidefilter') . $form->GetIcon('hidefilter'));
	echo $webForm->AddRow($form->GetLabel('popularalignment'), $form->GetHTML('popularalignment') . $form->GetIcon('popularalignment'));
	echo $webForm->AddRow($form->GetLabel('popularimagesize'), $form->GetHTML('popularimagesize') . $form->GetIcon('popularimagesize'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href = \'product_landing_directory.php?id=%d\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $item->directoryId, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	if(!isset($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}

	$item = new ProductLanding();
	
	if(!$item->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');		
	}

	$item->specValue->Get();
	$item->category->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('did', 'Product Landing Directory ID', 'hidden', $item->directoryId, 'numeric_unsigned', 1, 11);
	$form->AddField('id', 'ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $item->name, 'paragraph', 1, 120, true);
	$form->AddField('description', 'Description', 'textarea', $item->description, 'anything', null, null, true, 'rows="10" style="width: 300px;"');
	$form->AddField('group', 'Primary Group', 'select', $item->specGroup->ID, 'numeric_unsigned', 1, 11, true);
	$form->AddOption('group', '', '');
	$form->AddField('group2', 'Secondary Group', 'select', $item->specValue->Group->ID, 'numeric_unsigned', 1, 11, true, 'onchange="populateSpecs(this);"');
	$form->AddOption('group2', '', '');
	$form->AddField('value2', ' Secondary Value', 'select', $item->specValue->ID, 'numeric_unsigned', 1, 11, true);
	$form->AddOption('value2', '', '');

	$data = new DataQuery(sprintf("SELECT * FROM product_specification_group ORDER BY Reference ASC"));
	while($data->Row) {
		$form->AddOption('group', $data->Row['Group_ID'], $data->Row['Name']);
		$form->AddOption('group2', $data->Row['Group_ID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	if($form->GetValue('group2') > 0) {
		$data = new DataQuery(sprintf("SELECT * FROM product_specification_value WHERE Group_ID=%d ORDER BY Value ASC", mysql_real_escape_string($form->GetValue('group2'))));
		while($data->Row) {
			$form->AddOption('value2', $data->Row['Value_ID'], $data->Row['Value']);
			
			$data->Next();
		}
		$data->Disconnect();
	}
	
	$form->AddField('parent', 'Parent', 'hidden', $item->category->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('imagereference', 'Image Reference', 'text', $item->imageReference, 'paragraph', 0, 60, false);
	$form->AddField('hidefilter', 'Hide Filter', 'checkbox', $item->hideFilter, 'boolean', 1, 1, false);
	$form->AddField('popularalignment', 'Popular Alignment', 'select', $item->popularAlignment, 'alpha', 1, 10);
	$form->AddOption('popularalignment', 'Left', 'Left');
	$form->AddOption('popularalignment', 'Center', 'Center');
	$form->AddOption('popularalignment', 'Right', 'Right');
	$form->AddField('popularimagesize', 'Popular Image Size', 'select', $item->popularImageSize, 'float', 1, 11);
	$form->AddOption('popularimagesize', '1.00', 'Large');
	$form->AddOption('popularimagesize', '0.75', 'Medium');
	$form->AddOption('popularimagesize', '0.50', 'Small');
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$item->directoryId = $form->GetValue('did');
			$item->name = $form->GetValue('name');
			$item->description = $form->GetValue('description');
			$item->specGroup->ID = $form->GetValue('group');
			$item->specValue->ID = $form->GetValue('value2');
			$item->category->ID = $form->GetValue('parent');
			$item->imageReference = $form->GetValue('imagereference');
			$item->hideFilter = $form->GetValue('hidefilter');
			$item->popularAlignment = $form->GetValue('popularalignment');
			$item->popularImageSize = $form->GetValue('popularimagesize');
			$item->update();

			redirect('Location: product_landing_directory.php?id=' . $item->directoryId);
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
				valueField = document.getElementById(\'value2\');

				if(valueField) {
					valueField.length = 0;
					valueField.options[0] = new Option(\'Loading...\', \'\');
					valueField.setAttribute(\'disabled\', \'disabled\');

					req.get(\'lib/util/getSpecValuesByGroup.php\', \'id=\' + formElement.value);
				} else {
					alert(\'An error occurred whilst populating the specification values.\');
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
	
	$script .= sprintf('<script language="javascript" type="text/javascript">
		var foundDirectory = function(id, str) {
			var e = null;

			e = document.getElementById(\'did\');
			if(e) {
				e.value = id;
			}

			e = document.getElementById(\'directoryCaption\');
			if(e) {
				e.innerHTML = (id > 0) ? str : \'<em>None</em>\';
			}
		}
		</script>');
		
	$page = new Page(sprintf('<a href="product_landing_directory.php?id=%d">Product Landings</a> &gt; Update Landing', $item->directoryId), 'Update existing product landing page.');
	$page->AddToHead('<script language="javascript" src="js/HttpRequest.js" type="text/javascript"></script>');
	$page->AddToHead($script);
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating existing record');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $form->GetHTML('did');
	
	$directory = new ProductLandingDirectory($item->directoryId);
	
	echo $window->Open();
	echo $window->AddHeader('Enter details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow(sprintf('Product Landing Directory <a href="javascript:popUrl(\'product_landing_directory.php?action=getnode&callback=foundDirectory\', 650, 500);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>'), sprintf('<span id="directoryCaption">%s</span>', ($directory->id > 0) ? $directory->name : '<em>None</em>'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('group'), $form->GetHTML('group') . $form->GetIcon('group'));
	echo $webForm->AddRow($form->GetLabel('group2'), $form->GetHTML('group2') . $form->GetIcon('group2'));
	echo $webForm->AddRow($form->GetLabel('value2'), $form->GetHTML('value2') . $form->GetIcon('value2'));
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search" /></a>', sprintf("<span id=\"parentCaption\">%s</span>", $item->category->Name) . $form->GetHTML('parent') . $form->GetIcon('parent'));
	echo $webForm->AddRow($form->GetLabel('imagereference'), $form->GetHTML('imagereference') . $form->GetIcon('imagereference'));
	echo $webForm->AddRow($form->GetLabel('hidefilter'), $form->GetHTML('hidefilter') . $form->GetIcon('hidefilter'));
	echo $webForm->AddRow($form->GetLabel('popularalignment'), $form->GetHTML('popularalignment') . $form->GetIcon('popularalignment'));
	echo $webForm->AddRow($form->GetLabel('popularimagesize'), $form->GetHTML('popularimagesize') . $form->GetIcon('popularimagesize'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location.href=\'product_landing_directory.php?id=%d\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $item->directoryId, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function open(){
	if(!isset($_REQUEST['id'])) {
		redirect('Location: ?action=view');
	}

	$item = new ProductLanding();
	
	if(!$item->get($_REQUEST['id'])) {
		redirect('Location: ?action=view');		
	}
	
	$categories = array();
	
	if($item->category->ID > 0) {
		$categories = getCategories($item->category->ID);
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'open', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			new DataQuery(sprintf("DELETE FROM product_landing_product WHERE landingId=%d", mysql_real_escape_string($item->id)));
			
			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/select_([0-9]+)/', $key, $matches)) {
					$product = new ProductLandingProduct();
					$product->landingId = $item->id;
					$product->product->ID = $matches[1];
					$product->add();
				}	
			}
			
			redirectTo(sprintf('?action=open&id=%d', $item->id));
		}
	}
	
	$data = new DataQuery(sprintf("SELECT productId FROM product_landing_product WHERE landingId=%d", mysql_real_escape_string($item->id)));
	while($data->Row) {
		$_REQUEST['select_' . $data->Row['productId']] = 'Y';
	
		$data->Next();	
	}
	$data->Disconnect();
		
	$page = new Page('<a href="?action=view">Product Landings</a> &gt; Landing Products', 'Select featured products for this landing.');
	$page->Display('header');
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');

	$table = new DataTable('records');
	$table->SetSQL(sprintf("SELECT p.Product_ID, p.Product_Title, p.Meta_Title, p.SKU, p.Order_Min, pi.Image_Thumb FROM product AS p%s INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID AND ps.Value_ID=%d INNER JOIN product_specification AS ps2 ON ps2.Product_ID=p.Product_ID INNER JOIN product_specification_value AS psv on psv.Value_ID=ps2.Value_ID AND psv.Group_ID=%d LEFT JOIN product_images AS pi ON pi.Product_ID=p.Product_ID AND pi.Is_Active='Y' AND pi.Is_Primary='Y' WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Discontinued='N' AND p.Is_Demo_Product='N' GROUP BY p.Product_ID", ($item->category->ID > 0) ? sprintf(' INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID IN (%s)', implode(', ', $categories)) : '', $item->specValue->ID, $item->specGroup->ID));
	$table->SetExtractVars();
	$table->AddField("ID#", "Product_ID");
	$table->AddField("Name", "Product_Title", "left");
	$table->AddInput('', 'N', 'Y', 'select', 'Product_ID', 'checkbox');
	$table->SetMaxRows(10000);
	$table->SetOrderBy("Product_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input type="submit" name="select" value="select" class="btn" />';

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	redirectTo('product_landing_directory.php');
}

function purgeCache() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$cache = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND']);
		$cache->remove('product_landing_' . $_REQUEST['id']);
		
		$landing = new ProductLanding($_REQUEST['id']);
	
		redirect('Location: product_landing_directory.php?id=' . $landing->directoryId);
	}

	redirect('Location: product_landing_directory.php');
}

function getCategories($categoryId) {
	$items = array($categoryId);
	
	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		$items = array_merge($items, getCategories($data->Row['Category_ID']));
		
		$data->Next();	
	}
	$data->Disconnect();
	
	return $items;
}