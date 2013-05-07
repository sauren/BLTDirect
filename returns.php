<?php
	require_once('lib/common/appHeader.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/HtmlElement.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Return.php');

	$session->Secure();

	if(param('status') == 'complete') {
		$content = new HtmlElementDiv();
	    $content->AddChildElement(new HtmlElementP('Your return request has been successfully submitted.'));
	} else {
	    $content = new HtmlElementDiv;
		if($session->IsLoggedIn){
	        if(param('components')){
	            # Find out which components were selected for return.
	            $sql = sprintf('SELECT ol.Product_ID, ol.Quantity,
	                            ol.Product_Title AS Product_Title,
	                            p.Product_Title AS Component_Title,
	                            pc.Component_Quantity,
	                            p.Product_ID AS Component_ID
	                            FROM order_line AS ol
	                            INNER JOIN product_components AS pc
	                                ON ol.Product_ID = pc.Component_Of_Product_ID
	                            INNER JOIN product AS p ON p.Product_ID = pc.Product_ID
	                            WHERE ol.Order_Line_ID = %d',
	                            mysql_real_escape_string(id_param('lid')));

	            $line = new DataQuery($sql);    # Get list of all possibly selected components
	            $componentsToReturn = array();
	            $quantityToReturn = array();
	            $components = array();
	            while($line->Row){  # Generate array of possible $_REQUEST names.
	                $componentsInLine[] = 'component_'.$line->Row['Component_ID'];
	                $quantitiesInLine[] = 'quantity_'.$line->Row['Component_ID'];
	                $components[] = $line->Row['Component_ID'];
	                $line->Next();
	            }
	            if(param('components') == 'true'){  #Determine selected components.
	                for($i=0; $i<count($components); $i++){
	                    if(param($componentsInLine[$i])){
	                        $componentsToReturn[$componentsInLine[$i]] = $components[$i];
	                        $quantityToReturn[$quantitiesInLine[$i]] = param($quantitiesInLine[$i]);
	                    }
	                }
	            } else {
	                // work out quantities to return.
	                $sql = sprintf('SELECT ol.Product_ID, ol.Quantity,
	                                ol.Product_Title AS Product_Title,
	                                p.Product_Title AS Component_Title,
	                                pc.Component_Quantity,
	                                p.Product_ID AS Component_ID
	                                FROM order_line AS ol
	                                INNER JOIN product_components AS pc
	                                    ON ol.Product_ID = pc.Component_Of_Product_ID
	                                INNER JOIN product AS p ON p.Product_ID = pc.Product_ID
	                                WHERE ol.Order_Line_ID = %d',
	                                mysql_real_escape_string(id_param('lid')));
	                $line = new DataQuery($sql);
	                while($line->Row){
	                    $componentsToReturn['components_'.$line->Row['Component_ID']] = $line->Row['Component_ID'];
	                    $quantityToReturn['quantity_'.$line->Row['Component_ID']] = (int)$line->Row['Quantity'];
	                    $line->Next();
	                }
	            }
	        }
	        if(strtolower(param('action', '')) == 'finish'){
	            finish($content, $componentsToReturn, $quantityToReturn);
	        } elseif(param('components')){
	            $line->Disconnect();
	            # Add note to return request and then submit for approval.
	            step_3($session, $content, $componentsToReturn, $quantityToReturn);
	        }
	        elseif(id_param('oid') && id_param('lid')) {
	            step_3($session, $content);
	        }
	        else {
	            step_1($session, $content);
	        }

	    } else {
	        $login = new Form($_SERVER['PHP_SELF']);
	        $login->AddField('action', 'Action', 'hidden', 'login', 'alpha', 4, 6);
	        $login->SetValue('action', 'login');
	        $login->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	        $login->AddField('direct', 'Direct', 'hidden', 'returns.php', 'paragraph', 1, 255);
	        $login->AddField('username', 'Username', 'text', '', 'username', 6, 20);
	        $login->AddField('password', 'Password', 'password', '', 'password', 6, 100);

	        if($action == "login"){
	            $login->Validate();
	            if($session->Login($login->GetValue('username'), $login->GetValue('password'))){
	                redirect("Location: " . $login->GetValue('direct'));
	            }else {
	                $login->AddError("Sorry you were unable to login. Please check your username and password and try again.");
	            }
	        }
	        if(!$login->Valid) {
	            $loginError = new HtmlElementDiv($login->GetError());
	            $content->AddChildElement($loginError);
	            $content->AddChildElement(new HtmlElementBr);
	        }
	        $loginTable = new HtmlElementTable();
	        $loginTable->CellPadding = $loginTable->CellSpacing = 0;
	        $loginTable->Style = 'width:100%; background-color:#eee; border:1px solid #ddd';
	        $loginTable->AddRow();
	        $userpassTable = new HtmlElementTable();
	        $userpassTable->Width = '100%';
	        $userpassTable->Border = $userpassTable->CellPadding = 0;
	        $userpassTable->CellSpacing = 20;
	        $userpassTable->AddRow();
	        $userpassCol = new HtmlElementDiv;
	        $userpassColForm = new HtmlElementForm($_SERVER['PHP_SELF'], 'post');
	        $userpassCol->AddChild('div', $login->GetHtml('action') .
	                                      $login->GetHtml('confirm') .
	                                      $login->GetHtml('direct'));
	        $userpassCol->AddChild('h3', 'Login');
	        $userpassCol->AddChild('p', 'Please login to select the applicable order');
	        $userpassCol->AddChild('div', $login->GetLabel('username') .
	                                      $login->GetHtml('username') .
	                                      $login->GetLabel('password') .
	                                      $login->GetHtml('password'));
	        $userpassColForm->AddChildElement($userpassCol);
	        $userpassColForm->AddChildElement(new HtmlElementSubmit('login', 'login', null, 'submit'));
	        $userpassTable->AddColumn($userpassColForm, '', null, null, array('valign' => 'top'));
	        $userpassTable->AddColumn('td', '<p>&nbsp;</p>Or', null, null, array('valign' => 'top'));
	        $forgotpwd = new HtmlElementDiv();
	        $forgotpwd->AddChildElement(new HtmlElement('h3', 'Report Now'));
	        $forgotpwd->AddChildElement(new HtmlElement('p', 'If you have forgotten your password, or would prefer not to login now. Please use the form below. (Please note that preference will be given to return notifications provided via the login on the left.)'));

	        // Setup the adding form
	        $form = new Form($_SERVER['PHP_SELF']);
	        $form->TabIndex = 4;
	        $form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	        $form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
        	$form->AddField('title', 'Title', 'select', '', 'anything', 0, 20, false);
	        $form->AddOption('title', '', '');

	        $title = new DataQuery("select * from person_title order by Person_Title");
	        while($title->Row){
	            $form->AddOption('title', $title->Row['Person_Title'], $title->Row['Person_Title']);
	            $title->Next();
	        }
	        $title->Disconnect();

	        $form->AddField('fname', 'First Name', 'text', '', 'anything', 1, 60, false);
	        $form->AddField('lname', 'Last Name', 'text', '', 'anything', 1, 60, false);
	        $form->AddField('email', 'Your Email Address', 'text', '', 'email', NULL, NULL);
	        $form->AddField('phone', 'Daytime Phone', 'text', '', 'anything', NULL, NULL, false);
	        $form->AddField('order', 'Order Reference', 'text', '', 'anything', 1, 12, true);

	        $form->AddField('subject', 'Subject', 'select', '', 'anything', 1, 11, true, 'style="width:350px;"');
	        $form->AddOption('subject', '', 'Select a Subject');
	        // get options
	        $data = new DataQuery("select * from order_note_type where Is_Public='Y' order by Type_Name asc");
	        while($data->Row){
	            $form->AddOption('subject', $data->Row['Type_Name'], $data->Row['Type_Name']);
	            $data->Next();
	        }
	        $data->Disconnect();

	        $form->AddField('message', 'Note', 'textarea', '', 'paragraph', 1, 2000, true, 'style="width:350px; height:100px"');

	        if(!$form->Valid){
	            $forgotpwd->AddChild('div', $form->GetError());
	            $forgotpwd->AddChildElement(new HtmlElementBr());
	        }
	        $forgotpwdForm = new HtmlElementForm($_SERVER['PHP_SELF'], 'post');
	        $forgotpwdForm->AddChild('div', $form->GetHtml('action') .
	                                        $form->GetHtml('confirm'));
	        $forgotpwdFormTable = new HtmlElementTable();
	        $forgotpwdFormTable->Border = $forgotpwdFormTable->CellSpacing = '0';
	        $forgotpwdFormTable->CellPadding = '5';
	        $forgotpwdFormTable->AddRow();
	        $forgotpwdFormTable->AddColumn('td', sprintf('Title %s<br/>%s',
	                                                     $form->GetIcon('title'),
	                                                     $form->GetHTML('title')));
	        $forgotpwdFormTable->AddColumn('td', sprintf('First Name %s<br/>%s',
	                                                     $form->GetIcon('fname'),
	                                                     $form->GetHTML('fname')));
	        $forgotpwdFormTable->AddColumn('td', sprintf('Last Name %s<br/>%s',
	                                                     $form->GetIcon('lname'),
	                                                     $form->GetHTML('lname')));
	        $forgotpwdForm->AddChildElement($forgotpwdFormTable);
	        $forgotpwdForm->AddChildElement(new HtmlElementP(sprintf('Email Address %s<br/>%s', $form->GetIcon('email'), $form->GetHTML('email'))));
	        $forgotpwdForm->AddChildElement(new HtmlElementP(sprintf('Phone %s<br/>%s', $form->GetIcon('phone'), $form->GetHTML('phone'))));
	        $forgotpwdForm->AddChildElement(new HtmlElementP(sprintf('Order Reference %s<br/>%s', $form->GetIcon('order'), $form->GetHTML('order'))));
	        $forgotpwdForm->AddChildElement(new HtmlElementP(sprintf('Subject %s<br/>%s', $form->GetIcon('subject'), $form->GetHTML('subject'))));
	        $forgotpwdForm->AddChildElement(new HtmlElementP(sprintf('Your Message to Us %s<br/>%s', $form->GetIcon('message'), $form->GetHTML('message'))));
	        $forgotpwdFormPSubmit = new HtmlElementSubmit('Send', 'Send', 'Send', 'submit');
	        $forgotpwdFormP = new HtmlElementP();
	        $forgotpwdFormP->AddChildElement($forgotpwdFormPSubmit);
	        $forgotpwdForm->AddChildElement($forgotpwdFormP);
	        $forgotpwd->AddChildElement($forgotpwdForm);
	        $userpassTable->AddColumn($forgotpwd);
	        $loginTable->AddColumn($userpassTable);
	        $content->AddChildElement($loginTable);

	        // Check if the form was submitted
	        if(strtolower(param('confirm', '')) == "true" && $action != "login"){
	            $form->Validate();
	            if($form->Valid){
	                $subject = $form->GetValue('subject');

	                // Get Template
	                $findReplace = new FindReplace;
	                $findReplace->Add('/\[TITLE\]/', $form->GetValue('title'));
	                $findReplace->Add('/\[FNAME\]/', $form->GetValue('fname'));
	                $findReplace->Add('/\[LNAME\]/', $form->GetValue('lname'));
	                $findReplace->Add('/\[EMAIL\]/', $form->GetValue('email'));
	                $findReplace->Add('/\[PHONE\]/', $form->GetValue('phone'));
	                $findReplace->Add('/\[SUBJECT\]/', $subject);
	                $findReplace->Add('/\[ORDER\]/', $form->GetValue('order'));
	                $findReplace->Add('/\[MESSAGE\]/', $form->GetValue('message'));

	                // Replace Order Template Variables
	                $orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_contact.tpl");
	                $orderHtml = "";
	                for($i=0; $i < count($orderEmail); $i++){
	                    $orderHtml .= $findReplace->Execute($orderEmail[$i]);
	                }

	                unset($findReplace);
	                $findReplace = new FindReplace;
	                $findReplace->Add('/\[BODY\]/', $orderHtml);
	                $findReplace->Add('/\[NAME\]/', $GLOBALS['COMPANY']);
	                // Get Standard Email Template
	                $stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
	                $emailBody = "";
	                for($i=0; $i < count($stdTmplate); $i++){
	                    $emailBody .= $findReplace->Execute($stdTmplate[$i]);
	                }

                    $queue = new EmailQueue();
                    $queue->GetModuleID('returns');
					$queue->Subject = sprintf("%s Contact Us Query [%s]", $GLOBALS['COMPANY'], $subject);
					$queue->Body = $emailBody;
					$queue->ToAddress = 'customerservices@bltdirect.com';
					$queue->Type = 'H';
					$queue->Add();

	                redirect("Location: thanks.php");
	            }
	        }
	    }
	}

    function step_1($session, &$content){
        # Choose which order line you would like to return.
		// Setup the adding form
		$form = new Form($_SERVER['PHP_SELF']);
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('oid', 'Order ID', 'radio', '', 'numeric_unsigned', 1, 11);
		$form->AddField('lid', 'Line ID', 'radio', '', 'numeric_unsigned', 1, 11);

		$p = 'If you have received damaged, faulty, incorrect goods or have ordered incorrectly you can select the products you wish to return from the order history below.';
        $content->AddChildElement(new HtmlElementP($p));
        $p = 'Upon authorisation of your request you will be supplied with a RAN (Returns Authorisation Number).';
        $content->AddChildElement(new HtmlElementP($p));
        $p = 'For faulty or damaged goods or goods that have been supplied incorrectly we will email a FREEPOST label across to you for the return of the goods. You may take the parcel to any local post office for this service.';
        $content->AddChildElement(new HtmlElementP($p));
        $p = 'PLEASE NOTE FREEPOST SERVICE is not a recorded/signed for service. For high value items you may wish to return using a recorded service. BLT Direct would not be liable for these costs.';
        $content->AddChildElement(new HtmlElementP($p));
        $p = 'For larger or heavier goods, we may need to use a different mode of transport and you will be informed of this by email.';
        $content->AddChildElement(new HtmlElementP($p));
        $p = 'For goods that have been ordered incorrectly the customer will be responsible for returning the goods and the cost of this return.';
        $content->AddChildElement(new HtmlElementP($p));
        $p = 'On completing the steps below you will receive an email from our customer service team with an RAN (Returns Authorisation Number).';
        $content->AddChildElement(new HtmlElementP($p));

        $ol = new HtmlElementList('ol');
        $attr['style'] = 'font-weight:bold;';
        $ol->AddItem('Click on the circle next to the relevant order.', 'step_1_1', null, $attr);
        $ol->AddItem('Select the products from the order.)', 'step_1_2', null);
        $ol->AddItem('Select the quantity you wish to return.', 'step_1_3', null);
        $ol->AddItem('Click the proceed button to continue.', 'step_1_4', null);

        $returnInstructions = new HtmlElement('b');
        $returnInstructions->AddChildElement(new HtmlElementSpan("Return Instructions"));

        $div = new HtmlElementDiv();
        $div->AddChildElement($returnInstructions);
        $div->AddChildElement($ol);
		$div->AddChildElement(new HtmlElementSpan("&nbsp;"));

        $content->AddChildElement($div);
        $step1Form = new HtmlElementForm($_SERVER['PHP_SELF'], 'post');
        $table = new HtmlElementTable(null, null,
                                      array('style' => 'background-color:#eee; border:1px solid #ddd;',
                                            'width' => '100%',
                                            'cellpadding' => '0',
                                            'cellspacing' => '0'));
        $table->AddRow();
        $orderHeader = new HtmlElementTable(null, 'myAccountOrderHistory', array('cellspacing' => '0'));
        $orderHeader->AddRow();
        $orderHeader->AddColumn('th', '&nbsp;');
        $orderHeader->AddColumn('th', 'Order Date');
        $orderHeader->AddColumn('th', 'Order Number');
        $orderHeader->AddColumn('th', 'Order Total', null, null, array('style' => 'text-align: right;'));

        $sql = sprintf("SELECT o.* FROM orders AS o
                        INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID
                        INNER JOIN product AS p ON p.Product_ID=ol.Product_ID
                        WHERE o.Customer_ID=%d AND ol.Invoice_ID<>0 AND p.Is_Non_Returnable='N'
                        GROUP BY Order_ID
                        ORDER BY o.Ordered_On DESC",
                        mysql_real_escape_string($session->Customer->ID));
        $data = new DataQuery($sql);
        while($data->Row){
            $isChecked = $form->GetValue('oid');
            $checked = ($isChecked == $data->Row['Order_ID'])? array('checked' => true):null;
            $orderHeader->AddRow();
            $orderHeader->AddColumn(new HtmlElementRadio( 'oid',
                                                   $data->Row['Order_ID'],
                                                   $checked,
                                                   null, null,
                                                   array('onclick' => "returns_get_lines({$data->Row['Order_ID']})")));
            $orderHeader->AddColumn('td', cDatetime($data->Row['Ordered_On'], 'shortdate'));
            $orderHeader->AddColumn('td',
                                  $data->Row['Order_Prefix'].
                                  $data->Row['Order_ID']);
            $orderHeader->AddColumn('td', '&pound;'.number_format($data->Row['Total'], 2, '.', ','), null, null, array('align'=>'right'));
            $data->Next();
        }
        $data->Disconnect();

        $table->AddColumn($orderHeader, null, null, null, array('valign' => 'top', 'width' => '50%'));

        $lines = new HtmlElementDiv('lines');
        $table->AddColumn($lines, null, null, null, array('valign' => 'top', 'rowspan' => '2'));


        $step1Form->AddChildElement($table);

        $submitContainer = new HtmlElementDiv('goto_2');
        $submitContainer->Style = 'visibility:hidden';

        $submitFormWrapper = new HtmlElementDiv();
        $submitFormWrapper->SetContent($form->GetHTML('action'));
        $submitFormWrapper->AppendContent($form->GetHTML('confirm'));
        $submitContainer->AddChildElement($submitFormWrapper);
        /*$submit = new HtmlElementSubmit('proceed', 'Proceed');
        $submit->Class = 'submit';
        $submit->TabIndex = $form->GetTabIndex();
        $submitContainer->AddChildElement($submit);*/
        $step1Form->AddChildElement($submitContainer);
        $content->AddChildElement($step1Form);
    }

    // step_2() does not exists.

    function step_3($session, &$content, $componentsToReturn=null, $quantityToReturn=null){
        require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ReturnReason.php');

        # Additional information about the return, then finish.
        $form = new Form($_SERVER['PHP_SELF']);
        $form->AddField('note', 'note', 'textarea', '', 'paragraph', 0, 255, true, 'style="width:300px; height:100px;"');
        $form->AddField('reason', 'reason', 'select', '', 'paragraph', 0, 255);
        $form->AddField('quantity', 'quantity', 'hidden', param('quantity_'.id_param('lid')), 'numeric_unsigned', 1, 9, true, 'style="width:300px;"');
        $returnReason = new ReturnReason;
        $returnReason->GetReasons('Reason_Title');
        foreach($returnReason->Collection as $c){
            $form->AddOption('reason', $c->ID, $c->Title);
        }
        unset($returnReason);
        $additionalInfoHidden = array();
        if(!is_null($componentsToReturn)){
            foreach($componentsToReturn as $k => $v){
                $form->AddField($k, $k, 'hidden', $v, 'unsigned');
                $additionalInfoHidden[] = new HtmlElementHidden($k, $v, $k);
            }
            foreach($quantityToReturn as $k => $v){
                $form->AddField($k, $k, 'hidden', $v, 'unsigned');
                $additionalInfoHidden[] = new HtmlElementHidden($k, $v, $k);
            }
        }
        $additionalInfoHidden[] = new HtmlElementHidden('oid', id_param('oid'), 'oid');
        $additionalInfoHidden[] = new HtmlElementHidden('lid', id_param('lid'), 'lid');
        $additionalInfoHidden[] = new HtmlElementHidden('components', param('components'));

        $divHighlight = new HtmlElementDiv(null, null, null, false);
        $divHighlight->SetContent('<p>Please give the extact reason for your return. If you have ordered incorrectly, please enter in the return information box any relevant information you may have on the product you require and a contact telephone number our sales team may contact you on.</p>');
        $content->AddChildElement($divHighlight);

        $divTable = new HtmlElementTable(null, null, array('style' => 'width: 100%;'), false);
        $divTable->AddRow('row1', null, null);

        //$additionalInfoPrompt = new HtmlElementP('Please give the extact reason for your return. If you have received the wrong product please state the product you require as a replacement.');
        //$content->AddChildElement($additionalInfoPrompt);
        $additionalInfoForm = new HtmlElementForm($_SERVER['PHP_SELF'], 'post');
        $additionalInfoContent = new HtmlElementDiv(null, null, null, false);
        $additionalInfoContent->SetContent('<strong>Return Reason</strong><br />' . $form->GetHTML('reason'));
        $additionalInfoContent->AppendContent('<br/>');
        $additionalInfoContent->AppendContent('<br/>');
        $additionalInfoContent->AppendContent('<strong>Return Information</strong><br />' . $form->GetHTML('note'));
        $additionalInfoContent->AppendContent($form->GetHTML('quantity'));
        $additionalInfoContent->AppendContent('<br/>');
        $additionalInfoContent->AppendContent('<br/>');
        $additionalInfoSubmit = new HtmlElementSubmit('action', 'Finish', null, 'submit');
        foreach($additionalInfoHidden as $a)
            $additionalInfoForm->AddChildElement($a);
        $additionalInfoForm->AddChildElement($additionalInfoContent);
        $additionalInfoForm->AddChildElement($additionalInfoSubmit);

        $divTable->AddColumn('td', $additionalInfoForm->ToString(), null, null, array('style' => 'width: 50%; vertical-align: top;'));

        $subContent = new HtmlElementDiv();

        $divHighlight = new HtmlElementDiv(null, null, null, false);
        $divHighlight->SetContent('<p>For items that have been ordered incorrectly the customer is responsible for returning the goods and the return costs.</p>');
       	$subContent->AddChildElement($divHighlight);

        $divHighlight = new HtmlElementDiv(null, null, null, false);
        $divHighlight->SetContent('<p><strong>Please return to the following address:</strong></p>');
		$subContent->AddChildElement($divHighlight);

		$divHighlight = new HtmlElementDiv(null, null, null, false);
        $divHighlight->SetContent('<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td valign="top" style="width: 50%;">BLT Direct<br />Returns Department<br />Unit 9<br />The Quadrangle<br />The Drift<br />Nacton Road<br />Ipswich<br />Suffolk<br />IP3 9QR</td><td valign="top" style="width: 50%;">Return # (RNA):<br /><em>Your Number</em></td></tr></table>');
		$subContent->AddChildElement($divHighlight);

        $divTable->AddColumn('td', $subContent->ToString(), null, null, array('style' => 'width: 50%; vertical-align: top;'));

        $content->AddChildElement($divTable);

        $ending = new HtmlElementP('<br />'.$GLOBALS['COMPANY'].' prides itself on giving excellent customer service and will endeavor to reply to all emails within 8 business hours.');
        $content->AddChildElement($ending);
    }

    function finish(&$content, $componentsToReturn, $quantityToReturn){
        global $session;
        $form = new Form($_SEVER['PHP_SELF']);
        $form->AddField('lid', 'lid', 'hidden', id_param('lid'), 'numeric_unsigned', 1, 12);
        $form->AddField('oid', 'oid', 'hidden', id_param('oid'), 'numeric_unsigned', 1, 12);
        $form->AddField('note', 'note', 'hidden', param('note'), 'paragraph', 1, 255, false);
        $form->AddField('reason', 'reason', 'hidden', param('reason'), 'numeric_unsigned', 1, 11);
        $form->AddField('quantity', 'quantity', 'hidden', id_param('quantity'), 'numeric_unsigned', 1, 9);
        /*foreach($componentsToReturn as $k => $v){
            $form->AddField($k, $k, 'hidden', $v, 'unsigned');
        }
        foreach($quantityToReturn as $k => $v){
            $form->AddField($k, $k, 'hidden', $v, 'unsigned');
        }*/
        if($form->Validate()){
            $line = new OrderLine($form->GetValue('lid'));
            $return  = new ProductReturn();
            $return->OrderLine->ID = $form->GetValue('lid');
            $return->Invoice->ID = $line->InvoiceID;
            $return->Customer->ID = $session->Customer->ID;
            $return->Reason->ID = $form->GetValue('reason');
            $return->Note = $form->GetValue('note');
            $return->Quantity = $form->GetValue('quantity');
            $return->RequestedOn = now();
            $return->Add();

            redirect(sprintf("Location: %s?status=complete", $_SERVER['PHP_SELF']));
        }
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Returns</title>
	<!-- InstanceEndEditable -->

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs_print.css" media="print" />
	<link rel="stylesheet" type="text/css" href="css/Navigation.css" />
	<link rel="stylesheet" type="text/css" href="css/Menu.css" />
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		?>
		<link rel="stylesheet" type="text/css" href="css/Trade.css" />
        <?php
	}
	?>
	<link rel="shortcut icon" href="favicon.ico" />
