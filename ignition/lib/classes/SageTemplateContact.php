<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');

class SageTemplateContact extends SageTemplate {
	public $title;
	public $forename;
	public $surname;
	public $company;
	public $address1;
	public $address2;
	public $address3;
	public $town;
	public $postcode;
	public $county;
	public $country;
	public $telephone;
	public $fax;
	public $mobile;
	public $email;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_contact.tpl';
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_CONTACT_TITLE\]/', $this->formatCharacterData($this->title));
		$this->findReplace->Add('/\[SAGE_CONTACT_FORENAME\]/', $this->formatCharacterData($this->forename));
		$this->findReplace->Add('/\[SAGE_CONTACT_SURNAME\]/', $this->formatCharacterData($this->surname));
		$this->findReplace->Add('/\[SAGE_CONTACT_COMPANY\]/', $this->formatCharacterData($this->company));
		$this->findReplace->Add('/\[SAGE_CONTACT_ADDRESS1\]/', $this->formatCharacterData($this->address1));
		$this->findReplace->Add('/\[SAGE_CONTACT_ADDRESS2\]/', $this->formatCharacterData($this->address2));
		$this->findReplace->Add('/\[SAGE_CONTACT_ADDRESS3\]/', $this->formatCharacterData($this->address3));
		$this->findReplace->Add('/\[SAGE_CONTACT_TOWN\]/', $this->formatCharacterData($this->town));
		$this->findReplace->Add('/\[SAGE_CONTACT_POSTCODE\]/', $this->formatCharacterData(strtoupper($this->postcode)));
		$this->findReplace->Add('/\[SAGE_CONTACT_COUNTY\]/', $this->formatCharacterData($this->county));
		$this->findReplace->Add('/\[SAGE_CONTACT_COUNTRY\]/', $this->formatCharacterData($this->country));
		$this->findReplace->Add('/\[SAGE_CONTACT_TELEPHONE\]/', $this->formatCharacterData($this->telephone));
		$this->findReplace->Add('/\[SAGE_CONTACT_FAX\]/', $this->formatCharacterData($this->fax));
		$this->findReplace->Add('/\[SAGE_CONTACT_MOBILE\]/', $this->formatCharacterData($this->mobile));
		$this->findReplace->Add('/\[SAGE_CONTACT_EMAIL\]/', $this->email);
		$this->findReplace->Add('/\[SAGE_CONTACT_NOTES\]/', '');

		return parent::buildXml();
	}
}