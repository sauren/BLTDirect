<?php
require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");?>
	<img src="images/popups/logo_blt_1.jpg" width="100%" height="100" />
    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Where is My Country?</span></div>
<div class="maincontent">
<div class="maincontent1">
	<div id="Content">
		<p>Our online shop is configured to calculate shipping costs for those countries we deliver to regularly. </p>
		<p>Your custom is important to us. If your country is not listed on our site please call us on <strong><?php echo Setting::GetValue('telephone_sales_hotline'); ?></strong>.</p>
		<p>When contacting BLT Direct regarding shipping costs please have your full delivery information ready. </p>
        	<div id="Close"><a href="javascript:window.self.close();">Close Window</a></div>
	</div>
    </div>
    </div>
</body>
</html>
<?php include('../lib/common/appFooter.php'); ?>