<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$session->Secure(2);

$userId = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;

$user = new User($userId);

$page = new Page(sprintf('<a href="users.php">Users</a> &gt; <a href="user_holiday.php?id=%d">Holidays</a> &gt; Holiday Calendar', $user->ID), sprintf('Yearly holiday calendar for <strong>%s</strong>.', trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName))));
$page->Display('header');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'User ID', 'hidden', $userId, 'numeric_unsigned', 1, 11);
$form->AddField('year', 'Year', 'select', date('Y'), 'numeric_unsigned', 1,11);

$years = array();

$data = new DataQuery(sprintf("SELECT DISTINCT(YEAR(Start_Date)) AS Year FROM user_holiday WHERE User_ID=%d ORDER BY Year ASC", mysql_real_escape_string($form->GetValue('id'))));
while($data->Row) {
	$years[$data->Row['Year']] = $data->Row['Year'];

	$data->Next();
}
$data->Disconnect();

if(!isset($years[date('Y')])) {
	$years[date('Y')] = date('Y');
}

foreach($years as $year) {
	$form->AddOption('year', $year, $year);
}


$window = new StandardWindow("Show calendar year.");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');

echo $window->Open();
echo $window->AddHeader('Select calendar year');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('year'), $form->GetHTML('year') . '<input type="submit" name="show" value="show" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

echo '<br />';

$year = (isset($_REQUEST['year']) && is_numeric($_REQUEST['year'])) ? $_REQUEST['year'] : date('Y');

$dateMatrix = array();
$lowIndex = -1;
$highIndex = -1;

for($m=1; $m<=12; $m++) {
	$dateMatrix[$m] = array();

	$startIndex = date('w', mktime(0, 0, 0, $m, 1, $year));
	$endIndex = date('t', mktime(0, 0, 0, $m, 1, $year)) + $startIndex;

	$lowIndex = (($lowIndex == -1) || ($startIndex < $lowIndex)) ? $startIndex : $lowIndex;
	$highIndex = (($highIndex == -1) || ($endIndex > $highIndex)) ? $endIndex : $highIndex;
}

for($m=1; $m<=12; $m++) {
	$startIndex = date('w', mktime(0, 0, 0, $m, 1, $year));
	$endIndex = date('t', mktime(0, 0, 0, $m, 1, $year)) + $startIndex;
	$dayIndex = 0;

	for($i=$startIndex; $i<$endIndex; $i++) {
		$dayIndex++;
		$dateMatrix[$m][$i] = array('Date' => $dayIndex, 'Time' => mktime(0, 0, 0, $m, $dayIndex, $year));
	}
}

$holidays = array();

$data = new DataQuery(sprintf("SELECT * FROM user_holiday WHERE User_ID=%d%s", mysql_real_escape_string($form->GetValue('id')), ($form->GetValue('year') > 0) ? sprintf(" AND YEAR(Start_Date)=%d", mysql_real_escape_string($form->GetValue('year'))) : ''));
while($data->Row) {
	$holidays[] = array('Public' => false, 'StartDate' => $data->Row['Start_Date'], 'EndDate' => $data->Row['End_Date'], 'StartTime' => strtotime($data->Row['Start_Date']), 'EndTime' => strtotime($data->Row['End_Date']));

	$data->Next();
}
$data->Disconnect();

if($user->IsCasualWorker == 'N') {
	$data = new DataQuery(sprintf("SELECT * FROM public_holiday WHERE TRUE%s", ($form->GetValue('year') > 0) ? sprintf(" AND YEAR(Holiday_Date)=%d", mysql_real_escape_string($form->GetValue('year'))) : ''));
	while($data->Row) {
		$holidays[] = array('Public' => true, 'StartDate' => $data->Row['Holiday_Date'], 'EndDate' => $data->Row['Holiday_Date'], 'StartTime' => strtotime($data->Row['Holiday_Date']), 'EndTime' => strtotime($data->Row['Holiday_Date']));

		$data->Next();
	}
	$data->Disconnect();
}
?>

