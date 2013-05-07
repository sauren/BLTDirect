<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');

$session->Secure(3);

$return = new ProductReturn($_REQUEST['id']);

if(isset($_REQUEST['confirm'])){
	$return->GetLines();

	for($i=0; $i < count($return->Line); $i++){
		$return->Line[$i]->Status = 'Cancelled';
		$return->Line[$i]->Update();
	}

	$return->Status = 'Cancelled';
	$return->Update();

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
$page = new Page('Cancelling a Return', 'This action will cancel the return and all of its items. Would you like to continue?');
$page->Display('header');
?>
<br />
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<input type="hidden" name="id" value="<?php echo $return->ID; ?>" />
<input type="hidden" name="confirm" value="true" />
<input type="button" name="no" value="no" class="btn" onclick="window.self.close();"/>
<input type="submit" name="yes" value="yes" class="btn" />
</form>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');
?>
