<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProForma.php');

$session->Secure(3);

$proForma = new ProForma($_REQUEST['proformaid']);

if(strtolower($proForma->Status) == 'partially despatched'){

	if(isset($_REQUEST['confirm'])){
		$proForma->GetLines();

		for($i=0; $i < count($proForma->Line); $i++){
			if(empty($proForma->Line[$i]->DespatchID) && empty($proForma->Line[$i]->InvoiceID)){
				$proForma->Line[$i]->Status = 'Cancelled';
				$proForma->Line[$i]->Update();
			}
		}

		$proForma->Status = 'Despatched';
		$proForma->Update();

		echo '<html>
						<script>
							function closeWindow(){
								window.opener.location.reload(true);
								window.self.close();
							}
							//
						</script>
					  <body onload="closeWindow();">Closing Window...</body></html>';
		exit();
	}
	$page = new Page('Cancelling a Partially Despatched ProForma', 'Part of this pro forma has already been despatched, but you can cancel the remainding un-despatched items on pro forma. The overall pro forma status will change to Despatched and those pro forma lines cancelled will have their own status of Cancelled. <br /><br />Would you like to cancel the remainding un-despatched items now?');
	$page->Display('header');
?>
<br />
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="proformaid" value="<?php echo $proForma->ID; ?>" />
<input type="hidden" name="confirm" value="true" />
<input type="button" name="no" value="no" class="btn" onclick="window.self.close();"/>
<input type="submit" name="yes" value="yes" class="btn" />
</form>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
} else {
	if(isset($_REQUEST['confirm'])){
		$proForma->GetLines();

		for($i=0; $i < count($proForma->Line); $i++){
			$proForma->Line[$i]->Status = 'Cancelled';
			$proForma->Line[$i]->Update();
		}

		$proForma->Status = 'Cancelled';
		$proForma->Update();

		echo '<html>
						<script>
							function closeWindow(){
								window.opener.location.reload(true);
								window.self.close();
							}
							//
						</script>
					  <body onload="closeWindow();">Closing Window...</body></html>';
		exit();
		exit();
	}
	$page = new Page('Cancelling an ProForma', 'This action will cancel the pro forma and all of its items. Would you like to continue?');
	$page->Display('header');
?>
<br />
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="proformaid" value="<?php echo $proForma->ID; ?>" />
<input type="hidden" name="confirm" value="true" />
<input type="button" name="no" value="no" class="btn" onclick="window.self.close();"/>
<input type="submit" name="yes" value="yes" class="btn" />
</form>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
}
?>