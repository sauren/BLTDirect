<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Test.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

$test = new Test();

if(!$test->Get($_REQUEST['id'])) {
	redirect(sprintf("Location: tests.php"));
}

$user = new User();
$user->ID = $test->CreatedBy;
$user->Get();

$testCreator = trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName));
$testCreator = (strlen($testCreator) > 0) ? $testCreator : '<em>Unknown</em>';

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Test ID', 'hidden', '', 'numeric_unsigned', 1, 11);

$page = new Page('Test Profile', 'Here you can change your test information.');
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
		<th colspan="5">Test Information</th>
	</tr>
 </thead>
 <tbody>
   <tr>
	 <td>Created On:</td>
	 <td><?php echo cDatetime($test->CreatedOn, 'shortdatetime'); ?></td>
   </tr>
   <tr>
	 <td>Created By:</td>
	 <td><?php echo $testCreator; ?></td>
   </tr>
 </tbody>
</table><br />

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="3">Test Links</th>
	</tr>
 </thead>
 <tbody>
   <tr>
   	 <td width="33%"><a href="test_suppliers.php?id=<?php echo $test->ID; ?>">Edit Suppliers</a></td>
   	 <td width="33%"><a href="javascript:popUrl('test_generate_orders.php?id=<?php echo $test->ID; ?>', 800, 600);">Generate Dummy Orders</a></td>
   	 <td>&nbsp;</td>
   </tr>
 </tbody>
</table>

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');
?>