<!--    <script type='text/javascript' src='http://api.handsetdetection.com/sites/js/43071.js'></script>-->
	<script language="javascript" type="text/javascript" src="js/generic.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance_api.js"></script>
	<script language="javascript" type="text/javascript" src="js/mootools.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance.js"></script>
	<script language="javascript" type="text/javascript" src="js/bltdirect.js"></script>
    <script language="javascript" type='text/javascript' src="js/api.js"></script>
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script language="javascript" type="text/javascript" src="js/bltdirect/template.js"></script>
        <?php
	}
	?>
    
	<script language="javascript" type="text/javascript">
	//<![CDATA[
		<?php
		for($i=0; $i<count($GLOBALS['Cache']['Categories']); $i=$i+2) {
			echo sprintf("menu1.add('navProducts%d', 'navProducts', '%s', '%s', null, 'subMenu');", $i, $GLOBALS['Cache']['Categories'][$i], $GLOBALS['Cache']['Categories'][$i+1]);
		}
		?>
	//]]>
	</script>	
	<!-- InstanceBeginEditable name="head" -->
	<script src="ignition/js/HttpRequest.js" type="text/javascript"></script>
	<script src="ignition/js/returns.js" type="text/javascript"></script>
<!-- InstanceEndEditable -->
</head>
<body>

    <div id="Wrapper">
        <div id="Header">
            <div id="HeaderInner">
                <?php require('lib/templates/header.php'); ?>
            </div>
        </div>
        <div id="PageWrapper">
            <div id="Page">
                <div id="PageContent">
                    <?php
                    if(strtolower(Setting::GetValue('site_message_active')) == 'true') {
                        ?>

                        <div id="SiteMessage">
                            <div id="SiteMessageLeft">
                                <div id="SiteMessageRight">
                                    <marquee scrollamount="4"><?php echo Setting::GetValue('site_message_value'); ?></marquee>
                                </div>
                            </div>
                        </div>

                        <?php
                    }
                    ?>
                    
                    <a name="top"></a>
                    
                    <!-- InstanceBeginEditable name="pageContent" -->
              <h1>Returns</h1>
              <div id="orderConfirmation">
				<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returns.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
				</div>

				<?php
				echo $content->ToString();

				if(param('status') == 'complete') {
					?>

					<p>An email will follow shortly advising you of the procedure for returning your goods.</p>

					<?php
				}
				?>

			  <!-- else login -->
			  <!-- InstanceEndEditable -->
                </div>
            </div>
            <div id="PageFooter">
                <ul class="links">
                    <li><a href="./terms.php" title="BLT Direct Terms and Conditions of Use and Sale">Terms and Conditions</a></li>
                    <li><a href="./privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
                    <li><a href="./company.php" title="About BLT Direct">About Us</a></li>
                    <li><a href="./sitemap.php" title="Map of Site Contents">Site Map</a></li>
                    <li><a href="./support.php" title="Contact BLT Direct">Contact Us</a></li>
                    <li><a href="./index.php" title="Light Bulbs">Light Bulbs</a></li>
                    <li><a href="./products.php?cat=1251&amp;nm=Christmas+Lights" title="Christmas Lights">Christmas Lights</a></li> 
                    <li><a href="./Projector-Lamps.php" title="Projector Lamps">Projector Lamps</a></li>
                    <li><a href="./articles.php" title="Press Releases/Articles">Press Releases/Articles</a></li>
                </ul>
                
                <p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
            </div>
        </div>
        <div id="LeftNav">
            <?php require('lib/templates/left.php'); ?>
        </div>
        <div id="RightNav">
            <?php require('lib/templates/right.php'); ?>
        
            <div id="Azexis">
                <a href="http://www.azexis.com" target="_blank" title="Web Designers">Web Designers</a>
            </div>
        </div>
    </div>
	<script src="<?php print ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT']) ? 'http://www' : 'https://ssl'; ?>.google-analytics.com/urchin.js" type="text/javascript"></script>
	<script type="text/javascript">
	//<![CDATA[
		_uacct = "UA-1618935-2";
		urchinTracker();
	//]]>
	</script>

	<!-- InstanceBeginEditable name="Tracking Script" -->

<!--
<script>
var parm,data,rf,sr,htprot='http'; if(self.location.protocol=='https:')htprot='https';
rf=document.referrer;sr=document.location.search;
if(top.document.location==document.referrer||(document.referrer == '' && top.document.location != '')) {rf=top.document.referrer;sr=top.document.location.search;}
data='cid=256336&rf=' + escape(rf) + '&sr=' + escape(sr); parm=' border="0" hspace="0" vspace="0" width="1" height="1" '; document.write('<img '+parm+' src="'+htprot+'://stats1.saletrack.co.uk/scripts/stinit.asp?'+data+'">');
</script>
<noscript>
<img src="http://stats1.saletrack.co.uk/scripts/stinit.asp?cid=256336&rf=JavaScri
pt%20Disabled%20Browser" border="0" width="0" height="0" />
</noscript>
-->

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>
<?php include('lib/common/appFooter.php'); ?>
