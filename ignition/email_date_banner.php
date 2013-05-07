<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailDate.php');

if($action == 'select') {
	$session->Secure(3);
	select();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function select() {
	$date = new EmailDate();

	if(!$date->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: emails.php"));
	}

	if(isset($_REQUEST['bannerid'])) {
		$date->EmailBannerID = $_REQUEST['bannerid'];
		$date->Update();
	}

	redirect(sprintf("Location: email_dates.php?id=%d", $date->EmailID));
}

function view() {
	$date = new EmailDate();

	if(!$date->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: emails.php"));
	}

	$page = new Page(sprintf('<a href="email_profile.php?id=%d">Email Profile</a> &gt; <a href="email_dates.php?id=%d">Edit Dates</a> &gt; Edit Banner', $date->EmailID, $date->EmailID), 'Here you can select the banner for this email date.');
	$page->Display('header');
	?>

	<table class="DataTable">
		<thead>
			<tr>
				<th>&nbsp;</th>
				<th width="99%">Name</th>
				<th>Banner</th>
			</tr>
		</thead>
		<tbody>

			<?php
			$data = new DataQuery(sprintf("SELECT * FROM email_banner ORDER BY Name ASC"));
			if($data->TotalRows > 0) {
				while($data->Row) {
					?>

					<tr <?php echo ($date->EmailBannerID == $data->Row['EmailBannerID']) ? 'style="background-color: #9f9;"' : ''; ?>>
						<td><a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=select&id=<?php echo $date->ID; ?>&bannerid=<?php echo $data->Row['EmailBannerID']; ?>"><img src="images/aztector_5.gif" alt="Select Banner" border="0" /></a></td>
						<td><?php echo $data->Row['Name']; ?></td>
						<td><img src="<?php echo $GLOBALS['EMAIL_BANNER_IMAGES_DIR_WS'].$data->Row['FileName']; ?>" alt="<?php echo $data->Row['Name']; ?>" /></td>
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

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>