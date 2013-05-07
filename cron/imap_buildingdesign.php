<?php
ini_set('max_execution_time', '1800');
chdir("/var/www/vhosts/bltdirect.com/httpdocs/cron/");

require_once('../ignition/lib/classes/ApplicationHeader.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/htmlMimeMail5.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Enquiry.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/EnquiryLine.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Customer.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Contact.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Form.php');

$mailLog = false;
$log = array();
$logHeader = array();
$timing = microtime(true);
$script = 'IMAP Building Design';
$fileName = 'imap_buildingdesign.php';

## BEGIN SCRIPT
$GLOBALS['SESSION_USER_ID'] = 30;

$fromAddress = 'buildingdesign.co.uk';
$catchFields = array('fullname', 'title', 'streetaddress', 'address2', 'city', 'state county', 'address code', 'country', 'phone', 'fax', 'email', 'contacts-requirements');
$typeId = 0;
$form = new Form($_SERVER['PHP_SELF']);

$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type WHERE Developer_Key LIKE 'salesenquiries'"));
if ($data->TotalRows > 0) {
	$typeId = $data->Row['Enquiry_Type_ID'];
}
$data->Disconnect();

if ($typeId == 0) {
	$data = new DataQuery(sprintf("SELECT Enquiry_Type_ID FROM enquiry_type WHERE Is_Public='Y' ORDER BY Enquiry_Type_ID ASC LIMIT 0, 1"));
	if ($data->TotalRows > 0) {
		$typeId = $data->Row['Enquiry_Type_ID'];
	}
	$data->Disconnect();
}

$mailbox = imap_open("{localhost:993/imap/ssl/novalidate-cert}INBOX", "buildingdesign@bltdirect.com", "Teit7v") or die("Cannot connect: " . imap_last_error());
$check = imap_check($mailbox);

for($i=$check->Nmsgs; $i>0; $i--) {
	$header = imap_header($mailbox, $i);

	if($fromAddress == strtolower($header->from[0]->host)) {
		$lines = explode("\x0A", imap_body($mailbox, $i));

		$attributes = array();

		foreach($catchFields as $field) {
			$attributes[$field] = '';
		}

		for($j=0; $j<count($lines); $j++) {
			if(strlen(trim($lines[$j])) > 0) {
				foreach($catchFields as $field) {
					if(preg_match(sprintf('/%s :(.*)/', $field), $lines[$j], $matches)) {
						$attributes[$field] .= trim($matches[1]);

						if($field == 'contacts-requirements') {
							$k = 1;

							while(isset($lines[$j + $k]) && (strlen(trim($lines[$j + $k])) > 0)) {
								$attributes[$field] .= sprintf(' %s', trim($lines[$j + $k]));
								$k++;
							}

							$attributes[$field] = str_replace("\x0A", '', $attributes[$field]);
							$attributes[$field] = str_replace("\x0D", '', $attributes[$field]);

						} elseif($field == 'email') {
							if(!preg_match(sprintf('/%s/', $form->RegularExp['email']), $attributes[$field])) {
								$attributes[$field] = '';
							}
						}
					}
				}
			}
		}

		$customerId = 0;

		if(strlen($attributes['Email Address']) > 0) {
			$sql = sprintf("SELECT cu.Customer_ID FROM customer AS cu WHERE cu.Username LIKE '%s'", mysql_real_escape_string($attributes['Email Address']));
		} else {
			$sql = sprintf("SELECT cu.Customer_ID FROM customer AS cu INNER JOIN contact AS c ON cu.Contact_ID=c.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE p.Name_First LIKE '%s' AND p.Name_Last LIKE '%s' AND p.Phone_1 LIKE '%s'", mysql_real_escape_string($attributes['Forename']), mysql_real_escape_string($attributes['Surname']), mysql_real_escape_string($attributes['Tel Number']));
		}

		$data = new DataQuery($sql);
		if($data->TotalRows > 0) {
			$customerId = $data->Row['Customer_ID'];
		} else {
			$addressLines = explode(',', $attributes['streetaddress']);

			if(!is_array($addressLines)) {
				$addressLines = array($addressLines);
			}

			$nameLines = explode(' ', $attributes['fullname']);

			if(!is_array($nameLines)) {
				$nameLines = array($nameLines);
			}

			$firstName = '';
			$lastName = '';

			if(count($nameLines) == 1) {
				$lastName = $nameLines[0];

			} elseif(count($nameLines) == 2) {
				$firstName = $nameLines[0];
				$lastName = $nameLines[1];

			} elseif(count($nameLines) >= 3) {
				$firstName = $nameLines[0];
				$lastName = $nameLines[count($nameLines) - 1];
			}

			$customer = new Customer();
			$customer->Username = (strlen($attributes['email']) > 0) ? $attributes['email'] : '0@no-email.co.uk';
			$customer->Contact->Type = 'I';
			$customer->Contact->IsCustomer = 'Y';
			$customer->Contact->Person->Title = $attributes['title'];
			$customer->Contact->Person->Name = $firstName;
			$customer->Contact->Person->LastName = $lastName;
			$customer->Contact->Person->Phone1 = $attributes['phone'];
			$customer->Contact->Person->Fax = $attributes['fax'];
			$customer->Contact->Person->Email = $customer->Username;
			$customer->Contact->Person->Address->Line1 = isset($addressLines[0]) ? trim($addressLines[0]) : '';
			$customer->Contact->Person->Address->Line2 = isset($addressLines[1]) ? trim($addressLines[1]) : '';
			$customer->Contact->Person->Address->Line3 = $attributes['address2'];
			$customer->Contact->Person->Address->City = $attributes['city'];
			$customer->Contact->Person->Address->Zip = $attributes['address code'];
			$customer->Contact->Person->Address->Region->GetIDFromString($attributes['state county']);
			$customer->Contact->Person->Address->Country = 222;
			$customer->Contact->OnMailingList = 'H';
			$customer->Contact->Add();
			$customer->Add(false);

			$customerId = $customer->ID;
		}
		$data->Disconnect();

		$enquiry = new Enquiry();
		$enquiry->Prefix = 'T';
		$enquiry->Subject = 'Building Design Sales Enquiry';
		$enquiry->Status = 'Unread';
		$enquiry->Type->ID = $typeId;
		$enquiry->Customer->ID = $customerId;
		$enquiry->Add();

		$enquiryLine = new EnquiryLine();
		$enquiryLine->IsCustomerMessage = 'Y';
		$enquiryLine->IsPublic = 'Y';
		$enquiryLine->Enquiry->ID = $enquiry->ID;
		$enquiryLine->Message = $attributes['contacts-requirements'];
		$enquiryLine->Add();

		$log[] = sprintf("Adding Building Design Enquiry: #%s%s, Subject: %s", $enquiry->Prefix, $enquiry->ID, $enquiry->Subject);
	}

	imap_delete($mailbox, $i);
}

imap_close($mailbox, CL_EXPUNGE);
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
?>