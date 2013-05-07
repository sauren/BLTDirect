<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateItem.php');

class SageTemplateItemContainer extends SageTemplate {
	public $item;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_item_container.tpl';
		$this->item = new SageTemplateItem();
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_ITEM\]/', $this->item->buildXml());

		return parent::buildXml();
	}
}