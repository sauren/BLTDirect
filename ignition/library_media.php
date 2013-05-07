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
	require_once('lib/common/app_header.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LibraryMedia.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, true);
	$form->AddField('thumb', 'Thumbnail', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('title', 'Title', 'text', '', 'alpha_numeric', 1, 100, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()) {
			$media = new LibraryMedia();
			$media->Title = $form->GetValue('title');

			$largeField = 'image';
			$thumbField = (empty($_FILES['thumb']['name']))?'image':'thumb';

			if($media->Add($thumbField, $largeField)) {
				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			} else {
				for($i=0; $i<count($media->Src->Errors); $i++) {
					$form->AddError($media->Src->Errors[$i]);
				}

				for($i=0; $i<count($media->Thumb->Errors); $i++) {
					$form->AddError($media->Thumb->Errors[$i]);
				}
			}
		}
	}

	$page = new Page('<a href="library_media.php">Media Library</a> &gt; Add Image','Add your image details below.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Add image");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below. If no Thumbnail is specified the Image will be used.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow($form->GetLabel('thumb'), $form->GetHTML('thumb') . $form->GetIcon('thumb'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'library_media.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	echo "<br />";

	$page->Display('footer');
}

function update()
{
	require_once('lib/common/app_header.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LibraryMedia.php');

	$media = new LibraryMedia();

	if(!$media->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', $media->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('thumb', 'Thumbnail', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('title', 'Title', 'text', $media->Title, 'alpha_numeric', 1, 100, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->Validate()) {
			$media->Title = $form->GetValue('title');

			$largeField = 'image';
			$thumbField = (empty($_FILES['thumb']['name']))?'image':'thumb';

			if($media->Update($thumbField, $largeField)) {
				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			} else {
				for($i=0; $i<count($media->Src->Errors); $i++) {
					$form->AddError($media->Src->Errors[$i]);
				}

				for($i=0; $i<count($media->Thumb->Errors); $i++) {
					$form->AddError($media->Thumb->Errors[$i]);
				}
			}

			redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
		}
	}

	$page = new Page('<a href="library_media.php">Media Library</a> &gt; Update Image','Update your image details below.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Update image");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below. If no Thumbnail is specified the Image will be used.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));

	if($media->Thumb->Exists()) {
		echo $webForm->AddRow('Current Image', sprintf('<img src="%s" />', $GLOBALS['MEDIA_DIR_WS'] . $media->Thumb->FileName));
	}

	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow($form->GetLabel('thumb'), $form->GetHTML('thumb') . $form->GetIcon('thumb'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'library_media.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	echo "<br />";

	$page->Display('footer');
}

function remove()
{
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/LibraryMedia.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id']))
	{
		$media = new LibraryMedia();
		$media->Remove($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page($headerString ."Media Library", "Here you can add content to the media library");
	$page->Display('header');

	$data = new DataQuery("SELECT * FROM library_media");
	if($data->TotalRows == 0) {
		echo '<table class="DataTable"><thead><tr><th>Thumbnail</th><th>Title</th><th width="1%" nowrap="nowrap">&nbsp;</th></tr></thead><tbody>';
		echo '<tr><td align="middle" colspan="3">There are currently no items to view.</td></tr>';
		echo '</tbody></table>';
	} else {
		echo '<table class="DataTable"><thead><tr><th>Thumbnail</th><th>Title</th><th>&nbsp;</th></tr></thead><tbody>';
		while($data->Row){
			echo sprintf('<tr><td><a href="%s" target="_blank"><img src="%s" border="0" alt="%s" /></a></td><td>%s&nbsp;</td><td align="right"><a href="javascript:copy(\''.substr($GLOBALS['HTTP_SERVER'], 0, -1).$GLOBALS['MEDIA_DIR_WS'].$data->Row['SRC'].'\');"><img src="./images/icon_pages_1.gif" alt="Copy URL to clipboard" border="0" /></a> <a href="library_media.php?action=update&id=%s"><img src="./images/icon_edit_1.gif" alt="Update Media" border="0"></a> <a href="javascript:confirmRequest(\'library_media.php?action=remove&id=%s\',\'Are you sure you want to remove this Image.\');"><img src="./images/aztector_6.gif" alt="Remove" border="0"></a></td></tr>',$GLOBALS['MEDIA_DIR_WS'] . $data->Row['SRC'], $GLOBALS['MEDIA_DIR_WS'] . $data->Row['Thumb'], $data->Row['Title'], $data->Row['Title'], $data->Row['Media_ID'], $data->Row['Media_ID']);
			$data->Next();
		}
		echo '</tbody></table>';
	}
	$data->Disconnect();

	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add new image" class="btn" onclick="window.location.href=\'library_media.php?action=add\'">');

	$page->Display('footer');
}

require_once('lib/common/app_header.php');
?>