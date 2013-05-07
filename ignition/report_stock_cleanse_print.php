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
		<title>Stock Cleanse Print Preview</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>

	<frameset border="0" rows="60,*" framespacing="2" frameborder="yes" id="frameSet" bordercolor="#5C6B80">
		<frame src="?action=openheader&warehouse=<?php echo $_REQUEST['warehouse']; ?>" name="frameHeader" frameborder="no" scrolling="auto" border="0"></frame>
		<frame src="?action=openbody&warehouse=<?php echo $_REQUEST['warehouse']; ?>" name="frameBody" frameborder="no" scrolling="auto" border="0"></frame>
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
				<td width="50%" align="left"><span class="pageTitle">Stock Cleanse Print Preview</span></td>
				<td width="50%" align="right"><a href="javascript:popUrl('?action=openbody&warehouse=<?php echo $_REQUEST['warehouse']; ?>&print=true' , 800, 600);"><img src="images/icon_print_1.gif" border="0" width="16" height="16" alt="Print" /></a></td>
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');

	$items = '<table width="100%" cellspacing="0" cellpadding="5" class="order">';
	$items .= '<tr><th align="left">Quickfind</th><th align="left">Product Name</th><th align="left">Locations</th></tr>';
	
	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, SUM(ws.Quantity_In_Stock) AS Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID INNER JOIN product AS p ON ws.Product_ID=p.Product_ID AND p.Is_Stocked='N' WHERE w.Warehouse_ID=%d AND p.Product_Type<>'G' GROUP BY p.Product_ID HAVING Quantity=0 ORDER BY p.Product_ID ASC", mysql_real_escape_string($_REQUEST['warehouse'])));
	while($data->Row) {
		$locations = array();
		
		$data2 = new DataQuery(sprintf("SELECT ws.Shelf_Location FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON ws.Warehouse_ID=w.Warehouse_ID WHERE w.Warehouse_ID=%d AND ws.Product_ID=%d", mysql_real_escape_string($_REQUEST['warehouse']), $data->Row['Product_ID']));
		while($data2->Row) {
			$locations[] = $data2->Row['Shelf_Location'];

			$data2->Next();
		}
		$data2->Disconnect();

		$items .= sprintf('<tr><td>%d</td><td>%s</td><td>%s</td></tr>', $data->Row['Product_ID'], strip_tags($data->Row['Product_Title']), implode(', ', $locations));
	
		$data->Next();
	}
	$data->Disconnect();

	$items .= '<table>';
	
	$findReplace = new FindReplace();
	$findReplace->Add('/\[LOCATIONS\]/', $items);

	echo $findReplace->Execute(Template::GetContent('print_stock_cleanse'));

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