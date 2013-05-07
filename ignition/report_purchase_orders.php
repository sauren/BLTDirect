<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('supplier', 'Supplier', 'select', 0, 'numeric_unsigned', 1, 11);
	$form->AddOption('supplier', 0, '-- Select Supplier --');
	$form->AddField('date', 'Purchase Period', 'text', date('d/m/Y', mktime(0, 0, 0, date('m'), date('d')-1, date('Y'))), 'date_ddmmyyy', 10, 10, true, 'onclick="scwShow(this,this);" onfocus="scwShow(this,this);"');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, o.Org_Name, CONCAT(p.Name_First, ' ', p.Name_Last) AS Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID ORDER BY o.Org_Name ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], (strlen($data->Row['Org_Name']) > 0) ?  sprintf('%s (%s)', $data->Row['Org_Name'], $data->Row['Name']) : $data->Row['Name']);
		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->GetValue('supplier') == 0) {
			$form->AddError('Please select a supplier to report on.', 'supplier');
		}

		if($form->Validate()) {

		}
	}

	$page = new Page('Purchase Order Report', 'Please choose a supplier and period for your report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Report on purchase orders.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');

	echo $window->Open();
	echo $window->AddHeader('Select a supplier and period for your report.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHTML('supplier').$form->GetIcon('supplier'));
	echo $webForm->AddRow($form->GetLabel('date'), $form->GetHTML('date').$form->GetIcon('date'));
	echo $webForm->Close();
	echo $window->CloseContent();

	echo $window->AddHeader('Click below to submit your request');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow('&nbsp;', '<input type="button" name="submit" value="submit" class="btn" onclick="popUrl(\'print_purchases.php?supplier=\' + document.getElementById(\'supplier\').options[document.getElementById(\'supplier\').selectedIndex].value + \'&date=\' + document.getElementById(\'date\').value, 800, 600);" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}
?>