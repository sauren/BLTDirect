<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateCustomer.php');

class SageTemplateCustomerContainer extends SageTemplate {
	public $customer;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_customer_container.tpl';
		$this->customer = new SageTemplateCustomer();
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_CUSTOMER\]/', $this->customer->buildXml());

		return parent::buildXml();
	}
}