<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/OrderNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure();
$order = new Order();

if(id_param('oid') && $order->Get(id_param('oid'))) {
	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND o.Is_Sample='N' AND o.Order_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID), mysql_real_escape_string(id_param('oid'))));
	if($data->Row['Counter'] == 0) {
		redirect(sprintf("Location: orders.php"));
	}
	$data->Disconnect();
} else {
	redirect('Location: orders.php');
}

$order->Customer->Get();
$order->Customer->Contact->Get();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('oid', 'Order ID', 'hidden', $order->ID, 'numeric_unsigned', 1, 11);
$form->AddField('subject', 'Subject', 'select', 0, 'numeric_unsigned', 1, 11);
$form->AddOption('subject', '', '-- Subject -- ');

$data = new DataQuery("select * from order_note_type where Is_Public='Y' order by Type_Name asc");
while($data->Row){
	$form->AddOption('subject', $data->Row['Order_Note_Type_ID'], $data->Row['Type_Name']);
	$data->Next();
}
$data->Disconnect();

$form->AddField('message', 'Note', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:100%; height:100px"');

if(strtolower(param('confirm', '')) == "true"){
	if($form->Validate()){
		$note = new OrderNote();
		$note->TypeID = $form->GetValue('subject');
		$note->Message = $form->GetValue('message');
		$note->OrderID = $form->GetValue('oid');
		$note->IsPublic = 'Y';
		$note->IsAlert = 'Y';
		$note->Add();

		$note->SendToAdmin($order->Customer->Contact->Person->GetFullName(), $order->Customer->GetEmail());

		$order->IsNotesUnread = 'Y';
		$order->Update();

		redirect("Location: orderNotes.php?sent=true&oid=" . $order->ID);
	}
}
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Order Notes for Order Ref #<?php echo $order->ID; ?></span></div>
<div class="maincontent">
<div class="maincontent1">
			<p><a href="orders.php">&laquo; View All Orders</a> | <a href="/orders.php?orderid=<?php echo $order->ID; ?>">View Order Ref #<?php echo $order->ID; ?> Details</a> </p>

				<?php
				if(param('sent')) {
					?>
					<h3 class="blue">Thank you...</h3>
					<p>Your message has been added to your order history and has been sent to us directly.</p>
					<?php
				}

				if(!$form->Valid){
					echo $form->GetError();
					echo "<br />";
				}

				echo '<table class="catProducts" cellspacing="0">';

				$data = new DataQuery(sprintf("select note.*, ot.Type_Name from order_note as note left join order_note_type as ot on note.Order_Note_Type_ID=ot.Order_Note_Type_ID where note.Order_ID=%d AND note.Is_Public='Y' order by note.Created_On desc", $order->ID));
				if($data->TotalRows > 0){
					while($data->Row){
						if(empty($data->Row['Created_By'])){
							$author = $order->Customer->Contact->Person->GetFullName();
						} else {
							$user = new User($data->Row['Created_By']);
							$author = $user->Person->GetFullName();
						}
						$date = cDatetime($data->Row['Created_On']);

						echo sprintf('<tr><th>Subject: %s</th><th>Date: %s</th><th>Author: %s</th></tr>', (strlen($data->Row['Type_Name']) > 0) ? $data->Row['Type_Name'] : '<em>Unknown</em>', $date, $author);
						echo sprintf('<tr><td colspan="3">%s</td></tr>', $data->Row['Order_Note']);

						$data->Next();
					}
				} else {
					echo '<tr><td align="center">No Order Notes have been entered</td></tr>';
				}
				$data->Disconnect();

				echo '</table><br />';

				echo $form->Open();
				echo $form->GetHTML('action');
				echo $form->GetHTML('confirm');
				echo $form->GetHTML('oid');
				echo '<div id="ShippingCalc"><h3 class="blue">Add Order Note</h3><p>If you would like to add any further information to your order please type below and click the add order note button.</p>';
				echo $form->GetHTML('subject').'<br />';
				echo $form->GetHTML('message');
				echo sprintf('<br /><br /><input type="submit" name="Add Order Note" value="Add Order Note" class="submit" tabindex="%s"></div>', $form->GetTabIndex());
				echo $form->Close();
	?>
	</div>
    </div>
    <?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>