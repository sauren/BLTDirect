<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CampaignEvent.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

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
	$event = new CampaignEvent();

	if(isset($_REQUEST['eventid']) && $event->Get($_REQUEST['eventid'])) {
		?>
		<html>
			<head>
				<title>Campaign Event Print Preview</title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			</head>

			<frameset border="0" rows="60,*" framespacing="2" frameborder="yes" id="frameSet" bordercolor="#5C6B80">
				<frame src="?action=openheader&eventid=<?php print $_REQUEST['eventid']; ?>" name="frameHeader" frameborder="no" scrolling="auto" border="0"></frame>
				<frame src="?action=openbody&eventid=<?php print $_REQUEST['eventid']; ?>" name="frameBody" frameborder="no" scrolling="auto" border="0"></frame>
			</frameset>

		</html>
		<?php
	} else {
		echo '<script language="javascript" type="text/javascript">window.self.close();</script>';
	}
}

function openHeader() {
	$event = new CampaignEvent();
	
	if($event->Get($_REQUEST['eventid'])) {
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
					<td width="50%" align="left"><span class="pageTitle">Campaign Event Print Preview</span></td>
					<td width="50%" align="right"><a href="javascript:popUrl('?action=openbody&print=true&eventid=<?php echo $_REQUEST['eventid']; ?>' , 800, 600);"><img src="../images/icons/printer.png" border="0" alt="Print" /></a></td>
				</tr>
			</table>

		</body>
		</html>
		<?php
	}

	require_once('lib/common/app_footer.php');
}

function openBody() {
	$event = new CampaignEvent();

	if($event->Get($_REQUEST['eventid'])) {
		$user = new User($GLOBALS['SESSION_USER_ID']);
		
		$ownerFound = false;

		$owner = new User();
		$owner->ID = $event->OwnedBy;

		if($owner->Get()) {
			$ownerFound = true;
		}

		$window = '<p>&nbsp;</p>';
		$window .= '<table width="100%">';
		$window .= '<tr>';
		$window .= '<td width="3%">&nbsp;</td>';
		$window .= sprintf('<td width="97%%">%s<br />%s</td>', sprintf('%s %s %s', $user->Person->Title, $user->Person->Name, $user->Person->LastName), $user->Person->Address->GetLongString());
		$window .= '</tr>';
		$window .= '</table>';

		$windowAddress = '<p>&nbsp;</p>';
		$windowAddress .= '<table width="100%">';
		$windowAddress .= '<tr>';
		$windowAddress .= '<td width="3%">&nbsp;</td>';
		$windowAddress .= sprintf('<td width="97%%">%s</td>', $user->Person->Address->GetLongString());
		$windowAddress .= '</tr>';
		$windowAddress .= '</table>';

		$windowDate = '<table width="100%">';
		$windowDate .= '<tr>';
		$windowDate .= '<td width="3%">&nbsp;</td>';
		$windowDate .= sprintf('<td width="97%%">%s</td>', date('jS F Y'));
		$windowDate .= '</tr>';
		$windowDate .= '</table>';
		$windowDate .= '<br />';
		$windowDate .= '<table width="100%">';
		$windowDate .= '<tr>';
		$windowDate .= '<td width="3%">&nbsp;</td>';
		$windowDate .= sprintf('<td width="97%%">%s<br />%s</td>', sprintf("%s %s %s", $user->Person->Title, $user->Person->Name, $user->Person->LastName), $user->Person->Address->GetLongString());
		$windowDate .= '</tr>';
		$windowDate .= '</table>';

		$findReplace = new FindReplace();
		$findReplace->Add('/\[WINDOW\]/', $window);
		$findReplace->Add('/\[WINDOWADDRESS\]/', $windowAddress);
		$findReplace->Add('/\[WINDOWDATE\]/', $windowDate);
		$findReplace->Add('/\[COMPANY\]/', trim(sprintf('%s %s %s', $user->Person->Title, $user->Person->Name, $user->Person->LastName)));
		$findReplace->Add('/\[CUSTOMER\]/', sprintf("%s %s %s", $user->Person->Title, $user->Person->Name, $user->Person->LastName));
		$findReplace->Add('/\[TITLE\]/', $user->Person->Title);
		$findReplace->Add('/\[FIRSTNAME\]/', $user->Person->Name);
		$findReplace->Add('/\[LASTNAME\]/', $user->Person->LastName);
		$findReplace->Add('/\[FULLNAME\]/', trim(str_replace("   ", " ", str_replace("  ", " ", sprintf("%s %s %s", $user->Person->Title, $user->Person->Name, $user->Person->LastName)))));
		$findReplace->Add('/\[FAX\]/', $user->Person->Fax);
		$findReplace->Add('/\[PHONE\]/', $user->Person->Phone1);
		$findReplace->Add('/\[ADDRESS\]/', $user->Person->Address->GetLongString());
		$findReplace->Add('/\[USERNAME\]/', sprintf("%s %s", $user->Person->Name, $user->Person->LastName));
		$findReplace->Add('/\[USEREMAIL\]/', $user->Person->Email);
		$findReplace->Add('/\[USERPHONE\]/', sprintf('%s', (strlen(trim($user->Person->Phone1)) > 0) ? $user->Person->Phone1 : $user->Person->Phone2));
		$findReplace->Add('/\[EMAIL\]/', $user->Person->Email);
		$findReplace->Add('/\[EMAILENCRYPTED\]/', '');
		$findReplace->Add('/\[PASSWORD\]/', 'Preview event');
		$findReplace->Add('/\[SALES\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $user->Person->Name, $user->Person->LastName), $user->Person->Phone1, $user->Username));

		if($ownerFound) {
			$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', sprintf("%s %s", $owner->Person->Name, $owner->Person->LastName), $owner->Person->Phone1, $owner->Username));
		} else {
			$findReplace->Add('/\[CREATOR\]/', sprintf('Sales: %s<br />Direct Dial: %s<br />E-mail Address: %s', Setting::GetValue('default_username'), Setting::GetValue('default_userphone'), Setting::GetValue('default_useremail')));
		}

		
		echo $findReplace->Execute($event->Template);
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