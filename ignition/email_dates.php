<?php
require_once('lib/common/app_header.php');

if($action == "add") {
	$session->Secure(3);
	add();
	exit;
} elseif($action == "update") {
	$session->Secure(3);
	update();
	exit;
} elseif($action == "remove") {
	$session->Secure(3);
	remove();
	exit;
} elseif($action == "sequence") {
	$session->Secure(3);
	sequence();
	exit;
} elseif($action == "moveup") {
	$session->Secure(3);
	moveup();
	exit;
} elseif($action == "movedown") {
	$session->Secure(3);
	movedown();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function moveup() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/EmailDateProduct.php');

	$date = new EmailDateProduct($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT EmailDateProductID, Sequence FROM email_date_product WHERE Sequence<%d AND EmailDateID=%d ORDER BY Sequence DESC LIMIT 0, 1", mysql_real_escape_string($date->Sequence), mysql_real_escape_string($date->EmailDateID)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE email_date_product SET Sequence=%d WHERE EmailDateProductID=%d", mysql_real_escape_string($data->Row['Sequence']), mysql_real_escape_string($date->ID)));
		new DataQuery(sprintf("UPDATE email_date_product SET Sequence=%d WHERE EmailDateProductID=%d", mysql_real_escape_string($date->Sequence), mysql_real_escape_string($data->Row['EmailDateProductID'])));
	}
	$data->Disconnect();
	
	redirect(sprintf("Location: ?action=sequence&id=%d", $date->EmailDateID));
}

