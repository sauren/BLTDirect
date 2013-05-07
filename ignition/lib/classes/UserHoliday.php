<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Timesheet.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/FindReplace.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Setting.php");

class UserHoliday {
	var $ID;
	var $User;
	var $StartDate;
	var $StartMeridiem;
	var $EndDate;
	var $EndMeridiem;
	var $Notes;
	var $Status;
	var $ApprovedOn;
	var $ApprovedBy;
	var $DeclinedBecause;
	var $DeclinedOn;
	var $DeclinedBy;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	function __construct($id=NULL) {
		$this->User = new User();
		$this->StartDate = '0000-00-00 00:00:00';
		$this->StartMeridiem = 'AM';
		$this->EndDate = '0000-00-00 00:00:00';
		$this->EndMeridiem = 'AM';
		$this->ApprovedOn = '0000-00-00 00:00:00';
		$this->DeclinedOn = '0000-00-00 00:00:00';

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM user_holiday WHERE User_Holiday_ID=%d", mysql_real_escape_string($this->ID)));

		if($data->TotalRows > 0){
			$this->User->ID = $data->Row['User_ID'];
			$this->StartDate = $data->Row['Start_Date'];
			$this->StartMeridiem = $data->Row['Start_Meridiem'];
			$this->EndDate = $data->Row['End_Date'];
			$this->EndMeridiem = $data->Row['End_Meridiem'];
			$this->Notes = $data->Row['Notes'];
			$this->Status = $data->Row['Status'];
			$this->ApprovedOn = $data->Row['Approved_On'];
			$this->ApprovedBy = $data->Row['Approved_By'];
			$this->DeclinedBecause = $data->Row['Declined_Because'];
			$this->DeclinedOn = $data->Row['Declined_On'];
			$this->DeclinedBy = $data->Row['Declined_By'];
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

	function Add() {
		$data = new DataQuery(sprintf("INSERT INTO user_holiday (User_ID, Start_Date, Start_Meridiem, End_Date, End_Meridiem, Notes, Status, Approved_On, Approved_By, Declined_Because, Declined_On, Declined_By, Created_On, Created_By, Modified_On, Modified_By) VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->User->ID), mysql_real_escape_string($this->StartDate), mysql_real_escape_string($this->StartMeridiem), mysql_real_escape_string($this->EndDate), mysql_real_escape_string($this->EndMeridiem), mysql_real_escape_string($this->Notes), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->ApprovedOn), mysql_real_escape_string($this->ApprovedBy), mysql_real_escape_string($this->DeclinedBecause), mysql_real_escape_string($this->DeclinedOn), mysql_real_escape_string($this->DeclinedBy), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;

		$this->Recalculate();
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE user_holiday SET User_ID=%d, Start_Date='%s', Start_Meridiem='%s', End_Date='%s', End_Meridiem='%s', Notes='%s', Status='%s', Approved_On='%s', Approved_By=%d, Declined_Because='%s', Declined_On='%s', Declined_By=%d, Modified_On=NOW(), Modified_By=%d WHERE User_Holiday_ID=%d", mysql_real_escape_string($this->User->ID), mysql_real_escape_string($this->StartDate), mysql_real_escape_string($this->StartMeridiem), mysql_real_escape_string($this->EndDate), mysql_real_escape_string($this->EndMeridiem), mysql_real_escape_string($this->Notes), mysql_real_escape_string($this->Status), mysql_real_escape_string($this->ApprovedOn), mysql_real_escape_string($this->ApprovedBy), mysql_real_escape_string($this->DeclinedBecause), mysql_real_escape_string($this->DeclinedOn), mysql_real_escape_string($this->DeclinedBy), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));

		$this->Recalculate();
	}

	function Recalculate() {
		if(!is_numeric($this->ID)){
			return false;
		}
		$data = new DataQuery(sprintf("DELETE FROM timesheet WHERE User_Holiday_ID=%d", mysql_real_escape_string($this->ID)));

		if($this->Status == 'Approved') {
			$this->User->Get();
			 
			$dates = array();

			$startDate = strtotime($this->StartDate);
			$endDate = strtotime($this->EndDate);
			$tempDate = strtotime($this->StartDate);

			while($tempDate <= $endDate) {
				if($startDate == $endDate) {
					$hours = $this->User->Hours;

					if($this->StartMeridiem == $this->EndMeridiem) {
						$hours /= 2;
					}
				} else {
	                if($tempDate == $startDate) {
						$hours = $this->User->Hours;

						if($this->StartMeridiem == 'PM') {
							$hours /= 2;
						}
					} elseif($tempDate == $endDate) {
						$hours = $this->User->Hours;

						if($this->EndMeridiem == 'AM') {
							$hours /= 2;
						}
					} else {
						$hours = $this->User->Hours;
					}
				}

				$dates[date('Y-m-d H:i:s', $tempDate)] = $hours;

				$tempDate += 86400;
			}

			foreach($dates as $date=>$hours) {
		        $timesheet = new Timesheet();
		        $timesheet->User->ID = $this->User->ID;
		        $timesheet->Type = 'Holiday';
				$timesheet->Date = $date;
				$timesheet->Hours = $hours;
				$timesheet->UserHoliday->ID = $this->ID;
				$timesheet->Add();
			}
		}
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM user_holiday WHERE User_Holiday_ID=%d", mysql_real_escape_string($this->ID)));
		new DataQuery(sprintf("DELETE FROM timesheet WHERE User_Holiday_ID=%d", mysql_real_escape_string($this->ID)));
	}
	
	function Approve() {
		$this->Status = 'Approved';
		$this->ApprovedOn = date('Y-m-d H:i:s');
		$this->ApprovedBy = $GLOBALS['SESSION_USER_ID'];
		$this->Update();
	}
	
	function Decline($reason) {
		$this->Status = 'Declined';
		$this->DeclinedBecause = $reason;
		$this->DeclinedOn = date('Y-m-d H:i:s');
		$this->DeclinedBy = $GLOBALS['SESSION_USER_ID'];
		$this->Update();
	}
}