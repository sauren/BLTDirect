<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SageTemplate.php');

class SageTemplateProduct extends SageTemplate {
	public $id;
	public $sku;
	public $name;
	public $description;
	public $longDescription;
	public $price;
	public $weight;
	public $quantityInStock;
	public $quantityOnOrder;
	public $reorderQuantity;
	public $taxCode;
	public $department;
	public $publish;
	public $specialOffer;
	public $manufacturer;
	public $itemType;

	public function __construct() {
		parent::__construct();

		$this->template = 'sage_export_product.tpl';
	}

	public function buildXml() {
		$this->findReplace->Add('/\[SAGE_PRODUCT_ID\]/', $this->id);
		$this->findReplace->Add('/\[SAGE_PRODUCT_SKU\]/', $this->formatCharacterData(strtoupper($this->sku)));
		$this->findReplace->Add('/\[SAGE_PRODUCT_NAME\]/', $this->formatCharacterData($this->name));
		$this->findReplace->Add('/\[SAGE_PRODUCT_DESCRIPTION\]/', $this->formatCharacterData($this->description));
		$this->findReplace->Add('/\[SAGE_PRODUCT_LONGDESCRIPTION\]/', $this->formatCharacterData($this->longDescription));
		$this->findReplace->Add('/\[SAGE_PRODUCT_PRICE\]/', $this->price);
		$this->findReplace->Add('/\[SAGE_PRODUCT_WEIGHT\]/', $this->weight);
		$this->findReplace->Add('/\[SAGE_PRODUCT_QUANTITYINSTOCK\]/', $this->quantityInStock);
		$this->findReplace->Add('/\[SAGE_PRODUCT_QUANTITYONORDER\]/', $this->quantityOnOrder);
		$this->findReplace->Add('/\[SAGE_PRODUCT_REORDERQUANTITY\]/', $this->reorderQuantity);
		$this->findReplace->Add('/\[SAGE_PRODUCT_TAXCODE\]/', $this->taxCode);
		$this->findReplace->Add('/\[SAGE_PRODUCT_DEPARTMENT\]/', $this->department);
		$this->findReplace->Add('/\[SAGE_PRODUCT_PUBLISH\]/', $this->publish);
		$this->findReplace->Add('/\[SAGE_PRODUCT_SPECIALOFFER\]/', $this->specialOffer);
		$this->findReplace->Add('/\[SAGE_PRODUCT_MANUFACTURER\]/', $this->formatCharacterData($this->manufacturer));
		$this->findReplace->Add('/\[SAGE_PRODUCT_ITEMTYPE\]/', $this->itemType);

		return parent::buildXml();
	}
}