function movedown() {
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"].'lib/classes/EmailDateProduct.php');

	$date = new EmailDateProduct($_REQUEST['id']);

	$data = new DataQuery(sprintf("SELECT EmailDateProductID, Sequence FROM email_date_product WHERE Sequence>%d AND EmailDateID=%d ORDER BY Sequence ASC LIMIT 0, 1", mysql_real_escape_string($date->Sequence), mysql_real_escape_string($date->EmailDateID)));
	if($data->TotalRows > 0) {
		new DataQuery(sprintf("UPDATE email_date_product SET Sequence=%d WHERE EmailDateProductID=%d", mysql_real_escape_string($data->Row['Sequence']), mysql_real_escape_string($date->ID)));
		new DataQuery(sprintf("UPDATE email_date_product SET Sequence=%d WHERE EmailDateProductID=%d", mysql_real_escape_string($date->Sequence), mysql_real_escape_string($data->Row['EmailDateProductID'])));
	}
	$data->Disconnect();

	redirect(sprintf("Location: ?action=sequence&id=%d", $date->EmailDateID));
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailDate.php');

	if(isset($_REQUEST['id'])) {
		$date = new EmailDate($_REQUEST['id']);
		$date->Delete();

		redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $date->EmailID));
	}

	redirect(sprintf("Location: emails.php"));
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Email.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailDate.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$email = new Email();

	if(!$email->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: emails.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Email ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('subject', 'Subject', 'text', '', 'anything', 1, 255);
	$form->AddField('date', 'Date', 'text', '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('pool', 'Product Pool', 'select', '0', 'numeric_unsigned', 1, 10, false);
	$form->AddOption('pool', '0', '');

	$data = new DataQuery(sprintf("SELECT * FROM email_product_pool ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('pool', $data->Row['EmailProductPoolID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('lines', 'Product Lines', 'select', '1', 'numeric_unsigned', 1, 11);
	$form->AddField('randomised', 'Randomise Products', 'checkbox', 'Y', 'boolean', 1, 1, false);

	for($i=0; $i<9; $i++) {
		$form->AddOption('lines', $i+1, $i+1);
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$date = new EmailDate();
			$date->Subject = $form->GetValue('subject');
			$date->Date = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date'), 6, 4), substr($form->GetValue('date'), 3, 2), substr($form->GetValue('date'), 0, 2));
			$date->EmailID = $email->ID;
			$date->EmailProductPoolID = $form->GetValue('pool');
			$date->ProductLines = $form->GetValue('lines');
			$date->IsRandomised = $form->GetValue('randomised');
			$date->Add();

			redirect(sprintf("Location: email_dates.php?id=%d", $email->ID));
		}
	}

	$page = new Page(sprintf('<a href="email_profile.php?id=%d">Email Profile</a> &gt; <a href="%s?id=%d">Edit Dates</a> &gt; Add Date', $email->ID, $_SERVER['PHP_SELF'], $email->ID), 'Here you can add a date for this email.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add Date');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('subject'), $form->GetHTML('subject').$form->GetIcon('subject'));
	echo $webForm->AddRow($form->GetLabel('date'), $form->GetHTML('date').$form->GetIcon('date'));
	echo $webForm->AddRow($form->GetLabel('pool'), $form->GetHTML('pool').$form->GetIcon('pool'));
	echo $webForm->AddRow($form->GetLabel('lines'), $form->GetHTML('lines').$form->GetIcon('lines'));
	echo $webForm->AddRow($form->GetLabel('randomised'), $form->GetHTML('randomised').$form->GetIcon('randomised'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'email_dates.php?id=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $email->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailDate.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$date = new EmailDate();

	if(!$date->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: emails.php"));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Email Date ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('subject', 'Subject', 'text', $date->Subject, 'anything', 1, 255);
	$form->AddField('date', 'Date', 'text', ($date->Date != '0000-00-00 00:00:00') ? sprintf('%s/%s/%s', substr($date->Date, 8, 2), substr($date->Date, 5, 2), substr($date->Date, 0, 4)) : '', 'date_ddmmyyy', 1, 10, false, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$form->AddField('pool', 'Product Pool', 'select', $date->EmailProductPoolID, 'numeric_unsigned', 1, 10, false);
	$form->AddOption('pool', '0', '');

	$data = new DataQuery(sprintf("SELECT * FROM email_product_pool ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('pool', $data->Row['EmailProductPoolID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('lines', 'Product Lines', 'select', $date->ProductLines, 'numeric_unsigned', 1, 11);
	$form->AddField('randomised', 'Randomise Products', 'checkbox', $date->IsRandomised, 'boolean', 1, 1, false);
	
	for($i=0; $i<9; $i++) {
		$form->AddOption('lines', $i+1, $i+1);
	}

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			$date->Subject = $form->GetValue('subject');
			$date->Date = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date'), 6, 4), substr($form->GetValue('date'), 3, 2), substr($form->GetValue('date'), 0, 2));
			$date->EmailProductPoolID = $form->GetValue('pool');
			$date->ProductLines = $form->GetValue('lines');
			$date->IsRandomised = $form->GetValue('randomised');
			$date->Update();

			redirect(sprintf("Location: email_dates.php?id=%d", $date->EmailID));
		}
	}

	$page = new Page(sprintf('<a href="email_profile.php?id=%d">Email Profile</a> &gt; <a href="%s?id=%d">Edit Dates</a> &gt; Update Date', $date->EmailID, $_SERVER['PHP_SELF'], $date->EmailID), 'Here you can update a date for this email.');
	$page->AddToHead('<script language="javascript" type="text/javascript" src="js/scw.js"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update Date');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('id');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('subject'), $form->GetHTML('subject').$form->GetIcon('subject'));
	echo $webForm->AddRow($form->GetLabel('date'), $form->GetHTML('date').$form->GetIcon('date'));
	echo $webForm->AddRow($form->GetLabel('pool'), $form->GetHTML('pool').$form->GetIcon('pool'));
	echo $webForm->AddRow($form->GetLabel('lines'), $form->GetHTML('lines').$form->GetIcon('lines'));
	echo $webForm->AddRow($form->GetLabel('randomised'), $form->GetHTML('randomised').$form->GetIcon('randomised'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'email_dates.php?id=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $date->EmailID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function sequence() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Email.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailDate.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailDateProduct.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

	$date = new EmailDate();

	if(!$date->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: emails.php"));
	}
	
	$email = new Email($date->EmailID);

	$pool = array();
	$poolReversed = array();

	$data = new DataQuery(sprintf("SELECT p.Product_ID FROM email_product_pool_product AS eppp INNER JOIN product AS p ON p.Product_ID=eppp.ProductID WHERE eppp.EmailProductPoolID=%d", mysql_real_escape_string($date->EmailProductPoolID)));
	while($data->Row) {
		$pool[$data->Row['Product_ID']] = $data->Row['Product_ID'];

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT pc.Category_ID FROM email_product_pool_category AS eppc INNER JOIN product_categories AS pc ON pc.Category_ID=eppc.CategoryID WHERE eppc.EmailProductPoolID=%d", mysql_real_escape_string($date->EmailProductPoolID)));
	while($data->Row) {
		$result = $email->GetSubCategoryProducts($data->Row['Category_ID']);

		foreach($result as $productId) {
			$pool[$productId] = $productId;
		}

		$data->Next();
	}
	$data->Disconnect();
	
	foreach($pool as $productId=>$poolItem) {
		$pool[$productId] = sprintf('p.Product_ID=%d', $productId);
		$poolReversed[$productId] = sprintf('ProductID<>%d', $productId);
	}
	
	if(count($pool) > 0) {
		$data = new DataQuery(sprintf("SELECT Product_ID FROM product AS p LEFT JOIN email_date_product AS edp ON edp.ProductID=p.Product_ID AND edp.EmailDateID=%d WHERE edp.EmailDateProductID IS NULL AND (%s)", mysql_real_escape_string($date->ID), implode(' OR ', mysql_real_escape_string($pool))));
		while($data->Row) {
			$dateProduct = new EmailDateProduct();
			$dateProduct->EmailDateID = $date->ID;
			$dateProduct->ProductID = $data->Row['Product_ID'];
			$dateProduct->Add();
			
			$data->Next();	
		}
		$data->Disconnect();

		new DataQuery(sprintf("DELETE FROM email_date_product WHERE EmailDateID=%d AND (%s)", mysql_real_escape_string($date->ID), implode(' AND ', mysql_real_escape_string($poolReversed))));
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Email Date ID', 'hidden', '', 'numeric_unsigned', 1, 11);

	$page = new Page(sprintf('<a href="email_profile.php?id=%d">Email Profile</a> &gt; <a href="%s?id=%d">Edit Dates</a> &gt; Sequence Products', $date->EmailID, $_SERVER['PHP_SELF'], $date->EmailID), 'Here you can sequence the products for this email date.');
	$page->Display('header');
	
	$table = new DataTable('products');
	$table->SetExtractVars();
	$table->SetSQL(sprintf("SELECT edp.EmailDateProductID, p.Product_ID, p.Product_Title FROM email_date_product AS edp LEFT JOIN product AS p ON p.Product_ID=edp.ProductID WHERE edp.EmailDateID=%d", mysql_real_escape_string($date->ID)));
	$table->AddField('Product ID#', 'Product_ID', 'left');
	$table->AddField('Name', 'Product_Title', 'left');
	$table->AddLink("?action=moveup&id=%s", "<img src=\"images/aztector_3.gif\" alt=\"Move up\" border=\"0\" />", "EmailDateProductID");
	$table->AddLink("?action=movedown&id=%s", "<img src=\"images/aztector_4.gif\" alt=\"Move down\" border=\"0\" />", "EmailDateProductID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Sequence");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
			
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Email.php');

	$email = new Email();

	if(!$email->Get($_REQUEST['id'])) {
		redirect(sprintf("Location: emails.php"));
	}

	$page = new Page(sprintf('<a href="email_profile.php?id=%d">Email Profile</a> &gt; Edit Dates', $email->ID), 'Here you can manage dates for this email.');
	$page->Display('header');

	$table = new DataTable('dates');
	$table->SetSQL(sprintf("SELECT ed.*, eb.Name AS Banner, epp.Name AS ProductPool FROM email_date AS ed LEFT JOIN email_banner AS eb ON ed.EmailBannerID=eb.EmailBannerID LEFT JOIN email_product_pool AS epp ON epp.EmailProductPoolID=ed.EmailProductPoolID WHERE ed.EmailID=%d", mysql_real_escape_string($email->ID)));
	$table->AddField("ID#", "EmailDateID");
	$table->AddField("Date", "Date", "left");
	$table->AddField('Banner', 'Banner', 'left');
	$table->AddField('Subject', 'Subject', 'left');
	$table->AddField('Product Pool', 'ProductPool', 'left');
	$table->AddField('Product Lines', 'ProductLines', 'right');
	$table->AddField('Is Randomised', 'IsRandomised', 'center');
	$table->AddLink("email_dates.php?action=sequence&id=%s", "<img src=\"images/folderopen.gif\" alt=\"Order products\" border=\"0\">", "EmailDateID", true, false, array('IsRandomised', '==', 'N'));
	$table->AddLink("email_date_banner.php?action=update&id=%s", "<img src=\"images/page_blue_b.gif\" alt=\"Manage banner\" border=\"0\">", "EmailDateID");
	$table->AddLink("email_date_panels.php?action=update&id=%s", "<img src=\"images/page_blue_p.gif\" alt=\"Manage panels\" border=\"0\">", "EmailDateID");
	$table->AddLink("email_dates.php?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "EmailDateID");
	$table->AddLink("javascript:confirmRequest('email_dates.php?action=remove&id=%s','Are you sure you want to remove this item?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "EmailDateID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Date");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input type="button" name="add" value="add date" class="btn" onclick="window.location.href=\'email_dates.php?action=add&id=%d\'" />', $email->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}