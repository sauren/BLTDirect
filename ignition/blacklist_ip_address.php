<?php
require_once('lib/common/app_header.php');

if($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BlacklistIPAddress.php');

	if(isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])){
		$ip = new BlacklistIPAddress();
		$ip->Delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BlacklistIPAddress.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('from', 'IP Address From', 'text', '', 'anything', 7, 15, true);
	$form->AddField('to', 'IP Address To', 'text', '', 'anything', 7, 15, false);
	$form->AddField('reason', 'Reason', 'textarea', '', 'anything', 0, 255, false, 'style="width: 300px;" rows="5"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){		
		if($form->Validate()){
			$addressA = (strlen($form->GetValue('from')) > 0) ? $form->GetValue('from') : null;
			$addressB = (strlen($form->GetValue('to')) > 0) ? $form->GetValue('to') : null;
			
			if(!is_null($addressA)) {
				if((ip2long($addressA) == -1) || (ip2long($addressA) === false)) {
					$form->AddError('IP Address From must be a valid IP address.', 'from');
				}
			}
			
			if(!is_null($addressB)) {
				if((ip2long($addressB) == -1) || (ip2long($addressB) === false)) {
					$form->AddError('IP Address To must be a valid IP address.', 'to');
				}
			}
			
			if($form->Valid) {
				$ip = new BlacklistIPAddress();
				$ip->SetIPAddress($addressA, $addressB);
				$ip->Reason = $form->GetValue('reason');
				$ip->Add();
	
				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			}
		}
	}

	$page = new Page(sprintf('<a href="%s">IP Blacklist</a> &gt; Add IP Address', $_SERVER['PHP_SELF']), 'Add a new ip address to this blacklist.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Adding an IP Address');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $window->Open();
	echo $window->AddHeader('Enter an IP address.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('from'), $form->GetHTML('from') . $form->GetIcon('from'));
	echo $webForm->AddRow($form->GetLabel('to'), $form->GetHTML('to') . $form->GetIcon('to'));
	echo $webForm->AddRow($form->GetLabel('reason'), $form->GetHTML('reason') . $form->GetIcon('reason'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'blacklist_ip_address.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/BlacklistIPAddress.php');

	if(!isset($_REQUEST['id'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}
	
	$ip = new BlacklistIPAddress($_REQUEST['id']);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', '', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', '', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', '', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('from', 'IP Address From', 'text', $ip->IPAddressFrom, 'anything', 7, 15, true);
	$form->AddField('to', 'IP Address To', 'text', $ip->IPAddressTo, 'anything', 7, 15, false);
	$form->AddField('reason', 'Reason', 'textarea', $ip->Reason, 'anything', 0, 255, false, 'style="width: 300px;" rows="5"');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){		
		if($form->Validate()){
			$addressA = (strlen($form->GetValue('from')) > 0) ? $form->GetValue('from') : null;
			$addressB = (strlen($form->GetValue('to')) > 0) ? $form->GetValue('to') : null;
			
			if(!is_null($addressA)) {
				if((ip2long($addressA) == -1) || (ip2long($addressA) === false)) {
					$form->AddError('IP Address From must be a valid IP address.', 'from');
				}
			}
			
			if(!is_null($addressB)) {
				if((ip2long($addressB) == -1) || (ip2long($addressB) === false)) {
					$form->AddError('IP Address To must be a valid IP address.', 'to');
				}
			}
			
			if($form->Valid) {
				$ip->SetIPAddress($addressA, $addressB);
				$ip->Reason = $form->GetValue('reason');
				$ip->Update();
	
				redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
			}
		}
	}

	$page = new Page(sprintf('<a href="%s">IP Blacklist</a> &gt; Update IP Address', $_SERVER['PHP_SELF']), 'Edit an ip address for this blacklist.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Updating an IP Address');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Update an IP address.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('from'), $form->GetHTML('from') . $form->GetIcon('from'));
	echo $webForm->AddRow($form->GetLabel('to'), $form->GetHTML('to') . $form->GetIcon('to'));
	echo $webForm->AddRow($form->GetLabel('reason'), $form->GetHTML('reason') . $form->GetIcon('reason'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'blacklist_ip_address.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$page = new Page('IP Blacklist', 'Listing all blacklisted IP addresses.');
	$page->Display('header');

	$table = new DataTable('blacklist');
	$table->SetSQL("SELECT Blacklist_IP_Address_ID, INET_NTOA(IP_Address_From) AS IP_Address_From, INET_NTOA(IP_Address_To) AS IP_Address_To, Reason, Created_On FROM blacklist_ip_address");
	$table->AddField("ID#", "Blacklist_IP_Address_ID");
	$table->AddField("IP Address From", "IP_Address_From", "left");
	$table->AddField("IP Address To", "IP_Address_To", "left");
	$table->AddField("Reason", "Reason", "left");
	$table->AddField("Blacklist Date", "Created_On", "left");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Blacklist_IP_Address_ID");
	$table->AddLink("blacklist_ip_address.php?action=update&id=%s","<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Blacklist_IP_Address_ID");
	$table->AddLink("javascript:confirmRequest('blacklist_ip_address.php?action=remove&id=%s','Are you sure you want to remove this item?');","<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Blacklist_IP_Address_ID");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add new ip address" class="btn" onclick="window.location.href=\'blacklist_ip_address.php?action=add\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>