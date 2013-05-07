<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class ReturnReason {
    var $ID;
    var $Title;
    var $Description;
    var $ResultantPrefix;
    var $CreatedOn;
    var $CreatedBy;
    var $ModifiedOn;
    var $ModifiedBy;
    var $Collection;

    function __construct($id=NULL){
    	$this->ResultantPrefix = 'R';

        if(!is_null($id)){
            $this->ID=$id;
            $this->Get();
        }

        $this->Collection = array();
    }

    function Get($id = null) {
        if(!is_null($id)) {
            $this->ID = $id;
        }

        if(!is_numeric($this->ID)){
            return false;
        }

        $data = new DataQuery(sprintf("SELECT * FROM return_reason WHERE Reason_ID=%d", mysql_real_escape_string($this->ID)));
        if($data->TotalRows > 0) {
            $this->Title = $data->Row['Reason_Title'];
            $this->Description = $data->Row['Reason_Desc'];
            $this->ResultantPrefix = $data->Row['Resultant_Prefix'];
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

    function GetByTitle($title = null) {
        if(!is_null($title)) {
            $this->Title = $title;
        }

        $data = new DataQuery(sprintf("SELECT Reason_ID FROM return_reason WHERE Reason_Title LIKE '%s'", mysql_real_escape_string($this->Title)));
        if($data->TotalRows > 0) {
           $this->Get($data->Row['Reason_ID']);

            $data->Disconnect();
            return true;
        }

        $data->Disconnect();
        return false;
    }

    function GetReasons($order=null){  # Get all reasons in table
        if(is_null($order)) $order = 'Reason_ID';
        $sql = "SELECT * FROM return_reason
                ORDER BY $order ASC";
        $data = new DataQuery($sql);
        if($data->TotalRows < 1) return false;
        while($data->Row){
            $i = count($this->Collection);
            $this->Collection[$i] = new ReturnReason;
            $this->Collection[$i]->ID = $data->Row['Reason_ID'];
            $this->Collection[$i]->Title = $data->Row['Reason_Title'];
            $this->Collection[$i]->Description = $data->Row['Reason_Desc'];
            $this->Collection[$i]->ResultantPrefix = $data->Row['Resultant_Prefix'];
            $this->Collection[$i]->CreatedOn = $data->Row['Created_On'];
            $this->Collection[$i]->ModifiedOn = $data->Row['Modified_On'];
            $this->Collection[$i]->CreatedBy = $data->Row['Created_By'];
            $this->Collection[$i]->ModifiedBy = $data->Row['Modified_By'];
            $data->Next();
        }
        return $data;   # Remember to disconnect after use!
    }

    function Add(){
        $sql = sprintf("INSERT INTO `return_reason`
                       (Reason_Title, Reason_Desc, Resultant_Prefix,
                       Created_On, Created_By, Modified_On, Modified_By)
                       VALUES
                       ('%s', '%s', '%s', '%s', %d, '%s', %d)",
                       mysql_real_escape_string($this->Title),
                       mysql_real_escape_string($this->Description),
                       mysql_real_escape_string($this->ResultantPrefix),
                       mysql_real_escape_string(now()),
                       mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
                       mysql_real_escape_string(now()),
                       mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
        $data = new DataQuery($sql);
        $this->ID = $data->InsertID;
    }

    function Update($id=null){
        if(!is_null($id)) $this->ID = $id;
        $sql = sprintf("UPDATE `return_reason` SET
                        Reason_Title='{$this->Title}',
                        Reason_Desc='{$this->Description}',
                        Resultant_Prefix='{$this->ResultantPrefix}',
                        Created_On='{$this->CreatedOn}',
                        Created_By={$this->CreatedBy},
                        Modified_On='%s',
                        Modified_By=%d
                        WHERE Reason_ID={$this->ID}",
                        mysql_real_escape_string(now()), 
                        mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
        $data = new DataQuery($sql);
    }

    function Delete($id=NULL){
        if(!is_null($id)) $this->ID = $id;

        if(!is_numeric($this->ID)){
            return false;
        }
        $sql = "delete from `return_reason` where Reason_ID=" . mysql_real_escape_string($this->ID);
        $data = new DataQuery($sql);
    }
}