<?php require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Order Notes</span></div>
<div class="maincontent">
<div class="maincontent1">
              <div id="orderConfirmation">
						<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a> <?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a> | <a href="?action=logout">Logout</a></p>			</div><p>Please select an Order you would like to contact us regarding. Below is a list of your recent orders. Your most recent orders are displayed first.</p>
			<table cellspacing="0" class="myAccountOrderHistory">
				<tr>
				 	<th><strong>Order Date</strong></th>
					<th><strong>Order Number</strong></th>
					<th><strong>Ordered By</strong></th>
					<th><strong>Order Total</strong></th>
					<th><strong>Status</strong></th>
				</tr>

			<?php
				$data = new DataQuery(sprintf("SELECT o.*, p2.Name_First, p2.Name_Last from orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND o.Is_Sample='N' AND o.Status NOT IN ('Incomplete', 'Unauthenticated') ORDER BY o.Order_ID DESC", mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID)));
				if($data->TotalRows == 0) {
			?>

			<tr>
				<td colspan="5" class="center">There are no order notes available for viewing.</td>
		  </tr>

		  <?php
		} else {
			while($data->Row){
			?>
			 <tr>
				 	<td><a href="orderNotes.php?oid=<?php echo $data->Row['Order_ID']; ?>"><?php echo cDatetime($data->Row['Ordered_On'], 'longdate'); ?></a></td>
					<td><a href="orderNotes.php?oid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_Prefix'] . $data->Row['Order_ID']; ?></a></td>
					<td><?php echo trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])); ?></td>
					<td>&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
					<td><?php echo $data->Row['Status']; ?></td>
			  </tr>
			<?php
					$data->Next();
				}
		}
				$data->Disconnect();
				echo "</table>";
			?>
            </table>
</div>
</div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>