<?php
require_once('../../../ignition/lib/classes/ApplicationHeader.php');
require_once('../lib/common/config.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UserSession.php');

$session = new UserSession($GLOBALS['PORTAL_NAME'], $GLOBALS['PORTAL_URL']);
$session->Start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Portal Window</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<frameset cols="*" id="i_display" border="0" framespacing="0">
	<frameset rows="*" id="i_content_win">
		<frame src="../welcome.php" name="i_content_display" frameborder="no" marginwidth="0" marginheight="0"></frame>
	</frameset>
</frameset>

<noframes></noframes>

</html>