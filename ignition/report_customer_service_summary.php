<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
report();
exit();

function report(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/chart/libchart.php');

	$months = 36;

	$legend = array();
	$legend[] = "Customer Service Enq";
	$legend[] = "Returns created (R)";
	$legend[] = "Returns Created (D)";
	$legend[] = "Orders created (N)";
	$legend[] = "Orders created (B)";
	$legend[] = "Orders created (R)";

	$page = new Page('Monthly statistic reports for the: Last '.$months.' Months', '');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	$chartFileName = $GLOBALS['SESSION_USER_ID'].'_'.rand(0, 99999);
	$chartWidth = 900;
	$chartHeight = 600;
	$chartTitle = 'Monthly Statistics';
	$chartReference = sprintf('temp/charts/chart_%s.png', $chartFileName);

	$chart = new LineChart(900, 600, $legend);
	
	$enquiries = array();
	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(Created_On, '%%Y-%%m') AS Month, COUNT(Enquiry_ID) AS Enquiries FROM enquiry WHERE DATE_ADD(Created_On,INTERVAL $months MONTH) > NOW() AND Enquiry_Type_ID = 4
		GROUP BY Month
		ORDER BY Created_On ASC"));
	
	while($data->Row){
		$enquiries[$data->Row['Month']] = $data->Row['Enquiries'];
		$data->Next();
	}

	$sum1 = 0;
	foreach($enquiries as $k => $value){
		$sum1 += $value;
	}

	$Orders = array();
	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(Created_On, '%%Y-%%m') AS Month, COUNT(Order_ID) AS Orders, Order_Prefix
		FROM orders WHERE DATE_ADD(Created_On,INTERVAL $months MONTH)>NOW()AND Order_Prefix IN ('B', 'N', 'R')
		GROUP BY Month, Order_Prefix
		ORDER BY Created_On ASC"));
	
	while($data->Row){
		$Orders[$data->Row['Month']][$data->Row['Order_Prefix']] = $data->Row;
		$data->Next();
	}
	
	$sum2 = 0;
	foreach($Orders as $k => $subArray){
		foreach($subArray as $id => $value){
			if(in_array('N', $value)){
		$sum2 += $value['Orders'];
			} 
		}
	}

	$sum3 = 0;
	foreach($Orders as $k => $subArray){
		foreach($subArray as $id => $value){
			if(in_array('B', $value)){
		$sum3 += $value['Orders'];
			} 
		}
	}

	$sum4 = 0;
	foreach($Orders as $k => $subArray){
		foreach($subArray as $id => $value){
			if(in_array('R', $value)){
		$sum4 += $value['Orders'];
			} 
		}
	}

	$Returns = array();
	$data = new DataQuery(sprintf("SELECT DATE_FORMAT(Created_On, '%%Y-%%m') AS Month, COUNT(Return_ID) AS returnid,  Authorisation
		FROM `return` WHERE DATE_ADD(Created_On,INTERVAL $months MONTH)>NOW() AND Authorisation != 'N'
		GROUP BY Month, Authorisation
		ORDER BY Created_On ASC"));
	
	while($data->Row){
		$Returns[$data->Row['Month']][$data->Row['Authorisation']] = $data->Row;
		$data->Next();
	}

	$sum5 = 0;
	foreach($Returns as $k => $subArray){
		foreach($subArray as $id => $value){
			if(in_array('R', $value)){
		$sum5 += $value['returnid'];
			} 
		}
	}

	$sum6 = 0;
	foreach($Returns as $k => $subArray){
		foreach($subArray as $id => $value){
			if(in_array('D', $value)){
		$sum6 += $value['returnid'];
			} 
		}
	}

	$totalSums = array();
	$totalSums[] = $sum1;
	$totalSums[] = $sum5;
	$totalSums[] = $sum6;
	$totalSums[] = $sum2;
	$totalSums[] = $sum3;
	$totalSums[] = $sum4;


	for($i = $months-1; $i >= 0; $i--) {
		$start = date('Y-m', mktime(0, 0, 0, date('m') - $i, 1, date('Y')));
		$points = array();
		$points[] = isset($enquiries[$start]) ? $enquiries[$start] : 0;
		$points[] = isset($Returns[$start]['R']) ? $Returns[$start]['R']['returnid'] : 0;
		$points[] = isset($Returns[$start]['D']) ? $Returns[$start]['D']['returnid'] : 0;
		$points[] = isset($Orders[$start]['N']) ? $Orders[$start]['N']['Orders'] : 0;
		$points[] = isset($Orders[$start]['B']) ? $Orders[$start]['B']['Orders'] : 0;
		$points[] = isset($Orders[$start]['R']) ? $Orders[$start]['R']['Orders'] : 0;
		$chart->addPoint(new Point($start, $points));
	}

	$chart->SetTitle($chartTitle);
	$chart->SetLabelY('Frequency');
	$chart->ReduceLabels = false;
	$chart->ShowText = false;
	$chart->render($chartReference);
	?>

	<br />
	<h3>Last <?php echo $months; ?> Month statistics</h3>
	<p>Statistics for the last <?php print $months; ?> months.</p>

	<div style="text-align: center; border: 1px solid #eee; margin-top: 20px; margin-bottom: 20px;">
		<img src="<?php echo $chartReference; ?>" width="<?php print $chartWidth; ?>" height="<?php print $chartHeight; ?>" alt="<?php print $chartTitle; ?>" />
	</div>

	<br />
	<div>
		<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Types</strong></td>
			<td style="border-bottom:1px solid #aaaaaa;"><strong>Total</strong></td>
		</tr>
			
			<?php foreach($legend as $key => $led){ ?>
			<tr>
				<td><?php echo $led; ?></td>
				<td><?php echo $totalSums[$key]; ?></td>
			</tr>
			<?php } ?>
	</div>
	</table>
	<script language="javascript">
		window.onload = function() {
			var httpRequest = new HttpRequest();
			httpRequest.post('lib/util/removeChart.php', 'chart=<?php print $chartReference; ?>');
		}
	</script>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>