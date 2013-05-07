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
		<title>Contact Group Print Preview</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>

	<frameset border="0" rows="60,*" framespacing="2" frameborder="yes" id="frameSet" bordercolor="#5C6B80">
		<frame src="?action=openheader&gid=<?php echo $_REQUEST['gid']; ?>" name="frameHeader" frameborder="no" scrolling="auto" border="0"></frame>
		<frame src="?action=openbody&gid=<?php echo $_REQUEST['gid']; ?>" name="frameBody" frameborder="no" scrolling="auto" border="0"></frame>
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
				<td width="50%" align="left"><span class="pageTitle">Contact Group Print Preview</span></td>
				<td width="50%" align="right"><a href="javascript:popUrl('?action=openbody&gid=<?php echo $_REQUEST['gid']; ?>&print=true' , 800, 600);"><img src="images/icon_print_1.gif" border="0" width="16" height="16" alt="Print" /></a></td>
			</tr>
		</table>

	</body>
	</html>

	<?php
	require_once('lib/common/app_footer.php');
}

function openBody() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');

	$items = array();

	$data = new DataQuery(sprintf("SELECT c.Contact_ID, o.Org_Name, CONCAT_WS(' ', p.Name_First, p.Name_Last) AS Contact_Name FROM contact AS c INNER JOIN contact_group_assoc AS cga ON cga.Contact_ID=c.Contact_ID AND cga.Contact_Group_ID=%d INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY o.Org_Name ASC, Contact_Name ASC", mysql_real_escape_string($_REQUEST['gid'])));
	while($data->Row) {
		$contact = new Contact($data->Row['Contact_ID']);
		
		$items[] = sprintf('<strong>%s</strong><br />%s', $contact->Person->GetFullName(), $contact->Person->Address->GetLongString());
	
		$data->Next();
	}
	$data->Disconnect();
	
	$requests = '';
	$pageIndex = 0;
	$columns = 2;
	$columnIndex = 0;
	$rows = 6;
	$rowIndex = 0;
	
	for($i=0; $i<count($items); $i++) {
		if($rowIndex == 0) {
			if($pageIndex > 0) {
				$requests .= '<br style="page-break-before: always;" />';
			}

			$pageIndex++;
			
			$requests .= '<table width="100%" cellspacing="0" cellpadding="5" class="label">';
		}
		
		if($columnIndex == 0) {
			$requests .= '<tr>';
		}
		
		$requests .= sprintf('<td valign="top" class="column%d">', ($columnIndex + 1));
		$requests .= $items[$i];
		$requests .= '</td>';
		
		$columnIndex++;
		
		if($columnIndex == $columns) {
			$columnIndex = 0;
			
			$requests .= '</tr>';
		}
		
		$rowIndex++;
		
		if($rowIndex == ($rows * $columns)) {
			$rowIndex = 0;
			
			$requests .= '</table>';
		}
	}
	
	if($rowIndex < ($rows * $columns)) {
		$requests .= '</table>';
	}
	
	$findReplace = new FindReplace();
	$findReplace->Add('/\[REQUESTS\]/', $requests);

	echo $findReplace->Execute(Template::GetContent('print_catalogue_request'));

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

