<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Contact.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ContactProductTrade.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

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
} elseif($action == 'complete') {
	$session->Secure(3);
	complete();
	exit;
} else {
	$session->Secure(3);
	view();
	exit;
}

function remove() {
	if(isset($_REQUEST['id'])) {
		$trade = new ContactProductTrade();
		$trade->delete($_REQUEST['id']);
	}

	redirect(sprintf("Location: %s?cid=%d", $_SERVER['PHP_SELF'], $_REQUEST['cid']));
}

function add() {
	$contact = new Contact($_REQUEST['cid']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cid', 'Contact ID', 'hidden', $contact->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('productid', 'Product ID', 'text', '', 'numeric_unsigned', 1, 10, true);
	$form->AddField('price', 'Price', 'text', '', 'float', 1, 11, true);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$trade = new ContactProductTrade();
			$trade->contact->ID = $contact->ID;
			$trade->product->ID = $form->GetValue('productid');
			$trade->price = $form->GetValue('price');
			
			if(!$trade->product->Get()) {
				$form->AddError('Product does not exist.', 'productid');	
			}
			
			if($form->Valid) {
				$trade->add();

				redirect(sprintf("Location: ?cid=%d", $contact->ID));
			}
		}
	}

	if($contact->Type == "O"){
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; <a href="?cid=%d">Contact Trade Products</a> &gt; Add Trade Product Price', $contact->ID, $contact->Organisation->Name, $contact->ID), 'Add a trade product price for this contact.');
		$page->Display('header');
	} else {
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; <a href="?cid=%d">Contact Trade Products</a>  &gt; Add Trade Product Price', $contact->ID, $contact->Person->Name, $contact->Person->LastName, $contact->ID), 'Add a trade product price for this contact.');
		$page->Display('header');
	}

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Add a trade product price');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cid');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('productid'), $form->GetHTML('productid') . $form->GetIcon('productid'));
	echo $webForm->AddRow($form->GetLabel('price'), $form->GetHTML('price') . $form->GetIcon('price'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update() {
	$trade = new ContactProductTrade($_REQUEST['id']);
	$contact = new Contact($trade->contact->ID);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('id', 'Trade Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('price', 'Price', 'text', $trade->price, 'float', 1, 11, true);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$trade->price = $form->GetValue('price');
			$trade->update();

			redirect(sprintf("Location: ?cid=%d", $contact->ID));
		}
	}
	
	if($contact->Type == "O"){
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s</a> &gt; <a href="?cid=%d">Contact Trade Products</a> &gt; Update Trade Product Price', $contact->ID, $contact->Organisation->Name, $contact->ID), 'Update trade product price for this contact.');
		$page->Display('header');
	} else {
		$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; <a href="?cid=%d">Contact Trade Products</a>  &gt; Update Trade Product Price', $contact->ID, $contact->Person->Name, $contact->Person->LastName, $contact->ID), 'Update trade product price for this contact.');
		$page->Display('header');
	}

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Update trade product price');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('id');
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('price'), $form->GetHTML('price') . $form->GetIcon('price'));
	echo $webForm->AddRow('', sprintf('<input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	$contact = new Contact($_REQUEST['cid']);

	$page = new Page(sprintf('<a href="contact_profile.php?cid=%d">%s %s</a> &gt; Contact Trade Products', $contact->ID, $contact->Person->Name, $contact->Person->LastName), 'Below is a list of trade product prices for this contact.');
	$page->Display('header');

	$table = new DataTable("schedules");
	$table->SetSQL(sprintf("SELECT cpt.*, p.Product_Title FROM contact_product_trade AS cpt INNER JOIN product AS p ON p.Product_ID=cpt.productId WHERE cpt.contactId=%d", $contact->ID));
	$table->AddField('ID#', 'id', 'left');
	$table->AddField('Product ID', 'productId', 'left');
	$table->AddField('Product Name', 'Product_Title', 'left');
	$table->AddField('Price', 'price', 'right');
	$table->AddLink("?action=update&id=%s", "<img src=\"images/icon_edit_1.gif\" alt=\"Edit\" border=\"0\">", "id");
	$table->AddLink("javascript:confirmRequest('?action=remove&id=%s&cid=" . $contact->ID . "', 'Are you sure you want to remove this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "id");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_Title");
	$table->Order = 'ASC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo sprintf('<br /><input name="schedule" type="button" value="add new trade product" class="btn" onclick="window.self.location.href=\'?action=add&cid=%d\';" />', $contact->ID);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
