<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateInvoice.php');

class SageTemplateInvoiceContainer extends SageTemplate {
	public $invoice;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_invoice_container.tpl';
		$this->invoice = new SageTemplateInvoice();
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_INVOICE\]/', $this->invoice->buildXml());

		return parent::buildXml();
	}
}