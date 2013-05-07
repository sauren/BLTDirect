<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PurchaseLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PurchaseBatch.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PurchaseBatchLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PurchaseDespatch.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PurchaseDespatchLine.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Courier.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Person.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Order.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/PriceEnquiry.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Supplier.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Cipher.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/WarehouseStock.php");

class Purchase {
	var $ID;
	var $CustomReferenceNumber;
	var $PriceEnquiry;
	var $SupplierID;
	var $Type;
	var $Order;
	var $PurchasedOn;
	var $Person;
	var $Organisation;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Line;
	var $LinesHtml;
    var $Batch;
	var $BatchesFetched;
    var $Despatch;
	var $DespatchesFetched;
	var $Supplier;
	var $SupOrg;
	var $IsSupplierComplete;
	var $SupplierNotes;
	var $PSID;
	var $Warehouse;
	var $Postage;
	var $Branch;
	var $Status;
	var $SubTotal;
	var $OrderNote;

	function __construct($id=NULL, $session=NULL) {
		$this->PriceEnquiry = new PriceEnquiry();
		$this->Supplier = new Person();
		$this->Type = 'Stock';
		$this->IsSupplierComplete = 'N';
		$this->Person = new Person();
		$this->Line = array();
		$this->Order = new Order();
		$this->PurchasedOn = '0000-00-00 00:00:00';
		$this->Warehouse = new Warehouse();
		$this->PSID = (is_null($session)) ? 0 : $session->ID;
		$this->Postage = 0;
		$this->Branch = 0;
		$this->Status = 'Irrelevant';
		$this->SubTotal = 0;
        $this->Batch = array();
		$this->BatchesFetched = false;
        $this->Despatch = array();
		$this->DespatchesFetched = false;

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		} elseif(!is_null($session)) {
			$this->Get();
		}
	}

	function Get($id=NULL,$session=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_null($session)) {
			$this->PSID = $session->ID;
		}

		$data = new DataQuery((!empty($this->PSID))?sprintf("select * from purchase where PSID='%s'", mysql_real_escape_string($this->PSID)) : sprintf("select * from purchase where Purchase_ID=%d",mysql_real_escape_string( $this->ID)));
		if($data->TotalRows > 0) {
			$this->ID = $data->Row['Purchase_ID'];
			$this->PriceEnquiry->ID = $data->Row['Price_Enquiry_ID'];
			$this->SupplierID = $data->Row['Supplier_ID'];
			$this->Type = $data->Row['Type'];
			$this->PSID = $data->Row['PSID'];
			$this->CustomReferenceNumber = $data->Row['Custom_Reference_Number'];
			$this->Order->ID = $data->Row['Order_ID'];
			$this->PurchasedOn = $data->Row['Purchased_On'];
			$this->Person->Title = $data->Row['Purchase_Title'];
			$this->Person->Name = $data->Row['Purchase_First_Name'];
			$this->Person->Initial = $data->Row['Purchase_Initial'];
			$this->Person->LastName = $data->Row['Purchase_Last_Name'];
			$this->Organisation = $data->Row['Purchase_Organisation_Name'];
			$this->Person->Address->Line1 = $data->Row['Purchase_Address_1'];
			$this->Person->Address->Line2 = $data->Row['Purchase_Address_2'];
			$this->Person->Address->Line3 = $data->Row['Purchase_Address_3'];
			$this->Person->Address->City = $data->Row['Purchase_City'];
			$this->Person->Address->Region->Name = $data->Row['Purchase_Region'];
			$this->Person->Address->Country->Name = $data->Row['Purchase_Country'];
			$this->Person->Address->Zip = $data->Row['Purchase_Zip'];
			$this->Supplier->Title = $data->Row['Supplier_Title'];
			$this->Supplier->Name = $data->Row['Supplier_First_Name'];
			$this->Supplier->Initial = $data->Row['Supplier_Initial'];
			$this->Supplier->LastName = $data->Row['Supplier_Last_Name'];
			$this->Supplier->Fax = $data->Row['Supplier_Fax'];
			$this->SupOrg = $data->Row['Supplier_Organisation_Name'];
			$this->Supplier->Address->Line1 = $data->Row['Supplier_Address_1'];
			$this->Supplier->Address->Line2 = $data->Row['Supplier_Address_2'];
			$this->Supplier->Address->Line3 = $data->Row['Supplier_Address_3'];
			$this->Supplier->Address->City = $data->Row['Supplier_City'];
			$this->Supplier->Address->Region->Name = $data->Row['Supplier_Region'];
			$this->Supplier->Address->Country->Name = $data->Row['Supplier_Country'];
			$this->Supplier->Address->Zip = $data->Row['Supplier_Zip'];
			$this->IsSupplierComplete = $data->Row['Is_Supplier_Complete'];
			$this->SupplierNotes = $data->Row['Supplier_Notes'];
			$this->CreatedOn = $data->Row['Created_On'];
			$this->CreatedBy = $data->Row['Created_By'];
			$this->ModifiedOn = $data->Row['Modified_On'];
			$this->ModifiedBy = $data->Row['Modified_By'];
			$this->Warehouse->ID = $data->Row['Warehouse_ID'];
			$this->Postage = $data->Row['Postage_ID'];
			$this->Branch = $data->Row['For_Branch'];
			$this->Status = $data->Row['Purchase_Status'];
			$this->OrderNote = $data->Row['Order_Note'];

			$this->GetLines();

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetLines(){
		$this->Line = array();
		$this->LinesHtml = '';

		$this->SubTotal = 0;
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("SELECT Purchase_Line_ID FROM purchase_line WHERE Purchase_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row){
			$line = new PurchaseLine($data->Row['Purchase_Line_ID']);
			$line->Product->Get();
			$line->Manufacturer->Get();

			$this->LinesHtml .= sprintf("<tr><td>%sx</td><td>%s<br />Part Number: %s<br />Supplier Part Number: %s<br />Manufacturer: %s</td><td>%s</td><td align=\"right\">&pound;%s</td><td align=\"right\">&pound;%s</td></tr>", $line->Quantity, $line->Product->Name, $line->Product->SKU, $line->SKU, $line->Manufacturer->Name, $line->Product->ID, number_format($line->Cost, 2, '.', ','), number_format(($line->Cost*$line->Quantity), 2, '.', ','));
			$this->SubTotal += ($line->Cost * $line->Quantity);

			$this->Line[] = $line;

			$data->Next();
		}
		$data->Disconnect();
	}

    function GetBatches() {
        $this->Batch = array();
		$this->BatchesFetched = true;
		if(!is_numeric($this->ID)){
			return false;
		}
        $data = new DataQuery(sprintf("SELECT Purchase_Batch_ID FROM purchase_batch WHERE Purchase_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Batch[] = new PurchaseBatch($data->Row['Purchase_Batch_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

    function GetDespatches() {
        $this->Despatch = array();
		$this->DespatchesFetched = true;
		if(!is_numeric($this->ID)){
			return false;
		}
        $data = new DataQuery(sprintf("SELECT Purchase_Despatch_ID FROM purchase_despatch WHERE Purchase_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Despatch[] = new PurchaseDespatch($data->Row['Purchase_Despatch_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function AddLine($productId, $quantity=1, $cost=false) {
		$line = new PurchaseLine();
		$line->Product->Get($productId);
		$line->Quantity = $quantity;
		$line->QuantityDec = $line->Quantity;
		$line->Purchase = $this->ID;

		$exists = $line->Exists();
		$added = $line->Add();

		if(!$exists && $added){
			if($cost) {
				$supplierId = 0;
				$data = new DataQuery(sprintf("SELECT Supplied_By FROM purchase_line WHERE Purchase_ID=%d AND Supplied_By>0 LIMIT 0, 1", mysql_real_escape_string($this->ID)));
				if($data->TotalRows > 0) {
					$supplierId = $data->Row['Supplied_By'];
				}
				$data->Disconnect();

				if($supplierId > 0) {
					$data = new DataQuery(sprintf("SELECT Cost, Supplier_SKU FROM supplier_product WHERE Product_ID=%d AND Supplier_ID=%d", mysql_real_escape_string($line->Product->ID), mysql_real_escape_string($supplierId)));
					if($data->TotalRows > 0) {
						$line->SuppliedBy = $supplierId;
						$line->Cost = $data->Row['Cost'];
						$line->SKU = $data->Row['Supplier_SKU'];

						$data2 = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($this->Warehouse->ID), mysql_real_escape_string($line->Product->ID)));
						if($data2->TotalRows > 0) {
							$line->Location = $data2->Row['Shelf_Location'];
						}
						$data2->Disconnect();

						$line->Update();
					}
					$data->Disconnect();
				}
			}

			$this->Line[] = $line;

			return true;
		} else {
			return $added;
		}
	}

	function SetSuppliers(){
		//Used to set the preffered supplier for all lines, if a preferred supplier is obtainable, and if the line does not have a supplier;
		$lineGrabber = new DataQuery(sprintf("SELECT * FROM purchase_line WHERE Purchase_ID = %d",mysql_real_escape_string($this->ID)));
		while ($lineGrabber->Row) {
			$line = new PurchaseLine($lineGrabber->Row['Purchase_Line_ID']);
			if($line->SuppliedBy == 0){
				$supplierDetails = new DataQuery(sprintf("SELECT * FROM supplier_product WHERE Product_ID=%d AND Preferred_Supplier = 'Y'",mysql_real_escape_string($line->Product->ID)));
				if($supplierDetails->TotalRows >0){
					$line->SuppliedBy = $supplierDetails->Row['Supplier_ID'];
					$line->Cost = $supplierDetails->Row['Cost'];
					$line->SKU = $supplierDetails->Row['Supplier_SKU'];

					$data = new DataQuery(sprintf("SELECT Shelf_Location FROM warehouse_stock WHERE Warehouse_ID=%d AND Product_ID=%d AND Shelf_Location<>'' LIMIT 0, 1", mysql_real_escape_string($this->Warehouse->ID), mysql_real_escape_string($line->Product->ID)));
					if($data->TotalRows > 0) {
						$line->Location = $data->Row['Shelf_Location'];
					}
					$data->Disconnect();

					$line->Update();
				}
				$supplierDetails->Disconnect();

			}
			$lineGrabber->Next();
		}
		$lineGrabber->Disconnect();
	}

	function Exists(){
		$data = new DataQuery(sprintf("select * from purchase where PSID='%s'", mysql_real_escape_string($this->PSID)));
		if($data->TotalRows > 0){
			$data->Disconnect();
			return true;
		} else {
			$data->Disconnect();
			return false;
		}
	}

	function SetDefaults(){
		$today = getdate();
		$this->PurchasedOn = $today['year'].'-'.$today['mon'].'-'.$today['mday'];
	}

	function Add(){
		$sql = sprintf("insert into purchase (Price_Enquiry_ID, Supplier_ID, Type,
						Order_Note, PSID, Custom_Reference_Number, Order_ID, Purchased_On, Purchase_Title, Purchase_First_Name, Purchase_Initial, Purchase_Last_Name, Purchase_Organisation_Name, Purchase_Address_1, Purchase_Address_2, Purchase_Address_3, Purchase_City, Purchase_Region, Purchase_Country, Purchase_Zip,
						 Supplier_Title, Supplier_First_Name, Supplier_Initial, Supplier_Last_Name, Supplier_Organisation_Name, Supplier_Address_1, Supplier_Address_2, Supplier_Address_3, Supplier_City, Supplier_Region, Supplier_Country, Supplier_Zip, Is_Supplier_Complete, Supplier_Notes, Supplier_Fax, Created_On, Created_By, Modified_On, Modified_By, Warehouse_ID, Postage_ID,
						For_Branch,Purchase_Status) values (%d, %d, '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', Now(), %d, Now(), %d, %d,%d,%d,'%s')",
		mysql_real_escape_string($this->PriceEnquiry->ID),
		mysql_real_escape_string($this->SupplierID),
		mysql_real_escape_string($this->Type),
		mysql_real_escape_string(stripslashes($this->OrderNote)),
		mysql_real_escape_string($this->PSID),
		mysql_real_escape_string($this->CustomReferenceNumber),
		mysql_real_escape_string($this->Order->ID),
		mysql_real_escape_string($this->PurchasedOn),
		mysql_real_escape_string(stripslashes($this->Person->Title)),
		mysql_real_escape_string(stripslashes($this->Person->Name)),
		mysql_real_escape_string($this->Person->Initial),
		mysql_real_escape_string(stripslashes($this->Person->LastName)),
		mysql_real_escape_string(stripslashes($this->Organisation)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line1)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line2)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line3)),
		mysql_real_escape_string(stripslashes($this->Person->Address->City)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Region->Name)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Country->Name)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Zip)),
		mysql_real_escape_string(stripslashes($this->Supplier->Title)),
		mysql_real_escape_string(stripslashes($this->Supplier->Name)),
		mysql_real_escape_string($this->Supplier->Initial),
		mysql_real_escape_string(stripslashes($this->Supplier->LastName)),
		mysql_real_escape_string(stripslashes($this->SupOrg)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Line1)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Line2)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Line3)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->City)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Region->Name)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Country->Name)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Zip)),
		mysql_real_escape_string($this->IsSupplierComplete),
		mysql_real_escape_string($this->SupplierNotes),
		mysql_real_escape_string($this->Supplier->Fax),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->Warehouse->ID),
		mysql_real_escape_string($this->Postage),
		mysql_real_escape_string($this->Branch),
		mysql_real_escape_string($this->Status));

		$data = new DataQuery($sql);

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("update purchase set Type='%s', Order_Note='%s', PSID='%s', Custom_Reference_Number='%s', Order_ID=%d,
					    Purchased_On='%s', Purchase_Title='%s', Purchase_First_Name='%s', Purchase_Initial='%s', Purchase_Last_Name='%s', Purchase_Organisation_Name='%s', Purchase_Address_1='%s', Purchase_Address_2='%s', Purchase_Address_3='%s', Purchase_City='%s', Purchase_Region='%s', Purchase_Country='%s', Purchase_Zip='%s',
					    Supplier_Title='%s', Supplier_First_Name='%s', Supplier_Initial='%s', Supplier_Last_Name='%s', Supplier_Organisation_Name='%s', Supplier_Address_1='%s', Supplier_Address_2='%s', Supplier_Address_3='%s', Supplier_City='%s', Supplier_Region='%s', Supplier_Country='%s', Supplier_Zip='%s', Is_Supplier_Complete='%s', Supplier_Notes='%s', Modified_On=Now(), Modified_By=%d, Warehouse_ID=%d, Postage_ID=%d, For_Branch=%d, Purchase_Status='%s'
						WHERE Purchase_ID=%d",
		mysql_real_escape_string($this->Type),
		mysql_real_escape_string(stripslashes($this->OrderNote)),
		mysql_real_escape_string($this->PSID),
		mysql_real_escape_string($this->CustomReferenceNumber),
		mysql_real_escape_string($this->Order->ID),
		mysql_real_escape_string($this->PurchasedOn),
		mysql_real_escape_string(stripslashes($this->Person->Title)),
		mysql_real_escape_string(stripslashes($this->Person->Name)),
		mysql_real_escape_string($this->Person->Initial),
		mysql_real_escape_string(stripslashes($this->Person->LastName)),
		mysql_real_escape_string(stripslashes($this->Organisation)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line1)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line2)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Line3)),
		mysql_real_escape_string(stripslashes($this->Person->Address->City)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Region->Name)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Country->Name)),
		mysql_real_escape_string(stripslashes($this->Person->Address->Zip)),
		mysql_real_escape_string(stripslashes($this->Supplier->Title)),
		mysql_real_escape_string(stripslashes($this->Supplier->Name)),
		mysql_real_escape_string($this->Supplier->Initial),
		mysql_real_escape_string(stripslashes($this->Supplier->LastName)),
		mysql_real_escape_string(stripslashes($this->SupOrg)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Line1)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Line2)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Line3)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->City)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Region->Name)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Country->Name)),
		mysql_real_escape_string(stripslashes($this->Supplier->Address->Zip)),
		mysql_real_escape_string($this->IsSupplierComplete),
		mysql_real_escape_string($this->SupplierNotes),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->Warehouse->ID),
		mysql_real_escape_string($this->Postage),
		mysql_real_escape_string($this->Branch),
		mysql_real_escape_string($this->Status),
		mysql_real_escape_string($this->ID)));

		$data->Disconnect();

		return true;
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("delete from purchase where Purchase_ID=%d", mysql_real_escape_string($this->ID)));
		PurchaseLine::DeletePurchaseId($this->ID);

		$data = new DataQuery(sprintf("SELECT Purchase_Batch_ID FROM purchase_batch WHERE Purchase_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			PurchaseBatchLine::DeletePurchase($data->Row['Purchase_Batch_ID']);

			$data->Next();
		}
		$data->Disconnect();

		PurchaseBatch::DeletePurchaseId($this->ID);

        $data = new DataQuery(sprintf("SELECT Purchase_Despatch_ID FROM purchase_despatch WHERE Purchase_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			PurchaseDespatchLine::DeletePurchase($data->Row['Purchase_Despatch_ID']);
			$data->Next();
		}
		$data->Disconnect();
		PurchaseDespatch::DeletePurchase($this->ID);
	}

	function Cancel() {
		$this->GetLines();

		$cancel = true;

        for($i=0; $i<count($this->Line); $i++) {
        	if($this->Line[$i]->QuantityDec < $this->Line[$i]->Quantity) {
        		$cancel = false;
			}
		}

		if($cancel) {
            for($i=0; $i<count($this->Line); $i++) {
            	$this->Line[$i]->QuantityDec = 0;
            	$this->Line[$i]->Update();
			}

			$this->Status = 'Cancelled';
		} else {
            for($i=0; $i<count($this->Line); $i++) {
                $this->Line[$i]->Quantity -= $this->Line[$i]->QuantityDec;
            	$this->Line[$i]->QuantityDec = 0;
            	$this->Line[$i]->Update();
			}

			$this->Status = 'Fulfilled';
		}

		$this->Update();
	}

	function EmailToBuy($email){
		$this->GetLines();
		$this->Order->Get();

		if(empty($this->Order->Customer->Contact->ID))$this->Order->Customer->Get();
		if(empty($this->Order->Customer->Contact->Person->ID)) $this->Order->Customer->Contact->Get();

		$findReplace = new FindReplace;

		$findReplace->Add('/\[PURCHASE_REF\]/', $this->ID);
		$findReplace->Add('/\[PURCHASE_DATE\]/', cDatetime($this->PurchasedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[SHIPTO\]/',$this->GetBranchShip());
		$findReplace->Add('/\[SUPPLIER_DETAILS\]/', $this->GetSupplierAddress(true));
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal, 2, '.',','));
		$findReplace->Add('/\[NOTICES\]/',$this->PostageMessages());
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format(($this->SubTotal*((strtotime($this->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($this->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))), 2, '.',','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format(($this->SubTotal*((strtotime($this->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($this->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2)))+$this->SubTotal, 2, '.',','));
		$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->SubTotal, 2, '.',','));

		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_purchase_to_buy.tpl");
		$orderHtml = "";
		for($i=0; $i < count($orderEmail); $i++){
			$orderHtml .= $findReplace->Execute($orderEmail[$i]);
		}

		$supname = (empty($this->SupOrg))? $this->Supplier->GetFullName():$this->SupOrg;
		$findReplace = new FindReplace;
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', $supname);
		$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		
		$emailBody = '';

		for($i=0; $i < count($stdTmplate); $i++){
			$emailBody .= $findReplace->Execute($stdTmplate[$i]);
		}

		$queue = new EmailQueue();
		$queue->GetModuleID('purchases');
		$queue->Type = 'H';
		$queue->Priority = 'H';
		$queue->Subject = sprintf("%s - Purchase Order [#%d]", $GLOBALS['COMPANY'], $this->ID);
		$queue->Body = $emailBody;
		$queue->ToAddress = $email;
		$queue->FromAddress = 'customerservices@bltdirect.com';
		$queue->Add();
	}

	function GetBranchShip(){
		$html = $this->Organisation;
		$sep = "<br>";
		$streets = "";
		if(!empty($this->Person->Address->Line1)) $streets .= $this->Person->Address->Line1;
		if(!empty($this->Person->Address->Line2)) $streets .= $sep . $this->Person->Address->Line2;
		if(!empty($this->Person->Address->Line3)) $streets .= $sep . $this->Person->Address->Line3;

		$html .= '<br>'.$streets.'<br>'.$this->Person->Address->City.'<br>'.$this->Person->Address->Region->Name.'<br>'.$this->Person->Address->Country->Name.'<br>'.$this->Person->Address->Zip;
		return $html;
	}

	function GetDocToBuy(){
		$this->Order->Get();
		$this->GetLines();
		if(empty($this->Order->Customer->Contact->ID))$this->Order->Customer->Get();
		if(empty($this->Order->Customer->Contact->Person->ID)) $this->Order->Customer->Contact->Get();

		$findReplace = new FindReplace;
		$findReplace->Add('/\[SUPPLIER_DETAILS\]/', $this->GetSupplierAddress(true));
		$findReplace->Add('/\[PURCHASE_REF\]/', $this->ID);
		$findReplace->Add('/\[PURCHASE_DATE\]/', cDatetime($this->PurchasedOn, 'longdate'));
		$findReplace->Add('/\[ORDER_REF\]/', $this->Order->Prefix . $this->Order->ID);
		$findReplace->Add('/\[SHIPTO\]/', $this->GetBranchShip());
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal, 2, '.',','));
		$findReplace->Add('/\[NOTICES\]/',$this->PostageMessages());
		$findReplace->Add('/\[ORDER_NOTE\]/', (strlen($this->OrderNote) > 0) ? '<br /><br /><strong>Notes:</strong><br />'.$this->OrderNote : '');
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format(($this->SubTotal*((strtotime($this->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($this->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))), 2, '.',','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format(($this->SubTotal*((strtotime($this->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($this->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2)))+$this->SubTotal, 2, '.',','));
		$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->SubTotal, 2, '.',','));

		$file = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/print_purchase_to_buy.tpl");
		$html = "";
		for($i=0; $i < count($file); $i++){
			$html .= $findReplace->Execute($file[$i]);
		}

		return $html;
	}

	function EmailCustomer($email){
		$this->GetLines();
		$this->Order->Get();
		if(empty($this->Order->Customer->Contact->ID))$this->Order->Customer->Get();
		if(empty($this->Order->Customer->Contact->Person->ID)) $this->Order->Customer->Contact->Get();

		$findReplace = new FindReplace;
		$findReplace->Add('/\[PURCHASE_REF\]/', $this->ID);
		$findReplace->Add('/\[PURCHASE_DATE\]/', cDatetime($this->PurchasedOn, 'longdate'));
		$findReplace->Add('/\[CUSTOMER_NAME\]/', $this->Order->Customer->Contact->Person->GetFullName());
		$findReplace->Add('/\[SUPPLIER_DETAILS\]/', $this->GetSupplierAddress());
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[NOTICES\]/',$this->PostageMessages());

		$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email_purchase.tpl");
		$orderHtml = "";
		for($i=0; $i < count($orderEmail); $i++){
			$orderHtml .= $findReplace->Execute($orderEmail[$i]);
		}

		$supname = (empty($this->SupOrg))? $this->Supplier->GetFullName():$this->SupOrg;
		$findReplace = new FindReplace;
		$findReplace->Add('/\[BODY\]/', $orderHtml);
		$findReplace->Add('/\[NAME\]/', $supname);

		$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
		$emailBody = "";
		for($i=0; $i < count($stdTmplate); $i++){
			$emailBody .= $findReplace->Execute($stdTmplate[$i]);
		}

		$mail = new htmlMimeMail5();
		$mail->setFrom($GLOBALS['EMAIL_FROM']);
		$mail->setSubject(sprintf("%s Purchase Order [#%s%s]", $GLOBALS['COMPANY'], $this->Order->Prefix, $this->Order->ID));
		$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
		$mail->setHTML($emailBody);
		$mail->send(array($email));
	}

    function EmailSupplier() {
    	if($this->SupplierID > 0) {
			$this->GetLines();
			$this->Order->Get();

			if(empty($this->Order->Customer->Contact->ID)) {
				$this->Order->Customer->Get();
			}

			if(empty($this->Order->Customer->Contact->Person->ID)) {
				$this->Order->Customer->Contact->Get();
			}

			$supplier = new Supplier($this->SupplierID);
			if(!is_numeric($this->ID)){
				return false;
			}
			$data = new DataQuery(sprintf("SELECT Despatch_ID FROM despatch WHERE Purchase_ID=%d", mysql_real_escape_string($this->ID)));
			if($data->TotalRows > 0) {
	            $reference = serialize(array('Despatch' => $data->Row['Despatch_ID']));

				$cypher = new Cipher($reference);
				$cypher->Encrypt();

				$reference = base64_encode($cypher->Value);

				$findReplace = new FindReplace();
				$findReplace->Add('/\[PURCHASE_PRINT_LINK\]/', sprintf('%sprintDespatchNote.php?ref=%s', $GLOBALS['HTTP_SERVER'], $reference));

				$findReplace->Add('/\[PURCHASE_REF\]/', $this->ID);
				$findReplace->Add('/\[PURCHASE_DATE\]/', cDatetime($this->PurchasedOn, 'longdate'));
				$findReplace->Add('/\[SUPPLIER_DETAILS\]/', $this->GetSupplierAddress());
				$findReplace->Add('/\[LINES\]/', $this->LinesHtml);

				$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/purchase_supplier.tpl");
				$orderHtml = '';

				for($i=0; $i<count($orderEmail); $i++){
					$orderHtml .= $findReplace->Execute($orderEmail[$i]);
				}

				$findReplace = new FindReplace;
				$findReplace->Add('/\[BODY\]/', $orderHtml);
				$findReplace->Add('/\[NAME\]/', (empty($this->SupOrg))? $this->Supplier->GetFullName():$this->SupOrg);

				$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
				$emailBody = '';

				for($i=0; $i<count($stdTmplate); $i++){
					$emailBody .= $findReplace->Execute($stdTmplate[$i]);
				}

				$mail = new htmlMimeMail5();
				$mail->setFrom($GLOBALS['EMAIL_FROM']);
				$mail->setSubject(sprintf("%s Purchase Order [#%s%s]", $GLOBALS['COMPANY'], $this->Order->Prefix, $this->Order->ID));
				$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
				$mail->setHTML($emailBody);
				$mail->send(array($supplier->GetEmail()));
			}
		}
	}
	
	function EmailSupplierAmended() {
    	if($this->SupplierID > 0) {
			$this->GetLines();
			$this->Order->Get();

			$findReplace = new FindReplace();
			$findReplace->Add('/\[PURCHASE_ID\]/', $this->ID);
			$findReplace->Add('/\[PURCHASE_DATE\]/', cDatetime($this->PurchasedOn, 'longdate'));
			$findReplace->Add('/\[PURCHASE_SUPPLIER\]/', $this->GetSupplierAddress());
			$findReplace->Add('/\[PURCHASE_LINES\]/', $this->LinesHtml);

			$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/purchase_supplier_amended.tpl");
			$orderHtml = '';

			for($i=0; $i<count($orderEmail); $i++){
				$orderHtml .= $findReplace->Execute($orderEmail[$i]);
			}

			$findReplace = new FindReplace;
			$findReplace->Add('/\[BODY\]/', $orderHtml);
			$findReplace->Add('/\[NAME\]/', (empty($this->SupOrg))? $this->Supplier->GetFullName():$this->SupOrg);

			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = '';

			for($i=0; $i<count($stdTmplate); $i++){
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			$supplier = new Supplier($this->SupplierID);
			
			$mail = new htmlMimeMail5();
			$mail->setFrom($GLOBALS['EMAIL_FROM']);
			$mail->setSubject(sprintf("%s Amended Purchase Order [#%s%s]", $GLOBALS['COMPANY'], $this->Order->Prefix, $this->Order->ID));
			$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
			$mail->setHTML($emailBody);
			$mail->send(array($supplier->GetEmail()));
		}
	}
	
	function EmailSupplierDeleted() {
    	if($this->SupplierID > 0) {
			$this->Order->Get();

			$findReplace = new FindReplace();
			$findReplace->Add('/\[PURCHASE_ID\]/', $this->ID);
			$findReplace->Add('/\[PURCHASE_DATE\]/', cDatetime($this->PurchasedOn, 'longdate'));
			$findReplace->Add('/\[PURCHASE_SUPPLIER\]/', $this->GetSupplierAddress());

			$orderEmail = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/purchase_supplier_deleted.tpl");
			$orderHtml = '';

			for($i=0; $i<count($orderEmail); $i++){
				$orderHtml .= $findReplace->Execute($orderEmail[$i]);
			}

			$findReplace = new FindReplace;
			$findReplace->Add('/\[BODY\]/', $orderHtml);
			$findReplace->Add('/\[NAME\]/', (empty($this->SupOrg))? $this->Supplier->GetFullName():$this->SupOrg);

			$stdTmplate = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/email/template_standard.tpl");
			$emailBody = '';

			for($i=0; $i<count($stdTmplate); $i++){
				$emailBody .= $findReplace->Execute($stdTmplate[$i]);
			}

			$supplier = new Supplier($this->SupplierID);
			
			$mail = new htmlMimeMail5();
			$mail->setFrom($GLOBALS['EMAIL_FROM']);
			$mail->setSubject(sprintf("%s Amended Purchase Order [#%s%s]", $GLOBALS['COMPANY'], $this->Order->Prefix, $this->Order->ID));
			$mail->setText('This is an HTMl email. If you only see this text your email client only supports plain text emails.');
			$mail->setHTML($emailBody);
			$mail->send(array($supplier->GetEmail()));
		}
	}

	function GetDocument(){
		$this->Order->Get();
		$this->GetLines();
		
		if(empty($this->Order->Customer->Contact->ID))$this->Order->Customer->Get();
		if(empty($this->Order->Customer->Contact->Person->ID)) $this->Order->Customer->Contact->Get();

		$findReplace = new FindReplace;
		$findReplace->Add('/\[SUPPLIER_DETAILS\]/', $this->GetSupplierAddress(true));
		$findReplace->Add('/\[PURCHASE_REF\]/', $this->ID);
		$findReplace->Add('/\[PURCHASE_DATE\]/', cDatetime($this->PurchasedOn, 'longdate'));
		$findReplace->Add('/\[ORDER_REF\]/', $this->Order->Prefix . $this->Order->ID);
		$findReplace->Add('/\[SHIPTO\]/', $this->Order->GetShippingAddress());
		$findReplace->Add('/\[LINES\]/', $this->LinesHtml);
		$findReplace->Add('/\[NOTICES\]/',$this->PostageMessages());
		$findReplace->Add('/\[ORDER_NOTE\]/', (strlen($this->OrderNote) > 0) ? '<br /><br /><strong>Notes:</strong><br />'.$this->OrderNote : '');
		$findReplace->Add('/\[TAX\]/', "&pound;" . number_format(($this->SubTotal*((strtotime($this->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($this->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2))), 2, '.',','));
		$findReplace->Add('/\[TOTAL\]/', "&pound;" . number_format(($this->SubTotal*((strtotime($this->CreatedOn) < strtotime('2010-01-01 00:00:00')) ? 0.150 : ((strtotime($this->CreatedOn) < strtotime('2011-01-04 00:00:00')) ? 0.175 : 0.2)))+$this->SubTotal, 2, '.',','));
		$findReplace->Add('/\[NET\]/', "&pound;" . number_format($this->SubTotal, 2, '.',','));
		$findReplace->Add('/\[SUBTOTAL\]/', "&pound;" . number_format($this->SubTotal, 2, '.',','));

		$file = file($GLOBALS["DIR_WS_ADMIN"] . "lib/templates/print_purchase_note.tpl");
		$html = "";
		for($i=0; $i < count($file); $i++){
			$html .= $findReplace->Execute($file[$i]);
		}

		return $html;
	}

	function PostageMessages(){
		$messages = '';

		$postage = new Postage($this->Postage);

		$now = getDatetime();
		$dateSplit = explode(' ', $now);
		$today = $dateSplit[0];
		$postageDate = $today . ' ' .$postage->CuttOffTime . ':00';
		$secDiff = dateDiff($postageDate, $now, 's');
		if($postage->CuttOffTime != '00:00' && $secDiff > 0){
			$messages .= "<p>".$postage->Message."</p>";
		}
		return $messages;
	}

	function GetSupplierAddress($getFax = false){
		$html = (empty($this->SupOrg))? $this->Supplier->GetFullName():$this->SupOrg;
		$sep = "<br>";
		$streets = "";
		if(!empty($this->Supplier->Address->Line1)) $streets .= $this->Supplier->Address->Line1;
		if(!empty($this->Supplier->Address->Line2)) $streets .= $sep . $this->Supplier->Address->Line2;
		if(!empty($this->Supplier->Address->Line3)) $streets .= $sep . $this->Supplier->Address->Line3;

		$html .= '<br>'.$streets.'<br>'.$this->Supplier->Address->City.'<br>'.$this->Supplier->Address->Region->Name.'<br>'.$this->Supplier->Address->Country->Name.'<br>'.$this->Supplier->Address->Zip;

		if($getFax) {
			if(!empty($this->Supplier->Fax)) {
				$html .= '<br>Fax: '.$this->Supplier->Fax;
			}
		}

		return $html;
	}
}