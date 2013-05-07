<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

function GetChildIDS($cat) {
	$string = "";
	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($cat)));
	while($children->Row) {
		$string .= "OR c.Category_ID=".$children->Row['Category_ID']." ";
		$string .= GetChildIDS($children->Row['Category_ID']);
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}

$session->Secure();

$limit = 25;
$page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : 1;

$category = (isset($_REQUEST['category'])) ? $_REQUEST['category'] : 0;

if($page < 1) {
	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

if($session->Warehouse->Type == 'S'){
	$supplier = new Supplier($session->Warehouse->Contact->ID);

	if($supplier->IsComparable == 'N') {
		redirect("Location: index.php");
	}
} else {
	redirect("Location: index.php");
}

if(isset($_REQUEST['category'])) {
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM supplier_categories WHERE Supplier_ID=%d AND Category_ID=%d", mysql_real_escape_string($session->Warehouse->Contact->ID), mysql_real_escape_string($_REQUEST['category'])));
	if($data->Row['count'] == 0) {
		$data->Disconnect();
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
	$data->Disconnect();
}

if($category > 0) {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('category', 'Category', 'hidden', $category, 'numeric_unsigned', 1, 11);
	$form->AddField('limit', 'Limit', 'hidden', $limit, 'numeric_unsigned', 1, 11);
	$form->AddField('page', 'Page', 'hidden', $page, 'numeric_unsigned', 1, 11);

	$check = new DataQuery(sprintf("SELECT COUNT(*) AS Total FROM supplier_product AS s INNER JOIN product AS p ON p.Product_ID=s.Product_ID INNER JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID WHERE s.Supplier_ID=%d AND s.Preferred_Supplier='N' AND (c.Category_ID=%d %s)", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($category), mysql_real_escape_string(GetChildIDS($category))));
	$totalRows = $check->Row['Total'];
	$check->Disconnect();

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, s.Supplier_SKU, s.Cost FROM supplier_product AS s INNER JOIN product AS p ON p.Product_ID=s.Product_ID INNER JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID WHERE s.Supplier_ID=%d AND s.Preferred_Supplier='N' AND (c.Category_ID=%d %s) ORDER BY p.Product_ID ASC LIMIT %d, %d", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($category), mysql_real_escape_string(GetChildIDS($category)), (($page-1) * $limit), mysql_real_escape_string($limit)));

	$products = array();

	while($data->Row) {
		$data2 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Product_ID=%d AND Supplier_ID<>%d AND Cost>0 ORDER BY Cost ASC LIMIT 0, 1", $data->Row['Product_ID'], mysql_real_escape_string($supplier->ID)));

		$products[$data->Row['Product_ID']]['SKU'] = $data->Row['Supplier_SKU'];
		$products[$data->Row['Product_ID']]['Product_Title'] = $data->Row['Product_Title'];
		$products[$data->Row['Product_ID']]['Cost'] = $data->Row['Cost'];
		$products[$data->Row['Product_ID']]['Beaten_Cost'] = ($data2->TotalRows > 0) ? $data2->Row['Cost'] : -1;

		$data2->Disconnect();
		$data->Next();
	}

	foreach($products as $k=>$v) {
		$form->AddField('cost_'.$k, 'Cost for '.$v['Product_Title'], 'text', $v['Cost'], 'float', 1, 11, true, 'size="4"');
		$form->AddField('sku_'.$k, 'Part Number for '.$v['Product_Title'], 'text', $v['SKU'], 'anything', 1, 32, true, 'size="10"');
	}

	$data->Disconnect();
}

if(isset($_REQUEST['update']) && isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true')) {
	foreach($products as $k=>$v) {
		if($form->GetValue('cost_'.$k) > 0) {
			if($form->GetValue('cost_'.$k) < $v['Beaten_Cost']) {
				if($form->GetValue('cost_'.$k) != $v['Cost']) {
					$data = new DataQuery(sprintf("UPDATE supplier_product SET Preferred_Supplier='Y', Cost=%f WHERE Supplier_ID=%d AND Product_ID=%d", $form->GetValue('cost_'.$k), mysql_real_escape_string($supplier->ID), mysql_real_escape_string($k)));
					$data->Disconnect();
				} else {
					$data = new DataQuery(sprintf("UPDATE supplier_product SET Preferred_Supplier='Y' WHERE Supplier_ID=%d AND Product_ID=%d", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($k)));
					$data->Disconnect();
				}

				$data = new DataQuery(sprintf("UPDATE supplier_product SET Preferred_Supplier='N' WHERE Supplier_ID<>%d AND Product_ID=%d", mysql_real_escape_string($supplier->ID), mysql_real_escape_string($k)));
				$data->Disconnect();

			} else {
				if($form->GetValue('cost_'.$k) != $v['Cost']) {
					$data = new DataQuery(sprintf("UPDATE supplier_product SET Cost=%f WHERE Supplier_ID=%d AND Product_ID=%d", $form->GetValue('cost_'.$k), mysql_real_escape_string($supplier->ID), mysql_real_escape_string($k)));
					$data->Disconnect();
				}
			}
		}

		$data = new DataQuery(sprintf("UPDATE supplier_product SET Supplier_SKU='%s' WHERE Supplier_ID=%d AND Product_ID=%d", $form->GetValue('sku_'.$k), mysql_real_escape_string($supplier->ID), mysql_real_escape_string($k)));
		$data->Disconnect();
	}

	redirect(sprintf("Location: %s?category=%d&page=%d&limit=%d", $_SERVER['PHP_SELF'], $form->GetValue('category'), $form->GetValue('page'), $form->GetValue('limit')));
}
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
		<h1>Unsupplied Products</h1>
		<?php
		$data = new DataQuery(sprintf("SELECT Category_Title FROM product_categories WHERE Category_ID=%d", mysql_real_escape_string($category)));
		$title = $data->Row['Category_Title'];
		$data->Disconnect();
		?>
		<p>Viewing products in the <?php print $title; ?> category.</p>
        <?php
        if($category > 0) {
        	if(!$form->Valid){
        		echo $form->GetError();
        		echo "<br />";
        	}

        	echo $form->Open();
        	echo $form->GetHTML('confirm');
        	echo $form->GetHTML('action');
        	echo $form->GetHTML('category');
        	echo $form->GetHTML('limit');
        	echo $form->GetHTML('page');

        	if(count($products) > 0) {
				?>
				<br />
				<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
					<thead>
						<tr>
							<th class="dataHeadOrdered" nowrap>ID <span class="iconOrdered"><img src="./images/icon_ordered_asc_1.gif" width="11" height="10"></span></th>
							<th nowrap>SKU <span class="iconOrdered"><img src="./images/blank.gif" width="11" height="10"></span></th>
							<th nowrap>Product <span class="iconOrdered"><img src="./images/blank.gif" width="11" height="10"></span></th>
							<?php /*<th nowrap>Beaten Cost<span class="iconOrdered"><img src="./images/blank.gif" width="11" height="10"></span></th>*/ ?>
							<th nowrap>Cost<span class="iconOrdered"><img src="./images/blank.gif" width="11" height="10"></span></th>
						</tr>
					</thead>
					<tbody>

					<?php
					foreach($products as $k=>$v) {
						?>

						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td class="dataOrdered" align="left"><?php print $k; ?>&nbsp;</td>
							<td align="left"><?php print $form->GetHTML('sku_'.$k); ?></td>
							<td align="left"><?php print $v['Product_Title']; ?>&nbsp;</td>
							<?php /*<td align="right"><?php print ($v['Beaten_Cost'] == -1) ? '-' : '&pound;'.number_format($v['Beaten_Cost'], 2, '.', ','); ?>&nbsp;</td>*/ ?>
							<td align="right">&pound;<?php print $form->GetHTML('cost_'.$k); ?></td>
						</tr>

						<?php
					}
					?>
					</tbody>
				</table>
				<br />
				<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataNav_1">
					<tr>
						<td nowrap><img src="images/icon_pages_1.gif" width="14" height="15" align="absmiddle"> <?php print $page; ?> of <?php print ceil($totalRows/$limit); ?> </td>
						<?php
						if($page == 1) {
						?>
							<td class="navCell_1"><span class="fade">First</span></td>
							<td class="navCell_1"><span class="fade">Previous</span></td>
						<?php
						} else {
						?>
							<td class="navCell_1" onMouseOver="setClassName(this, 'navCell_2');" onMouseOut="setClassName(this, 'navCell_1');"><a href="<?php print $_SERVER['PHP_SELF']; ?>?page=1&category=<?php print $category; ?>" width="70" align="center">First</a></td>
							<td class="navCell_1" onMouseOver="setClassName(this, 'navCell_2');" onMouseOut="setClassName(this, 'navCell_1');"><a href="<?php print $_SERVER['PHP_SELF']; ?>?page=<?php print $page-1; ?>&category=<?php print $category; ?>" width="70" align="center">Previous</a></td>
						<?php
						}

						$start = 1;
						$end = ($totalRows < 10)?$totalRows:10;

						if(($page - 4) > 2){
							$start = $page - 4;
							if(($start + 9) > $totalRows){
								$end = $totalRows;
								$start = (($end-9)>0)?$end - 9:1;
							} else {
								$end = $start + 9;
							}
						}

						if($end > ceil($totalRows/$limit)) {
							$end = ceil($totalRows/$limit);
						}

						for($i=$start; $i<$end+1; $i++){
							$tempClass = "navCell_1";
							$tempScript = " onMouseOver=\"setClassName(this, 'navCell_2');\" onMouseOut=\"setClassName(this, 'navCell_1');\"";
							if(($i) == $page){
								$tempClass = "navCell_2";
								$tempScript = "";
							}
							$newLink =  sprintf("%s?page=%s&category=%d", $_SERVER['PHP_SELF'], $i, $category);
							echo sprintf("<td class=\"%s\" %s><a href=\"%s\">%s</a></td>", $tempClass, $tempScript, $newLink, $i);
						}

						if($page == ceil($totalRows/$limit)) {
						?>
							<td class="navCell_1"><span class="fade">Next</span></td>
							<td class="navCell_1"><span class="fade">Last</span></td>
						<?php
						} else {
						?>
							<td class="navCell_1" onMouseOver="setClassName(this, 'navCell_2');" onMouseOut="setClassName(this, 'navCell_1');"><a href="<?php print $_SERVER['PHP_SELF']; ?>?page=<?php print $page+1; ?>&category=<?php print $category; ?>" width="70" align="center">Next</a></td>
							<td class="navCell_1" onMouseOver="setClassName(this, 'navCell_2');" onMouseOut="setClassName(this, 'navCell_1');"><a href="<?php print $_SERVER['PHP_SELF']; ?>?page=<?php print ceil($totalRows/$limit); ?>&category=<?php print $category; ?>" width="70" align="center">Last</a></td>
						<?php
						}
						?>
					</tr>
				</table>
				<br />

				<?php
				echo sprintf('<input type="submit" name="update" value="Update" class="submit" tabindex="%s">', $form->GetTabIndex());
        	} else {
        		echo '<p>There are no products available for viewing.</p>';
        	}

        	echo $form->Close();
        } else {
        	echo '<p>Please select a category of products to report on.</p>';

        	$data = new DataQuery(sprintf("select pc.* from product_categories as pc INNER JOIN supplier_categories AS sc ON sc.Category_ID=pc.Category_ID where pc.Is_Active='Y' AND sc.Supplier_ID=%d", $supplier->ID));

        	echo '<ul class="warehouse">';
        	while($data->Row){
        		echo sprintf('<li><a href="%s?category=%d" title="%s">%s</a></li>', $_SERVER['PHP_SELF'], $data->Row['Category_ID'], $data->Row['Category_Title'], $data->Row['Category_Title']);
        		$data->Next();
        	}
        	echo '</ul>';

        	$data->Disconnect();
        }
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