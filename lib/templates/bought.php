<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CartLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");

$directContinuation = isset($directContinuation) ? $directContinuation : false;

if(isset($_SESSION['Cart']) && ($_SESSION['Cart'] == 'added')) {
	$showAlternatives = false;

	if(isset($_SESSION['CartLineID'])) {
		$subCartLine = new CartLine($_SESSION['CartLineID']);
		
		$subProductRelated = $subCartLine->Product;
		$subProductRelated->GetRelatedByType('Energy Saving Alternative');
		
		if(!empty($subProductRelated->RelatedType['Energy Saving Alternative'])) {
			$showAlternatives = true;
		}
	}
	?>
	
	<script type="text/javascript">
	function hideBought() {
		var e = document.getElementById('cart-bought');
		
		if(e) {
			e.style.display = 'none';	
		}
	}
	</script>
	
	<div class="cart-bought" id="cart-bought">
		<div class="attention">
			<div class="attention-info attention-info-general">
				<div class="cart-bought-arrow">
					<span class="attention-info-title">Product Added</span><br />
					The selected product has been added to your shopping cart.<br /><br />
					
					<input type="button" name="cart" value="View Cart / Checkout" class="button" onclick="window.self.location.href = './cart.php';" />

					<?php
					if($directContinuation) { 
						if(isset($_REQUEST['direct']) && !preg_match('/^([a-z]+):\/\//i', urldecode($_REQUEST['direct']))) {
							echo sprintf('<input type="button" name="continue" value="Continue Shopping" class="button button-grey" onclick="window.self.location.href = \'%s\';" />', $_REQUEST['direct']);
						}
					} else {
						echo sprintf('<input type="button" name="continue" value="Continue Shopping" class="button button-grey" onclick="hideBought();" />');
					}
					?>
				</div>
				
				<?php
				if($showAlternatives) {
					?>
					<div class="cart-bought-switch">
						<span class="attention-info-title cart-bought-switch-title">Why Not Switch to a More Energy Efficient Product?</span><br />
						Exchange with an energy efficient alternative below.
					</div>

					<?php
				}
				?>

				<div class="clear"></div>

				<?php
				if($showAlternatives) {
					$hideSavings = false;
					$hideSwitch = false;
					$hideSpecifications = true;
					?>
					
					<div class="cart-bought-alternatives">
						<table class="list">
							<?php
							foreach($subProductRelated->RelatedType['Energy Saving Alternative'] as $related) {
								$subProduct = new Product($related['Product_ID']);

								if ($cart->MobileDetected) {
									include('lib/mobile/productLine.php');
								} else {
									include('lib/templates/productLine.php');
								}
							}
							?>
						</table>
					</div>	
					
					<?php
					unset($hideSavings);
					unset($hideSwitch);
					unset($hideSpecifications);
				}
				?>

			</div>
		</div>
	</div>
	
	<?php
	unset($_SESSION['Cart']);
	unset($_SESSION['CartLineID']);
}