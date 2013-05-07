<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailDate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailPanelAssoc.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$session->Secure(3);

$date = new EmailDate();

if(!$date->Get($_REQUEST['id'])) {
	redirect(sprintf("Location: emails.php"));
}

$panels = array();

$data = new DataQuery(sprintf("SELECT * FROM email_panel ORDER BY Name ASC"));
while($data->Row) {
	$panels[] = $data->Row;

	$data->Next();
}
$data->Disconnect();

$associations = array();

$data = new DataQuery(sprintf("SELECT EmailPanelID FROM email_panel_assoc WHERE EmailDateID=%d", mysql_real_escape_string($date->ID)));
while($data->Row) {
	$associations[$data->Row['EmailPanelID']] = true;

	$data->Next();
}
$data->Disconnect();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Email Date ID', 'hidden', '', 'numeric_unsigned', 1, 11);

foreach($panels as $panel) {
	$form->AddField('panel_' . $panel['EmailPanelID'], $panel['Name'] . ' Panel', 'checkbox', isset($associations[$panel['EmailPanelID']]) ? 'Y' : 'N', 'boolean', 1, 1, false);
}

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
	if($form->Validate()) {
		$selected = array();

		foreach($panels as $panel) {
			if($form->GetValue('panel_' . $panel['EmailPanelID']) == 'Y') {
				$selected[$panel['EmailPanelID']] = true;
			}
		}

		if(count($selected) != 3) {
			$form->AddError('You must select a total of 3 panels.');
		}

		if($form->Valid) {
			new DataQuery(sprintf("DELETE FROM email_panel_assoc WHERE EmailDateID=%d", mysql_real_escape_string($date->ID)));

			$assoc = new EmailPanelAssoc();

			foreach($panels as $panel) {
				if(isset($selected[$panel['EmailPanelID']])) {
					$assoc->EmailDateID = $date->ID;
					$assoc->EmailPanelID = $panel['EmailPanelID'];
					$assoc->Add();
				}
			}

			redirect(sprintf("Location: email_dates.php?id=%d", $date->EmailID));
		}
	}
}

$page = new Page(sprintf('<a href="email_profile.php?id=%d">Email Profile</a> &gt; <a href="email_dates.php?id=%d">Edit Dates</a> &gt; Edit Panels', $date->EmailID, $date->EmailID), 'Here you can select the panels for this email date.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');
?>

<table class="DataTable">
	<thead>
		<tr>
			<th>&nbsp;</th>
			<th width="99%">Name</th>
			<th>Panel</th>
		</tr>
	</thead>
	<tbody>

		<?php
		if(count($panels) > 0) {
			foreach($panels as $panel) {
				?>

				<tr <?php echo isset($associations[$panel['EmailPanelID']]) ? 'style="background-color: #9f9;"' : ''; ?>>
					<td><?php echo $form->GetHTML('panel_' . $panel['EmailPanelID']); ?></td>
					<td><?php echo $panel['Name']; ?></td>
					<td><img src="<?php echo $GLOBALS['EMAIL_PANEL_IMAGES_DIR_WS'].$panel['FileName']; ?>" alt="<?php echo $panel['Name']; ?>" /></td>
				</tr>

				<?php
			}
		} else {
			?>

			<tr>
				<td align="center" colspan="3">There are no panels available for viewing.</td>
			</tr>

			<?php
		}
		?>

	</tbody>
</table><br />

<input type="submit" class="btn" name="update" value="update" />

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>