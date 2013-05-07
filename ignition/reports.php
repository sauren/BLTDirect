<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Report.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');

if($action == 'add') {
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update') {
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'remove') {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'open') {
	$session->Secure(3);
	open();
	exit;
} elseif($action == 'load') {
	$session->Secure(3);
	load();
	exit;
} elseif($action == 'clear') {
	$session->Secure(3);
	clear();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
		$report = new Report();
		$report->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add() {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('name', 'Name', 'text', '', 'anything', 1, 120);
	$form->AddField('reference', 'Reference', 'text', '', 'anything', 1, 120);
	$form->AddField('script', 'Script', 'text', '', 'link_relative', 1, 240);
	$form->AddField('interval', 'Interval (Days)', 'text', '', 'numeric_unsigned', 1, 11, false);
	$form->AddField('threshold', 'Threshold (Hour)', 'text', '', 'numeric_unsigned', 1, 2, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if($form->GetValue('threshold') > 23) {
				$form->AddError('Threshold cannot be higher than 23.', 'threshold');	
			}
			
			if($form->Valid) {
				$report = new Report();
				$report->Name = $form->GetValue('name');
				$report->Reference = $form->GetValue('reference');
				$report->Script = $form->GetValue('script');
				$report->Interval = $form->GetValue('interval');
				$report->Threshold = $form->GetValue('threshold');
				$report->Add();
				
				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			}
		}
	}

	$page = new Page('<a href="reports.php">Reports</a> &gt; Add Report', 'Add a new report to this system.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Report');
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference') . $form->GetIcon('reference'));
	echo $webForm->AddRow($form->GetLabel('script'), $form->GetHTML('script') . $form->GetIcon('script'));
	echo $webForm->AddRow($form->GetLabel('interval'), $form->GetHTML('interval') . $form->GetIcon('interval'));
	echo $webForm->AddRow($form->GetLabel('threshold'), $form->GetHTML('threshold') . $form->GetIcon('threshold'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'reports.php\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$report = new Report();
	
	if(!isset($_REQUEST['id']) || !$report->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: reports.php"));
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Report ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('name', 'Name', 'text', $report->Name, 'anything', 1, 120);
	$form->AddField('reference', 'Reference', 'text', $report->Reference, 'anything', 1, 120);
	$form->AddField('script', 'Script', 'text', $report->Script, 'link_relative', 1, 240);
	$form->AddField('interval', 'Interval (Days)', 'text', $report->Interval, 'numeric_unsigned', 1, 11, false);
	$form->AddField('threshold', 'Threshold (Hour)', 'text', $report->Threshold, 'numeric_unsigned', 1, 2, false);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if($form->GetValue('threshold') > 23) {
				$form->AddError('Threshold cannot be higher than 23.', 'threshold');	
			}
			
			if($form->Valid) {
				$report->Name = $form->GetValue('name');
				$report->Reference = $form->GetValue('reference');
				$report->Script = $form->GetValue('script');
				$report->Interval = $form->GetValue('interval');
				$report->Threshold = $form->GetValue('threshold');
				$report->Update();
				
				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			}
		}
	}

	$page = new Page('<a href="reports.php">Reports</a> &gt; Update Report', 'Update an existing report within this system.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update Report');
	$webForm = new StandardForm();
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
	echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference') . $form->GetIcon('reference'));
	echo $webForm->AddRow($form->GetLabel('script'), $form->GetHTML('script') . $form->GetIcon('script'));
	echo $webForm->AddRow($form->GetLabel('interval'), $form->GetHTML('interval') . $form->GetIcon('interval'));
	echo $webForm->AddRow($form->GetLabel('threshold'), $form->GetHTML('threshold') . $form->GetIcon('threshold'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'reports.php\';" /> <input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function cache($reportScript) {
	include($reportScript);
}

function open() {
	$report = new Report();
	
	if(!isset($_REQUEST['id']) || !$report->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: reports.php"));
	}
	
	$cacheItems = array();
	
	$data = new DataQuery(sprintf("SELECT ReportCacheID, IsOnDemand, CreatedOn FROM report_cache WHERE ReportID=%d ORDER BY CreatedOn DESC", mysql_real_escape_string($report->ID)));
	while($data->Row) {
		$cacheItems[] = $data->Row;
		
		$data->Next();
	}
	$data->Disconnect();

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'open', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Report ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('cache', 'Report Data', 'select', (count($cacheItems) > 0) ? $cacheItems[0]['ReportCacheID'] : '', 'anything', 1, 11, false);
	$form->AddGroup('cache', 'Y', 'On Demand Reports');
	$form->AddGroup('cache', 'N', 'Automatic Reports');
	
	foreach($cacheItems as $cacheItem) {
		$form->AddOption('cache', $cacheItem['ReportCacheID'], cDatetime($cacheItem['CreatedOn'], 'shortdatetime'), $cacheItem['IsOnDemand']);
	}

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$cacheId = $form->GetValue('cache');
			
			if(isset($_REQUEST['newreport'])) {
				cache(sprintf('%slib/reports/%s', $GLOBALS['DIR_WS_ADMIN'], $report->Script));
				
				$data = new DataQuery(sprintf("SELECT ReportCacheID FROM report_cache WHERE ReportID=%d ORDER BY CreatedOn DESC LIMIT 0, 1", mysql_real_escape_string($report->ID)));
				if($data->TotalRows > 0) {
					$cacheId =  $data->Row['ReportCacheID'];
				}
				$data->Disconnect();
			}
			
			if($cacheId == 0) {
				$form->AddError('Report Data must have a selected value.', 'cache');
			}
			
			if($form->Valid) {
				redirect(sprintf("Location: report_%s?id=%d", $report->Script, $cacheId));
			}
		}
	}
	
	$script = sprintf('<script language="javascript" type="text/javascript">
		var clearCache = function() {
			var e = document.getElementById(\'cache\');
			
			if(e) {
				window.self.location.href = \'%s?action=clear&id=%d&cache=\' + e.value;
			}
		}
		</script>', $_SERVER['PHP_SELF'], $form->GetValue('id'));
	
	$page = new Page('<a href="reports.php">Reports</a> &gt; Open Report', 'Open this report using cached data.');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow(sprintf('%s Report', $report->Name));
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	
	echo $window->Open();
	
	echo $window->AddHeader('Select cached report data to view.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('Report', $report->Name);
	echo $webForm->AddRow($form->GetLabel('cache'), $form->GetHTML('cache') . ' <a href="javascript:clearCache();"><img src="images/icon_cross_3.gif" alt="Clear" border="0" /></a>');
	echo $webForm->AddRow('', '<input type="submit" name="openreport" value="open report" class="btn" />');
	echo $webForm->Close();
	
	echo $window->AddHeader('Create an on demand report and cache.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('', '<input type="submit" name="newreport" value="new report" class="btn" />');
	echo $webForm->Close();
	
	echo $window->CloseContent();
	
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function load() {
	$report = new Report();
	
	if(!isset($_REQUEST['id']) || !$report->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: reports.php"));
	}
	
	$data = new DataQuery(sprintf("SELECT ReportCacheID, CreatedOn FROM report_cache WHERE ReportID=%d ORDER BY CreatedOn DESC LIMIT 0, 1", $report->ID));
	if($data->TotalRows > 0) {
		redirect(sprintf("Location: report_%s?id=%d", $report->Script, $data->Row['ReportCacheID']));
	}
	$data->Disconnect();
	
	redirect(sprintf("Location: reports.php"));
}

function clear() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReportCache.php');
	
	if(isset($_REQUEST['cache']) && is_numeric($_REQUEST['cache'])) {
		$cache = new ReportCache();
		$cache->Delete($_REQUEST['cache']);
	}

	redirect(sprintf("Location: %s?action=open&id=%d", $_SERVER['PHP_SELF'], $_REQUEST['id']));
}

function view() {
	$page = new Page('Reports', 'Listing all available reports.');
	$page->Display('header');

	$table = new DataTable('reports');
	$table->SetSQL("SELECT r.*, IF(r.`Interval`>0, CONCAT_WS(' ', r.`Interval`, 'day(s)'), '') AS IntervalText, CONCAT(IF(r.Threshold>9, r.Threshold, CONCAT('0', r.Threshold)), ':00') AS ThresholdText, COUNT(rc.ReportCacheID) AS ReportsCached FROM report AS r LEFT JOIN report_cache AS rc ON rc.ReportID=r.ReportID GROUP BY r.ReportID");
	$table->AddField('Reports Cached', 'ReportsCached', 'hidden');
	$table->AddField('ID#', 'ReportID', 'right');
	$table->AddField('Name', 'Name', 'left');
	$table->AddField('Reference', 'Reference', 'left');
	$table->AddField('Script', 'Script', 'left');
	$table->AddField('Interval', 'IntervalText', 'right');
	$table->AddField('Threshold', 'ThresholdText', 'right');
	$table->SetMaxRows(25);
	$table->SetOrderBy("Name");
	$table->AddLink('reports.php?action=update&id=%s', '<img src="images/icon_edit_1.gif" alt="Update" border="0" />', 'ReportID');
	$table->AddLink('reports.php?action=load&id=%s', '<img src="images/folderopen.gif" alt="Load" border="0" />', 'ReportID', true, false, array('ReportsCached', '>', 0));
	$table->AddLink('reports.php?action=open&id=%s', '<img src="images/icon_view_1.gif" alt="Open" border="0" />', 'ReportID');
	$table->AddLink('javascript:confirmRequest(\'reports.php?action=remove&id=%s\', \'Are you sure you want to remove this item?\');', '<img src="images/aztector_6.gif" alt="Remove" border="0" />', 'ReportID');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo '<input type="button" name="add" value="add report" class="btn" onclick="window.location.href=\'reports.php?action=add\'" />';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}