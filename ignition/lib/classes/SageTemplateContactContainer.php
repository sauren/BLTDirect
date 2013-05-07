<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplateContact.php');

class SageTemplateContactContainer extends SageTemplate {
	public $contact;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_contact_container.tpl';
		$this->contact = new SageTemplateContact();
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_CONTACT\]/', $this->contact->buildXml());

		return parent::buildXml();
	}
}