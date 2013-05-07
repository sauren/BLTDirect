<?php
	require_once('lib/common/app_header.php');	
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSearch.php');
	
	$session->Secure(2);
	
	$sql = "";
	if(isset($_REQUEST['searchString']) && !empty($_REQUEST['searchString'])){
		
		$search = new ProductSearch($_REQUEST['searchString'],'./order_product.php?pid=');
		$search->PrepareSQL();
		//$search->Search();
		
		$table = new DataTable('results');
		$table->SetSQL($search->Query);
		$table->SetMaxRows(15);
		$table->Order = 'DESC';
		$table->OrderBy = 'score';
		$table->Finalise();
		$table->ExecuteSQL();
	}
		
	
	// Initiate the Page
	$page = new Page('Create a New Order Manually', '');
	$page->Display('header');
?>
<table width="100%" border="0">
  <tr>
    <td width="300" valign="top"><?php include('./order_toolbox.php'); ?></td>
    <td width="20" valign="top">&nbsp;</td>
    <td valign="top"><p><strong>Product Search</strong><br />
	<?php
				// Check for Products in this Category
				if(!empty($search) && $table->Table->TotalRows > 0){
				$table->DisplayNavigation();
			?>
			<br />
			<table cellspacing="0" class="catProducts">
				<tr>
					<th colspan="2">Search Results for <?php echo $_REQUEST['searchString']; ?></th>
					<th colspan="2">Price</th>
				</tr>
			<?php
				
					while($table->Table->Row){
						$prod = new Product($table->Table->Row['Product_ID']);
			?>
				<tr>
					<td><a href="./order_product.php?pid=<?php echo $prod->ID; ?>&cat=<?php echo $category->ID; ?>">
					<?php if(!empty($prod->DefaultImage->Thumb->FileName)){ echo sprintf('<img src="%s%s" border="0" />',$GLOBALS['PRODUCT_IMAGES_DIR_WS'], $prod->DefaultImage->Thumb->FileName); } ?>&nbsp;</a></td>
					<td>
						<a href="./order_product.php?pid=<?php echo $prod->ID; ?>&cat=<?php echo $category->ID; ?>" title="Click for More Information"><strong><?php echo $prod->Name; ?></strong><br />
					  <span class="smallGreyText">QuickFind #: <?php echo $prod->ID; ?>, Part Number: <?php echo $prod->SKU; ?></span></a>				  </td>
					<td align="right" class="price">
						&pound;<?php echo number_format($prod->PriceCurrent, 2, '.', ','); ?><br />
						<span class="smallGreyText">&pound;<?php echo number_format($prod->PriceCurrentIncTax, 2, '.', ','); ?> inc.  VAT</span>
					</td>
					<td><?php echo $prod->GetBuyIt(0, true, 'order_customise.php'); ?></td>
				</tr>
			<?php
						$table->Next();
					}
			?>
			</table>
			<?php
				}
				$table->DisplayNavigation();
				$table->Disconnect();
			?>
    </td>
  </tr>
</table>
<?php
	$page->Display('footer');
require_once('lib/common/app_footer.php');
?>