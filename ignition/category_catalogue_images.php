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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryCatalogueImage.php');

	if(isset($_REQUEST['img'])) {
		$image = new CategoryCatalogueImage($_REQUEST['img']);
		$image->Delete();

		redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $image->CategoryID));
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryCatalogueImage.php');

	$category = new Category();

	if(!isset($_REQUEST['categoryid']) || !$category->Get($_REQUEST['categoryid'])) {
		redirect(sprintf("Location: product_categories.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, true);
	$form->AddField('categoryid', 'Category ID', 'hidden', '', 'numeric_unsigned', 1, 11, false);
	$form->AddField('title', 'Title', 'text', '', 'alpha_numeric', 1, 120, false);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()){
			$image = new CategoryCatalogueImage();
			$image->CategoryID = $form->GetValue('categoryid');
			$image->Title = $form->GetValue('title');

			if($image->Add('image')) {
		   		redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $form->GetValue('categoryid')));
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

	$page = new Page(sprintf('<a href="product_categories.php">%s</a> &gt; <a href="%s?cat=%d">Category Catalogue Images</a> &gt; Add Image', $category->Name, $_SERVER['PHP_SELF'], $category->ID), 'Upload your catalogue images here.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add an image.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('categoryid');
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s?cat=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $category->ID, $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CategoryCatalogueImage.php');

	$image = new CategoryCatalogueImage($_REQUEST['img']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('img', 'Image ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Title', 'text', $image->Title, 'alpha_numeric', 1, 120, false);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()){
			$image->Title = $form->GetValue('title');

			if($image->Update()) {
		   		redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $image->CategoryID));
			}
		}
	}

	$page = new Page(sprintf('<a href="%s"> Catalogue Images</a> &gt; Update Image', $_SERVER['PHP_SELF']), 'Update your  catalogue images here.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update an image.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('img');
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'%s?cat=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_SERVER['PHP_SELF'], $image->CategoryID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	$category = new Category();

	if(!isset($_REQUEST['cat']) || !$category->Get($_REQUEST['cat'])) {
		redirect(sprintf("Location: product_categories.php"));
	}

	$page = new Page(sprintf('<a href="product_categories.php">%s</a> &gt; Category Catalogue Images', $category->Name), 'Add your hi-resolution images here for  catalogues.');
	$page->Display('header');

	$data = new DataQuery(sprintf("SELECT * FROM category_catalogue_image WHERE Category_ID=%d ORDER BY Title ASC", mysql_real_escape_string($category->ID)));
	if($data->TotalRows > 0) {
		?>

		<table class="DataTable">
			<thead>
				<tr>
					<th>Thumbnail</th>
					<th>Title</th>
					<th width="1%">&nbsp;</th>
				</tr>
			</thead>
			<tbody>

				<?php
				while($data->Row) {
					echo sprintf('<tr><td>%s&nbsp;</td><td>%s&nbsp;</td><td align="right" nowrap="nowrap"><a href="%s?action=update&img=%s"><img src="images/icon_edit_1.gif" alt="Update Settings" border="0" /></a> <a href="javascript:confirmRequest(\'%s?action=remove&img=%s\',\'Are you sure you want to remove this image?\');"><img src="images/aztector_6.gif" alt="Remove" border="0" /></a></td></tr>', file_exists($GLOBALS['CATEGORY_CATALOGUE_THUMB_DIR_FS'].$data->Row['Thumb_File_Name']) ? sprintf('<img src="%s%s" border="0" />', $GLOBALS['CATEGORY_CATALOGUE_THUMB_DIR_WS'], $data->Row['Thumb_File_Name']) : '', $data->Row['Title'], $_SERVER['PHP_SELF'], $data->Row['Category_Catalogue_Image_ID'], $_SERVER['PHP_SELF'], $data->Row['Category_Catalogue_Image_ID']);

					$data->Next();
				}
				?>

			</tbody>
		</table>

		<?php
	} else {
		echo 'There are no images available for viewing.';
		echo '<br />';
	}
	$data->Disconnect();

	echo sprintf('<br /><input type="button" name="add" value="add new image" class="btn" onclick="window.location.href=\'%s?action=add&categoryid=%d\'" />', $_SERVER['PHP_SELF'], $category->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>