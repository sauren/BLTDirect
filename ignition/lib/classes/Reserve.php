<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReserveItem.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');

class Reserve {
	public $id;
	public $supplier;
	public $status;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	public $line;
	public $linesFetched;
	
	public function __construct($id = null) {
		$this->supplier = new Supplier();
		$this->status = 'Pending';
		$this->line = array();
		$this->linesFetched = false;

		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM reserve WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
			$this->supplier->ID = $data->Row['supplierId'];

            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function getLines() {
		$this->line = array();
		$this->linesFetched = true;

		if(!is_numeric($this->id)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT id FROM reserve_item WHERE reserveId=%d", mysql_real_escape_string($this->id)));
		while($data->Row) {
			$line = new ReserveItem($data->Row["id"]);
			$line->product->Get();

			$this->line[] = $line;

			$data->Next();
		}
		$data->Disconnect();
	}

	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO reserve (supplierId, status, createdOn, createdBy, modifiedOn, modifiedBy) VALUES (%d, '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->supplier->ID), mysql_real_escape_string($this->status), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
	}
	
	public function update() {
		if(!is_numeric($this->id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE reserve SET supplierId=%d, status='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->supplier->ID), mysql_real_escape_string($this->status), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->id)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM reserve WHERE id=%d", mysql_real_escape_string($this->id)));
		ReserveItem::DeleteReserve($this->id);
	}

	public function cancel() {
		$this->status = 'Cancelled';
		$this->update();
	}

	public function complete() {
		$this->status = 'Completed';
		$this->update();		
	}

	public function getDocument($documentIdentifier = array()) {
		if(!$this->linesFetched) {
			$this->getLines();
		}

		if(empty($this->supplier->Contact->ID)) {
			$this->supplier->Get();
			$this->supplier->Contact->Get();
		}

		$itemsHtml = '<table width="100%" cellspacing="0" cellpadding="5" class="order"><tr><th align="left">Quickfind</th><th align="left">Name</th><th align="left">SKU</th><th align="right">Quantity</th></tr>';

		foreach($this->line as $line) {
			$itemsHtml .= sprintf('<tr><td align="left">%d</td><td align="left">%s</td><td align="left">%s</td><td align="right">%s</td></tr>', $line->product->ID, $line->product->Name, $line->product->SKU, $line->quantity);
		}

		$itemsHtml .= '</table><br />';

		$findReplace = new FindReplace();
		$findReplace->Add('/\[RESERVE_ID\]/', $this->id);
		$findReplace->Add('/\[RESERVE_DATE\]/', cDatetime($this->createdOn, 'longdate'));
		$findReplace->Add('/\[RESERVE_SUPPLIER\]/', $this->supplier->Contact->Person->GetFullName());
		$findReplace->Add('/\[RESERVE_ITEMS\]/', $itemsHtml);

		return $findReplace->Execute(Template::GetContent($documentIdentifier['Template']));
	}

	public function getPrintDocument($documentIdentifier = array()) {
		if(!isset($documentIdentifier['Template'])) {
			$documentIdentifier['Template'] = 'print_reserve';
		}

		return $this->getDocument($documentIdentifier);
	}
}