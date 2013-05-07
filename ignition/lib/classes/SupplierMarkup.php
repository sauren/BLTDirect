<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');

class SupplierMarkup {
    var $ID;
    var $SupplierID;
    var $Value;
    var $CreatedOn;
    var $CreatedBy;
    var $ModifiedOn;
    var $ModifiedBy;

    function SupplierMarkup($id=null) {
        if(!is_null($id)){
            $this->ID = $id;
            $this->Get();
        }
    }

    function Get($id=null) {
        if(!is_null($id)) {
        	$this->ID = $id;
        }
        if(!is_numeric($this->ID)){
            return false;
        }

        $data = new DataQuery(sprintf("SELECT * FROM supplier_markup WHERE Markup_ID=%d", mysql_real_escape_string($this->ID)));
        if($data->TotalRows > 0) {
            $this->SupplierID = $data->Row['Supplier_ID'];
            $this->Value = $data->Row['Markup_Value'];
            $this->CreatedOn = $data->Row['Created_On'];
            $this->CreatedBy = $data->Row['Created_By'];
            $this->ModifiedOn = $data->Row['Modified_On'];
            $this->ModifiedBy = $data->Row['Modified_By'];

            $data->Disconnect();
            return true;
        }

        $data->Disconnect();
        return false;
    }

	function GetBySupplierID($supplierId = null) {
        if(!is_null($supplierId)) {
        	$this->SupplierID = $supplierId;
        }
        if(!is_numeric($this->ID)){
            return false;
        }

        $data = new DataQuery(sprintf("SELECT Markup_ID FROM supplier_markup WHERE Supplier_ID=%d", mysql_real_escape_string($this->SupplierID)));
        if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['Markup_ID']);

            $data->Disconnect();
            return $return;
        }

        $data->Disconnect();
        return false;
    }

    function Add($id=null) {
        if(!is_null($id)) $this->ID = $id;
        $sql = sprintf("INSERT INTO supplier_markup
               (Supplier_ID, Markup_Value, Created_On, Created_By,
               Modified_On, Modified_By)
               VALUES ( %d, %f, '%s', %d, '%s', %d)",
                   mysql_real_escape_string($this->SupplierID),
                   mysql_real_escape_string($this->Value),
                   mysql_real_escape_string(date('Y-m-d H:i:s')),
                   mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
                   mysql_real_escape_string(date('Y-m-d H:i:s')),
                   mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));

        $data = new DataQuery($sql);

        $this->ID = $data->InsertID;
    }

    function Update($id=null) {
        if(!is_null($id)) $this->ID = $id;
        if(!is_numeric($this->ID)){
            return false;
        }
        $sql = sprintf("UPDATE supplier_markup
                SET Supplier_ID = %d,
                Markup_Value = %f,
                Modified_On = '%s',
                Modified_By = %d
                WHERE Markup_ID = %d",
                mysql_real_escape_string($this->SupplierID),
                mysql_real_escape_string($this->Value),
                mysql_real_escape_string(date('Y-m-d H:i:s')),
                mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
                mysql_real_escape_string($this->ID));

		new DataQuery($sql);
    }

    function Remove($id=null) {
        if(!is_null($id)) {
        	$this->ID = $id;
        }
        if(!is_numeric($this->ID)){
            return false;
        }

        new DataQuery(sprintf("DELETE FROM supplier_markup WHERE Markup_ID=%d", mysql_real_escape_string($this->ID)));
    }
}
?>