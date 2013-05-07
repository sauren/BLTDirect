<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Overhead.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('type', 'Type', 'select', '', 'numeric_unsigned', 1, 11, true);
$form->AddOption('type', '', '');

$data = new DataQuery(sprintf("SELECT Overhead_Type_ID, Name FROM overhead_type ORDER BY Name ASC"));
while($data->Row) {
	$form->AddOption('type', $data->Row['Overhead_Type_ID'], $data->Row['Name']);

	$data->Next();
}
$data->Disconnect();

$form->AddField('name', 'Name', 'text', '', 'anything', 1, 128, true);
$form->AddField('year', 'Year', 'select', date('Y'), 'numeric_unsigned', 1, 11, true);

for($i=date('Y')-5; $i<=date('Y')+5; $i++) {
	$form->AddOption('year', $i, $i);
}

for($i=1; $i<=12; $i++) {
	$form->AddField('month_'.$i, date('F', mktime(0, 0, 0, $i, 1, date('Y'))), 'text', '0.00', 'float', 1, 11, true);
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate() ){
		for($i=1; $i<=12; $i++) {
			if($form->GetValue('month_'.$i) > 0) {
                $overhead = new Overhead();
				$overhead->Type->ID = $form->GetValue('type');
				$overhead->Name = $form->GetValue('name');
				$overhead->Value = $form->GetValue('month_'.$i);
				$overhead->Period = 'M';
				$overhead->StartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, $i, 1, $form->GetValue('year')));
				$overhead->EndDate = date('Y-m-d H:i:s', mktime(0, 0, 0, $i + 1, 1, $form->GetValue('year')));
				$overhead->Add();
			}
		}

		redirect('Location: overheads.php');
	}
}

$page = new Page('Overhead Generator', 'Generate overheads for an entire year.');
$page->AddOnLoad("document.getElementById('name').focus();");
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Generate Overhead');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Enter general details.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('type'), $form->GetHTML('type') . $form->GetIcon('type'));
echo $webForm->AddRow($form->GetLabel('name'), $form->GetHTML('name') . $form->GetIcon('name'));
echo $webForm->AddRow($form->GetLabel('year'), $form->GetHTML('year') . $form->GetIcon('year'));
echo $webForm->Close();
echo $window->CloseContent();

echo $window->AddHeader('Enter monthly overheads.');
echo $window->OpenContent();
echo $webForm->Open();

for($i=1; $i<=12; $i++) {
	echo $webForm->AddRow($form->GetLabel('month_'.$i), $form->GetHTML('month_'.$i) . $form->GetIcon('month_'.$i));
}

echo $webForm->AddRow('', sprintf('<input type="submit" name="generate" value="generate" class="btn" tabindex="%s" />', $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();

echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');