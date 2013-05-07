<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductImage.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

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

function remove() {
	if(isset($_REQUEST['img'])) {
		$image = new ProductImage($_REQUEST['img']);
		$image->Delete();

		redirectTo(sprintf("?valueid=%d", $image->SpecificationValueID));
	}
	
	redirectTo(sprintf('product_specs_groups.php'));
}

function add() {
	$value = new ProductSpecValue();

	if(!isset($_REQUEST['valueid']) || !$value->Get($_REQUEST['valueid'])) {
		redirectTo(sprintf('product_specs_groups.php'));
	}

	$value->Group->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('valueid', 'Value ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, true);
	$form->AddField('thumb', 'Thumbnail', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('title', 'Image Title', 'text', '', 'alpha_numeric', 1, 100, false);
	$form->AddField('description', 'Image Description', 'text', '', 'alpha_numeric', 1, 255, false);
	$form->AddField('active', 'Active Image', 'checkbox', 'Y', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$image = new ProductImage();
			$image->SpecificationValueID = $value->ID;
			$image->Name = $form->GetValue('title');
			$image->Description = $form->GetValue('description');
			$image->IsActive = $form->GetValue('active');
			
			$largeField = 'image';
			$thumbField = (empty($_FILES['thumb']['name']))?'image':'thumb';

			if($image->Add($thumbField, $largeField)) {		
		   		redirect(sprintf("Location: ?valueid=%d", $value->ID));
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

	$page = new Page(sprintf('<a href="product_specs_groups_values.php">Product Specification Groups</a> &gt; <a href="product_specs_groups_values.php?group=%d">Product Specification Group Values</a> &gt; <a href="?valueid=%d">Images</a> &gt; Add Product Image', $value->Group->ID, $value->ID), 'Add specification product image.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow("Add a Product Image.");
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('valueid');

	echo $window->Open();
	echo $window->AddHeader('Please complete the form below. If no Thumbnail is specified the Image will be used.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow($form->GetLabel('thumb'), $form->GetHTML('thumb') . $form->GetIcon('thumb'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'?valueid=%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $value->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	$image = new ProductImage();

	if(!isset($_REQUEST['img']) || !$image->Get($_REQUEST['img'])) {
		redirectTo(sprintf('product_specs_groups.php'));
	}

	$value = new ProductSpecValue($image->SpecificationValueID);
	$value->Group->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('img', 'Image ID', 'hidden', $image->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('thumb', 'Thumbnail', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('title', 'Image Title', 'text', $image->Name, 'alpha_numeric', 1, 100, false);
	$form->AddField('description', 'Image Description', 'text', $image->Description, 'alpha_numeric', 1, 255, false);
	$form->AddField('active', 'Active Image', 'checkbox', $image->IsActive, 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()){
			$largeField = 'image';
			$thumbField = (empty($_FILES['thumb']['name']))?'image':'thumb';

			$image->SpecificationValueID = $value->ID;
			$image->Name = $form->GetValue('title');
			$image->Description = $form->GetValue('description');
			$image->IsActive = $form->GetValue('active');
			$image->Update($thumbField, $largeField);
		
		    redirect(sprintf("Location: ?valueid=%d", $value->ID));
		}
	}
	
	$page = new Page(sprintf('<a href="product_specs_groups_values.php">Product Specification Groups</a> &gt; <a href="product_specs_groups_values.php?group=%d">Product Specification Group Values</a> &gt; <a href="?valueid=%d">Images</a> &gt; Update Product Image', $value->Group->ID, $value->ID), 'Update specification product image.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow("Update Image.");
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
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
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'?valueid=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $value->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	$value = new ProductSpecValue();

	if(!isset($_REQUEST['valueid']) || !$value->Get($_REQUEST['valueid'])) {
		redirectTo(sprintf('product_specs_groups.php'));
	}

	$value->Group->Get();

	$page = new Page(sprintf('<a href="product_specs_groups_values.php">Product Specification Groups</a> &gt; <a href="product_specs_groups_values.php?group=%d">Product Specification Group Values</a> &gt; Images', $value->Group->ID), 'Manage specification product images.');
	$page->Display('header');

	$data = new DataQuery(sprintf("select * from product_images where Specification_Value_ID=%d", mysql_real_escape_string($_REQUEST['valueid'])));
	if($data->TotalRows == 0){
		echo "There are no images associated with this Specification Value<br />";
	} else {
		echo '<table class="DataTable"><thead><tr><th>Thumbnail</th><th>Title</th><th>Active</th><th>&nbsp;</th></tr></thead><tbody>';
		while($data->Row){
			echo sprintf('<tr><td><a href="%s%s" target="_blank"><img src="%s%s" border="0"/></a></td><td>%s</td><td>%s</td><td><a href="?action=update&img=%s"><img src="images/icon_edit_1.gif" alt="Update" border="0"></a><a href="javascript:confirmRequest(\'?action=remove&img=%s\',\'Are you sure you want to remove this item.\');"><img src="images/aztector_6.gif" alt="Remove" border="0"></a></td></tr>',
							$GLOBALS['PRODUCT_IMAGES_DIR_WS'],
							$data->Row['Image_Src'],
							$GLOBALS['PRODUCT_IMAGES_DIR_WS'],
							$data->Row['Image_Thumb'],
							$data->Row['Image_Title'],
							$data->Row['Is_Active'],
							$data->Row['Product_Image_ID'],
							$data->Row['Product_Image_ID']);
			$data->Next();
		}
		echo '</tbody></table>';
	}

	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add new specification image" class="btn" onclick="window.location.href=\'?action=add&valueid=%d\'">', $value->ID);
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}