<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(3);

$despatch = new Despatch();

if(!$despatch->Get($_REQUEST['despatchid'])) {
	redirectTo('orders_despatched.php');
}

$despatch->IsIgnition = true;
$despatch->GetLines();
$despatch->Order->Get();
	
$page = new Page('Despatch #' . $despatch->ID, '');
$page->Display('header');
?>

<table border="0" cellspacing="0" width="100%">
	<tr>
		<td valign="top" style="width: 250px;">

			<?php
			$window = new StandardWindow('Despatch Options');
			echo $window->Open();
			echo $window->AddHeader('Please make a selection.');
			echo $window->OpenContent();
			?>

			<ul>
				<li><?php echo sprintf('<a href="order_details.php?orderid=%s">Order Details</a>', $despatch->Order->ID); ?><br /><br /></li>
				<li><?php echo sprintf('<a href="despatch_changeAddress.php?despatchid=%s">Change Address</a>', $despatch->ID); ?><br /><br /></li>
				<li><?php echo sprintf('<a href="despatch_view.php?despatchid=%s" target="_blank">Print Despatch</a>', $despatch->ID); ?><br /><br /></li>
			</ul>

			<?php
			echo $window->CloseContent();
			echo $window->Close();
			?>

		</td>
		<td valign="top" style="width: 20px;"></td>
		<td valign="top">
			<?php echo $despatch->GetDocument(($despatch->Order->IsPlainLabel == 'N') ? true : false); ?>
		</td>
	</tr>
</table>

<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');