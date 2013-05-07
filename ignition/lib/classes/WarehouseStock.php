<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Address.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Manufacturer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');

class WarehouseStock {
	var $ID;
	var $Product;
	var $Manufacturer;
	var $Warehouse;
	var $Location;
	var $QuantityInStock;
	var $Cost;
	var $IsArchived;
	var $Stocked;
	var $Imported;
	var $Moniter;
	var $IsBackordered;
	var $BackorderExpectedOn;
	var $IsWrittenOff;
	var $WrittenOffOn;
	var $WrittenOffBy;

	function WarehouseStock($id = NULL){
		$this->Product = new Product();
		$this->Manufacturer = new Manufacturer();
		$this->Warehouse = new Warehouse();
		$this->IsArchived = 'N';
		$this->Stocked = 'N';
		$this->Imported = 'N';
		$this->Moniter = 'N';
		$this->IsBackordered = 'N';
		$this->BackorderExpectedOn = '0000-00-00 00:00:00';
		$this->IsWrittenOff = 'N';
		$this->WrittenOffOn = '0000-00-00 00:00:00';
		$this->WrittenOffBy = 0;

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT * FROM warehouse_stock WHERE Stock_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Product->Get($data->Row['Product_ID']);
			$this->Manufacturer->ID = $data->Row['Manufacturer_ID'];
			$this->Warehouse->Get($data->Row['Warehouse_ID']);
			$this->Location = $data->Row['Shelf_Location'];
			$this->QuantityInStock = $data->Row['Quantity_In_Stock'];
			$this->Cost = $data->Row['Cost'];
			$this->IsArchived = $data->Row['Is_Archived'];
			$this->Stocked = $data->Row['Is_Stocked'];
			$this->Imported = $data->Row['Is_Stock_Imported'];
			$this->Moniter = $data->Row['Moniter_Stock'];
			$this->IsBackordered = $data->Row['Is_Backordered'];
			$this->BackorderExpectedOn = $data->Row['Backorder_Expected_On'];
			$this->IsWrittenOff = $data->Row['Is_Writtenoff'];
			$this->WrittenOffOn = $data->Row['Writtenoff_On'];
			$this->WrittenOffBy = $data->Row['Writtenoff_By'];


			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetViaWarehouseProduct($wid=null, $pid=null){
		if(!is_null($wid)) {
			$this->Warehouse->ID = $wid;
		}

		if(!is_null($pid)) {
			$this->Product->ID = $pid;
		}

		$data = new DataQuery(sprintf("SELECT * FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", mysql_real_escape_string($this->Warehouse->ID), mysql_real_escape_string($this->Product->ID)));
		if($data->TotalRows > 0) {
			$this->ID = $data->Row['Stock_ID'];
			$this->Product->Get($data->Row['Product_ID']);
			$this->Warehouse->Get($data->Row['Warehouse_ID']);
			$this->Location = $data->Row['Shelf_Location'];
			$this->QuantityInStock = $data->Row['Quantity_In_Stock'];
			$this->Cost = $data->Row['Cost'];
			$this->Stocked = $data->Row['Is_Stocked'];
			$this->Imported = $data->Row['Is_Stock_Imported'];
			$this->Moniter = $data->Row['Moniter_Stock'];
			$this->IsBackordered = $data->Row['Is_Backordered'];
			$this->BackorderExpectedOn = $data->Row['Backorder_Expected_On'];
			$this->IsWrittenOff = $data->Row['Is_Writtenoff'];
			$this->WrittenOffOn = $data->Row['Writtenoff_On'];
			$this->WrittenOffBy = $data->Row['Writtenoff_By'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add($connection = null) {
		$sql = sprintf("INSERT INTO warehouse_stock(Product_ID,
													Manufacturer_ID,
													Warehouse_ID,
													Shelf_Location,
													Quantity_In_Stock,
													Cost,
													Is_Archived,
													Is_Stocked,
													Is_Stock_Imported,
													Moniter_Stock,
													Modified_On,
													Modified_By,
													Created_On,
													Created_By,
													Is_Backordered,
													Backorder_Expected_On,
													Is_Writtenoff,
													Writtenoff_On,
													Writtenoff_By)
						VALUES (%d, %d, %d, '%s', %d, %f, '%s', '%s', '%s', '%s', NOW(), %d, NOW(), %d, '%s', '%s', '%s', '%s', %d)",
		mysql_real_escape_string($this->Product->ID),
		mysql_real_escape_string($this->Manufacturer->ID),
		mysql_real_escape_string($this->Warehouse->ID),
		mysql_real_escape_string($this->Location),
		mysql_real_escape_string($this->QuantityInStock),
		mysql_real_escape_string($this->Cost),
		mysql_real_escape_string($this->IsArchived),
		mysql_real_escape_string($this->Stocked),
		mysql_real_escape_string($this->Imported),
		mysql_real_escape_string($this->Moniter),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->IsBackordered),
		mysql_real_escape_string($this->BackorderExpectedOn),
		mysql_real_escape_string($this->IsWrittenOff),
		mysql_real_escape_string($this->WrittenOffOn),
		mysql_real_escape_string($this->WrittenOffBy));

		$data = new DataQuery($sql, $connection);
		
		$this->ID = $data->InsertID;
		
		$this->updateComponentStock();
	}

	function Update(){
		$data = new DataQuery(sprintf("SELECT Quantity_In_Stock FROM warehouse_stock WHERE Stock_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			if($this->QuantityInStock > $data->Row['Quantity_In_Stock']) {
				$data2 = new DataQuery(sprintf("SELECT o.Order_ID FROM orders AS o INNER JOIN order_line AS ol ON o.Order_ID=ol.Order_ID WHERE o.Is_Warehouse_Undeclined='Y' AND o.Is_Restocked='N' AND ol.Product_ID=%d GROUP BY o.Order_ID", mysql_real_escape_string($this->Product->ID)));
				while($data2->Row) {
					Order::OrderRestock($data2->Row['Order_ID']);
					$data2->Next();
				}
				$data2->Disconnect();
			}
		}
		$data->Disconnect();

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE warehouse_stock SET Product_ID = %d,
													Manufacturer_ID=%d,
													Warehouse_ID = %d,
													Shelf_Location = '%s',
													Quantity_In_Stock = %d,
													Cost=%f,
													Is_Archived='%s',
													Is_Stocked='%s',
													Is_Stock_Imported='%s',
													Moniter_Stock='%s',
													Modified_On = Now(),
													Modified_By = %d,
													Is_Backordered='%s',
													Backorder_Expected_On='%s',
													Is_Writtenoff='%s',
													Writtenoff_On='%s',
													Writtenoff_By=%d
													WHERE Stock_ID = %d",
			mysql_real_escape_string($this->Product->ID),
			mysql_real_escape_string($this->Manufacturer->ID),
			mysql_real_escape_string($this->Warehouse->ID),
			mysql_real_escape_string($this->Location),
			mysql_real_escape_string($this->QuantityInStock),
			mysql_real_escape_string($this->Cost),
			mysql_real_escape_string($this->IsArchived),
			mysql_real_escape_string($this->Stocked),
			mysql_real_escape_string($this->Imported),
			mysql_real_escape_string($this->Moniter),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
			mysql_real_escape_string($this->IsBackordered),
			mysql_real_escape_string($this->BackorderExpectedOn),
			mysql_real_escape_string($this->IsWrittenOff),
			mysql_real_escape_string($this->WrittenOffOn),
			mysql_real_escape_string($this->WrittenOffBy),
			mysql_real_escape_string($this->ID)));
			
		$this->updateComponentStock();
	}

