<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BreadCrumb.php');

$session->Secure();

$category = new Category();
$category->ID = (isset($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])) ? $_REQUEST['cat'] : 1;
$category->Get();

$breadCrumb = new BreadCrumb();
$breadCrumb->Get($category->ID);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/templates/portal-warehouse.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Warehouse Portal</title>
<!-- InstanceEndEditable -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="/ignition/css/i_content.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="/warehouse/css/lightbulbs.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="/warehouse/css/lightbulbs_print.css" rel="stylesheet" type="text/css" media="print" />
	<link href="/warehouse/css/default.css" rel="stylesheet" type="text/css" media="screen" />
	<script language="javascript" src="/warehouse/js/generic_1.js" type="text/javascript"></script>
    <script language="javascript" type="text/javascript">
	var toggleGroup = function(group) {
		var e = document.getElementById(group);
		if(e) {
			if(e.style.display == 'none') {
				e.style.display = 'block';
			} else {
				e.style.display = 'none';
			}
		}
	}
	</script>
    <!-- InstanceBeginEditable name="head" --><!-- InstanceEndEditable -->
</head>
<body>
<div id="Wrapper">
	<div id="Header">
		<a href="/warehouse" title="Back to Home Page"><img src="/images/template/logo_blt_1.jpg" width="185" height="70" border="0" class="logo" alt="BLT Direct Logo" /></a>
		<div id="NavBar" class="warehouse">Warehouse Portal</div>
		<div id="CapTop" class="warehouse">
			<div class="curveLeft"></div>
		</div>
		<ul id="NavTop" class="nav warehouse">
			<?php if($session->IsLoggedIn){
				echo sprintf('<li class="login"><a href="%s?action=logout" title="Logout">Logout</a></li>', $_SERVER['PHP_SELF']);
			} else {
				echo '<li class="login"><a href="/index.php" title="Login as a BLT Direct supplier or warehouse">Login</a></li>';
			}?>
			<li class="account"><a href="/warehouse/account_settings.php" title="Your BLT Direct Account">My Account</a></li>
			<li class="contact"><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
			<li class="help"><a href="/support.php" title="Light Bulb, Lamp and Tube Help">Help</a></li>
		</ul>
	</div>

