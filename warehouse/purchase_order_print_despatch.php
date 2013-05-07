<?php
require_once('lib/appHeader.php');

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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseDespatch.php');

	$despatch = new PurchaseDespatch();

	if(isset($_REQUEST['id']) && $despatch->Get($_REQUEST['id'])) {
		?>

		<html>
		<head>
			<title>[#<?php echo $despatch->ID; ?>] Purchase Order Despatch Print Preview</title>
			<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		</head>

		<frameset border="0" rows="60,*" framespacing="2" frameborder="yes" id="frameSet" bordercolor="#5C6B80">
			<frame src="<?php echo $_SERVER['PHP_SELF']; ?>?action=openheader&id=<?php echo $despatch->ID; ?>" name="frameHeader" frameborder="no" scrolling="auto" border="0"></frame>
			<frame src="<?php echo $_SERVER['PHP_SELF']; ?>?action=openbody&id=<?php echo $despatch->ID; ?>" name="frameBody" frameborder="no" scrolling="auto" border="0"></frame>
		</frameset>

		</html>

		<?php
	} else {
		echo sprintf('<script language="javascript" type="text/javascript">window.self.close();</script>');
	}
}

function openHeader() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseDespatch.php');

	$despatch = new PurchaseDespatch($_REQUEST['id']);
	?>

	<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<link href="css/i_import.css" rel="stylesheet" type="text/css">
		<script language="javascript" type="text/javascript" src="js/generic_1.js"></script>
	</head>
	<body style="padding-bottom: 0px;">

		<table width="100%">
			<tr>
				<td width="50%" align="left"><span class="pageTitle">[#<?php echo $despatch->ID; ?>] Purchase Order Print Preview</span></td>
				<td width="50%" align="right"><a href="javascript:popUrl('<?php echo $_SERVER['PHP_SELF']; ?>?action=openbody&print=true&id=<?php echo $despatch->ID; ?>' , 800, 600);"><img src="images/icon_print_1.gif" border="0" width="16" height="16" alt="Print" /></a></td>
			</tr>
		</table>

	</body>
	</html>

	<?php
}

function openBody() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseDespatch.php');

	$despatch = new PurchaseDespatch($_REQUEST['id']);

	echo $despatch->PrintDespatch();

	if(isset($_REQUEST['print']) && ($_REQUEST['print'] == 'true')) {
		echo sprintf('<script language="javascript" type="text/javascript">
			window.onload = function() {
				window.print();
				window.close();
			}
			</script>');
	}
}