<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(2);
?>
<html>
<head>
	<title>Ignition Window</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="stylesheet" href="css/i_import.css" type="text/css" />
	<script src="js/HttpRequest.js" language="javascript"></script>
	<script src="js/HttpRequestData.js" language="javascript"></script>
	<script language="javascript" type="text/javascript">
		var refresh = function(period) {
			setTimeout(function() {
				window.location.reload(true);
			}, period);
		}

		window.onload = function() {
			refresh(300000);
		}
	</script>
	<style>
		body {
			margin: 0;
			padding: 10px;
		}
	</style>
</head>
<body>

<table cellspacing="0" cellpadding="0" width="100%" border="0">
	<tr>
		<th style="text-align: left; padding: 5px 0 0 0;">
			<span style="font-size: 9px;">Recent Documents</span>
			<hr style="height: 1px;" />
		</th>
	</tr>

	<?php
	$data = new DataQuery(sprintf("SELECT Recent_Name, Recent_Url FROM users_recent WHERE User_ID=%d ORDER BY Created_On DESC LIMIT 0, 100", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	while($data->Row) {
		?>

		<tr>
			<td nowrap="nowrap" valign="top"><a href="<?php echo $data->Row['Recent_Url']; ?>" target="i_content_display"><?php echo $data->Row['Recent_Name']; ?></a></td>
		</tr>

		<?php

		$data->Next();
	}
	$data->Disconnect();
	?>

</table>

</body>
</html>
<?php
require_once('lib/common/app_footer.php');
?>