<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/ProductSearch.php');

$session->Secure(3);
view();
exit;

function view(){
    require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Form.php');
    require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/StandardWindow.php');
    require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/StandardForm.php');
    require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/DataTable.php');
    require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Product.php');

    $serve = (isset($_REQUEST['serve']))?$_REQUEST['serve']:'view';

    $page = new Page('Product Search','');
    $form = new Form($_SERVER['PHP_SELF'], 'get');
    $form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
    $form->AddField('serve', 'Serve', 'hidden', $serve, 'alpha', 1, 6);
    $form->AddField('string', 'Search for...', 'text', '', 'paragraph', 1, 255);

    $window = new StandardWindow("Search for a Product.");
    $webForm = new StandardForm;


    $sql = "";
    if(isset($_REQUEST['string']) && !empty($_REQUEST['string'])){
        if($form->Validate()){
            $search = new ProductSearch($_REQUEST['string'],'./product_profile.php?pid=');
            $search->PrepareSQL();

            $table = new DataTable('results');
            $table->AddField('ID#', 'Product_ID', 'left');
            $table->AddField('Title', 'Product_Title', 'left');
        /*	if($serve == "pop"){
                $table->AddLink("product_search.php?action=use&pid=%s",
                                "[use]",
                                "Product_ID");
            } else {
                $table->AddLink("product_profile.php?pid=%s",
                                "<img src=\"./images/icon_edit_1.gif\" alt=\"Update Settings\" border=\"0\">",
                                "Product_ID");
            }*/
            $table->SetSQL($search->Query);
            $table->SetMaxRows(10);
            $table->Order = 'DESC';
            $table->OrderBy = 'score';
            $table->Finalise();
            $table->ExecuteSQL();
        }
    }

    $page->Display('header');
    if(!$form->Valid){
        echo $form->GetError();
        echo "<br>";
    }
    echo $form->Open();
    echo $form->GetHTML('confirm');
    echo $form->GetHTML('serve');
    echo $window->Open();
    echo $window->AddHeader('You can enter a sentence below. The more words you include the closer your results will be.');
    echo $window->OpenContent();
    echo $webForm->Open();
    echo $webForm->AddRow($form->GetLabel('string'), $form->GetHTML('string') . '<input type="submit" name="search" value="search" class="btn" />');
    echo $webForm->Close();
    echo $window->CloseContent();
    echo $window->Close();
    echo $form->Close();
    echo "<br>";


    if(isset($_REQUEST['string']) && !empty($_REQUEST['string'])){
        //$table->DisplayTable();
        $table->DisplayNavigation();
        echo $table->GetTableHeader();
        while($table->Table->Row){
            $prod = new Product($table->Table->Row['Product_ID']);

            echo sprintf('<tr><td><img src="../images/products/%s" /></td>', $prod->DefaultImage->Thumb->FileName);

            echo sprintf('<td><strong><a href="product_profile.php?pid=%s">%s</a></strong><br />Quickfind: <strong>%s</strong>, SKU: %s, Price &pound;%s (Inc. VAT)</td>',$prod->ID, $prod->Name, $prod->ID, $prod->SKU, number_format($prod->PriceCurrentIncTax, 2));

            if($serve == "pop"){
                echo sprintf('<td><a href="product_search.php?action=use&pid=%s">[USE]</a></td></tr>', $prod->ID);
            } else {
                echo sprintf('<td><a href="product_profile.php?pid=%s"><img src="./images/icon_edit_1.gif" alt="Update Settings" border="0"></a></td>', $prod->ID);
            }
            echo '</tr>';
            $table->Next();
        }

        echo '</table>';
        echo "<br>";
        $table->DisplayNavigation();
        echo "<br>";
    }
    $page->Display('footer');
    require_once('lib/common/app_footer.php');
}
?>
