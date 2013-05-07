<?php
require_once('../lib/classes/ApplicationHeader.php');

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Session.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Language.php');

$session = new Session();

$user = new User();
$user->GetFromSession($session->ID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Ignition Toolbar</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link href="../css/i_header.css" rel="stylesheet" type="text/css">
	<script src="../js/generic_1.js" language="javascript"></script>
</head>

<body marginheight="0" marginwidth="0" topmargin="0" leftmargin="0" onLoad="counter();">

	<table width="100%"  border="0" cellspacing="0" cellpadding="0">
	  <tr>
	    <td width="40%" height="70" class="logoArea"><img src="../images/logo_ignition_1.jpg" width="114" height="70" hspace="15" vspace="0" border="0"></td>
	    <td width="60%" height="70" align="right" valign="bottom">
			<table height="29" border="0" cellpadding="0" cellspacing="0">
			  <tr>
				<td class="toolBtnOut" onMouseOver="setClassName(this, 'toolBtnOver');" onMouseOut="setClassName(this, 'toolBtnOut');" onClick="counter();"><a href="javascript:printDisplay();"><img src="../images/btn_print_1.gif" width="78" height="29" border="0"></a></td>
				<td class="toolBtnOut" onMouseOver="setClassName(this, 'toolBtnOver');" onMouseOut="setClassName(this, 'toolBtnOut');" nowrap><img src="../images/icon_user_1.gif" width="16" height="16" hspace="6" vspace="0" border="0" align="absmiddle"><span class="userString">You are logged in as <span class="usersName"><?php echo  sprintf('%s %s', $user->Person->Name, $user->Person->LastName); ?></span></span></td>
				<td class="toolBtnOut" onMouseOver="setClassName(this, 'toolBtnOver');" onMouseOut="setClassName(this, 'toolBtnOut');"><a href="javascript:systemLogout(false);"><img src="../images/btn_logout_1.gif" width="99" height="29" border="0"></a></td>
			  </tr>
			</table>
		</td>
	  </tr>
	</table>

</body>
</html>
<?php
$GLOBALS['DBCONNECTION']->Close();