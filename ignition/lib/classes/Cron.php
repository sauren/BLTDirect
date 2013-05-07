<?php
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');

class Cron {
	const LOG_LEVEL_NONE = 0x00;
	const LOG_LEVEL_ERROR = 0x01;
	const LOG_LEVEL_WARNING = 0x02;
	const LOG_LEVEL_INFO = 0x03;

	private $log;
	private $timingStart;
	private $timingEnd;
	private $complete;
	public $mailLogLevel;
	public $scriptName;
	public $scriptFileName;

	public function __construct() {
		$this->log = array();
		$this->mailLogLevel = self::LOG_LEVEL_NONE;
		$this->timingStart = microtime(true);
		$this->timingEnd = 0;
		$this->complete = false;
	}

	public function log($message, $level = self::LOG_LEVEL_INFO) {
		$this->log[] = array('Message' => $message, 'Level' => $level);
	}

	public function execute() {
		$this->timingEnd = microtime(true);
		$this->complete = true;

		if($this->mailLogLevel > self::LOG_LEVEL_NONE) {
			$this->mailLog($this->mailLogLevel);
		}
	}

	public function output() {
		if($this->complete) {
			$log = array();

			for($i=0; $i<count($this->log); $i++) {
				$log[] = sprintf('&lt;%s&gt; %s', $this->getLogLevel($this->log[$i]['Level']), $this->log[$i]['Message']);
			}

			$log = array_merge($this->getLogPrefix(), $log);
			$output = implode('<br />', $log);

			echo $output;
		}
	}

	private function getLogPrefix() {
		$log = array();

		if($this->complete) {
			$log[] = sprintf("Script: %s", $this->scriptName);
			$log[] = sprintf("File Name: %s", $this->scriptFileName);
			$log[] = sprintf("Date Executed: %s", date('Y-m-d H:i:s'));
			$log[] = sprintf("Execution Time: %s seconds", number_format($this->timingEnd - $this->timingStart, 4, '.', ''));
			$log[] = '';
		}

		return $log;
	}

	public function getLogLevel($logLevel) {
		$level = '';

		switch($logLevel) {
			case self::LOG_LEVEL_ERROR:
				$level = 'Error';
				break;
			case self::LOG_LEVEL_WARNING:
				$level = 'Warning';
				break;
			case self::LOG_LEVEL_INFO:
				$level = 'Info';
				break;
		}

		return $level;
	}

	private function mailLog($logLevel) {
		$log = array();

		for($i=0; $i<count($this->log); $i++) {
			if($this->log[$i]['Level'] <= $logLevel) {
				$log[] = sprintf('<%s> %s', $this->getLogLevel($this->log[$i]['Level']), $this->log[$i]['Message']);
			}
		}

		if(count($log) > 0) {
			$log = array_merge($this->getLogPrefix(), $log);
			$level = $this->getLogLevel($logLevel);

			$mail = new htmlMimeMail5();
			$mail->setFrom('root@bltdirect.com');
			$mail->setSubject(sprintf("Cron%s [%s] <root@bltdirect.com> php /var/www/vhosts/bltdirect.com/httpdocs/cron/%s", !empty($level) ? sprintf(' %s', $level) : $level, $this->scriptName, $this->scriptFileName));
			$mail->setText(implode("\n", $log));
			$mail->send(array('adam@azexis.com'));
		}
	}
}