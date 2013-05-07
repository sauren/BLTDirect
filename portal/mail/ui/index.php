<?php
require_once('../../../ignition/lib/classes/ApplicationHeader.php');
require_once('../lib/common/config.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserSession.php');

$session = new UserSession($GLOBALS['PORTAL_NAME'], $GLOBALS['PORTAL_URL']);
$session->Start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Mail Portal</title>
	<link rel="shortcut icon" href="../favicon.ico" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="Content-Language" content="en-GB" />
	<meta name="robots" content="noindex, nofollow" />
</head>

<frameset border="0" rows="143,*" framespacing="0" frameborder="no" id="i_frmSet_1">
	<frame src="header_1.php" name="i_header" frameborder="no" scrolling="no" border="0" noresize="noresize"></frame>

	<frameset cols="169,*" border="0" frameborder="no" framespacing="0" id="i_frmSet_2">
		<frame src="nav_frm_1.php" name="i_nav" frameborder="no" border="0" noresize="noresize"></frame>
		<frame src="content_frm_1.php" name="i_content" frameborder="no" border="0" noresize="noresize" scrolling="auto">
	</frameset>
</frameset>

<noframes>Sorry, The Mail Portal requires &quot;Frame&quot; support, which your web browser does not have.</noframes>

</html>
<?php
$GLOBALS['DBCONNECTION']->Close();