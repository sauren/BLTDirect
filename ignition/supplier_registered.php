<?php
require_once('lib/common/app_header.php');

$session->Secure(3);

$page = new Page('Create a New Supplier Account', 'You have successfully created a new supplier.  The suppliers details have been directly emailed to them.');
$page->Display('header');
$page->Display('footer');

require_once('lib/common/app_footer.php');
?>
