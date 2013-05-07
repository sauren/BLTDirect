<?php
	require_once('lib/common/app_header.php');	
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BreadCrumb.php');
	
	$session->Secure(2);
	
	$category = new Category;
	if(isset($_REQUEST['cat']) && !empty($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])){
		$category->Get($_REQUEST['cat']);
		$breadCrumb = new BreadCrumb();
		$breadCrumb->LinkCode = "./order_products.php?cat=%s";
		$breadCrumb->Get($category->ID);
	}

	$page = new Page('Create a New Order Manually', '');
	$page->Display('header');
?>
<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top"><p><strong><?php echo $category->Name; ?></strong><br/><?php if(isset($breadCrumb)) echo $breadCrumb->Text; ?></p>
	
	<?php
			// Check for Sub Categories
			$children = new DataQuery(sprintf("select * from product_categories where Category_Parent_ID=%d and Is_Active='Y'", mysql_real_escape_string($category->ID)));
			$productColumns = 2;
			if($children->TotalRows > 0){
				echo "<table class=\"productCategories clear\">";
				$tempColumn = 0;
				$rows = 0;
				while($children->Row){
					++$tempColumn;
					++$rows;
					if($tempColumn == 1) echo "<tr>";
					
					$tempStr = "<td>%s<br /><p>%s</p></td>";
					$image = '';

					if(!empty($children->Row['Category_Thumb']) && file_exists($GLOBALS['CATEGORY_IMAGES_DIR_FS'].$children->Row['Category_Thumb'])){
						$image =  sprintf("<a href=\"order_products.php?cat=%s\"><img src=\"%s%s\" width=\"40\"/></a>", $children->Row['Category_ID'], $GLOBALS['CATEGORY_IMAGES_DIR_WS'], $children->Row['Category_Thumb']);
					}
					$link = sprintf('<a href="order_products.php?cat=%s">%s</a>', $children->Row['Category_ID'], $children->Row['Category_Title']);
					echo sprintf($tempStr, $image, $link);

					if(($tempColumn == $productColumns) || ($rows == $children->TotalRows)){
						echo "</tr>";
						$tempColumn = 0;
					}
					$children->Next();
				}
				echo "</table>";
			}
			$children->Disconnect();
				
			$products = new DataQuery(sprintf("select p.Product_ID from product_in_categories as pc inner join product as p on pc.Product_ID=p.Product_ID  where Category_ID=%d and ((Now() Between p.Sales_Start and p.Sales_End) or (p.Sales_Start='0000-00-00 00:00:00' and p.Sales_End='0000-00-00 00:00:00') or (p.Sales_Start='0000-00-00 00:00:00' and p.Sales_End>Now()) or (p.Sales_Start<=Now() and p.Sales_End='0000-00-00 00:00:00')) and p.Is_Active='Y' and p.Discontinued='N'",
							mysql_real_escape_string($category->ID)));
			if($products->TotalRows > 0){
			?>
			<br />
			<table cellspacing="0" class="catProducts">
				<tr>
					<th colspan="2">Products in <?php echo $category->Name; ?></th>
					<th colspan="2">Price</th>
				</tr>
			<?php
				
					while($products->Row){
						$prod = new Product($products->Row['Product_ID']);
			?>
				<tr>
					<td align="center">
						<a href="./order_product.php?pid=<?php echo $prod->ID; ?>&cat=<?php echo $category->ID; ?>" title="Click for More Information">&nbsp;<?php if(!empty($prod->DefaultImage->Thumb->FileName)){
						echo '<img src="' . $GLOBALS['PRODUCT_IMAGES_DIR_WS'].$prod->DefaultImage->Thumb->FileName . '" width="50" />'; } ?></a>
					</td>
					<td>					  <a href="./order_product.php?pid=<?php echo $prod->ID; ?>&cat=<?php echo $category->ID; ?>" title="Click for More Information"><strong><?php echo $prod->Name; ?></strong><br />
					<span class="smallGreyText">QuickFind #: <?php echo $prod->ID; ?>, Part Number: <?php echo $prod->SKU; ?></span></a>
					</td>
				  <td align="right" class="price">
						&pound;<?php echo number_format($prod->PriceCurrent, 2, '.', ','); ?><br />
					  <span class="smallGreyText">&pound;<?php echo number_format($prod->PriceCurrentIncTax, 2, '.', ','); ?> inc.  VAT</span>
				  </td>
					<td nowrap="nowrap" align="right"><?php echo $prod->GetBuyIt($category->ID, true, 'order_customise.php'); ?></td>
				</tr>
			<?php
						$products->Next();
					}
			?>
			</table>
			<?php
				}
				$products->Disconnect();
			?>
	
	
	</td>
  </tr>
</table>
<?php
	$page->Display('footer');
require_once('lib/common/app_footer.php');

