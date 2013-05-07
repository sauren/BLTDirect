<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$user = new User();
$user->ID = $GLOBALS['SESSION_USER_ID'];
$user->Get();

$page = new Page(sprintf('Enquiry Feedback for %s', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName))), 'Listing all posts between staff and customer recipients for this enquiry.');
$page->AddToHead('<link rel="stylesheet" type="text/css" href="css/m_enquiries.css" />');
$page->Display('header');
?>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td valign="top">

			<div style="background-color: #f6f6f6; padding: 10px;">
				<p><span class="pageSubTitle">Average Rating</span><br /><span class="pageDescription">Your average stats for a number of periods is shown below.</span></p>

				<?php
				$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count, AVG(Rating) AS Average FROM enquiry WHERE Rating>0 AND Owned_By=%d AND Closed_On>ADDDATE(NOW(), INTERVAL -30 DAY)", mysql_real_escape_string($user->ID)));
				$avg30Days = number_format($data->Row['Average'], 2, '.', '');
				$cnt30Days = number_format($data->Row['Count'], 2, '.', '');
				$data->Disconnect();
				
				$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count, AVG(Rating) AS Average FROM enquiry WHERE Rating>0 AND Owned_By=%d AND Closed_On>ADDDATE(NOW(), INTERVAL -90 DAY)", mysql_real_escape_string($user->ID)));
				$avg90Days = number_format($data->Row['Average'], 2, '.', '');
				$cnt90Days = number_format($data->Row['Count'], 2, '.', '');
				$data->Disconnect();
				
				$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count, AVG(Rating) AS Average FROM enquiry WHERE Rating>0 AND Owned_By=%d", mysql_real_escape_string($user->ID)));
				$avgAllTime = number_format($data->Row['Average'], 2, '.', '');
				$cntAllTime = number_format($data->Row['Count'], 2, '.', '');
				$data->Disconnect();
				?>
				
				<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
					<tr>
						<td width="150"><p><strong>Last 30 Days:</strong></p></td>
						<td><p><?php echo sprintf('<strong style="color: #f00; font-size: 14px;">%s</strong> (calculated from %d ratings.)', $avg30Days, $cnt30Days); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>Last 90 Days:</strong></p></td>
						<td><p><?php echo sprintf('<strong style="color: #f00; font-size: 14px;">%s</strong> (calculated from %d ratings.)', $avg90Days, $cnt90Days); ?></p></td>
					</tr>
					<tr>
						<td><p><strong>All Time:</strong></p></td>
						<td><p><?php echo sprintf('<strong style="color: #f00; font-size: 14px;">%s</strong> (calculated from %d ratings.)', $avgAllTime, $cntAllTime); ?></p></td>
					</tr>
				</table>

			</div>
			<br />
			
			<div style="background-color: #f6f6f6; padding: 10px;">
				<p><span class="pageSubTitle">Customer Comments</span><br /><span class="pageDescription">Comments regarding the transaction of your enquiries are shown below.</span></p>

				<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
					<?php
					$light = true;
					
					$data = new DataQuery(sprintf("SELECT * FROM enquiry WHERE Rating>0 AND Owned_By=%d ORDER BY Rating DESC", mysql_real_escape_string($user->ID)));
					while($data->Row) {
						$rating = number_format($data->Row['Rating'], 0, '', '');
						$ratingImg = '';
	
						for($i=0;$i<$rating;$i++) {
							$ratingImg .= sprintf('<img src="images/enquiry_rating_on.gif" align="absmiddle" height="15" width="16" alt="%d out of 5" />', $rating);
						}
						for($i=$rating;$i<5;$i++) {
							$ratingImg .= sprintf('<img src="images/enquiry_rating_off.gif" align="absmiddle" height="15" width="16" alt="%d out of 5" />', $rating);
						}
						?>
						
						<tr>
							<td>
							
								<div style="background-color: <?php echo ($light) ? '#fff' : '#eee'; ?>; padding: 10px;">
								
									<table cellpadding="0" cellspacing="0" border="0" class="enquiryForm">
										<tr>
											<td width="200"><p><strong>Rating:</strong></p></td>
											<td><p><?php print $ratingImg; ?></p></td>
										</tr>
										<tr>
											<td valign="top"><p><strong>Comment:</strong></p></td>
											<td><p><?php print nl2br($data->Row['Rating_Comment']); ?></p></td>
										</tr>
									</table>	
									
								</div>
								
							</td>
						</tr>
						
						<?php	
						$light = !$light;
											
						$data->Next();
					}
					$data->Disconnect();
					?>
				</table>
						
			</div>
			<br />

		</td>
	</tr>
</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>