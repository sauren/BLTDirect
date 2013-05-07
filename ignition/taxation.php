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
} else {
	$session->Secure(2);
	view();
	exit;
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxClass.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Tax.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxCode.php');
	
	$taxClass = new TaxClass($_REQUEST['taxc']);
	
	$code = new TaxCode();
	$code->ID = isset($_REQUEST['code']) ? $_REQUEST['code'] : 0;
	$code->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('taxc', 'Tax Class ID', 'hidden', $taxClass->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('geozone', 'Geozone', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddOption('geozone', '0', '');

	$data = new DataQuery("select * from geozone order by Geozone_Title asc");
	while($data->Row){
		$form->AddOption('geozone', $data->Row['Geozone_ID'], $data->Row['Geozone_Title']);

		$data->Next();
	}
	$data->Disconnect();
	
	$taxCodes = array();

	$form->AddField('code', 'Tax Code', 'select', '0', 'numeric_unsigned', 1, 11, true, 'onchange="toggleTaxRate(this);"');
	$form->AddOption('code', '0', '');

	$data = new DataQuery("SELECT Tax_Code_ID, Integration_Reference, Description, Rate FROM tax_code ORDER BY Integration_Reference ASC");
	while($data->Row){
		$form->AddOption('code', $data->Row['Tax_Code_ID'], sprintf('T%s: %s (%s%%)', $data->Row['Integration_Reference'], $data->Row['Description'], $data->Row['Rate']));

		$taxCodes[] = sprintf('taxCodes.push(%s);', $data->Row['Tax_Code_ID']);
		$taxCodes[] = sprintf('taxCodes.push(%s);', $data->Row['Rate']);

		$data->Next();
	}
	$data->Disconnect();
	
	$form->AddField('rate', 'Tax Rate (%)', 'text', '', 'float', 1, 11, true, ($code->ID > 0) ? 'disabled="disabled"' : '');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($code->ID > 0) {
			$form->InputFields['rate']->Required = false;
		}

		if($form->Validate()){
			$tax = new Tax();
			$tax->ClassID = $form->GetValue('taxc');
			$tax->Geozone->ID = $form->GetValue('geozone');
			$tax->CodeID = $code->ID;
			$tax->Rate = ($code->ID > 0) ? $code->Rate : $form->GetValue('rate');
			$tax->Add();

			 redirect(sprintf("Location: taxation.php?taxc=%d", $taxClass->ID));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var taxCodes = new Array();
		%s

		var toggleTaxRate = function(obj) {
			var e = document.getElementById(\'rate\');

			if(e) {
				if(obj.value > 0) {
					e.setAttribute(\'disabled\', \'disabled\');

					for(var i=0; i<taxCodes.length; i=i+2) {
						if(taxCodes[i] == obj.value) {
							e.value = taxCodes[i+1];
							break;
						}
					}
				} else {
					e.removeAttribute(\'disabled\');
				}
		}
	}
		</script>', implode($taxCodes));

	$page = new Page(sprintf('<a href="taxation_classes.php">Tax Settings</a> &gt; <a href="taxation.php?taxc=%d">%s</a> &gt; Add Tax Setting', $taxClass->ID, $taxClass->Name),'');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Add a Taxation Rate.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('taxc');

	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('geozone'), $form->GetHTML('geozone') . $form->GetIcon('geozone'));
	echo $webForm->AddRow($form->GetLabel('code'), $form->GetHTML('code') . $form->GetIcon('code'));
	echo $webForm->AddRow($form->GetLabel('rate'), $form->GetHTML('rate') . $form->GetIcon('rate'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'taxation.php?taxc=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $taxClass->ID, $form->GetTabIndex()));
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxClass.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Tax.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxCode.php');
	
	$taxClass = new TaxClass($_REQUEST['taxc']);
	$tax = new Tax($_REQUEST['tax']);
	
	$code = new TaxCode();
	$code->ID = isset($_REQUEST['code']) ? $_REQUEST['code'] : $tax->CodeID;
	$code->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('taxc', 'Tax Class ID', 'hidden', $taxClass->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('tax', 'Tax ID', 'hidden', $tax->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('geozone', 'Geozone', 'select', $tax->Geozone->ID, 'numeric_unsigned', 1, 11);
	$form->AddOption('geozone', '0', '');

	$zones = new DataQuery("select * from geozone order by Geozone_Title asc");
	while($zones->Row){
		$form->AddOption('geozone', $zones->Row['Geozone_ID'], $zones->Row['Geozone_Title']);
		$zones->Next();
	}
	$zones->Disconnect();
	
	$form->AddField('code', 'Tax Code', 'select', $tax->CodeID, 'numeric_unsigned', 1, 11, true, 'onchange="toggleTaxRate(this);"');
	$form->AddOption('code', '0', '');

	$data = new DataQuery("SELECT Tax_Code_ID, Integration_Reference, Description, Rate FROM tax_code ORDER BY Integration_Reference ASC");
	while($data->Row){
		$form->AddOption('code', $data->Row['Tax_Code_ID'], sprintf('T%s: %s (%s%%)', $data->Row['Integration_Reference'], $data->Row['Description'], $data->Row['Rate']));

		$taxCodes[] = sprintf('taxCodes.push(%s);', $data->Row['Tax_Code_ID']);
		$taxCodes[] = sprintf('taxCodes.push(%s);', $data->Row['Rate']);
	
		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('rate', 'Tax Rate (%)', 'text', $tax->Rate, 'float', 1, 11, true, ($code->ID > 0) ? 'disabled="disabled"' : '');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($code->ID > 0) {
			$form->InputFields['rate']->Required = false;
		}

		if($form->Validate()){
			$tax->ClassID = $form->GetValue('taxc');
			$tax->Geozone->ID = $form->GetValue('geozone');
			$tax->CodeID = $code->ID;
			$tax->Rate = ($code->ID > 0) ? $code->Rate : $form->GetValue('rate');
			$tax->Update();

			redirect(sprintf("Location: taxation.php?taxc=%d", $taxClass->ID));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var taxCodes = new Array();
		%s

		var toggleTaxRate = function(obj) {
			var e = document.getElementById(\'rate\');

			if(e) {
				if(obj.value > 0) {
					e.setAttribute(\'disabled\', \'disabled\');

					for(var i=0; i<taxCodes.length; i=i+2) {
						if(taxCodes[i] == obj.value) {
							e.value = taxCodes[i+1];
							break;
						}
					}
				} else {
					e.removeAttribute(\'disabled\');
				}
		}
	}
		</script>', implode($taxCodes));

	$page = new Page(sprintf('<a href="taxation_classes.php">Tax Settings</a> &gt; <a href="taxation.php?taxc=%d">%s</a> &gt; Update Tax Setting', $taxClass->ID, $taxClass->Name),'');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Update Taxation Rate.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('taxc');
	echo $form->GetHTML('tax');
	echo $window->Open();
	echo $window->AddHeader('Required fields are marked with an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('geozone'), $form->GetHTML('geozone') . $form->GetIcon('geozone'));
	echo $webForm->AddRow($form->GetLabel('code'), $form->GetHTML('code') . $form->GetIcon('code'));
	echo $webForm->AddRow($form->GetLabel('rate'), $form->GetHTML('rate') . $form->GetIcon('rate'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'taxation.php?taxc=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $taxClass->ID, $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Tax.php');

	if(isset($_REQUEST['confirm']) && ($_REQUEST['confirm'] == 'true') && isset($_REQUEST['tax'])){
		$tax = new Tax;
		$tax->Remove($_REQUEST['tax']);
	} 

	redirect("Location: taxation.php?taxc=" . $_REQUEST['taxc']);
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TaxClass.php');
	
	$taxClass = new TaxClass($_REQUEST['taxc']);
	
	$page = new Page('<a href="taxation_classes.php">Tax Settings</a> &gt; ' . $taxClass->Name ,'Specify the Geographical Zone and the Tax Rate that applies to that zone. Any Zone not displayed is not charged Tax.');
	$page->Display('header');
	
	$table = new DataTable('tax');
	$table->SetSQL(sprintf("SELECT t.Tax_ID, t.Tax_Rate, g.Geozone_Title FROM tax AS t LEFT JOIN geozone AS g ON g.Geozone_ID=t.Geozone_ID WHERE t.Tax_Class_ID=%d", mysql_real_escape_string($taxClass->ID)));
	$table->AddField('ID#', 'Tax_ID', 'right');
	$table->AddField('Zone', 'Geozone_Title', 'left');
	$table->AddField('Rate (%)', 'Tax_Rate', 'right');
	$table->AddLink("taxation.php?action=update&tax=%s", "<img src=\"./images/icon_edit_1.gif\" alt=\"Update\" border=\"0\">", "Tax_ID");
	$table->AddLink("javascript:confirmRequest('taxation.php?action=remove&confirm=true&tax=%s','Are you sure you want to remove this Tax Setting? Note: this operation will remove this tax information from the associated Zone and the zone will no longer be subject to tax.');", "<img src=\"./images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Tax_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Geozone_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br /><input type="button" name="add" value="add a new class" class="btn" onclick="window.location.href=\'taxation.php?action=add&taxc=' . $taxClass->ID .'\'">';

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
