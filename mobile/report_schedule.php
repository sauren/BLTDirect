<?php
require_once('lib/common/app_header.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once ($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
	$start = date('Y-m-01 00:00:00');
	$end = date('Y-m-d H:i:s');

	$accountManagers = array();
	$scheduleTypes = array();

	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Account_Manager FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID INNER JOIN contact AS c ON c.Account_Manager_ID=u.User_ID ORDER BY Account_Manager ASC"));
	while($data->Row) {
		$accountManagers[$data->Row['User_ID']] = $data->Row['Account_Manager'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT cst.Contact_Schedule_Type_ID, cst.Name FROM contact_schedule_type AS cst ORDER BY Name ASC"));
	while($data->Row) {
		$scheduleTypes[$data->Row['Contact_Schedule_Type_ID']] = $data->Row['Name'];

		$data->Next();
	}
	$data->Disconnect();
	?>

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

	<h1>Schedule Report</h1>

	<?php
	foreach($accountManagers as $accountManagerId=>$accountManagerName) {
		$schedules = array();
		$totals = array();

		new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_schedule SELECT DATE(cs.Completed_On) AS Completed_On, cs.Contact_Schedule_Type_ID FROM contact_schedule AS cs WHERE cs.Owned_By=%d AND cs.Is_Complete='Y' AND cs.Completed_On BETWEEN '%s' AND '%s'", mysql_real_escape_string($accountManagerId), $start, $end));

		$data = new DataQuery(sprintf("SELECT ts.Completed_On, COUNT(ts.Completed_On) AS Count, ts.Contact_Schedule_Type_ID FROM temp_schedule AS ts GROUP BY ts.Completed_On, ts.Contact_Schedule_Type_ID ORDER BY ts.Completed_On ASC"));
		while($data->Row) {
			if(!isset($schedules[$data->Row['Completed_On']])) {
				$schedules[$data->Row['Completed_On']] = array();
			}

			if(!isset($schedules[$data->Row['Completed_On']][$data->Row['Contact_Schedule_Type_ID']])) {
				$schedules[$data->Row['Completed_On']][$data->Row['Contact_Schedule_Type_ID']] = 0;
			}

			$schedules[$data->Row['Completed_On']][$data->Row['Contact_Schedule_Type_ID']] += $data->Row['Count'];

			if(!isset($totals[$data->Row['Contact_Schedule_Type_ID']])) {
				$totals[$data->Row['Contact_Schedule_Type_ID']] = 0;
			}

			$totals[$data->Row['Contact_Schedule_Type_ID']] += $data->Row['Count'];

			$data->Next();
		}
		$data->Disconnect();

		new DataQuery(sprintf("DROP TABLE temp_schedule"));

		if(count($schedules) > 0) {
			?>

			<h2><?php echo $accountManagerName; ?></h2>
			<p></p>

			<table width="100%" border="0" cellpadding="3" cellspacing="0">
				<tr style="background-color:#eeeeee;">
					<td style="border-bottom:1px solid #dddddd;"><strong>Completed</strong></td>

					<?php
					foreach($scheduleTypes as $scheduleTypeId=>$scheduleType) {
						echo sprintf('<td style="border-bottom:1px solid #dddddd;" align="center"><strong>%s</strong></td>', $scheduleType);
					}
					?>

				</tr>

				<?php
				foreach($schedules as $completedDate=>$scheduleItem) {
					?>

					<tr>
						<td style="border-top:1px solid #dddddd;"><?php echo $completedDate; ?></td>

						<?php
						foreach($scheduleTypes as $scheduleTypeId=>$scheduleType) {
							echo sprintf('<td style="border-top:1px solid #dddddd;" align="center">%s</td>', isset($scheduleItem[$scheduleTypeId]) ? $scheduleItem[$scheduleTypeId] : '');
						}
						?>

					</tr>

					<?php
				}
				?>

				<tr style="background-color:#eeeeee;">
					<td style="border-top:1px solid #dddddd;">&nbsp;</td>

					<?php
					foreach($scheduleTypes as $scheduleTypeId=>$scheduleType) {
						echo sprintf('<td style="border-top:1px solid #dddddd;" align="center"><strong>%s</strong></td>', isset($totals[$scheduleTypeId]) ? $totals[$scheduleTypeId] : '');
					}
					?>

				</tr>
			</table><br />

			<?php
		}
	}
	?>

	</body>
	</html>

	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();