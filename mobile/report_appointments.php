<?php
ini_set('max_execution_time', '1800');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/User.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");

$secure = isset($_SESSION['Mobile']['Secure']) ? $_SESSION['Mobile']['Secure'] : false;

if($secure) {
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

	<h1>Appointment Report</h1>
	<p></p>

	<h2>Forthcoming Appointments</h2>
	<p></p>

	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr style="background-color:#eeeeee;">
			<td style="border-bottom:1px solid #dddddd;"><strong>Appointment</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Contact</strong></td>
			<td style="border-bottom:1px solid #dddddd;"><strong>Message</strong></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT ca.Message, ca.AppointmentOn, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Name FROM contact_appointment AS ca INNER JOIN contact AS c ON c.Contact_ID=ca.ContactID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE DATE(ca.AppointmentOn)>=DATE(NOW()) AND DATE(ADDDATE(ca.AppointmentOn, INTERVAL -3 DAY))<=DATE(NOW()) ORDER BY ca.AppointmentOn ASC"));
		if($data->TotalRows > 0) {
			while($data->Row) {
                ?>

				<tr>
					<td style="border-top:1px solid #dddddd;"><?php echo $data->Row['AppointmentOn']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $data->Row['Name']; ?></td>
					<td style="border-top:1px solid #dddddd;"><?php echo $data->Row['Message']; ?></td>
				</tr>

				<?php
				$data->Next();
			}
		} else {
            ?>

			<tr>
				<td style="border-top:1px solid #dddddd;" align="center" colspan="3">There are no items available for viewing.</td>
			</tr>

			<?php
		}
		$data->Disconnect();
		?>

	</table>

	</body>
	</html>

	<?php
} else {
	header("HTTP/1.0 404 Not Found");
}

$GLOBALS['DBCONNECTION']->Close();