<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Purchase.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PurchaseBatchLine.php");

class PurchaseBatch {
	var $ID;
	var $Purchase;
	var $Status;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Line;

	function PurchaseBatch($id = null){
		$this->Purchase = new Purchase();
		$this->Line = array();

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM purchase_batch WHERE Purchase_Batch_ID=%d", mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0) {
			$this->Purchase->Get($data->Row['Purchase_ID']);
			$this->Status = $data->Row['Batch_Status'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedBy = $data->Row['Modified_On'];
			$this->ModifiedOn = $data->Row['Modified_By'];

			$data2 = new DataQuery(sprintf("SELECT Purchase_Batch_Line_ID FROM purchase_batch_line WHERE Purchase_Batch_ID=%d", mysql_real_escape_string($this->ID)));
			while($data2->Row) {
				$this->Line[] = new PurchaseBatchLine($data2->Row['Purchase_Batch_Line_ID']);

				$data2->Next();
			}
			$data2->Disconnect();

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO purchase_batch (Purchase_ID, Batch_Status, Created_On, Created_By, Modified_On, Modified_By) values (%d, '%s', Now(), %d, Now(), %d)", mysql_real_escape_string($this->Purchase->ID), mysql_real_escape_string($this->Status), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();

		return true;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE purchase_batch SET Batch_Status='%s', Modified_On=Now(), Modified_By=%d WHERE Purchase_Batch_ID=%d", mysql_real_escape_string($this->Status), mysql_real_escape_string($this->ModifiedBy), mysql_real_escape_string($this->ID)));

		return true;
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		PurchaseBatchLine::DeletePurchaseBatch($this->ID);
		return true;
	}

	function PrintBatch() {
		$this->Purchase->Get();

		$createdBy = new User($this->CreatedBy);

		$batchLines = '';

		if(count($this->Line) > 0) {
			$batchLines .= '<table width="100%" cellspacing="0" cellpadding="5" class="order">';
			$batchLines .= '<tr>';
			$batchLines .= '<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;" width="75">Qty</th>';
			$batchLines .= '<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">Quickfind ID #</th>';
			$batchLines .= '<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">Name</th>';
			$batchLines .= '<th align="left" nowrap="nowrap" style="border-bottom:1px solid #FA8F00;">SKU</th>';
			$batchLines .= '</tr>';

			for($i=0; $i<count($this->Line); $i++) {
				$this->Line[$i]->PurchaseLine->Get();
				$this->Line[$i]->PurchaseLine->Product->Get();

				$batchLines .= sprintf('<tr><td>%s</td><td>%d</td><td>%s&nbsp;</td><td>%s&nbsp;</td></tr>', $this->Line[$i]->Quantity, $this->Line[$i]->PurchaseLine->Product->ID, $this->Line[$i]->PurchaseLine->Product->Name, $this->Line[$i]->PurchaseLine->Product->SKU);
			}

			$batchLines .= '</table>';
		}

		$findReplace = new FindReplace();
		$findReplace->Add('/\[HTTP_SERVER\]/', $GLOBALS['HTTP_SERVER']);
		$findReplace->Add('/\[HTTPS_SERVER\]/', $GLOBALS['HTTPS_SERVER']);

		$findReplace->Add('/\[BATCH_ID\]/', $this->ID);
		$findReplace->Add('/\[BATCH_PURCHASE_ID\]/', $this->Purchase->ID);
		$findReplace->Add('/\[BATCH_CUSTOM_REFERENCE\]/', $this->Purchase->CustomReference);
		$findReplace->Add('/\[BATCH_LINES\]/', $batchLines);
		$findReplace->Add('/\[BATCH_CREATED_ON\]/', cDatetime(date('Y-m-d H:i:s'), 'shortdate'));
		$findReplace->Add('/\[BATCH_CREATED_BY\]/', trim(sprintf('%s %s', $createdBy->Person->Name, $createdBy->Person->LastName)));
		$findReplace->Add('/\[BATCH_BRANCH_CONTACT\]/', $this->Purchase->GetBranchShip());
		$findReplace->Add('/\[BATCH_BRANCH_NAME\]/', $this->Purchase->Person->GetFullName());
		$findReplace->Add('/\[BATCH_SUPPLIER_CONTACT\]/', $this->Purchase->GetSupplierAddress());
		$findReplace->Add('/\[BATCH_SUPPLIER_NAME\]/', $this->Purchase->Supplier->GetFullName());

		$templateFile = file(sprintf('%slib/templates/print/purchase_batch.tpl', $GLOBALS["DIR_WS_ADMIN"]));
		$templateHtml = '';

		for ($i=0; $i < count($templateFile); $i++) {
			$templateHtml .= $findReplace->Execute($templateFile[$i]);
		}

		return $templateHtml;
	}

	static function DeletePurchaseId($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from purchase_batch where Purchase_ID=%d", mysql_real_escape_string($id)));
	}
}