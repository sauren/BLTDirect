<?php
require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');


$session->Secure();?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Return Online</span></div>
<div class="maincontent">
<div class="maincontent1">
					<p>You can start a return process by selecting one of your following recent despatches.</p>

					<table cellspacing="0" class="myAccountOrderHistory">
						<tr>
							<th>Order Date</th>
							<th>Order Number</th>
							<th>Ordered By</th>
							<th>Order Total</th>
							<th>Status</th>
							<th width="1%">&nbsp;</th>
						</tr>

						<?php
						$data = new DataQuery(sprintf("SELECT o.*, CONCAT_WS(' ', p2.Name_First, p2.Name_Last) AS Name FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID INNER JOIN person AS p2 ON p2.Person_ID=n.Person_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND o.Is_Sample='N' AND o.Status IN ('Partially Despatched', 'Despatched') ORDER BY o.Order_ID DESC",  $session->Customer->Contact->Parent->ID, $session->Customer->Contact->ID));
						if($data->TotalRows > 0) {
							while($data->Row) {
								?>

								<tr>
									<td><?php echo cDatetime($data->Row['Ordered_On'], 'longdate'); ?></td>
									<td><a href="orders.php?orderid=<?php echo $data->Row['Order_ID']; ?>"><?php echo $data->Row['Order_Prefix'] . $data->Row['Order_ID']; ?></a></td>
									<td><?php echo $data->Row['Name']; ?></td>
									<td>&pound;<?php echo number_format($data->Row['Total'], 2, '.', ','); ?></td>
									<td><?php echo $data->Row['Status']; ?></td>
									<td nowrap="nowrap"><input class="submit" type="button" value="Raise Return/Query" onclick="window.self.location.href = 'return.php?orderid=<?php echo $data->Row['Order_ID']; ?>';" /></td>
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
 <?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>