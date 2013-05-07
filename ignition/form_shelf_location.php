<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

if($action == 'report') {
	$session->Secure(3);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	$page = new Page('Shelf Location Form');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('location', 'Shelf Location', 'select', '', 'anything', 0, 255, true);

	$data = new DataQuery(sprintf("SELECT DISTINCT ws.Shelf_Location FROM warehouse AS w INNER JOIN warehouse_stock AS ws ON ws.Warehouse_ID=w.Warehouse_ID INNER JOIN users AS u ON w.Type_Reference_ID=u.Branch_ID WHERE w.Type='B' AND u.User_ID=%d ORDER BY ws.Shelf_Location ASC", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	while($data->Row) {
		$form->AddOption('location', $data->Row['Shelf_Location'], $data->Row['Shelf_Location']);
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		report($form->GetValue('location'));
		exit;
	}

	$page->Display('header');
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Stock check for Products from a Category.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('location'), $form->GetHtml('location') . $form->GetIcon('location'));
	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $form->Close();
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function report($location) {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 1, 12);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$form->AddField('order','Sort by','select','O','alpha_numeric',0,40,false);
	$form->AddOption('order','N','Product Name');
	$form->AddOption('order','Q','Quickfind');
	$form->AddOption('order','O','Preferred Supplier');
	$form->AddOption('order','I','Qty Incoming');
	$form->AddOption('order','S','Qty Stocked');

	if($form->GetValue('order')=='Q'){
		$ordering = 'ws.Product_ID';
	}elseif($form->GetValue('order')=='S'){
		$ordering = 'ws.Quantity_In_Stock';
	}elseif($form->GetValue('order')=='O'){
		$ordering = 'o.Org_Name';
	} else {
		$ordering = 'p.Product_Title';
	}

	$stock = array();

	$data = new DataQuery(sprintf("SELECT ws.*, p.Product_Title, p.SKU, COUNT(c.Product_ID) AS Components,
							sp.Supplier_Product_ID, o.Org_Name FROM warehouse_stock ws
							INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID
							INNER JOIN users AS u ON w.Type_Reference_ID=u.Branch_ID
							INNER JOIN product AS p ON ws.Product_ID=p.Product_ID
							LEFT JOIN product_in_categories AS cat ON p.Product_ID=cat.Product_ID
							LEFT JOIN product_components AS c ON c.Component_Of_Product_ID=p.Product_ID
							LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID
							LEFT JOIN supplier AS s ON s.Supplier_ID=sp.Supplier_ID
							LEFT JOIN contact AS c1 ON c1.Contact_ID=s.Contact_ID
							LEFT JOIN contact AS co ON co.Contact_ID=c1.Parent_Contact_ID
							LEFT JOIN organisation AS o ON o.Org_ID=co.Org_ID
							WHERE w.Type = 'B' AND u.User_ID = %d AND p.Discontinued <> 'Y'
							AND (sp.Product_ID IS NULL OR sp.Preferred_Supplier='Y')
							AND ws.Shelf_Location LIKE '%s'
							GROUP BY p.Product_ID Order By %s ASC",
							mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($location), mysql_real_escape_string($ordering)));

	while($data->Row) {
		if($data->Row['Components'] == 0 ) {
			$stockItem = array();
			$stockItem['id'] = $data->Row['Product_ID'];
			$stockItem['stock_id'] = $data->Row['Stock_ID'];
			$stockItem['supplier_product_id'] = $data->Row['Supplier_Product_ID'];
			$stockItem['name'] = strip_tags($data->Row['Product_Title']);
			$stockItem['sku'] = $data->Row['SKU'];
			$stockItem['supplier'] = (empty($data->Row['Org_Name'])) ? '&nbsp;' : $data->Row['Org_Name'];
			$stockItem['qty_stocked'] = $data->Row['Quantity_In_Stock'];
			$stockItem['location'] = $data->Row['Shelf_Location'];

			$stock[] = $stockItem;
		}
		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Shelf Location Form');
	$page->Display('header');

	$window = new StandardWindow('Sort products');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cat');
	echo $form->GetHTML('sub');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('order'),$form->GetHTML('order'));
	echo $webForm->AddRow('','<input type="submit" id="ordering" name="ordering" value="sort" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo "<br />";

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}
	?>
	<br />
	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		  <thead>
		  <tr>
			<th <?php print ($form->GetValue('order')=='N') ? 'class="dataHeadOrdered"' : ''; ?>nowrap><strong>Product Name</strong></td>
			<th nowrap><strong>Part Number</strong></td>
			<th <?php print ($form->GetValue('order')=='Q') ? 'class="dataHeadOrdered"' : ''; ?>nowrap><strong>Quickfind</strong></td>
			<th <?php print ($form->GetValue('order')=='O') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Preferred Supplier</strong></td>
			<th <?php print ($form->GetValue('order')=='I') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Qty Incoming </strong></td>
			<th <?php print ($form->GetValue('order')=='S') ? 'class="dataHeadOrdered"' : ''; ?>nowrap align="right"><strong>Qty Stocked</strong></td>
			<th <?php print ($form->GetValue('order')=='L') ? 'class="dataHeadOrdered"' : ''; ?>nowrap><strong>Shelf Location</strong></td>
		  </tr>
		  </thead>
		  <tbody>
		  <?php
		  for($i = 0; $i < count($stock); $i++) {
		  	?>
		  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			  	<td <?php print ($form->GetValue('order')=='N') ? 'class="dataOrdered"' : ''; ?>><a href="product_profile.php?pid=<?php echo $stock[$i]['id']; ?>"><?php echo $stock[$i]['name']; ?></a></td>
			  	<td><?php echo $stock[$i]['sku']; ?></td>
			  	<td <?php print ($form->GetValue('order')=='Q') ? 'class="dataOrdered"' : ''; ?>><?php echo $stock[$i]['id']; ?></td>
			  	<td <?php print ($form->GetValue('order')=='O') ? 'class="dataOrdered"' : ''; ?>><?php echo $stock[$i]['supplier']; ?></td>
			  	<td <?php print ($form->GetValue('order')=='I') ? 'class="dataOrdered"' : ''; ?>align="right"><?php echo $stock[$i]['qty_incoming']; ?></td>
			  	<td <?php print ($form->GetValue('order')=='S') ? 'class="dataOrdered"' : ''; ?>align="right"><?php echo $stock[$i]['qty_stocked']; ?></td>
			  	<td <?php print ($form->GetValue('order')=='L') ? 'class="dataOrdered"' : ''; ?>><?php echo $stock[$i]['location']; ?></td>
		  	</tr>
			 <?php
		  }
		  ?>
		  </tbody>
	</table>

	<br />

	<input type="button" class="btn" value="print" name="print" onclick="window.self.print();" />

	<?php
	echo $form->Close();
}
?>