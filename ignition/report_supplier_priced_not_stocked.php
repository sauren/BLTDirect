<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} elseif($action == 'export') {
	$session->Secure(2);
	export();
	exit;
} else {
	$session->Secure(2);
	start();
	exit();
}

function getCsv($row, $fd=',', $quot='"') {
	$str ='';

	foreach($row as $cell){
		$cell = str_replace($quot, $quot.$quot, $cell);

		if((strchr($cell, $fd) !== false) || (strchr($cell, $quot) !== false) || (strchr($cell, "\n") !== false)) {
			$str .= $quot.$cell.$quot.$fd;
		} else {
			$str .= $quot.$cell.$quot.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}

function start(){
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
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
	$form->AddField('supplier', 'Supplier', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('supplier', '0', '');
	$form->AddField('compare', 'Compare Against', 'selectmultiple', '', 'numeric_unsigned', 1, 11);

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, IF(c.Parent_Contact_ID>0, CONCAT_WS(' ', o.Org_Name, CONCAT('(', CONCAT_WS(' ', p.Name_First, p.Name_Last), ')')), CONCAT_WS(' ', p.Name_First, p.Name_Last)) AS Supplier FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY Supplier ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier']);
		$form->AddOption('compare', $data->Row['Supplier_ID'], $data->Row['Supplier']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('positionfrom', 'From Position', 'text', '1', 'numeric_unsigned', 1, 11);
	$form->AddField('positionto', 'To Position', 'text', '1000', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
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

			redirectTo(sprintf('?action=report&start=%s&end=%s&supplier=%d&compare=%s&positionfrom=%d&positionto=%d', $start, $end, $form->GetValue('supplier'), implode(',', $form->GetValue('compare')), $form->GetValue('positionfrom'), $form->GetValue('positionto')));
		} else {
			if($form->Validate()) {
				redirectTo(sprintf('?action=report&start=%s&end=%s&supplier=%d&compare=%s&positionfrom=%d&positionto=%d', sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('supplier'), implode(',', $form->GetValue('compare')), $form->GetValue('positionfrom'), $form->GetValue('positionto')));
			}
		}
	}

	$page = new Page('Supplier Priced Not Stocked Report', 'Please choose a start and end date for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Report on Stock Costs.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Filter out products sold for particular orders.');
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

	echo $window->AddHeader('Select supplier details.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier'));
	echo $webForm->AddRow($form->GetLabel('compare'), $form->GetHTML('compare'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Select product position criteria.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('positionfrom'), $form->GetHTML('positionfrom'));
	echo $webForm->AddRow($form->GetLabel('positionto'), $form->GetHTML('positionto'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('', '<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	$form->AddField('end', 'End Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	$form->AddField('supplier', 'Supplier', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('compare', 'Compare Against', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('positionfrom', 'Position From', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('positionto', 'Position To', 'hidden', '', 'numeric_unsigned', 1, 11);
	
	$supplier = new Supplier();
	$supplier->Get($form->GetValue('supplier'));
	$supplier->Contact->Get();

	$compare = explode(',', $form->GetValue('compare'));

	$page = new Page('Supplier Priced Not Stocked Report: ' . cDatetime($form->GetValue('start'), 'longdatetime') . ' to ' . cDatetime($form->GetValue('end'), 'longdatetime'), '');
	$page->Display('header');
	?>

	<h3><?php echo $supplier->Contact->Person->GetFullName(); ?></h3>
	<br />

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Product Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>SKU</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Priced On</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;" align="right" nowrap="nowrap"><strong>Cost</strong><br /><?php echo $supplier->Contact->Person->GetFullName(); ?></td>

			<?php
			foreach($compare as $compareItem) {
				$compareObj = new Supplier();
				$compareObj->Get($compareItem);
				$compareObj->Contact->Get();
				
				echo sprintf('<td style="border-bottom:1px solid #aaaaaa;" align="right" nowrap="nowrap"><strong>Cost</strong><br />%s</td>', $compareObj->Contact->Person->GetFullName());
			}
			?>
		</tr>

		<?php
		$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU, sp.Cost, spp.Created_On AS Priced_On FROM supplier_product_price AS spp INNER JOIN product AS p ON p.Product_ID=spp.Product_ID AND p.Discontinued=\'N\' AND p.Is_Stocked=\'N\' AND p.Position_Orders>=%d%s LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Supplier_ID=spp.Supplier_ID WHERE spp.Supplier_ID=%d AND spp.Created_On BETWEEN \'%s\' AND \'%s\' GROUP BY p.Product_ID ORDER BY p.Product_Title ASC', ($form->GetValue('positionfrom') < 1) ? 1 : $form->GetValue('positionfrom'), ($form->GetValue('positionto') > 0) ? sprintf(' AND p.Position_Orders<=%d', mysql_real_escape_string($form->GetValue('positionto'))) : '', mysql_real_escape_string($form->GetValue('supplier')), mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
		while($data->Row) {
			$costs = array();

			$data2 = new DataQuery(sprintf("SELECT Supplier_ID, Cost FROM supplier_product WHERE Supplier_ID IN (%s) AND Product_ID=%d", implode(', ', $compare), mysql_real_escape_string($data->Row['Product_ID'])));
			while($data2->Row) {
				$costs[$data2->Row['Supplier_ID']] = $data2->Row['Cost'];

				$data2->Next();	
			}
			$data2->Disconnect();
			?>

			<tr>
				<td style="border-bottom: 1px dashed #aaaaaa;"><?php echo strip_tags($data->Row['Product_Title']); ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;"><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo $data->Row['Product_ID']; ?></a></td>
				<td style="border-bottom: 1px dashed #aaaaaa;"><?php echo $data->Row['SKU']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;"><?php echo $data->Row['Priced_On']; ?></td>
				<td style="border-bottom: 1px dashed #aaaaaa;" align="right">&pound;<?php echo number_format($data->Row['Cost'], 2, '.', ','); ?></td>

				<?php
				foreach($compare as $compareItem) {
					echo sprintf('<td style="border-bottom:1px dashed #aaaaaa;" align="right">&pound;%s</td>', isset($costs[$compareItem]) ? $costs[$compareItem] : number_format(0, 2));
				}
				?>
			</tr>
			
			<?php
			$data->Next();
		}
		$data->Disconnect();
		?>
		
	</table>
	<br />

	<input type="button" class="btn" name="export" value="export" onclick="window.self.location.href = '?action=export&start=<?php echo $form->GetValue('start'); ?>&end=<?php echo $form->GetValue('end'); ?>&supplier=<?php echo $form->GetValue('supplier'); ?>&compare=<?php echo $form->GetValue('compare'); ?>&positionfrom=<?php echo $form->GetValue('positionfrom'); ?>&positionto=<?php echo $form->GetValue('positionto'); ?>';" />

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function export() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'export', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Start Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	$form->AddField('end', 'End Date', 'hidden', '0000-00-00 00:00:00', 'anything', 1, 19);
	$form->AddField('supplier', 'Supplier', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('compare', 'Compare Against', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('positionfrom', 'Position From', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('positionto', 'Position To', 'hidden', '', 'numeric_unsigned', 1, 11);

	$fileDate = getDatetime();
	$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

	$fileName = sprintf('blt_supplier_prices_not_stocked_%s.csv', $fileDate);
	
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=" . basename($fileName) . ";");
	header("Content-Transfer-Encoding: binary");

	$compare = explode(',', $form->GetValue('compare'));

	$line = array();
	$line[] = 'Name';
	$line[] = 'Product ID';
	$line[] = 'SKU';
	$line[] = 'Priced On';
	$line[] = 'Cost';

	foreach($compare as $compareItem) {
		$compareObj = new Supplier();
		$compareObj->Get($compareItem);
		$compareObj->Contact->Get();
	
		$line[] = $compareObj->Contact->Person->GetFullName();	
	}

	echo getCsv($line);

	$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU, sp.Cost, spp.Created_On AS Priced_On FROM supplier_product_price AS spp INNER JOIN product AS p ON p.Product_ID=spp.Product_ID AND p.Discontinued=\'N\' AND p.Is_Stocked=\'N\' AND p.Position_Orders>=%d%s LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Supplier_ID=spp.Supplier_ID WHERE spp.Supplier_ID=%d AND spp.Created_On BETWEEN \'%s\' AND \'%s\' GROUP BY p.Product_ID ORDER BY p.Product_Title ASC', ($form->GetValue('positionfrom') < 1) ? 1 : $form->GetValue('positionfrom'), ($form->GetValue('positionto') > 0) ? sprintf(' AND p.Position_Orders<=%d', $form->GetValue('positionto')) : '', mysql_real_escape_string($form->GetValue('supplier')), mysql_real_escape_string($form->GetValue('start')), mysql_real_escape_string($form->GetValue('end'))));
	while($data->Row) {
		$costs = array();

		$data2 = new DataQuery(sprintf("SELECT Supplier_ID, Cost FROM supplier_product WHERE Supplier_ID IN (%s) AND Product_ID=%d", implode(', ', $compare), $data->Row['Product_ID']));
		while($data2->Row) {
			$costs[$data2->Row['Supplier_ID']] = $data2->Row['Cost'];

			$data2->Next();	
		}
		$data2->Disconnect();

		$line = array();
		$line[] = strip_tags($data->Row['Product_Title']);
		$line[] = $data->Row['Product_ID'];
		$line[] = $data->Row['SKU'];
		$line[] = $data->Row['Priced_On'];
		$line[] = $data->Row['Cost'];

		foreach($compare as $compareItem) {
			$line[] = isset($costs[$compareItem]) ? $costs[$compareItem] : number_format(0, 2);
		}

		echo getCsv($line);
		
		$data->Next();			
	}
	$data->Disconnect();
}