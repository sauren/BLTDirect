<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Reserve.php');

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
		<title>Reserve Print Preview</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>

	<frameset border="0" rows="60,*" framespacing="2" frameborder="yes" id="frameSet" bordercolor="#5C6B80">
		<frame src="?action=openheader&id=<?php echo $_REQUEST['id']; ?>" name="frameHeader" frameborder="no" scrolling="auto" border="0"></frame>
		<frame src="?action=openbody&id=<?php echo $_REQUEST['id']; ?>" name="frameBody" frameborder="no" scrolling="auto" border="0"></frame>
	</frameset>

	</html>

	<?php
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
				<td width="50%" align="left"><span class="pageTitle">Reserve Print Preview</span></td>
				<td width="50%" align="right"><a href="javascript:popUrl('?action=openbody&id=<?php echo $_REQUEST['id']; ?>&print=true' , 800, 600);"><img src="images/icon_print_1.gif" border="0" width="16" height="16" alt="Print" /></a></td>
			</tr>
		</table>

	</body>
	</html>

	<?php
}

function openBody() {
	$reserve = new Reserve($_REQUEST['id']);

	echo $reserve->getPrintDocument();

	if(isset($_REQUEST['print']) && ($_REQUEST['print'] == 'true')) {
		echo sprintf('<script language="javascript" type="text/javascript">
			window.onload = function() {
				window.print();
				window.close();
			}
			</script>');
	}
}