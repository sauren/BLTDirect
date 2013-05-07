<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductImage.php');

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
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['img'])){
		$image = new ProductImage;
		$image->Delete($_REQUEST['img']);
	}
	
	redirect(sprintf("Location: product_images.php?pid=%d", $_REQUEST['pid']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_images WHERE Product_ID=%d", mysql_real_escape_string($_REQUEST['pid'])));
	$default = ($data->Row['Count'] > 0) ? 'N' : 'Y';
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);

	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, true);
	$form->AddField('thumb', 'Thumbnail', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('title', 'Image Title', 'text', '', 'alpha_numeric', 1, 100, false);
	$form->AddField('description', 'Image Description', 'text', '', 'alpha_numeric', 1, 255, false);
	$form->AddField('active', 'Active Image', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('default', 'Default Image', 'checkbox', $default, 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$image = new ProductImage;
			$image->ParentID = $form->GetValue('pid');
			$image->Name = $form->GetValue('title');
			$image->Description = $form->GetValue('description');
			$image->IsActive = $form->GetValue('active');
			$image->IsDefault = $form->GetValue('default');
			$largeField = 'image';
			$thumbField = (empty($_FILES['thumb']['name']))?'image':'thumb';

			if($image->Add($thumbField, $largeField)) {
				$cache = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND']);
				$cache->remove('product__' . $_REQUEST['pid']);
		
		   		redirect(sprintf("Location: product_images.php?pid=%d", $form->GetValue('pid')));
			} else {
				for($i=0; $i<count($image->Thumb->Errors); $i++) {
					$form->AddError($image->Thumb->Errors[$i]);
				}

				for($i=0; $i<count($image->Large->Errors); $i++) {
					$form->AddError($image->Large->Errors[$i]);
				}
			}
		}
	}
	
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_images.php?pid=%s">Product Images</a> &gt; Add Product Image', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow("Add a Product Image.");
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below. If no Thumbnail is specified the Image will be used.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow($form->GetLabel('thumb'), $form->GetHTML('thumb') . $form->GetIcon('thumb'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow($form->GetLabel('default'), $form->GetHTML('default') . $form->GetIcon('default'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_images.php?pid=%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
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
	$image = new ProductImage;
	$image->Get($_REQUEST['img']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('img', 'Image ID', 'hidden', $image->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('thumb', 'Thumbnail', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('title', 'Image Title', 'text', $image->Name, 'alpha_numeric', 1, 100, false);
	$form->AddField('description', 'Image Description', 'text', $image->Description, 'alpha_numeric', 1, 255, false);
	$form->AddField('active', 'Active Image', 'checkbox', $image->IsActive, 'boolean', 1, 1, false);
	$form->AddField('default', 'Default Image', 'checkbox', $image->IsDefault, 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$image->Parent->ID = $form->GetValue('pid');
			$image->Name = $form->GetValue('title');
			$image->Description = $form->GetValue('description');
			$image->IsActive = $form->GetValue('active');
			$image->IsDefault = $form->GetValue('default');
			$largeField = 'image';
			$thumbField = (empty($_FILES['thumb']['name']))?'image':'thumb';
			$image->Update($thumbField, $largeField);
			
			$cache = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND']);
			$cache->remove('product__' . $_REQUEST['pid']);
		
		    redirect(sprintf("Location: product_images.php?pid=%d", $form->GetValue('pid')));
		}
	}
	
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_images.php?pid=%s">Product Images</a> &gt; Update Product Image', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow("Update a Product Image.");
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $form->GetHTML('img');
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below. If no Thumbnail is specified the Image will be used.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow($form->GetLabel('thumb'), $form->GetHTML('thumb') . $form->GetIcon('thumb'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow($form->GetLabel('default'), $form->GetHTML('default') . $form->GetIcon('default'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_images.php?pid=%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Product Images', $_REQUEST['pid']),'You may add more than one image to your Products, however only one may be the default image used by your Product Categories. Click on a thumnail image to view larger images in a new browser window.');
	$page->Display('header');

	$data = new DataQuery(sprintf("select * from product_images where Product_ID=%d", mysql_real_escape_string($_REQUEST['pid'])));
	if($data->TotalRows == 0){
		echo "There are no images associated with this Product Profile<br />";
	} else {
		echo '<table class="DataTable"><thead><tr><th>Thumbnail</th><th>Title</th><th>Default</th><th>Active</th><th>&nbsp;</th></tr></thead><tbody>';
		while($data->Row){
			echo sprintf('<tr><td><a href="%s%s" target="_blank"><img src="%s%s" border="0"/></a></td><td>%s&nbsp;</td><td>%s</td><td>%s</td><td><a href="product_images.php?action=update&img=%s&pid=%s"><img src="./images/icon_edit_1.gif" alt="Update Settings" border="0"></a> <a href="javascript:confirmRequest(\'product_images.php?action=remove&confirm=true&img=%s&pid=%s\',\'Are you sure you want to remove this Image. The image will be deleted on the server.\');"><img src="./images/aztector_6.gif" alt="Remove" border="0"></a></td></tr>',
							$GLOBALS['PRODUCT_IMAGES_DIR_WS'],
							$data->Row['Image_Src'],
							$GLOBALS['PRODUCT_IMAGES_DIR_WS'],
							$data->Row['Image_Thumb'],
							$data->Row['Image_Title'],
							$data->Row['Is_Primary'],
							$data->Row['Is_Active'],
							$data->Row['Product_Image_ID'],
							$data->Row['Product_ID'],
							$data->Row['Product_Image_ID'],
							$data->Row['Product_ID']);
			$data->Next();
		}
		echo '</tbody></table>';
	}

	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new product image" class="btn" onclick="window.location.href=\'product_images.php?action=add&pid=%d\'">', $_REQUEST['pid']);
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>