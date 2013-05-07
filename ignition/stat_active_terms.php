<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

$term = isset($_REQUEST['term']) ? $_REQUEST['term'] : '';
$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : '0000-00-00 00:00:00';
$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : '0000-00-00 00:00:00';

$displayStart = ($start != '0000-00-00 00:00:00') ? sprintf('%s/%s/%s', substr($start, 8, 2), substr($start, 5, 2), substr($start, 0, 4)) : '';
$displayEnd = ($end != '0000-00-00 00:00:00') ? sprintf('%s/%s/%s', substr($end, 8, 2), substr($end, 5, 2), substr($end, 0, 4)) : '';

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('start', 'Start', 'hidden', $start, 'anything', 19, 19);
$form->AddField('end', 'End', 'hidden', $end, 'anything', 19, 19);
$form->AddField('startDate', 'Start Date', 'text', $displayStart, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('endDate', 'End Date', 'text', $displayEnd, 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('term', 'Search Term', 'text', '', 'anything', 0, 255, false);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$start = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('startDate'), 6, 4), substr($form->GetValue('startDate'), 3, 2), substr($form->GetValue('startDate'), 0, 2));
		$end = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('endDate'), 6, 4), substr($form->GetValue('endDate'), 3, 2), substr($form->GetValue('endDate'), 0, 2));

		redirect(sprintf("Location: %s?start=%s&end=%s&term=%s", $_SERVER['PHP_SELF'], urlencode($start), urlencode($end), $term));
	}
}

$page = new Page('Search Terms', sprintf('Statistics for search terms between %s and %s with the term \'%s\'.', cDatetime($start, 'shortdatetime'), cDatetime($end, 'shortdatetime'), $term));
$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('start');
echo $form->GetHTML('end');

$window = new StandardWindow("Search term options.");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Select the date range for the search term.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('term'), $form->GetHTML('term'));
echo $webForm->AddRow($form->GetLabel('startDate'), $form->GetHTML('startDate'));
echo $webForm->AddRow($form->GetLabel('endDate'), $form->GetHTML('endDate'));
echo $webForm->AddRow('&nbsp;', '<input type="submit" name="submit" value="submit" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

echo $form->Close();

if(($displayStart != '0000-00-00 00:00:00') && ($displayEnd != '0000-00-00 00:00:00')) {
	$data = new DataQuery(sprintf("CREATE TEMPORARY TABLE temp_term SELECT COUNT(csi.Session_Item_ID) AS Page_Requests, cs.Session_ID, cs.Created_On, cs.User_Agent_ID FROM customer_session AS cs LEFT JOIN customer_session_item AS csi ON csi.Session_ID=cs.Session_ID WHERE cs.Created_On>='%s' AND cs.Created_On<'%s' AND cs.Referrer_Search_Term LIKE '%s' GROUP BY cs.Session_ID", mysql_real_escape_string($start), mysql_real_escape_string($end), mysql_real_escape_string($term)));
	$data->Disconnect();

	$data = new DataQuery(sprintf("ALTER TABLE `temp_term` ADD INDEX `Session_ID` (`Session_ID`)"));
	$data->Disconnect();

	$data = new DataQuery(sprintf("ALTER TABLE `temp_term` ADD INDEX `Page_Requests` (`Page_Requests`)"));
	$data->Disconnect();

	$data = new DataQuery(sprintf("ALTER TABLE `temp_term` ADD INDEX `Created_On` (`Created_On`)"));
	$data->Disconnect();

	echo '<br />';

	$table = new DataTable('terms');
	$table->SetSQL(sprintf("SELECT Session_ID, Created_On, Page_Requests FROM temp_term"));
	$table->SetTotalRowSQL(sprintf("SELECT COUNT(*) AS TotalRows FROM temp_term"));
	$table->AddField("Session #", "Session_ID");
	$table->AddField("Session Date", "Created_On");
	$table->AddField("Page Requests", "Page_Requests");
	$table->AddLink("stat_session_details.php?id=%s", '<img src="images/icon_search_1.gif" alt="View Session Details" border="0" / >', 'Session_ID', true, true);
	$table->SetMaxRows(25);
	$table->SetOrderBy("Page_Requests");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
}

require_once('lib/common/app_footer.php');
?>