<?php
require_once('../lib/common/appHeadermobile.php');

$session->Secure();

$showAlert = false;

if($session->Customer->IsSecondaryActive == 'Y') {
	$session->Customer->IsSecondaryActive = 'N';
	$session->Customer->Update();

	$showAlert = true;
}

$contacts = array();

if($session->Customer->Contact->HasParent) {
	$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", $session->Customer->Contact->Parent->ID));
	while($data->Row) {
		$contacts[] = $data->Row['Contact_ID'];
		
		$data->Next();	
	}
	$data->Disconnect();
} else {
	$contacts[] = $session->Customer->Contact->ID;
}
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">My Account</span></div>
<div class="maincontent">
<div class="maincontent1">
<p>Welcome back <?php print $session->Customer->Contact->Person->Name; ?>. You are now logged into your account centre.</p>
			<?php
			if($showAlert) {
				?>

				<table width="100%" border="0" cellpadding="0" cellspacing="0" class="alert">
					<tr>
						<td class="center">
							<br />
							<p><strong>Changes have been made to your account affecting the way in which you are required to log in.</strong></p>
							<p>From now on you will be prompted for your email address and password when logging in. If you forget your password you may request it through our forgotten password facility which will be sent to your chosen email address. Your email address for this account may be changed within your online <a href="profile.php">profile</a>.</p>
							<p>If you require further assistance please do not hesitate to <a href="support.php">contact us</a>.</p>
						</td>
					</tr>
				</table><br />

				<?php
			}
			?>

              <table width="100%" border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
                <tr>
                  <th width="50%">Account Holder </th>
                  <td width="50%"><?php echo $session->Customer->Contact->Person->GetFullName(); ?></td>
                </tr>
                <tr>
                  <th>Account Type </th>
                  <td>
	                  <?php
	                  if($session->Customer->Contact->IsTradeAccount == 'Y') {
	                  	  echo 'Trade Account';
					  } else {
					  	echo $session->Customer->GetAccountType() . ' Account';
					  }
	                  ?>
                  </td>
                </tr>
				<?php
				if($session->Customer->Contact->HasParent){
				?>
				<tr>
                  <th>Business Name</th>
                  <td><?php echo $session->Customer->Contact->Parent->Organisation->Name; ?></td>
                </tr>
				<?php } ?>
                <tr>
                  <th>E-mail Address</th>
                  <td><?php echo $session->Customer->Username; ?></td>
                </tr>
				<?php
				if(strtoupper($session->Customer->IsCreditActive) == 'Y'){
				?>
				<tr>
					<th>Monthly Credit Allowance</th>
					<td>&pound;<?php echo number_format($session->Customer->CreditLimit, 2, '.', ','); ?></td>
				</tr>
				<tr>
					<th>Remaining Allowance This Month</th>
					<td>&pound;<?php echo number_format($session->Customer->GetRemaingAllowance(), 2, '.', ','); ?></td>
				</tr>
				<tr>
					<th>Credit Invoice Terms</th>
					<td><?php echo $session->Customer->CreditPeriod; ?> Days</td>
				</tr>
				<?php } ?>
              </table>
              <br />

			<?php
			if($session->Customer->Contact->IsEmailInvalid == 'Y') {
				echo '<span class="alert"><strong>Invalid Email Address</strong><br />Recent attempts to contact you via your email address have failed.<br />Please review and update, if necessary, your Profile with a valid email address through the below highlighted link or by <a href="./profile.php">clicking here</a>. Please note that this warning may appear for a valid email address if network problems prevent successful submission of an email to your account.</span>';
				echo '<br />';
			}
			
			$items = array();
			$items[] = sprintf('<p><a href="returnorder.php"><strong>Return Online</strong><br />Register Return, Breakage or Not Received</a></p>');
			?>

			<div class="accountCenterLinks">
				<table width="100%">
					<tr>
						<td width="100%" valign="top">

							<?php
							for($i=0; $i<ceil(count($items)/2); $i++) {
								echo $items[$i];
							}
							?>

						</td></tr><tr>
						<td width="100%" valign="top">

							<?php
							for($i=ceil(count($items)/2); $i<count($items); $i++) {
								echo $items[$i];
							}
							?>

						</td>
					</tr>
				</table>

			</div>
			<div class="accountCenterLinks">

			<?php
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM customer_product AS cp INNER JOIN product AS p ON p.Product_ID=cp.Product_ID INNER JOIN customer AS c ON c.Customer_ID=cp.Customer_ID WHERE c.Contact_ID IN (%s)", implode(', ', $contacts)));
			$productCount = $data->Row['Count'];
			$data->Disconnect();
			
			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID WHERE c.Contact_ID IN (%s)", implode(', ', $contacts)));
			$quoteCount = $data->Row['Count'];
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID WHERE c.Contact_ID IN (%s) AND o.Is_Sample='N' AND o.Status NOT IN ('Incomplete', 'Unauthenticated')", implode(', ', $contacts)));
			$orderCount = $data->Row['Count'];
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM invoice AS i INNER JOIN customer AS c ON c.Customer_ID=i.Customer_ID WHERE c.Contact_ID IN (%s)", implode(', ', $contacts)));
			$invoiceCount = $data->Row['Count'];
			$data->Disconnect();

			$items = array();
			
			if($session->Customer->Contact->IsTradeAccount == 'N') {
				$items[] = sprintf('<p><a href="index.php"><strong>My Home Page</strong><br />View our best selling products, your bulbs and any special offers.</a></p>');
				$items[] = sprintf('<p><a href="introduce.php"><strong>Introduce A Friend</strong><br />Introduce your friends to receive discount rewards.</a></p>');
			}
			
			$items[] = sprintf('<p><a href="bulbs.php"><strong>My Bulbs</strong> (%d)<br />View Past Products/Locations | Favourite Products</a></p>', $productCount);
			$items[] = sprintf('<p><a href="quotes.php"><strong>My Quotes</strong> (%d)<br />View New/Open Quotes | List All Past Quotes</a></p>', $quoteCount);
			$items[] = sprintf('<p><a href="orders.php"><strong>My Orders</strong> (%d)<br />View New/Open Orders | List All Past Orders</a></p>', $orderCount);

			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID WHERE c.Contact_ID IN (%s) AND o.Is_Sample='Y'", implode(', ', $contacts)));
			if($data->Row['Count'] > 0) {
				$items[] = sprintf('<p><a href="samples.php"><strong>My Samples</strong> (%d)<br />View New/Open Samples | List All Past Samples</a></p>', $data->Row['Count']);
			}
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM coupon AS co INNER JOIN customer AS c ON co.Owned_By=c.Customer_ID WHERE c.Contact_ID IN (%s)", implode(', ', $contacts)));
			if($data->Row['Count'] > 0) {
				$items[] = sprintf('<p><a href="coupons.php"><strong>My Coupons</strong> (%d)<br />View Current Coupons</a></p>', $data->Row['Count']);
			}
			$data->Disconnect();

			$items[] = sprintf('<p><a href="invoices.php"><strong>My Invoices</strong> (%d)<br />View New Invoices | List All Past Invoices</a></p>', $invoiceCount);

			if($session->Customer->IsAffiliate == 'Y') {
				$items[] = sprintf('<p><a href="affiliate.php"><strong>Affiliate Information</strong><br />View Affiliate Information | List All Affiliate Statistics</a></p>', $invoiceCount);
			}

			$items[] = sprintf('<p><a href="enquiries.php"><strong>Enquiry Centre</strong><br />Contact our enquiry centre for all your sales needs.</a></p>');
			$items[] = sprintf('<p><a href="eNotes.php"><strong>Order Notes</strong><br />Send us a message regarding returns, errors, etc</a></p>');
			$items[] = sprintf('<p><a href="duplicate.php"><strong>Duplicate A Past Order</strong><br />Duplicate New/Open Orders</a></p>');
			$items[] = sprintf('<p><a %s href="profile.php"><strong>My Profile</strong><br />View/Edit my contact details</a></p>', ($session->Customer->Contact->IsEmailInvalid == 'Y') ? 'style="background-color: #FDFFA8;"' : '');

			if($session->Customer->Contact->HasParent){
				$items[] = sprintf('<p><a href="businessProfile.php"><strong>My Business Profile</strong><br />View/Edit my business details </a></p>');
			}
			$items[] = sprintf('<p><a href="changePassword.php"><strong>Change Password</strong><br />Worried about your security, or if you just keep forgetting your password you can change it here.</a></p>');
			$items[] = sprintf('<p><a href="?action=logout"><strong>Logout</strong><br />Logout from your account.</a></p>');			
			?>            
			<table width="100%">
				<tr>
					<td width="100%" valign="top">

						<?php
						for($i=0; $i<ceil(count($items)/2); $i++) {
							echo $items[$i];
						}
						?>

					</td></tr><tr>
					<td width="100%" valign="top">

						<?php
						for($i=ceil(count($items)/2); $i<count($items); $i++) {
							echo $items[$i];
						}
						?>

					</td>
				</tr>
			</table>
              </div>
              </div>
              </div>
         <?php include("ui/footer.php");?>
		 <?php include('../lib/common/appFooter.php'); ?>