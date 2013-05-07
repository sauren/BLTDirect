<?php $filename = basename($_SERVER['REQUEST_URI']); ?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">BLT Direct Ltd.</span></div>
<div class="footer">
<ul class="menu">
<li><a href="index.php">Home</a></li>
<!--<li><a class="WhiteLnkSideMenu" href="">Shop By Category</a></li>
<li><a class="WhiteLnkSideMenu" href="">Shop By Brand</a></li>
-->
<li><a href="company.php">About Us</a></li>
<li><a href="deliveryRates.php">Delivery Rates</a></li>
<li><a href="lampBaseExamples.php">Lamp Base Examples</a></li>
<li><a href="energy-saving-bulbs.php">Energy Saving Comparisions</a></li>
<li><a href="lampColourTemperatures.php">Color Temperature Examples</a></li>
<li><a href="unsubscribe.php">Unsubscribe</a></li>
<li><a href="weeedirective.php">WEEE Directive</a></li>
<li><a href="articles.php">Press Realeases/Articles</a></li>
<li><a href="feedback.php">Customer Feedback</a></li>
<li><a href="privacy.php">Privacy Policy</a></li>
<li><a href="terms.php">Terms & Conditions</a></li>
</ul>
</div>
<?php /*?>     <li><a href="index.php">Home</a></li>
<!--	 <li><a href="products.php">Shop By Category</a></li>
	 <li><a href="products.php">Shop By Brand</a></li>-->
	 <li><a href="company.php">About Us</a></li>
     <li><a href="deliveryRates.php">Delivery Rates</a></li>
     <li><a href="lampBaseExamples.php">Lamp Base Examples</a></li>
     <li><a href="energy-saving-bulbs.php">Energy Saving Comparisions</a></li>
     <li><a href="lampColourTemperatures.php">Color Temperature Examples</a></li>
	 <li><a href="unsubscribe.php">Unsubcribe</a></li>
     <li><a href="weeedirective.php">WEEE Directive</a></li>
     <li><a href="articles.php">Press Realeases/Articles</a></li>
     <li><a href="feedback.php">Customer Feedback</a></li>
     <li><a href="privacy.php">Privacy Policy</a></li>
	 <li><a href="terms.php">Terms & Conditions</a></li><br />
<?php */?>    <span><input type="submit" value="Switch to Standard Website" onclick="javascript:window.location.href='<?php echo  $GLOBALS['HTTP_SERVER'] .$filename."?mode=desktop"; ?>'; return false;" /><p><font color="#EC0000">not optimized for mobile browsing</font></p></span>
</div>
</div>