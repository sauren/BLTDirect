<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Alert.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

$session->Secure(3);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('referenceid', 'Reference ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('owner', 'Owner', 'hidden', '', 'anything', 1, 120);
$form->AddField('type', 'Type', 'select', '', 'paragraph', 1, 120);
$form->AddOption('type', '', '');

switch(strtolower($form->GetValue('owner'))) {
	case 'product':
		$form->AddOption('type', 'No image', 'No image');
		$form->AddOption('type', 'No shipping', 'No shipping');
		$form->AddOption('type', 'Incorrect grammar', 'Incorrect grammar');
		$form->AddOption('type', 'Requires specification', 'Requires specification');
		$form->AddOption('type', 'Other', 'Other');
		break;
		
	case 'order':
		$form->AddOption('type', 'Price alteration', 'Price alteration');
		$form->AddOption('type', 'Price too low', 'Price too low');
		$form->AddOption('type', 'Price too high', 'Price too high');
		$form->AddOption('type', 'Monitor stock reorder value too low', 'Monitor stock reorder value too low');
		$form->AddOption('type', 'Monitor stock reorder value too high', 'Monitor stock reorder value too high');
		$form->AddOption('type', 'Running out of stock', 'Running out of stock');
		$form->AddOption('type', 'Stock higher quantity', 'Stock higher quantity');
		$form->AddOption('type', 'Stock issue', 'Stock issue');
		$form->AddOption('type', 'Other', 'Other');

		$form->AddField('referenceid2', 'Product', 'select', '0', 'numeric_unsigned', 1, 11);
		$form->AddOption('referenceid2', '0', '');
		
		$data = new DataQuery(sprintf("SELECT Product_ID, Product_Title FROM order_line WHERE Order_ID=%d ORDER BY Product_Title ASC", mysql_real_escape_string($form->GetValue('referenceid'))));
		while($data->Row) {
			$form->AddOption('referenceid2', $data->Row['Product_ID'], strip_tags($data->Row['Product_Title']));
			
			$data->Next();	
		}
		$data->Disconnect();
		break;
}
		
$form->AddField('description', 'Description', 'textarea', '', 'anything', null, null, false, 'rows="5" style="width: 300px; font-family: arial, sans-serif;"');

$returnTitle = '';
$returnLink = '';

switch(strtolower($form->GetValue('owner'))) {
	case 'product':
		$returnTitle = 'Product Profile';
		$returnLink = 'product_profile.php?pid=' . $form->GetValue('referenceid');
		break;
		
	case 'order':
		$returnTitle = 'Order Details';
		$returnLink = 'order_details.php?orderid=' . $form->GetValue('referenceid');
		break;
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$alert = new Alert();
		$alert->referenceId = $form->GetValue('referenceid');
		
		switch(strtolower($form->GetValue('owner'))) {
			case 'order':
				$alert->referenceId2 = $form->GetValue('referenceid2');
				break;	
		}
	
		$alert->owner = $form->GetValue('owner');
		$alert->type = $form->GetValue('type');
		$alert->description = $form->GetValue('description');
		$alert->add();

		redirectTo($returnLink);
	}
}

$page = new Page(sprintf('<a href="%s">%s</a> &gt; Add Alert', $returnLink, $returnTitle), 'Add a new alert message.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Add alert');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('referenceid');
echo $form->GetHTML('owner');

echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));

switch(strtolower($form->GetValue('owner'))) {
	case 'order':
		echo $webForm->AddRow($form->GetLabel('referenceid2'), $form->GetHTML('referenceid2') . $form->GetIcon('referenceid2'));	
		break;
}

echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onclick="window.self.location=\'%s\';" /> <input type="submit" name="add" value="add" class="btn" tabindex="%s" />', $returnLink, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');