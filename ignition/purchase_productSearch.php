<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSearch.php');

$session->Secure(2);

global $cart;
$cart = new Purchase(null,$session);
$cart->PSID = $session->ID;
if($cart->Exists()==false){
	$cart->SetDefaults();
	$cart->Add();
}
if($cart->Warehouse->ID == 0){
	$data = new DataQuery(sprintf("SELECT * FROM warehouse w INNER JOIN users u ON u.Branch_ID = w.Type_Reference_ID WHERE w.Type = 'B' AND u.User_ID = %d",$GLOBALS['SESSION_USER_ID']));
	$cart->Warehouse->ID = $data->Row['Warehouse_ID'];
	$data->Disconnect();
	$cart->Update();
}

$sql = "";
$search = "";
if(isset($_REQUEST['searchString']) && !empty($_REQUEST['searchString'])){
	$string = stripslashes($_REQUEST['searchString']);

	$search = new ProductSearch($_REQUEST['searchString'],'purchase_product.php?pid=','INNER JOIN warehouse_stock w ON w.Product_ID = p.Product_ID',sprintf('AND w.Warehouse_ID = %d',$cart->Warehouse->ID));

}

$page = new Page('Create a New Order Manually', '');
$page->Display('header');
?>
<table width="100%" border="0">
  <tr>
    <td width="250" valign="top"><?php include('./purchase_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top">
			<?php
				// Check for Products in this Category
				if(!empty($search) && $search->Results->TotalRows > 0){
			?>
			<br />
			<table cellspacing="0" class="catProducts">
				<tr>
					<th colspan="2">Search Results for: <?php echo htmlentities(stripslashes($_REQUEST['searchString'])); ?></th>
					<th colspan="2">Price</th>
					</tr>
			<?php
					while($search->Results->Row){

						$productInStock = new DataQuery(sprintf("SELECT * FROM warehouse_stock WHERE Warehouse_ID = %d AND Product_ID = %d", mysql_real_escape_string($cart->Warehouse->ID), mysql_real_escape_string($search->Results->Row['Product_ID'])));
						if($productInStock->TotalRows > 0){

							$prod = new Product($search->Results->Row['Product_ID']);
							?>
								<tr>
									<td><a href="./purchase_product.php?pid=<?php echo $prod->ID; ?>&cat=<?php echo $category->ID; ?>"><?php if(!empty($prod->DefaultImage->Thumb->FileName)){ echo sprintf('<img src="%s%s"  ',$GLOBALS['PRODUCT_IMAGES_DIR_WS'], $prod->DefaultImage->Thumb->FileName);} ?> border="0" width="<?php echo $prod->DefaultImage->Thumb->Width; ?>" height="<?php echo $prod->DefaultImage->Thumb->Height; ?>" alt="Click to View Product Information" /></a>
									<?php echo $products->Row['matches']; ?>
									</td>
									<td>
										<a href="./purchase_product.php?pid=<?php echo $prod->ID; ?>&cat=<?php echo $category->ID; ?>" title="Click for More Information"><strong><?php echo $prod->Name; ?></strong><br />
									  <span class="smallGreyText">QuickFind #: <?php echo $prod->ID; ?>, Part Number: <?php echo $prod->SKU; ?></span></a>				  </td>
									<td align="right" class="price">
										&pound;<?php echo number_format($prod->PriceCurrent, 2, '.', ','); ?><br />
										<span class="smallGreyText">&pound;<?php echo number_format($prod->PriceCurrentIncTax, 2, '.', ','); ?> inc.  VAT</span>
									</td>
									<td><?php echo $prod->GetBuyIt(0,true,'purchase_customise.php'); ?></td>
								</tr>
							<?php
						}

						$productInStock->Disconnect();

						$search->Results->Next();
					}
			?>
			</table>
			<?php
				$search->Results->Disconnect();
				} else {
			?>
				Sorry, There were no results for: <strong><?php echo htmlentities(stripslashes($string)); ?></strong>
			<?php
				}
			?>
			</table>
		</td>
  </tr>
</table>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');