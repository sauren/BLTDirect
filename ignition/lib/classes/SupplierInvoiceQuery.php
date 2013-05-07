<?php
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/DataQuery.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/Supplier.php");
require_once($GLOBALS["DIR_WS_ADMIN"]."lib/classes/EmailQueue.php");

class SupplierInvoiceQuery {
	public $ID;
	public $Supplier;
	public $DebitID;
	public $InvoiceReference;
	public $InvoiceDate;
	public $InvoiceAmount;
	public $Status;
    public $Product;
	public $Description;
	public $Quantity;
	public $Cost;
	public $Total;
	public $ChargeStandard;
	public $ChargeReceived;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id = null) {
		$this->Supplier = new Supplier();
		$this->Product = new Product();

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM supplier_invoice_query WHERE SupplierInvoiceQueryID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Supplier->ID = $data->Row['SupplierID'];
			$this->DebitID = $data->Row['DebitID'];
			$this->InvoiceReference = $data->Row['InvoiceReference'];
			$this->InvoiceDate = $data->Row['InvoiceDate'];
			$this->InvoiceAmount = $data->Row['InvoiceAmount'];
			$this->Status = $data->Row['Status'];
            $this->Product->ID = $data->Row['ProductID'];
			$this->Description = $data->Row['Description'];
			$this->Quantity = $data->Row['Quantity'];
			$this->Cost = $data->Row['Cost'];
			$this->Total = $data->Row['Total'];
			$this->ChargeStandard = $data->Row['ChargeStandard'];
			$this->ChargeReceived = $data->Row['ChargeReceived'];
			$this->CreatedOn = $data->Row['CreatedOn'];
			$this->CreatedBy = $data->Row['CreatedBy'];
			$this->ModifiedOn = $data->Row['ModifiedOn'];
			$this->ModifiedBy = $data->Row['ModifiedBy'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO supplier_invoice_query (SupplierID, DebitID, InvoiceReference, InvoiceDate, InvoiceAmount, Status, ProductID, Description, Quantity, Cost, Total, ChargeStandard, ChargeReceived, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, '%s', '%s', %f, '%s', %d, '%s', %d, %f, %f, %f, %f, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->DebitID), mysql_real_escape_string($this->InvoiceReference), mysql_real_escape_string($this->InvoiceDate), mysql_real_escape_string($this->InvoiceAmount), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->ChargeStandard), mysql_real_escape_string($this->ChargeReceived), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE supplier_invoice_query SET SupplierID=%d, DebitID=%d, InvoiceReference='%s', InvoiceDate='%s', InvoiceAmount='%f', Status='%s', ProductID=%d, Description='%s', Quantity=%d, Cost=%f, Total=%f, ChargeStandard=%f, ChargeReceived=%f, ModifiedOn=NOW(), ModifiedBy=%d WHERE SupplierInvoiceQueryID=%d", mysql_real_escape_string($this->Supplier->ID), mysql_real_escape_string($this->DebitID), mysql_real_escape_string($this->InvoiceReference), mysql_real_escape_string($this->InvoiceDate), mysql_real_escape_string($this->InvoiceAmount), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->Product->ID), mysql_real_escape_string($this->Description), mysql_real_escape_string($this->Quantity), mysql_real_escape_string($this->Cost), mysql_real_escape_string($this->Total), mysql_real_escape_string($this->ChargeStandard), mysql_real_escape_string($this->ChargeReceived), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM supplier_invoice_query WHERE SupplierInvoiceQueryID=%d", mysql_real_escape_string($this->ID)));
	}

	public function Recalculate() {
		$this->Total = $this->Cost * $this->Quantity;
		$this->Update();
	}

	public function EmailSupplier() {
		if($this->Supplier->ID > 0) {
			$this->Supplier->Get();
			$this->Supplier->Contact->Get();

			$line = sprintf('<tr><td>%d</td><td>%s</td><td>%s</td><td align="right">&pound;%s</td><td align="right">&pound;%s</td><td align="right">&pound;%s</td><td align="right">&pound;%s</td></tr>', $this->Quantity, $this->Description, ($this->Product->ID > 0) ? $this->Product->ID : '', number_format($this->ChargeStandard, 2, '.', ','), number_format($this->ChargeReceived, 2, '.', ','), number_format($this->Cost, 2, '.', ','), number_format($this->Total, 2, '.', ','));

			$findReplace = new FindReplace();
			$findReplace->Add('/\[SUPPLIER_DETAILS\]/', $this->GetSupplierAddress());
			$findReplace->Add('/\[SUPPLIER_QUERY_ID\]/', $this->ID);
			$findReplace->Add('/\[SUPPLIER_QUERY_DATE\]/', cDatetime($this->CreatedOn, 'longdate'));
			$findReplace->Add('/\[SUPPLIER_QUERY_INVOICE_REFERENCE\]/', $this->InvoiceReference);
			$findReplace->Add('/\[SUPPLIER_QUERY_INVOICE_DATE\]/', ($this->InvoiceDate != '0000-00-00 00:00:00') ? cDatetime($this->InvoiceDate, 'shordate') : '');
			$findReplace->Add('/\[SUPPLIER_QUERY_LINE\]/', $line);

			$importTemplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/supplier_invoice_query.tpl");
			$importHtml = '';

			for($i=0; $i<count($importTemplate); $i++){
				$importHtml .= $findReplace->Execute($importTemplate[$i]);
			}

			$findReplace = new FindReplace();
			$findReplace->Add('/\[BODY\]/', $importHtml);
			$findReplace->Add('/\[NAME\]/', $this->Supplier->Contact->Person->GetFullName());

			$standardTemplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$standardHtml = '';

			for($i=0; $i<count($standardTemplate); $i++){
				$standardHtml .= $findReplace->Execute($standardTemplate[$i]);
			}

            $queue = new EmailQueue();
			$queue->GetModuleID('supplierinvoicequeries');
			$queue->Subject = sprintf("%s - Invoice Query [#%s]", $GLOBALS['COMPANY'], $this->ID);
			$queue->Body = $standardHtml;
			$queue->ToAddress = $this->Supplier->GetEmail();
			$queue->Priority = 'H';
			$queue->Type = 'H';
			$queue->Add();
		}
	}

    public function GetSupplierAddress() {
		$sep = '<br />';
		$streets = '';

		if(!empty($this->Supplier->Contact->Person->Address->Line1)) {
			$streets .= $this->Supplier->Contact->Person->Address->Line1;
		}

		if(!empty($this->Supplier->Contact->Person->Address->Line2)) {
			$streets .= $sep . $this->Supplier->Contact->Person->Address->Line2;
		}

		if(!empty($this->Supplier->Contact->Person->Address->Line3)) {
			$streets .= $sep . $this->Supplier->Contact->Person->Address->Line3;
		}

		$html = $this->Supplier->Contact->Person->GetFullName();
		$html .= $sep;
		$html .= implode($sep, array($streets, $this->Supplier->Contact->Person->Address->City, $this->Supplier->Address->Contact->Person->Region->Name, $this->Supplier->Address->Contact->Person->Country->Name, $this->Supplier->Contact->Person->Address->Zip));

		return $html;
	}
}