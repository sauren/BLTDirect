<?php
require_once('lib/common/app_header.php');

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
	?>

	<html>
	<head>
		<title>Order Collection Print Preview</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>

	<frameset border="0" rows="60,*" framespacing="2" frameborder="yes" id="frameSet" bordercolor="#5C6B80">
		<frame src="?action=openheader&orderid=<?php echo $_REQUEST['orderid']; ?>" name="frameHeader" frameborder="no" scrolling="auto" border="0"></frame>
		<frame src="?action=openbody&orderid=<?php echo $_REQUEST['orderid']; ?>" name="frameBody" frameborder="no" scrolling="auto" border="0"></frame>
	</frameset>

	</html>

	<?php
	require_once('lib/common/app_footer.php');
}

function openHeader() {
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
				<td width="50%" align="left"><span class="pageTitle">Order Collection Print Preview</span></td>
				<td width="50%" align="right"><a href="javascript:popUrl('?action=openbody&orderid=<?php echo $_REQUEST['orderid']; ?>&print=true' , 800, 600);"><img src="images/icon_print_1.gif" border="0" width="16" height="16" alt="Print" /></a></td>
			</tr>
		</table>

	</body>
	</html>

	<?php
	require_once('lib/common/app_footer.php');
}

function openBody() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

	$order = new Order($_REQUEST['orderid']);

	echo $order->GetPrintDocument();

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