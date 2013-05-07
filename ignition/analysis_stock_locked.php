<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/chart/libchart.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

$session->Secure(2);
view();
exit;

function view() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('months', 'Months Supply', 'select', '1', 'numeric_unsigned', 1, 11);

	for($i=1; $i<=12; $i++) {
		$form->AddOption('months', $i, $i);
	}

	$suppliers = array();
	
	$data = new DataQuery(sprintf('SELECT s.Supplier_ID, IF(LENGTH(o.Org_Name)>0, CONCAT_WS(\' \', o.Org_Name, CONCAT(\'(\', CONCAT_WS(\' \', p.Name_First, p.Name_Last), \')\')), CONCAT_WS(\' \', p.Name_First, p.Name_Last)) AS Supplier, COUNT(s.Product_ID) AS Products, SUM(s.Cost*s.Quantities) AS Cost FROM (SELECT p.Product_ID, s.Supplier_ID, s.Contact_ID, sp.Cost, SUM(ol.Quantities) AS Quantities FROM product AS p INNER JOIN supplier AS s ON s.Supplier_ID=p.LockedSupplierID LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Supplier_ID=s.Supplier_ID AND sp.Cost>0 LEFT JOIN (SELECT ol.Product_ID, SUM(ol.Quantity) AS Quantities FROM order_line AS ol INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY ol.Product_ID UNION ALL SELECT pc.Product_ID, SUM(ol.Quantity*pc.Component_Quantity) AS Quantities FROM product_components AS pc INNER JOIN order_line AS ol ON ol.Product_ID=pc.Component_Of_Product_ID INNER JOIN orders AS o ON o.Order_ID=ol.Order_ID AND o.Created_On>=ADDDATE(NOW(), INTERVAL -%d MONTH) WHERE ol.Product_ID>0 AND ol.Despatch_ID>0 GROUP BY pc.Product_ID) AS ol ON ol.Product_ID=p.Product_ID GROUP BY p.Product_ID ORDER BY p.Product_ID ASC) AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID GROUP BY s.Supplier_ID ORDER BY Supplier ASC', mysql_real_escape_string($form->GetValue('months')), mysql_real_escape_string($form->GetValue('months'))));
	while($data->Row) {
		$suppliers[$data->Row['Supplier_ID']] = $data->Row;

		$data->Next();	
	}
	$data->Disconnect();

	$chart1FileName = 'analysis_stock_locked-products' . rand(0, 9999999);
	$chart1Width = 600;
	$chart1Height = 400;
	$chart1Title = sprintf('Ownership of number of locked products for the last %d months for each supplier', $form->GetValue('months'));
	$chart1Reference = sprintf('temp/charts/chart_%s.png', $chart1FileName);

	$chart2FileName = 'analysis_stock_locked-costs' . rand(0, 9999999);
	$chart2Width = 600;
	$chart2Height = 400;
	$chart2Title = sprintf('Ownership of costs of locked products for the last %d months for each supplier', $form->GetValue('months'));
	$chart2Reference = sprintf('temp/charts/chart_%s.png', $chart2FileName);

	$chart1 = new PieChart($chart1Width, $chart1Height);
	$chart2 = new PieChart($chart2Width, $chart2Height);

	$totalCost = 0;
	$totalProducts = 0;
		
	foreach($suppliers as $supplierId=>$supplierData) {
		$totalCost += $supplierData['Cost'];
		$totalProducts += $supplierData['Products'];
	}
			
	foreach($suppliers as $supplierId=>$supplierData) {
		$supplierName1 = sprintf('%s - %s', $supplierData['Products'], $supplierData['Supplier']);
		$supplierName2 = sprintf('£%s - %s', number_format($supplierData['Cost'], 2, '.', ','), $supplierData['Supplier']);
			
		$chart1->addPoint(new Point($supplierName1, $supplierData['Products']));
		$chart2->addPoint(new Point($supplierName2, $supplierData['Cost'] / 1000));
	}

	$chart1->SetTitle($chart1Title);
	$chart1->SetLabelY('Distribution of products');
	$chart1->ShowText = false;
	$chart1->ShowLabels = true;
	$chart1->render($chart1Reference);

	$chart2->SetTitle($chart2Title);
	$chart2->SetLabelY('Distribution of costs');
	$chart2->ShowText = false;
	$chart2->ShowLabels = true;
	$chart2->render($chart2Reference);

	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleProducts = function(id, reference) {
			var element = document.getElementById(\'products-\' + id + \'-\' + reference);
			var image = document.getElementById(\'image-\' + id + \'-\' + reference);
			
			if(element) {
				if(element.style.display == \'table-row\') {
					element.style.display = \'none\';
					image.src = \'images/button-plus.gif\';
				} else {
					element.style.display = \'table-row\';
					image.src = \'images/button-minus.gif\';
				}
			}
		}
		</script>');

	$page = new Page('Analysis / Stock Locked', 'Analysing locked stock for products.');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Analysis parameters');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Configure your analysis parameters here.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('months'), $form->GetHTML('months'));
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	?>

	<br />
	<h3>Analysis Summary</h3>
	<p>
		Summary of supplier distribution for products for the last <strong><?php echo $form->GetValue('months'); ?></strong> months.<br />
		Total cost of locked products <strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong>.<br />
		Total number of locked products <strong><?php echo $totalProducts; ?></strong>.
	</p>

	<div style="text-align: center;">
		<img src="<?php echo $chart1Reference; ?>" width="<?php print $chart1Width; ?>" height="<?php print $chart1Height; ?>" alt="<?php print $chart1Title; ?>" /><br />
		<img src="<?php echo $chart2Reference; ?>" width="<?php print $chart2Width; ?>" height="<?php print $chart2Height; ?>" alt="<?php print $chart2Title; ?>" /><br />
	</div>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}