<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');

$session->Secure(3);

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
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="5">Product Information</th>
	</tr>
 </thead>
 <tbody>
   <tr>
   	 <td rowspan="11" align="center" valign="middle">
	 	<?php
	 	if(file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Thumb->FileName) && !empty($product->DefaultImage->Thumb->FileName)){
	 		echo sprintf("<img src=\"%s%s\" border=\"0\" alt=\"%s\" />", $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $product->DefaultImage->Thumb->FileName, $product->DefaultImage->Name);
	 	} else {
	 		echo "[No Default Image]";
	 	}
		?>
	 </td>
     <td>Type:</td>
     <td colspan="3"><strong><?php echo $type; ?></strong></td>
   </tr>
   <tr>
     <td>Title:</td>
     <td><?php echo $product->Name; ?></td>
	 <td>Quickfind ID:</td>
	 <td><?php echo $product->ID; ?></td>
   </tr>
   <tr>
     <td>Manufacturer:</td>
     <td><?php echo $product->Manufacturer->Name; ?></td>
	 <td>SKU:</td>
	 <td><?php echo $product->SKU; ?></td>
   </tr>
   <tr>
     <td>Model:</td>
     <td><?php echo $product->Model; ?> &nbsp;</td>
	 <td>RRP Price:</td>
	 <td>&pound;<?php echo $product->PriceRRP; ?>&nbsp;</td>
   </tr>
   <tr>
     <td>Variant:</td>
     <td><?php echo $product->Variant ?> &nbsp;</td>
	 <td>Our Base Price:</td>
	 <td>&pound;<?php echo $product->PriceOurs; ?>&nbsp;</td>
   </tr>
   <tr>
     <td>Status:</td>
     <td><?php echo $product->Status; ?> &nbsp;</td>
	 <td>Current Price:</td>
	 <td>&pound;<?php echo $product->PriceCurrent; ?>&nbsp;</td>
   </tr>
   <tr>
     <td>Sales Start On:</td>
     <td><?php echo (isDatetime($product->SalesStart))?cDatetime($product->SalesStart):"N/A"; ?>&nbsp;</td>
	 <td>Price Status:</td>
	 <td><?php echo $product->PriceStatus ?>&nbsp;</td>
   </tr>
   <tr>
     <td>Sales End On:</td>
     <td><?php echo (isDatetime($product->SalesEnd))?cDatetime($product->SalesEnd):"N/A"; ?>&nbsp;</td>
	 <td>Total Orders:</td>
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
	 <td>Discounted Reason:</td>
	 <td><?php echo $product->DiscontinuedBecause; ?></td>
	 <td>Superseded By:</td>
	 <td><?php echo $superseded->Name; ?></td>
   </tr>
 </tbody>
 </table>
 <br>

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

 <br>

<?php
$data = new DataQuery(sprintf("SELECT SUM(Quantity_In_Stock) AS Quantity FROM warehouse_stock WHERE Product_ID=%d", mysql_real_escape_string($product->ID)));
$stockQuantity = $data->Row['Quantity'];
$data->Disconnect();

if($product->Type == 'S') {
	?>

	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
	 <thead>
 		<tr>
			<th colspan="4">Stock &amp; Warehousing Information</th>
		</tr>
	 </thead>
	 <tbody>
	   <tr>
   		 <td>Stock Profile:</td>
   		 <td><?php echo $product->Stocked; ?></td>
		 <td>Shelf Width (m):</td>
		 <td><?php echo ($product->Width > 0)?$product->Width:"N/A"; ?></td>
	   </tr>
	   <tr>
   		 <td>Monitor Stock:</td>
   		 <td><?php echo $product->StockMonitor; ?></td>
		 <td>Shelf Height (m):</td>
		 <td><?php echo ($product->Height > 0)?$product->Height:"N/A"; ?></td>
	   </tr>
	   <tr>
   		 <td>Stock Alert Level:</td>
   		 <td><?php echo $product->StockAlert; ?></td>
		 <td>Shelf Depth (m):</td>
		 <td><?php echo ($product->Depth > 0)?$product->Depth:"N/A"; ?></td>
	   </tr>
	   <tr>
   		 <td>Stock Reorder Quantity:</td>
   		 <td><?php echo $product->StockReorderQuantity; ?></td>
		 <td>Volume (m<sup>3</sup>):</td>
		 <td><?php echo ($product->Volume > 0)?$product->Volume:"N/A"; ?></td>
	   </tr>
	   <tr>
   		 <td>Quantity In Stock</td>
   		 <td><?php echo $stockQuantity; ?></td>
		 <td>Weight (Kg):</td>
		 <td><?php echo ($product->Weight > 0)?$product->Weight:"N/A"; ?></td>
	   </tr>
	 </tbody>
	 </table>
	 <br>

	 <?php
}
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
 <thead>
 	<tr>
		<th colspan="6">Despatch Information</th>
	</tr>
 </thead>
 <tbody>
   <tr>
   	 <td>Estimated Despatch Days Min:</td>
   	 <td><?php echo $product->DespatchDaysMin; ?></td>
	 <td>Estimated Despatch Days Max:</td>
	 <td><?php echo $product->DespatchDaysMax; ?></td>
	 <td>Order Shipping rule:</td>
	 <td> <?php
	 if($product->OrderRule == 'S')
	 echo "Ship from the supplier";
	 elseif($product->OrderRule == 'W')
	 echo "Automatically ship when ordered";
	 else
	 echo "Manually select in order desk";
   	?></td>
   </tr>
 </tbody>
 </table>

  <br>

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