<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateCustomerContainer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateInvoiceContainer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateProductContainer.php');

class SageTemplateCompany extends SageTemplate {
	public $products;
	public $customers;
	public $invoices;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_company.tpl';
		$this->products = array();
		$this->customers = array();
		$this->invoices = array();
	}

	public function addProduct(SageTemplateProductContainer $product) {
		$this->products[] = $product;
	}

	public function addCustomer(SageTemplateCustomerContainer $customer) {
		$this->customers[] = $customer;
	}

	public function addInvoice(SageTemplateInvoiceContainer $invoice) {
		$this->invoices[] = $invoice;
	}

	private function getProductXml() {
		$items = array();

		foreach($this->products as $item) {
			$items[] = $item->buildXml();
		}

		return implode($items);
	}

	private function getCustomerXml() {
		$items = array();

		foreach($this->customers as $item) {
			$items[] = $item->buildXml();
		}

		return implode($items);
	}

	private function getInvoiceXml() {
		$items = array();

		foreach($this->invoices as $item) {
			$items[] = $item->buildXml();
		}

		return implode($items);
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_PRODUCTS\]/', $this->getProductXml());
		$this->findReplace->Add('/\[SAGE_CUSTOMERS\]/', $this->getCustomerXml());
		$this->findReplace->Add('/\[SAGE_INVOICES\]/', $this->getInvoiceXml());

		return parent::buildXml();
	}
}