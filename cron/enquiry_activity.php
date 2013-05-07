<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Enquiry.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/EnquiryLine.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'Enquiry Customer Activity';
$fileName = 'enquiry_activity.php';

## BEGIN SCRIPT
$minutePeriod = 30;
$typeId = 0;

$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type WHERE Developer_Key LIKE 'customeractivity'"));
if ($data->TotalRows > 0) {
	$typeId = $data->Row['Enquiry_Type_ID'];
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT csi.Session_ID, csi2.Customer_ID FROM customer_session_item AS csi INNER JOIN (SELECT Session_ID, Customer_ID FROM customer_session_item WHERE Customer_ID>0 GROUP BY Session_ID) AS csi2 ON csi.Session_ID=csi2.Session_ID WHERE csi.Created_On>=ADDDATE(NOW(), INTERVAL -%d MINUTE) AND csi.Created_On<ADDDATE(NOW(), INTERVAL -%d MINUTE) GROUP BY csi.Session_ID", $minutePeriod * 2, $minutePeriod));
while($data->Row) {
	$data9 = new DataQuery(sprintf("SELECT MIN(Created_On) AS First_Seen_On, MAX(Created_On) AS Last_Seen_On FROM customer_session_item WHERE Session_ID=%d", $data->Row['Session_ID']));
	if(strtotime($data9->Row['Last_Seen_On']) < (time() - (60 * $minutePeriod))) {
		$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM orders WHERE Created_On>='%s' AND Created_On<='%s'", $data9->Row['First_Seen_On'], $data9->Row['Last_Seen_On']));
		if($data2->Row['Count'] == 0) {
			$enquiryId = 0;
			$products = array();

			$data3 = new DataQuery(sprintf("SELECT Page_Request FROM customer_session_item WHERE Session_ID=%d AND Page_Request LIKE '/product.php?pid=%%'", $data->Row['Session_ID']));
			while($data3->Row) {
				if(preg_match('/\/product\.php.*pid=([0-9]*).*/', $data3->Row['Page_Request'], $matches)) {
					if(!isset($products[$matches[1]])) {
						$products[$matches[1]] = 0;
					}

					$products[$matches[1]]++;
				}

				$data3->Next();
			}
			$data3->Disconnect();

			if(count($products) > 0) {
				if($typeId > 0) {
					$data3 = new DataQuery(sprintf("SELECT Enquiry_ID FROM enquiry WHERE Customer_ID=%d AND Enquiry_Type_ID=%d AND Status NOT LIKE 'Closed'", $data->Row['Customer_ID'], mysql_real_escape_string($typeId)));
					if($data3->TotalRows > 0) {
						$enquiryId = $data3->Row['Enquiry_ID'];
					}
					$data3->Disconnect();
				}

				if($enquiryId > 0) {
					$enquiry = new Enquiry($enquiryId);
					$enquiry->IsPendingAction = 'Y';
					$enquiry->Update();

					$log[] = sprintf("Updating Enquiry: #%s%s, Subject: %s, Customer: #%s", $enquiry->Prefix, $enquiry->ID, $enquiry->Subject, $enquiry->Customer->ID);
				} else {
					$enquiry = new Enquiry();
					$enquiry->Type->ID = $typeId;
					$enquiry->Customer->ID = $data->Row['Customer_ID'];
					$enquiry->Status = 'Unread';
					$enquiry->Subject = 'Customer activity log';
					$enquiry->Add(false);

					$log[] = sprintf("Adding Enquiry: #%s%s, Subject: %s, Customer: #%s", $enquiry->Prefix, $enquiry->ID, $enquiry->Subject, $enquiry->Customer->ID);
				}

				$message = sprintf("This customer recently visited the website without ordering and was last seen on %s.\nView the session details (<a href=\"stat_session_details.php?id=%d\" target=\"_blank\">#%d</a>) for a visual record of this activity.", cDatetime($data9->Row['Last_Seen_On'], 'shortdatetime'), $data->Row['Session_ID'], $data->Row['Session_ID']);
				$message .= sprintf("\n\nProducts viewed:");

				foreach($products as $productId=>$count) {
					$data3 = new DataQuery(sprintf("SELECT Product_Title FROM product WHERE Product_ID=%d", mysql_real_escape_string($productId)));
					if($data3->TotalRows > 0) {
						$message .= sprintf("\n - #%d: <a href=\"product_profile.php?pid=%d\" target=\"_blank\">%s</a>%s", $productId, $productId, strip_tags($data3->Row['Product_Title']), ($count > 1) ? sprintf(' (%d times)', $count) : '');
					}
					$data3->Disconnect();
				}

				$enquiryLine = new EnquiryLine();
				$enquiryLine->Enquiry->ID = $enquiry->ID;
				$enquiryLine->IsCustomerMessage = 'N';
				$enquiryLine->IsPublic = 'N';
				$enquiryLine->Message = $message;
				$enquiryLine->Add();
			}
		}
		$data2->Disconnect();
	}
	$data9->Disconnect();

	$data->Next();
}
$data->Disconnect();
## END SCRIPT

$logHeader[] = sprintf("Script: %s", $script);
$logHeader[] = sprintf("File Name: %s", $fileName);
$logHeader[] = sprintf("Date Executed: %s", date('Y-m-d H:i:s'));
$logHeader[] = sprintf("Execution Time: %s seconds", number_format(microtime(true) - $timing, 4, '.', ''));
$logHeader[] = '';

$log = array_merge($logHeader, $log);

if ($mailLog) {
	$mail = new htmlMimeMail5();
	$mail->setFrom('root@bltdirect.com');
	$mail->setSubject(sprintf("Cron [%s] <root@bltdirect.com> php /var/www/vhosts/bltdirect.com/httpdocs/cron/%s", $script, $fileName));
	$mail->setText(implode("\n", $log));
	$mail->send(array('adam@azexis.com'));
}

echo implode("<br />", $log);

$GLOBALS['DBCONNECTION']->Close(); 