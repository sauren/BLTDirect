<?php
	require_once('lib/common/app_header.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/QuoteNote.php');
	$session->secure(2);

	$quote = new Quote($_REQUEST['qid']);

	if($action == 'dismiss'){
		$data = new DataQuery(sprintf("select * from quote_note where Quote_ID=%d and Is_Alert='Y'", mysql_real_escape_string($quote->ID)));
		while($data->Row){
			$id = 'dismiss_' . $data->Row['Quote_Note_ID'];
			if(isset($_REQUEST[$id]) && $_REQUEST[$id] == 'Y'){
				$update = new DataQuery(sprintf("update quote_note set Is_Alert='N' where Quote_Note_ID=%d", $data->Row['Quote_Note_ID']));
			}
			$data->Next();
		}
		$data->Disconnect();
		$GLOBALS['DBCONNECTION']->Close();

		// done everything now close browser;
		echo "<html><head><script>window.self.close();</script></head><body></body></html>";

	}


	$page = new Page('IMPORTANT!', '<img src="./images/icon_alert_1.gif" align="absmiddle" /> Please read the important notes below. You can remove this alert the next time you open this quote by dismissing all alerts.');

	$page->Display('header');

?>
<form action="quote_alerts.php" method="post">
	<input type="hidden" name="action" value="dismiss" />
	<input type="hidden" name="confirm" value="true" />
	<input type="hidden" name="qid" value="<?php echo $quote->ID; ?>" />
	<table class="catProducts" cellspacing="0">
		<tr>
			<th>Dismiss</th>
			<th>Alert</th>
		</tr>
	<?php
		$data = new DataQuery(sprintf("select * from quote_note where Quote_ID=%d and Is_Alert='Y'", mysql_real_escape_string($quote->ID)));
		while($data->Row){
	?>
		<tr>
			<td align="center"><input type="checkbox" name="dismiss_<?php echo $data->Row['Quote_Note_ID'];?>" value="Y" /></td>
			<td><?php echo $data->Row['Quote_Note']; ?></td>
		</tr>
	<?php
			$data->Next();
		}
		$data->Disconnect();
	?>
	</table>
	<br />
	<div align="center"><input type="submit" name="OK" value="OK" class="btn" /></div>
</form>
<?php
	$page->Display('footer');
require_once('lib/common/app_footer.php');
?>