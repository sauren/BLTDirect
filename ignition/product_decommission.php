<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(3);

$product = new Product($_REQUEST['pid']);

$supersededProduct = new Product();
 
if($product->SupersededBy > 0) {
	$supersededProduct->Get($product->SupersededBy);		
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('pid', 'Product ID', 'hidden', '', 'numeric_unsigned', 1, 11);
$form->AddField('product', '', 'hidden', $supersededProduct->ID, 'numeric_unsigned', 1, 11, false);
$form->AddField('name', 'Superseded By', 'text', $supersededProduct->Name, 'anything', 3, 255, false, 'onFocus="this.blur();"');
$form->AddField('description', 'Reason', 'textarea', $product->DiscontinuedBecause, 'paragraph', 1, 255, true, 'rows="5" style="width: 300px;"');
$form->AddField('date', 'Discontinued Date', 'text', ($product->Discontinued == 'N') ? date('d/m/Y') : sprintf('%s/%s/%s', substr($product->DiscontinuedOn, 8, 2), substr($product->DiscontinuedOn, 5, 2), substr($product->DiscontinuedOn, 0, 4)), 'date_ddmmyyy', 1, 10, true, 'onclick="scwShow(this, this);" onfocus="scwShow(this, this);"');
$form->AddField('showprice', 'Show Price', 'checkbox', $product->DiscontinuedShowPrice, 'boolean', 1, 1, false);

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		$product->Discontinued = 'Y';
		$product->DiscontinuedBecause = $form->GetValue('description');
		$product->DiscontinuedOn = sprintf('%s-%s-%s 00:00:00', substr($form->GetValue('date'), 6, 4), substr($form->GetValue('date'), 3, 2), substr($form->GetValue('date'), 0, 2));
		$product->DiscontinuedBy = $GLOBALS['SESSION_USER_ID'];
		$product->DiscontinuedShowPrice = $form->GetValue('showprice');
		$product->SupersededBy = $form->GetValue('product');
		$product->Update();

		$cache = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND']);
		$cache->remove('product__' . $_REQUEST['pid']);
		$cache->remove('product_prices__product_id__' . $_REQUEST['pid']);
		
		redirectTo('product_profile.php?pid=' . $form->GetValue('pid'));
	}
}

$script = sprintf('<script language="javascript" type="text/javascript">
	var productResponseHandler = function(details) {
		var items = details.split("{br}\n");
		var e = null;
		
		e = document.getElementById(\'product\');

		if(e) {
			e.value = items[0];
		}
		
		e = document.getElementById(\'name\');

		if(e) {
			e.value = items[1];
		}
	}

	var productResponseError = function() {
		alert(\'The Product ID could not be found.\');
	}

	var productRequest = new HttpRequest();
	productRequest.setCaching(false);
	productRequest.setHandlerResponse(productResponseHandler);
	productRequest.setHandlerError(productResponseError);

	var foundProduct = function(pid) {
		productRequest.get(\'lib/util/getProduct.php?id=\' + pid);
	}
	</script>');

$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Discontinue', $_REQUEST['pid']),'The more information you supply the better your system will become.');
$page->LinkScript('js/scw.js');
$page->AddToHead($script);
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}
$window = new StandardWindow("Discontinue Product.");
$webForm = new StandardForm;

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('pid');
echo $form->GetHTML('product');
 
echo $window->Open();
echo $window->AddHeader('All fields are required on this form');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('date'), $form->GetHTML('date') . $form->GetIcon('date'));
echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
echo $webForm->AddRow($form->GetLabel('name') . '<a href="javascript:popUrl(\'popFindProduct.php?callback=foundProduct\', 650, 500);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', $form->GetHTML('name') . $form->GetIcon('name'));
echo $webForm->AddRow($form->GetLabel('showprice'), $form->GetHTML('showprice') . $form->GetIcon('showprice'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_profile.php?pid=%s\';"> <input type="submit" name="discontinue" value="discontinue" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');