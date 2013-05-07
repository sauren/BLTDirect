<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$form = new Form($_SERVER['PHP_SELF'], 'GET');
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('quoteid', 'Quote Number', 'text', '', 'numeric_unsigned', 1, 11, false);
$form->AddField('productid', 'Product Number', 'text', '', 'numeric_unsigned', 1, 11, false);

$sqlSelect = '';
$sqlFrom = '';
$sqlWhere = '';
$sqlOther = '';

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$sqlSelect = sprintf("SELECT q.*, c.Country ");
		$sqlFrom = sprintf("FROM quote AS q LEFT JOIN countries AS c ON c.Country_ID=q.Shipping_Country_ID ");
		$sqlWhere = sprintf("WHERE TRUE ");
		$sqlOther = sprintf("GROUP BY q.Quote_ID ");

		if(strlen($form->GetValue('quoteid')) > 0) {
			$sqlWhere .= sprintf('AND q.Quote_ID=%1$d ', $form->GetValue('quoteid'));
		}
		
		if((strlen($form->GetValue('productid')) > 0)) {
			$sqlFrom .= sprintf('INNER JOIN quote_line AS ql ON ql.Quote_ID=q.Quote_ID ');
			
			if(strlen($form->GetValue('productid')) > 0) {
				$sqlWhere .= sprintf('AND ql.Product_ID=%1$d ', $form->GetValue('productid'));
			}
		}
	}
}

$page = new Page('Quote Search', 'Search for a quotation here.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Search for a quote');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Search for quotes by any of the below fields.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('quoteid'), $form->GetHTML('quoteid'));
echo $webForm->AddRow($form->GetLabel('productid'), $form->GetHTML('productid'));
echo $webForm->AddRow('', '<input type="submit" name="search" value="search" class="btn" />');
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

echo '<br />';

if(isset($_REQUEST['confirm'])) {
	$table = new DataTable('quotes');
	$table->SetSQL(sprintf("%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlOther));
	$table->SetExtractVars();
	$table->AddField('ID#', 'Quote_ID', 'left');
	$table->AddField('Date', 'Created_On', 'left');
	$table->AddField('Status', 'Status', 'left');
	$table->AddField('Organisation', 'Shipping_Organisation_Name', 'left');
	$table->AddField('Name', 'Billing_First_Name', 'left');
	$table->AddField('Surname', 'Billing_Last_Name', 'left');
	$table->AddField('Zip', 'Billing_Zip', 'left');
	$table->AddField('Shipping Name', 'Shipping_First_Name', 'left');
	$table->AddField('Shipping Surname', 'Shipping_Last_Name', 'left');
	$table->AddField('Shipping Zip', 'Shipping_Zip', 'left');
	$table->AddField('Shipping Country', 'Country', 'left');
	$table->AddLink("quote_details.php?quoteid=%s", "<img src=\"./images/folderopen.gif\" alt=\"Update\" border=\"0\">", "Quote_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Created_On");
	$table->Order = 'DESC';
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();
}

$page->Display('footer');
require_once('lib/common/app_footer.php');