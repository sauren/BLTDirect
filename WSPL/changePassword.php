<?php
	require_once('../lib/common/appHeadermobile.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	$session->Secure();

	$isOrg = $session->Customer->Contact->HasParent;
	$direct = "accountcenter.php";
	if(param('direct')) {
		$form = new Form('gateway.php');
		if (preg_match("/{$form->RegularExp['link_relative']}/", param('direct'))) {
			$direct = htmlspecialchars(param('direct'));
		}
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->Icons['valid'] = '';
	$form->AddField('action', 'Action', 'hidden', 'register', 'alpha', 8, 8);
	$form->AddField('direct', 'Direct', 'hidden', $direct, 'link_relative', 1, 255);
	$form->SetValue('direct', $direct);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$form->AddField('oldpassword', 'Current Password', 'password', '', 'password', 6, 100, true, 'tabindex="10"');
	$form->AddField('password', 'New Password', 'password', '', 'password', PASSWORD_LENGTH_CUSTOMER, 100, true, 'tabindex="11"');
	$form->AddField('confirmPassword', 'Confirm New Password', 'password',  '', 'password', PASSWORD_LENGTH_CUSTOMER, 100, true, 'tabindex="12"');

	$confirmPassError  =  "";

	if(strtolower(param('confirm')) == "true"){
		$form->Validate();
		if(trim(sha1($form->GetValue('oldpassword'))) != trim($session->Customer->GetPassword())){
			$form->AddError('Your Current Password was not correct.', 'oldpassword');
		}
		if($form->GetValue('password') != $form->GetValue('confirmPassword')){
			$form->AddError('Confirm Password is not the same as Password.', 'confirmPassword');
			$confirmPassError = "Is not the same as your Password.";
		}
		if($form->GetValue('oldpassword') == $form->GetValue('password')){
			$form->AddError('Your New Password cannot be the same as your Current Password', 'password');
		}

		if($form->Valid){
			$session->Customer->SetPassword($form->GetValue('password'));
			$session->Customer->Update();
		 
			redirect("Location: " . $form->GetValue('direct'));
		}
	}
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Change My Security Settings</span></div>
<div class="maincontent">
<div class="maincontent1">
              <div id="orderConfirmation">
			<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a> <?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a> | <a href="?action=logout">Logout</a></p>			</div><?php
				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}
				echo $form->Open();
				echo $form->GetHtml('action');
				echo $form->GetHtml('confirm');
				echo $form->GetHtml('direct');
			?>
              <table width="100%" cellspacing="0" class="form">
                <tr>
                  <th colspan="2">Your Security Information</th>
                </tr>
				<tr>
                  <td><?php echo $form->GetLabel('oldpassword'); ?> (<?php echo PASSWORD_LENGTH_CUSTOMER ?> - 12 Alphanumeric Characters) <br />
                  	<small>(If you have been sent a reset password by email use that as your current password)</small>
                  </td></tr>
                  <tr>
                  <td><?php echo $form->GetHtml('oldpassword'); ?> <?php echo $form->GetIcon('oldpassword'); ?></td>
                </tr>
                <tr>
                  <td><?php echo $form->GetLabel('password'); ?> (<?php echo PASSWORD_LENGTH_CUSTOMER ?> - 12 Alphanumeric Characters) <br />
                  </td></tr>
                  <tr>
                  <td><?php echo $form->GetHtml('password'); ?> <?php echo $form->GetIcon('password'); ?></td>
                </tr>
                <tr>
                  <td><?php echo $form->GetLabel('confirmPassword'); ?> <br />
                  </td></tr>
                  <tr>
                  <td><?php echo $form->GetHtml('confirmPassword'); ?> <?php echo $form->GetIcon('confirmPassword')  . " ". $confirmPassError; ?></td>
                </tr>
              </table>
              <p>&nbsp;              </p>
              <p>
                <input name="Update" type="submit" class="submit" id="Update" value="Update" tabindex="13" />
              </p>
              <p><?php echo $form->Close(); ?></p>
             
              </div>
			</div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>