<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

if($action == 'adddiscount') {
	$session->Secure(3);
	adddiscount();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function adddiscount() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Coupon.php');
    require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/EmailPanel.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/StandardWindow.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'adddiscount', 'alpha', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('coupon', 'Coupon', 'select', '', 'numeric_unsigned', 1, 11);
	$form->Addoption('coupon', '', '');

	$data = new DataQuery(sprintf("SELECT Coupon_ID, Coupon_Ref, Coupon_Title FROM coupon WHERE Is_Active='Y' AND Is_Invisible='N' AND Staff_Only='N' AND Introduced_By=0 ORDER BY Coupon_Title ASC"));
	while($data->Row) {
		$form->Addoption('coupon', $data->Row['Coupon_ID'], sprintf('%s (%s)', $data->Row['Coupon_Title'], $data->Row['Coupon_Ref']));

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$coupon = new Coupon();

			if($coupon->Get($form->GetValue('coupon'))) {
				$image = imagecreatefromgif("../images/email/template/panel/discount.gif");
				$white = imagecolorallocate($image, 255, 255, 255);

				$discountText = sprintf('%d%% DISCOUNT', $coupon->Discount);

				imagestring($image, 5, (imagesx($image) - (9 * strlen($discountText))) / 2, 85, $discountText, $white);
				imagestring($image, 5, 27, 224, $coupon->Reference, $white);

				$fileName = sprintf('discount_%s.jpg', time());

				imagejpeg($image, sprintf('%s%s', $GLOBALS['EMAIL_PANEL_IMAGES_DIR_FS'], $fileName), 100);
				imagedestroy($image);

				$panel = new EmailPanel();
	            $panel->Name = sprintf('Special Offer: %s', $coupon->Name);
				$panel->FileName = $fileName;
				$panel->Link = sprintf('%scart.php?entity=[ENTITY]&action=update&confirm=true&coupon=%s', $GLOBALS['HTTP_SERVER'], $coupon->Reference);
				$panel->Add();
			}

			redirect("Location: ?action=view");
		}
	}

	$page = new Page('<a href="?action=view">Panels</a> &gt; Add Discount Panel', 'Add a new discount panel.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Please enter the discount information');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('coupon'),$form->GetHTML('coupon').$form->GetIcon('coupon'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'?action=view\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$page = new Page('Panels', 'Listing all email panels.');
	$page->Display('header');
	?>

	<table class="DataTable">
		<thead>
			<tr>
				<th width="99%">Name</th>
				<th>Panel</th>
			</tr>
		</thead>
		<tbody>

			<?php
			$data = new DataQuery(sprintf("SELECT * FROM email_panel ORDER BY Name ASC"));
			if($data->TotalRows > 0) {
				while($data->Row) {
					?>

					<tr>
						<td><?php echo $data->Row['Name']; ?></td>
						<td><img src="<?php echo $GLOBALS['EMAIL_PANEL_IMAGES_DIR_WS'].$data->Row['FileName']; ?>" alt="<?php echo $data->Row['Name']; ?>" /></td>
					</tr>

					<?php
					$data->Next();
				}
			} else {
				?>

				<tr>
					<td align="center" colspan="2">There are no panels available for viewing.</td>
				</tr>

				<?php
			}
			$data->Disconnect();
			?>

		</tbody>
	</table>
	<br />

	<input type="button" class="btn" name="adddiscount" value="add discount panel" onclick="window.self.location.href = '?action=adddiscount';" />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}