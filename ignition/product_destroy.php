<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

$session->Secure(3);

if($action == "destroy"){
	if(isset($_REQUEST['pid']) && is_numeric($_REQUEST['pid'])){
		remove();
		exit;
	} else {
		view();
		exit;
	}
} else {
	view();
	exit;
}

function remove(){
	$product = new Product;
	$product->Delete($_REQUEST['pid']);
	redirect("Location: product_list.php");
	exit;
}

function view(){
	$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Destroy', $_REQUEST['pid'], $_REQUEST['pid']),'');
	$page->Display('header');
?>
		<table class="bubbleInfo">
			<tr>
				<td>
					<p><strong>IMPORTANT!</strong><br />You are about to destroy a product. This will permanently remove this product from the database including its related images, options, specifications, pricing, special offers, components, related products and will remove it from all associated categories.</p>
					<p>Please note that some sections of your site may still use this product or link to it.</p>
					<p>If this product is no longer available or has been superseded you should decommission the product instead of destroying it. This will provide visitors and your staff with useful information if you receive enquiries about this product.</p>
					<p>What would you like to do?</p>
					<input type="button" class="btn" name="decommission" value="decommission" onClick="window.location.href='product_decommission.php?pid=<?php echo $_REQUEST['pid']; ?>';" />
					<input type="button" class="btn" name="destroy" value="destroy" onClick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?action=destroy&pid=<?php echo $_REQUEST['pid']; ?>'" />
				</td>
			</tr>
		</table>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
}
?>
