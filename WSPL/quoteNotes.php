<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Quote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/QuoteNote.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure();

if(id_param('qid')){
	$quote = new Quote(id_param('qid'));

	$data = new DataQuery(sprintf("SELECT COUNT(*) AS Counter FROM quote AS q INNER JOIN customer AS c ON c.Customer_ID=q.Customer_ID INNER JOIN contact AS n ON n.Contact_ID=c.Contact_ID WHERE ((n.Parent_Contact_ID>0 AND n.Parent_Contact_ID=%d) OR (n.Parent_Contact_ID=0 AND n.Contact_ID=%d)) AND q.Quote_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID), mysql_real_escape_string($session->Customer->Contact->ID), mysql_real_escape_string(id_param('qid'))));
	if($data->Row['Counter'] == 0) {
		redirect(sprintf("Location: quotes.php"));
	}
	$data->Disconnect();
} else {
	redirect('Location: quotes.php');
}

$quote->Customer->Get();
$quote->Customer->Contact->Get();

$sql = sprintf("select * from quote_note where Quote_ID=%d and Is_Public='Y' order by Created_On desc", mysql_real_escape_string($quote->ID));

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('qid', 'Quote ID', 'hidden', $quote->ID, 'numeric_unsigned', 1, 11);
$form->AddField('message', 'Note', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:80%; height:100px"');

if(strtolower(param('confirm', '')) == "true"){
	if($form->Validate()){
		$note = new QuoteNote();
		$note->Message = $form->GetValue('message');
		$note->QuoteID = $form->GetValue('qid');
		$note->IsPublic = 'Y';
		$note->Add();

		$note->SendToAdmin($quote->Customer->Contact->Person->GetFullName(), $quote->Customer->GetEmail());

		redirect("Location: quoteNotes.php?sent=true&qid=" . $quote->ID);
	}
}
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Quote Notes for Quote Ref #<?php echo $quote->ID; ?></span></div>
<div class="maincontent">
<div class="maincontent1">
			<p><a href="./quotes.php">&laquo; View All Quotes</a> | <a href="./quote.php?quoteid=<?php echo $quote->ID; ?>">View Quote Ref #<?php echo $quote->ID; ?> Details</a> </p>

				<?php if(param('sent')) { ?>
				<h3 class="blue">Thank you...</h3>
				<p>Your message has been added to your quote history and has been sent to us directly.</p>
				<?php } ?>
				<?php
	// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}


		echo '<table class="catProducts" cellspacing="0" width="100%">';
		$data = new DataQuery($sql);
		if($data->TotalRows > 0){
			while($data->Row){
				if(empty($data->Row['Created_By'])){
					$author = $quote->Customer->Contact->Person->GetFullName();
				} else {
					$user = new User($data->Row['Created_By']);
					$author = $user->Person->GetFullName();
				}
				$date = cDatetime($data->Row['Created_On']);
				if(!empty($data->Row['Type_Name'])) echo sprintf('<tr><th colspan="2">%s</th></tr>', $data->Row['Type_Name']);
				echo sprintf('<tr><th style="font-weight:normal;">Date: %s</th><th style="font-weight:normal;">Author: %s</th></tr>', $date, $author);
				echo sprintf('<tr><td colspan="2" width="100%">%s</td></tr>', $data->Row['Quote_Note']);
				$data->Next();
			}
		} else {
			echo '<tr><td align="center">No Quote Notes have been entered</td></tr>';
		}
		$data->Disconnect();
		echo '</table><br />';

		// now do the addition form
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('qid');
		echo '<div id="ShippingCalc"><h3 class="blue">Add Quote Note</h3><p>If you would like to add any further information to your quote please type below and click the add quote note button.</p>';
		echo $form->GetHTML('subject') . "<br />";
		echo $form->GetHTML('message');
		echo sprintf('<br /><p><input type="submit" name="add quote note" value="add quote note" class="submit" tabindex="%s" /></p></div>', $form->GetTabIndex());
		echo $form->Close();
	?>
</div>
</div>
<?php include("ui/footer.php");
include('../lib/common/appFooter.php'); ?>