<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserHoliday.php');

if($action == 'openheader'){
	$session->Secure(2);
	openHeader();
	exit;
} elseif($action == 'openbody'){
	$session->Secure(2);
	openBody();
	exit;
} else {
	$session->Secure(2);
	open();
	exit;
}

function open() {
	$holiday = new UserHoliday();

	if(isset($_REQUEST['id']) && $holiday->Get($_REQUEST['id'])) {
		?>
		<html>
			<head>
				<title>User Holiday Print Preview</title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			</head>

			<frameset border="0" rows="60,*" framespacing="2" frameborder="yes" id="frameSet" bordercolor="#5C6B80">
				<frame src="?action=openheader&id=<?php print $_REQUEST['id']; ?>" name="frameHeader" frameborder="no" scrolling="auto" border="0"></frame>
				<frame src="?action=openbody&id=<?php print $_REQUEST['id']; ?>" name="frameBody" frameborder="no" scrolling="auto" border="0"></frame>
			</frameset>

		</html>
		<?php
	} else {
		echo '<script language="javascript" type="text/javascript">window.self.close();</script>';
	}
}

function openHeader() {
	$holiday = new UserHoliday();
	
	if($holiday->Get($_REQUEST['id'])) {
		?>
		<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<link href="css/i_import.css" rel="stylesheet" type="text/css">
			<script language="javascript" type="text/javascript" src="js/generic_1.js"></script>
		</head>
		<body style="padding-bottom: 0px;">
		
			<table width="100%">
				<tr>
					<td width="50%" align="left"><span class="pageTitle">User Holiday Print Preview</span></td>
					<td width="50%" align="right"><a href="javascript:popUrl('<?php echo $_SERVER['PHP_SELF']; ?>?action=openbody&print=true&id=<?php echo $_REQUEST['id']; ?>' , 800, 600);"><img src="images/icon_print_1.gif" border="0" width="16" height="16" alt="Print" /></a></td>
				</tr>
			</table>

		</body>
		</html>
		<?php
	}

	require_once('lib/common/app_footer.php');
}

function openBody() {
	$holiday = new UserHoliday();

	if($holiday->Get($_REQUEST['id'])) {
		$holiday->User->Get();
		
		$findReplace = new FindReplace();
		$findReplace->Add('/\[HOLIDAY_USER\]/', trim(sprintf('%s %s', $holiday->User->Person->Name, $holiday->User->Person->LastName)));
		$findReplace->Add('/\[HOLIDAY_START_DATE\]/', cDatetime($holiday->StartDate, 'shortdate') . ' ' . $holiday->StartMeridiem);
		$findReplace->Add('/\[HOLIDAY_END_DATE\]/', cDatetime($holiday->EndDate, 'shortdate') . ' ' . $holiday->EndMeridiem);
		$findReplace->Add('/\[HOLIDAY_NOTES\]/', $holiday->Notes);
		$findReplace->Add('/\[HOLIDAY_APPROVED_DATE\]/', cDatetime($holiday->ApprovedOn, 'shortdate'));
		
		echo $findReplace->Execute(Template::GetContent('print_user_holiday_authorisation'));
	}
	
	if(isset($_REQUEST['print']) && ($_REQUEST['print'] == 'true')) {
		echo sprintf('<script language="javascript" type="text/javascript">
			window.onload = function() {
				window.print();
				window.close();
			}
			</script>');
	}

	require_once('lib/common/app_footer.php');
}