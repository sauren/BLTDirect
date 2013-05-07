<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->Icons['valid'] = '';
$form->AddField('action', 'Action', 'hidden', 'send', 'alpha', 4, 4);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('title', 'Title', 'select', '', 'anything', 0, 20, false);
$form->AddOption('title', '', '');

$data = new DataQuery("SELECT * FROM person_title ORDER BY Person_Title ASC");
while($data->Row){
	$form->AddOption('title', $data->Row['Person_Title'], $data->Row['Person_Title']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('fname', 'First Name', 'text', '', 'anything', 1, 60, false);
$form->AddField('lname', 'Last Name', 'text', '', 'anything', 1, 60, false);
$form->AddField('email', 'Email Address', 'text', '', 'email', NULL, NULL, true, 'style="width: 200px;"');
$form->AddField('format', 'Your Preferred Email Format', 'select', 'H', 'alpha', 1, 1);
$form->AddOption('format', 'H', 'HTML');
$form->AddOption('format', 'P', 'Plain Text');

if(strtolower(param('confirm', '')) == "true"){
	if($form->Validate()) {
		$emailAddress = trim($form->GetValue('email'));
		$data = new DataQuery(sprintf("SELECT c.Contact_ID FROM person AS p INNER JOIN contact AS c ON c.Person_ID=p.Person_ID WHERE Email LIKE '%s'", mysql_real_escape_string($emailAddress)));
		if($data->TotalRows > 0) {
			while($data->Row) {
				$contact = new Contact($data->Row['Contact_ID']);
				$contact->OnMailingList = $form->GetValue('format');
				$contact->Update();

				$data->Next();
			}
		} else {
			$customer = new Customer();
			$customer->Username = $form->GetValue('email');
			$customer->Contact->Type = 'I';
			$customer->Contact->Person->Title = $form->GetValue('title');
			$customer->Contact->Person->Name = addslashes($form->GetValue('fname'));
			$customer->Contact->Person->LastName = addslashes($form->GetValue('lname'));
			$customer->Contact->Person->Email = $form->GetValue('email');
			$customer->Contact->OnMailingList = $form->GetValue('format');
			$customer->Contact->Add();
			$customer->Add();
		}

		redirect(sprintf("Location: %s?subscribed=true", $_SERVER['PHP_SELF']));
	}
}
include("ui/nav.php");
include("ui/search.php");
?>
<div class="maincontent">
<div class="maincontent1">
		<?php if(param('subscribed')){ ?>
			   <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">You have Subscribed to BLT Direct</span></div>			  <p>Your email address has been added to our newsletter mailing list.</p>
			  <p><a href="./index.php">Visit our homepage</a></p>
		<?php } else { ?>
              <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Subscribe to BLT Direct</span></div>
			  <p>Subscribe to our e-newsletter and get amazing light bulbs, lamp and tube offers and discounts delivered direct to your inbox. Keep up to date with new lighting technology emerging to help save on your electricity bill and more. </p>
			  <p>To subscribe please enter your email address in the field below and press the yes button.</p>

		<?php
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}
		echo $form->Open();
		echo $form->GetHtml('action');
		echo $form->GetHtml('confirm');
		?>
			<table width="100%" cellspacing="0" class="form">
				<tr>
					<th colspan="4">Your Subscription Details</th>
				</tr>
						<table border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td><?php echo $form->GetLabel('title'); ?> <?php echo $form->GetIcon('title'); ?>
								<?php echo $form->GetHtml('title'); ?> </td>
                                </tr><tr>
								<td><?php echo $form->GetLabel('fname'); ?> <?php echo $form->GetIcon('fname'); ?><br />
								<?php echo $form->GetHtml('fname'); ?> </td></tr>
                                <tr>
								<td><?php echo $form->GetLabel('lname'); ?> <?php echo $form->GetIcon('lname'); ?><br />
								<?php echo $form->GetHtml('lname'); ?></td>
							</tr>
						
				<tr>
					<td><?php echo $form->GetLabel('email'); ?> <br /><?php echo $form->GetHtml('email'); ?>
					<?php echo $form->GetIcon('email'); ?></td>
                    </tr><br />
					
				
				<tr>
					<td><?php echo $form->GetLabel('format'); ?><br /> <?php echo $form->GetHtml('format'); ?>
					<?php echo $form->GetIcon('format'); ?></td>
                    </tr>
                    </table>
		    		
				
			</table><br />

			<input type="submit" name="Subscribe" value="Subscribe" class="submit" /><br />
		<?php
		echo $form->Close();
		}
		?>
		<br />
		<p>If you are already subscribed and would like to cancel your subscription, <a href="unsubscribe.php">click here</a>.</p>
        </div>
        </div>
        <?php include("ui/footer.php")?>
        <?php include('../lib/common/appFooter.php'); ?>