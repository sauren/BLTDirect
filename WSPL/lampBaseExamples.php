<?php
require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");?>
	                <?php
					$data = new DataQuery(sprintf("SELECT * FROM article WHERE Article_Category_ID=%d AND Is_Active='Y' ORDER BY Created_On DESC", LAMP_BASE_ARTICLE_CAT));

						while($data->Row) {
							$desc=nl2br(stripslashes($data->Row['Article_Description']));							
							$strrplc=str_replace('width="443" height="101"','width="100%" height="80"',$desc);
							print sprintf('<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">%s</span></div><p>%s</p>', $data->Row['Article_Title'], $strrplc);
							$data->Next();
						}
					$data->Disconnect();						
					?>
<div class="maincontent">
<div class="maincontent1">

					<?php
					$data = new DataQuery(sprintf("SELECT lb.*, psv.Value FROM lamp_base AS lb INNER JOIN product_specification_value AS psv ON psv.Value_ID=lb.Specification_Value_ID ORDER BY lb.Sequence_Number ASC"));
					while($data->Row) {
						?>

						<div class="baseExample" align="center">
							<?php
							if(!empty($data->Row['Image']) && file_exists($GLOBALS['BASE_IMAGES_DIR_FS'].$data->Row['Image'])) {
								echo sprintf('<a href="search.php?filter=%d"><img src="%s%s" alt="%s" border="0" /></a>', $data->Row['Specification_Value_ID'], $GLOBALS['BASE_IMAGES_DIR_WS'], $data->Row['Image'], $data->Row['Name']);
							}
							?>
							<br /><span class="content">
								<p><strong><?php echo $data->Row['Name']; ?></strong><br />View light bulbs with a <?php echo $data->Row['Value']; ?> lamp base.</p>
								<a href="search.php?filter=<?php echo $data->Row['Specification_Value_ID']; ?>" title="Search for <?php echo $data->Row['Name']; ?> Base Light Bulbs"><input type="button" value="  Search" style="background-image:url(images/searchIcon.gif); background-repeat:no-repeat; padding:2px; font-size:12px; padding:3px;" /></a>
							</span>
						</div>
						<?php
						$data->Next();
					}
					$data->Disconnect();
					?>
					<div class="clear"></div>
                    </div>
                    </div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>