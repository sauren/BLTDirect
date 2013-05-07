<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');

class Report {
	public $ID;
	public $Name;
	public $Reference;
	public $Script;
	public $Interval;
	public $Threshold;
	public $CreatedOn;
	public $CreatedBy;
	public $ModifiedOn;
	public $ModifiedBy;

	public function __construct($id=NULL) {
		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM report WHERE ReportID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Name = $data->Row['Name'];
			$this->Reference = $data->Row['Reference'];
			$this->Script = $data->Row['Script'];
			$this->Interval = $data->Row['Interval'];
			$this->Threshold = $data->Row['Threshold'];
			$this->CreatedOn = $data->Row['CreatedOn'];
			$this->CreatedBy = $data->Row['CreatedBy'];
			$this->ModifiedOn = $data->Row['ModifiedOn'];
			$this->ModifiedBy = $data->Row['ModifiedBy'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function GetByReference($reference=NULL) {
		if(!is_null($reference)) {
			$this->Reference = $reference;
		}

		$data = new DataQuery(sprintf("SELECT ReportID FROM report WHERE Reference LIKE '%s'", mysql_real_escape_string($this->Reference)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['ReportID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$data = new DataQuery(sprintf("INSERT INTO report (Name, Reference, Script, `Interval`, Threshold, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES ('%s', '%s', '%s', %d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Reference), mysql_real_escape_string($this->Script), mysql_real_escape_string($this->Interval), mysql_real_escape_string($this->Threshold), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	public function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE report SET Name='%s', Reference='%s', Script='%s', `Interval`=%d, Threshold=%d, ModifiedOn=NOW(), ModifiedBy=%d WHERE ReportID=%d", mysql_real_escape_string($this->Name), mysql_real_escape_string($this->Reference), mysql_real_escape_string($this->Script), mysql_real_escape_string($this->Interval), mysql_real_escape_string($this->Threshold), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		$data = new DataQuery(sprintf("SELECT ReportCacheID FROM report_cache WHERE ReportID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$cache = new ReportCache();
			$cache->Delete($data->Row['ReportCacheID']);
			
			$data->Next();
		}
		$data->Disconnect();

		new DataQuery(sprintf("DELETE FROM report WHERE ReportID=%d", mysql_real_escape_string($this->ID)));
	}
}