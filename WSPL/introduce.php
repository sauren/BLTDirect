<?php
	require_once('../lib/common/appHeadermobile.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$session->Secure();

	$coupon = new Coupon();

	$data = new DataQuery(sprintf("SELECT Coupon_ID FROM coupon WHERE Introduced_By=%d", mysql_real_escape_string($session->Customer->ID)));
	if($data->TotalRows > 0) {
		$coupon->Get($data->Row['Coupon_ID']);
	} else {
		$coupon->Reference = $coupon->GenerateReference();
		$coupon->Name = 'Introduce A Friend';
		$coupon->Description = 'Introduce A Friend';
		$coupon->Discount = Setting::GetValue('customer_coupon_discount');
		$coupon->IsFixed = 'N';
		$coupon->OrdersOver = Setting::GetValue('customer_coupon_orders_over');
		$coupon->UsageLimit = Setting::GetValue('customer_coupon_usage_limit');
		$coupon->IsAllProducts = 'Y';
		$coupon->IsActive = 'Y';
		$coupon->StaffOnly = 'N';
		$coupon->UseBand = 0;
		$coupon->IsAllCustomers = 'Y';
		$coupon->ExpiresOn = '0000-00-00 00:00:00';
		$coupon->IntroducedBy = $session->Customer->ID;
		$coupon->Add();
	}
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'send', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('email', 'Friends Email Address', 'text', '', 'email', 1, 255);
	include("ui/nav.php");
	include("ui/search.php");?>
    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Introduce A Friend</span></div>
<div class="maincontent">
<div class="maincontent1">
			<div id="orderConfirmation">
				<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
			</div>
			<p>We would like to thank you for your business to date with BLT Direct, now you can introduce friends and contacts to us and both benefit from the experience. Simply let your friends have the introduction coupon below which will discount their order by <?php print $coupon->Discount; ?>%. For each successfully placed order using your coupon you will receive discount reward proportional to that of your friends.</p>

			<p style="font-weight: bold; color: #0c0; font-size: 18px;"><?php print $coupon->Reference; ?></p>

			<h3>Discount Rewarded</h3>
			<p>You will receive the following discount off your next order. Any remaining discount reward will be carried over to subsequent orders.</p>
			<p style="font-weight: bold; color: #0c0; font-size: 18px;">&pound;<?php print number_format($session->Customer->AvailableDiscountReward, 2, '.', ','); ?></p>

			<h3>Introduced Friends</h3>
			<?php
			$data = new DataQuery(sprintf("SELECT Customer_ID, Ordered_On FROM orders WHERE Coupon_ID=%d AND Status='Despatched'", mysql_real_escape_string($coupon->ID)));
			if($data->TotalRows > 0) {
				echo '<p>Friends who have successfully placed orders using your introductory coupon:</p>';
				echo '<table cellspacing="0" class="catProducts">';
				echo '<tr>';
				echo '<th width="50%">Friend</th>';
				echo '<th width="50%">Ordered On</th>';
				echo '</tr>';

				while($data->Row) {
					$customer = new Customer($data->Row['Customer_ID']);
					$customer->Contact->Get();

					echo '<tr>';
					echo sprintf('<td>%s %s</td>', $customer->Contact->Person->Name, $customer->Contact->Person->LastName);
					echo sprintf('<td>%s</td>', cDatetime($data->Row['Ordered_On'], 'longdate'));
					echo '</tr>';

					$data->Next();
				}

				echo '</table>';
			} else {
				echo '<p>Non of your friends have used your discount coupon.</p>';
			}
			$data->Disconnect();
			?>
	</div>
</div>
<?php include('../lib/common/appFooter.php'); ?>