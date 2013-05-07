<?php
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Branch.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductReorder.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseReserve.php');
require_once ($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');

class Warehouse {
	var $ID;
	var $ParentID;
	var $Type;
	var $Contact;
	var $Name;
	var $Despatch;
	var $Invoice;
	var $Purchase;
	var $IsNextDayTrackingRequired;

	function __construct($id = NULL) {
		$this->Contact = new Branch();
		$this->IsNextDayTrackingRequired = 'Y';

		if (!is_null($id)) {
			$this->Get($id);
		}
	}

	function Get($id = NULL) {
		if (!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM warehouse WHERE Warehouse_ID=%d", mysql_real_escape_string($this->ID)));
		if ($data->TotalRows > 0) {
			$this->ParentID = $data->Row['Parent_Warehouse_ID'];
			$this->Type = $data->Row['Type'];
			$this->Contact = ($this->Type == 'B') ? new Branch() : new Supplier();
			$this->Contact->ID = $data->Row['Type_Reference_ID'];
			$this->Name = $data->Row['Warehouse_Name'];
			$this->Despatch = $data->Row['Despatch_Options'];
			$this->Invoice = $data->Row['Invoice_Options'];
			$this->Purchase = $data->Row['Purchase_Options'];
			$this->IsNextDayTrackingRequired = $data->Row['Is_Next_Day_Tracking_Required'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetByType($referenceId, $type) {
		$data = new DataQuery(sprintf("SELECT Warehouse_ID FROM warehouse WHERE Type_Reference_ID=%d AND Type='%s'", mysql_real_escape_string($referenceId), mysql_real_escape_string($type)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['Warehouse_ID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$sql = sprintf("INSERT INTO warehouse (Parent_Warehouse_ID, Type,
													Warehouse_Name,
													Type_Reference_ID,
													Despatch_Options,
													Invoice_Options,
													Purchase_Options,
													Is_Next_Day_Tracking_Required,
													Created_On,
													Created_By,
													Modified_On,
													Modified_By)
							VALUES(%d, '%s', '%s', %d, '%s', '%s', '%s', '%s', Now(), %d, Now(), %d)", mysql_real_escape_string($this->ParentID), mysql_real_escape_string($this->Type), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Contact->ID), mysql_real_escape_string($this->Despatch), mysql_real_escape_string($this->Invoice), mysql_real_escape_string($this->Purchase), mysql_real_escape_string($this->IsNextDayTrackingRequired), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

		$data = new DataQuery($sql);
		
		$this->ID = $data->InsertID;
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		$sql = sprintf("UPDATE warehouse SET Parent_Warehouse_ID=%d, Type='%s',
											Warehouse_Name='%s',
											Type_Reference_ID=%d,
											Despatch_Options='%s',
											Invoice_Options='%s',
											Purchase_Options='%s',
											Is_Next_Day_Tracking_Required='%s',
											Modified_On=Now(),
											Modified_By=%d
											WHERE Warehouse_ID=%d", mysql_real_escape_string($this->ParentID), mysql_real_escape_string($this->Type), mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Contact->ID), mysql_real_escape_string($this->Despatch), mysql_real_escape_string($this->Invoice), mysql_real_escape_string($this->Purchase), mysql_real_escape_string($this->IsNextDayTrackingRequired), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID));

		new DataQuery($sql);
	}
	
	function alreadyWarehouse() {
		$data = new DataQuery(sprintf("SELECT * FROM warehouse WHERE Type = '%s' AND Type_Reference_ID = %d", mysql_real_escape_string($this->Type), mysql_real_escape_string($this->Contact->ID)));
		if ($data->TotalRows > 0) {
			if ($this->ID == $data->Row['Warehouse_ID']) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	function Delete($id = NULL) {
		if (!is_null($id))
			$this->ID = $id;

		if(!is_numeric($this->ID)){
			return false;
		}
			
		new DataQuery(sprintf("delete from warehouse where Warehouse_ID = %d", mysql_real_escape_string($this->ID)));
		WarehouseStock::DeleteWarehouseID($this->ID);

		new DataQuery(sprintf("UPDATE warehouse SET Parent_Warehouse_ID=0 WHERE Parent_Warehouse_ID=%d", mysql_real_escape_string($this->ID)));
	}

	function ChangeQuantity($productID, $quantity) {
		$this->Get();
		
		if($quantity <> 0) {
			$product = new Product($productID);

			$componentSearch = new DataQuery(sprintf("SELECT * FROM product_components WHERE Component_Of_Product_ID=%d AND Product_ID<>Component_Of_Product_ID", mysql_real_escape_string($productID)));
			while ($componentSearch->Row) {
				$componentID = $componentSearch->Row['Product_ID'];
				$componentQuantity = $componentSearch->Row['Component_Quantity'];
				$componentQuantity = $componentQuantity * $quantity;
				
				$this->ChangeQuantity($componentID, $componentQuantity);
				
				$componentSearch->Next();
			}
			$componentSearch->Disconnect();
			
			$quantityRemaining = $quantity;
			
			$fh = fopen($GLOBALS["DIR_WS_ADMIN"] . 'logs/stock.txt', 'a');
			if(!is_numeric($this->ID)){
			return false;
			}
			if($this->Type == 'S') {
				$sql = sprintf("SELECT Stock_ID, Quantity_In_Stock FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d ORDER BY Is_Archived ASC, Stock_ID ASC", mysql_real_escape_string($this->ID), mysql_real_escape_string($productID));
			} else {
				$sql = sprintf("SELECT ws.Stock_ID, ws.Quantity_In_Stock FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='%s' WHERE ws.Product_ID=%d ORDER BY ws.Is_Archived ASC, ws.Stock_ID ASC", mysql_real_escape_string($this->Type), mysql_real_escape_string($productID));
			}
			
			$data = new DataQuery($sql);
			if($data->TotalRows > 0) {
				fwrite($fh, sprintf("Deducting Product ID: %d, Quantity: %d\n", $productID, $quantityRemaining));
						
				while($data->Row) {
					if($data->Row['Quantity_In_Stock'] > $quantityRemaining) {
						$stock = new WarehouseStock($data->Row['Stock_ID']);
						$stock->QuantityInStock -= $quantityRemaining;
						$stock->Update();
						
						fwrite($fh, sprintf("Decrease Product ID: %d, Old Quantity: %d, New Quantity: %d, Date: %s\n", $productID, $data->Row['Quantity_In_Stock'], $stock->QuantityInStock, date('Y-m-d H:i:s')));
						
						$quantityRemaining = 0;
					} else {
						if($this->Type == 'S') {
							$data2 = new DataQuery(sprintf("SELECT COUNT(Stock_ID) AS Count FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", mysql_real_escape_string($this->ID), mysql_real_escape_string($productID)));
							if($data2->Row['Count'] > 1) {
								$stock = new WarehouseStock();
								$stock->Delete($data->Row['Stock_ID']);
								
								fwrite($fh, sprintf("Delete Product ID: %d, Old Quantity: %d, Date: %s\n", $productID, $data->Row['Quantity_In_Stock'], date('Y-m-d H:i:s')));
							} else {
								$stock = new WarehouseStock($data->Row['Stock_ID']);
								$stock->QuantityInStock = 0;
								$stock->Update();
								
								fwrite($fh, sprintf("Zeroed Product ID: %d, Old Quantity: %d, New Quantity: %d, Date: %s\n", $productID, $data->Row['Quantity_In_Stock'], 0, date('Y-m-d H:i:s')));
							}
							$data2->Disconnect();

						} else {

							$data3 = new DataQuery(sprintf("SELECT w.Warehouse_ID FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=1023 GROUP BY w.Warehouse_ID"));
							while($data3->Row) {
								$data2 = new DataQuery(sprintf("SELECT COUNT(Stock_ID) AS Count FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d", mysql_real_escape_string($data3->Row['Warehouse_ID']), mysql_real_escape_string($productID)));
								if($data2->Row['Count'] > 1) {
									$stock = new WarehouseStock();
									$stock->Delete($data->Row['Stock_ID']);
									
									fwrite($fh, sprintf("Delete Product ID: %d, Old Quantity: %d, Date: %s\n", $productID, $data->Row['Quantity_In_Stock'], date('Y-m-d H:i:s')));
								} else {
									$stock = new WarehouseStock($data->Row['Stock_ID']);
									$stock->QuantityInStock = 0;
									$stock->Update();
									
									fwrite($fh, sprintf("Zeroed Product ID: %d, Old Quantity: %d, New Quantity: %d, Date: %s\n", $productID, $data->Row['Quantity_In_Stock'], 0, date('Y-m-d H:i:s')));
								}
								$data2->Disconnect();

								$data3->Next();
							}
							$data3->Disconnect();
						}
						
						$quantityRemaining -= $data->Row['Quantity_In_Stock'];
					}
					
					if($quantityRemaining == 0) {
						break;
					}
					
					$data->Next();
				}
				
				fwrite($fh, "\n");
			}
			$data->Disconnect();
			
			fclose($fh);
			
			$warehouse = new Warehouse($this->ID);

			if($warehouse->Type == 'S') {
				while($warehouse->ID > 0) {
					$data = new DataQuery(sprintf("SELECT Parent_Warehouse_ID FROM warehouse WHERE Warehouse_ID=%d", mysql_real_escape_string($warehouse->ID)));
					if ($data->TotalRows > 0) {
						$warehouse->ID = $data->Row['Parent_Warehouse_ID'];

						if($warehouse->ID > 0) {
							$warehouse->Get();
							$warehouse->ChangeQuantity($productID, $quantity);
						}
					} else {
						$warehouse->ID = 0;
					}
					$data->Disconnect();
				}
			}

			WarehouseReserve::deductReserves($this->ID, $productID, $quantity);
			
			if($product->Type == 'S') {
				if($product->StockMonitor == 'Y') {
					if($product->Stocked == 'Y') {
						$data = new DataQuery(sprintf("SELECT SUM(ws.Quantity_In_Stock) AS Stock_Quantity FROM warehouse_stock AS ws INNER JOIN warehouse AS w ON w.Warehouse_ID=ws.Warehouse_ID AND w.Type='B' WHERE ws.Product_ID=%d", mysql_real_escape_string($product->ID)));
						$stockQuantity = $data->Row['Stock_Quantity'];
						$data->Disconnect();
						
						$data = new DataQuery(sprintf("SELECT SUM(pl.Quantity_Decremental) AS Quantity_Incoming FROM purchase AS p INNER JOIN branch AS b ON b.Branch_ID=p.For_Branch INNER JOIN purchase_line AS pl ON pl.Purchase_ID=p.Purchase_ID WHERE pl.Quantity_Decremental>0 AND pl.Product_ID=%d", mysql_real_escape_string($product->ID)));
						$stockQuantity += $data->Row['Quantity_Incoming'];
						$data->Disconnect();
						
						if($stockQuantity <= $product->StockAlert) {
							$reorder = new ProductReorder();
							
							if(!$reorder->GetByProductID($product->ID)) {
								$reorder->ReorderQuantity = $product->StockReorderQuantity;
								$reorder->Add();
							}
						}
					}
				}
			}
		}
	}
}