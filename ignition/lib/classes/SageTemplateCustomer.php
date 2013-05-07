<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateContact.php');

class SageTemplateCustomer extends SageTemplate {
	public $id;
	public $companyName;
	public $accountReference;
	public $creditLimit;
	public $invoiceAddress;
	public $deliveryAddress;
	public $terms;
	public $nominalCode;
	public $contacts;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_customer.tpl';
		$this->invoiceAddress = new SageTemplateContact();
		$this->deliveryAddress = new SageTemplateContact();
		$this->contacts = array();
	}

	public function addContact(SageTemplateContactContainer $contact) {
		$this->contacts[] = $contact;
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_CUSTOMER_ID\]/', $this->id);
		$this->findReplace->Add('/\[SAGE_CUSTOMER_COMPANYNAME\]/', $this->formatCharacterData($this->companyName));
		$this->findReplace->Add('/\[SAGE_CUSTOMER_ACCOUNTREFERENCE\]/', $this->formatCharacterData(strtoupper($this->accountReference)));
		$this->findReplace->Add('/\[SAGE_CUSTOMER_CREDITLIMIT\]/', $this->creditLimit);
		$this->findReplace->Add('/\[SAGE_CUSTOMER_INVOICEADDRESS\]/', $this->invoiceAddress->buildXml());
		$this->findReplace->Add('/\[SAGE_CUSTOMER_DELIVERYADDRESS\]/', $this->deliveryAddress->buildXml());
		$this->findReplace->Add('/\[SAGE_CUSTOMER_TERMS\]/', $this->terms);
		$this->findReplace->Add('/\[SAGE_CUSTOMER_CURRENCY\]/', 'GBP');
		$this->findReplace->Add('/\[SAGE_CUSTOMER_CONTACTS\]/', $this->getContactXml());
		$this->findReplace->Add('/\[SAGE_CUSTOMER_NOMINAL_CODE\]/', $this->nominalCode);

		return parent::buildXml();
	}

	private function getContactXml() {
		$items = array();

		foreach($this->contacts as $item) {
			$items[] = $item->buildXml();
		}

		return implode($items);
	}
}