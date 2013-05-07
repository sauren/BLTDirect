<?php
	require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');

	if($GLOBALS['USE_SSL'] && ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT'])){
		$url = ($GLOBALS['USE_SSL'])?$GLOBALS['HTTPS_SERVER']:$GLOBALS['HTTP_SERVER'];
		$self = substr($_SERVER['PHP_SELF'], 1);
		$qs = '';
		if(!empty($_SERVER['QUERY_STRING'])){$qs = '?' . $_SERVER['QUERY_STRING'];}
		redirect(sprintf("Location: %s%s%s", $url, $self, $qs));
	}

	$email = 'sales@bltdirect.com';

	$form = new Form($_SERVER['PHP_SELF']);
	$form->Icons['valid'] = '';
	$form->AddField('action', 'Action', 'hidden', 'send', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);

	$form->AddField('title', 'Title', 'select', 'Mr', 'alpha', 1, 4);
	$title = new DataQuery("select * from person_title order by Person_Title");
	while($title->Row){
		$form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
		$title->Next();
	}
	$title->Disconnect();
	unset($title);

	$form->AddField('fname', 'First Name', 'text', '', 'alpha_numeric', 1, 60);
	$form->AddField('lname', 'Last Name', 'text', '', 'alpha_numeric', 1, 60);

	$form->AddField('email', 'Your Email Address', 'text', '', 'email', NULL, NULL);
	$form->AddField('phone', 'Daytime Phone', 'text', '', 'telephone', NULL, NULL, false);

	$form->AddField('message', 'Message', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:90%; height:150px;"');

	$form->AddField('foundVia', 'How did you find us?', 'select', '0', 'numeric_unsigned', 1, 11);
	$found = new DataQuery("select * from customer_found_via order by Found_Via");
	while($found->Row){
		$form->AddOption('foundVia', $found->Row['Found_Via_ID'], $found->Row['Found_Via']);
		$found->Next();
	}
	$found->Disconnect();

	$confirmPassError  =  "";
	$userError = "";
	$emailError = "";
	if(strtolower(param('confirm', '')) == "true"){
		if($form->Validate()) {
			$findReplace = new FindReplace;
			$findReplace->Add('/\[TITLE\]/', $form->GetValue('title'));
			$findReplace->Add('/\[FNAME\]/', $form->GetValue('fname'));
			$findReplace->Add('/\[LNAME\]/', $form->GetValue('lname'));
			$findReplace->Add('/\[EMAIL\]/', $form->GetValue('email'));
			$findReplace->Add('/\[PHONE\]/', $form->GetValue('phone'));
			$findReplace->Add('/\[MESSAGE\]/', $form->GetValue('message'));

			// Replace Order Template Variables
			$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_job.tpl");
			$orderHtml = "";
			for($i=0; $i < count($orderEmail); $i++){
				$orderHtml .= $findReplace->Execute($orderEmail[$i]);
			}

			unset($findReplace);
			$findReplace = new FindReplace;
			$findReplace->Add('/\[BODY\]/', $orderHtml);
			$findReplace->Add('/\[NAME\]/', 'BLT Direct');
			// Get Standard Email Template
			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = "";
			for($i=0; $i < count($stdTmplate); $i++){
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			$mail = new htmlMimeMail5();
			$mail->setFrom($GLOBALS['EMAIL_FROM']);
			$mail->setSubject(sprintf("%s Job Query [%s]", $GLOBALS['COMPANY'], $subject));
			$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
			$mail->setHTML($emailBody);
			$mail->send(array($email));

			redirect("Location: thanks.php");
		}
	}?>
    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">BLT Direct Job Opportunities</span></div>
<div class="maincontent">
<div class="maincontent1">
<!--              <p class="breadCrumb"><a href="#">Home</a></p>-->
              <p>If you work or have worked within the lighting industry and you think you can bring skills to our company either in product ranges or sales why not drop us an email telling us about you.</p>
              <p>We are also looking for companies and individuals to ship products for us in other european countries.</p>

			  <?php
				if(!$form->Valid){
					echo $form->GetError();
					echo "<br>";
				}
				echo $form->Open();
				echo $form->GetHtml('action');
				echo $form->GetHtml('confirm');
			?>

			  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="bluebox">
                <tr>
                  <td><h3 class="blue">Contact Us Form </h3>
                  <p class="blue">Please complete the fields below. Required fields are marked with an asterisk (*).</p>

				  <table border="0" cellspacing="0" cellpadding="5">
					<tr>
					  <td width="247">Title<?php echo $form->GetIcon('title'); ?><br /><?php echo $form->GetHTML('title'); ?></td>
                      </tr>
                      <tr>
					  <td>First Name<?php echo $form->GetIcon('fname'); ?> <br /><?php echo $form->GetHTML('fname'); ?></td>
                      </tr>
                      <tr>
					  <td>Last Name<?php echo $form->GetIcon('lname'); ?> <br /><?php echo $form->GetHTML('lname'); ?></td>
					</tr>
				  </table>
				  <br />
				  <p>Email Address<?php echo $form->GetIcon('email'); ?><br /><?php echo $form->GetHTML('email'); ?></p>
				  <p>Phone<?php echo $form->GetIcon('phone'); ?><br /><?php echo $form->GetHTML('phone'); ?></p>
				  <p>Your Message to Us<?php echo $form->GetIcon('message'); ?><br /><?php echo $form->GetHTML('message'); ?></p>
				  <p><input name="Send" type="submit" class="submit" id="Send" value="Send" /></p>


				  </td>
                </tr>
              </table>

			  <?php echo $form->Close(); ?>
              <br />

        <h3>Contacting Us Directly  </h3>
              <p>If you prefer to contact us directly by phone, fax or post please find our details below: </p>
              <p>BLT Direct,<br />
                Unit 9, The Quadrangle, <br />
                The Drift, Nacton Road,<br />
        Ipswich, Suffolk IP3 9QR</p>
              <ul>
                <li>Tel <?php echo Setting::GetValue('telephone_sales_hotline'); ?></li>
                <li>Fax 01473 718128 </li>
              </ul>
              <p>&nbsp; </p>
              </div>
              </div>
              <?php include("ui/footer.php")?>
              <?php include('../lib/common/appFooter.php'); ?>