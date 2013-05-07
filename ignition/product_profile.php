<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

if($action == 'purgecache') {
	$session->Secure(3);
	purgeCache();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function purgeCache() {
	if(isset($_REQUEST['pid']) && is_numeric($_REQUEST['pid'])){
		$product = new Product();
		$product->ID = $_REQUEST['pid'];
		$product->PurgeCache();
		
		redirectTo('?pid=' . $_REQUEST['pid']);
	}
	
	redirectTo('product_search.php');
}

function view() {
	$product = new Product($_REQUEST['pid']);
	$product->GetStatus();

	$page = new Page('Product Profile', 'Here you can change your product information.');
	$page->Display('header');

	switch($product->Type) {
		case 'S':
			$type = 'Standard';
			break;
		case 'G':
			$type = 'Group';
			break;
	    default:
			$type = '';
			break;
	}
	
	$superseded = new Product();

	if($product->Discontinued == 'Y') {
		if($product->SupersededBy > 0) {
			$superseded->Get($product->SupersededBy); 
		}
	}
	
	$data = new DataQuery(sprintf("SELECT COALESCE(SUM(ws.Quantity_In_Stock), 0) AS Stock FROM warehouse AS w INNER JOIN warehouse_stock AS ws ON ws.Warehouse_ID=w.Warehouse_ID AND ws.Product_ID=%d WHERE w.Type='B'", mysql_real_escape_string($product->ID)));
	$stockWarehoused = $data->Row['Stock'];
	$data->Disconnect();
	
	$data = new DataQuery(sprintf("SELECT COALESCE(SUM(pl.Quantity_Decremental), 0) AS Stock FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE p.For_Branch>0 AND pl.Quantity_Decremental>0 AND pl.Product_ID=%d", mysql_real_escape_string($product->ID)));
	$stockIncoming = $data->Row['Stock'];
	$data->Disconnect();
	?>

	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
	 <thead>
 		<tr>
			<th colspan="5">Product Information</th>
		</tr>
	 </thead>
	 <tbody>
	   <tr>
   		 <td rowspan="25" align="center" valign="middle">
	 		<?php
	 		if(file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Thumb->FileName) && !empty($product->DefaultImage->Thumb->FileName)){
	 			echo sprintf("<img src=\"%s%s\" border=\"0\" alt=\"%s\" />", $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $product->DefaultImage->Thumb->FileName, $product->DefaultImage->Name);
	 		} else {
	 			echo "[No Default Image]";
	 		}
			?>
		 </td>
	     <td width="12.5%">Type:</td>
	     <td width="30%"><strong><?php echo $type; ?></strong></td>
	     <td width="12.5%">Quickfind ID:</td>
		 <td width="30%"><?php echo $product->ID; ?></td>
	   </tr>
	   <tr>
	     <td>Title:</td>
	     <td><?php echo $product->Name; ?></td>
		 <td>SKU:</td>
		 <td><?php echo $product->SKU; ?></td>
	   </tr>
	   <tr>
	     <td>Quality:</td>
	     <td><?php echo $product->Quality; ?></td>
		 <td>&nbsp;</td>
		 <td>&nbsp;</td>
	   </tr>
	   <tr>
	     <td>Manufacturer:</td>
	     <td><?php echo $product->Manufacturer->Name; ?></td>
		 <td>RRP Price:</td>
		 <td>&pound;<?php echo $product->PriceRRP; ?>&nbsp;</td>
	   </tr>
	   <tr>
	     <td>Model:</td>
	     <td><?php echo $product->Model; ?> &nbsp;</td>
		 <td>Our Base Price:</td>
		 <td>&pound;<?php echo $product->PriceOurs; ?>&nbsp;</td>
	   </tr>
	   <tr>
	     <td>Variant:</td>
	     <td><?php echo $product->Variant ?> &nbsp;</td>
		 <td>Current Price:</td>
		 <td>&pound;<?php echo $product->PriceCurrent; ?>&nbsp;</td>
	   </tr>
	   <tr>
	     <td>Status:</td>
	     <td><?php echo $product->Status; ?> &nbsp;</td>
		 <td>Price Status:</td>
		 <td><?php echo $product->PriceStatus ?>&nbsp;</td>
	   </tr>
	   <tr>
	     <td>Sales Start On:</td>
	     <td><?php echo (isDatetime($product->SalesStart))?cDatetime($product->SalesStart):"N/A"; ?>&nbsp;</td>
		 <td>Locked Supplier:</td>
		 <td>
			<?php
			if($product->LockedSupplierID > 0) {
				$lockedSupplier = new Supplier($product->LockedSupplierID);
				$lockedSupplier->Contact->Get();

				if($lockedSupplier->Contact->Parent->Organisation->ID > 0) {
					$lockedSupplierName = $lockedSupplier->Contact->Parent->Organisation->Name;
				} elseif(strlen(trim(sprintf('%s %s', $lockedSupplier->Contact->Person->Name, $lockedSupplier->Contact->Person->LastName))) > 0) {
					$lockedSupplierName = trim(sprintf('%s %s', $lockedSupplier->Contact->Person->Name, $lockedSupplier->Contact->Person->LastName));
				} else {
					$lockedSupplierName = 'View';
				}

				echo sprintf('<a href="contact_profile.php?cid=%d">%s</a>', $lockedSupplier->Contact->ID, $lockedSupplierName);

				$data = new DataQuery(sprintf("SELECT Lead_Days FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($product->LockedSupplierID), mysql_real_escape_string($product->ID)));
				if($data->TotalRows > 0) {
					echo sprintf(' [%d Lead Days]', $data->Row['Lead_Days']);
				}
				$data->Disconnect();
			} else {
				echo 'None';	
			}
			?>
			(<a href="product_edit_locked_supplier.php?pid=<?php echo $product->ID; ?>">Edit</a>)
		 </td>
	   </tr>
	   <tr>
	     <td>Sales End On:</td>
	     <td><?php echo (isDatetime($product->SalesEnd))?cDatetime($product->SalesEnd):"N/A"; ?>&nbsp;</td>
	     <td>Drop Supplier:</td>
		 <td>
			<?php
			if($product->DropSupplierID > 0) {
				$dropSupplier = new Supplier($product->DropSupplierID);
				$dropSupplier->Contact->Get();

				if($dropSupplier->Contact->Parent->Organisation->ID > 0) {
					$dropSupplierName = $dropSupplier->Contact->Parent->Organisation->Name;
				} elseif(strlen(trim(sprintf('%s %s', $dropSupplier->Contact->Person->Name, $dropSupplier->Contact->Person->LastName))) > 0) {
					$dropSupplierName = trim(sprintf('%s %s', $dropSupplier->Contact->Person->Name, $dropSupplier->Contact->Person->LastName));
				} else {
					$dropSupplierName = 'View';
				}

				echo sprintf('<a href="contact_profile.php?cid=%d">%s</a>', $dropSupplier->Contact->ID, $dropSupplierName);
			} else {
				echo 'None';	
			}
			?>
			(<a href="product_edit_drop_supplier.php?pid=<?php echo $product->ID; ?>">Edit</a>)
		 </td>
	   </tr>
	   <tr>
	   	<td>Flexible Suppliers:</td>
		 <td>
			<?php
			$flexibleSuppliers = array();

			$data = new DataQuery(sprintf("SELECT IF(c.Parent_Contact_ID>0, CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')')), CONCAT_WS(' ', p.Name_First, p.Name_Last)) AS Supplier FROM product_supplier_flexible AS psf INNER JOIN supplier AS s ON s.Supplier_ID=psf.SupplierID INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID WHERE psf.ProductID=%d", mysql_real_escape_string($product->ID)));
			while($data->Row) {
				$flexibleSuppliers[] = $data->Row['Supplier'];

				$data->Next();
			}
			$data->Disconnect();

			if(!empty($flexibleSuppliers)) {
				echo implode(', ', $flexibleSuppliers);
			} else {
				echo 'None';
			}
			?>
		 </td>
		 <td>Drop Supplier Expires:</td>
	     <td><?php echo isDatetime($product->DropSupplierExpiresOn) ? cDatetime($product->DropSupplierExpiresOn, 'shortdate') : ''; ?>&nbsp;</td>
	   </tr>
	   <tr>
	   	 <td>Special Order Supplier:</td>
	     <td>
	     	<?php
			if($product->SpecialOrderSupplierID > 0) {
				$specialOrderSupplier = new Supplier($product->SpecialOrderSupplierID);
				$specialOrderSupplier->Contact->Get();

				if($specialOrderSupplier->Contact->Parent->Organisation->ID > 0) {
					$specialOrderSupplierName = $specialOrderSupplier->Contact->Parent->Organisation->Name;
				} elseif(strlen(trim(sprintf('%s %s', $specialOrderSupplier->Contact->Person->Name, $specialOrderSupplier->Contact->Person->LastName))) > 0) {
					$specialOrderSupplierName = trim(sprintf('%s %s', $specialOrderSupplier->Contact->Person->Name, $specialOrderSupplier->Contact->Person->LastName));
				} else {
					$specialOrderSupplierName = 'View';
				}

				echo sprintf('<a href="contact_profile.php?cid=%d">%s</a>', $specialOrderSupplier->Contact->ID, $specialOrderSupplierName);
			} else {
				echo 'None';	
			}
			?>
			(<a href="product_edit_special_order.php?pid=<?php echo $product->ID; ?>">Edit</a>)
		</td>
		 <td>Drop Supplier Quantity:</td>
	     <td><?php echo $product->DropSupplierQuantity; ?></td>
	   </tr>
	   <tr>
	   	 <td>Special Order Lead Days:</td>
	     <td><?php echo $product->SpecialOrderLeadDays; ?></td>
		 <td>&nbsp;</td>
	     <td>&nbsp;</td>
	   </tr>
	   <tr>
	     <td colspan="4" style="padding-top: 20px;"><strong>Sales Statistics</strong></td>
	   </tr>
	   <tr>
	     <td>Total (Quantities):</td>
	     <td><?php echo $product->TotalQuantities; ?></td>
		 <td>Total (Orders):</td>
		 <td><?php echo $product->TotalOrders; ?></td>
	   </tr>
	   <tr>
	     <td>Position (Quantities):</td>
	     <td>#<?php echo $product->PositionQuantities; ?></td>
	     <td>Position (Orders):</td>
	     <td>#<?php echo $product->PositionOrders; ?></td>
	   </tr>
	   <tr>
	     <td>Recent Position (Quantities):</td>
	     <td>#<?php echo $product->PositionQuantitiesRecent; ?></td>
	     <td>Recent Position (Orders):</td>
	     <td>#<?php echo $product->PositionOrdersRecent; ?></td>
	   </tr>
	   <tr>
   		 <td>3 Month Quantities</td>
   		 <td><?php echo $product->TotalQuantities3Month; ?></td>
		 <td>3 Month Orders</td>
   		 <td><?php echo $product->TotalOrders3Month; ?></td>
	   </tr>
	    <tr>
   		 <td>12 Month Quantities:</td>
		 <td><?php echo $product->TotalQuantities12Month; ?></td>
		 <td>12 Month Orders:</td>
		 <td><?php echo $product->TotalOrders12Month; ?></td>
	   </tr>
	   <tr>
	     <td colspan="4" style="padding-top: 20px;"><strong>Stock Details</strong></td>
	   </tr>
	   <tr>
	     <td>Stock (Warehoused):</td>
	     <td><?php echo $stockWarehoused; ?></td>
		 <td>Stock (Incoming):</td>
		 <td><?php echo $stockIncoming; ?></td>
	   </tr>
	   <tr>
	     <td>Stocked:</td>
	     <td><?php echo ($product->Stocked == 'Y') ? 'Yes' : 'No'; ?></td>
		 <td>Stocked Temporarily:</td>
		 <td><?php echo ($product->StockedTemporarily == 'Y') ? 'Yes' : 'No'; ?></td>
	   </tr>
	   <tr>
	   	 <td>Weight:</td>
		 <td><?php echo $product->Weight; ?> Kg</td>
		 <td>Stock Alert Level:</td>
		 <td><?php echo $product->StockAlert; ?></td>
	   </tr>
   	   <tr>
   	   	 <td>Monitor Stock:</td>
		 <td><?php echo $product->StockMonitor; ?></td>
		 <td>Stock Reorder Quantity:</td>
		 <td><?php echo $product->StockReorderQuantity; ?></td>
	   </tr>
	   <tr>
	     <td colspan="4" style="padding-top: 20px;"><strong>Discontinued Details</strong></td>
	   </tr>
	   <tr>
   		 <td>Discounted Reason:</td>
   		 <td><?php echo $product->DiscontinuedBecause; ?></td>
		 <td>Superseded By:</td>
		 <td><?php echo $superseded->Name; ?></td>
	   </tr>
	 </tbody>
	 </table>
	 <br>

	<?php
	$links = array();
	$links['Basic Information'] = sprintf('product_basic.php?action=update&pid=%d', $product->ID);
	$links['Descriptions'] = sprintf('product_description.php?action=update&pid=%d', $product->ID);
	$links['Meta Information'] = sprintf('product_meta.php?action=update&pid=%d', $product->ID);
	$links['Prices'] = sprintf('product_price.php?pid=%d', $product->ID);
	$links['Stock Settings'] = sprintf('product_stock.php?action=update&pid=%d', $product->ID);
	$links['Offers'] = sprintf('product_offer.php?pid=%d', $product->ID);
	$links['Images'] = sprintf('product_images.php?pid=%d', $product->ID);
	$links['Videos'] = sprintf('product_videos.php?pid=%d', $product->ID);
	$links['Examples'] = sprintf('product_image_examples.php?pid=%d', $product->ID);

	if($product->Type == 'G') {
		$links['Components'] = sprintf('product_component.php?pid=%d', $product->ID);
	}

	$links['Product Options (Additional)'] = sprintf('product_option_group.php?pid=%d', $product->ID);

	if($product->Type == 'S') {
		$links['Suppliers'] = sprintf('supplier_product.php?pid=%d', $product->ID);
		$links['Supplier Price History'] = sprintf('product_supplier_prices.php?pid=%d', $product->ID);
	}

	$links['Related Products'] = sprintf('product_related.php?pid=%d', $product->ID);
	$links['Commerce Settings'] = sprintf('product_commerce.php?action=update&pid=%d', $product->ID);
	$links['Categories'] = sprintf('product_in_categories.php?pid=%d', $product->ID);
	$links['Tech Specs'] = sprintf('product_specs.php?pid=%d', $product->ID);
	$links['Downloads'] = sprintf('product_downloads.php?pid=%d', $product->ID);
	$links['Duplicate Product'] = sprintf('product_duplicate.php?pid=%d', $product->ID);
	$links['Destroy Product'] = sprintf('product_destroy.php?pid=%d', $product->ID);
	$links['Warehouse Stock'] = sprintf('warehouse_stock_view.php?pid=%d', $product->ID);
	$links['Warehouse Reserves'] = sprintf('warehouse_reserves.php?pid=%d', $product->ID);
	$links['Decommission Product'] = sprintf('product_decommission.php?pid=%d', $product->ID);
	
	if($product->Discontinued == 'Y') {
		$links['Recommission Product'] = sprintf('product_recommission.php?pid=%d', $product->ID);
	}

	$links['Add Alert'] = sprintf('alert_add.php?owner=Product&referenceid=%d', $product->ID);
	$links['Purge Cache'] = sprintf('?action=purgecache&pid=%d', $product->ID);
	$links['Links'] = sprintf('product_links.php?pid=%d', $product->ID);
	$links['Locked Supplier'] = sprintf('product_edit_locked_supplier.php?pid=%d', $product->ID);
	$links['Flexible Suppliers'] = sprintf('product_supplier_flexible.php?pid=%d', $product->ID);
	$links['Product Barcodes'] =  sprintf('product_barcodes.php?pid=%d', $product->ID);
	
	$columns = 3;
	$index = 0;
	$colCount = 0;
	?>

	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
	 <thead>
		<tr>
			<th colspan="3">Product Links</th>
		</tr>
	 </thead>
	 <tbody>
		<?php
		foreach($links as $text=>$link) {
			$index++;
			$colCount++;

			if($colCount == 1) {
				echo '<tr>';
			}

			echo sprintf('<td width="%f%%"><a href="%s">%s</a></td>', (100/$columns), $link, $text);

			if($colCount == $columns) {
				echo '</tr>';
				$colCount = 0;
			}
		}

		if($colCount > 0) {
			for($i=$colCount; $i<$columns; $i++) {
				echo '<td>&nbsp;</td>';
			}
		}
		?>
	 </tbody>
	</table>
	<br />

	 <table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
	 <thead>
 		<tr>
			<th>Product Description</th>
		</tr>
	 </thead>
	 <tbody>
	   <tr>
   		 <td style="height:100px; overflowy:scroll;"><?php echo nl2br($product->Description); ?></td>
	   </tr>
	 </tbody>
	 </table>
	 <br />

	 <table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
	 <thead>
 		<tr>
			<th colspan="4">Tax &amp; Shipping Information</th>
		</tr>
	 </thead>
	 <tbody>
	   <tr>
   		 <td>Tax Band:</td>
   		 <td><?php echo $product->TaxClass->Name; ?></td>
		 <td>Shipping Band:</td>
		 <td><?php echo $product->ShippingClass->Name; ?></td>
	   </tr>
	 </tbody>
	 </table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}