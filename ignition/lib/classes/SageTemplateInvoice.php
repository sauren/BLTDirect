<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateContact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateItem.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateItemContainer.php');

class SageTemplateInvoice extends SageTemplate {
	public $id;
	public $customerId;
	public $invoiceNumber;
	public $customerOrderNumber;
	public $accountReference;
	public $orderNumber;
	public $currency;
	public $notes1;
	public $notes2;
	public $notes3;
	public $invoiceDate;
	public $invoiceAddress;
	public $deliveryAddress;
	public $carriage;
	public $type;
	public $nominalCode;
	public $details;
	public $taxCode;
	public $department;
	public $paymentReference;
	public $paymentAmount;
	public $bankAccount;
	public $paymentType;
	public $postedDate;
	public $items;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_invoice.tpl';
		$this->invoiceAddress = new SageTemplateContact();
		$this->deliveryAddress = new SageTemplateContact();
		$this->carriage = new SageTemplateItem();
		$this->items = array();
	}

	public function addItem(SageTemplateItemContainer $item) {
		$this->items[] = $item;
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_INVOICE_ID\]/', $this->id);
		$this->findReplace->Add('/\[SAGE_INVOICE_CUSTOMERID\]/', strtoupper($this->customerId));
		$this->findReplace->Add('/\[SAGE_INVOICE_INVOICENUMBER\]/', $this->invoiceNumber);
		$this->findReplace->Add('/\[SAGE_INVOICE_CUSTOMERORDERNUMBER\]/', $this->formatCharacterData($this->customerOrderNumber));
		$this->findReplace->Add('/\[SAGE_INVOICE_ACCOUNTREFERENCE\]/', $this->formatCharacterData(strtoupper($this->accountReference)));
		$this->findReplace->Add('/\[SAGE_INVOICE_ORDERNUMBER\]/', $this->formatCharacterData(strtoupper($this->orderNumber)));
		$this->findReplace->Add('/\[SAGE_INVOICE_CURRENCY\]/', $this->currency);
		$this->findReplace->Add('/\[SAGE_INVOICE_NOTES_1\]/', $this->notes1);
		$this->findReplace->Add('/\[SAGE_INVOICE_NOTES_2\]/', $this->notes2);
		$this->findReplace->Add('/\[SAGE_INVOICE_NOTES_3\]/', $this->notes3);
		$this->findReplace->Add('/\[SAGE_INVOICE_DATE\]/', $this->invoiceDate);
		$this->findReplace->Add('/\[SAGE_INVOICE_INVOICEADDRESS\]/', $this->invoiceAddress->buildXml());
		$this->findReplace->Add('/\[SAGE_INVOICE_DELIVERYADDRESS\]/', $this->deliveryAddress->buildXml());
		$this->findReplace->Add('/\[SAGE_INVOICE_CARRIAGE\]/', $this->carriage->buildXml());
		$this->findReplace->Add('/\[SAGE_INVOICE_ITEMS\]/', $this->getItemXml());
		$this->findReplace->Add('/\[SAGE_INVOICE_TYPE\]/', $this->type);
		$this->findReplace->Add('/\[SAGE_INVOICE_NOMINALCODE\]/', $this->nominalCode);
		$this->findReplace->Add('/\[SAGE_INVOICE_DETAILS\]/', $this->details);
		$this->findReplace->Add('/\[SAGE_INVOICE_TAXCODE\]/', $this->taxCode);
		$this->findReplace->Add('/\[SAGE_INVOICE_DEPARTMENT\]/', $this->department);
		$this->findReplace->Add('/\[SAGE_INVOICE_PAYMENTREFERENCE\]/', strtoupper($this->paymentReference));
		$this->findReplace->Add('/\[SAGE_INVOICE_PAYMENTAMOUNT\]/', $this->paymentAmount);
		$this->findReplace->Add('/\[SAGE_INVOICE_BANKACCOUNT\]/', $this->bankAccount);
		$this->findReplace->Add('/\[SAGE_INVOICE_PAYMENTTYPE\]/', $this->paymentType);
		$this->findReplace->Add('/\[SAGE_INVOICE_POSTEDDATE\]/', $this->postedDate);

		return parent::buildXml();
	}

	private function getItemXml() {
		$items = array();

		foreach($this->items as $item) {
			$items[] = $item->buildXml();
		}

		return implode($items);
	}
}