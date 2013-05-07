<?php
require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");?>
<?php include("product_menu.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Best Sellers</span></div>
<?php include('best_products.php'); ?>
<?php include("ui/footer.php");
require_once('../lib/common/appFooter.php');?>