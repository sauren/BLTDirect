<?php
	require_once('../lib/common/appHeadermobile.php');
	include("ui/nav.php");
	include("ui/search.php");?>
    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Thank You</span></div>
<div class="maincontent">
<div class="maincontent1">
		<p>Thank you for your enquiry. Your details have been sent to us and we will be in contact with you as soon as possible.</p>
        <p><a href="support.php">Return to Contact Page</a></p>

        <?php
        if(param('newaccount') == 'true') {
			?>

			<h3>Your New Account</h3>
			<p>An account has been created for you and you have been automatically logged in. Your account password has been e-mailed to you and may be changed within your <a href="accountcenter.php">Account Centre</a> through the <a href="changePassword.php">Change Password</a> section.</p>
			<p>To enable us to more effectively fulfil your enquiry please continue to your account centre and complete your online profile.</p>

			<?php
        }
        ?>
<?php
include("ui/footer.php");
 include('../lib/common/appFooter.php'); ?>