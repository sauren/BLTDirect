<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'unsubscribe', 'alpha', 11, 11);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('email', 'Email Address', 'text', '', 'email', NULL, NULL, true, 'style="width: 200px;"');

if(param('ref')) {
	$reference = trim(urldecode(param('ref')));
	$reference = base64_decode($reference);

	$cypher = new Cipher($reference);
	$cypher->Decrypt();

	if(preg_match(sprintf("/%s/", $form->RegularExp['email']), $cypher->Value)) {
		$data = new DataQuery(sprintf("SELECT c.Contact_ID FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID WHERE p.Email LIKE '%s'", mysql_real_escape_string(trim($cypher->Value))));
		if($data->TotalRows > 0) {
			while($data->Row) {
				new DataQuery(sprintf("UPDATE contact SET On_Mailing_List='N' WHERE Contact_ID=%d", $data->Row['Contact_ID']));

				$data->Next();
			}
		}
		$data->Disconnect();

		redirect(sprintf('Location: unsubscribe.php?unsubscribed=true'));
	} else {
		redirect(sprintf('Location: unsubscribe.php?action=unsubscribe&confirm=true&email=%s', $cypher->Value));
	}
}

if(strtolower(param('confirm', '')) == "true"){
	if($form->Validate()) {
		$data = new DataQuery(sprintf("SELECT c.Contact_ID FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID WHERE p.Email LIKE '%s'", mysql_real_escape_string(trim($form->GetValue('email')))));
		if($data->TotalRows > 0) {
			while($data->Row) {
				new DataQuery(sprintf("UPDATE contact SET On_Mailing_List='N' WHERE Contact_ID=%d", $data->Row['Contact_ID']));

				$data->Next();
			}

			$data->Disconnect();

			redirect('Location: unsubscribe.php?unsubscribed=true');
		} else {
			$form->AddError('The email address entered could not be found in our system.', 'email');
		}
		$data->Disconnect();
	}
}
?>
<?php
include("ui/nav.php");
include("ui/search.php");?>
<div class="maincontent">
<div class="maincontent1">
<?php if(param('unsubscribed')){ ?>
			  <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">You have Unsubscribed from BLT Direct</span></div>
			  <p>Your email address has been removed from our newsletter mailing list.</p>
			  <p><a href="./">Visit our homepage</a></p>
		<?php } else { ?>
              <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Unsubscribe from BLT Direct</span></div>

			  <p style="text-align:justify">Are you sure you want to unsubsrcibe from our newsletter informing you of great discounts on our products?</p>
			  <p style="text-align:justify">To unsubscribe please enter your email address in the field below and press the yes button.</p>
<br />
		<?php
		if(!$form->Valid){
			echo $form->GetError();
			echo '<br />';
		}
		echo $form->Open();
		echo $form->GetHtml('action');
		echo $form->GetHtml('confirm');
		?>
			  <p>Email Address<br /><?php echo $form->GetHTML('email') . ' ' . $form->GetIcon('email'); ?></p>
			  <p><input type="submit" name="Unsubscribe" value="Unsubscribe" class="submit" /></p>
		<?php
		echo $form->Close();
		}
		?>
		</div>
        </div>
        <?php include("ui/footer.php")?>
        <?php include('../lib/common/appFooter.php'); ?>