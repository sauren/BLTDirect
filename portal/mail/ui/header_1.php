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
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link href="../css/default.css" rel="stylesheet" type="text/css" media="screen" />
	<base target="_top" />
</head>
<body>
	<div id="Wrapper">
		<div id="Header">
			<img src="../images/template/logo_blt_1.jpg" width="185" height="70" class="logo" alt="BLT Direct" />
			<div id="NavBar">Mail Portal</div>
			<div id="CapTop">
				<div class="curveLeft"></div>
			</div>
			<ul>
				<li class="account"><a href="../user_security.php" title="Security Settings" target="i_content">Security Settings</a></li>

				<?php
				if($session->IsLoggedIn){
					echo sprintf('<li class="login"><a href="../login.php?action=logout" title="Login/Logout" target="i_content">Login/Logout</a></li>');
				} else {
					echo '<li class="login"><a href="../login.php" title="Login/Logout" target="i_content">Login/Logout</a></li>';
				}
				?>
			</ul>
		</div>
	</div>
</body>
</html>
<?php
$GLOBALS['DBCONNECTION']->Close();