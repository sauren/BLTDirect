<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

$settings = array();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

$data = new DataQuery(sprintf("SELECT Setting_ID FROM settings ORDER BY Property ASC"));
while($data->Row) {
	$setting = new Setting($data->Row['Setting_ID']);
	$settings[$setting->ID] = $setting;

	if($setting->Type == 'boolean') {
		$form->AddField('setting_'.$setting->ID, $setting->Property, 'select', $setting->Value, 'anything', 0, 255, false);
		$form->AddOption('setting_'.$setting->ID, 'true', 'True');
		$form->AddOption('setting_'.$setting->ID, 'false', 'False');
	} else {
		$form->AddField('setting_'.$setting->ID, $setting->Property, 'text', $setting->Value, 'anything', 0, 255, false);
	}

	$data->Next();
}
$data->Disconnect();

if($action == 'update') {
	if($form->Validate()) {
		foreach($settings as $setting) {
			if($setting->Value != $form->GetValue('setting_'.$setting->ID)) {
				$setting->Value = $form->GetValue('setting_'.$setting->ID);
				$setting->Update();
			}
		}

		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
}

$page = new Page('System Settings','Here you can change your systems dynamic settings.');
$page->Display('header');

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
?>

 <table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th>Property</th>
		<th>Value</th>
		<th>Description</th>
		<th>Modified By</th>
	</tr>
 </thead>
 <tbody>

	 <?php
	 foreach($settings as $setting) {
	 	$modifiedBy = 'System Administrator';

	 	if($setting->ModifiedBy > 0) {
	 		$user = new User($setting->ModifiedBy);
	 		$modifiedBy = sprintf("%s %s", $user->Person->Name, $user->Person->LastName);
	 	}
	 	?>

	 	<tr>
	 		<td width="20%" valign="top" nowrap="nowrap"><em><?php print $setting->Property; ?></em></td>
	 		<td width="20%" valign="top"><?php print $form->GetHTML('setting_'.$setting->ID); ?></td>
	 		<td width="40%" valign="top"><?php print $setting->Description; ?>&nbsp;</td>
	 		<td width="20%" valign="top" nowrap="nowrap"><?php print $modifiedBy; ?></td>
	 	</tr>

	 	<?php
	 }
	 ?>

 </tbody>
 </table>

 <br />

 <input type="submit" class="btn" value="update settings" name="update" />

<?php
echo $form->Close();

$page->Display('footer');

require_once('lib/common/app_footer.php');
?>