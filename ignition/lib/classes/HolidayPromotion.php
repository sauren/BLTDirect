<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Setting.php');

class HolidayPromotion {
	public $Halloween;
	public $Christmas;

	public function __construct(){
		$this->GetHalloween();
		$this->GetChristmas();
	}

	public function GetHalloween(){
		$this->Halloween = new StdClass;

		$start = Setting::GetValue('halloween_promotion_startdate');
		$end = Setting::GetValue('halloween_promotion_enddate');

		$startParams = explode('-', $start);
		$endParams = explode('-', $end);

		$startingYear = date('Y') > $startParams[0] ? date('Y') : $startParams[0];
		$endingYear = date('Y') > $endParams[0] ? date('Y') : $endParams[0];

		$this->Halloween->Start = mktime(0, 0, 0, $startParams[1], $startParams[2], $startingYear);
		$this->Halloween->End = mktime(0, 0, 0, $endParams[1], $endParams[2], $endingYear) > $this->Halloween->Start ? mktime(0, 0, 0, $endParams[1], $endParams[2], $endingYear) : mktime(0, 0, 0, $endParams[1], $endParams[2] + 1, $endingYear);
	}

	public function IsHalloween(){
		if(time() >= $this->Halloween->Start && time() < $this->Halloween->End){
			return true;
		}
		return false;
	}

	public function GetChristmas(){
		$this->Christmas = new StdClass;

		$start = Setting::GetValue('christmas_promotion_startdate');
		$end = Setting::GetValue('christmas_promotion_enddate');

		$startParams = explode('-', $start);
		$endParams = explode('-', $end);

		$startingYear = date('Y') > $startParams[0] ? date('Y') : $startParams[0];
		$endingYear = date('Y') > $endParams[0] ? date('Y') : $endParams[0];

		$this->Christmas->Start = mktime(0, 0, 0, $startParams[1], $startParams[2], $startingYear);
		$this->Christmas->End = mktime(0, 0, 0, $endParams[1], $endParams[2], $endingYear) > $this->Christmas->Start ? mktime(0, 0, 0, $endParams[1], $endParams[2], $endingYear) : mktime(0, 0, 0, $endParams[1], $endParams[2] + 1, $endingYear);
	}

	public function IsChristmas(){
		if(time() >= $this->Christmas->Start && time() < $this->Christmas->End){
			return true;
		}
		return false;
	}
}