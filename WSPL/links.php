<?php require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Useful Links</span></div>
<div class="maincontent">
<div class="maincontent1">
              <p style="text-align:justify">Ellwood Electrical Ltd are pleased to offer the following useful links. The following links will open in a new web browser window. </p>
              <?php
				$data = new DataQuery("SELECT * FROM link ORDER BY Title ASC");
				if($data->TotalRows > 0) {
				?>

				<table width="100%" border="0" cellspacing="0" cellpadding="0">


					<?php
						while($data->Row) {
							?>

							<tr>
			                  <td>
			                  <?php
			                  if((strlen($data->Row['Image']) > 0) && (file_exists('images/links/'.$data->Row['Image']))) {
				                  ?>
				                  <a href="<?php print $data->Row['URL']; ?>" target="_blank"><img src="images/links/<?php print $data->Row['Image']; ?>" alt="<?php print $data->Row['Title']; ?>" width="100%" /></a><br />
				                  <?php
			                  }
			                  ?>			                  </td>
                              </tr>
                              <tr>
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
					print '<br /><p><strong>There are no links to display.</strong></p>';
			}
			?>
             
              <p  style="text-align:justify;" class="smallGreyText"><strong>Disclaimer:</strong> <br />
              The links Above are external websites. We have no control over their content and cannot be held liable for any loss of data, copyright infringements, or other problems at these sites.</p>
</div>
</div>

<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>