	function Delete($id = NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		
		$this->Get();

		new DataQuery(sprintf("DELETE FROM warehouse_stock WHERE Stock_ID=%d", mysql_real_escape_string($this->ID)));
		
		$this->updateComponentStock();
	}

	function ProductInWarehouse(){
		$data = new DataQuery(sprintf("SELECT * FROM warehouse_stock WHERE Product_ID=%d AND Warehouse_ID=%d",mysql_real_escape_string($this->Product->ID),mysql_real_escape_string($this->Warehouse->ID)));
		if($data->TotalRows > 0){
			if($data->Row['Stock_ID'] == $this->ID) return false;
			else return true;
		}
		else return false;
	}

	public function updateComponentStock() {
		$data = new DataQuery(sprintf("SELECT pc.Component_Of_Product_ID, MIN(FLOOR(COALESCE(ws.Quantity, 0)/pc2.Component_Quantity)) AS Quantity, SUM(ws.Cost*pc2.Component_Quantity) AS Cost FROM product_components AS pc INNER JOIN product_components AS pc2 ON pc2.Component_Of_Product_ID=pc.Component_Of_Product_ID LEFT JOIN (SELECT Product_ID, SUM(Quantity_In_Stock) AS Quantity, SUM(Cost*Quantity_In_Stock)/SUM(Quantity_In_Stock) AS Cost FROM warehouse_stock WHERE Warehouse_ID=%d GROUP BY Product_ID) AS ws ON ws.Product_ID=pc2.Product_ID WHERE pc.Product_ID=%d GROUP BY pc.Component_Of_Product_ID", mysql_real_escape_string($this->Warehouse->ID), mysql_real_escape_string($this->Product->ID)));
		while($data->Row) {
			new DataQuery(sprintf("DELETE FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", mysql_real_escape_string($this->Warehouse->ID), mysql_real_escape_string($data->Row['Component_Of_Product_ID'])));

			if($data->Row['Quantity'] > 0) {
				$stock = new WarehouseStock();
				$stock->Product->ID = $data->Row['Component_Of_Product_ID'];
				$stock->Warehouse->ID = $this->Warehouse->ID;
				$stock->QuantityInStock = $data->Row['Quantity'];
				$stock->Cost = $data->Row['Cost'];
				$stock->Add();
			}
	
			$data->Next();			
		}
		$data->Disconnect();
	}

	static function DeleteWarehouseID($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from warehouse_stock WHERE Warehouse_ID = %d", mysql_real_escape_string($id)));
	}
	static function DeleteProduct($id){

		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("delete from warehouse_stock WHERE Product_ID=%d", mysql_real_escape_string($id)));
	}
}