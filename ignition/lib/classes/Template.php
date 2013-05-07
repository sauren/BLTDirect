<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Channel.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

class Template {
	public $ID;
	public $Channel;
	public $Reference;
	public $Content;

	public function __construct($id = null) {
		$this->Channel = new Channel();

		if(!is_null($id)) {
			$this->Get($id);
		}
	}

	public function Get($id = null) {
		if(!is_null($id)) {
			$this->ID = $id;
		}


		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM template WHERE TemplateID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->Channel->ID = $data->Row['ChannelID'];
			$this->Reference = $data->Row['Reference'];
			$this->Content = $data->Row['Content'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	public function Add(){
		$data = new DataQuery(sprintf("INSERT INTO template (ChannelID, Reference, Content) VALUES (%d, '%s', '%s')", mysql_real_escape_string($this->Channel->ID), mysql_real_escape_string($this->Reference), mysql_real_escape_string($this->Content)));

		$this->ID = $data->InsertID;
	}

	public function Update(){

		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE template SET ChannelID=%d, Reference='%s', Content='%s' WHERE TemplateID=%d", mysql_real_escape_string($this->Channel->ID), mysql_real_escape_string($this->Reference), mysql_real_escape_string($this->Content), mysql_real_escape_string($this->ID)));
	}

	public function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM template WHERE TemplateID=%d", mysql_real_escape_string($this->ID)));
	}

    public static function GetContent($reference, $channelId = 0) {
		$content = '';

		$data = new DataQuery(sprintf("SELECT Content FROM template WHERE Reference LIKE '%s' AND (ChannelID=0 OR ChannelID=%d) ORDER BY ChannelID DESC LIMIT 0, 1", mysql_real_escape_string($reference), mysql_real_escape_string($channelId)));
		if($data->TotalRows > 0) {
			$content = $data->Row['Content'];
		}
		$data->Disconnect();

		return $content;
	}
}