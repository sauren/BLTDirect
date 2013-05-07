<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Supplier.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DebitLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FindReplace.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/htmlMimeMail5.php");

class Debit {
	var $ID;
	var $Prefix;
	var $Supplier;
	var $IntegrationID;
	var $Total;
	var $IsPaid;
	var $Person;
	var $Organisation;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Line;
	var $LinesHtml;
	var $DSID;
	var $Status;

	function Debit($id=NULL, $session=NULL) {
		$this->Supplier = new Supplier();
		$this->Person = new Person();
		$this->Line = array();
		$this->IsPaid = 'N';
		$this->Total = 0;
		$this->DSID = (is_null($session)) ? '' : $session->ID;

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		} elseif(!is_null($session)) {
			$this->Get();
		}
	}

	function Get($id=NULL, $session=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_null($session)) {
			$this->DSID = $session->ID;
		}

		
		$sql = (!empty($this->DSID)) ? sprintf("SELECT * FROM debit WHERE DSID='%s'", mysql_real_escape_string($this->DSID)) : sprintf("SELECT * FROM debit WHERE Debit_ID=%d", mysql_real_escape_string($this->ID));

		$data = new DataQuery($sql);

		if($data->TotalRows > 0) {
			$this->ID = $data->Row['Debit_ID'];
			$this->Prefix = $data->Row['Prefix'];
			$this->Supplier->ID = $data->Row['Supplier_ID'];
			$this->IntegrationID = $data->Row['Integration_ID'];
			$this->DSID = $data->Row['DSID'];
			$this->Total = $data->Row['Debit_Total'];
			$this->IsPaid = $data->Row['Is_Paid'];
			$this->Person->ID = $data->Row['Person_ID'];
			$this->Person->Title = $data->Row['Debit_Title'];
			$this->Person->Name = $data->Row['Debit_First_Name'];
			$this->Person->Initial = $data->Row['Debit_Initial'];
			$this->Person->LastName = $data->Row['Debit_Last_Name'];
			$this->Organisation = $data->Row['Debit_Organisation'];
			$this->Person->Address->Line1 = $data->Row['Debit_Address_1'];
			$this->Person->Address->Line2 = $data->Row['Debit_Address_2'];
			$this->Person->Address->Line3 = $data->Row['Debit_Address_3'];
			$this->Person->Address->City = $data->Row['Debit_City'];
			$this->Person->Address->Region->Name = $data->Row['Debit_Region'];
			$this->Person->Address->Country->Name = $data->Row['Debit_Country'];
			$this->Person->Address->Zip = $data->Row['Debit_Zip'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->Status = $data->Row['Status'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetLines(){
		$this->Line = array();
		$this->LinesHtml = '';

		$data = new DataQuery(sprintf("SELECT Debit_Line_ID FROM debit_line WHERE Debit_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$line = new DebitLine($data->Row['Debit_Line_ID']);

			$this->Line[] = $line;
			$this->LinesHtml .= sprintf('<tr><td>%sx</td><td>%s<br /><strong>%s</strong>%s</td><td>%s</td><td align="right">&pound;%s</td><td align="right">&pound;%s</td></tr>', $line->Quantity, $line->Description, ((strlen($line->Reason) > 0) ? $line->Reason : ''), ((strlen($line->Custom) > 0) ? '<br />' . $line->Custom : ''), $line->Product->ID, number_format($line->Cost, 2, '.', ','), number_format($line->Total, 2, '.', ','));

			$data->Next();
		}
		$data->Disconnect();
	}

	function AddLine($productId, $quantity = 1) {
		$line = new DebitLine();
		$line->Product->Get($productId);
		$line->Description = $line->Product->Name;
		$line->Quantity = $quantity;
		$line->DebitID = $this->ID;

		$supplierDetails = new DataQuery(sprintf("SELECT sp.Supplier_ID, sp.Cost FROM supplier_product AS sp WHERE sp.Product_ID=%d AND sp.Preferred_Supplier='Y'", mysql_real_escape_string($line->Product->ID)));
		if($supplierDetails->TotalRows >0){
			$line->SuppliedBy = $supplierDetails->Row['Supplier_ID'];
			$line->Cost = $supplierDetails->Row['Cost'];
			$line->Total = number_format(($supplierDetails->Row['Cost'] * $line->Quantity), 2, '.', '');
		}
		$supplierDetails->Disconnect();

		$exists = $line->Exists();
		$added = $line->Add();

		if(!$exists && $added) {
			$this->Line[] = $line;
			return true;
		} else {
			return $added;
		}
	}

	function Exists() {
		$data = new DataQuery(sprintf("SELECT * FROM debit WHERE DSID='%s'", mysql_real_escape_string($this->DSID)));

		if($data->TotalRows > 0) {
			$data->Disconnect();
			return true;
		} else {
			$data->Disconnect();
			return false;
		}
	}

	function SetSuppliers(){
		
		$lineGrabber = new DataQuery(sprintf("SELECT * FROM debit_line WHERE Debit_ID = %d",mysql_real_escape_string($this->ID)));
		while ($lineGrabber->Row) {
			$line = new DebitLine($lineGrabber->Row['Debit_Line_ID']);
			if($line->SuppliedBy == 0){
				$supplierDetails = new DataQuery(sprintf("SELECT sp.Supplier_ID, sp.Cost FROM supplier_product AS sp WHERE sp.Product_ID=%d AND sp.Preferred_Supplier='Y'", mysql_real_escape_string($line->Product->ID)));
				if($supplierDetails->TotalRows >0){
					$line->Cost = $supplierDetails->Row['Cost'];
					$line->Total = number_format(($supplierDetails->Row['Cost'] * $line->Quantity), 2, '.', '');
					$line->Update();
				}
				$supplierDetails->Disconnect();
			}
			$lineGrabber->Next();
		}
		$lineGrabber->Disconnect();
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO debit (Prefix, Supplier_ID, Integration_ID, Status, DSID, Debit_Total, Is_Paid, Debit_Title, Debit_First_Name, Debit_Initial, Debit_Last_Name, Debit_Organisation, Debit_Address_1, Debit_Address_2, Debit_Address_3, Debit_City, Debit_Region, Debit_Country, Debit_Zip, Created_On, Created_By, Modified_On, Modified_By, Person_ID) VALUES ('%s', %d, '%s', '%s', '%s', %f, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', Now(), %d, Now(), %d, %d)", mysql_real_escape_string($this->Prefix), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->IntegrationID), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->DSID), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->IsPaid), mysql_real_escape_string($this->Person->Title), mysql_real_escape_string($this->Person->Name), mysql_real_escape_string($this->Person->Initial), mysql_real_escape_string($this->Person->LastName), mysql_real_escape_string($this->Organisation), mysql_real_escape_string($this->Person->Address->Line1), mysql_real_escape_string($this->Person->Address->Line2), mysql_real_escape_string($this->Person->Address->Line3), mysql_real_escape_string($this->Person->Address->City), mysql_real_escape_string($this->Person->Address->Region->Name), mysql_real_escape_string($this->Person->Address->Country->Name), mysql_real_escape_string($this->Person->Address->Zip), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->Person->ID)));

		$this->ID = $data->InsertID;
	}

	function Update() {

		
		new DataQuery(sprintf("UPDATE debit SET Prefix='%s', Supplier_ID=%d, Integration_ID='%s', Status='%s', DSID='%s', Debit_Total='%f', Is_Paid='%s', Debit_Title='%s', Debit_First_Name='%s', Debit_Initial='%s', Debit_Last_Name='%s', Debit_Organisation='%s', Debit_Address_1='%s', Debit_Address_2='%s', Debit_Address_3='%s', Debit_City='%s', Debit_Region='%s', Debit_Country='%s', Debit_Zip='%s', Modified_On=Now(), Modified_By=%d, Person_ID=%d WHERE Debit_ID=%d", mysql_real_escape_string($this->Prefix), mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->IntegrationID), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->DSID), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->IsPaid), mysql_real_escape_string($this->Person->Title), mysql_real_escape_string($this->Person->Name), mysql_real_escape_string($this->Person->Initial), mysql_real_escape_string($this->Person->LastName), mysql_real_escape_string($this->Organisation), mysql_real_escape_string($this->Person->Address->Line1), mysql_real_escape_string($this->Person->Address->Line2), mysql_real_escape_string($this->Person->Address->Line3), mysql_real_escape_string($this->Person->Address->City), mysql_real_escape_string($this->Person->Address->Region->Name), mysql_real_escape_string($this->Person->Address->Country->Name), mysql_real_escape_string($this->Person->Address->Zip), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->Person->ID), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		new DataQuery(sprintf("DELETE FROM debit WHERE Debit_ID=%d", mysql_real_escape_string($this->ID)));
		DebitLine::DeleteDebit($this->ID);
	}

	function GetDocument() {
		$this->GetLines();

		$findReplace = new FindReplace;
		$findReplace->Add('/\[SUPPLIER_DETAILS\]/', $this->GetSupplierAddress(true));
		$findReplace->Add('/\[DEBIT_REF\]/', $this->Prefix . $this->ID);
		$findReplace->Add('/\[DEBIT_DATE\]/', cDatetime($this->CreatedOn, 'longdate'));
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->Total, 2, '.',','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format(($this->Total * ((strtotime($this->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($this->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))), 2, '.',','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format(($this->Total + ($this->Total * ((strtotime($this->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($this->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2)))), 2, '.',','));

		$file = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/print_debitNote.tpl");
		$html = "";

		for($i=0; $i < count($file); $i++){
			$html .= $findReplace->Execute($file[$i]);
		}

		return $html;
	}

	function EmailSupplier() {
		$this->GetLines();

		$findReplace = new FindReplace;
		$findReplace->Add('/\[SUPPLIER_DETAILS\]/', $this->GetSupplierAddress(true));
		$findReplace->Add('/\[DEBIT_REF\]/', $this->Prefix . $this->ID);
		$findReplace->Add('/\[DEBIT_DATE\]/', cDatetime($this->CreatedOn, 'longdate'));
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->Total, 2, '.',','));
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format(($this->Total * ((strtotime($this->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($this->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))), 2, '.',','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format(($this->Total + ($this->Total * ((strtotime($this->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($this->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2)))), 2, '.',','));

		$file = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_debitNote.tpl");
		$html = "";

		for($i=0; $i < count($file); $i++){
			$html .= $findReplace->Execute($file[$i]);
		}

		$findReplace = new FindReplace;
		$findReplace->Add('/\[BODY\]/', $html);
		$findReplace->Add('/\[NAME\]/', $this->Person->GetFullName());

		$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		$emailBody = "";
		for($i=0; $i < count($stdTmplate); $i++){
			$emailBody .= $findReplace->Execute($stdTmplate[$i]);
		}

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf("%s Debit Note [#%s]", $GLOBALS['COMPANY'], $this->ID));
		$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($emailBody);

		$person = new Person($this->Person->ID);

		if(strlen($person->Email) > 0) {
			$mail->send(array($person->Email));

			return true;
		}

		return false;
	}

	function GetSupplierAddress($getFax = false){
		$address = $this->Person->GetFullName();
		$address .= "<br />";
		if (!empty($this->Organisation)) {
			$address .= $this->Organisation . "<br />";
		}
		$address .= $this->Person->Address->GetFormatted('<br />');

		if($getFax) {
			if(!empty($this->Person->Fax)) {
				$address .= '<br />Fax: '. $this->Person->Fax;
			}
		}

		return $address;
	}
}