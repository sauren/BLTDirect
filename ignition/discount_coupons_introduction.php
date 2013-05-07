<?php
	require_once('lib/common/app_header.php');

	if($action == "add"){
		$session->Secure(3);
		add();
		exit;
	} elseif($action == "update"){
		$session->Secure(3);
		update();
		exit;
	} elseif($action == "remove"){
		$session->Secure(3);
		remove();
		exit;
	}else {
		$session->Secure(2);
		view();
		exit;
	}

	function add(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Password.php');

		// Generate Random
		// Generate a 9 random character string
		$randomString = new Password(9);
		$randomString->Generate();
		$couponRef = strtoupper($randomString->Value);
		$couponRef = substr($couponRef, 0 , 3) . '-' . substr($couponRef, 3, 3) . '-' . substr($couponRef, 6, 3);

		$coupon = new Coupon;

		$form = new Form("discount_coupons.php");
		$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('reference', 'Coupon Reference', 'text', $couponRef, 'alpha_numeric', 1, 15, true);
		$form->AddField('title', 'Coupon Title', 'text', '', 'paragraph', 1, 45, true);
		$form->AddField('description', 'Coupon Description', 'textarea', '', 'paragraph', 1, 255, true, 'style="width:90%;height:50px;"');
		$form->AddField('discount', 'Discount Percentage', 'text', '0', 'numeric_unsigned', 1, 3, true);
		$form->AddField('ordersOver', 'Discount On Orders Over', 'text', '0.00', 'float', 1, 11, true);
		$form->AddField('usage', 'Usage Limit', 'text', '1', 'numeric_unsigned', 1, 11, true);
		$form->AddField('products', 'Product Option', 'radio', 'Y', 'alpha', 1, 1, true);
		$form->AddOption('products', 'Y', 'This Coupon applies to all products.');
		$form->AddOption('products', 'B', 'This Schema applies to band');
		$form->AddOption('products', 'N', 'I would like this coupon to apply to specific products.');
		$form->AddField('active', 'Activate Coupon', 'checkbox', 'Y', 'boolean', NULL, NULL, false);
		$form->AddField('staff', 'Staff Only', 'checkbox', 'N', 'boolean', NULL, NULL, false);
		$form->AddField('expires', 'Coupon Expires On', 'datetime', '0000-00-00 00:00:00', 'datetime', cDatetime(getDatetime(), 'y'), (cDatetime(getDatetime(), 'y')+10));
		$form->AddField('band', 'Product Band', 'select', '0', 'numeric_unsigned', 1, 11);
		$form->AddOption('band', 0, 'Select...');
		$bands = new DataQuery('SELECT * FROM product_band ORDER BY Product_Band_ID ASC');
		while($bands->Row) {
			$form->AddOption('band', $bands->Row['Product_Band_ID'], $bands->Row['Band_Ref']. ' - ' . $bands->Row['Band_Title']);
			$bands->Next();
		}
		$bands->Disconnect();
		unset($bands);


		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			$form->Validate();
			// Hurrah! Create a new entry.
			$coupon->Reference = strtoupper($form->GetValue('reference'));
			$coupon->Name = $form->GetValue('title');
			$coupon->Description = $form->GetValue('description');
			$coupon->Discount = $form->GetValue('discount');
			$coupon->IsFixed = 'N';
			$coupon->OrdersOver = $form->GetValue('ordersOver');
			$coupon->UsageLimit = $form->GetValue('usage');
			$coupon->IsAllProducts = $form->GetValue('products');
			$coupon->IsActive = $form->GetValue('active');
			$coupon->StaffOnly = $form->GetValue('staff');
			$coupon->UseBand = $form->GetValue('band');
			$coupon->IsAllCustomers = 'Y';
			$coupon->ExpiresOn = $form->GetValue('expires');

			if($discount->IsAllProducts == 'B' && empty($discount->UseBand)) {
				$form->AddError('You must select a band from the drop down list.', 'band');
			}

			if($coupon->Exists()){
				$form->AddError('The Coupon Reference Already exists. Please try a different Reference.', 'reference');
			}

			if($form->Valid){
				$coupon->Add();
				redirect(sprintf("Location: discount_coupon_settings.php?coupon=%d", $coupon->ID));
				exit;
			}
		}

		$page = new Page('Add Discount Coupon','This coupon will be openly available to all customers. Please supply a coupon reference or use the one automatically created for you.');
		$page->Display('header');

		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}

		$window = new StandardWindow('Update');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference') . $form->GetIcon('reference'));
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
		echo $webForm->AddRow($form->GetLabel('discount'), $form->GetHTML('discount') . '% '  . $form->GetIcon('discount'));
		echo $webForm->AddRow($form->GetLabel('ordersOver'), $form->GetHTML('ordersOver') . $form->GetIcon('ordersOver'));
		echo $webForm->AddRow($form->GetLabel('usage'), $form->GetHTML('usage') . $form->GetIcon('usage'));
		echo $webForm->AddRow($form->GetLabel('products'), '');
		echo $webForm->AddRow($form->GetHTML('products', 1), $form->GetLabel('products', 1));
		echo $webForm->AddRow($form->GetHTML('products', 2), $form->GetLabel('products', 2) . $form->GetHTML('band'));
		echo $webForm->AddRow($form->GetHTML('products', 3), $form->GetLabel('products', 3));
		echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
		echo $webForm->AddRow($form->GetLabel('staff'), $form->GetHTML('staff') . $form->GetIcon('staff'));
		echo $webForm->AddRow($form->GetLabel('expires'), $form->GetHTML('expires') . $form->GetIcon('expires'));

		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'discount_coupons.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
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
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Coupon.php');
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

		$coupon = new Coupon($_REQUEST['coupon']);

		$form = new Form("discount_coupons.php");
		$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
		$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$form->AddField('coupon', 'Coupon ID', 'hidden', $coupon->ID, 'numeric_unsigned', 1, 3, true);
		$form->AddField('reference', 'Coupon Reference', 'text', $coupon->Reference, 'alpha_numeric', 1, 15, true);
		$form->AddField('title', 'Coupon Title', 'text', $coupon->Name, 'paragraph', 1, 45, true);
		$form->AddField('description', 'Coupon Description', 'textarea', $coupon->Description, 'paragraph', 1, 255, true, 'style="width:90%;height:50px;"');
		$form->AddField('discount', 'Discount Percentage', 'text', $coupon->Discount, 'numeric_unsigned', 1, 3, true);
		$form->AddField('ordersOver', 'Discount On Orders Over', 'text', $coupon->OrdersOver, 'float', 1, 11, true);
		$form->AddField('usage', 'Usage Limit', 'text', $coupon->UsageLimit, 'numeric_unsigned', 1, 11, true);
		$form->AddField('products', 'Product Option', 'radio', $coupon->IsAllProducts, 'alpha', 1, 1, true);
		$form->AddOption('products', 'Y', 'This coupon applies to all products.');
		$form->AddOption('products', 'B', 'This coupon applies to band');
		$form->AddOption('products', 'N', 'I would like this coupon to apply to specific products.');
		$form->AddField('active', 'Activate Coupon', 'checkbox', $coupon->IsActive, 'boolean', NULL, NULL, false);
		$form->AddField('staff', 'Staff Only', 'checkbox', $coupon->StaffOnly, 'boolean', NULL, NULL, false);
		$form->AddField('expires', 'Coupon Expires On', 'datetime', $coupon->ExpiresOn, 'datetime', cDatetime(getDatetime(), 'y'), (cDatetime(getDatetime(), 'y')+10));


		$form->AddField('band', 'Product Band', 'select', $coupon->UseBand, 'numeric_unsigned', 1, 11);
		$form->AddOption('band', 0, 'Select...');
		$bands = new DataQuery('SELECT * FROM product_band ORDER BY Product_Band_ID ASC');
		while($bands->Row) {
			$form->AddOption('band', $bands->Row['Product_Band_ID'], $bands->Row['Band_Ref']. ' - ' . $bands->Row['Band_Title']);
			$bands->Next();
		}
		$bands->Disconnect();
		unset($bands);

		// Check if the form has been submitted
		if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
			$form->Validate();
			// Hurrah! Create a new entry.
			$coupon->Reference = strtoupper($form->GetValue('reference'));
			$coupon->Name = $form->GetValue('title');
			$coupon->Description = $form->GetValue('description');
			$coupon->Discount = $form->GetValue('discount');
			$coupon->IsFixed = 'N';
			$coupon->OrdersOver = $form->GetValue('ordersOver');
			$coupon->UsageLimit = $form->GetValue('usage');
			$coupon->IsAllProducts = $form->GetValue('products');
			$coupon->IsActive = $form->GetValue('active');
			$coupon->StaffOnly = $form->GetValue('staff');
			$coupon->UseBand = $form->GetValue('band');
			$coupon->IsAllCustomers = 'Y';
			$coupon->ExpiresOn = $form->GetValue('expires');

			if($discount->IsAllProducts == 'B' && empty($discount->UseBand)) {
				$form->AddError('You must select a band from the drop down list.', 'band');
			}

			if($form->Valid){
				$coupon->Update();
				redirect(sprintf("Location: discount_coupon_settings.php?coupon=%d", $coupon->ID));
				exit;
			}
		}

		$page = new Page('Edit Discount Coupon','This coupon will be openly available to all customers. Please supply a coupon reference or use the one automatically created for you.');
		$page->Display('header');

		// Show Error Report if Form Object validation fails
		if(!$form->Valid){
			echo $form->GetError();
			echo "<br>";
		}

		$window = new StandardWindow('Update');
		echo $form->Open();
		echo $form->GetHTML('action');
		echo $form->GetHTML('confirm');
		echo $form->GetHTML('coupon');
		echo $window->Open();
		echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
		echo $window->OpenContent();
		$webForm = new StandardForm;
		echo $webForm->Open();
		echo $webForm->AddRow($form->GetLabel('reference'), $form->GetHTML('reference') . $form->GetIcon('reference'));
		echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
		echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
		echo $webForm->AddRow($form->GetLabel('discount'), $form->GetHTML('discount') . '% ' . $form->GetIcon('discount'));
		echo $webForm->AddRow($form->GetLabel('ordersOver'), $form->GetHTML('ordersOver') . $form->GetIcon('ordersOver'));
		echo $webForm->AddRow($form->GetLabel('usage'), $form->GetHTML('usage') . $form->GetIcon('usage'));
		echo $webForm->AddRow($form->GetLabel('products'), '');
		echo $webForm->AddRow($form->GetHTML('products', 1), $form->GetLabel('products', 1));
		echo $webForm->AddRow($form->GetHTML('products', 2), $form->GetLabel('products', 2) . $form->GetHTML('band'));
		echo $webForm->AddRow($form->GetHTML('products', 3), $form->GetLabel('products', 3));
		echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
		echo $webForm->AddRow($form->GetLabel('staff'), $form->GetHTML('staff') . $form->GetIcon('staff'));
		echo $webForm->AddRow($form->GetLabel('expires'), $form->GetHTML('expires') . $form->GetIcon('expires'));

		echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'discount_coupons.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
		echo $webForm->Close();
		echo $window->CloseContent();
		echo $window->Close();
		echo $form->Close();


		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}

	function remove(){
		//TODO: Refer to equiv function in discount_schemas.php.
	}

	function view(){
		require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');

		$page = new Page('Introduction Coupon Settings','View discount coupons created via introduce a friend.');
		$page->Display('header');

		//echo "<br>";
		//echo '<input type="button" name="add" value="add a new coupon" class="btn" onclick="window.location.href=\'discount_coupons.php?action=add\'">';
		//echo "<br><br>";

		$table = new DataTable('coupons');
		$table->SetSQL("select * from coupon WHERE Introduced_By>0 AND Is_Invisible='N'");
		$table->AddField('ID#', 'Coupon_ID', 'right');
		$table->AddField('Reference', 'Coupon_Ref', 'left');
		$table->AddField('Name', 'Coupon_Title', 'left');

		$table->AddField('Orders Over', 'Orders_Over', 'right');
		$table->AddField('Discount %', 'Discount_Amount', 'right');
		$table->AddField('Usage Limit', 'Usage_Limit', 'right');
		$table->AddField('Expires On', 'Expires_On', 'left');

		$table->AddField('Active', 'Is_Active', 'center');
		$table->AddLink("discount_coupon_settings.php?coupon=%s",
						"<img src=\"./images/folderopen.gif\" alt=\"Open Coupon\" border=\"0\">",
						"Coupon_ID");
		$table->AddLink("discount_coupons.php?action=update&coupon=%s",
						"<img src=\"./images/icon_edit_1.gif\" alt=\"Update Coupon\" border=\"0\">",
						"Coupon_ID");
		$table->SetMaxRows(25);
		$table->SetOrderBy("Coupon_ID");
		$table->Finalise();
		$table->DisplayTable();
		echo "<br>";
		$table->DisplayNavigation();
		//echo "<br>";
		//echo "<br>";
		//echo '<input type="button" name="add" value="add a new coupon" class="btn" onclick="window.location.href=\'discount_coupons.php?action=add\'">';
		$page->Display('footer');
require_once('lib/common/app_footer.php');
	}
?>
