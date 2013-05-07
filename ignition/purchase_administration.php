<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Warehouse.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PurchaseLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ProductSearch.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataTable.php');

if($action == 'administer') {
	$session->Secure(3);
	administer();
	exit;
} elseif($action == 'cancel') {
	$session->Secure(3);
	cancel();
	exit;
} elseif($action == 'removeline') {
	$session->Secure(3);
	removeline();
	exit;
} elseif($action == 'addline') {
	$session->Secure(3);
	addline();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function cancel(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Purchase.php');

	if(isset($_REQUEST['pid'])) {
		$purchase = new Purchase($_REQUEST['pid']);
		$purchase->Cancel();
	}

	redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
}

function removeline() {
	$purchase = new Purchase($_REQUEST['pid']);
	$purchase->GetLines();

	$fulfilled = true;

	for($i = 0; $i<count($purchase->Line); $i++) {
		$diff = 0;

		if($purchase->Line[$i]->ID == $_REQUEST['lid']) {
			$diff = $purchase->Line[$i]->Quantity * -1;
		}

		if($diff != 0) {
			$purchase->Line[$i]->QuantityDec += $diff;
			$purchase->Line[$i]->Quantity = 0;

			if($purchase->Line[$i]->QuantityDec < 0) {
				$purchase->Line[$i]->Quantity += (0 - $purchase->Line[$i]->QuantityDec);
				$purchase->Line[$i]->QuantityDec = 0;
			}

			$purchase->Line[$i]->Update();
		}

		if($purchase->Line[$i]->Quantity == 0) {
			$purchase->Line[$i]->Delete();
		} else {
			if($purchase->Line[$i]->QuantityDec != 0){
				$fulfilled = false;
			}
		}
	}

	$purchase->GetLines();

	$purchase->Status = "Unfulfilled";

	if($fulfilled){
		$purchase->Status = "Fulfilled";
	} else {
		for($i=0; $i < count($purchase->Line); $i++){
            if($purchase->Line[$i]->QuantityDec == 0){
				$partial = true;
			}
		}

		if($partial) {
			$purchase->Status = "Partially Fulfilled";
		}
	}

	$purchase->Update();

	redirect(sprintf("Location: %s?action=administer&pid=%d", $_SERVER['PHP_SELF'], $purchase->ID));
}

function addline() {
	$purchase = new Purchase($_REQUEST['pid']);
	$purchase->GetLines();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'addline', 'alpha', 1, 12);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('pid', 'Purchase ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('quantity', 'Quantity', 'text', '1', 'numeric_unsigned', 1, 9);
	$form->AddField('product', 'Product ID', 'hidden', '0', 'numeric_unsigned', 1, 11, false);
	$form->AddField('name', 'Product', 'text', '', 'paragraph', 1, 100, false, 'onFocus="this.Blur();"');

	if(isset($_REQUEST['confirm']) && ($form->GetValue('product') > 0)) {
		if($form->Validate()) {
			$purchase->AddLine($form->GetValue('product'), $form->GetValue('quantity'), true);

			redirect(sprintf("Location: %s?action=administer&pid=%d", $_SERVER['PHP_SELF'], $purchase->ID));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var setProduct = function(productId, productName) {
			var product = document.getElementById(\'product\');
			var name = document.getElementById(\'name\');
			var add = document.getElementById(\'add\');

			if(product && name && add) {
				product.value = productId;
				name.value = productName;

				add.removeAttribute(\'disabled\');
			}
		}
		</script>');

	$page = new Page(sprintf('<a href="%s?action=administer&pid=%d">Purchase Order</a> &gt; Add Product', $_SERVER['PHP_SELF'], $purchase->ID), 'Use the search box to add a product to this order.');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Add a Product.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('pid');
	echo $form->GetHTML('product');
	echo $window->Open();
	echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('quantity'), $form->GetHTML('quantity').$form->GetIcon('quantity'));
	echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . "<input type=\"submit\" name=\"find\" value=\"find\" class=\"btn\" />\n");
	echo $webForm->AddRow('', '<input type="submit" id="add" name="add" value="add" class="btn" disabled="disabled" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	if(isset($_REQUEST['name']) && !empty($_REQUEST['name'])) {
		echo '<br />';

		$search = new ProductSearch($_REQUEST['name'], '', '', '', 'desc', false);
		$search->PrepareSQL();

		$table = new DataTable('results');
		$table->AddField('ID', 'Product_ID', 'left');
		$table->AddField('Title', 'Product_Title', 'left');
		$table->AddField('', '', 'left');
		$table->SetSQL($search->Query);
		$table->SetMaxRows(25);
		$table->Order = 'DESC';
		$table->OrderBy = 'score';
		$table->Finalise();
		$table->ExecuteSQL();

		echo $table->GetTableHeader();

		while($table->Table->Row){
			$prod = new Product($table->Table->Row['Product_ID']);

			echo '<tr>';
			echo sprintf('<td><img src="%s%s" /></td>', $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $prod->DefaultImage->Thumb->FileName);
			echo sprintf('<td><strong><a href="product_profile.php?pid=%s">%s</a></strong><br />Quickfind: <strong>%s</strong>, SKU: %s, Price &pound;%s (Inc. VAT)</td>',$prod->ID, $prod->Name, $prod->ID, $prod->SKU, number_format($prod->PriceCurrentIncTax, 2));
			echo sprintf('<td><a href="javascript:setProduct(%d, \'%s\');">[USE]</a></td>', $prod->ID, $prod->Name);
			echo '</tr>';

			$table->Next();
		}

		echo '</table>';
		echo '<br />';

		$table->DisplayNavigation();
	}

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	if(isset($_REQUEST['status'])) {
		if(strlen($_REQUEST['status']) == 0) {
			$_REQUEST['status'] = 'U';
		}
	}

	$form = new Form($_SERVER['PHP_SELF'],'GET');

	$form->AddField('status','Filter by status','select','U','alpha_numeric',0,40,false);
	$form->AddOption('status','N','No filter');
	$form->AddOption('status','F','View Fulfilled orders only');
	$form->AddOption('status','U','View Unfulfilled orders only');

	$window = new StandardWindow('Filter orders');
	$webForm = new StandardForm();
	echo $form->Open();
	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('status'),$form->GetHTML('status').'<input type="submit" name="search" value="Search" class="btn">');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";

	if($form->GetValue('status')=='N'){
		$sql = sprintf("SELECT * FROM purchase p INNER JOIN users u ON u.Branch_ID = p.For_Branch WHERE p.Purchase_Status NOT LIKE 'Irrelevant' AND p.Purchase_Status NOT LIKE 'Cancelled1' AND u.User_ID=%d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
	}elseif($form->GetValue('status')=='F'){
		$sql = sprintf("SELECT * FROM purchase p INNER JOIN users u ON u.Branch_ID = p.For_Branch WHERE p.Purchase_Status NOT LIKE 'Irrelevant' AND p.Purchase_Status NOT LIKE 'Cancelled1' AND u.User_ID=%d AND p.Purchase_Status LIKE 'Fulfilled'", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
	}elseif($form->GetValue('status')=='U'){
		$sql = sprintf("SELECT * FROM purchase p INNER JOIN users u ON u.Branch_ID = p.For_Branch WHERE p.Purchase_Status NOT LIKE 'Irrelevant' AND p.Purchase_Status NOT LIKE 'Cancelled1' AND u.User_ID=%d AND (p.Purchase_Status LIKE 'Partially Fulfilled' OR p.Purchase_Status LIKE 'Unfulfilled')", mysql_real_escape_string($GLOBALS['SESSION_USER_ID']));
	}

	$page = new Page("Purchases","Here you can administer purchase orders made by your particular branch");
	$page->Display('header');

	$table = new DataTable("com");
	$table->SetSQL($sql);
	$table->AddField('ID#','Purchase_ID');
	$table->AddField('Date Ordered','Purchased_On');
	$table->AddField('Type','Type');
	$table->AddField('Organisation','Supplier_Organisation_Name');
	$table->AddField('First Name','Supplier_First_Name');
	$table->AddField('Last Name','Supplier_Last_Name');
	$table->AddField('Status','Purchase_Status');
	$table->AddField('Custom Reference', 'Custom_Reference_Number');
	$table->AddField('Notes','Order_Note');
	$table->AddLink('purchase_open.php?pid=%s',"<img src=\"./images/folderopen.gif\" alt=\"Open this purchase order\" border=\"0\">",'Purchase_ID');
	$table->AddLink('purchase_administration.php?action=administer&pid=%s',"<img src=\"./images/icon_edit_1.gif\" alt=\"Administer this purchase order\" border=\"0\">",'Purchase_ID');
	$table->AddLink("javascript:confirmRequest('purchase_repeat.php?pid=%s','Are you sure you want to repeat this order?');","<img src=\"./images/icon_pages_1.gif\" alt=\"Repeat this purchase order\" border=\"0\">",'Purchase_ID');
	$table->AddLink("javascript:confirmRequest('purchase_administration.php?action=cancel&pid=%s','Are you sure you want to cancel this item?');", "<img src=\"./images/aztector_6.gif\" alt=\"Cancel\" border=\"0\">","Purchase_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Purchased_On');
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo "<br>";
	$table->DisplayNavigation();
	$page->Display('footer');
}

function administer() {
	$purchase = new Purchase($_REQUEST['pid']);
	$purchase->GetLines();

	$dataQ = new DataQuery(sprintf("SELECT * FROM warehouse w INNER JOIN users u ON u.Branch_ID = w.Type_Reference_ID WHERE w.Type = 'B' AND User_ID = %d", mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
	$warehouse = new Warehouse($dataQ->Row['Warehouse_ID']);
	$dataQ->Disconnect();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action','Action','hidden','administer','alpha',10,10);
	$form->AddField('confirm','Confirm','hidden','true','anything',4,4);
	$form->AddField('pid','','hidden',$_REQUEST['pid'],'numeric_unsigned',1,11);

	$notes = new Form($_SERVER['PHP_SELF']);
	$notes->AddField('date', 'Purchase Date', 'text', sprintf('%s/%s/%s', substr($purchase->PurchasedOn, 8, 2), substr($purchase->PurchasedOn, 5, 2), substr($purchase->PurchasedOn, 0, 4)), 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
	$notes->AddField('notes','Notes','textarea',$purchase->OrderNote,'anything',0,2000, false, 'style="width: 100%;" rows="5"');
	$notes->AddField('customreference','Customer Reference Number','text',$purchase->CustomReferenceNumber, 'anything', 0, 30, false);
	$notes->AddField('confirm','Confirm','hidden','true','alpha',4,4);
	$notes->AddField('action2','Action','hidden','notes','alpha',4,5);
	$notes->AddField('action','Action','hidden','administer','alpha',10,10);
	$notes->AddField('pid','','hidden',$purchase->ID,'numeric_unsigned',1,11);

	for($i = 0; $i<count($purchase->Line);$i++){
		$form->AddField('qty_'.$purchase->Line[$i]->ID, 'Quantity', 'text', $purchase->Line[$i]->Quantity, 'numeric_unsigned', 1, 9, true, 'size="5"');
		$form->AddField('cost_'.$purchase->Line[$i]->ID, 'Cost', 'text', number_format($purchase->Line[$i]->Cost, 2, '.', ''), 'float', 1, 11, true, 'size="5"');
	}

	if($_REQUEST['confirm']==true) {
		if($_REQUEST['action2'] == 'notes') {
			if($notes->Valid){
				$purchase->PurchasedOn = sprintf('%s-%s-%s 00:00:00', substr($notes->GetValue('date'), 6, 4), substr($notes->GetValue('date'), 3, 2), substr($notes->GetValue('date'), 0, 2));
				$purchase->OrderNote = $notes->GetValue('notes');
				$purchase->CustomReferenceNumber = $notes->GetValue('customreference');
				$purchase->Update();

				redirect(sprintf("Location: %s?action=administer&pid=%d", $_SERVER['PHP_SELF'], $purchase->ID));
			}

		} else {
			$fulfilled = true;

			for($i = 0; $i<count($purchase->Line);$i++) {
				$purchase->Line[$i]->Cost = $form->GetValue('cost_'. $purchase->Line[$i]->ID);
				
				$diff = $form->GetValue('qty_'.$purchase->Line[$i]->ID) - $purchase->Line[$i]->Quantity;

				if($diff != 0) {
					if($form->GetValue('qty_'.$purchase->Line[$i]->ID) < 0){
						$form->AddError("Initial quantities cannot be less than zero.");
					}

					if($form->Validate()) {
						$purchase->Line[$i]->QuantityDec += $diff;
						$purchase->Line[$i]->Quantity = $form->GetValue('qty_'.$purchase->Line[$i]->ID);

						if($purchase->Line[$i]->QuantityDec < 0) {
							$purchase->Line[$i]->Quantity += (0-$purchase->Line[$i]->QuantityDec);
							$purchase->Line[$i]->QuantityDec = 0;
						}
					}
				}

				$purchase->Line[$i]->Update();

				if($purchase->Line[$i]->Quantity == 0) {
					$purchase->Line[$i]->Delete();
				} else {
					if($purchase->Line[$i]->QuantityDec != 0){
						$fulfilled = false;
					}
				}
			}

			$purchase->GetLines();

			if($form->Valid) {
				$purchase->Status = "Unfulfilled";

				if($fulfilled){
					$purchase->Status = "Fulfilled";
				} else {
					for($i=0; $i < count($purchase->Line); $i++){
		            	$purchase->Line[$i]->Product->Get();

		            	if($purchase->Line[$i]->QuantityDec == 0){
							$partial = true;
						}
					}

					if($partial) {
						$purchase->Status = "Partially Fulfilled";
					}
				}

				$purchase->Update();

				redirect(sprintf("Location: %s?action=administer&pid=%d", $_SERVER['PHP_SELF'], $purchase->ID));
			}
		}
	}

	$page = new Page("Administer Purchase Order [#".$_REQUEST['pid']."]",'Here you can administer a purchase order');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	?>

				<table width="100%"  border="0" cellspacing="0" cellpadding="0">
	              <tr>
				    <td>

	                  <table cellpadding="0" cellspacing="0" border="0" class="invoiceAddresses">
	                  <tr>
	                    <td valign="top" class="billing"><p> <strong>Billing Address:</strong><br />
	                    <?php echo $purchase->GetSupplierAddress(); ?>
	                    <td valign="top" class="shipping"><p> <strong>Shipping Address:</strong><br />
	                    <?php echo $purchase->GetBranchShip(); ?>
	                  </tr>
	                </table>

	              </tr>
	              <tr>
	                <td colspan="2">
	                <br><br>

	                <?php
	                if(!$form->Valid) {
						echo $form->GetError();
						echo "<br>";
					}

                	echo $notes->Open();
					echo $notes->GetHTML('confirm');
					echo $notes->GetHTML('action2');
					echo $notes->GetHTML('action');
					echo $notes->GetHTML('pid');
					?>

					<table cellspacing="0" class="orderDetails">
					  <tr>
					    <th>Item</th>
					    <th>Value</th>
					  </tr>
					  <tr>
					  	<td><?php echo $notes->GetLabel('date'); ?></td>
					 	<td><?php echo $notes->GetHTML('date'); ?></td>
					  </tr>
					  <tr>
					  	<td><?php echo $notes->GetLabel('customreference'); ?></td>
					 	<td><?php echo $notes->GetHTML('customreference'); ?></td>
					  </tr>
					  <tr>
					  	<td><?php echo $notes->GetLabel('notes'); ?></td>
					 	<td><?php echo $notes->GetHTML('notes'); ?></td>
					  </tr>
					 </table>
					<br /><br />

					 <input type="submit" class="btn" value="update" name="updatenotes" />

					 <br />
					 <?php
					 echo $notes->Close();
					 ?>
					<br />

	                <table cellspacing="0" class="orderDetails">
	                  <tr>
						<th>&nbsp;</th>
	                    <th>Qty Incoming</th>
	                    <th>Product</th>
	                    <th>Quickfind</th>
	                    <th>Manufacturer</th>
	                    <th>Initial Quantity</th>
	                    <th>Cost</th>
	                    <th style="text-align: right;">Line Cost</th>
	                  </tr>

	                  <?php

	                  echo $form->Open();
	                  echo $form->GetHTML('action');
	                  echo $form->GetHTML('confirm');
	                  echo $form->GetHTML('pid');

	                  for($i=0; $i < count($purchase->Line); $i++){
	                  	$purchase->Line[$i]->Product->Get();
	                  	$purchase->Line[$i]->Manufacturer->Get();

	                  	$cost = $purchase->Line[$i]->Cost * $purchase->Line[$i]->Quantity;
				?>
	                  <tr>
	                  	<td width="1%">

	                  		<?php
	                  		if($purchase->Line[$i]->QuantityDec > 0) {
	                  			?>
	                  			<a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=removeline&pid=<?php echo $purchase->ID; ?>&lid=<?php echo $purchase->Line[$i]->ID; ?>"><img src="images/icon_trash_1.gif" alt="Remove" /></a>
	                  			<?php
	                  		}
	                  		?>

	                  	</td>
	                    <td>
						<?php echo $purchase->Line[$i]->QuantityDec; ?>x</td>
	                    <td>
							<?php echo $purchase->Line[$i]->Product->Name; ?><br><small>Part Number: <?php echo $purchase->Line[$i]->Product->SKU;?></small>
						</td>
						<td><a href='product_profile.php?pid=<?php echo $purchase->Line[$i]->Product->ID;?>'><?php echo $purchase->Line[$i]->Product->ID;?></a></td>
						<td><?php echo $purchase->Line[$i]->Manufacturer->Name; ?></td>
						<td nowrap="nowrap"><?php echo $form->GetHTML('qty_'.$purchase->Line[$i]->ID); ?></td>
						<td nowrap="nowrap">&pound;<?php echo $form->GetHTML('cost_'.$purchase->Line[$i]->ID); ?></td>
						<td nowrap="nowrap" align="right">&pound;<?php echo number_format($cost, 2, '.', ','); ?></td>
	                  </tr>

	                  <?php
	                  	$totalCost += $cost;
	                  }
				?>

				<tr>
	                  	<td width="1%">&nbsp;</td>
	                    <td>&nbsp;</td>
	                    <td>&nbsp;</td>
	                    <td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td align="right"><strong>&pound;<?php echo number_format($totalCost, 2, '.', ','); ?></strong></td>
	                  </tr>

	                  </table>
	                  <br>

					<input type="button" class="btn" value="back" onclick="window.self.location.href='purchase_administration.php'" />
					<input type="button" class="btn" value="add" onclick="window.self.location.href='purchase_administration.php?action=addline&pid=<?php echo $purchase->ID; ?>'" />
	             	<input type="submit" name="confirm" value="confirm administration" class="btn" />

	            </table>
				<?php
				echo $form->Close();
				$page->Display('footer');
}

require_once('lib/common/app_footer.php');