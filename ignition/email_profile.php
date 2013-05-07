<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Email.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailBanner.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailDate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Campaign.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignEvent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

$email = new Email();

if(!$email->Get($_REQUEST['id'])) {
	redirect(sprintf("Location: emails.php"));
}

$email->GetDates();

$campaign = new Campaign($email->CampaignID);

$user = new User();
$user->ID = $email->CreatedBy;
$user->Get();

$emailCreator = trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName));
$emailCreator = (strlen($emailCreator) > 0) ? $emailCreator : '<em>Unknown</em>';

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Email ID', 'hidden', '', 'numeric_unsigned', 1, 11);

if(isset($_REQUEST['generate'])) {
	$invalid = false;

	for($i=0; $i<count($email->Date); $i++) {
		$email->Date[$i]->GetPanels();

		if(($email->Date[$i]->EmailBannerID == 0) || ($email->Date[$i]->EmailProductPoolID == 0) || (count($email->Date[$i]->Panel) != 3)) {
			$invalid = true;
			break;
		}
	}

	if($invalid) {
		$form->AddError('Cannot generate invalid dates');
	}

	if($form->Valid) {
		if(count($email->Date) > 0) {
			for($i=0; $i<count($email->Date); $i++) {
				$email->Date[$i]->GetPanels();

				$banner = new EmailBanner($email->Date[$i]->EmailBannerID);

				$event = new CampaignEvent();
				$event->Campaign->ID = $campaign->ID;
				$event->Type = 'E';
				$event->Title = $banner->Name;
				$event->Subject = $email->Date[$i]->Subject;
				$event->IsAutomatic = 'Y';
				$event->Scheduled = strtotime($email->Date[$i]->Date);
				$event->Template = $email->GetTemplate($email->Date[$i]->ID);
				$event->Add();
			}

			redirect(sprintf("Location: campaign_profile.php?id=%d", $campaign->ID));
		}
	}
}

$page = new Page('Email Profile', 'Here you can change your email information.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="5">Email Information</th>
	</tr>
 </thead>
 <tbody>
   <tr>
     <td>Campaign:</td>
     <td><a href="campaign_profile.php?id=<?php echo $campaign->ID; ?>"><?php echo $campaign->Title; ?></a></td>
   </tr>
   <tr>
	 <td>Created On:</td>
	 <td><?php echo cDatetime($email->CreatedOn, 'shortdatetime'); ?></td>
   </tr>
   <tr>
	 <td>Created By:</td>
	 <td><?php echo $emailCreator; ?></td>
   </tr>
 </tbody>
 </table><br />

 <table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="3">Email Links</th>
	</tr>
 </thead>
 <tbody>
   <tr>
   	 <td width="50%"><a href="email_templates.php?id=<?php echo $email->ID; ?>">Edit Template</a></td>
   	 <td width="50%"><a href="email_dates.php?id=<?php echo $email->ID; ?>">Edit Dates</a></td>
   </tr>
 </tbody>
</table><br />

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="4">Dates</th>
	</tr>
 </thead>
 <tbody>

	<?php
	if(count($email->Date) > 0) {
		foreach($email->Date as $dateItem) {
			$dateItem->GetPanels();

			$banner = new EmailBanner($dateItem->EmailBannerID);
			?>

		 	<tr <?php echo (($dateItem->EmailBannerID == 0) || ($dateItem->EmailProductPoolID == 0) || (count($dateItem->Panel) != 3)) ? 'style="background-color: #f99;"' : ''; ?>>
		 		<td width="1%"><a href="email_dates.php?action=update&id=<?php echo $dateItem->ID; ?>">#<?php echo $dateItem->ID; ?></a></td>
		 		<td><?php echo $banner->Name; ?>&nbsp;</td>
		 		<td><?php echo $dateItem->Subject; ?>&nbsp;</td>
		 		<td align="right"><?php echo cDatetime($dateItem->Date, 'shortdate'); ?>&nbsp;</td>
		 	</tr>

	 		<?php
		}
	} else {
		?>

		<tr>
			<td align="center" colspan="2">There are no dates available for viewing.</td>
		</tr>

		<?php
	}
	?>

 </tbody>
</table><br />

<?php
if(count($email->Date) > 0) {
	$nextDateId = 0;

	foreach($email->Date as $dateItem) {
		if(strtotime($dateItem->Date) > time()) {
			$dateItem->GetPanels();

			if(($dateItem->EmailBannerID > 0) && ($dateItem->EmailProductPoolID > 0) && (count($dateItem->Panel) == 3)) {
				$nextDateId = $dateItem->ID;
				break;
			}
		}
	}

	if($nextDateId > 0) {
		$date = new EmailDate($nextDateId);
		$template = new EmailTemplate();

		if($template->Get($email->EmailTemplateID)) {
			?>

			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
			 <thead>
			 	<tr>
					<th>Preview (<?php echo cDatetime($date->Date, 'shortdate'); ?>)</th>
				</tr>
			 </thead>
			 <tbody>
			 	<tr>
			 		<td style="padding: 0; background-color: #fff;"><iframe src="email_profile_preview.php?id=<?php echo $email->ID; ?>" width="100%" height="500" frameborder="0"></iframe></td>
			 	</tr>
			 </tbody>
			</table><br />

			<?php
		}
	}
}
?>

<div style="text-align: right;">
	<input class="btn" type="submit" name="generate" value="generate" />
</div>

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');