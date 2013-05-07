<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Enquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EnquiryLine.php');

$session->Secure();

$months = 24;
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Return Online</span></div>
<div class="maincontent">
<div class="maincontent1">
			<div id="orderConfirmation">
				<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returns.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
			</div>
			<p>You can start a return process by selecting one of your following recent despatches.</p>

			<?php
			if($action == 'returned') {
				$bubble = new Bubble('Return Raised', 'Your online return has been raised. Please be patient while we process your request. We aim to be back to you within 3 working hours.');

				echo $bubble->GetHTML();
				echo '<br />';			
			}
			?>

			<table cellspacing="0" class="myAccountOrderHistory">
				<tr>
					<th>Number</th>
					<th>Delivery Address</th>
					<th>Order</th>
					<th>Postage</th>
					<th style="text-align: right;">Despatched</th>
					<th style="text-align: right;">Your Expected Date</th>
					<th width="1%">&nbsp;</th>
				</tr>

				<?php
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

				$data = new DataQuery(sprintf("SELECT d.*, p.Postage_Title, p.Postage_Days FROM despatch AS d INNER JOIN orders AS o ON o.Order_ID=d.Order_ID INNER JOIN postage AS p ON p.Postage_ID=o.Postage_ID INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE c.Contact_ID IN (%s) ORDER BY d.Despatch_ID DESC", implode(', ', $contacts)));
				if($data->TotalRows > 0) {
					while($data->Row) {
						$siteAddress = array();

						if(!empty($data->Row['Despatch_Address_1'])) {
							$siteAddress[] = $data->Row['Despatch_Address_1'];
						}

						if(!empty($data->Row['Despatch_Address_2'])) {
							$siteAddress[] = $data->Row['Despatch_Address_2'];
						}

						if(!empty($data->Row['Despatch_City'])) {
							$siteAddress[] = $data->Row['Despatch_City'];
						}

						$thresholdMin = strtotime($data->Row['Despatched_On']) + ($data->Row['Postage_Days'] * 2 * 86400);
						$thresholdMax = mktime(date('H'), date('i'), date('s'), date('m')-$months, date('d'), date('Y'));
						?>

						<tr>
							<td><a href="javascript:popUp('despatch.php?despatchid=<?php echo $data->Row['Despatch_ID']; ?>', 800, 600);"><?php echo $data->Row['Despatch_ID']; ?></a></td>
							<td><?php echo implode(', ', $siteAddress); ?></td>
							<td><a href="orders.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_ID']; ?></a></td>
							<td><?php echo $data->Row['Postage_Title']; ?></td>
							<td align="right"><?php echo date('jS M Y', strtotime($data->Row['Despatched_On'])); ?></td>
							<td align="right"><?php echo date('jS M Y', strtotime($data->Row['Despatched_On']) + ($data->Row['Postage_Days'] * 86400)); ?></td>
							<td nowrap="nowrap">
								<?php
								if(($thresholdMin < time()) && ($thresholdMax < strtotime($data->Row['Despatched_On']))) {
									?>

									<input class="submit" type="button" value="Raise Return" onclick="window.self.location.href = 'returnonline.php?despatchid=<?php echo $data->Row['Despatch_ID']; ?>';" />

									<?php
								}
								?>

							</td>
						</tr>

						<?php
						$data->Next();
					}
				} else {
					?>

					<tr>
						<td colspan="7" align="center">There are no items available for viewing.</td>
				  </tr>

				  <?php
				}
				$data->Disconnect();
			?>

			</table>
</div>
</div>
<?php 
include("ui/footer.php");
include('lib/common/appFooter.php'); ?>