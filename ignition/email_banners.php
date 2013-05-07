<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

if($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/EmailBanner.php');

	if(isset($_REQUEST['id'])) {
		$banner = new EmailBanner();
		$banner->Delete($_REQUEST['id']);		
	}
	
	redirect("Location: ?action=view");
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Coupon.php');
    require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/EmailBanner.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 64);
	$form->AddField('image', 'Image', 'file', '', 'file');

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$banner = new EmailBanner();
	        $banner->Name = $form->GetValue('name');
			
			if($banner->Add('image')) {
				redirect("Location: ?action=view");
			} else {
				for($i=0; $i<count($banner->Image->Errors); $i++) {
					$form->AddError($banner->Image->Errors[$i], 'image');
				}
			}
		}
	}

	$page = new Page('<a href="?action=view">Banners</a> &gt; Add Banner', 'Add a new banner.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Please enter the banner information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'),$form->GetHTML('name').$form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('image'),$form->GetHTML('image').$form->GetIcon('image'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Banners', 'Listing all email banners.');
	$page->Display('header');
	?>

	<table class="DataTable">
		<thead>
			<tr>
				<th width="99%">Name</th>
				<th>Banner</th>
				<th width="1%"></th>
			</tr>
		</thead>
		<tbody>

			<?php
			$data = new DataQuery(sprintf("SELECT * FROM email_banner ORDER BY Name ASC"));
			if($data->TotalRows > 0) {
				while($data->Row) {
					?>

					<tr>
						<td><?php echo $data->Row['Name']; ?></td>
						<td><img src="<?php echo $GLOBALS['EMAIL_BANNER_IMAGES_DIR_WS'].$data->Row['FileName']; ?>" alt="<?php echo $data->Row['Name']; ?>" /></td>
						<td><a href="javascript:confirmRequest('?action=remove&id=<?php echo $data->Row['EmailBannerID']; ?>', 'Are you sure you wish to remove this item?')"><img src="images/button-cross.gif" alt="Remove" /></a></td>
					</tr>

					<?php
					$data->Next();
				}
			} else {
				?>

				<tr>
					<td align="center" colspan="3">There are no banners available for viewing.</td>
				</tr>

				<?php
			}
			$data->Disconnect();
			?>

		</tbody>
	</table>
	<br />

	<input type="button" class="btn" name="add" value="add banner" onclick="window.self.location.href = '?action=add';" />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}