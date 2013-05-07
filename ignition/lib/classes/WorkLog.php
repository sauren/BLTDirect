<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

class WorkLog {
	public $id;
	public $type;
	public $log;
	public $createdOn;
	public $createdBy;
	public $modifiedOn;
	public $modifiedBy;
	
	public function __construct($id = null) {
		if(!is_null($id)) {
			$this->get($id);
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM work_log WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->TotalRows > 0) {
			foreach($data->Row as $key=>$value) {
				$this->$key = $value;
			}
			
            $data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO work_log (type, log, createdOn, createdBy, modifiedOn, modifiedBy) VALUES ('%s', '%s', NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->type), mysql_real_escape_string($this->log), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->id = $data->InsertID;
		
		$this->email();
	}
	
	public function update() {
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE work_log SET type='%s', log='%s', modifiedOn=NOW(), modifiedBy=%d WHERE id=%d", mysql_real_escape_string($this->type), mysql_real_escape_string($this->log), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->id)));
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}
		
		new DataQuery(sprintf("DELETE FROM work_log WHERE id=%d", mysql_real_escape_string($this->id)));
	}
	
	public function email() {
		$this->get();
		
		$createdBy = new User();
		$createdBy->Get($this->createdBy);
		
		$findReplace = new FindReplace();
		$findReplace->Add('/\[WORKLOG_TYPE\]/', $this->type);
		$findReplace->Add('/\[WORKLOG_LOG\]/', $this->log);
		$findReplace->Add('/\[WORKLOG_CREATED_ON\]/', cDatetime($this->createdOn, 'shortdate'));
		$findReplace->Add('/\[WORKLOG_CREATED_BY\]/', $createdBy->Person->GetFullName());

		$customHtml = $findReplace->Execute(Template::GetContent('email_work_log'));

		$findReplace = new FindReplace();
		$findReplace->Add('/\[NAME\]/', 'Administrator');
		$findReplace->Add('/\[BODY\]/', $customHtml);

		$standardTemplate = file($GLOBALS["DIR_WS_ADMIN"] . 'lib/templates/email/template_standard.tpl');
		$standardHtml = '';
		
		for($i=0; $i<count($standardTemplate); $i++){
			$standardHtml .= $findReplace->Execute($standardTemplate[$i]);
		}
		
		$queue = new EmailQueue();
		$queue->GetModuleID('work');
		$queue->Priority = 'H';
		$queue->ToAddress = 'steve@bltdirect.com';
		$queue->Subject = sprintf('%s - Work Log (%s)', $GLOBALS['COMPANY'], $this->type);
		$queue->Body = $standardHtml;
		$queue->Add();
	}
}