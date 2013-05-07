<?php require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Affiliates</span></div>
<div class="maincontent">
<div class="maincontent1">
              <?php
				$data = new DataQuery("SELECT * FROM affiliate ORDER BY Title ASC");
				if($data->TotalRows > 0) {
				?>

				<table width="100%" border="0" cellspacing="0" cellpadding="0">


					<?php
						while($data->Row) {
							?>

							<tr>
			                  <td>
			                  <?php
			                  if((strlen($data->Row['Image']) > 0) && (file_exists('images/affiliates/'.$data->Row['Image']))) {
				                  ?>
				                  <a href="<?php print $data->Row['URL']; ?>" target="_blank"><img src="images/affiliates/<?php print $data->Row['Image']; ?>" alt="<?php print $data->Row['Title']; ?>" /></a><br />
				                  <?php
			                  }
			                  ?>			                  </td>
			                  <td><p><a href="<?php print $data->Row['URL']; ?>" target="_blank"><strong><?php print $data->Row['Title']; ?></strong></a><br />
			                  <?php print $data->Row['Description']; ?></p>
			                  <p><a href="<?php print $data->Row['URL']; ?>" target="_blank"><?php print $data->Row['URL']; ?></a></p></td>
			                </tr>

			                <?php
							$data->Next();
						}
					?>

			</table>
			<?php
				$data->Disconnect();
			} else {
					print '<br /><p><strong>There are no affiliates to display.</strong></p>';
			}
			?>
              
              <p class="smallGreyText"><strong>Disclaimer:</strong> <br />
              The affiliates Above are external websites. We have no control over their content and cannot be held liable for any loss of data, copyright infringements, or other problems at these sites.</p>              
</div>
</div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>