<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/QuoteLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$session->Secure();

if(id_param('quoteid')){
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND q.Quote_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID), mysql_real_escape_string(id_param('quoteid'))));
	if($data->Row['Counter'] == 0) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
	$data->Disconnect();
  
	redirect("Location: quote.php?quoteid=" . id_param('quoteid'));
}
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">My Quotes</span></div>
<div class="maincontent">
<div class="maincontent1">
			<div id="orderConfirmation">
						<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a> <?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a> | <a href="?action=logout">Logout</a></p>			</div><p>Below is a list of your recent quotes. Your most recent quotes are displayed first.</p>
	<table cellspacing="0" class="myAccountOrderHistory">
		<tr>
			<th><strong>Quote Date</strong></th>
			<th><strong>Quote Number</strong></th>
			<th><strong>Quoted For</strong></th>
			<th><strong>Quote Total</strong></th>
			<th><strong>Status</strong></th>
		</tr>

        		<?php
		$contacts = array();

		if($session->Customer->Contact->HasParent) {
			$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID)));
			while($data->Row) {
				$contacts[] = $data->Row['Contact_ID'];
				
				$data->Next();	
			}
			$data->Disconnect();
		} else {
			$contacts[] = $session->Customer->Contact->ID;
		}

		$data = new DataQuery(sprintf("SELECT q.*, p2.Name_First, p2.Name_Last FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE c.Contact_ID IN (%s) ORDER BY q.Quote_ID DESC", implode(', ', $contacts)));
		if($data->TotalRows == 0) {
			?>

			<tr>
				<td colspan="5" align="center">There are no quotes available for viewing.</td>
		  </tr>

		  <?php
		} else {
			while($data->Row){
		?>
		 <tr>
				<td><a href="quote.php?quoteid=<?php echo $data->Row['Quote_ID']; ?>"><?php echo cDatetime($data->Row['Quoted_On'], 'longdate'); ?></a></td>
				<td><a href="quote.php?quoteid=<?php echo $data->Row['Quote_ID']; ?>"><?php echo $data->Row['Quote_Prefix'] . $data->Row['Quote_ID']; ?></a></td>
				<td><?php echo trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])); ?></td>
				<td>&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
				<td><?php echo $data->Row['Status']; ?></td>
		  </tr>
		<?php
				$data->Next();
			}
		}
		$data->Disconnect();
		echo "</table>";?>
        </table>
        </div>
        </div>
        <?php include("ui/footer.php");
include('../lib/common/appFooter.php'); ?>