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
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CreditNote.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FindReplace.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Invoice.php");

	$connections = getSyncConnections();
	
	$html = '';

	$documents = $_SESSION['SageExport'][$_REQUEST['documents']];

	foreach($documents as $key=>$items) {
		if(preg_match('/batchinvoice-([0-9]+)-([0-9]+)-([0-9.]+)/', $key, $matches) || preg_match('/batchinvoice_lbuk-([0-9]+)-([0-9.]+)/', $key, $matches)) {
            $lines = '<table width="100%" cellspacing="0" cellpadding="5" class="order">';
			$lines .= '<tr>';
			$lines .= sprintf('<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">Invoice for %s</th>', sprintf('%s/%s/%s', substr($matches[1], 6, 2), substr($matches[1], 4, 2), substr($matches[1], 0, 4)));
			$lines .= '<th align="right" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">Total</th>';
			$lines .= '</tr>';

			$total = 0;

			for($i=0; $i<count($items); $i++) {
				$connection = preg_match('/batchinvoice_lbuk-([0-9]+)-([0-9.]+)/', $key, $matches) ? $connections[1]['Connection'] : $connections[0]['Connection'];
				
				$invoice = new Invoice($items[$i], $connection);

                $lines .= '<tr>';
				$lines .= sprintf('<td align="left">%d</td>', $invoice->ID);
				$lines .= sprintf('<td align="right">&pound;%s</td>', number_format(round($invoice->Total, 2), 2, '.', ','));
				$lines .= '</tr>';

				$total += $invoice->Total;
			}

            $lines .= '<tr>';
			$lines .= '<td align="left"></td>';
			$lines .= sprintf('<td align="right"><strong>&pound;%s</strong></td>', number_format(round($total, 2), 2, '.', ','));
			$lines .= '</tr>';

            $findReplace = new FindReplace();
			$findReplace->Add('/\[INVOICE_LINES\]/', $lines);

			$templateFile = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/print/sage_invoices.tpl");
			$templateHtml = '';

			for($i=0; $i<count($templateFile); $i++) {
				$templateHtml .= $findReplace->Execute($templateFile[$i]);
			}

            if(!empty($html)) {
				$html .= '<br style="page-break-after:always" />';
			}

			$html .= $templateHtml;
		} elseif(preg_match('/batchcredit-([0-9]+)-([0-9]+)-([0-9.]+)/', $key, $matches) || preg_match('/batchcredit_lbuk-([0-9]+)-([0-9.]+)/', $key, $matches)) {
            $lines = '<table width="100%" cellspacing="0" cellpadding="5" class="order">';
			$lines .= '<tr>';
			$lines .= sprintf('<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">Credits for %s</th>', sprintf('%s/%s/%s', substr($matches[1], 6, 2), substr($matches[1], 4, 2), substr($matches[1], 0, 4)));
			$lines .= '<th align="right" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">Total</th>';
			$lines .= '</tr>';

			$total = 0;

			for($i=0; $i<count($items); $i++) {
				$connection = preg_match('/batchcredit_lbuk-([0-9]+)-([0-9.]+)/', $key, $matches) ? $connections[1]['Connection'] : $connections[0]['Connection'];

				$credit = new CreditNote($items[$i], $connection);

                $lines .= '<tr>';
				$lines .= sprintf('<td align="left">%d</td>', $credit->ID);
				$lines .= sprintf('<td align="right">&pound;%s</td>', number_format(round($credit->Total, 2), 2, '.', ','));
				$lines .= '</tr>';

				$total += $credit->Total;
			}

            $lines .= '<tr>';
			$lines .= '<td align="left"></td>';
			$lines .= sprintf('<td align="right"><strong>&pound;%s</strong></td>', number_format(round($total, 2), 2, '.', ','));
			$lines .= '</tr>';

            $findReplace = new FindReplace();
			$findReplace->Add('/\[INVOICE_LINES\]/', $lines);

			$templateFile = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/print/sage_invoices.tpl");
			$templateHtml = '';

			for($i=0; $i<count($templateFile); $i++) {
				$templateHtml .= $findReplace->Execute($templateFile[$i]);
			}

            if(!empty($html)) {
				$html .= '<br style="page-break-after:always" />';
			}

			$html .= $templateHtml;
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