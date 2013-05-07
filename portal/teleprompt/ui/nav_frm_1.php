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
	<title>Teleprompt Portal</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<base target="_top" />
	<link href="../css/default.css" rel="stylesheet" type="text/css" media="screen" />
	<script language="javascript" type="text/javascript">
		function toggleCat(cat) {
			var e = document.getElementById(cat);
			if(e) {
				if(e.style.display == 'none') {
					e.style.display = 'block';
				} else {
					e.style.display = 'none';
				}
			}
		}
	</script>
</head>
<body>
	<div id="Wrapper">
		<div id="LeftNav">
			<p class="title"><strong>Navigation</strong></p>

			<ul class="rootCat">
				<li><a href="../welcome.php" target="i_content">Home</a></li>
				<li><a href="../order_create.php" target="i_content">Create New Order</a></li>
			</ul>
			<div class="cap"></div>
			<div class="shadow"></div>
		</div>
	</div>
</body>
</html>