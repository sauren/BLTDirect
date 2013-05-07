<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Purchase.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PurchaseDespatchLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FindReplace.php");

class PurchaseDespatch {
	var $ID;
	var $Purchase;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Line;
	var $LinesFetched;

	function __construct($id = null){
		$this->Purchase = new Purchase();
		$this->Line = array();
		$this->LinesFetched = false;

		if(!is_null($id)){
			$this->Get($id);
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM purchase_despatch WHERE Purchase_Despatch_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Purchase->Get($data->Row['Purchase_ID']);
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedBy = $data->Row['Modified_On'];
			$this->ModifiedOn = $data->Row['Modified_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetLines() {
        $this->Line = array();
		$this->LinesFetched = true;

		if(!is_numeric($this->ID)){
			return false;
		}

        $data = new DataQuery(sprintf("SELECT Purchase_Despatch_Line_ID FROM purchase_despatch_line WHERE Purchase_Despatch_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Line[] = new PurchaseDespatchLine($data->Row['Purchase_Despatch_Line_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO purchase_despatch (Purchase_ID, Created_On, Created_By, Modified_On, Modified_By) values (%d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Purchase->ID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update() {

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE purchase_despatch SET Modified_On=NOW(), Modified_By=%d WHERE Purchase_Despatch_ID=%d", mysql_real_escape_string($this->ModifiedBy), mysql_real_escape_string($this->ID)));
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM purchase_despatch WHERE Purchase_Despatch_ID=%d", mysql_real_escape_string($this->ID)));
		PurchaseDespatchLine::DeletePurchaseDespatch($this->ID);
	}

    function GetDocument($documentIdentifier = array()) {
        $this->Purchase->Get();

        if(!$this->LinesFetched) {
        	$this->GetLines();
		}

		$despatchLines = '';

		if(count($this->Line) > 0) {
			$despatchLines .= '<table width="100%" cellspacing="0" cellpadding="5" class="order">';
			$despatchLines .= '<tr>';
			$despatchLines .= '<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">Qty</th>';
			$despatchLines .= '<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">Quickfind</th>';
			$despatchLines .= '<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">Name</th>';
			$despatchLines .= '<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">SKU</th>';
			$despatchLines .= '<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00; text-align: right;">Cost</th>';
			$despatchLines .= '<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00; text-align: right;">Total</th>';
			$despatchLines .= '</tr>';

			for($i=0; $i<count($this->Line); $i++) {
				$this->Line[$i]->PurchaseLine->Get();
				$this->Line[$i]->PurchaseLine->Product->Get();

				$despatchLines .= sprintf('<tr><td>%s</td><td>%d</td><td>%s&nbsp;</td><td>%s&nbsp;</td><td align="right">&pound;%s</td><td align="right">&pound;%s</td></tr>', $this->Line[$i]->Quantity, $this->Line[$i]->PurchaseLine->Product->ID, $this->Line[$i]->PurchaseLine->Product->Name, $this->Line[$i]->PurchaseLine->Product->SKU, number_format(round($this->Line[$i]->PurchaseLine->Cost, 2), 2, '.', ','), number_format(round($this->Line[$i]->PurchaseLine->Cost * $this->Line[$i]->Quantity, 2), 2, '.', ','));
			}

			$despatchLines .= '</table>';
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[HTTP_SERVER\]/', $GLOBALS['HTTP_SERVER']);
		$findReplace->Add('/\[HTTPS_SERVER\]/', $GLOBALS['HTTPS_SERVER']);

		$findReplace->Add('/\[DESPATCH_ID\]/', $this->ID);
		$findReplace->Add('/\[DESPATCH_PURCHASE_ID\]/', $this->Purchase->ID);
		$findReplace->Add('/\[DESPATCH_CUSTOM_REFERENCE\]/', $this->Purchase->CustomReference);
		$findReplace->Add('/\[DESPATCH_LINES\]/', $despatchLines);
		$findReplace->Add('/\[DESPATCH_CREATED_ON\]/', cDatetime(date('Y-m-d H:i:s'), 'shortdate'));
		$findReplace->Add('/\[DESPATCH_BRANCH_CONTACT\]/', $this->Purchase->GetBranchShip());
		$findReplace->Add('/\[DESPATCH_BRANCH_NAME\]/', $this->Purchase->Person->GetFullName());
		$findReplace->Add('/\[DESPATCH_SUPPLIER_CONTACT\]/', $this->Purchase->GetSupplierAddress());
		$findReplace->Add('/\[DESPATCH_SUPPLIER_NAME\]/', $this->Purchase->Supplier->GetFullName());

		$templateFile = file(sprintf('%slib/templates/%s', $GLOBALS["DIR_WS_ADMIN"], $documentIdentifier['Template']));
		$templateHtml = '';

		for($i=0; $i<count($templateFile); $i++){
			$templateHtml .= $findReplace->Execute($templateFile[$i]);
		}

		return $templateHtml;
	}

    function PrintDespatch($documentIdentifier = array()) {
		$documentIdentifier['Template'] = 'print/purchase_despatch.tpl';

		return $this->GetDocument($documentIdentifier);
	}

	static function DeletePurchase($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("DELETE FROM purchase_despatch WHERE Purchase_ID=%d", $id));
	}
}