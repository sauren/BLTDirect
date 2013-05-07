<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(3);

$credit = new CreditNote($_REQUEST['cnid']);

if($action == 'email'){
	$credit->EmailCustomer();
	
	redirect(sprintf("Location: credit_note.php?cnid=%d", $credit->ID));
}

$page = new Page(sprintf('Credit Note #%d', $credit->ID, ''));
$page->Display('header');
?>

<table border="0" cellspacing="0" width="100%">
	<tr>
		<td valign="top">
    
			<?php
			$window = new StandardWindow("Credit Note Options");
			
			echo $window->Open();
			echo $window->AddHeader('Please make a selection.');
			echo $window->OpenContent();
			?>
			
			<ul>
				<li><?php echo sprintf('<a href="order_details.php?orderid=%s">&laquo; Back to Order</a>', $credit->Order->ID); ?><br /><br /></li>
				<li><a href="credit_note_view.php?cnid=<?php echo $credit->ID; ?>" target="_blank">Printable Version</a><br /><br /></li>
				<li><a href="javascript:confirmRequest('<?php echo $_SERVER['PHP_SELF']; ?>?cnid=<?php echo $credit->ID; ?>&action=email', 'Are you sure you want to email this credit note to the customer?');">Email Credit Note</a><br /><br /></li>
			</ul>
			
			<?php
			echo $window->CloseContent();
			echo $window->Close();
			?>
			
		</td>
		<td style="width:20px;" valign="top"></td>
		<td>
		
			<div style="width:100%; height:100%; overflow:auto;">
				<?php echo $credit->GetDocument(); ?>
			</div>
		
		</td>
	</tr>
</table>
<?php
$page->Display('footer');
require_once('lib/common/app_footer.php');