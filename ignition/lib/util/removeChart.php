<?php
require_once('../classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'].'lib/classes/IFile.php');

if(isset($_REQUEST['chart'])) {
	sleep(3);

	$file = new IFile(null, ".");
	$file->FileName = sprintf('../../%s', $_REQUEST['chart']);
	$file->Delete();
}
?>