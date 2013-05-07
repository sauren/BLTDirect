<?php
require_once('../lib/common/appHeadermobile.php');
?>
<?php
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Downloads from BLT Direct</span></div>
<div class="maincontent">
<div class="maincontent1">
<!--'		              <p class="breadCrumb"><a href="index.php">Home</a></p>-->
					  <p>Unless specified otherwise all downloads on this page require the Adobe Acrobat reader plugin. If you do not have Adobe Acrobat you can get it from <a href="http://www.adobe.com" target="_blank">www.adobe.com</a></p>

<?php /*?>
					<?php
					if(!empty($GLOBALS['Cache']['Brochure']->Image2->FileName) && file_exists($GLOBALS['BROCHURE_SPREAD_IMAGE_DIR_FS'].$GLOBALS['Cache']['Brochure']->Image2->FileName)) {
						echo sprintf('<p><a href="%s%s" target="_blank"><strong><img src="%s%s" alt="%s" width="200" height="200" hspace="15" align="left" />%s</strong></a><br />Download the latest edition of our Brochure packed with special offers and a discount coupon you can redeem online for any product in our catalogue. Act now before the special offers expire!</p>', $GLOBALS['BROCHURE_DOWNLOAD_DIR_WS'], $GLOBALS['Cache']['Brochure']->Download->FileName, $GLOBALS['BROCHURE_SPREAD_IMAGE_DIR_WS'], $GLOBALS['Cache']['Brochure']->Image2->FileName, $GLOBALS['Cache']['Brochure']->Name, $GLOBALS['Cache']['Brochure']->Name);
						echo '<br />';
					}
					?><?php */?>
					<p><a href="images/brochures/Autumn-Brochure-Download-Page.jpg"><img src="images/brochures/Autumn-Brochure-Download-Page.jpg" alt="" width="200" height="200" hspace="15" align="left" /></a><br />
		            <p><strong><a href="downloads/free_post_label.pdf">Freepost Label</a></strong><br />
					  If you need to send us a light bulb or fluorescent tube for identification or return damaged goods or products packed incorrectly, please download and print our <a href="downloads/free_post_label.pdf">freepost label</a> to send returns to BLT Direct. Please ensure you write your goods return number on the label before posting.</p>


					  <div class="clear"></div>
</div>
</div>
<?php include("ui/footer.php");?>
<?php include('../lib/common/appFooter.php'); ?>
