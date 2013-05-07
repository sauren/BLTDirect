<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ProductCookie.php");

$maxItems = 10;

$cookie = new ProductCookie();

$products = $cookie->GetProducts();
$productsCount = count($products);

if($productsCount > 0) {
	?>

	<div class="grid">
		<h2>Recently Viewed Products</h2>
		
		<div class="grid-product<?php echo !empty($gridClass) ? sprintf(' %s', $gridClass) : ''; ?>">
		
			<?php
			$limit = ($productsCount > $maxItems) ? $maxItems : $productsCount;
			$index = 0;

			foreach($products as $productId) {
				if($index >= $limit) {
					break;
				}

				$subProduct = new Product();

				if($subProduct->Get($productId)) {
					include('../lib/templates/productPanel_wspl.php');
				}

				$index++;
			}
			?>
			<br />
			<div class="clear"></div>
			
		</div>
	</div>
	
	<?php
}