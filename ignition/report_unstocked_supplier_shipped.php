<?php
require_once('lib/common/app_header.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
    $form->AddField('start', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date Range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
	$form->AddOption('range', 'all', '-- All --');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'thisminute', 'This Minute');
	$form->AddOption('range', 'thishour', 'This Hour');
	$form->AddOption('range', 'thisday', 'This Day');
	$form->AddOption('range', 'thismonth', 'This Month');
	$form->AddOption('range', 'thisyear', 'This Year');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lasthour', 'Last Hour');
	$form->AddOption('range', 'last3hours', 'Last 3 Hours');
	$form->AddOption('range', 'last6hours', 'Last 6 Hours');
	$form->AddOption('range', 'last12hours', 'Last 12 Hours');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastday', 'Last Day');
	$form->AddOption('range', 'last2days', 'Last 2 Days');
	$form->AddOption('range', 'last3days', 'Last 3 Days');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');
    $form->AddField('supplier', 'Supplier', 'select', '', 'anything', 1, 11);
    $form->AddGroup('supplier', 'Y', 'Favourites');
    $form->AddGroup('supplier', 'N', 'Non Favourites');
	$form->AddOption('supplier', '', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, s.Is_Favourite, IF((LENGTH(TRIM(o.Org_Name)) > 0) AND (LENGTH(TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last))) > 0), CONCAT_WS(' ', TRIM(o.Org_Name), CONCAT('(', TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)), ')')), IF(LENGTH(TRIM(o.Org_Name)) > 0, TRIM(o.Org_Name), TRIM(CONCAT_WS(' ', p.Name_First, p.Name_Last)))) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID ORDER BY o.Org_Name ASC, Supplier_Name ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name'], $data->Row['Is_Favourite']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			$form->Validate('range');
			$form->Validate('supplier');

			if($form->Valid) {
				switch($form->GetValue('range')) {
					case 'all': 		$start = date('Y-m-d H:i:s', 0);
					$end = date('Y-m-d H:i:s');
					break;

					case 'thisminute': 	$start = date('Y-m-d H:i:00');
					$end = date('Y-m-d H:i:s');
					break;
					case 'thishour': 	$start = date('Y-m-d H:00:00');
					$end = date('Y-m-d H:i:s');
					break;
					case 'thisday': 	$start = date('Y-m-d 00:00:00');
					$end = date('Y-m-d H:i:s');
					break;
					case 'thismonth': 	$start = date('Y-m-01 00:00:00');
					$end = date('Y-m-d H:i:s');
					break;
					case 'thisyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")));
					$end = date('Y-m-d H:i:s');
					break;

					case 'lasthour': 	$start = date('Y-m-d H:00:00', mktime(date("H")-1, 0, 0, date("m"), date("d"),  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
					break;
					case 'last3hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-3, 0, 0, date("m"), date("d"),  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
					break;
					case 'last6hours': 	$start = date('Y-m-d H:00:00', mktime(date("H")-6, 0, 0, date("m"), date("d"),  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
					break;
					case 'last12hours': $start = date('Y-m-d H:00:00', mktime(date("H")-12, 0, 0, date("m"), date("d"),  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(date("H"), 0, 0, date("m"), date("d"),  date("Y")));
					break;

					case 'lastday': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-1, date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
					break;
					case 'last2days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-2, date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
					break;
					case 'last3days': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-3, date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), date("d"),  date("Y")));
					break;

					case 'lastmonth': 	$start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-1, 1,  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
					break;
					case 'last3months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-3, 1,  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
					break;
					case 'last6months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-6, 1,  date("Y")));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1,  date("Y")));
					break;

					case 'lastyear': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-1));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
					break;
					case 'last2years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-2));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
					break;
					case 'last3years': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 1, 1, date("Y")-3));
					$end = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1,  date("Y")));
					break;
				}

				redirect(sprintf('Location: %s?action=report&start=%s&end=%s&supplier=%d', $_SERVER['PHP_SELF'], $start, $end, $form->GetValue('supplier')));
			}
		} else {
            $form->Validate('start');
            $form->Validate('end');
			$form->Validate('supplier');

			if($form->Valid) {
				$start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2));
				$end = (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2))))));

				redirect(sprintf('Location: %s?action=report&start=%s&end=%s&supplier=%d', $_SERVER['PHP_SELF'], $start, $end, $form->GetValue('supplier')));
			}
		}
	}

	$page = new Page('Unstocked Supplier Shipped Report', 'Please choose a supplier and period for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Unstocked Supplier Shipped.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

    echo $window->Open();
    echo $window->AddHeader('Select a supplier for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Select one of the predefined date ranges for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('range'), $form->GetHTML('range'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Or select the date range from below for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('start'), $form->GetHTML('start'));
	echo $webForm->AddRow($form->GetLabel('end'), $form->GetHTML('end'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiryLine.php');
    require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

    $form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
    $form->AddField('start', 'Start Date', 'hidden', '', 'anything', 1, 19);
	$form->AddField('end', 'End Date', 'hidden', '', 'anything', 1, 19);
    $form->AddField('supplier', 'Supplier', 'hidden', '0', 'anything', 1, 11);
    $form->AddField('priceenquiry', 'Price Enquiry', 'select', '0', 'anything', 1, 11);
    $form->AddOption('priceenquiry', '0', '');

    $data = new DataQuery(sprintf("SELECT Price_Enquiry_ID, Created_On FROM price_enquiry WHERE Status LIKE 'Pending' ORDER BY Price_Enquiry_ID ASC"));
    while($data->Row) {
		$form->AddOption('priceenquiry', $data->Row['Price_Enquiry_ID'], sprintf('#%d: %s', $data->Row['Price_Enquiry_ID'], $data->Row['Created_On']));

    	$data->Next();
	}
	$data->Disconnect();

    new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_product SELECT ol.Product_ID, COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantity FROM order_line AS ol INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>='%s' AND o.Created_On<'%s' WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY ol.Product_ID", mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	new DataQuery(sprintf("ALTER TABLE temp_product ADD INDEX Product_ID (Product_ID)"));

	$products = array();

    $data = new DataQuery(sprintf("SELECT COUNT(DISTINCT o.Order_ID) AS Orders, SUM(ol.Quantity) AS Quantity, p.Product_ID, p.Product_Title, p.Position_Quantities_Recent, p.Position_Orders_Recent, tp.Orders AS Total_Orders, tp.Quantity AS Total_Quantity FROM orders AS o INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID INNER JOIN warehouse AS w ON w.Warehouse_ID=ol.Despatch_From_ID AND w.Type='S' AND w.Type_Reference_ID=%d INNER JOIN product AS p ON p.Product_ID=ol.Product_ID AND p.Is_Stocked='N' LEFT JOIN temp_product AS tp ON tp.Product_ID=ol.Product_ID WHERE o.Created_On>='%s' AND o.Created_On<'%s' AND ol.Despatch_ID>0 GROUP BY p.Product_ID ORDER BY Orders DESC, Quantity DESC", mysql_real_escape_string($form->GetValue('supplier')), mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$products[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

    foreach($products as $product) {
		$form->AddField('select_'.$product['Product_ID'], 'Select Product', 'checkbox', 'N', 'boolean', 1, 1, false);
	}

    if(isset($_REQUEST['confirm'])) {
    	if(isset($_REQUEST['createexport'])) {
			if($form->Validate()) {
                $fileDate = getDatetime();
				$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

				$fileName = sprintf('blt_unstocked_supplier_shipped_%s.csv', $fileDate);

				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Content-Type: application/force-download");
				header("Content-Disposition: attachment; filename=" . basename($fileName) . ";");
				header("Content-Transfer-Encoding: binary");

				$line = array();
				$line[] = 'Product ID';
				$line[] = 'Product Name';
				$line[] = 'Orders';
				$line[] = 'Quantity';

				echo getCsv($line);

				foreach($products as $product) {
					if($form->GetValue('select_'.$product['Product_ID']) == 'Y') {
						$line = array();
						$line[] = $product['Product_ID'];
						$line[] = $product['Product_Title'];
						$line[] = $product['Orders'];
						$line[] = $product['Quantity'];

						echo getCsv($line);
					}
				}

				exit;
			}
		} elseif(isset($_REQUEST['createpriceenquiry'])) {
			if($form->Validate()) {
				if($form->GetValue('priceenquiry') > 0) {
					$priceEnquiry = new PriceEnquiry($form->GetValue('priceenquiry'));
					$priceEnquiry->GetLines();

                    foreach($products as $product) {
                    	if($form->GetValue('select_'.$product['Product_ID']) == 'Y') {
                    		$exists = false;

                    		for($i=0; $i<count($priceEnquiry->Line); $i++) {
                    			if($priceEnquiry->Line[$i]->Product->ID == $product['Product_ID']) {
									$exists = true;
									break;
								}
							}

							if(!$exists) {
								$quantity = (round($product['Total_Quantity'] / 10) * 10);

								if($quantity > 0) {
									$priceEnquiry->AddLine($product['Product_ID'], $quantity, $product['Total_Orders']);
								}
							}
						}
					}

					$priceEnquiry->Recalculate();
				} else {
            		$priceEnquiry = new PriceEnquiry();
					$priceEnquiry->Status = 'Pending';
					$priceEnquiry->Add();

					foreach($products as $product) {
						if($form->GetValue('select_'.$product['Product_ID']) == 'Y') {
							$quantity = (round($product['Total_Quantity'] / 10) * 10);

							if($quantity > 0) {
								$priceEnquiry->AddLine($product['Product_ID'], $quantity, $product['Total_Orders']);
							}
						}
					}

					$priceEnquiry->Recalculate();
				}

				redirect(sprintf("Location: price_enquiry_details.php?id=%d", $priceEnquiry->ID));
			}
		}
	}

	$page = new Page('Unstocked Supplier Shipped Report: ' . cDatetime($form->GetValue('start'), 'longdate') . ' to ' . cDatetime($form->GetValue('end'), 'longdate'), '');
	$page->Display('header');

    if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

    echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('start');
	echo $form->GetHTML('end');
	echo $form->GetHTML('supplier');

    $window = new StandardWindow("Report Options");
	$webForm = new StandardForm;

    echo $window->Open();
    echo $window->AddHeader('Select a pending price enquiry to feed selected products into.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('priceenquiry'), $form->GetHTML('priceenquiry'));
	echo $webForm->AddRow('', '<input type="submit" name="createpriceenquiry" value="price enquiry" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();

    echo $window->AddHeader('Create an export of the selected products.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('', '<input type="submit" name="createexport" value="export" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo '<br />';
	?>

	<h3>Unstocked Products Supplied</h3>
	<p>Listing all products unstocked shipped by third party suppliers and total orders and quantities for suppliers over the last 3 months.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa">&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind</strong><br />&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product</strong><br />&nbsp;</td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Position (Quantities)</strong><br />For All Suppliers</td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Position (Orders)</strong><br />For All Suppliers</td>
            <td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Orders</strong><br />For All Suppliers</td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quantity</strong><br />For All Suppliers</td>
            <td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Orders</strong><br />For Period</td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quantity</strong><br />For Period</td>
		</tr>

		<?php
		if(count($products) > 0) {
			foreach($products as $product) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td width="1%"><?php echo $form->GetHTML('select_'.$product['Product_ID']); ?></td>
					<td><?php echo $product['Product_ID']; ?></td>
					<td><a href="product_profile.php?pid=<?php echo $product['Product_ID']; ?>" target="_blank"><?php echo $product['Product_Title']; ?></a></td>
					<td><?php echo ($product['Position_Quantities_Recent'] > 0) ? $product['Position_Quantities_Recent'] : '&nbsp;'; ?></td>
					<td><?php echo ($product['Position_Orders_Recent'] > 0) ? $product['Position_Orders_Recent'] : '&nbsp;'; ?></td>
                    <td align="right"><?php echo $product['Total_Orders']; ?></td>
					<td align="right"><?php echo $product['Total_Quantity']; ?></td>
                    <td align="right"><?php echo $product['Orders']; ?></td>
					<td align="right"><?php echo $product['Quantity']; ?></td>
				</tr>

				<?php
			}
		} else {
			?>

            <tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td align="center" colspan="9">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		?>

	</table>

	<?php
    echo $form->Close();

	$page->Display('footer');
}

function getCsv($row, $fd=',', $quot='"') {
	$str ='';

	foreach($row as $cell) {
		$cell = str_replace($quot, $quot.$quot, $cell);

		if((strchr($cell, $fd) !== false) || (strchr($cell, $quot) !== false) || (strchr($cell, "\n") !== false)) {
			$str .= $quot.$cell.$quot.$fd;
		} else {
			$str .= $quot.$cell.$quot.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}