<div id="PageWrapper">
	<div id="Page">
		<div id="PageContent"><!-- InstanceBeginEditable name="pageContent" -->

   	 	<h1><?php echo $category->Name; ?> Supplied Products</h1>
		<p class="breadcrumb"><a href="./">Supplied Products</a> <?php if(isset($_REQUEST['cat'])){ echo "/ <a href=\"./products_supplied.php\">Products</a>"; } ?> <?php if(isset($breadCrumb)) echo $breadCrumb->Text; ?></p>

		<?php
		if(!empty($category->CategoryOrder)){
			$sql = sprintf("select * from product_categories where Category_Parent_ID=%d and Is_Active='Y' order by %s", mysql_real_escape_string($category->ID), mysql_real_escape_string($category->CategoryOrder));
		} else {
			$sql = sprintf("select * from product_categories where Category_Parent_ID=%d and Is_Active='Y' ORDER BY Category_Title", mysql_real_escape_string($category->ID));
		}

		$children = new DataQuery($sql);

		if($category->ShowImages == 'Y') {
			$productColumns = 3;
			if($children->TotalRows > 0){
				echo "<table class=\"productCategories clear\">";
				$tempColumn = 0;
				$rows = 0;
				while($children->Row){
					++$tempColumn;
					++$rows;
					if($tempColumn == 1) echo "<tr>";

					$tempStr = sprintf('<td width="%f%%%%">%%s<br /><p>%%s</p></td>', (100 / $productColumns));

					if(!empty($children->Row['Category_Thumb'])){
						$image =  sprintf("<a href=\"products_supplied.php?cat=%s&nm=%s\"><img src=\"../images/categories/%s\" alt=\"%s\"/></a>", $children->Row['Category_ID'], urlencode($children->Row['Meta_Title']), $children->Row['Category_Thumb'], $children->Row['Meta_Title']);
					} else {
						$image =  sprintf("<a href=\"products_supplied.php?cat=%s&nm=%s\"><img src=\"../images/template/image_coming_soon_2.jpg\" alt=\"%s\"/></a>", $children->Row['Category_ID'], urlencode($children->Row['Meta_Title']), $children->Row['Meta_Title']);
					}
					$link = sprintf('<a href="products_supplied.php?cat=%s&nm=%s" title="%s">%s</a>', $children->Row['Category_ID'],  urlencode($children->Row['Meta_Title']), $children->Row['Meta_Title'],  $children->Row['Category_Title']);
					echo sprintf($tempStr, $image, $link);

					if(($tempColumn == $productColumns) || ($rows == $children->TotalRows)){
						echo "</tr>";
						$tempColumn = 0;
					}
					$children->Next();
				}
				echo "</table>";
			}
		} else {
			$productColumns = 2;
			if($children->TotalRows > 0){
				$childrenArr = array();

				while($children->Row){
					$childrenArr[] = sprintf('<a href="products_supplied.php?cat=%s&nm=%s" title="%s">%s</a>', $children->Row['Category_ID'],  urlencode($children->Row['Meta_Title']), $children->Row['Meta_Title'],  $children->Row['Category_Title']);
					$children->Next();
				}

				$tempColumn = 0;
				$rows = 0;
				$columnArr = array();
				$col = 0;
				$count = 0;

				for($i=0;$i < count($childrenArr); $i++) {
					if($count >= (count($childrenArr) / $productColumns)) {
						$col++;
						$count = 0;
					}

					$columnArr[$col][] = $childrenArr[$i];
					$count++;
				}

				echo "<table class=\"productCategories clear\">";

				for($i=0;$i < count($columnArr[0]); $i++) {

					echo "<tr>";

					for($j=0;$j < $productColumns; $j++) {
						if(isset($columnArr[$j][$i])) {
							$link = $columnArr[$j][$i];
						} else {
							$link = '&nbsp;';
						}

						echo sprintf("<td style=\"text-align: left;\">%s</td>", $link);
					}

					echo "</tr>";
				}

				echo "</table>";
			}
		}

		$children->Disconnect();

		echo '<br />';

		$table = new DataTable("products");
		$table->SetSQL(sprintf("SELECT p.Product_Title, p.SKU, s.* FROM supplier_product AS s INNER JOIN product AS p ON p.Product_ID=s.Product_ID INNER JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID WHERE s.Supplier_ID=%d AND c.Category_ID=%d", mysql_real_escape_string($session->Warehouse->Contact->ID), mysql_real_escape_string($category->ID)));
		$table->AddField('ID', 'Product_ID', 'left');
		$table->AddField('Our SKU','SKU');
		$table->AddField('Product', 'Product_Title', 'left');
		$table->AddField('Cost', 'Cost', 'right');
		$table->AddField('Your SKU', 'Supplier_SKU', 'right');
		$table->AddLink('product_supplied_edit.php?spid=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Update the stock settings\" border=\"0\">",'Supplier_Product_ID');
		$table->AddLink("../product.php?pid=%s", "<img src=\"../ignition/images/folderopen.gif\" alt=\"View Product\" border=\"0\">", "Product_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Product_ID");
		$table->Order = "Asc";
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		?>

		<!-- InstanceEndEditable -->
		</div>
  	</div>

	<div id="PageFooter">
		<ul class="links">
			<li><a href="/privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
			<li><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
		</ul>
		<p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
	</div>
</div>

	<div id="LeftNav">
		<div id="CatalogueNav" class="greyNavLeft">
			<div id="NavLeftItems" class="warehouse">
			<p class="title"><strong>Warehouse Options </strong> </p>

			<ul class="rootCat">
				<?php
				if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N'))) {
					?>
					<li><a href="/warehouse/orders_pending.php">Pending Orders</a></li>
                    
                    <?php
					if($session->Warehouse->Type == 'B') {
						echo '<li><a href="/warehouse/orders_pending_tax_free.php">Pending Orders<br />(Tax Free)</a></li>';
					}
					?>
                    
					<li><a href="/warehouse/orders_collections.php">Collection Orders</a></li>
					<li><a href="/warehouse/orders_backordered.php">Backordered Orders</a></li>
                    
                    <?php
					if(($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsBidder == 'Y')) {
						echo '<li><a href="/warehouse/orders_bidding.php">Bidding Orders</a></li>';
					}
					
					if($session->Warehouse->Type == 'S') {
						echo '<li><a href="/warehouse/orders_warehouse_declined.php">Warehouse Declined Orders</a></li>';
					}
					?>
                    
					<li><a href="/warehouse/orders_despatched.php">Despatched Orders</a></li>
					<li><a href="/warehouse/orders_search.php">Search Orders</a></li>
					<?php
				}

				if($session->Warehouse->Type == 'B') {
					?>
					<li><a href="/warehouse/orders_auto_despatch.php">Auto Despatch Orders</a></li>
					<?php
				}

				if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N'))) {
					?>

					<li><a href="/warehouse/despatches_track.php">Track Consignments</a></li>
					<li><a href="/warehouse/products_stocked.php">Stocked Products</a></li>

					<?php
				}
				?>
                
                <li><a href="/warehouse/products_backordered.php">Products Backordered</a></li>
                
                <?php
				if(($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N')) {
					?>

					<li><a href="/warehouse/products_held.php">Products Held</a></li>
					<li><a href="/warehouse/products_supplied.php">Products Supplied</a></li>

					<?php
					$supplier = new Supplier($session->Warehouse->Contact->ID);

					if($supplier->IsComparable == 'Y') {
						?>

						<li><a href="/warehouse/products_unsupplied.php">Unsupplied Products</a></li>

						<?php
					}
				}

				if($session->Warehouse->Type == 'B') {
					?>

					<li><a href="/warehouse/timesheets.php">Timesheets</a></li>

					<li><a href="javascript:toggleGroup('navLabels');" target="_self">Labels</a></li>
                    <ul id="navLabels" class="subCat" style="display: none;">
                        <li><a href="/warehouse/downloads/2nd-class-stamp.pdf" target="_blank">2<sup>nd</sup> Class Stamps</a></li>
                    </ul>
                    
					<?php
				}

				if($session->Warehouse->Type == 'S') {
					?>
					
					<li><a href="javascript:toggleGroup('navReserves');" target="_self">Reserves</a></li>
					<ul id="navReserves" class="subCat" style="display: none;">
						<li><a href="/warehouse/reserves_pending.php">Pending</a></li>
						<li><a href="/warehouse/reserves_completed.php">Completed</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navPriceEnquiries');" target="_self">Price Enquiries</a></li>
					<ul id="navPriceEnquiries" class="subCat" style="display: none;">
						<li><a href="/warehouse/price_enquiries_pending.php">Pending</a></li>
						<li><a href="/warehouse/price_enquiries_completed.php">Completed</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navPurchaseRequests');" target="_self">Purchase Requests</a></li>
					<ul id="navPurchaseRequests" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/purchase_requests_pending.php">Pending</a></li>
                    	<li><a href="/warehouse/purchase_requests_confirmed.php">Confirmed</a></li>
                    	<li><a href="/warehouse/purchase_requests_completed.php">Completed</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navPurchaseOrders');" target="_self">Purchase Orders</a></li>
					<ul id="navPurchaseOrders" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/purchase_orders_unfulfilled.php">Unfulfilled</a></li>
                    	<li><a href="/warehouse/purchase_orders_fulfilled.php">Fulfilled</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navReturnRequests');" target="_self">Return Requests</a></li>
					<ul id="navReturnRequests" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/supplier_return_requests_pending.php">Pending</a></li>
                    	<li><a href="/warehouse/supplier_return_requests_confirmed.php">Confirmed</a></li>
                    	<li><a href="/warehouse/supplier_return_requests_completed.php">Completed</a></li>
					</ul>
                    
					<li><a href="/warehouse/supplier_return_requests_pending_purchase.php">Damages</a></li>
                    
                    <li><a href="javascript:toggleGroup('navInvoiceQueries');" target="_self">Invoice Queries</a></li>
                    <ul id="navInvoiceQueries" class="subCat" style="display: none;">
                        <li><a href="/warehouse/supplier_invoice_queries_pending.php">Pending</a></li>
                        <li><a href="/warehouse/supplier_invoice_queries_resolved.php">Resolved</a></li>
                    </ul>
                        
                    <li><a href="javascript:toggleGroup('navDebits');" target="_self">Debits</a></li>
					<ul id="navDebits" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/debits_pending.php">Pending</a></li>
                    	<li><a href="/warehouse/debits_completed.php">Completed</a></li>
					</ul>
                    
                    <li><a href="javascript:toggleGroup('navReports');" target="_self">Reports</a></li>
					<ul id="navReports" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/report_orders_despatched.php">Orders Despatched</a></li>
						<li><a href="/warehouse/report_reserved_stock.php">Reserved Stock</a></li>
						<li><a href="/warehouse/report_stock_dropped.php">Stock Dropped</a></li>
					</ul>

					<?php
				}
				?>
				
				<li><a href="/warehouse/account_settings.php">Account Settings</a></li>
			</ul>
			</div>
			<div class="cap"></div>
			<div class="shadow"></div>
		</div>
	</div>
</div>
</body>
<!-- InstanceEnd --></html>