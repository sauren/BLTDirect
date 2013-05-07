<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/FindReplace.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Supplier.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Template.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/StandardWindow.php');

if($action == 'email') {
	$session->Secure(2);
	email();
	exit;
} elseif($action == 'emailchase') {
	$session->Secure(2);
	emailChase();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function email() {
	$purchase = new Purchase($_REQUEST['pid']);
	$purchase->GetLines();

	$tempSup = new Supplier($purchase->Line[0]->SuppliedBy);
	$purchase->EmailToBuy($tempSup->GetEmail());

	redirectTo(sprintf('?pid=%d&emailed', $_REQUEST['pid']));
}

function emailChase() {
	$purchase = new Purchase($_REQUEST['pid']);

	$supplier = new Supplier();

	if($supplier->Get($purchase->SupplierID)) {
		$supplier->Contact->Get();

		$purchases = array();

		$data = new DataQuery(sprintf('SELECT p.Purchase_ID, p.Supplier_ID, pl.Product_ID, pl.Description, pl.Quantity_Decremental, pl.Cost, pl.Quantity_Decremental*pl.Cost AS Total FROM purchase AS p INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID AND pl.Quantity_Decremental>0 WHERE p.Purchase_ID=%d AND p.Purchase_Status IN (\'Unfulfilled\', \'Partially Fulfilled\') ORDER BY pl.Description ASC', mysql_real_escape_string($purchase->ID)));
		while($data->Row) {
			$purchases[] = $data->Row;

			$data->Next();
		}
		$data->Disconnect();

		if(!empty($purchases)) {
			$purchaseHtml = '<table width="100%" cellspacing="0" cellpadding="5" class="order"><tr><th align="left">Purchase</th><th align="left">Product</th><th align="left">Quickfind</th><th align="right">Quantity</th><th align="right">Cost</th><th align="right">Total</th></tr>';

			foreach($purchases as $purchaseData) {
				$purchaseHtml .= sprintf('<tr><td>%d</td><td>%s</td><td>%d</td><td align="right">%d</td><td align="right">&pound;%s</td><td align="right">&pound;%s</td></tr>', $purchaseData['Purchase_ID'], $purchaseData['Description'], $purchaseData['Product_ID'], $purchaseData['Quantity_Decremental'], number_format(round($purchaseData['Cost'], 2), 2, '.', ','), number_format(round($purchaseData['Total'], 2), 2, '.', ','));
			}

			$purchaseHtml .= '</table><br />';

			$findReplace = new FindReplace();
			$findReplace->Add('/\[SUPPLIER_ID\]/', $supplier->Contact->ID);
			$findReplace->Add('/\[SUPPLIER_NAME\]/', $supplier->Contact->Person->GetFullName());
			$findReplace->Add('/\[SUPPLIER_EMAIL\]/', $supplier->Contact->Person->Email);

			$findReplace->Add('/\[PURCHASES\]/', $purchaseHtml);

			$template = $findReplace->Execute(Template::GetContent('email_purchase_reminder'));

			$findReplace = new FindReplace();
			$findReplace->Add('/\[BODY\]/', $template);
			$findReplace->Add('/\[NAME\]/', $supplier->Contact->Person->Name);
			$findReplace->Add('/\[SALES\]/', 'Wendy Ellwood<br />customerservices@bltdirect.com');

			$template = $findReplace->Execute(Template::GetContent('email_template_informal'));

			$queue = new EmailQueue();
			$queue->GetModuleID('purchases');
			$queue->Type = 'H';
			$queue->Priority = 'H';
			$queue->Subject = sprintf("%s - Purchase Order Reminder [#%d]", $GLOBALS['COMPANY'], $purchase->ID);
			$queue->Body = $template;
			$queue->ToAddress = $supplier->Contact->Person->Email;
			$queue->FromAddress = 'customerservices@bltdirect.com';
			$queue->Add();
		}
	}

	redirectTo(sprintf('?pid=%d&emailed', $purchase->ID));
}

function view() {
	if(!isset($_REQUEST['pid'])) {
		redirect("Location: purchase_administration.php");
	}

	$purchase = new Purchase($_REQUEST['pid']);

	$page = new Page('Purchase #' . $purchase->ID, '');
	$page->Display('header');

	if(isset($_REQUEST['emailed'])) {
		$bubble = new Bubble('Email Sent', 'Email has been added to the email queue.');

		echo $bubble->GetHTML();
		echo '<br />';
	}
	?>

	<table border="0" cellspacing="0" width="100%">
		<tr>
			<td valign="top">

				<?php
				$win = new StandardWindow("Purchase Options");

				echo $win->Open();
				echo $win->AddHeader('Please make a selection.');
				echo $win->OpenContent();
				?>

				<ul>
					<li><a href="purchase_view.php?purchaseid=<?php echo $purchase->ID; ?>" target="_blank">Printable Version</a><br /><br /></li>

					<li><a href="javascript:confirmRequest('?pid=<?php echo $purchase->ID; ?>&action=email','Are you sure you want to email this purchase order to the supplier?');">Email Purchase Order</a><br /><br /></li>

					<li><a href="javascript:confirmRequest('?pid=<?php echo $purchase->ID; ?>&action=emailchase','Are you sure you want to email this purchase chase request to the supplier?');">Email Chase Request</a><br /><br /></li>
				</ul>

				<?php
				echo $win->CloseContent();
				echo $win->Close();
				?>

			</td>
			<td style="width:20px;" valign="top"></td>
			<td>
				<div style="width:100%; height:100%; overflow:auto;">
					<?php echo $purchase->GetDocToBuy(); ?>
				</div>
			</td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}