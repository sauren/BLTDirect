<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Catalogue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

$catalogue = new Catalogue();

if(!$catalogue->Get($_REQUEST['id'])) {
	redirect(sprintf("Location: catalogues.php"));
}

$user = new User();
$user->ID = $catalogue->CreatedBy;
$user->Get();

$catalogueCreator = trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName));
$catalogueCreator = (strlen($catalogueCreator) > 0) ? $catalogueCreator : '<em>Unknown</em>';

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Catalogue ID', 'hidden', '', 'numeric_unsigned', 1, 11);

$page = new Page('Catalogue Profile', 'Here you can change your catalogue information.');
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
		<th colspan="5">Catalogue Information</th>
	</tr>
 </thead>
 <tbody>
   <tr>
	 <td>Title:</td>
	 <td><?php echo $catalogue->Title; ?></td>
   </tr>
   <tr>
	 <td>Created On:</td>
	 <td><?php echo cDatetime($catalogue->CreatedOn, 'shortdatetime'); ?></td>
   </tr>
   <tr>
	 <td>Created By:</td>
	 <td><?php echo $catalogueCreator; ?></td>
   </tr>
 </tbody>
</table><br />

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="3">Catalogue Links</th>
	</tr>
 </thead>
 <tbody>
   <tr>
   	 <td width="33%"><a href="catalogue_description.php?action=update&id=<?php echo $catalogue->ID; ?>">Edit Description</a></td>
   	 <td width="33%"><a href="catalogue_sections.php?id=<?php echo $catalogue->ID; ?>">Edit Sections</a></td>
   	 <td width="33%"><a href="catalogue_options.php?action=update&id=<?php echo $catalogue->ID; ?>">Edit Options</a></td>
   </tr>
   <tr>
   	 <td><a href="javascript:popUrl('catalogue_preview.php?id=<?php echo $catalogue->ID; ?>', 800, 600);">Preview Catalogue</a></td>
   	 <td><a href="javascript:popUrl('catalogue_export.php?id=<?php echo $catalogue->ID; ?>', 800, 200);">Export Catalogue</a></td>
   	 <td>&nbsp;</td>
   </tr>
 </tbody>
</table>

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>