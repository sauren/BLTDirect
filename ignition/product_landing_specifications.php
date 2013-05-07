<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/ProductLanding.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/ProductLandingSpecification.php');

if($action == "moveup") {
	$session->Secure(3);
	moveup();
	exit;
} elseif($action == "movedown") {
	$session->Secure(3);
	movedown();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function moveup() {
	$spec = new ProductLandingSpecification($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT id, sequence FROM product_landing_specification WHERE sequence<%d AND landingId=%d ORDER BY sequence DESC LIMIT 0, 1", mysql_real_escape_string($spec->sequence), mysql_real_escape_string($spec->landingId)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE product_landing_specification SET Sequence=%d WHERE id=%d", $data->Row['sequence'], mysql_real_escape_string($spec->id)));
		new DataQuery(sprintf("UPDATE product_landing_specification SET Sequence=%d WHERE id=%d", mysql_real_escape_string($spec->sequence), $data->Row['id']));
	}
	$data->Disconnect();
	
	redirect(sprintf("Location: ?action=sequence&id=%d", $spec->landingId));
}

function movedown() {
	$spec = new ProductLandingSpecification($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT id, sequence FROM product_landing_specification WHERE sequence>%d AND landingId=%d ORDER BY sequence ASC LIMIT 0, 1", $spec->sequence, $spec->landingId));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE product_landing_specification SET sequence=%d WHERE id=%d", $data->Row['sequence'], mysql_real_escape_string($spec->id)));
		new DataQuery(sprintf("UPDATE product_landing_specification SET sequence=%d WHERE id=%d", mysql_real_escape_string($spec->sequence), $data->Row['id']));
	}
	$data->Disconnect();

	redirect(sprintf("Location: ?action=sequence&id=%d", $spec->landingId));
}

function view() {
	if(!isset($_REQUEST['id'])) {
		redirect('Location: product_landings.php');
	}

	$item = new ProductLanding();
	
	if(!$item->get($_REQUEST['id'])) {
		redirect('Location: product_landings.php');
	}

	$categories = array();
	
	if($item->category->ID > 0) {
		$categories = getCategories($item->category->ID);
	}
					
	$pool = array();
	$poolReversed = array();

	$data = new DataQuery(sprintf("SELECT psv.Value_ID FROM product AS p%s INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID AND ps.Value_ID=%d INNER JOIN product_specification AS ps2 ON ps2.Product_ID=p.Product_ID INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps2.Value_ID AND psv.Group_ID=%d WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' GROUP BY psv.Value_ID", ($item->category->ID > 0) ? sprintf(' INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID IN (%s)', implode(', ', $categories)) : '', mysql_real_escape_string($item->specValue->ID), mysql_real_escape_string($item->specGroup->ID)));
	while($data->Row) {
		$pool[$data->Row['Value_ID']] = $data->Row['Value_ID'];

		$data->Next();
	}
	$data->Disconnect();
	
	foreach($pool as $valueId=>$poolItem) {
		$pool[$valueId] = sprintf('psv.Value_ID=%d', $valueId);
		$poolReversed[$valueId] = sprintf('valueId<>%d', $valueId);
	}
	
	if(count($pool) > 0) {
		$data = new DataQuery(sprintf("SELECT psv.Value_ID FROM product_specification_value AS psv LEFT JOIN product_landing_specification AS pls ON pls.valueId=psv.Value_ID AND pls.landingId=%d WHERE pls.id IS NULL AND (%s) ORDER BY psv.Value ASC", mysql_real_escape_string($item->id), implode(' OR ', $pool)));
		while($data->Row) {
			$spec = new ProductLandingSpecification();
			$spec->landingId = $item->id;
			$spec->value->ID = $data->Row['Value_ID'];
			$spec->add();
			
			$data->Next();	
		}
		$data->Disconnect();

		new DataQuery(sprintf("DELETE FROM product_landing_specification WHERE landingId=%d AND (%s)", mysql_real_escape_string($item->id), mysql_real_escape_string(implode(' AND ',$poolReversed))));
	}
	
	$page = new Page(sprintf('<a href="?action=view">Product Landing</a> &gt; Sequence Specifications', $item->id), 'Here you can sequence the specifications for this product landing.');
	$page->Display('header');
	
	$table = new DataTable('values');
	$table->SetExtractVars();
	$table->SetSQL(sprintf("SELECT pls.id, psv.Value_ID, psv.Value FROM product AS p%s INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID AND ps.Value_ID=%d INNER JOIN product_specification AS ps2 ON ps2.Product_ID=p.Product_ID INNER JOIN product_specification_value AS psv ON psv.Value_ID=ps2.Value_ID AND psv.Group_ID=%d LEFT JOIN product_landing_specification AS pls ON pls.valueId=psv.Value_ID AND pls.landingId=%d WHERE ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End='0000-00-00 00:00:00') OR (p.Sales_Start='0000-00-00 00:00:00' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End='0000-00-00 00:00:00')) AND p.Is_Active='Y' AND p.Is_Demo_Product='N' GROUP BY psv.Value_ID", ($item->category->ID > 0) ? sprintf(' INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID AND pic.Category_ID IN (%s)', implode(', ', $categories)) : '', mysql_real_escape_string($item->specValue->ID), mysql_real_escape_string($item->specGroup->ID), mysql_real_escape_string($item->id)));
	$table->AddField('Value ID#', 'Value_ID', 'left');
	$table->AddField('Name', 'Value', 'left');
	$table->AddLink("?action=moveup&id=%s", "<img src=\"images/aztector_3.gif\" alt=\"Move up\" border=\"0\" />", "id");
	$table->AddLink("?action=movedown&id=%s", "<img src=\"images/aztector_4.gif\" alt=\"Move down\" border=\"0\" />", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("pls.sequence ASC, psv.Value");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
			
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function getCategories($categoryId) {
	$items = array($categoryId);
	
	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		$items = array_merge($items, getCategories($data->Row['Category_ID']));
		
		$data->Next();	
	}
	$data->Disconnect();
	
	return $items;
}