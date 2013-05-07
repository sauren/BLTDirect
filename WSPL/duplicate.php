<?php require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Duplicate A Past Order</span></div>
<div class="maincontent">
<div class="maincontent1">
              <div id="orderConfirmation">
			<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a> <?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a> | <a href="?action=logout">Logout</a></p>
				<div id="return-info">
				    <p id="return-info-header">
				    Duplication Instructions
				    </p>
				    <ol>
				      <li>Select the order you wish to repeat by clicking on the circle next to it.</li>
				      <li>Alter the specific product quantities from each of those listed, or remove a product line entirely from duplication by unselecting its checkbox.</li>
				      <li>Click the Proceed button to duplicate the order details.</li>
				      <li>Prices shown are current prices and subject to change and discounts etc.</li>
				    </ol>
				  </div>

				  <form action="duplicate.php" method="post">

				 <table class="center" style="background-color:#eee; border:1px solid #ddd; width: 95%;" cellpadding="0" cellspacing="0">
				 	<tr>
				 		<td width="100%" valign="top">

							   <table cellspacing="0" class="myAccountOrderHistory">
					            <tr>
					              <th>&nbsp;</th>
					              <th><strong>Order Date</strong></th>
					              <th><strong>Order Number</strong></th>
								  <th><strong>Ordered By</strong></th>
					              <th style="text-align: right;"><strong>Order Total</strong></th>
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
			
					            $data = new DataQuery(sprintf("SELECT o.*, p2.Name_First, p2.Name_Last FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE c.Contact_ID IN (%s) AND o.Is_Sample='N' AND o.Status NOT IN ('Incomplete', 'Unauthenticated') ORDER BY o.Order_ID DESC", implode(', ', $contacts)));
					            if($data->TotalRows == 0) {
								?>

								<tr>
									<td colspan="5" class="center">There are no orders available for viewing.</td>
							  </tr>

							  <?php
							} else {
								while($data->Row) {
					            	?>

					            	<tr>
						                <td><input onclick="getDuplicationLines(<?php print $data->Row['Order_ID']; ?>)" type="radio" name="orderid" value="<?php print $data->Row['Order_ID']; ?>" /></td>
										<td><?php print cDatetime($data->Row['Ordered_On'], 'shortdate'); ?></td>
										<td><?php print $data->Row['Order_Prefix'] . $data->Row['Order_ID']; ?></td>
										<td><?php echo trim(sprintf('%s %s', $data->Row['Name_First'], $data->Row['Name_Last'])); ?></td>
										<td align="right">&pound;<?php print number_format($data->Row['Total'], 2, '.', ','); ?></td>
									</tr>

					            	<?php

					            	$data->Next();
					            }
							}
					            $data->Disconnect();
					            ?>

					          </table>


				 		</td>
				 		<td width="50%" valign="top" id="lines"></td>
				 	</tr>
				 </table>

				 </form>










</div>
</div>





<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>