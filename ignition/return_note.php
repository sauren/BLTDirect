<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataQuery.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Return.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/HtmlElement.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Form.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/WarehouseStock.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Page.php');

$session->Secure(3);

$return = new ProductReturn($_REQUEST['id']);

$page = new Page('Add Note', 'Add a note to this return.');
$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('id', 'id', 'hidden', $REQUEST['id'], 'numeric_unsigned', 1, 11);
$form->AddField('note', 'note', 'textarea', $return->AdminNote, 'paragraph', 1, 1024, true, 'cols="40" rows="10"');
$form->AddField('confirm', 'confirm', 'hidden', 'true', 'paragraph', 1, 1024);

$page->Display('header');
if($action == 'update'
&& $form->GetValue('confirm') == 'true')
{
    if($form->Validate()){
        $return->AdminNote = $form->GetValue('note');
		$return->Update();

        redirect(sprintf("Location: return_details.php?id=%d", $return->ID));
    }
}
echo $form->Open();
echo $form->GetHTML('id');
echo $form->GetHTML('note');
echo '<br /><br /><input type="submit" name="action" value="update" class="btn" />';
echo $form->Close();

$page->Display('footer');
?>