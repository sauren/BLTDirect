<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');

class SageTemplateItem extends SageTemplate {
	public $id;
	public $sku;
	public $name;
	public $description;
	public $comments;
	public $quantity;
	public $price;
	public $discountAmount;
	public $discountPercentage;
	public $reference;
	public $taxRate;
	public $totalNet;
	public $totalTax;
	public $nominalCode;
	public $department;
	public $type;
	public $taxCode;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_item.tpl';
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_ITEM_ID\]/', $this->id);
		$this->findReplace->Add('/\[SAGE_ITEM_SKU\]/', $this->formatCharacterData(strtoupper($this->sku)));
		$this->findReplace->Add('/\[SAGE_ITEM_NAME\]/', $this->formatCharacterData($this->name));
		$this->findReplace->Add('/\[SAGE_ITEM_DESCRIPTION\]/', $this->formatCharacterData($this->description));
		$this->findReplace->Add('/\[SAGE_ITEM_COMMENTS\]/', $this->formatCharacterData($this->comments));
		$this->findReplace->Add('/\[SAGE_ITEM_QUANTITY\]/', $this->quantity);
		$this->findReplace->Add('/\[SAGE_ITEM_PRICE\]/', $this->price);
		$this->findReplace->Add('/\[SAGE_ITEM_DISCOUNTAMOUNT\]/', $this->discountAmount);
		$this->findReplace->Add('/\[SAGE_ITEM_DISCOUNTPERCENTAGE\]/', $this->discountPercentage);
		$this->findReplace->Add('/\[SAGE_ITEM_REFERENCE\]/', $this->formatCharacterData(strtoupper($this->reference)));
		$this->findReplace->Add('/\[SAGE_ITEM_TAXRATE\]/', $this->taxRate);
		$this->findReplace->Add('/\[SAGE_ITEM_TOTALNET\]/', $this->totalNet);
		$this->findReplace->Add('/\[SAGE_ITEM_TOTALTAX\]/', $this->totalTax);
		$this->findReplace->Add('/\[SAGE_ITEM_NOMINALCODE\]/', $this->nominalCode);
		$this->findReplace->Add('/\[SAGE_ITEM_DEPARTMENT\]/', $this->department);
		$this->findReplace->Add('/\[SAGE_ITEM_TYPE\]/', $this->type);
		$this->findReplace->Add('/\[SAGE_ITEM_TAXCODE\]/', $this->taxCode);

		return parent::buildXml();
	}
}