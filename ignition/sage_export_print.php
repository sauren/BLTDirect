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
	if(isset($_REQUEST['documents'])) {
		?>

		<html>
		<head>
			<title>Sage Export Print Preview</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		</head>

		<frameset border="0" rows="60,*" framespacing="2" frameborder="yes" id="frameSet" bordercolor="#5C6B80">
			<frame src="<?php echo $_SERVER['PHP_SELF']; ?>?action=openheader&documents=<?php echo $_REQUEST['documents']; ?>" name="frameHeader" frameborder="no" scrolling="auto" border="0"></frame>
			<frame src="<?php echo $_SERVER['PHP_SELF']; ?>?action=openbody&documents=<?php echo $_REQUEST['documents']; ?>" name="frameBody" frameborder="no" scrolling="auto" border="0"></frame>
		</frameset>

		</html>

		<?php
	} else {
		echo sprintf('<script language="javascript" type="text/javascript">window.self.close();</script>');
	}

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
				<td width="50%" align="left"><span class="pageTitle">Sage Export Print Preview</span></td>
				<td width="50%" align="right"><a href="javascript:popUrl('<?php echo $_SERVER['PHP_SELF']; ?>?action=openbody&print=true&documents=<?php echo $_REQUEST['documents']; ?>' , 800, 600);"><img src="images/icon_print_1.gif" border="0" width="16" height="16" alt="Print" /></a></td>
			</tr>
		</table>

	</body>
	</html>

	<?php
	require_once('lib/common/app_footer.php');
}

function openBody() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Invoice.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNote.php');

	$html = '';
	
	$documents = $_SESSION['SageExport'][$_REQUEST['documents']];

	foreach($documents as $key=>$id) {
		if(preg_match('/orderinvoice-([0-9]+)/', $key)) {
			$invoice = new Invoice($id);

            if(!empty($html)) {
				$html .= '<br style="page-break-after:always" />';
			}

			$html .= $invoice->GetDocument();

		} elseif(preg_match('/ordercredit-([0-9]+)/', $key)) {
            $credit = new CreditNote($id);

            if(!empty($html)) {
				$html .= '<br style="page-break-after:always" />';
			}

			$html .= $credit->GetDocument();
		}
	}

	echo $html;

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