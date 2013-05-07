<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');

$session->Secure(3);

$quote = new Quote($_REQUEST['quoteid']);

if(strtolower($quote->Status) == 'partially despatched'){

	if(isset($_REQUEST['confirm'])){
		$quote->GetLines();

		for($i=0; $i < count($quote->Line); $i++){
			if(empty($quote->Line[$i]->DespatchID) && empty($quote->Line[$i]->InvoiceID)){
				$quote->Line[$i]->Status = 'Cancelled';
				$quote->Line[$i]->Update();
			}
		}

		$quote->Status = 'Despatched';
		$quote->Update();

		echo '<html>
						<script>
							function closeWindow(){
								window.opener.location.reload(true);
								window.self.close();
							}
						</script>
					  <body onload="closeWindow();">Closing Window...</body></html>';
		exit;
	}
	
	$page = new Page('Cancelling a Partially Despatched Quote', 'Part of this quote has already been despatched, but you can cancel the remainding un-despatched items on quote. The overall quote status will change to Despatched and those quote lines cancelled will have their own status of Cancelled. <br /><br />Would you like to cancel the remainding un-despatched items now?');
	$page->Display('header');
?>
<br />
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="quoteid" value="<?php echo $quote->ID; ?>" />
<input type="hidden" name="confirm" value="true" />
<input type="button" name="no" value="no" class="btn" onclick="window.self.close();"/>
<input type="submit" name="yes" value="yes" class="btn" />
</form>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
} else {
	if(isset($_REQUEST['confirm'])){
		$quote->GetLines();

		for($i=0; $i < count($quote->Line); $i++){
			$quote->Line[$i]->Status = 'Cancelled';
			$quote->Line[$i]->Update();
		}

		$quote->Status = 'Cancelled';
		$quote->Update();

		echo '<html>
						<script>
							function closeWindow(){
								window.opener.location.reload(true);
								window.self.close();
							}
						</script>
					  <body onload="closeWindow();">Closing Window...</body></html>';
		exit;
	}
	
	$page = new Page('Cancelling an Quote', 'This action will cancel the quote and all of its items. Would you like to continue?');
	$page->Display('header');
?>
<br />
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="quoteid" value="<?php echo $quote->ID; ?>" />
<input type="hidden" name="confirm" value="true" />
<input type="button" name="no" value="no" class="btn" onclick="window.self.close();"/>
<input type="submit" name="yes" value="yes" class="btn" />
</form>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
}