<?php
require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if(!$secure) {
	redirect('Location: login.php');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<style>
		body, th, td {
			font-family: arial, sans-serif;
			font-size: 0.8em;
		}
		h1, h2, h3, h4, h5, h6 {
			margin-bottom: 0;
			padding-bottom: 0;
		}
		h1 {
			font-size: 1.6em;
		}
		h2 {
			font-size: 1.2em;
		}
		p {
			margin-top: 0;
		}
	</style>
</head>
<body>

	<h1>Home</h1>
	
	<ul>
		<li><a href="report_appointments.php" title="Appointments Report" target="i_content">Appointments</a></li>
		<li><a href="report_sales.php" title="Sales Report" target="i_content">Sales</a></li>
		<li><a href="report_invoices.php" title="Invoices Report" target="i_content">Invoices</a></li>
		<li><a href="report_profit_control.php" title="Profit Control Report" target="i_content">Profit Control</a></li>
		<li><a href="report_shipping.php" title="Shipping Report" target="i_content">Shipping</a></li>
		<li><a href="report_packing.php" title="Packing Report" target="i_content">Packing</a></li>
		<li><a href="report_reorders.php" title="Reorders Report" target="i_content">Reorders</a></li>
		<li><a href="report_percentages.php" title="Customer Percentages Report" target="i_content">Percentages</a></li>
		<li><a href="report_schedule.php" title="Schedule Report" target="i_content">Schedule</a></li>
		<li><a href="report_average_value.php" title="Average Value Report" target="i_content">Average Value</a></li>
		<li><a href="report_products.php" title="Product Report" target="i_content">Products</a></li>
		<li><a href="report_supplier_savings.php" title="Supplier Savings Report" target="i_content">Supplier Savings</a></li>
		<li><a href="report_product_creations.php" title="Product Creations Report" target="i_content">Product Creations</a></li>
		<li><a href="report_order_markup.php" title="Order Markup Report" target="i_content">Order Markup</a></li>
		<li><a href="report_uncosted.php" title="Uncosted Report" target="i_content">Uncosted</a></li>		<li><a href="report_accounts.php" title="Accounts Report" target="i_content">Accounts</a></li>
		<li><a href="report_stock.php" title="Stock Report" target="i_content">Stock</a></li>
		<li><a href="report_stock_summary.php" title="Stock Summary Report" target="i_content">Stock Summary</a></li>
		<li><a href="report_stock_turnover.php" title="Stock Turnover Report" target="i_content">Stock Turnover</a></li>
		<li><a href="report_order_turnover_monthly.php" title="Order Turnover Monthly Report" target="i_content">Order Turnover Monthly</a></li>
		<li><a href="report_ellwood_turnover.php" title="Ellwood Electrical Turnover Report" target="i_content">Ellwood Sales</a></li>
		<li><a href="report_holidays.php" title="Holidays" target="i_content">Holidays</a></li>
		<li><a href="report_warehouse_shipped.php" title="Warehouse Shipped" target="i_content">Warehouse Shipped</a></li>
		<li><a href="report_orders_year_overlay.php" title="Orders Year Overlay" target="i_content">Orders Year Overlay</a></li>
		<li><a href="report_orders_year_overlay_rep.php" title="Orders Year Overlay" target="i_content">Sales Rep Orders Year Overlay</a></li>
		<li><a href="report_orders_current.php" title="Orders Current" target="i_content">Orders Current</a></li>
	</ul>
	
</body>
</html>