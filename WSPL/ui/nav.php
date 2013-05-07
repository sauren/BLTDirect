<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="Keywords" content="light bulbs, light bulb, lightbulbs, lightbulb, lamps, fluorescent, tubes, osram, energy saving, sylvania, philips, ge, halogen, low energy, metal halide, candle, dichroic, gu10, projector, blt direct" />
<meta name="Description" content="We specialise in supplying lamps, light bulbs and fluorescent tubes, Our stocks include Osram,GE, Sylvania, Omicron, Pro lite, Crompton, Ushio and Philips light bulbs, " />	
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta content='width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;' name='viewport' />
<meta name="viewport" content="width=device-width, maximum-scale=1.0", user-scalable=no />
<meta name="viewport" content="width=device-width, initial-scale=1", maximum-scale=1, user-scalable=0 />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<title>BLT Direct</title>
<link rel="shortcut icon" href="favicon.ico" />
<link rel="stylesheet" type="text/css" href="css/default.css" />
<link rel="stylesheet" type="text/css" href="css/lightbulbs.css" media="screen" />
<link rel="stylesheet" type="text/css" href="css/new.css" />
<link rel="stylesheet" type="text/css" href="css/login.css" />
<link rel="stylesheet" type="text/css" href="css/Filter.css" />
<link rel="stylesheet" type="text/css" href="css/Navigation.css" />
<link rel="stylesheet" type="text/css" href="css/Search.css" />
<link rel="stylesheet" type="text/css" href="css/jquery.fancybox.css" />
<link rel="stylesheet" href="css/slimbox.css" type="text/css" media="screen" />
<script language="javascript" type="text/javascript" src="js/generic.js"></script>
<script language="javascript" type="text/javascript" src="js/evance_api.js"></script>
<script language="javascript" type="text/javascript" src="js/mootools.js"></script>
<script language="javascript" type="text/javascript" src="js/evance.js"></script>
<script language="javascript" type="text/javascript" src="js/bltdirect.js"></script>
<script language="javascript" type="text/javascript" src="js/tabs_new.js"></script>
<script language="javascript" type="text/javascript" src="js/fancybox.js"></script>
<script language="javascript" type="text/javascript" src="js/slimbox.js"></script>
<script language="javascript" type="text/javascript" src="js/pcAnywhere.js"></script>
<script language="javascript" type="text/javascript" src="js/swfobject.js"></script>
<script language="javascript" type="text/javascript" src="js/jquery.js"></script>
<script language="javascript" type="text/javascript" src="js/bltdirect/template.js"></script>
<style type="text/css">
		<style type="text/css" media="screen">
		<!--
			* {
				margin:0px;
				padding:0px;
			}
			#Container {
				font-family:Verdana,Arial,Helvetica,sans-serif;
				width:100%;
				margin-left:auto;
				margin-right:auto;
			}
			div.story {
				font-size:11px;
				background-repeat: no-repeat;
				background-position: center center;
				min-height:50px;
			}
			p.paragraph-style-1 {}
			span.character-style-1 {}
			span.table {}
			.column {
				float:left;
				width:50%;
				padding:10px;
			}
			a {
				color:#2A599D;
				text-decoration: none;
			}
			a:hover {
				text-decoration: underline;
			}
			h1 {
				color:#2A599D;
				font-size:16px;
				padding:10px;
			}
			h2 {
				clear:both;
				color:#2A599D;
				font-size:14px;
				padding:10px;
			}
			h3 {
				color:#2A599D;
				font-size:12px;
			}
			h4 {
				color:#2A599D;
				font-size:12px;
			}
			p {
				padding:3px 2px 3px 0px;
			}
			p.back {
				color:#599D2A;
				font-size:12px;
				font-weight: bold;
				padding:10px;
			}
			p.back a {
				color:#9D2A59;
			}
			table {
				margin-bottom:10px;
			}
			.clear {
				clear:both;
			}
		-->
		</style>
</style>
</head>
<body>
<?php 
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CartLine.php');
?>
<div class="container">
<div class="header">
<div class="logo" align="center">
<img src="images/logo.jpg" alt="BLT Logo" />
</div>
</div>
<?php /*?>
<ul>
<li><a href="index.php"><img src="images/home.png" height="27" /><div style="color:#FFF;">Home</div></a></li>
<li><a href="finder.php"><img src="images/search_nav.jpg" height="27" /><div style="color:#FFF;">Bulb Finder</div></a></li>
<li><div><a href="search.php?show=advanced"><img src="images/advance_search1.png" height="27" /><span style="color:#FFF;">Advanced Search</span></a></div></li>
<?php
if($session->IsLoggedIn){
echo sprintf('<li><a href="accountcenter.php" title="Go to My Accounts"><img src="images/login.png" height="27" /><div style="color:#FFF;">My Account</div></a></li>', $_SERVER['PHP_SELF']);
}else {
echo '<li><a href="gateway.php" title="Login or Register with BLT Direct"><img src="images/login.png" height="27" /><div style="color:#FFF;">Login/Register</div></a></li>';
}
?>
<li><a href="cart.php"><img src="images/cart.png" height="27" /><div style="color:#FFF;">Cart</div></a></li>
<li><a href="support.php"><img src="images/contactus.png" height="27" /><div style="color:#FFF;">Contact</div></a></li>
</ul>
<?php */?>
<div class="nav">
<table width="100%" style="vertical-align:middle; margin-top:5px;">
<tr>
<td width="16%"><a href="index.php"><img src="images/home.png" height="27" /></a></td>
<td width="16%"><a href="finder.php"><img src="images/bulbfinder.png" height="27" /></a></td>
<td width="16%"><a href="search.php?show=advanced"><img src="images/a_search.png" height="27" /></a></td>
<?php
if($session->IsLoggedIn){
echo sprintf('<td width="16%"><a href="accountcenter.php" title="Go to My account"><img src="images/login.png" height="27" /></a></td>
', $_SERVER['PHP_SELF']);
}else {
echo '<td width="16%"><a href="gateway.php" title="Login/Regiter in BLT Direct"><img src="images/login.png" height="27" /></a></td>';
}
?>
<?php
if($cart->TotalLines > 0){
?> 
<td width="16%" class="cartlogo"><a href="cart.php" ><span class="notification"><sup><?php echo $cart->TotalLines;?></sup></span></a></td>
<?php } else {?>
<td width="16%"><a href="cart.php"><img src="images/cart.png" height="27" /></a></td>
<?php }?>
<td width="16%"><a href="support.php"><img src="images/contactus.png" height="27" /></a></td>
</tr>
<tr>
<td width="16%" valign="top"><a href="index.php" class="navtext">Home</a></td>
<td width="16%" valign="top"><a href="finder.php" class="navtext">Bulb Finder</a></td>
<td width="16%" valign="top"><a href="search.php?show=advanced" class="navtext">Advanced Search</a></td>
<?php
if($session->IsLoggedIn){
	echo sprintf('<td width="16%" valign="top"><a href="accountcenter.php" class="navtext">My Account</a></td>
', $_SERVER['PHP_SELF']);
}else {
	echo '<td width="16%" valign="top"><a href="gateway.php" class="navtext">Login / Register</a></td>';
}?>

<td width="16%" valign="top"><a href="cart.php" class="navtext">Cart</a></span></td>
<td width="16%" valign="top"><a href="support.php" class="navtext">Contact</a></td>
</tr>
</table>
</div>