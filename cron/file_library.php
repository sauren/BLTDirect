<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Cron.php');

$cron = new Cron();
$cron->scriptName = 'File Library';
$cron->scriptFileName = 'file_library.php';
$cron->mailLogLevel = Cron::LOG_LEVEL_NONE;

## BEGIN SCRIPT
$files = array();

$dir = $GLOBALS["FILE_DIR_FS"];

if($handle = opendir($dir)) {
	while(false !== ($file = readdir($handle))) {
	    if(!is_dir($dir.$file)) {
	        $files[] = $file;
	    }
	}

	closedir($handle);
}

$dataFiles = array();

$data = new DataQuery(sprintf("SELECT SRC FROM library_file"));
while($data->Row) {
	$dataFiles[] = strtolower($data->Row['SRC']);

	$data->Next();	
}
$data->Disconnect();
	
foreach($files as $file) {
	if(strtolower($file) != 'placeholder') {
		$found = false;
		
		foreach($dataFiles as $dataFile) {
			if(strtolower($file) == $dataFile) {
				$found = true;
				break;
			}
		}
		
		if(!$found) {
			$items = explode('.', $file);

			$data2 = new DataQuery(sprintf("INSERT INTO library_file (File_Type_ID, Title, Description, SRC, Created_On, Created_By, Modified_On, Modified_By) VALUES (0, '%s', '', '%s', NOW(), 0, NOW(), 0)", mysql_real_escape_string($items[0]), mysql_real_escape_string($file)));
			
			$cron->log(sprintf('Inserting File: #%d, File Name: %s', $data2->InsertID, $file), Cron::LOG_LEVEL_INFO);
		}
	}
}
## END SCRIPT

$cron->execute();
$cron->output();

$GLOBALS['DBCONNECTION']->Close();