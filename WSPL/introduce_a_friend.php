<?php require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Introduce A Friend</span></div>
<div class="maincontent">
<div class="maincontent1">
			<p style="text-align:justify">When you have become a customer of BLT Direct you are able to benefit from a reward structure by introducing friends and contacts to us. Within your <a href="./accountcenter.php" style="color: #334499;"><strong>Account Centre</strong></a> and on each of your despatch notes will be your unique coupon code.</p>
			<p style="text-align:justify">Simply let your friends have the introduction coupon issued to you within your account centre or on your customer despatch note which will discount their order by <?php print Setting::GetValue('customer_coupon_discount'); ?>%. For each successfully placed order using your unique code you will receive discount reward proportional to that of your friends which you can redeem next time you order from us.</p>

			<br />
			<div style="text-align: center;"><img style="border:1px solid #ccc;" src="images/introduce_a_friend.jpg" alt="Introduce A Friend" width="100%"  height="320"/></div>
            </div></br>
</div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>