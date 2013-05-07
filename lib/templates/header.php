<?php session_start();?>
<div id="Top">
	<?php
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		if(!empty($session->Customer->Contact->TradeImage->FileName) && file_exists($GLOBALS['TRADE_IMAGES_DIR_FS'] . $session->Customer->Contact->TradeImage->FileName)) {
			echo sprintf('<img src="%s%s" alt="%s" />', $GLOBALS['TRADE_IMAGES_DIR_WS'], $session->Customer->Contact->TradeImage->FileName, $session->Customer->Contact->Person->GetFullName());
		} else {
			echo sprintf('<div id="TopText">%s<br /><span>Trade Account</span></div>', $session->Customer->Contact->Person->GetFullName());
		}
	} else {
		?>
		<a href="./index.php" title="Light Bulbs, Lamps and Tubes Direct"><img src="./images/logos/logo_blt_2.jpg" class="logo" alt="Light Bulbs, Lamps and Tubes Direct" /></a>
		<?php
	}
	?>
</div>
			
<div id="NavBar">
	<ul>
		<li><a href="./index.php" id="navHome" class="out" title="Light Bulbs, Lamps and Tubes Direct Home Page" onfocus="menu1.onRollOver('navHome');" onmouseover="menu1.onRollOver('navHome');" onmouseout="menu1.onRollOut('navHome');" onblur="menu1.onRollOut('navHome');">Home</a></li>
		<li><a href="./products.php" id="navProducts" class="out" title="Light Bulb, Lamp and Tube Products"  onmouseover="menu1.onRollOver('navProducts');" onmouseout="menu1.onRollOut('navProducts');"  onfocus="menu1.onRollOver('navProducts');" onblur="menu1.onRollOut('navProducts');">Products</a></li>
		<li><a href="./accountcenter.php" id="navAccount" class="out" title="Your BLT Direct Account" onmouseover="menu1.onRollOver('navAccount');" onmouseout="menu1.onRollOut('navAccount');" onfocus="menu1.onRollOver('navAccount');" onblur="menu1.onRollOut('navAccount');">My Account</a></li>
		<li><a href="./information.php" id="navInformation" class="out" title="BLT Direct Information" onmouseover="menu1.onRollOver('navInformation');" onmouseout="menu1.onRollOut('navInformation');">Information</a></li>
		<li><a href="./cookiePolicy.php" id="navCookie" class="out" title="BLT Direct Cookie" onmouseover="menu1.onRollOver('navCookie');" onmouseout="menu1.onRollOut('navCookie');" onfocus="menu1.onRollOver('navCookie');" onblur="menu1.onRollOut('navCookie');">Cookies</a></li>
		<li><a href="./downloads.php" id="navDownloads" class="out" title="BLT Direct Downloads" onmouseover="menu1.onRollOver('navDownloads');" onmouseout="menu1.onRollOut('navDownloads');" onfocus="menu1.onRollOver('navDownloads');" onblur="menu1.onRollOut('navDownloads');">Downloads</a></li>
		<li><a href="./support.php" id="navSupport" class="out" title="BLT Direct Support" onmouseover="menu1.onRollOver('navSupport');" onmouseout="menu1.onRollOut('navSupport');" onfocus="menu1.onRollOver('navSupport');" onblur="menu1.onRollOut('navSupport');">Support</a></li>
		<li><a href="javascript:window.external.AddFavorite('http://www.bltdirect.com','Light Bulbs from BLT Direct');" id="navFavourites" class="out" title="Add BLT Direct to Your Favourites" onmouseover="menu1.onRollOver('navFavourites');" onmouseout="menu1.onRollOut('navFavourites');" onfocus="menu1.onRollOver('navFavourites');" onblur="menu1.onRollOut('navFavourites');">Add To Favourites</a></li>
	</ul>
</div>

<div id="SearchBar">
	<form name="productSearch" id="productSearch" method="get" action="./search.php">
		<label for="search" style="display:none;">Search our product database</label>
		<input type="text" id="search" name="search" value="<?php echo isset($_REQUEST['search']) ? htmlentities($_REQUEST['search']) : ''; ?>" class="txt" placeholder="Search for products" autocomplete="off" />
		<input type="submit" name="GO" value="GO" class="button" />
	</form>

	<a href="./search.php?show=advanced" title="Light Bulbs, Lamps and Tubes Advanced Search">Advanced Search</a>
</div>

<div id="CapTop">
	<div class="curveLeft"></div>
	<div class="curveRight"></div>	
</div>

<ul class="nav">
<?php $url=$_SERVER['REQUEST_URI']; ?>
<li class="mobile"><a href="./wsplmobile<?php echo $url;?>" title="Visit Mobile Site">Mobile Site</a></li>
	<?php
	if($session->IsLoggedIn){
		echo sprintf('<li class="login"><a href="%s?action=logout" title="Logout of Your BLT Direct Account">Logout</a></li>', $_SERVER['PHP_SELF']);
	} else {
		echo '<li class="login"><a href="./gateway.php" title="Login or Register with BLT Direct">Login/Register</a></li>';
	}
	?>
	
	<li class="account"><a href="./accountcenter.php" title="Your BLT Direct Account">My Account</a></li>
	<li class="contact"><a href="./support.php" title="Contact BLT Direct">Contact Us</a></li>
	<li class="help"><a href="./support.php" title="BLT Direct Support">Help</a></li>
</ul>

<?php
if($session->Customer->Contact->IsTradeAccount == 'Y') {
	?>
	
	<div class="phone">
		Sales Hotline: <?php echo Setting::GetValue('telephone_sales_hotline'); ?> <span class="phone-extra">(24 hours a day, 7 days a week)</span><br />
		Customer Service: <?php echo Setting::GetValue('telephone_customer_services'); ?> <span class="phone-extra">(9am-5pm)</span>
	</div>

	<?php
} else {
	?>
	
	<p class="callTag">
		<span style="font-size:1.2em;">Sales Hotline <span class="phone"><span style="font-size:1.2em;"><?php echo Setting::GetValue('telephone_sales_hotline'); ?></span></span></span> <span style="color: #d00;">(24 hours a day, 7 days a week)</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Customer Service <span class="phone"><?php echo  Setting::GetValue('telephone_customer_services'); ?></span> <span style="color: #d00;">(9am-5pm)</span>
	</p>
	
	<?php
}