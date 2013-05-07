<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/DataQuery.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Timesheet.php");

class PublicHoliday {
	var $ID;
	var $Title;
	var $HolidayDate;

	function PublicHoliday($id = null) {
		if(!is_null($id)) {
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

		$data = new DataQuery(sprintf("SELECT * FROM public_holiday WHERE Public_Holiday_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Title = $data->Row['Title'];
			$this->HolidayDate = $data->Row['Holiday_Date'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO public_holiday (Title, Holiday_Date) VALUES ('%s', '%s')", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->HolidayDate)));

		$this->ID = $data->InsertID;

		$this->Recalculate();
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE public_holiday SET Title='%s', Holiday_Date='%s' WHERE Public_Holiday_ID=%d", mysql_real_escape_string($this->Title), mysql_real_escape_string($this->HolidayDate), mysql_real_escape_string($this->ID)));

		$this->Recalculate();
	}

    function Recalculate($userId = 0) {
    	if(!is_numeric($this->ID)){
			return false;
		}
    	new DataQuery(sprintf("DELETE FROM timesheet WHERE Public_Holiday_ID=%d%s", mysql_real_escape_string($this->ID), ($userId > 0) ? sprintf(' AND User_ID=%d', $userId) : ''));

		$data = new DataQuery(sprintf("SELECT User_ID FROM users WHERE Is_Casual_Worker='N'%s ORDER BY User_ID ASC", ($userId > 0) ? sprintf(' AND User_ID=%d', $userId) : ''));
		while($data->Row) {
			$timesheet = new Timesheet();
            $timesheet->User->Get($data->Row['User_ID']);
            $timesheet->Type = 'Holiday';
			$timesheet->Date = $this->HolidayDate;
			$timesheet->Hours = $timesheet->User->Hours;
			$timesheet->PublicHoliday->ID = $this->ID;
			$timesheet->Add();

			$data->Next();
		}
		$data->Disconnect();
	}

	function Delete($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM public_holiday WHERE Public_Holiday_ID=%d", mysql_real_escape_string($this->ID)));
		Timesheet::DeletePublicHoliday($this->ID);
	}
}