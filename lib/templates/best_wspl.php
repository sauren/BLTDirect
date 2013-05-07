<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

$maxItems = isset($subProduct) ? 9 : 10;

include('../lib/cache/best.php');

$products = $cacheData;
$productsCount = count($products);

if($productsCount > 0) {
	?>

	<div class="grid">
		<h2>Best Sellers</h2>
		<div class="grid-product">
		
			<?php
			if(isset($subProduct)) {
				include('../lib/templates/productPanel_wspl.php');
			}
			
			$limit = ($productsCount > $maxItems) ? $maxItems : $productsCount;
			$index = 0;

			foreach($products as $productId) {
				if($index >= $limit) {
					break;
				}
				
				$subProduct = new Product();

				if($subProduct->Get($productId)) {
					$gridClass = '';
					
					include('../lib/templates/productPanel_wspl.php');
				}

				$index++;
			}
			?>
			
			<div class="clear"></div>
			
		</div>
	</div>
	
	<?php
}