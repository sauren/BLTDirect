<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Report.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/IFile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Cipher.php');

class ReportCache {
	public $ID;
	public $Report;
	public $IsOnDemand;
	public $File;
	public $CreatedOn;
	public $CreatedBy;
	private $Data;

	public function __construct($id=NULL) {
		$this->Report = new Report();
		$this->IsOnDemand = 'N';

		$this->File = new IFile();
		$this->File->OnConflict = 'makeunique';
		$this->File->Extensions = '';
		$this->File->SetDirectory(sprintf('%slocal/reports/', $GLOBALS['DATA_DIR_FS']));

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	public function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$data = new DataQuery(sprintf("SELECT * FROM report_cache WHERE ReportCacheID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Report->ID = $data->Row['ReportID'];
			$this->IsOnDemand = $data->Row['IsOnDemand'];
			$this->File->SetName($data->Row['FileName']);
			$this->CreatedOn = $data->Row['CreatedOn'];
			$this->CreatedBy = $data->Row['CreatedBy'];

			$this->Data = file_get_contents(sprintf('%s%s', $this->File->Directory, $this->File->FileName));

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function GetMostRecent() {
		$data = new DataQuery(sprintf("SELECT MAX(ReportCacheID) AS ReportCacheID FROM report_cache WHERE ReportID=%d", mysql_real_escape_string($this->Report->ID)));
		if($data->TotalRows > 0) {
			$return = $this->Get($data->Row['ReportCacheID']);

			$data->Disconnect();
			return $return;
		}

		$data->Disconnect();
		return false;
	}

	public function Add() {
		$this->File->SetName(md5(sprintf('cache_%d_%s', $this->Report->ID, date('Ymd_His'))));

		$fh = fopen(sprintf('%s%s', $this->File->Directory, $this->File->FileName), 'w');
		fwrite($fh, $this->Data);
		fclose($fh);

		if(php_sapi_name() != 'cli') {
			$this->IsOnDemand = 'Y';
		}

		$data = new DataQuery(sprintf("INSERT INTO report_cache (ReportID, IsOnDemand, FileName, CreatedOn, CreatedBy) VALUES (%d, '%s', '%s', NOW(), %d)", mysql_real_escape_string($this->Report->ID), mysql_real_escape_string($this->IsOnDemand), mysql_real_escape_string($this->File->FileName), $GLOBALS['SESSION_USER_ID']));

		$this->ID = $data->InsertID;
	}

	public function Delete($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(empty($this->File->FileName)) {
			$this->Get();
		}

		if(!empty($this->File->FileName) && $this->File->Exists()) {
			$this->File->Delete();
		}
		
		new DataQuery(sprintf("DELETE FROM report_cache WHERE ReportCacheID=%d", mysql_real_escape_string($this->ID)));
	}

	public function SetData($data) {
		$cypher = new Cipher(serialize($data));
		$cypher->Encrypt();

		$this->Data = $cypher->Value;
	}

	public function GetData() {
		$cypher = new Cipher($this->Data);
		$cypher->Decrypt();

		return unserialize($cypher->Value);
	}
}