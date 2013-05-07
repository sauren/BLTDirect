<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');

$session->Secure(2);

if(!isset($_REQUEST['callback'])) {
	echo '<script language="javascript" type="text/javascript">alert(\'An error has occurred.\n\nPlease inform the system administrator that the callback function is absent.\'); window.close();</script>';
	require_once('lib/common/app_footer.php');
	exit;
}

require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/LibraryMedia.php');

$page = new Page('Find Media', 'Tick the media you wish to use.');
$page->Display('header');

$data = new DataQuery("SELECT * FROM library_media");
if($data->TotalRows == 0) {
	echo '<p>There is no media currently available for browsing.</p>';
} else {
	?>
	<script language="JavaScript">
	var processMedia = function(id, title, src) {
		window.opener.<?php echo $_REQUEST['callback']; ?>(id, title, src);
		window.close();
	}
	</script>
	<?php
	echo '<table class="DataTable"><thead><tr><th>Thumbnail</th><th>Title</th><th width="1%" nowrap="nowrap">&nbsp;</th></tr></thead><tbody>';

	$data = new DataQuery("SELECT * FROM library_media");
	if($data->TotalRows == 0) {
		echo '<tr><td align="middle" colspan="3">There are currently no items to view.</td></tr>';
	} else {
		while($data->Row){
			echo '<tr>';
			echo sprintf('<td><a href="%s%s" target="_blank"><img src="%s%s" border="0" alt="%s" /></a></td>', $GLOBALS['MEDIA_DIR_WS'], $data->Row['SRC'], $GLOBALS['MEDIA_DIR_WS'], $data->Row['Thumb'], $data->Row['Title']);
			echo sprintf('<td>%s&nbsp;</td>', $data->Row['Title']);
			echo sprintf('<td nowrap="nowrap" align="right"><a href="%s%s" target="_blank"><img src="./images/folderopen.gif" alt="Open Media" border="0" /></a> <a href="javascript:processMedia(\'%d\', \'%s\', \'%s\');"><img src="./images/aztector_5.gif" alt="Use Media" border="0" /></a></td></tr>', $GLOBALS['MEDIA_DIR_WS'], $data->Row['SRC'], $data->Row['Media_ID'], $data->Row['Title'], $data->Row['SRC']);
			echo '</tr>';

			$data->Next();
		}
	}
	$data->Disconnect();

	echo '</tbody></table>';
}
$data->Disconnect();

$page->Display('footer');

require_once('lib/common/app_footer.php');
?>