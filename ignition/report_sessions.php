<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

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

	$page = new Page('Sessions Report', 'Please choose a start and end date for your report');
	
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('start', 'Report Start Date', 'text', '', 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('end', 'Report End Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('range', 'Date range', 'select', 'none', 'alpha_numeric', 0, 32);
	$form->AddOption('range', 'none', '-- None --');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'thisminute', 'This Minute');
	$form->AddOption('range', 'thishour', 'This Hour');
	$form->AddOption('range', 'thisday', 'This Day');
	$form->AddOption('range', 'thismonth', 'This Month');
	$form->AddOption('range', 'thisyear', 'This Year');
	$form->AddOption('range', 'thisfinancialyear', 'This Financial Year');
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
	$form->AddOption('range', 'lastweek', 'Last Week (Last 7 Days)');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastmonth', 'Last Month');
	$form->AddOption('range', 'last3months', 'Last 3 Months');
	$form->AddOption('range', 'last6months', 'Last 6 Months');
	$form->AddOption('range', 'last12months', 'Last 12 Months');
	$form->AddOption('range', 'x', '');
	$form->AddOption('range', 'lastyear', 'Last Year');
	$form->AddOption('range', 'last2years', 'Last 2 Years');
	$form->AddOption('range', 'last3years', 'Last 3 Years');
	$form->AddField('user', 'User', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('user', '', '');
	
	$data = new DataQuery(sprintf("SELECT u.User_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Person_Name FROM users AS u INNER JOIN person AS p ON p.Person_ID=u.Person_ID ORDER BY Person_Name ASC"));
	while($data->Row) {
		$form->AddOption('user', $data->Row['User_ID'], $data->Row['Person_Name']);
		
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(($form->GetValue('range') != 'none') && (strlen($form->GetValue('range')) > 1)) {
			switch($form->GetValue('range')) {
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
				case 'thisfinancialyear':
					$boundary = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y"))); 
					
					if(time() < strtotime($boundary)) {
						$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y")-1)); 
						$end = $boundary;
					} else {
						$start = $boundary;
						$end = date('Y-m-d 00:00:00', mktime(0, 0, 0, 5, 1, date("Y")+1));
					}
					
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

				case 'lastweek': 	$start = date('Y-m-d 00:00:00', mktime(0, 0, 0, date("m"), date("d")-7, date("Y")));
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
				case 'last12months': $start = date('Y-m-01 00:00:00', mktime(0, 0, 0, date("m")-12, 1,  date("Y")));
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

			redirect(sprintf("Location: %s?action=report&start=%s&end=%s&userid=%d", $_SERVER['PHP_SELF'], $start, $end, $form->GetValue('user')));
		} else {
			
			if($form->Validate()){
				redirect(sprintf("Location: %s?action=report&start=%s&end=%s&userid=%d", $_SERVER['PHP_SELF'], sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)), (strlen($form->GetValue('end')) > 0) ? sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('end'), 6, 4), substr($form->GetValue('end'), 3, 2), substr($form->GetValue('end'), 0, 2)) : date('Y-m-d H:i:s', mktime(0, 0, 0, date('m', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))), date('d', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))) + 1, date('Y', strtotime(sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('start'), 6, 4), substr($form->GetValue('start'), 3, 2), substr($form->GetValue('start'), 0, 2)))))), $form->GetValue('user')));
			}
		}
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');
	
	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	$window = new StandardWindow("Report on Order Breakdown.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
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
	
	echo $window->AddHeader('Select the user to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('user'), $form->GetHTML('user'));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
	
	if(!isset($_SESSION['preferences']['report_sessions']['mode'])) {
		$_SESSION['preferences']['report_sessions']['mode'] = 'N';
	}

	if(isset($_REQUEST['mode'])) {
		$_SESSION['preferences']['report_sessions']['mode']	= $_REQUEST['mode'];
	}
	
	$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : '0000-00-00 00:00:00';
	$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : '0000-00-00 00:00:00';
	$userId = isset($_REQUEST['userid']) ? $_REQUEST['userid'] : 0;
	$session = isset($_REQUEST['session']) ? $_REQUEST['session'] : '';
	
	if(($start == '0000-00-00 00:00:00') || ($end == '0000-00-00 00:00:00') || ($userId == 0)) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$user = new User($userId);

	$page = new Page('Sessions Report: ' . cDatetime($start, 'longdate') . ' to ' . cDatetime($end, 'longdate'), '');
	$page->Display('header');
	
	$sessions = array();
	
	$data = new DataQuery(sprintf("SELECT Session_ID, Created_On FROM sessions WHERE Created_On>='%s' AND Created_On<'%s' AND User_ID=%d ORDER BY Created_On DESC", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($userId)));
	while($data->Row) {
		$sessions[] = $data->Row;
		
		$data->Next();
	}
	$data->Disconnect();
	?>

	<br />
	<h3><?php echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName)); ?>'s Sessions</h3>
	<p>Session statistics for the above user.</p>
	
	<table width="100%" border="0">
		<tr>
			<td valign="top" width="1%">
				<div style="padding: 10px; background-color: #eee; white-space: nowrap;">
					<?php
					for($i=0; $i<count($sessions); $i++) {
						echo sprintf('<a href="%s?action=report&userid=%d&start=%s&end=%s&session=%s">%s</a> <span style="color: #999;">%s</span><br />', $_SERVER['PHP_SELF'], $user->ID, $start, $end, $sessions[$i]['Session_ID'], cDatetime($sessions[$i]['Created_On'], 'shortdatetime'), date('D', strtotime($sessions[$i]['Created_On'])));
					}
					?>
				</div>
			</td>
			<td valign="top" style="padding: 10px;">
			
				<?php
				if(!empty($session)) {
					$store = array('Products' => array(), 'Categories' => array());
					$items = array();
					
					$data = new DataQuery(sprintf("SELECT Session_Item_ID, Created_On, Page_Request FROM session_item WHERE Session_ID LIKE '%s' ORDER BY Created_On ASC", mysql_real_escape_string($session)));
					while($data->Row) {
						if($_SESSION['preferences']['report_sessions']['mode'] == 'N') {
							$items[] = $data->Row;
						} else {
							if(preg_match('/\/product_profile.php/', $data->Row['Page_Request'])) {
								if(preg_match('/[?&]pid=([0-9]+)&{0,1}/', $data->Row['Page_Request'], $matches)) {
									if(!isset($store['Products'][$matches[1]])) {
										$store['Products'][$matches[1]] = new Product($matches[1]);
									}
									
									$item = $data->Row;
									$item['Page_Title'] = $store['Products'][$matches[1]]->Name;
									
									$items[] = $item;
								}
							} elseif(preg_match('/\/product_categories.php/', $data->Row['Page_Request'])) {
								if(preg_match('/[?&]node=([0-9]+)&{0,1}/', $data->Row['Page_Request'], $matches)) {
									if(!isset($store['Categories'][$matches[1]])) {
										$store['Categories'][$matches[1]] = new Category($matches[1]);
									}
									
									$item = $data->Row;
									$item['Page_Title'] = $store['Categories'][$matches[1]]->Name;
								
									$items[] = $item;
								}
							}
						}
						
						$data->Next();
					}
					$data->Disconnect();
					
					$timeConnected = strtotime($items[count($items) - 1]['Created_On']) - strtotime($items[0]['Created_On']); 
					$avgPageTime = number_format($timeConnected / (count($items) - 1), 1, '.', '');
					?>
					
					<h3 style="font-size: 16px; font-weight: bold;">Report Mode</h3>
					<br />
					
					<input type="checkbox" name="mode" <?php echo (isset($_SESSION['preferences']['report_sessions']['mode']) && ($_SESSION['preferences']['report_sessions']['mode'] == 'Y')) ? 'checked="checked"' : ''; ?> onclick="window.self.location.href = '?action=report&userid=<?php echo $_REQUEST['userid']; ?>&start=<?php echo $_REQUEST['start']; ?>&end=<?php echo $_REQUEST['end']; ?>&session=<?php echo $_REQUEST['session']; ?>&mode=' + (this.checked ? 'Y' : 'N');" /> Show category/product requests with titles only.
					<br /><br />
					
					<h3 style="font-size: 16px; font-weight: bold;">Session Information</h3>
					<br />
					
					<table width="100%" border="0">
						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td width="20%"><strong>Session ID</strong></td>
							<td><?php echo $session; ?></td>
						</tr>
						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td><strong>Page Requests</strong></td>
							<td><?php echo count($items); ?></td>
						</tr>
						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td><strong>Time Connected</strong></td>
							<td><?php echo number_format($timeConnected/60, 1, '.', ''); ?> minutes</td>
						</tr>
						<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
							<td><strong>Average Page Time</strong></td>
							<td><?php echo $avgPageTime; ?> seconds</td>
						</tr>
					</table>
					<br />
					
					<h3 style="font-size: 16px; font-weight: bold;">Page Requests</h3>
					<br />
					
					<table width="100%" border="0">
						<tr>
							<td style="border-bottom:1px solid #aaaaaa"><strong>#</strong></td>
							<td style="border-bottom:1px solid #aaaaaa"><strong>Page Request</strong></td>
							
							<?php
							if($_SESSION['preferences']['report_sessions']['mode'] == 'Y') {
								echo '<td style="border-bottom:1px solid #aaaaaa"><strong>Page Title</strong></td>';
							}
							?>
							
							<td style="border-bottom:1px solid #aaaaaa"><strong>Time Requested</strong></td>
						</tr>
						
						<?php
						for($i=0; $i<count($items); $i++) {
							?>
						
							<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
								<td><?php echo $i+1; ?></td>
								<td><?php echo $items[$i]['Page_Request']; ?></td>
								
								<?php
								if($_SESSION['preferences']['report_sessions']['mode'] == 'Y') {
									echo '<td>' . $items[$i]['Page_Title'] . '</td>';
								}
								?>
							
								<td nowrap="nowrap"><?php echo cDatetime($items[$i]['Created_On'], 'shortdatetime'); ?></td>
							</tr>
							
							<?php
						}
						?>
					
					</table>
					
					<?php
				} else {
					?>
				
					<h3 style="font-size: 16px; font-weight: bold;">Sessions</h3>
					<br />
				
					<p>Select a session to the left to view its contents.</p>
					
					<?php
				}
				?>
				
			</td>
		</tr>
	</table>
		
	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>