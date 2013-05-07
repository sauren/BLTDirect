<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateProduct.php');

class SageTemplateProductContainer extends SageTemplate {
	public $product;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_product_container.tpl';
		$this->product = new SageTemplateProduct();
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_PRODUCT\]/', $this->product->buildXml());

		return parent::buildXml();
	}
}