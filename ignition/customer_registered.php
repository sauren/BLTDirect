<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$session->Secure(3);

$customer = new Customer($_REQUEST['customerid']);

$page = new Page('Create a New Customer Account', 'You have successfully created a new customer account.  The customers details have been directly emailed to them.');
$page->Display('header');

echo '<p>Click the below button to redirect to the new customers profile.</p>';
echo sprintf('<input type="button" value="view profile" class="btn" onclick="window.self.location.href = \'contact_profile.php?cid=%d\';" />', $customer->Contact->ID);

$page->Display('footer');

require_once('lib/common/app_footer.php');