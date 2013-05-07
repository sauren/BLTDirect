<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductVideo.php');

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
	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['vid'])){
		$image = new ProductVideo;
		$image->Delete($_REQUEST['vid']);
	}
	
	redirect(sprintf("Location: product_videos.php?pid=%d", $_REQUEST['pid']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_videos WHERE Product_ID=%d", mysql_real_escape_string($_REQUEST['pid'])));
	$default = ($data->Row['Count'] > 0) ? 'N' : 'Y';
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('vtitle', 'Video Title', 'text', '', 'anything', 1, 255);
	$form->AddField('youtubeurl', 'Youtube Url', 'text', '', 'link_relative', 1, 255);
	$form->AddField('active', 'Active Video', 'checkbox', 'Y', 'boolean', 1, 1, false);


	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$video = new ProductVideo;
			$video->ProductID = $form->GetValue('pid');
			$video->VideoTitle = $form->GetValue('vtitle');
			$video->YoutubeURL = $form->GetValue('youtubeurl');
			$video->IsActive = $form->GetValue('active');
			$video->Add();
			
		   	redirect(sprintf("Location: product_videos.php?pid=%d", $form->GetValue('pid')));
		}
	}
	
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_videos.php?pid=%s">Product Videos</a> &gt; Add Product Videos', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow("Add a Product Video.");
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('vtitle'), $form->GetHTML('vtitle') . $form->GetIcon('vtitle'));
	echo $webForm->AddRow($form->GetLabel('youtubeurl'), $form->GetHTML('youtubeurl') . $form->GetIcon('youtubeurl'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_videos.php?pid=%s\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
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
	$video = new ProductVideo;
	$video->Get($_REQUEST['vid']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('vid', 'Video ID', 'hidden', $_REQUEST['vid'], 'numeric_unsigned', 1, 11);
	$form->AddField('pid', 'Product ID', 'hidden', $_REQUEST['pid'], 'numeric_unsigned', 1, 11);
	$form->AddField('vtitle', 'Video Title', 'text', $video->VideoTitle, 'anything', 1, 255);
	$form->AddField('youtubeurl', 'Youtube Url', 'text', $video->YoutubeURL, 'link_relative', 1, 255);
	$form->AddField('active', 'Active Video', 'checkbox', $video->IsActive, 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$video->ID = $form->GetValue('vid');
			$video->ProductID = $form->GetValue('pid');
			$video->VideoTitle = $form->GetValue('vtitle');
			$video->YoutubeURL = $form->GetValue('youtubeurl');
			$video->IsActive = $form->GetValue('active');
			$video->Update();
		    redirect(sprintf("Location: product_videos.php?pid=%d", $form->GetValue('pid')));
		}
	}
	
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; <a href="product_videos.php?pid=%s">Product video</a> &gt; Update Product video', $_REQUEST['pid'], $_REQUEST['pid']),'The more information you supply the better your system will become');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	
	$window = new StandardWindow("Update a Product Video.");
	$webForm = new StandardForm;
	
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('pid');
	echo $form->GetHTML('vid');
	echo $window->Open();
	echo $window->AddHeader('Please complete the form below.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('vtitle'), $form->GetHTML('vtitle') . $form->GetIcon('vtitle'));
	echo $webForm->AddRow($form->GetLabel('youtubeurl'), $form->GetHTML('youtubeurl') . $form->GetIcon('youtubeurl'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_videos.php?pid=%s\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Product Video', $_REQUEST['pid']),'You may add more than one video to your Products.');
	$page->Display('header');

	$data = new DataQuery(sprintf("select * from product_videos where Product_ID=%d", mysql_real_escape_string($_REQUEST['pid'])));
	if($data->TotalRows == 0){
		echo "There are no videos associated with this Product Profile<br />";
	} else {
		echo '<table class="DataTable">
				<thead>
					<tr>
						<th>Title</th>
						<th>Youtube URL</th>
						<th>Active</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
			<tbody>';
		while($data->Row){
			echo sprintf('<tr>
					<td>%s</td>
					<td>%s</td>
					<td>%s</td>
					<td>
						<a href="product_videos.php?action=update&vid=%s&pid=%s">
							<img src="./images/icon_edit_1.gif" alt="Update Settings" border="0">
						</a> 
						<a href="javascript:confirmRequest(\'product_videos.php?action=remove&confirm=true&vid=%s&pid=%s\',\'Are you sure you want to remove this Image. The image will be deleted on the server.\');">
							<img src="./images/aztector_6.gif" alt="Remove" border="0">
						</a>
						</td>
					</tr>',
							$data->Row['Video_Title'],
							$data->Row['Youtube_Url'],
							$data->Row['Is_Active'],
							$data->Row['Product_Video_ID'],
							$data->Row['Product_ID'],
							$data->Row['Product_Video_ID'],
							$data->Row['Product_ID']);
			$data->Next();
		}
		echo '</tbody></table>';
	}

	echo "<br>";
	echo sprintf('<input type="button" name="add" value="add a new product video" class="btn" onclick="window.location.href=\'product_videos.php?action=add&pid=%d\'">', $_REQUEST['pid']);
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>