<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

if(isset($_REQUEST['report'])) {
	if($_REQUEST['report'] == 'today') {
		$_REQUEST['start'] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
		$_REQUEST['end'] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')));

	} elseif($_REQUEST['report'] == 'thismonth') {
		$_REQUEST['start'] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m'), 1, date('Y')));
		$_REQUEST['end'] = date('Y-m-d H:i:s', mktime(0, 0, 0, date('m') + 1, 1, date('Y')));

	} elseif($_REQUEST['report'] == 'thisyear') {
		$_REQUEST['start'] = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, date('Y')));
		$_REQUEST['end'] = date('Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, date('Y') + 1));
	}
}

$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : '0000-00-00 00:00:00';
$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : '0000-00-00 00:00:00';

$terms = array();
$totalFrequency = 0;

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'filter', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('report', 'Report', 'hidden', '', 'anything', 1, 64);
$form->AddField('start', 'Start', 'hidden', $start, 'anything', 19, 19);
$form->AddField('end', 'End', 'hidden', $end, 'anything', 19, 19);

$sqlFrom = "FROM customer_session ";
$sqlWhere = "WHERE Referrer_Search_Term<>'' ";

if(($start != '0000-00-00 00:00:00') && ($end != '0000-00-00 00:00:00')) {
	$sqlWhere .= sprintf(" AND Created_On>='%s' AND Created_On<'%s' ", mysql_real_escape_string($start), mysql_real_escape_string($end));
} elseif($start != '0000-00-00 00:00:00') {
	$sqlWhere .= sprintf(" AND Created_On>='%s' ", mysql_real_escape_string($start));
} elseif($end != '0000-00-00 00:00:00') {
	$sqlWhere .= sprintf(" AND Created_On<'%s' ", mysql_real_escape_string($end));
}

$data = new DataQuery(sprintf("SELECT COUNT(*) AS Count, Referrer_Search_Term %s %s GROUP BY Referrer_Search_Term ORDER BY Count DESC LIMIT 0, 100", $sqlFrom, $sqlWhere));
while($data->Row) {
	$terms[] = array(
		'Frequency' => $data->Row['Count'],
		'SearchTerm' => $data->Row['Referrer_Search_Term']);

	$totalFrequency += $data->Row['Count'];

	$data->Next();
}
$data->Disconnect();

for($i=0; $i<count($terms); $i++) {
	$terms[$i]['FrequencyPercent'] = ($totalFrequency > 0) ? number_format(($terms[$i]['Frequency'] / $totalFrequency) * 100, 1, '.', '') : 0;
}

$page = new Page('Referrer Term Overview', 'Statistics for referrer search terms.');
$page->Display('header');

$window = new StandardWindow("Statistic options");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('report');
echo $form->GetHTML('start');
echo $form->GetHTML('end');

echo $form->Close();

echo '<br />';
?>

<h3>Search Terms</h3>
<br />

<table width="100%" border="0">
	<tr>
		<td style="border-bottom:1px solid #aaaaaa" valign="top"><strong>Search Term</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" valign="top" align="right"><strong>Frequency</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" width="80%">&nbsp;</td>
	</tr>

	<?php
	for($i=0; $i<count($terms); $i++) {
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td nowrap="nowrap"><?php echo $terms[$i]['SearchTerm']; ?></td>
			<td nowrap="nowrap" align="right"><?php echo number_format($terms[$i]['Frequency'], 0, '.', ','); ?></td>
			<td>
				<?php
				if($terms[$i]['FrequencyPercent'] > 0) {
					echo sprintf('<div style="font-size: 0; height: 5px; width: %s%%; background-color: #000000;"></div>', $terms[$i]['FrequencyPercent']);
				} else {
					echo '<div style="font-size: 0; height: 5px;"></div>';
				}
				?>
			</td>
		</tr>

		<?php
	}
	?>

</table>
<br />

<?php
require_once('lib/common/app_footer.php');
?>