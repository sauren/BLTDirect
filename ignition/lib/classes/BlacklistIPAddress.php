<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class BlacklistIPAddress {
	var $ID;
	var $IPAddressFrom;
	var $IPAddressTo;
	var $Reason;
	var $CreatedBy;
	var $CreatedOn;
	var $ModifiedBy;
	var $ModifiedOn;

	function BlacklistIPAddress($id = null) {
		$this->IPAddressFrom = '0.0.0.0';
		$this->IPAddressTo = '0.0.0.0';

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	function Get($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT INET_NTOA(IP_Address_From) AS IP_Address_From, INET_NTOA(IP_Address_To) AS IP_Address_To, Reason, Created_On, Created_By, Modified_On, Modified_By FROM blacklist_ip_address WHERE Blacklist_IP_Address_ID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0){
			$this->IPAddressFrom = $data->Row['IP_Address_From'];
			$this->IPAddressTo = $data->Row['IP_Address_To'];
			$this->Reason = $data->Row['Reason'];
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
		$data = new DataQuery(sprintf("INSERT INTO blacklist_ip_address (IP_Address_From, IP_Address_To, Reason, Created_On, Created_By, Modified_On, Modified_By) VALUES (INET_ATON('%s'), INET_ATON('%s'), '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->IPAddressFrom), mysql_real_escape_string($this->IPAddressTo), mysql_real_escape_string($this->Reason), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
		$this->ID = $data->InsertID;
		$data->Disconnect();
	}

	function Update() {
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("UPDATE blacklist_ip_address SET IP_Address_From=INET_ATON('%s'), IP_Address_To=INET_ATON('%s'), Reason='%s', Modified_On=NOW(), Modified_By=%d WHERE Blacklist_IP_Address_ID=%d", mysql_real_escape_string($this->IPAddressFrom), mysql_real_escape_string($this->IPAddressTo), mysql_real_escape_string($this->Reason), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function Delete($id = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("DELETE FROM blacklist_ip_address WHERE Blacklist_IP_Address_ID=%d", mysql_real_escape_string($this->ID)));
		$data->Disconnect();
	}

	function SetIPAddress($addressA, $addressB = null) {
		if(is_null($addressB)) {
			$this->IPAddressFrom = $addressA;
			$this->IPAddressTo = $addressA;
		} else {
			$this->IPAddressFrom = $addressA;
			$this->IPAddressTo = $addressB;
		}
	}

	function Validate() {
		$ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

		$data = new DataQuery(sprintf("SELECT Reason FROM blacklist_ip_address WHERE IP_Address_From>=INET_ATON('%s') AND IP_Address_To<=INET_ATON('%s')", mysql_real_escape_string($ipAddress), mysql_real_escape_string($ipAddress)));
		if($data->TotalRows > 0) {
			echo sprintf('<p><strong>Blacklisted IP Address:</strong> %s<br />Please call %s on %s.</p>', $ipAddress, $GLOBALS['COMPANY'], $GLOBALS['COMPANY_PHONE']);
			echo sprintf('<p><u>IP Address blacklisted for the following reasons:</u></p>');

			while($data->Row) {
				echo sprintf('<p>%s</p>', nl2br($data->Row['Reason']));

				$data->Next();
			}

			$data->Disconnect();

			$GLOBALS['DBCONNECTION']->Close();
			exit;
		}
		$data->Disconnect();
	}

	function IsBlacklisted($ipAddress) {
		$data = new DataQuery(sprintf("SELECT Reason FROM blacklist_ip_address WHERE INET_ATON('%s') BETWEEN IP_Address_From AND IP_Address_To", mysql_real_escape_string($ipAddress)));
		if($data->TotalRows > 0) {
			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
}
?>