<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/EmailQueue.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Order.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/OrderLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/SupplierReturnRequest.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ReturnLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ReturnLineDespatch.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/ReturnReason.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Customer.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FindReplace.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/CustomerContact.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Despatch.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReturnPDF.php');

class ProductReturn {
	var $ID;
	var $Order;
	var $Despatch;
	var $OrderLine;
	var $SupplierReturnRequest;
	var $Line;
	var $DespatchLine;
	var $Customer;
	var $Quantity;
	var $Reason;
	var $Note;
	var $AdminNote;
	var $EmailedOn;
	var $RequestedOn;
	var $ReadOn;
	var $ReadBy;
	var $ReceivedOn;
	var $AuthorisedOn;
	var $AuthorisedBy;
	var $Status;
	var $TotalLines;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $IsArchived;
	var $IsRefunding;
	var $Authorisation;

	function ProductReturn($id=NULL){
		$this->Authorisation = 'N';
		$this->Line = array();
		$this->DespatchLine = array();
		$this->Order = new Order();
		$this->Despatch = new Despatch();
		$this->SupplierReturnRequest = new SupplierReturnRequest();
		$this->OrderLine = new OrderLine();
		$this->Customer = new Customer();
		$this->Reason = new ReturnReason();
		$this->RequestedOn = '0000-00-00 00:00:00';
		$this->EmailedOn = '0000-00-00 00:00:00';
		$this->ReadOn = '0000-00-00 00:00:00';
		$this->ReadBy = 0;
		$this->AdminNote = '';
		$this->ReceivedOn = '0000-00-00 00:00:00';
		$this->AuthorisedOn = '0000-00-00 00:00:00';
		$this->AuthorisedBy = 0;
		$this->DespatchedOn = '0000-00-00 00:00:00';
		$this->Status = 'Unread';
		$this->IsArchived = 'N';
		$this->IsRefunding = 'N';

		if(!is_null($id)){
			$this->ID=$id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$sql = "SELECT r.*, ol.Order_ID FROM `return` r
                INNER JOIN order_line ol
                ON ol.Order_Line_ID = r.Order_Line_ID
                WHERE Return_ID = {$this->ID}";
		$data = new DataQuery($sql);
		if($data->TotalRows < 1) return false;
		$this->OrderLine->ID = $data->Row['Order_Line_ID'];
		$this->Order->ID = $data->Row['Order_ID'];
		$this->Despatch->ID = $data->Row['Despatch_ID'];
		$this->SupplierReturnRequest->ID = $data->Row['Supplier_Return_Request_ID'];
		$this->Customer->ID = $data->Row['Customer_ID'];
		$this->Reason->ID = $data->Row['Reason_ID'];
		$this->Quantity = $data->Row['Quantity'];
		$this->Note = $data->Row['Note'];
		$this->AdminNote = $data->Row['Admin_Note'];
		$this->Status = $data->Row['Status'];
		$this->IsArchived = $data->Row['Is_Archived'];
		$this->IsRefunding = $data->Row['Is_Refunding'];
		$this->Authorisation = $data->Row['Authorisation'];
		$this->RequestedOn = $data->Row['Requested_On'];
		$this->EmailedOn = $data->Row['Emailed_On'];
		$this->ReadOn = $data->Row['Read_On'];
		$this->ReadBy = $data->Row['Read_By'];
		$this->ReceivedOn = $data->Row['Received_On'];
		$this->CreatedOn = $data->Row['Created_On'];
		$this->CreatedBy = $data->Row['Created_By'];
		$this->ModifiedOn = $data->Row['Modified_On'];
		$this->ModifiedBy = $data->Row['Modified_By'];

		$data->Disconnect();
	}

	function GetVia($col, $val){
		$data = new DataQuery(sprintf("SELECT Return_ID FROM `return` WHERE %s=%s", mysql_real_escape_string($col), mysql_real_escape_string($val)));
		if($data->TotalRows > 0){
			$this->Get($data->Row['Return_ID']);

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$sql = sprintf("INSERT INTO `return`
                       (Despatch_ID, Order_Line_ID, Supplier_Return_Request_ID, Customer_ID, Reason_ID,
                       Quantity, Note, Admin_Note, Status, Is_Archived, Is_Refunding, Authorisation, Emailed_On, Requested_On,
                       Read_On, Read_By, Authorised_On, Authorised_By, Received_On,
                       Created_On, Created_By, Modified_On, Modified_By)
                       VALUES
                       (%d, %d, %d, %d, %d,
                       %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', NOW(), '%s',
                       %d, '%s', %d, '%s', NOW(),
                       %d, NOW(), %d)",
		mysql_real_escape_string($this->Despatch->ID),
		mysql_real_escape_string($this->OrderLine->ID),
		mysql_real_escape_string($this->SupplierReturnRequest->ID),
		mysql_real_escape_string($this->Customer->ID),
		mysql_real_escape_string($this->Reason->ID),
		mysql_real_escape_string($this->Quantity),
		mysql_real_escape_string(stripslashes($this->Note)),
		mysql_real_escape_string(stripslashes($this->AdminNote)),
		mysql_real_escape_string($this->Status),
		mysql_real_escape_string($this->IsArchived),
		mysql_real_escape_string($this->IsRefunding),
		mysql_real_escape_string($this->Authorisation),
		mysql_real_escape_string($this->EmailedOn),
		mysql_real_escape_string($this->ReadOn),
		mysql_real_escape_string($this->ReadBy),
		mysql_real_escape_string($this->AuthorisedOn),
		mysql_real_escape_string($this->AuthorisedBy),
		mysql_real_escape_string($this->ReceivedOn),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
		$data = new DataQuery($sql);
		$this->ID = $data->InsertID;
	}

	function Update($id=null){
		if(!is_null($id)) $this->ID = $id;
		$sql = sprintf("UPDATE `return`
                        SET Order_Line_ID={$this->OrderLine->ID},
                        Despatch_ID={$this->Despatch->ID},
                        Supplier_Return_Request_ID={$this->SupplierReturnRequest->ID},
                        Customer_ID={$this->Customer->ID},
                        Reason_ID={$this->Reason->ID},
                        Quantity='{$this->Quantity}',
                        Note='%s',
                        Admin_Note='%s',
                        Status='{$this->Status}',
                       	Is_Archived='{$this->IsArchived}',
                       	Is_Refunding='{$this->IsRefunding}',
                       	Authorisation='{$this->Authorisation}',
                        Emailed_On='{$this->EmailedOn}',
                        Requested_On='{$this->RequestedOn}',
                        Read_On='{$this->ReadOn}',
                        Read_By={$this->ReadBy},
                        Authorised_On='{$this->AuthorisedOn}',
                        Authorised_by={$this->AuthorisedBy},
                        Received_On='{$this->ReceivedOn}',
                        Created_On='{$this->CreatedOn}',
                        Created_By={$this->CreatedBy},
                        Modified_On=NOW(),
                        Modified_By=%d
                        WHERE Return_ID={$this->ID}",
		mysql_real_escape_string(stripslashes($this->Note)),
		mysql_real_escape_string(stripslashes($this->AdminNote)),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
		$data = new DataQuery($sql);
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$this->GetLines();
		$this->GetDespatchLines();

		for($i=0; $i<count($this->Line); $i++){
			$this->Line[$i]->Delete();
		}

		for($i=0; $i<count($this->DespatchLine); $i++){
			$this->DespatchLine[$i]->Delete();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM `return` WHERE Return_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function GetLines(){
		$this->Line = array();
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT rl.Return_Line_ID FROM return_line AS rl INNER JOIN product as p ON rl.Product_ID=p.Product_ID WHERE rl.Return_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$this->Line[] = new ProductReturnLine($data->Row['Return_Line_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function GetDespatchLines(){
		$this->DespatchLine = array();
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT rl.Return_Line_Despatch_ID FROM return_line_despatch AS rl INNER JOIN product as p ON rl.Product_ID=p.Product_ID WHERE rl.Return_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$this->DespatchLine[] = new ProductReturnLineDespatch($data->Row['Return_Line_Despatch_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function FindLineByProductId($id, &$array){
		foreach($this->Line as $l){
			if(empty($l->Product->ID))
			$l->Get();
			if($l->Product->ID == $id){
				if(!is_array($array))
				return $l;
				else
				$array[] = $l;
			}
		}
		if(!empty($array))
		return true;
		else
		return false;
	}

	function SendEmail($msg=null){
		if(is_null($msg)) $msg = strtolower($this->Status);
		$subject = '';
		
		$returnPath = explode('@', $GLOBALS['EMAIL_RETURN']);
		$returnPath = (count($returnPath) == 2) ? sprintf('%s.return.%d@%s', $returnPath[0], $this->ID, $returnPath[1]) : $GLOBALS['EMAIL_RETURN'];

        $queue = new EmailQueue();
        $queue->GetModuleID('returns');
        $queue->ReturnPath = $returnPath;
        
		switch($msg){
			case 'authorised':
				$this->Reason->Get();

				$subject = 'Return Confirmation';
				$findReplace = new FindReplace;
				$order = new Order($this->OrderLine->Order);
				$findReplace->Add('/\[RETURN_REF\]/', $this->ID);
				$findReplace->Add('/\[CUSTOM_REF\]/', $order->CustomID);
				$findReplace->Add('/\[REQUEST_DATE\]/',
				cDatetime($this->RequestedOn, 'longdate'));
				$findReplace->Add('/\[CUSTOMER_NAME\]/',
				$this->Customer->Contact->Person->GetFullName());
				$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
				$findReplace->Add('/\[ORDER_WEIGHT\]/',$this->OrderLine->Product->Weight);

				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/" . ((strtolower($this->Reason->Title) != 'ordered incorrectly') ? 'email_return_authorise_return_template.tpl' : 'email_return_authorise_template.tpl'));
				$orderTxt = $orderHtml = "";

				for($i=0; $i < count($orderEmail); $i++){
					$orderHtml .= $findReplace->Execute($orderEmail[$i]);
				}
                $requested = cDatetime($this->RequestedOn, 'longdate');

                if(strtolower($this->Reason->Title) != 'ordered incorrectly') {
					$orderTxt .= "We are pleased to inform you that your return request has been successfully processed.\n\n";
					$orderTxt .= "Carefully package your goods and return them to us using the freepost label attached. Upon receipt of the returned goods we will despatch replacement goods within 3 working days unless notified otherwise.\n\n";
					$orderTxt .= "Please note, freepost service is not a recorded/signed for service. For high value items you may wish to return using a recorded service. BLT Direct would not be liable for these costs.\n\n";
                } else {
					$orderTxt .= "We are pleased to inform you that your return has been authorised.\n\n";
					$orderTxt .= "Please package your goods carefully and return them to the address below.\n\n";
					$orderTxt .= "For valuable items you may wish to send back by a recorded service. Please note this is not a free service.\n\n";

					$orderTxt .= "Return # (RNA): Your Number\nBLT Direct\nReturns Department\nUnit 9\nThe Quadrangle\nThe Drift\nNacton Road\nIpswich\nSuffolk\nIP3 9QR\n\n";
                }

                $orderTxt .= "Return Ref:        {$this->ID}\n";
                $orderTxt .= "Request Date:      {$requested}\n";
				$fullname = $this->Customer->Contact->Person->GetFullName();
                $orderTxt .= "Customer:          $fullname\n";
                $orderTxt .= "Customer ID:       {$this->Customer->Contact->ID}\n";

				break;
			case 'replacing':
				// Get Order Template
				$subject = 'Return Confirmation';
				$findReplace = new FindReplace;
				$order = new Order($this->OrderLine->Order);
				$findReplace->Add('/\[RETURN_REF\]/', $this->ID);
				$findReplace->Add('/\[REQUEST_DATE\]/',
				                    cDatetime($this->RequestedOn, 'longdate'));
				$findReplace->Add('/\[CUSTOMER_NAME\]/',
				                    $this->Customer->Contact->Person->GetFullName());
				$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);

				// Replace Order Template Variables
				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_return_authorise_despatch_template.tpl");
				$orderTxt = $orderHtml = "";
				for($i=0; $i < count($orderEmail); $i++){
					$orderHtml .= $findReplace->Execute($orderEmail[$i]);
				}

                // A plaintext version of the email should be provided.
                $orderTxt .= "We are pleased to inform you that your return ".
                             "request was successfully received.\n\n";
                $orderTxt .= "On this occasion we do not require you to ".
                             "return the goods to us. Please dispose of them ".
                             "safely. Replacements will be despatched within 3 working days unless notified otherwise.\n\n";
				$orderTxt .= "An email will follow advising your of your new order reference.\n\n";
                $orderTxt .= "Return Ref:        {$this->ID}\n";
                $orderTxt .= "Request Date:      {$requested}\n";
				$fullname = $this->Customer->Contact->Person->GetFullName();
                $orderTxt .= "Customer:          $fullname\n";
                $orderTxt .= "Customer ID:       {$this->Customer->Contact->ID}\n";

				unset($findReplace);
				break;
			case 'received':
				$subject = 'Return Received';
				// Get Order Template
				$findReplace = new FindReplace;
				$order = new Order($this->OrderLine->Order);
				$findReplace->Add('/\[RETURN_REF\]/', $this->ID);
				$findReplace->Add('/\[REQUEST_DATE\]/',
				cDatetime($this->RequestedOn, 'longdate'));
				$findReplace->Add('/\[RETURNED_ON\]/',
				cDatetime($this->ReceivedOn, 'longdate'));
				$findReplace->Add('/\[CUSTOMER_NAME\]/',
				$this->Customer->Contact->Person->GetFullName());
				$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
				// Replace Order Template Variables
				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_return_received_template.tpl");
				$orderTxt = $orderHtml = "";
				for($i=0; $i < count($orderEmail); $i++){
					$orderHtml .= $findReplace->Execute($orderEmail[$i]);
				}

                // A plaintext version of the email should be provided.
                $requested = cDatetime($this->RequestedOn, 'longdate');
                $received = cDatetime($this->ReceivedOn, 'longdate');
                $orderTxt .= "We received your return on $received and will ".
                             "be processing it shortly. You shall receive ".
                             "another email when we have completed this ".
                             "process.\n\n";
                $orderTxt .= "Return Ref:        {$this->ID}\n";
                $orderTxt .= "Request Date:      {$requested}\n";
                $orderTxt .= "Return Date:       {$received}\n";
				$fullname = $this->Customer->Contact->Person->GetFullName();
                $orderTxt .= "Customer:          $fullname\n";
                $orderTxt .= "Customer ID:       {$this->Customer->Contact->ID}\n";

				unset($findReplace);
				break;
			case 'resolved':
				$subject = 'Return Resolved';
				// Get Order Template
				$findReplace = new FindReplace;
				$this->GetLines();
				$resolve_details = '';
				$resolve_details .= "<ul>\n";
				foreach($this->Line as $l){
					$s = '<strong>' . strtolower($l->Status) . '</strong>';
					$resolve_details .= "\t<li>{$l->Product->Name} is being $s</li>\n";
				}
				$resolve_details .= "</ul>\n";
				$findReplace->Add('/\[RESOLVE_DETAILS\]/', $resolve_details);
				$findReplace->Add('/\[RETURN_REF\]/', $this->ID);
				$findReplace->Add('/\[REQUEST_DATE\]/',
				cDatetime($this->RequestedOn, 'longdate'));
				$findReplace->Add('/\[RETURNED_ON\]/',
				cDatetime($this->ReceivedOn, 'longdate'));
				$findReplace->Add('/\[CUSTOMER_NAME\]/',
				$this->Customer->Contact->Person->GetFullName());
				$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
				// Replace Order Template Variables
				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_return_resolved_template.tpl");
				$orderTxt = $orderHtml = "";
				for($i=0; $i < count($orderEmail); $i++){
					$orderHtml .= $findReplace->Execute($orderEmail[$i]);
				}

                // A plaintext version of the email should be provided.
                $requested = cDatetime($this->RequestedOn, 'longdate');
                $orderTxt .= "We have resolved your return in the following way:\n\n";
                $resolve_details = '';
                foreach($this->Line as $l){
                    $s = strtolower($l->Status);
                    $resolve_details .= "{$l->Product->Name} is being $s\n";
                }
                $orderTxt .= "$resolve_details\n";
                $orderTxt .= "Goods that are marked as being returned or " .
                             "replaced have been despatched ".
                             "and are on their way to you. You should receive ".
                             "a separate email shortly containing your ".
                             "consignment details.\n";
                $orderTxt .= "If you receive goods in error please contact ".
                             "us via Order Note, by logging into your account ".
                             "at https://www.bltdirect.com/accountcenter.php\n\n";

                $orderTxt .= "Return Ref:        {$this->ID}\n";
                $orderTxt .= "Request Date:      {$requested}\n";
				$fullname = $this->Customer->Contact->Person->GetFullName();
                $orderTxt .= "Customer:          $fullname\n";
                $orderTxt .= "Customer ID:       {$this->Customer->Contact->ID}\n";

				unset($findReplace);
				break;
			case 'refused':
				$subject = 'Return Refused';
				// Get Order Template
				$findReplace = new FindReplace;
				$findReplace->Add('/\[REFUSE_NOTE\]/', $this->AdminNote);
				$findReplace->Add('/\[RETURN_REF\]/', $this->ID);
				$findReplace->Add('/\[REQUEST_DATE\]/',
				                  cDatetime($this->RequestedOn, 'longdate'));
				$findReplace->Add('/\[CUSTOMER_NAME\]/',
				                  $this->Customer->Contact->Person->GetFullName());
				$findReplace->Add('/\[CUSTOMER_ID\]/', $this->Customer->Contact->ID);
				// Replace Order Template Variables
				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_return_refused_template.tpl");
				$orderTxt = $orderHtml = "";
				for($i=0; $i < count($orderEmail); $i++){
					$orderHtml .= $findReplace->Execute($orderEmail[$i]);
				}

                // A plaintext version of the email should be provided.
                $requested = cDatetime($this->RequestedOn, 'longdate');
                $orderTxt .= "We regret to inform you that we cannot accept ".
                             "your request to return goods for the following ".
                             "reason:\n\n";
                $orderTxt .= "{$this->AdminNote}\n\n";

                $orderTxt .= "Return Ref:        {$this->ID}\n";
                $orderTxt .= "Request Date:      {$requested}\n";
				$fullname = $this->Customer->Contact->Person->GetFullName();
                $orderTxt .= "Customer:          $fullname\n";
                $orderTxt .= "Customer ID:       {$this->Customer->Contact->ID}\n";

				unset($findReplace);
				break;
			default:
				return false;
		}

		if(count($this->Line) <= 0) $this->GetLines();
		$this->TotalNet = $this->SubTotal
		+ $this->TotalShipping
		- $this->TotalDiscount;

		if(empty($this->Customer->Contact->ID))$this->Customer->Get();
		if(empty($this->Customer->Contact->Person->ID)) $this->Customer->Contact->Get();

		$findReplace = new FindReplace;
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', $this->Customer->Contact->Person->GetFullName());
		// Get Standard Email Template
		$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		$emailTxt = $emailBody = "";
		for($i=0; $i < count($stdTmplate); $i++){
			$emailBody .= $findReplace->Execute($stdTmplate[$i]);
		}

		$fullname = $this->Customer->Contact->Person->GetFullName();
        $emailTxt .= "Dear $fullname,\n\n";
        $emailTxt .= "$orderTxt\n";
        $emailTxt .= "Regards,\n\n";
        $emailTxt .= "BLT Direct\n\n";

		$queue->Subject = sprintf("%s %s [#%s%s]", $GLOBALS['COMPANY'], $subject, $this->Prefix, $this->ID);
		$queue->Body = $emailBody;
		$queue->ToAddress = $this->Customer->GetEmail();
		$queue->Add();

		switch($msg){
			case 'authorised':
				$this->Reason->Get();

				$pdf = new ReturnPDF();
				$pdf->SetFont('Helvetica', '', 14);
				$pdf->SetLeftMargin(15);
				$pdf->SetTopMargin(30);
				$pdf->Freepost = (strtolower($this->Reason->Title) != 'ordered incorrectly');
				$pdf->AddPage();
				$pdf->WriteHTML(sprintf('<p>Goods Return: #%d</p>', $this->ID));
				$pdf->Output(sprintf('%sreturn_label_%s.pdf', $GLOBALS['RETURN_DOCUMENT_DIR_FS'], $this->ID), 'F');

				$queue->AddAttachment(sprintf('%sreturn_label_%s.pdf', $GLOBALS['RETURN_DOCUMENT_DIR_FS'], $this->ID), sprintf('%sreturn_label_%s.pdf', $GLOBALS['RETURN_DOCUMENT_DIR_WS'], $this->ID));

				break;
		}

		$this->EmailedOn = now();
		$this->Update();
	}

	function Received(){
		$this->ReceivedOn = getDatetime();
		$this->Status = 'Received';
		if(!is_numeric($this->ID)){
			return false;
		}
		$receive = new DataQuery(sprintf("update `return` set Received_On=Now(), Status='%s' where Return_ID=%d", mysql_real_escape_string($this->Status), mysql_real_escape_string($this->ID)));
	}

	function AddLine($id, $qty = 1){
		$line = new ProductReturnLine();
		$line->Product->ID = $id;
		$line->Quantity = $qty;
		$line->ReturnID = $this->ID;
		$line->Add();

		$this->Line[] = $line;
	}

	function AddDespatchLine($id, $qty = 1){
		$line = new ProductReturnLineDespatch();
		$line->Product->ID = $id;
		$line->Quantity = $qty;
		$line->ReturnID = $this->ID;
		$line->Add();

		$this->DespatchLine[] = $line;
	}
}