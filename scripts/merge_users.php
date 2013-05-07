<?php
ini_set('max_execution_time', '900');
ini_set('display_errors','on');

require_once("../ignition/lib/common/config.php");
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/common/generic.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/MySQLConnection.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

$GLOBALS['DBCONNECTION'] = new MySqlConnection();

$tables = array();
$tables[] = 'address';
$tables[] = 'article';
$tables[] = 'article_category';
$tables[] = 'article_download';
$tables[] = 'campaign';
$tables[] = 'campaign_contact';
$tables[] = 'campaign_contact_event';
$tables[] = 'campaign_event';
$tables[] = 'contact';
$tables[] = 'contact_group';
$tables[] = 'contact_group_assoc';
$tables[] = 'countries';
$tables[] = 'coupon';
$tables[] = 'coupon_contact';
$tables[] = 'coupon_product';
$tables[] = 'credit_note';
$tables[] = 'credit_note_line';
$tables[] = 'customer';
$tables[] = 'customer_contact';
$tables[] = 'customer_product';
$tables[] = 'debit';
$tables[] = 'debit_line';
$tables[] = 'despatch';
$tables[] = 'despatch_line';
$tables[] = 'discount_banding';
$tables[] = 'discount_customer';
$tables[] = 'discount_product';
$tables[] = 'discount_schema';
$tables[] = 'document';
$tables[] = 'enquiry';
$tables[] = 'enquiry_line';
$tables[] = 'enquiry_line_document';
$tables[] = 'enquiry_line_quote';
$tables[] = 'enquiry_template';
$tables[] = 'enquiry_type';
$tables[] = 'invoice';
$tables[] = 'invoice_line';
$tables[] = 'link';
$tables[] = 'order_note';
$tables[] = 'orders';
$tables[] = 'organisation';
$tables[] = 'payment';
$tables[] = 'person';
$tables[] = 'postage';
$tables[] = 'product';
$tables[] = 'product_categories';
$tables[] = 'product_components';
$tables[] = 'product_images';
$tables[] = 'product_in_categories';
$tables[] = 'product_offers';
$tables[] = 'product_options';
$tables[] = 'product_prices';
$tables[] = 'product_related';
$tables[] = 'proforma';
$tables[] = 'purchase';
$tables[] = 'purchase_batch';
$tables[] = 'purchase_batch_line';
$tables[] = 'purchase_line';
$tables[] = 'quote';
$tables[] = 'quote_note';
$tables[] = 'regions';
$tables[] = '`return`';
$tables[] = 'return_line';
$tables[] = 'return_note';
$tables[] = 'return_reason';
$tables[] = 'settings';
$tables[] = 'shipping';
$tables[] = 'shipping_class';
$tables[] = 'shipping_postage';
$tables[] = 'supplier';
$tables[] = 'supplier_markup';
$tables[] = 'supplier_product';
$tables[] = 'tax';
$tables[] = 'tax_class';
$tables[] = 'units';
$tables[] = 'warehouse';
$tables[] = 'warehouse_stock';

foreach($tables as $table) {
	$data = new DataQuery(sprintf("UPDATE %s SET Created_By=8 WHERE Created_By=21", $table));
	$data->Disconnect();

	echo sprintf("UPDATE %s SET Created_By=8 WHERE Created_By=21<br />", $table);
}

$tables = array();
$tables[] = 'campaign_contact';
$tables[] = 'enquiry';

foreach($tables as $table) {
	$data = new DataQuery(sprintf("UPDATE %s SET Created_By=8 WHERE Created_By=21", $table));
	$data->Disconnect();

	echo sprintf("UPDATE %s SET Owned_By=8 WHERE Owned_By=21<br />", $table);
}

$GLOBALS['DBCONNECTION']->Close();
?>