<table width="100%" style="border-collapse: collapse;">
	<tr>
		<th align="left" style="border-bottom: 1px solid #fff; padding: 5px; background-color: #ccc;">Month</th>
		<th align="center" style="border-bottom: 1px solid #fff; padding: 5px; background-color: #ccc;" colspan="<?php echo $highIndex; ?>">Schedule</th>
	</tr>
	<tr>
		<td style="border-bottom: 1px solid #fff; padding: 5px; background-color: #eee;">&nbsp;</td>

		<?php
		$dayIndex = $lowIndex;

		for($i=$lowIndex; $i<$highIndex; $i++) {
			$day = '';

			switch($dayIndex) {
				case 0:
					$day = 'S';
					break;
				case 1:
					$day = 'M';
					break;
				case 2:
					$day = 'T';
					break;
				case 3:
					$day = 'W';
					break;
				case 4:
					$day = 'T';
					break;
				case 5:
					$day = 'F';
					break;
				case 6:
					$day = 'S';
					break;
			}
			?>

			<td align="center" style="border-bottom: 1px solid #fff; border-left: 1px solid #fff; padding: 5px; <?php echo (($dayIndex == 0) || ($dayIndex == 6)) ? 'background-color: #fcf;' : 'background-color: #eee;'; ?>"><?php echo $day; ?></td>

			<?php
			$dayIndex++;
			$dayIndex = ($dayIndex > 6) ? 0 : $dayIndex;
		}
		?>

	</tr>

	<?php
	for($m=1; $m<=12; $m++) {
		?>

		<tr>
			<td style="border-bottom: 1px solid #fff; padding: 5px; background-color: #eee"><?php echo date('F', mktime(0, 0, 0, $m, 1, $year)); ?></td>

			<?php
			$dayIndex = $lowIndex;

			for($i=$lowIndex; $i<$highIndex; $i++) {
				$date = isset($dateMatrix[$m][$i]) ? $dateMatrix[$m][$i]['Date'] : '';

				$backgroundColour = (($dayIndex == 0) || ($dayIndex == 6)) ? 'background-color: #fcf;' : 'background-color: #eee;';
				?>

				<td align="center" style="border-bottom: 1px solid #fff; border-left: 1px solid #fff; padding: 5px 0 5px 0; <?php echo $backgroundColour; ?>">
					<?php echo $date; ?><br />

					<?php
					if(isset($dateMatrix[$m][$i])) {
						$line = array('Left' => array('Show' => false, 'Colour' => '#444'), 'Middle' => array('Show' => false, 'Colour' => '#444'), 'Right' => array('Show' => false, 'Colour' => '#444'));

						foreach($holidays as $holidayItem) {
							if(($dateMatrix[$m][$i]['Time'] >= $holidayItem['StartTime']) && ($dateMatrix[$m][$i]['Time'] <= $holidayItem['EndTime'])) {
                            	$line['Middle']['Show'] = true;

								if($dateMatrix[$m][$i]['Time'] > $holidayItem['StartTime']) {
									$line['Left']['Show'] = true;
								}

								if($dateMatrix[$m][$i]['Time'] < $holidayItem['EndTime']) {
									$line['Right']['Show'] = true;
								}

								if($holidayItem['Public']) {
									$line['Middle']['Colour'] = '#0c0';
								}
							}
						}
						?>

						<table width="100%" style="margin: 5px 0 0 0; border-collapse: collapse;">
							<tr>
								<?php
								foreach($line as $lineItem) {
									?>

									<td width="<?php echo number_format(100 / count($line), 2, '.', '')?>%" style="padding: 0;">

										<?php
										if($lineItem['Show']) {
											?>

											<div style="background-color: <?php echo $lineItem['Colour']; ?>; height: 5px; font-size: 0;"></div>

											<?php
										}
										?>

									</td>

									<?php
								}
								?>

							</tr>
						</table>

						<?php
					}
					?>

				</td>

				<?php
				$dayIndex++;
				$dayIndex = ($dayIndex > 6) ? 0 : $dayIndex;
			}
			?>

		</tr>

		<?php
	}
	?>

</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');