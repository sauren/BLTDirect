<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$product = new Product($_REQUEST['pid']);

$form = new Form("product_description.php");
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('pid', 'ID', 'hidden', $product->ID, 'numeric_unsigned', 1, 11);
$form->AddField('blurb', 'Blurb', 'textarea', $product->Blurb, 'paragraph', 1, 255, true, 'style="width:100%; height:200px"');
$form->AddField('description', 'Description', 'textarea', $product->Description, 'anything', NULL, NULL, true, 'style="width:100%; height:300px"');

if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
	if($form->Validate()){
		$product->Description = $form->GetValue('description');
		$product->Blurb = $form->GetValue('blurb');
		$product->Update();
		
		redirect(sprintf("Location: product_profile.php?pid=%d", $product->ID));
	}
}

$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> > Descriptions', $_REQUEST['pid']),'The more information you supply the better your system will become');
$page->SetEditor(true);
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

$window = new StandardWindow('Update');
echo $form->Open();
echo $form->GetHTML('action');
echo $form->GetHTML('confirm');
echo $form->GetHTML('pid');
echo $window->Open();
echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
echo $window->OpenContent();
$webForm = new StandardForm;
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('blurb'), $form->GetHTML('blurb'));
echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description'));

echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_profile.php?pid=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $product->ID, $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');