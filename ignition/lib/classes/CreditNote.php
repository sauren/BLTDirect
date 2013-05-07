<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CreditNoteLine.php');

class CreditNote {
	var $ID;
	var $IntegrationID;
	var $IntegrationReference;
	var $IsCorrection;
	var $Order;
	var $CreditType;
	var $CreditStatus;
	var $TaxRate;
	var $TotalShipping;
	var $TotalCustom;
	var $TotalNet;
	var $TotalTax;
	var $Total;
	var $NominalCode;
	var $CustomText;
	var $CreditedOn;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Line;
	var $TotalSub;
	var $LinesHtml;
	var $CustomHtml;

	function __construct($id = null, $connection = null) {
		$this->IsCorrection = 'N';
		$this->Order = new Order();
		$this->Line = array();
		$this->TotalSub = 0;
		$this->NominalCode = $GLOBALS['SAGE_DEFAULT_NOMINAL_CODE'];
		$this->CreditedOn = '0000-00-00 00:00:00';

		if (!is_null($id)) {
			$this->Get($id, $connection);
		}
	}

	function Get($id = null, $connection = null) {
		if (!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("select * from credit_note where Credit_Note_ID=%d", mysql_real_escape_string($this->ID)), $connection);
		if($data->TotalRows > 0) {
			$this->IntegrationID = $data->Row['Integration_ID'];
			$this->IntegrationReference = $data->Row['Integration_Reference'];
			$this->IsCorrection = $data->Row['Is_Correction'];
			$this->Order->ID = $data->Row['Order_ID'];
			$this->CreditType = $data->Row['Credit_Type'];
			$this->CreditStatus = $data->Row['Credit_Status'];
			$this->TaxRate = $data->Row['Tax_Rate'];
			$this->TotalShipping = $data->Row['TotalShipping'];
			$this->TotalCustom = $data->Row['TotalCustom'];
			$this->TotalNet = $data->Row['TotalNet'];
			$this->TotalTax = $data->Row['TotalTax'];
			$this->Total = $data->Row['Total'];
			$this->NominalCode = $data->Row['Nominal_Code'];
			$this->CustomText = $data->Row['CustomText'];
			$this->CreditedOn = $data->Row['Credited_On'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		if($this->CreditedOn == '0000-00-00 00:00:00') {
			$this->CreditedOn = date('Y-m-d H:i:s');
		}
		
		$data = new DataQuery(sprintf("insert into credit_note (Integration_ID, Integration_Reference, Is_Correction, Order_ID, Credit_Type, Credit_Status, Tax_Rate, TotalShipping, TotalCustom, TotalNet, TotalTax, Total, Nominal_Code, CustomText, Credited_On, Created_On, Created_By, Modified_On, Modified_By) values ('%s', '%s', '%s', %d, '%s', '%s', %f, %f, %f, %f, %f, %f, %d, '%s', '%s', Now(), %d, Now(), %d)", mysql_real_escape_string($this->IntegrationID), mysql_real_escape_string($this->IntegrationReference), mysql_real_escape_string($this->IsCorrection), mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->CreditType), mysql_real_escape_string($this->CreditStatus), mysql_real_escape_string($this->TaxRate), mysql_real_escape_string($this->TotalShipping), mysql_real_escape_string($this->TotalCustom), mysql_real_escape_string($this->TotalNet), mysql_real_escape_string($this->TotalTax), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->NominalCode), mysql_real_escape_string($this->CustomText), mysql_real_escape_string($this->CreditedOn), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {
		if($this->CreditedOn == '0000-00-00 00:00:00') {
			$this->CreditedOn = date('Y-m-d H:i:s');
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("update credit_note set Integration_ID='%s', Integration_Reference='%s', Is_Correction='%s', Order_ID=%d, Credit_Type='%s', Credit_Status='%s', Tax_Rate=%f, TotalShipping=%f, TotalCustom=%f, TotalNet=%f, TotalTax=%f, Total=%f, Nominal_Code=%d, CustomText='%s', Credited_On='%s', Modified_On=Now(), Modified_By=%d where Credit_Note_ID=%d", mysql_real_escape_string($this->IntegrationID), mysql_real_escape_string($this->IntegrationReference), mysql_real_escape_string($this->IsCorrection), mysql_real_escape_string($this->Order->ID), mysql_real_escape_string($this->CreditType), mysql_real_escape_string($this->CreditStatus), mysql_real_escape_string($this->TaxRate), mysql_real_escape_string($this->TotalShipping), mysql_real_escape_string($this->TotalCustom), mysql_real_escape_string($this->TotalNet), mysql_real_escape_string($this->TotalTax), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->NominalCode), mysql_real_escape_string($this->CustomText), mysql_real_escape_string($this->CreditedOn), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new CreditNoteLine();
		$data->ID = $this->ID;
		$data->Delete();
		new DataQuery(sprintf("delete from credit_note where Credit_Note_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function GetCustom() {
		$this->CustomHtml = '';

		if (!empty($this->CustomText) || ($this->TotalCustom > 0)) {
			$this->CustomHtml .= '<tr>';
			$this->CustomHtml .= '<td>Other:</td>';
			$this->CustomHtml .= '<td colspan="3">' . $this->CustomText . '</td>';
			$this->CustomHtml .= '<td align="right">&pound;' . number_format($this->TotalCustom, 2, '.', ',') . '</td>';
			$this->CustomHtml .= '</tr>';
		}
	}

	function GetLines() {
		$this->LinesHtml = '';

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("select * from credit_note_line where Credit_Note_ID=%d", mysql_real_escape_string($this->ID)));
		while ($data->Row) {
			$line = new CreditNoteLine();
			$line->ID = $data->Row['Credit_Note_Line_ID'];
			$line->CreditNoteID = $data->Row['Credit_Note_ID'];
			$line->Quantity = $data->Row['Quantity'];
			$line->Description = $data->Row['Line_Description'];
			$line->Product->ID = $data->Row['Product_ID'];
			$line->Price = $data->Row['Price'];
			$line->TotalNet = $data->Row['TotalNet'];
			$this->TotalSub += $line->TotalNet;
			$line->TotalTax = $data->Row['TotalTax'];
			$line->Total = $data->Row['Total'];
			$line->CreatedOn = $data->Row['Created_On'];
			$line->CreatedBy = $data->Row['Created_By'];
			$line->ModifiedOn = $data->Row['Modified_On'];
			$line->ModifiedBy = $data->Row['Modified_By'];
			$this->Line[] = $line;

			$this->LinesHtml .= "<tr>";
			$this->LinesHtml .= sprintf("<td>%sx</td>
								<td>%s</td>
								<td>%s</td>
								<td align=\"right\">&pound;%s</td>
								<td align=\"right\">&pound;%s</td>", $line->Quantity, $line->Description, $line->Product->ID, number_format($line->Price, 2, '.', ','), number_format($line->TotalNet, 2, '.', ','));
			$this->LinesHtml .= "</tr>";

			$data->Next();
		}
		$data->Disconnect();
	}

	function GetDocument() {
		$this->GetLines();
		$this->GetCustom();

		$this->Order->Get();
		$this->Order->PaymentMethod->Get();

		if (empty($this->Order->Customer->Contact->ID))
			$this->Order->Customer->Get();
		if (empty($this->Order->Customer->Contact->Person->ID))
			$this->Order->Customer->Contact->Get();
		if (empty($this->CreatedOn))
			$this->CreatedOn = getDatetime();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[ORDER_REF\]/', $this->Order->Prefix . $this->Order->ID);
		$findReplace->Add('/\[CUSTOM_REF\]/', $this->Order->CustomID);
		$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->Order->OrderedOn, 'longdate'));
		$findReplace->Add('/\[CREDIT_REF\]/', $this->ID);
		$findReplace->Add('/\[ISSUE_DATE\]/', cDatetime($this->CreditedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[BILLTO\]/', $this->Order->GetBillingAddress() . '<br /><br />' . $this->Order->Customer->Contact->Person->GetPhone('<br />'));
		$findReplace->Add('/\[PAYMENT_METHOD\]/', $this->Order->GetPaymentMethod());
        $findReplace->Add('/\[CARD_NUMBER\]/', ($this->Order->PaymentMethod->Reference == 'card') ? $this->Order->Card->PrivateNumber() : '');
		$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->TotalNet, 2, '.', ','));
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->TotalSub, 2, '.', ','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->TotalTax, 2, '.', ','));
		$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->TotalShipping, 2, '.', ','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.', ','));
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[CUSTOM\]/', $this->CustomHtml);

		$file = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/print_creditNote.tpl");
		$html = "";
		for ($i = 0; $i < count($file); $i++) {
			$html .= $findReplace->Execute($file[$i]);
		}

		return $html;
	}

	function EmailCustomer() {
		$this->GetLines();
		$this->GetCustom();

		$this->Order->Get();
		if (empty($this->Order->Customer->Contact->ID))
			$this->Order->Customer->Get();
		if (empty($this->Order->Customer->Contact->Person->ID))
			$this->Order->Customer->Contact->Get();

		$findReplace = new FindReplace();
		$findReplace->Add('/\[ORDER_REF\]/', $this->Order->Prefix . $this->Order->ID);
		$findReplace->Add('/\[CUSTOM_REF\]/', $this->Order->CustomID);
		$findReplace->Add('/\[ORDER_DATE\]/', cDatetime($this->Order->OrderedOn, 'longdate'));
		$findReplace->Add('/\[CREDIT_REF\]/', $this->ID);
		$findReplace->Add('/\[ISSUE_DATE\]/', cDatetime($this->CreditedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[BILLTO\]/', $this->Order->GetBillingAddress());
		$findReplace->Add('/\[PAYMENT_METHOD\]/', $this->Order->GetPaymentMethod());
        $findReplace->Add('/\[CARD_NUMBER\]/', ($this->Order->PaymentMethod->Reference == 'card') ? $this->Order->Card->PrivateNumber() : '');
		$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->TotalNet, 2, '.', ','));
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->TotalSub, 2, '.', ','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format($this->TotalTax, 2, '.', ','));
		$findReplace->Add('/\[SHIPPING\]/', "&pound;" . number_format($this->TotalShipping, 2, '.', ','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format($this->Total, 2, '.', ','));
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[CUSTOM\]/', $this->CustomHtml);

		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_creditNote.tpl");
		$orderHtml = "";
		for ($i = 0; $i < count($orderEmail); $i++) {
			$orderHtml .= $findReplace->Execute($orderEmail[$i]);
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());

		$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		$emailBody = "";
		for ($i = 0; $i < count($stdTmplate); $i++) {
			$emailBody .= $findReplace->Execute($stdTmplate[$i]);
		}

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf("%s Credit Note [#%s]", $GLOBALS['COMPANY'], $this->ID));
		$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($emailBody);
		$mail->send(array($this->Order->Customer->GetEmail()));
	}
}