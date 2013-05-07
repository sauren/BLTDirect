<?php require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Link To Us</span></div>
<div class="maincontent">
<div class="maincontent1">
              <p style="text-align:justify">If you found our site useful or informative please use one of the following links on your site to help other people find us.</p>

              <table  width="100%" cellspacing="0" class="catProducts">
              	<tr>
              		<th width="50%">HTML Code</th>
              		<th width="50%" align="center">Preview</th>
              	</tr>
              	<tr>
              		<td>
						<textarea style="width: 98%;" onclick="select();" rows="4"><a href="<?php print $GLOBALS['HTTP_SERVER']; ?>?trace=linktous" title="Light bulbs from BLT Direct">Light bulbs from BLT Direct</a></textarea>
              		</td>
              		<td align="center">
						<a href="<?php print $GLOBALS['HTTP_SERVER']; ?>" title="Light bulbs from BLT Direct">Light bulbs from BLT Direct</a>
              		</td>
              	</tr>
              	<tr>
              		<td>
              			<textarea style="width: 98%;" onclick="select();" rows="4"><a href="<?php print $GLOBALS['HTTP_SERVER']; ?>?trace=linktous"><img src="<?php print $GLOBALS['HTTP_SERVER']; ?>images/logo_blt_2.gif" alt="Light bulbs from BLT Direct" title="Light bulbs from BLT Direct" width="146" height="57" /></a></textarea>
              		</td>
              		<td align="center">
						<a href="<?php print $GLOBALS['HTTP_SERVER']; ?>"><img src="images/logo_blt_2.gif" alt="Light bulbs from BLT Direct" title="Light bulbs from BLT Direct" width="146" height="57" /></a>
              		</td>
              	</tr>
              </table>
              </div>
</div>


<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>