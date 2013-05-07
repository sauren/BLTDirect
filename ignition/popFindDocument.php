<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

$session->Secure(2);

if(!isset($_REQUEST['callback'])) {
	echo '<script language="javascript" type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the callback function is absent.\'); window.close();</script>';
	require_once('lib/common/app_footer.php');
	exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Find Document</title>
	<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>
	<script language="javascript" type="text/javascript" src="js/TreeMenu.js"></script>
	<link href="css/NavigationMenu.css" rel="stylesheet" type="text/css" />

	<script language="javascript" type="text/javascript">
	var myTree = new TreeMenu('myTree');

	this.setNode = function(id, str) {
		window.opener.<?php echo $_REQUEST['callback']; ?>(id, str);
		window.self.close();
	}
	</script>
</head>
<body id="Wrapper">

	<div id="Navigation"></div>

	<script>
	myTree.url = 'lib/util/loadDocumentChildren.php';
	myTree.loading = '<div class="treeIsLoading"><img src="images/TreeMenu/loading.gif" align="absmiddle" /> Loading...</div>';
	myTree.addClass('default', 'images/TreeMenu/page.gif', 'images/TreeMenu/folder.gif', 'images/TreeMenu/folderopen.gif');
	myTree.addNode(0, null, '_root', 'default', true, null, null);
	myTree.build('Navigation');
	</script>

</body>
</html>
<?php
require_once('lib/common/app_footer.php');
?>