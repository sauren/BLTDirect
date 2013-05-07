<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/GoogleConversion.php');

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('year', 'Year', 'select', date('Y'), 'numeric_unsigned', 1, 11, true);

for($i=date('Y')-5; $i<=date('Y')+5; $i++) {
	$form->AddOption('year', $i, $i);
}

for($i=1; $i<=12; $i++) {
	$form->AddField('month_'.$i, date('F', mktime(0, 0, 0, $i, 1, date('Y'))), 'text', '0', 'float', 1, 11, true);
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate() ){
		for($i=1; $i<=12; $i++) {
			if($form->GetValue('month_'.$i) > 0) {
                $conversion = new GoogleConversion();
				$conversion->Conversions = $form->GetValue('month_'.$i);
				$conversion->Month = date('Y-m-d H:i:s', mktime(0, 0, 0, $i, 1, $form->GetValue('year')));
				$conversion->Add();
			}
		}

		redirect('Location: ?action=view');
	}
}

$page = new Page('Google Conversions Generator', 'Generate Google conversions for an entire year.');
$page->AddOnLoad("document.getElementById('name').focus();");
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Generate Google Conversions');
$webForm = new StandardForm();

echo $form->Open();
echo $form->GetHTML('confirm');

echo $window->Open();
echo $window->AddHeader('Enter general details.');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('year'), $form->GetHTML('year') . $form->GetIcon('year'));
echo $webForm->Close();
echo $window->CloseContent();

echo $window->AddHeader('Enter monthly conersion figures.');
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