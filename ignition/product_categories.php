<?php
ini_set('max_execution_time', '1800');

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
} elseif($action == "getnode"){
	$session->Secure(3);
	getNode();
	exit;
} elseif($action == "removeimage"){
	$session->Secure(3);
	removeImage();
	exit;
} elseif($action == "removerelated"){
	$session->Secure(3);
	removeRelated();
	exit;
} elseif($action == "moveup"){
	$session->Secure(3);
	moveUp();
	exit;
} elseif($action == "movedown"){
	$session->Secure(3);
	moveDown();
	exit;
} elseif($action == "duplicate"){
	$session->Secure(3);
	duplicate();
	exit;
} elseif($action == "markupprice"){
	$session->Secure(3);
	markupPrice();
	exit;
} elseif($action == "markupcost"){
	$session->Secure(3);
	markupCost();
	exit;
} elseif($action == "googlebase"){
	$session->Secure(3);
	googleBase();
	exit;
} elseif($action == "managestock"){
	$session->Secure(3);
	manageStock();
	exit;
} elseif($action == "manageshipping"){
	$session->Secure(3);
	manageShipping();
	exit;
} elseif($action == "managewatches"){
	$session->Secure(3);
	manageWatches();
	exit;
} elseif($action == "managereview"){
	$session->Secure(3);
	manageReview();
	exit;
} elseif($action == "manageprimaryspecs"){
	$session->Secure(3);
	managePrimarySpecs();
	exit;
} elseif($action == "managerelated"){
	$session->Secure(3);
	manageRelated();
	exit;
} elseif($action == "managelinks"){
	$session->Secure(3);
	manageLinks();
	exit;
} elseif($action == "managequality"){
	$session->Secure(3);
	manageQuality();
	exit;
} elseif($action == "managequalityproducts"){
	$session->Secure(3);
	manageQualityProducts();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function moveUp() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Sequencer.php');

	$category = new Category($_REQUEST['node']);

	if($category->Parent->ID > 0) {
		$category->Parent->Get();

		if($category->Parent->CategoryOrder != 'Category_ID') {
			$seq = new Sequencer('product_categories');
			$seq->MoveUp();
		}
	}

	redirect(sprintf("Location: %s?cat=%d&error=%s", $_SERVER['PHP_SELF'], $category->ID, urlencode('Unable to move category. Please ensure that the category is not at the top level and that its parent allows manual ranking of sub categories.')));
}

function moveDown() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/Sequencer.php');

	$category = new Category($_REQUEST['node']);

	if($category->Parent->ID > 0) {
		$category->Parent->Get();

		if($category->Parent->CategoryOrder != 'Category_ID') {
			$seq = new Sequencer('product_categories');
			$seq->MoveDown();
		}
	}

	redirect(sprintf("Location: %s?cat=%d&error=%s", $_SERVER['PHP_SELF'], $category->ID, urlencode('Unable to move category. Please ensure that the category is not at the top level and that its parent allows manual ranking of sub categories.')));
}

function googleBase() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	$category = new Category();

	if(!isset($_REQUEST['node']) || !$category->Get($_REQUEST['node'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'googlebase', 'alpha', 10, 10);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('suffix', 'Google Base Suffix', 'text', '', 'anything', 0, 255, false);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			googleBaseCategory($form->GetValue('node'), $form->GetValue('suffix'));

			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $form->GetValue('node')));
		}
	}

	$page = new Page(sprintf("Google Base: %s", $category->Name), 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Google Base Suffix');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('suffix'), $form->GetHTML('suffix'). $form->GetIcon('suffix'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function googleBaseCategory($categoryId, $suffix) {
	if($categoryId == 0) {
		new DataQuery(sprintf("UPDATE product SET Google_Base_Suffix='%s'", mysql_real_escape_string($suffix)));
	} else {
		$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			new DataQuery(sprintf("UPDATE product SET Google_Base_Suffix='%s' WHERE Product_ID=%d", mysql_real_escape_string($suffix), $data->Row['Product_ID']));

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			googleBaseCategory($data->Row['Category_ID'], $suffix);

			$data->Next();
		}
		$data->Disconnect();
	}
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	if(isset($_REQUEST['node']) && is_numeric($_REQUEST['node'])){
		$category = new Category();
		$category->ID = $_REQUEST['node'];
		$category->Remove();
	}

	redirect("Location: product_categories.php");
}

function removeImage(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	if($_REQUEST['type'] === 'large'){
		$category = new Category($_REQUEST['node']);
		$category->Large->Delete();
		$category->Large->Width=0;
		$category->Large->Height=0;
		$category->Large->SetName('');
		$category->Update();
	} elseif($_REQUEST['type'] === 'thumb'){
		$category = new Category($_REQUEST['node']);
		$category->Thumb->Delete();
		$category->Thumb->Width=0;
		$category->Thumb->Height=0;
		$category->Thumb->SetName('');
		$category->Update();
	}

	redirect("Location: product_categories.php?action=update&node=" . $_REQUEST['node']);
}

function removeRelated() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductRelated.php');

	if(isset($_REQUEST['id'])) {
		$related = new ProductRelated();
		$related->Delete($_REQUEST['id']);
	}

	if(isset($_REQUEST['node'])) {
		redirectTo('?action=managerelated&node=' . $_REQUEST['node']);
	}

	redirectTo('?action=view');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/UrlAlias.php");

	$category = new Category($_REQUEST['node']);
	$category->GetParentInfo();
	$category->ProductOffer->Get();

	$urlAlias = '';

	if($category->UseUrlAlias == 'Y') {
		$data = new DataQuery(sprintf("SELECT Alias FROM url_alias WHERE Type LIKE 'Category' AND Reference_ID=%d", mysql_real_escape_string($category->ID)));
		if($data->TotalRows > 0) {
			$urlAlias = $data->Row['Alias'];
		}
		$data->Disconnect();
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', $category->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Parent', 'hidden', $category->Parent->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Category Title', 'text', $category->Name, 'paragraph', 1, 150);
	$form->AddField('image', 'New Image', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('thumb', 'New Thumbnail', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('description', 'Category Description', 'textarea', $category->Description, 'paragraph', 1, 20000, false, 'style="width:100%; height:300px"');
	$form->AddField('descriptionsecondary', 'Category Description (Secondary)', 'textarea', $category->DescriptionSecondary, 'paragraph', 1, 20000, false, 'style="width:100%; height:300px"');
	$form->AddField('metaTitle', 'Meta Title', 'text', $category->MetaTitle, 'paragraph', 1, 255, false);
	$form->AddField('metaKeywords', 'Meta Keywords', 'text', $category->MetaKeywords, 'paragraph', 1, 255, false, 'style="width:100%;"');
	$form->AddField('metaDescription', 'Meta Description', 'text', $category->MetaDescription, 'paragraph', 1, 255, false, 'style="width:100%;"');
	$form->AddField('searchterm', 'Search Term', 'text', $category->SearchTerm, 'anything', 1, 255, false);
	$form->AddField('searchtermtitle', 'Search Term Title', 'text', $category->SearchTermTitle, 'anything', 1, 255, false);
	$form->AddField('active', 'Is Active', 'checkbox', $category->IsActive, 'boolean', 1, 1, false);
	$form->AddField('showimage', 'Show Image', 'checkbox', $category->ShowImage, 'boolean', 1, 1, false);
	$form->AddField('showimages', 'Show Images', 'checkbox', $category->ShowImages, 'boolean', 1, 1, false);
	$form->AddField('showdescriptions', 'Show Descriptions', 'checkbox', $category->ShowDescriptions, 'boolean', 1, 1, false);
	$form->AddField('showbestbuys', 'Show Best Buys', 'checkbox', $category->ShowBestBuys, 'boolean', 1, 1, false);
	$form->AddField('columncounttext', 'Columns (Text Only)', 'text', $category->ColumnCountText, 'numeric_unsigned', 1, 11);
	$form->AddField('order', 'Order', 'select', $category->Order, 'anything', 0, 45);
	$form->AddOption('order', 'auto_id', 'Auto ID#');
	$form->AddOption('order', 'sku', 'SKU');
	$form->AddOption('order', 'product_title', 'Product Title');
	$form->AddOption('order', 'rank', 'Rank');
	$form->AddField('catorder', 'Category Order', 'select', $category->CategoryOrder, 'anything', 0, 45);
	$form->AddOption('catorder', 'Category_ID', 'Auto ID#');
	$form->AddOption('catorder', 'Category_Title', 'Category Title');
	$form->AddOption('catorder', 'Sequence', 'Rank');
	$form->AddField('product', 'Offer Product ID', 'hidden', $category->ProductOffer->ID, 'numeric_unsigned', 1, 11, false);
	$form->AddField('name', 'Offer Product', 'text', $category->ProductOffer->Name, 'anything', null, null, false, 'onFocus="this.Blur();"');
	$form->AddField('isredirecting', 'Is Redirecting', 'checkbox', $category->IsRedirecting, 'boolean', 1, 1, false);
	$form->AddField('redirecturl', 'Redirect URL', 'text', $category->RedirectUrl, 'anything', 1, 2048, false, 'style="width: 100%"');
	$form->AddField('isfilteravailable', 'Is Filter Available', 'checkbox', $category->IsFilterAvailable, 'boolean', 1, 1, false);
	$form->AddField('isproductlistavailable', 'Is Product List Available', 'checkbox', $category->IsProductListAvailable, 'boolean', 1, 1, false);
	$form->AddField('useurlalias', 'Use URL Alias', 'checkbox', $category->UseUrlAlias, 'boolean', 1, 1, false, 'onclick="toggleUrlAlias(this);"');
	$form->AddField('urlalias', 'URL Alias', 'text', $urlAlias, 'anything', 1, 1024, false, ($form->GetValue('useurlalias') == 'N') ? 'disabled="disabled"' : '');
	$form->AddField('layout', 'Layout', 'select', $category->Layout, 'anything');
	$form->AddOption('layout', 'Table', 'Table');
	$form->AddOption('layout', 'Grid', 'Grid');
	$form->AddField('mode', 'Mode', 'select', $category->CategoryMode, 'anything');
	$form->AddOption('mode', 'Normal', 'Normal');
	$form->AddOption('mode', 'Box Rate', 'Box Rate');
	$form->AddField('pricecolour', 'Price Colour', 'text', $category->PriceColour, 'anything', 3, 6, false);
	$form->AddField('pricesize', 'Price Size', 'select', $category->PriceSize, 'numeric_unsigned');
	$priceSize = 14;
	while($priceSize <= 36){
		$form->AddOption('pricesize', $priceSize, $priceSize);
		$priceSize++;
	}
	$form->AddField('priceweight', 'Price Weight', 'select', $category->PriceWeight, 'anything');
	$form->AddOption('priceweight', 'Normal', 'Normal');
	$form->AddOption('priceweight', 'Italic', 'Italic');
	$form->AddOption('priceweight', 'Bold', 'Bold');
	$form->AddOption('priceweight', 'Bold Italic', 'Bold Italic');
	$form->AddField('showbuybutton', 'Show Buy Button', 'select', $category->ShowBuyButton, 'anything');
	$form->AddOption('showbuybutton', 'Y', 'Yes');
	$form->AddOption('showbuybutton', 'N', 'No');

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			if($category->Parent->ID != $form->GetValue('parent')) {
				$categories = getSubCategories($category->ID);

				$exists = false;

				foreach($categories as $categoryId) {
					if($form->GetValue('parent') == $categoryId) {
						$exists = true;
						break;
					}
				}

				if($exists) {
					$form->AddError('Parent cannot be set to this category or any of its sub categories.', 'parent');
				}
			}

			if($form->Valid) {
				$category->Name = $form->GetValue('title');
				$category->Parent->ID = $form->GetValue('parent');
				$category->Description = $form->GetValue('description');
				$category->DescriptionSecondary = $form->GetValue('descriptionsecondary');
				$category->MetaTitle = $form->GetValue('metaTitle');
				$category->MetaDescription = $form->GetValue('metaDescription');
				$category->MetaKeywords = $form->GetValue('metaKeywords');
				$category->SearchTerm = $form->GetValue('searchterm');
				$category->SearchTermTitle = $form->GetValue('searchtermtitle');
				$category->IsActive = $form->GetValue('active');
				$category->Order = $form->GetValue('order');
				$category->CategoryOrder = $form->GetValue('catorder');
				$category->ProductOffer->ID = $form->GetValue('product');
				$category->ShowImage = $form->GetValue('showimage');
				$category->ShowImages = $form->GetValue('showimages');
				$category->ShowDescriptions = $form->GetValue('showdescriptions');
				$category->ShowBestBuys = $form->GetValue('showbestbuys');
				$category->ColumnCountText = $form->GetValue('columncounttext');
				$category->IsRedirecting = $form->GetValue('isredirecting');
				$category->RedirectUrl = $form->GetValue('redirecturl');
				$category->IsFilterAvailable = $form->GetValue('isfilteravailable');
				$category->IsProductListAvailable = $form->GetValue('isproductlistavailable');
				$category->UseUrlAlias = $form->GetValue('useurlalias');
				$category->Layout = $form->GetValue('layout');
				$category->CategoryMode = $form->GetValue('mode');
				$category->PriceColour = $form->GetValue('pricecolour');
				$category->PriceSize = $form->GetValue('pricesize');
				$category->PriceWeight = $form->GetValue('priceweight');
				$category->ShowBuyButton = $form->GetValue('showbuybutton');
				$category->Update('thumb', 'image');

				if($form->GetValue('urlalias') != $urlAlias) {
					new DataQuery(sprintf("DELETE FROM url_alias WHERE Type LIKE 'Category' AND Reference_ID=%d", mysql_real_escape_string($category->ID)));

					if($category->UseUrlAlias == 'Y') {
						$urlAlias = new UrlAlias();
						$urlAlias->Alias = $form->GetValue('urlalias');
						$urlAlias->Type = 'Category';
						$urlAlias->ReferenceID = $category->ID;
						$urlAlias->Add();
					}
				}

				redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $category->ID));
			}
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var removeOffer = function() {
			document.getElementById(\'product\').value = 0;
			document.getElementById(\'name\').value = \'\';
		}
		</script>');

	$script .= sprintf('<script language="javascript" type="text/javascript">
		var toggleUrlAlias = function(obj) {
			var e = document.getElementById(\'urlalias\');

			if(e) {
				if(obj.checked) {
					e.removeAttribute(\'disabled\');
				} else {
					e.setAttribute(\'disabled\', \'disabled\');
				}
			}
		}
		</script>');

	$script .= sprintf('<script language="javascript" type="text/javascript">
		jQuery(function($) {
			// Color picker
			var picker = $("#pricecolour");

			picker.ColorPicker({
				onSubmit: function(hsb, hex, rgb, el) {
					$(el).val(hex);
					$(el).ColorPickerHide();
				},
				onBeforeShow: function () {
					$(this).ColorPickerSetColor(this.value);
				},
				onChange: function(hsb, hex, rgb) {
					picker.val(hex);
				}
			});
		});
		</script>');

	$page = new Page(sprintf("Update Category: %s", $category->Name) ,'Please complete the form below.');
	$page->LinkCSS('css/colorpicker.css');
	$page->LinkScript('../js/jquery.js');
	$page->LinkScript('../js/scw.js');
	$page->LinkScript('js/colorpicker.js');
	$page->AddToHead($script);
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$tmpParentTxt = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	$window = new StandardWindow('Update Category');

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	echo $form->GetHTML('product');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	$webForm = new StandardForm;
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . $tmpParentTxt, sprintf("<span id=\"parentCaption\">%s</span>", $category->Parent->Name) . $form->GetHTML('parent') . $form->GetIcon('parent'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	if(!empty($category->Large->FileName)){
		echo $webForm->AddRow(sprintf("Current Image<br />This image is not full size. To view it at 100%s please click the image.<br /><br />
									<a href=\"javascript:confirmRequest('product_categories.php?node=%s&action=removeImage&type=large','Are your sure you would like to delete this image?');\"><img src=\"./images/aztector_6.gif\"  border=\"0\"></a>", '%', $_REQUEST['node']),
		sprintf("<a href=\"%s%s\" target=\"_blank\"><img src=\"%s%s\" width=\"150\" border=\"0\" /></a>", $GLOBALS['CATEGORY_IMAGES_DIR_WS'], $category->Large->FileName, $GLOBALS['CATEGORY_IMAGES_DIR_WS'], $category->Large->FileName));
	} else {
		echo $webForm->AddRow('Current Image', 'No Image Set');
	}
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	if(!empty($category->Thumb->FileName)){
		echo $webForm->AddRow(sprintf("Current Thumbnail<br />This image is not full size. To view it at 100%s please click the image.<br /><br />
									<a href=\"javascript:confirmRequest('product_categories.php?node=%s&action=removeImage&type=thumb','Are your sure you would like to delete this image?');\"><img src=\"./images/aztector_6.gif\"  border=\"0\"></a>", '%', $_REQUEST['node']),
		sprintf("<a href=\"%s%s\" target=\"_blank\"><img src=\"%s%s\" width=\"150\" border=\"0\" /></a>", $GLOBALS['CATEGORY_IMAGES_DIR_WS'], $category->Thumb->FileName, $GLOBALS['CATEGORY_IMAGES_DIR_WS'], $category->Thumb->FileName));

	} else {
		echo $webForm->AddRow('Current Thumbnail', 'No Image Set');
	}

	echo $webForm->AddRow($form->GetLabel('thumb'), $form->GetHTML('thumb') . $form->GetIcon('thumb'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('descriptionsecondary'), $form->GetHTML('descriptionsecondary') . $form->GetIcon('descriptionsecondary'));
	echo $webForm->AddRow($form->GetLabel('metaTitle'), $form->GetHTML('metaTitle') . $form->GetIcon('metaTitle'));
	echo $webForm->AddRow($form->GetLabel('metaDescription'), $form->GetHTML('metaDescription') . $form->GetIcon('metaDescription'));
	echo $webForm->AddRow($form->GetLabel('metaKeywords'), $form->GetHTML('metaKeywords') . $form->GetIcon('metaKeywords'));
	echo $webForm->AddRow($form->GetLabel('searchterm'), $form->GetHTML('searchterm') . $form->GetIcon('searchterm'));
	echo $webForm->AddRow($form->GetLabel('searchtermtitle'), $form->GetHTML('searchtermtitle') . $form->GetIcon('searchtermtitle'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow($form->GetLabel('showimage'), $form->GetHTML('showimage') . $form->GetIcon('showimage'));
	echo $webForm->AddRow($form->GetLabel('showimages'), $form->GetHTML('showimages') . $form->GetIcon('showimages'));
	echo $webForm->AddRow($form->GetLabel('showdescriptions'), $form->GetHTML('showdescriptions') . $form->GetIcon('showdescriptions'));
	echo $webForm->AddRow($form->GetLabel('showbestbuys'), $form->GetHTML('showbestbuys') . $form->GetIcon('showbestbuys'));
	echo $webForm->AddRow($form->GetLabel('columncounttext'), $form->GetHTML('columncounttext') . $form->GetIcon('columncounttext'));
	echo $webForm->AddRow($form->GetLabel('order'), $form->GetHTML('order') . $form->GetIcon('order'));
	echo $webForm->AddRow($form->GetLabel('catorder'), $form->GetHTML('catorder') . $form->GetIcon('catorder'));
	echo $webForm->AddRow($form->GetLabel('name') . '<a href="javascript:popUrl(\'product_search.php?serve=pop\', 700, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', $form->GetHTML('name') . '<a href="javascript:removeOffer();"><img src="images/aztector_6.gif" alt="Remove offer" border="0" /></a>');
	echo $webForm->AddRow($form->GetLabel('isredirecting'), $form->GetHTML('isredirecting') . $form->GetIcon('isredirecting'));
	echo $webForm->AddRow($form->GetLabel('redirecturl'), $form->GetHTML('redirecturl') . $form->GetIcon('redirecturl'));
	echo $webForm->AddRow($form->GetLabel('isfilteravailable'), $form->GetHTML('isfilteravailable') . $form->GetIcon('isfilteravailable'));
	echo $webForm->AddRow($form->GetLabel('isproductlistavailable'), $form->GetHTML('isproductlistavailable') . $form->GetIcon('isproductlistavailable'));
	echo $webForm->AddRow($form->GetLabel('useurlalias'), $form->GetHTML('useurlalias') . $form->GetIcon('useurlalias'));
	echo $webForm->AddRow($form->GetLabel('urlalias'), $form->GetHTML('urlalias') . $form->GetIcon('urlalias'));
	echo $webForm->AddRow($form->GetLabel('layout'), $form->GetHTML('layout') . $form->GetIcon('layout'));
	echo $webForm->AddRow($form->GetLabel('mode'), $form->GetHTML('mode') . $form->GetIcon('mode') . ' (This will determine whether the Category should show the Box Prices for valid Products).');
	echo $webForm->AddRow($form->GetLabel('pricecolour'), $form->GetHTML('pricecolour') . $form->GetIcon('pricecolour'));
	echo $webForm->AddRow($form->GetLabel('pricesize'), $form->GetHTML('pricesize') . $form->GetIcon('pricesize'));
	echo $webForm->AddRow($form->GetLabel('priceweight'), $form->GetHTML('priceweight') . $form->GetIcon('priceweight'));
	echo $webForm->AddRow($form->GetLabel('showbuybutton'), $form->GetHTML('showbuybutton') . $form->GetIcon('showbuybutton'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function add() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/UrlAlias.php');

	$category = new Category();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Parent', 'hidden', (isset($_REQUEST['node']))?$_REQUEST['node']:0, 'numeric_unsigned', 1, 11);
	$form->AddField('title', 'Category Title', 'text', '', 'paragraph', 1, 150);
	$form->AddField('image', 'Image', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('thumb', 'Thumbnail', 'file', '', 'file', NULL, NULL, false);
	$form->AddField('description', 'Category Description', 'textarea', '', 'paragraph', 1, 20000, false, 'style="width:100%; height:300px"');
	$form->AddField('descriptionsecondary', 'Category Description (Secondary)', 'textarea', '', 'paragraph', 1, 20000, false, 'style="width:100%; height:300px"');
	$form->AddField('metaTitle', 'Meta Title', 'text', '', 'paragraph', 1, 255, false);
	$form->AddField('metaKeywords', 'Meta Keywords', 'text', '', 'paragraph', 1, 255, false, 'style="width:100%;"');
	$form->AddField('metaDescription', 'Meta Description', 'text', '', 'paragraph', 1, 255, false, 'style="width:100%;"');
	$form->AddField('searchterm', 'Search Term', 'text', '', 'anything', 1, 255, false);
	$form->AddField('searchtermtitle', 'Search Term Title', 'text', '', 'anything', 1, 255, false);
	$form->AddField('active', 'Is Active', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('showimage', 'Show Image', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('showimages', 'Show Images', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('showdescriptions', 'Show Descriptions', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('showbestbuys', 'Show Best Buys', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('columncounttext', 'Columns (Text Only)', 'text', $category->ColumnCountText, 'numeric_unsigned', 1, 11);
	$form->AddField('order', 'Order', 'select', 'auto_id', 'anything', 0, 45);
	$form->AddOption('order', 'auto_id', 'Auto ID#');
	$form->AddOption('order', 'sku', 'SKU');
	$form->AddOption('order', 'product_title', 'Product Title');
	$form->AddOption('order', 'rank', 'Rank');
	$form->AddField('catorder', 'Category Order', 'select', 'Category_ID', 'anything', 0, 45);
	$form->AddOption('catorder', 'Category_ID', 'Auto ID#');
	$form->AddOption('catorder', 'Category_Title', 'Category Title');
	$form->AddOption('catorder', 'Sequence', 'Rank');
	$form->AddField('product', 'Offer Product ID', 'hidden', '', 'numeric_unsigned', 1, 11, false);
	$form->AddField('name', 'Offer Product', 'text', '', 'anything', null, null, false, 'onFocus="this.Blur();"');
	$form->AddField('isredirecting', 'Is Redirecting', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('redirecturl', 'Redirect URL', 'text', '', 'anything', 1, 2048, false, 'style="width: 100%"');
	$form->AddField('isfilteravailable', 'Is Filter Available', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('isproductlistavailable', 'Is Product List Available', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('useurlalias', 'Use URL Alias', 'checkbox', 'N', 'boolean', 1, 1, false, 'onclick="toggleUrlAlias(this);"');
	$form->AddField('urlalias', 'URL Alias', 'text', '', 'anything', 1, 1024, false, 'disabled="disabled"');
	$form->AddField('layout', 'Layout', 'select', 'Table', 'anything');
	$form->AddOption('layout', 'Table', 'Table');
	$form->AddOption('layout', 'Grid', 'Grid');
	$form->AddField('mode', 'Mode', 'select', 'Normal', 'anything');
	$form->AddOption('mode', 'Normal', 'Normal');
	$form->AddOption('mode', 'Box Rate', 'Box Rate');
	$form->AddField('pricecolour', 'Price Colour', 'text', 'C60909', 'anything', 3, 6, false);
	$form->AddField('pricesize', 'Price Size', 'select', '21', 'numeric_unsigned');
	$priceSize = 14;
	while($priceSize <= 36){
		$form->AddOption('pricesize', $priceSize, $priceSize);
		$priceSize++;
	}
	$form->AddField('priceweight', 'Price Weight', 'select', 'Normal', 'anything');
	$form->AddOption('priceweight', 'Normal', 'Normal');
	$form->AddOption('priceweight', 'Italic', 'Italic');
	$form->AddOption('priceweight', 'Bold', 'Bold');
	$form->AddOption('priceweight', 'Bold Italic', 'Bold Italic');
	$form->AddField('showbuybutton', 'Show Buy Button', 'select', 'Y', 'anything');
	$form->AddOption('showbuybutton', 'Y', 'Yes');
	$form->AddOption('showbuybutton', 'N', 'No');

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true") {
		if($form->GetValue('useurlalias') == 'Y') {
			$form->InputFields['urlalias']->Required = true;
		}

		if($form->Validate()){
			$category->Name = $form->GetValue('title');
			$category->Parent = new Category;
			$category->Parent->ID = $form->GetValue('parent');
			$category->Description = $form->GetValue('description');
			$category->DescriptionSecondary = $form->GetValue('descriptionsecondary');
			$category->MetaTitle = $form->GetValue('metaTitle');
			$category->MetaDescription = $form->GetValue('metaDescription');
			$category->MetaKeywords = $form->GetValue('metaKeywords');
			$category->SearchTerm = $form->GetValue('searchterm');
			$category->SearchTermTitle = $form->GetValue('searchtermtitle');
			$category->IsActive = $form->GetValue('active');
			$category->Order = $form->GetValue('order');
			$category->CategoryOrder = $form->GetValue('catorder');
			$category->ProductOffer->ID = $form->GetValue('product');
			$category->ShowImage = $form->GetValue('showimage');
			$category->ShowImages = $form->GetValue('showimages');
			$category->ShowDescriptions = $form->GetValue('showdescriptions');
			$category->ShowBestBuys = $form->GetValue('showbestbuys');
			$category->ColumnCountText = $form->GetValue('columncounttext');
			$category->IsRedirecting = $form->GetValue('isredirecting');
			$category->IsFilterAvailable = $form->GetValue('isredirecting');
			$category->RedirectUrl = $form->GetValue('redirecturl');
			$category->IsFilterAvailable = $form->GetValue('isfilteravailable');
			$category->IsProductListAvailable = $form->GetValue('isproductlistavailable');
			$category->UseUrlAlias = $form->GetValue('useurlalias');
			$category->Layout = $form->GetValue('layout');
			$category->CategoryMode = $form->GetValue('mode');
			$category->PriceColour = $form->GetValue('pricecolour');
			$category->PriceSize = $form->GetValue('pricesize');
			$category->PriceWeight = $form->GetValue('priceweight');
			$category->ShowBuyButton = $form->GetValue('showbuybutton');
			$category->Add((empty($_FILES['thumb']['name'])) ? 'image' : 'thumb', 'image');

			if($category->UseUrlAlias == 'Y') {
				$urlAlias = new UrlAlias();
				$urlAlias->Alias = $form->GetValue('urlalias');
				$urlAlias->Type = 'Category';
				$urlAlias->ReferenceID = $category->ID;
				$urlAlias->Add();
			}

			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $form->GetValue('parent')));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var removeOffer = function() {
			document.getElementById(\'product\').value = 0;
			document.getElementById(\'name\').value = \'\';
		}
		</script>');

	$script .= sprintf('<script language="javascript" type="text/javascript">
		var toggleUrlAlias = function(obj) {
			var e = document.getElementById(\'urlalias\');

			if(e) {
				if(obj.checked) {
					e.removeAttribute(\'disabled\');
				} else {
					e.setAttribute(\'disabled\', \'disabled\');
				}
			}
		}
		</script>');
	$script .= sprintf('<script language="javascript" type="text/javascript">
		jQuery(function($) {
			// Color picker
			var picker = $("#pricecolour");

			picker.ColorPicker({
				onSubmit: function(hsb, hex, rgb, el) {
					$(el).val(hex);
					$(el).ColorPickerHide();
				},
				onBeforeShow: function () {
					$(this).ColorPickerSetColor(this.value);
				},
				onChange: function(hsb, hex, rgb) {
					picker.val(hex);
				}
			});
		});
		</script>');

	$page = new Page('Add a New Category','Please complete the form below.');
		$page->LinkCSS('css/colorpicker.css');
	$page->LinkScript('../js/jquery.js');
	$page->LinkScript('../js/scw.js');
	$page->LinkScript('js/colorpicker.js');
	$page->AddToHead($script);
	$page->AddToHead($script);
	$page->SetEditor(true);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$parent = new Category($_REQUEST['node']);

	$window = new StandardWindow('Add Category');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('product');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent'), $parent->Name . $form->GetHTML('parent') . $form->GetIcon('parent'));
	echo $webForm->AddRow($form->GetLabel('title'), $form->GetHTML('title') . $form->GetIcon('title'));
	echo $webForm->AddRow($form->GetLabel('image'), $form->GetHTML('image') . $form->GetIcon('image'));
	echo $webForm->AddRow($form->GetLabel('thumb'), $form->GetHTML('thumb') . $form->GetIcon('thumb'));
	echo $webForm->AddRow($form->GetLabel('description'), $form->GetHTML('description') . $form->GetIcon('description'));
	echo $webForm->AddRow($form->GetLabel('descriptionsecondary'), $form->GetHTML('descriptionsecondary') . $form->GetIcon('descriptionsecondary'));
	echo $webForm->AddRow($form->GetLabel('metaTitle'), $form->GetHTML('metaTitle') . $form->GetIcon('metaTitle'));
	echo $webForm->AddRow($form->GetLabel('metaDescription'), $form->GetHTML('metaDescription') . $form->GetIcon('metaDescription'));
	echo $webForm->AddRow($form->GetLabel('metaKeywords'), $form->GetHTML('metaKeywords') . $form->GetIcon('metaKeywords'));
	echo $webForm->AddRow($form->GetLabel('searchterm'), $form->GetHTML('searchterm') . $form->GetIcon('searchterm'));
	echo $webForm->AddRow($form->GetLabel('searchtermtitle'), $form->GetHTML('searchtermtitle') . $form->GetIcon('searchtermtitle'));
	echo $webForm->AddRow($form->GetLabel('active'), $form->GetHTML('active') . $form->GetIcon('active'));
	echo $webForm->AddRow($form->GetLabel('showimage'), $form->GetHTML('showimage') . $form->GetIcon('showimage'));
	echo $webForm->AddRow($form->GetLabel('showimages'), $form->GetHTML('showimages') . $form->GetIcon('showimages'));
	echo $webForm->AddRow($form->GetLabel('showdescriptions'), $form->GetHTML('showdescriptions') . $form->GetIcon('showdescriptions'));
	echo $webForm->AddRow($form->GetLabel('showbestbuys'), $form->GetHTML('showbestbuys') . $form->GetIcon('showbestbuys'));
	echo $webForm->AddRow($form->GetLabel('columncounttext'), $form->GetHTML('columncounttext') . $form->GetIcon('columncounttext'));
	echo $webForm->AddRow($form->GetLabel('order'), $form->GetHTML('order') . $form->GetIcon('order'));
	echo $webForm->AddRow($form->GetLabel('catorder'), $form->GetHTML('catorder') . $form->GetIcon('catorder'));
	echo $webForm->AddRow($form->GetLabel('name') . '<a href="javascript:popUrl(\'product_search.php?serve=pop\', 700, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', $form->GetHTML('name') . '<a href="javascript:removeOffer();"><img src="images/aztector_6.gif" alt="Remove offer" border="0" /></a>');
	echo $webForm->AddRow($form->GetLabel('isredirecting'), $form->GetHTML('isredirecting') . $form->GetIcon('isredirecting'));
	echo $webForm->AddRow($form->GetLabel('redirecturl'), $form->GetHTML('redirecturl') . $form->GetIcon('redirecturl'));
	echo $webForm->AddRow($form->GetLabel('isfilteravailable'), $form->GetHTML('isfilteravailable') . $form->GetIcon('isfilteravailable'));
	echo $webForm->AddRow($form->GetLabel('isproductlistavailable'), $form->GetHTML('isproductlistavailable') . $form->GetIcon('isproductlistavailable'));
	echo $webForm->AddRow($form->GetLabel('useurlalias'), $form->GetHTML('useurlalias') . $form->GetIcon('useurlalias'));
	echo $webForm->AddRow($form->GetLabel('urlalias'), $form->GetHTML('urlalias') . $form->GetIcon('urlalias'));
	echo $webForm->AddRow($form->GetLabel('layout'), $form->GetHTML('layout') . $form->GetIcon('layout'));
	echo $webForm->AddRow($form->GetLabel('mode'), $form->GetHTML('mode') . $form->GetIcon('mode') . ' (This will determine whether the Category should show the Box Prices for valid Products).');
	echo $webForm->AddRow($form->GetLabel('pricecolour'), $form->GetHTML('pricecolour') . $form->GetIcon('pricecolour'));
	echo $webForm->AddRow($form->GetLabel('pricesize'), $form->GetHTML('pricesize') . $form->GetIcon('pricesize'));
	echo $webForm->AddRow($form->GetLabel('priceweight'), $form->GetHTML('priceweight') . $form->GetIcon('priceweight'));
	echo $webForm->AddRow($form->GetLabel('showbuybutton'), $form->GetHTML('showbuybutton') . $form->GetIcon('showbuybutton'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/TreeMenu.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Bubble.php');

	$page = new Page('Product Categories for your Online Shop', '');
	$page->LinkScript('js/navigator_functions.js');
	$page->LinkScript('js/navigator_format.js');
	$page->LinkScript('js/navigator_classes.js');
	$page->LinkScript('js/HttpRequest.js');
	$page->LinkScript('js/TreeMenu.js');
	$page->AddToHead('<link href="./css/NavigationMenu.css" rel="stylesheet" type="text/css" />');
	$page->AddToHead('<script language="javascript" type="text/javascript">var myTree = new TreeMenu(\'myTree\');</script>');
	$page->AddToHead(sprintf("<script>\nvar s_navTree = new treeMenuSettings;\n</script>"));
	$page->AddOnLoad('s_navTree.drawOptions();');
	$page->AddDocType();
	$page->Display('header');

	if(isset($_REQUEST['error'])) {
		$bubble = new Bubble('Move Error', $_REQUEST['error']);

		echo $bubble->GetHTML();
		echo '<br />';
	}

	$function = 's_navTree';
	?>
		<div class="window_1">
			Product Categories Structure<br>
			<div class="treeCell" id="Navigation">
				<script>
				myTree.url = 'lib/util/loadCategoryChildren.php?function=s_navTree';
				myTree.loading = '<div class="treeIsLoading"><img src="images/TreeMenu/loading.gif" align="absmiddle" /> Loading...</div>';
				myTree.addClass('default', 'images/TreeMenu/page.gif', 'images/TreeMenu/folder.gif', 'images/TreeMenu/folderopen.gif');
				myTree.addClass('selected', 'images/TreeMenu/page.gif', 'images/TreeMenu/folder.gif', 'images/TreeMenu/folderopen.gif');
				myTree.addNode(0, null, '_root', 'default', true, null, null);
				<?php include('lib/common/productTree.php'); ?>
				myTree.build('Navigation');
				</script>
			</div>
			<div class="settings">
				<strong>Category Options</strong><br>
				<br>
				<div id="optionView" class="treeOption">
					<a href="javascript:s_navTree.selectOption('product_list.php?cat=');"><strong>View Products</strong><br>
					view the products associated with this category.</a>
				</div>
				<div id="optionAdd" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=add&node=');"><strong>Add Category</strong><br>
					as a child to the selected category.</a>
				</div>
				<div id="optionUpdate" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=update&node=');"><strong>Update Category</strong><br>
					settings for this category.</a>
				</div>
				<div id="optionRemove" class="treeOption">
					<a href="javascript:s_navTree.remove('<?php echo $_SERVER['PHP_SELF'] ?>?action=remove&node=');"><strong>Remove Category</strong><br>
					removes this and associated child categories.</a>
				</div>
                <div id="optionDuplicate" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=duplicate&node=');"><strong>Duplicate Category</strong><br>
					Duplicates this category, all sub categories and products.</a>
				</div>
                <div id="optionMoveUp" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=moveup&node=');"><strong>Move Up</strong><br>
					Moves this and associated child categories up.</a>
				</div>
                <div id="optionMoveDown" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=movedown&node=');"><strong>Move Down</strong><br>
					Moves this and associated child categories down.</a>
				</div>
				<div id="optionSpec" class="treeOption">
					<a href="javascript:s_navTree.selectOption('product_specs_groups_categories.php?cat=');"><strong>Alter Spec Groups</strong><br>
					Manage the exclusion of product specification groups for this category.</a>
				</div>
				<div id="optionMarkupPrice" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=markupprice&node=');"><strong>Markup Prices</strong><br>
					Markup the prices of all products from this category.</a>
				</div>
				<div id="optionMarkupCost" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=markupcost&node=');"><strong>Markup Costs</strong><br>
					Markup the costs of all products from this category for all suppliers.</a>
				</div>
				<div id="optionLink" class="treeOption">
					<a href="javascript:s_navTree.selectOption('product_category_link.php?cat=');"><strong>Linked Categories</strong><br>
					Link another categories sub categories to this category.</a>
				</div>
				<div id="optionGoogleBase" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=googlebase&node=');"><strong>Google Base</strong><br>
					Specify the Google Base product title suffix for all sub category products.</a>
				</div>
				<div id="optionCatalogueImages" class="treeOption">
					<a href="javascript:s_navTree.selectOption('category_catalogue_images.php?cat=');"><strong>Catalogue Images</strong><br>
					Add hi-res catalogue images to this category.</a>
				</div>
				<div id="optionStock" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=managestock&node=');"><strong>Stock Management</strong><br>
					Manage stock settings for this category.</a>
				</div>
                <div id="optionShipping" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=manageshipping&node=');"><strong>Shipping Settings</strong><br>
					Manage shipping settings for this category.</a>
				</div>
                <div id="optionShipping" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=managewatches&node=');"><strong>Product Watch Lists</strong><br>
					Manage product watch lists for this category.</a>
				</div>
				<div id="optionReview" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=managereview&node=');"><strong>Product Review Settings</strong><br>
					Manage product automated review settings for this category.</a>
				</div>
				<div id="optionPrimarySpecs" class="treeOption">
					<a href="javascript:s_navTree.selectOption('<?php echo $_SERVER['PHP_SELF'] ?>?action=manageprimaryspecs&node=');"><strong>Primary Specifications</strong><br>
					Set primary specifications for this category.</a>
				</div>
				<div id="optionManageRelated" class="treeOption">
					<a href="javascript:s_navTree.selectOption('?action=managerelated&node=');"><strong>Manage Related</strong><br>
					Manage related products for this immediate category.</a>
				</div>
				<div id="optionManageLinks" class="treeOption">
					<a href="javascript:s_navTree.selectOption('?action=managelinks&node=');"><strong>Manage Links</strong><br>
					Manage product similar links for this immediate category.</a>
				</div>
				<div id="optionManageQuality" class="treeOption">
					<a href="javascript:s_navTree.selectOption('?action=managequality&node=');"><strong>Manage Quality</strong><br>
					Manage product qualities for this immediate category.</a>
				</div>
				<div id="optionManageSearchKeywords" class="treeOption">
					<a href="javascript:s_navTree.selectOption('product_category_search_keywords.php?cid=');"><strong>Manage Search Keywords</strong><br>
					Manage search keywords for this immediate category.</a>
				</div>
			</div>
		</div>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function getNode(){
	$connection = isset($_REQUEST['connection']) ? $_REQUEST['connection'] : 0;
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Select Parent Node</title>
		<script language="javascript" type="text/javascript" src="js/HttpRequest.js"></script>
		<script language="javascript" type="text/javascript" src="js/TreeMenu.js"></script>
		<link href="css/NavigationMenu.css" rel="stylesheet" type="text/css" />

		<script language="javascript" type="text/javascript">
		var myTree = new TreeMenu('myTree');

		this.setNode = function(id, str) {
			window.opener.document.getElementById('parentCaption').innerHTML = str;
			window.opener.document.getElementById('parent').value = id;
			window.self.close();
		}
		</script>
	</head>
	<body id="Wrapper">

		<div id="Navigation"></div>

		<script>
		myTree.url = 'lib/util/loadCategoryChildren.php?connection=<?php print $connection; ?>';
		myTree.loading = '<div class="treeIsLoading"><img src="images/TreeMenu/loading.gif" align="absmiddle" /> Loading...</div>';
		myTree.addClass('default', 'images/TreeMenu/page.gif', 'images/TreeMenu/folder.gif', 'images/TreeMenu/folderopen.gif');
		myTree.addNode(0, null, '_root', 'default', true, 'javascript:setNode(0, \'_root\')');
		myTree.build('Navigation');
		</script>

	</body>
	</html>
	<?php
	require_once('lib/common/app_footer.php');
}

function duplicate() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/WarehouseStock.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductPrice.php');

	$category = new Category($_REQUEST['node']);

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'duplicate', 'alpha', 9, 9);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', $category->ID, 'numeric_unsigned', 1, 11);
	$form->AddField('parent', 'Destination Category', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Include Sub Categories?', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('products', 'Include Products?', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('duplicateProducts', 'Duplicate Products?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if(($form->GetValue('sub') == 'N') && ($form->GetValue('products') == 'N')) {
			$form->AddError('Please select to include either sub categories or products.');
		}

		if($form->Validate()){
			$source = $form->GetValue('node');
			$destination = $form->GetValue('parent');

			duplicateChildCategories($source, $destination, ($form->GetValue('sub') == 'Y') ? true : false, ($form->GetValue('products') == 'Y') ? true : false, ($form->GetValue('duplicateProducts') == 'Y') ? true : false);
			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $source));
		}
	}

	$page = new Page(sprintf("Duplicate Category Contents: %s", $category->Name) ,'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow('Duplicate Category');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	$tmpSource = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 500, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $tmpSource, "<span id=\"parentCaption\"></span>" . $form->GetHTML('parent') . $form->GetIcon('parent'));
	echo $webForm->AddRow($form->GetLabel('sub'), $form->GetHTML('sub'));
	echo $webForm->AddRow($form->GetLabel('products'), $form->GetHTML('products'));
	echo $webForm->AddRow($form->GetLabel('duplicateProducts'), $form->GetHTML('duplicateProducts'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="duplicate" value="duplicate" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function duplicateChildCategories($source, $destination, $sub, $products, $duplicate, $blacklistCategories = array(), $blacklistProducts = array()) {
	if($products) {
		$category = new Category($destination);

		$data2 = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d ORDER BY Product_ID ASC", mysql_real_escape_string($source)));
		while($data2->Row) {
			if(!isset($blacklistProducts[$data2->Row['Product_ID']])) {
				$product = new Product($data2->Row['Product_ID']);

				if($duplicate) {
					$product->Add();
				}

				$product->AddToCategory($category->ID);

				if($duplicate) {
					if(!empty($product->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Thumb->FileName)) {
						$oldFileName = $product->DefaultImage->Thumb->FileName;
						$product->DefaultImage->Thumb->CreateUniqueName($product->DefaultImage->Thumb->FileName);
						$newFileName = $product->DefaultImage->Thumb->FileName;
						$product->DefaultImage->Thumb->FileName = $oldFileName;
						$product->DefaultImage->Thumb->Copy(null, $newFileName);
						$product->DefaultImage->Thumb->FileName = $newFileName;
					}

					if(!empty($product->DefaultImage->Large->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Large->FileName)) {
						$oldFileName = $product->DefaultImage->Large->FileName;
						$product->DefaultImage->Large->CreateUniqueName($product->DefaultImage->Large->FileName);
						$newFileName = $product->DefaultImage->Large->FileName;
						$product->DefaultImage->Large->FileName = $oldFileName;
						$product->DefaultImage->Large->Copy(null, $newFileName);
						$product->DefaultImage->Large->FileName = $newFileName;
					}

					$product->DefaultImage->ParentID = $product->ID;
					$product->DefaultImage->Add();

					$data3 = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Product_ID=%d", $data2->Row['Product_ID']));
					while($data3->Row){
						$data4 = new ProductPrice();
						$data4->ProductID = $product->ID;
						$data4->PriceOurs = $data3->Row['Price_Base_Our'];
						$data4->PriceRRP = $data3->Row['Price_Base_RRP'];
						$data4->IsTaxIncluded = $data3->Row['Is_Tax_Included'];
						$data4->PriceStartsOn = $data3->Row['Price_Starts_On'];
						$data4->Quantity = $data3->Row['Quantity'];
						$data4->Add();

						$data3->Next();
					}
					$data3->Disconnect();

					$data3 = new DataQuery(sprintf("SELECT Specification_ID FROM product_specification WHERE Product_ID=%d", $data2->Row['Product_ID']));
					while($data3->Row){
						$spec = new ProductSpec($data3->Row['Specification_ID']);
						$spec->Product->ID = $product->ID;
						$spec->Add();

						$data3->Next();
					}
					$data3->Disconnect();

					$data3 = new DataQuery(sprintf("SELECT * FROM product_offers WHERE Product_ID=%d", $data2->Row['Product_ID']));
					while($data3->Row){
						$data4 = new DataQuery(sprintf("INSERT INTO product_offers (Product_ID, Price_Offer, Is_Tax_Included, Offer_Start_On, Offer_End_On) VALUES (%d, %f, '%s', '%s', '%s')", mysql_real_escape_string($product->ID), $data3->Row['Price_Offer'], $data3->Row['Is_Tax_Included'], $data3->Row['Offer_Start_On'], $data3->Row['Offer_End_On']));
						$data4->Disconnect();

						$data3->Next();
					}
					$data3->Disconnect();

					$data3 = new DataQuery(sprintf("SELECT Product_ID FROM product_related WHERE Related_To_Product_ID=%d", $data2->Row['Product_ID']));
					while($data3->Row){
						$data4 = new DataQuery(sprintf("INSERT INTO product_related (Product_ID, Related_To_Product_ID) VALUES (%d, %d)", $data3->Row['Product_ID'], mysql_real_escape_string($product->ID)));
						$data4->Disconnect();

						$data3->Next();
					}
					$data3->Disconnect();

					$data3 = new DataQuery(sprintf('SELECT Supplier_Product_ID FROM supplier_product WHERE Product_ID=%d', $data2->Row['Product_ID']));
					while($data3->Row){
						$supplier = new SupplierProduct($data3->Row['Supplier_Product_ID']);
						$supplier->Product->ID = $product->ID;
						$supplier->Add();

						$data3->Next();
					}
					$data3->Disconnect();
				}

				$blacklistProducts[$product->ID] = true;
			}

			$data2->Next();
		}
		$data2->Disconnect();
	}

	if($sub) {
		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($source)));
		while($data->Row) {
			if(!isset($blacklistCategories[$data->Row['Category_ID']])) {
				$category = new Category($data->Row['Category_ID']);
				$category->Parent->ID = $destination;

				if(!empty($category->Thumb->FileName) && file_exists($GLOBALS['CATEGORY_IMAGES_DIR_FS'].$category->Thumb->FileName)) {
					$oldFileName = $category->Thumb->FileName;
					$category->Thumb->CreateUniqueName($category->Thumb->FileName);
					$newFileName = $category->Thumb->FileName;
					$category->Thumb->FileName = $oldFileName;
					$category->Thumb->Copy(null, $newFileName);
					$category->Thumb->FileName = $newFileName;
				}

				if(!empty($category->Large->FileName) && file_exists($GLOBALS['CATEGORY_IMAGES_DIR_FS'].$category->Large->FileName)) {
					$oldFileName = $category->Large->FileName;
					$category->Large->CreateUniqueName($category->Large->FileName);
					$newFileName = $category->Large->FileName;
					$category->Large->FileName = $oldFileName;
					$category->Large->Copy(null, $newFileName);
					$category->Large->FileName = $newFileName;
				}

				$category->Add();

				$blacklistCategories[$category->ID] = true;

				duplicateChildCategories($data->Row['Category_ID'], $category->ID, $sub, $products, $duplicate, $blacklistCategories, $blacklistProducts);
			}

			$data->Next();
		}
		$data->Disconnect();
	}
}

function markupPrice() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	$category = new Category();

	if(!isset($_REQUEST['node']) || !$category->Get($_REQUEST['node'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'markupprice', 'alpha', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('method', 'Markup Method', 'select', '', 'anything', 1, 64, true, 'onchange="toggleCostMarkup(this);"');
	$form->AddOption('method', '', '');
	$form->AddOption('method', 'Absolute', 'Fixed Amount (&pound;)');
	$form->AddOption('method', 'Relative', 'Relative Amount (%)');
	$form->AddField('markup', 'Markup Value (&pound; or %)', 'text', '', 'float', 1, 11);
	$form->AddField('sub', 'Markup Sub Categories?', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('costmarkup', 'Markup Relative Prices by Cost?', 'checkbox', 'N', 'boolean', 1, 1, false, 'disabled="disabled"');

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			markupPriceCategory($form->GetValue('node'), $form->GetValue('method'), $form->GetValue('markup'), ($form->GetValue('sub') == 'Y') ? true : false, ($form->GetValue('costmarkup') == 'Y') ? true : false);

			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $form->GetValue('node')));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var toggleCostMarkup = function(obj) {
			var element = document.getElementById(\'costmarkup\');

			if(element) {
				if(obj.value == \'Relative\') {
					element.removeAttribute(\'disabled\');
				} else {
					element.setAttribute(\'disabled\', \'disabled\');
				}
			}
		}
		</script>');

	$page = new Page(sprintf("Markup Price: %s", $category->Name), 'Please complete the form below.');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Markup Price');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('method'), $form->GetHTML('method'). $form->GetIcon('method'));
	echo $webForm->AddRow($form->GetLabel('markup'), $form->GetHTML('markup'). $form->GetIcon('markup'));
	echo $webForm->AddRow($form->GetLabel('sub'), $form->GetHTML('sub'). $form->GetIcon('sub'));
	echo $webForm->AddRow($form->GetLabel('costmarkup'), $form->GetHTML('costmarkup'). $form->GetIcon('costmarkup'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function markupPriceCategory($categoryId, $method, $markup, $subCategories = false, $costMarkup = false, $products = array()) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductPrice.php');

	$productPrice = new ProductPrice();
	$productPrice->PriceStartsOn = date('Y-m-d H:i:s');

	$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = true;

			$productPrice->ProductID = $data->Row['Product_ID'];

			$prices = array();

			$data2 = new DataQuery(sprintf("SELECT Price_Base_Our, Price_Base_RRP, Quantity FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=NOW() ORDER BY Price_Starts_On ASC", $data->Row['Product_ID']));
			while($data2->Row) {
				$prices[$data2->Row['Quantity']] = $data2->Row;

				$data2->Next();
			}
			$data2->Disconnect();

			foreach($prices as $price) {
				$productPrice->Quantity = $price['Quantity'];
				$productPrice->PriceOurs = $price['Price_Base_Our'];
				$productPrice->PriceRRP = $price['Price_Base_RRP'];

				switch(strtolower($method)) {
					case 'absolute':
						$productPrice->PriceOurs += $markup;
						$productPrice->Add();

						break;

					case 'relative':
						if($costMarkup) {
							$data2 = new DataQuery(sprintf("SELECT Cost FROM supplier_product WHERE Cost>0 AND Product_ID=%d ORDER BY Cost ASC", $data->Row['Product_ID']));
							if($data2->TotalRows > 0) {
								$productPrice->PriceOurs += $markup * ($data2->Row['Cost'] / 100);
								$productPrice->Add();
							}
							$data2->Disconnect();


						} else {
							$productPrice->PriceOurs += $markup * ($productPrice->PriceOurs / 100);
							$productPrice->Add();
						}
						break;
				}
			}
		}

		$data->Next();
	}
	$data->Disconnect();

	if($subCategories) {
		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			$products = markupPriceCategory($data->Row['Category_ID'], $method, $markup, true, $costMarkup, $products);

			$data->Next();
		}
		$data->Disconnect();
	}

	return $products;
}

function markupCost() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	$category = new Category();

	if(!isset($_REQUEST['node']) || !$category->Get($_REQUEST['node'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'markupcost', 'alpha', 10, 10);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('method', 'Markup Method', 'select', '', 'anything', 1, 64);
	$form->AddOption('method', '', '');
	$form->AddOption('method', 'Absolute', 'Fixed Amount (&pound;)');
	$form->AddOption('method', 'Relative', 'Relative Amount (%)');
	$form->AddField('markup', 'Markup Value (&pound; or %)', 'text', '', 'float', 1, 11);
	$form->AddField('sub', 'Markup Sub Categories?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			markupCostCategory($form->GetValue('node'), $form->GetValue('method'), $form->GetValue('markup'), ($form->GetValue('sub') == 'Y') ? true : false);

			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $form->GetValue('node')));
		}
	}

	$page = new Page(sprintf("Markup Cost: %s", $category->Name), 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Markup Cost');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('method'), $form->GetHTML('method'). $form->GetIcon('method'));
	echo $webForm->AddRow($form->GetLabel('markup'), $form->GetHTML('markup'). $form->GetIcon('markup'));
	echo $webForm->AddRow($form->GetLabel('sub'), $form->GetHTML('sub'). $form->GetIcon('sub'));
	echo $webForm->AddRow("&nbsp;", sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function markupCostCategory($categoryId, $method, $markup, $subCategories = false, $products = array()) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

	$supplierProduct = new SupplierProduct();

	$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = true;

			switch(strtolower($method)) {
				case 'absolute':
					new DataQuery(sprintf("UPDATE supplier_product SET Cost=Cost+%f WHERE Product_ID=%d", mysql_real_escape_string($markup), $data->Row['Product_ID']));
					break;

				case 'relative':
					new DataQuery(sprintf("UPDATE supplier_product SET Cost=Cost+(%f*(Cost/100)) WHERE Product_ID=%d", mysql_real_escape_string($markup), $data->Row['Product_ID']));
					break;
			}
		}

		$data->Next();
	}
	$data->Disconnect();

	if($subCategories) {
		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			$products = markupCostCategory($data->Row['Category_ID'], $method, $markup, true, $products);

			$data->Next();
		}
		$data->Disconnect();
	}

	return $products;
}

function manageStock() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	$category = new Category();

	if(!isset($_REQUEST['node']) || !$category->Get($_REQUEST['node'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'managestock', 'alpha', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('monitorstock', 'Monitor Stock', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('sub', 'Apply to Sub Categories?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			manageStockCategory($form->GetValue('node'), $form->GetValue('monitorstock'), ($form->GetValue('sub') == 'Y') ? true : false);

			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $form->GetValue('node')));
		}
	}

	$page = new Page(sprintf("Stock Management: %s", $category->Name), 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Markup Cost');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('monitorstock'), $form->GetHTML('monitorstock'). $form->GetIcon('monitorstock'));
	echo $webForm->AddRow($form->GetLabel('sub'), $form->GetHTML('sub'). $form->GetIcon('sub'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function manageStockCategory($categoryId, $monitorStock, $subCategories = false, $products = array()) {
	$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = true;

			new DataQuery(sprintf("UPDATE product SET Monitor_Stock='%s' WHERE Product_ID=%d", mysql_real_escape_string($monitorStock), $data->Row['Product_ID']));
		}

		$data->Next();
	}
	$data->Disconnect();

	if($subCategories) {
		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			$products = manageStockCategory($data->Row['Category_ID'], $monitorStock, true, $products);

			$data->Next();
		}
		$data->Disconnect();
	}

	return $products;
}

function manageShipping() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	$category = new Category();

	if(!isset($_REQUEST['node']) || !$category->Get($_REQUEST['node'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'manageshipping', 'alpha', 14, 14);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', '', 'numeric_unsigned', 1, 11);
    $form->AddField('class', 'Shipping Class', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('class', '', '');

	$data = new DataQuery("SELECT Shipping_Class_ID, Shipping_Class_Title FROM shipping_class ORDER BY Shipping_Class_Title ASC");
	while($data->Row){
		$form->AddOption('class', $data->Row['Shipping_Class_ID'], $data->Row['Shipping_Class_Title']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('sub', 'Apply to Sub Categories?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			manageShippingCategory($form->GetValue('node'), $form->GetValue('class'), ($form->GetValue('sub') == 'Y') ? true : false);

			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $form->GetValue('node')));
		}
	}

	$page = new Page(sprintf("Shipping Settings: %s", $category->Name), 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Select Shipping Class');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('class'), $form->GetHTML('class'). $form->GetIcon('class'));
	echo $webForm->AddRow($form->GetLabel('sub'), $form->GetHTML('sub'). $form->GetIcon('sub'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function manageShippingCategory($categoryId, $classId, $subCategories = false, $products = array()) {
	$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = true;

			new DataQuery(sprintf("UPDATE product SET Shipping_Class_ID=%d WHERE Product_ID=%d", mysql_real_escape_string($classId), $data->Row['Product_ID']));
		}

		$data->Next();
	}
	$data->Disconnect();

	if($subCategories) {
		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			$products = manageShippingCategory($data->Row['Category_ID'], $classId, true, $products);

			$data->Next();
		}
		$data->Disconnect();
	}

	return $products;
}

function manageWatches() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	$category = new Category();

	if(!isset($_REQUEST['node']) || !$category->Get($_REQUEST['node'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'managewatches', 'alpha', 13, 13);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', '', 'numeric_unsigned', 1, 11);
    $form->AddField('watch', 'Watch List', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('watch', '', '');

	$data = new DataQuery("SELECT ProductWatchID, Name FROM product_watch ORDER BY Name ASC");
	while($data->Row){
		$form->AddOption('watch', $data->Row['ProductWatchID'], $data->Row['Name']);

		$data->Next();
	}
	$data->Disconnect();

	$form->AddField('sub', 'Apply to Sub Categories?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			manageWatchesCategory($form->GetValue('node'), $form->GetValue('watch'), ($form->GetValue('sub') == 'Y') ? true : false);

			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $form->GetValue('node')));
		}
	}

	$page = new Page(sprintf("Product Watch Lists: %s", $category->Name), 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Select Watch List');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('watch'), $form->GetHTML('watch'). $form->GetIcon('watch'));
	echo $webForm->AddRow($form->GetLabel('sub'), $form->GetHTML('sub'). $form->GetIcon('sub'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function manageWatchesCategory($categoryId, $watchId, $subCategories = false, $products = array()) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductWatchItem.php');

	$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = true;

			$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM product_watch_item WHERE ProductWatchID=%d AND ProductID=%d", mysql_real_escape_string($watchId), $data->Row['Product_ID']));
			if($data2->Row['Count'] == 0) {
				$item = new ProductWatchItem();
				$item->WatchID = $watchId;
				$item->Product->ID = $data->Row['Product_ID'];
				$item->Add();
			}
			$data2->Disconnect();
		}

		$data->Next();
	}
	$data->Disconnect();

	if($subCategories) {
		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			$products = manageWatchesCategory($data->Row['Category_ID'], $watchId, true, $products);

			$data->Next();
		}
		$data->Disconnect();
	}

	return $products;
}

function manageReview() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');

	$category = new Category();

	if(!isset($_REQUEST['node']) || !$category->Get($_REQUEST['node'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'managereview', 'alpha', 12, 12);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('automaticreview', 'Automatic Review', 'checkbox', 'N', 'boolean', 1, 1, false);
	$form->AddField('sub', 'Apply to Sub Categories?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			manageReviewCategory($form->GetValue('node'), $form->GetValue('automaticreview'), ($form->GetValue('sub') == 'Y') ? true : false);

			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $form->GetValue('node')));
		}
	}

	$page = new Page(sprintf("product Review Settings: %s", $category->Name), 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Select Product Review');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('automaticreview'), $form->GetHTML('automaticreview'). $form->GetIcon('automaticreview'));
	echo $webForm->AddRow($form->GetLabel('sub'), $form->GetHTML('sub'). $form->GetIcon('sub'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function manageReviewCategory($categoryId, $automaticReview, $subCategories = false, $products = array()) {
	$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = true;

			new DataQuery(sprintf("UPDATE product SET Is_Automatic_Review='%s' WHERE Product_ID=%d", mysql_real_escape_string($automaticReview), $data->Row['Product_ID']));
		}

		$data->Next();
	}
	$data->Disconnect();

	if($subCategories) {
		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			$products = manageReviewCategory($data->Row['Category_ID'], $automaticReview, true, $products);

			$data->Next();
		}
		$data->Disconnect();
	}

	return $products;
}

function getSubCategories($categoryId) {
	$categories = array($categoryId);

	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		$categories = array_merge($categories, getSubCategories($data->Row['Category_ID']));

		$data->Next();
	}
	$data->Disconnect();

	return $categories;
}

function managePrimarySpecs() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

	$category = new Category();

	if(!isset($_REQUEST['node']) || !$category->Get($_REQUEST['node'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'manageprimaryspecs', 'alpha', 18, 18);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$groups = array();
			
			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/([0-9]+)/', $key, $matches)) {
					$groups[] = $matches[1];
				}
			}
			
			if(!empty($groups)) {
				$values = array();
				
				$data = new DataQuery(sprintf("SELECT Value_ID FROM product_specification_value WHERE Group_ID IN (%s)", implode(', ', $groups)));
				while($data->Row) {
					$values[] = $data->Row['Value_ID'];
					
					$data->Next();
				}
				$data->Disconnect();
				
				if(!empty($values)) {
					managePrimarySpecsCategory($form->GetValue('node'), $values);
				}
			}

			redirect(sprintf("Location: %s?cat=%d", $_SERVER['PHP_SELF'], $form->GetValue('node')));
		}
	}

	$page = new Page(sprintf("Primary Specifications: %s", $category->Name), 'Select primary specifications for this category.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	
	$table = new DataTable('specifications');
	$table->SetSQL(sprintf("SELECT Group_ID, Name FROM product_specification_group"));
	$table->SetExtractVars();
	$table->AddField('Group ID', 'Group_ID', 'left');
	$table->AddField('Name', 'Name', 'left');
	$table->AddInput('', 'N', 'Y', 'select', 'Group_ID', 'checkbox');
	$table->SetMaxRows(999999);
	$table->SetOrderBy("Name");
	$table->Order = "ASC";
	$table->Finalise();
	$table->DisplayTable();

	echo '<br />';
	echo sprintf('<input type="submit" name="update" value="update" class="btn" tabindex="%s" />', $form->GetTabIndex());

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function managePrimarySpecsCategory($categoryId, array $values, $products = array()) {
	$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", $categoryId));
	while($data->Row) {
		if(!isset($products[$data->Row['Product_ID']])) {
			$products[$data->Row['Product_ID']] = true;
			
			new DataQuery(sprintf("UPDATE product_specification SET Is_Primary='N' WHERE Product_ID=%d", $data->Row['Product_ID']));
			new DataQuery(sprintf("UPDATE product_specification SET Is_Primary='Y' WHERE Product_ID=%d AND Value_ID IN (%s)", $data->Row['Product_ID'], implode(', ', $values)));
			
			$product = new Product($data->Row['Product_ID']);
			$product->UpdateSpecCache();
			
			$cache = Zend_Cache::factory('Output', $GLOBALS['CACHE_BACKEND']);
			$cache->remove('product__' . $product->ID);
			$cache->remove('product_prices__product_id__' . $product->ID);
		}

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		$products = managePrimarySpecsCategory($data->Row['Category_ID'], $values, $products);

		$data->Next();
	}
	$data->Disconnect();

	return $products;
}

function manageRelated() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductRelated.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'managerelated', 'alpha', 13, 13);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Category ID', 'hidden', '', 'numeric_unsigned', 1, 11);

	$products = array();

	$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID WHERE pic.Category_ID=%d ORDER BY p.Product_ID ASC', mysql_real_escape_string($form->GetValue('node'))));
	while($data->Row) {
		$item = $data->Row;
		$item['Related'] = array();

		$data2 = new DataQuery(sprintf('SELECT pr.Product_Related_ID, pr.Type, p.Product_ID, p.Product_Title, p.SKU FROM product AS p INNER JOIN product_related AS pr ON pr.Product_ID=p.Product_ID WHERE pr.Related_To_Product_ID=%d', mysql_real_escape_string($item['Product_ID'])));
		while($data2->Row) {
			$item['Related'][] = $data2->Row;

			$data2->Next();
		}
		$data2->Disconnect();

		$formProduct = new Form($_SERVER['PHP_SELF']);
		$formProduct->AddField('action', 'Action', 'hidden', 'managerelated', 'alpha', 13, 13);
		$formProduct->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
		$formProduct->AddField('form', 'Form', 'hidden', $item['Product_ID'], 'paragraph', 1, 120);
		$formProduct->AddField('node', 'Category ID', 'hidden', '', 'numeric_unsigned', 1, 11);
		$formProduct->AddField('pid', 'Product ID', 'text', '', 'numeric_unsigned', 1, 11, true, 'width="100%"');
		$formProduct->AddField('type', 'Type', 'select', '', 'paragraph', 0, 240, false, 'width="100%"');
		$formProduct->AddOption('type', '', '');
		$formProduct->AddOption('type', 'Energy Saving Alternative', 'Energy Saving Alternative');

		if(isset($_REQUEST['confirm'])) {
			if(isset($_REQUEST['form'])	&& ($_REQUEST['form'] == $item['Product_ID'])) {
				if($formProduct->Validate()) {
					$product = new Product();

					if(!$product->Get($formProduct->GetValue('pid'))) {
						$formProduct->AddError(sprintf('The chosen Product ID #%d does not exist.', $product->ID), 'pid');
					}

					if($formProduct->Valid) {
						$related = new ProductRelated();
						$related->Product->ID = $formProduct->GetValue('pid');
						$related->Parent->ID = $item['Product_ID'];
						$related->Type = $formProduct->GetValue('type');
						$related->IsActive = 'Y';
						$related->Add();

						redirectTo(sprintf('?action=managerelated&node=%d', $formProduct->GetValue('node')));
					}
				}
			}
		}

		$item['Form'] = $formProduct;

		$products[] = $item;

		$data->Next();
	}
	$data->Disconnect();

	$page = new Page('Manage Related Products', 'Manage related products of this category.');
	$page->Display('header');

	foreach($products as $product) {
		$formProduct = $product['Form'];

		if(!$formProduct->Valid) {
			echo $formProduct->GetError();
			echo "<br />";
		}
	}
	?>

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th width="10%">Product ID</th>
				<th>Name</th>
				<th>SKU</th>
			</tr>
		</thead>
		<tbody>

			<?php
			foreach($products as $product) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $product['Product_ID']; ?></td>
					<td><?php echo $product['Product_Title']; ?></td>
					<td><?php echo $product['SKU']; ?></td>
				</tr>
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td style="background-color: #fff;">&nbsp;</td>
					<td style="background-color: #fff;" colspan="2">

						<?php
						$formProduct = $product['Form'];

						echo $formProduct->Open();
						echo $formProduct->GetHTML('action');
						echo $formProduct->GetHTML('confirm');
						echo $formProduct->GetHTML('form');
						echo $formProduct->GetHTML('node');
						?>
						
						<br />
						<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
							<thead>
								<tr>
									<th width="10%">Product ID</th>
									<th>Name</th>
									<th>SKU</th>
									<th>Type</th>
									<th width="1%">&nbsp;</th>
								</tr>
							</thead>
							<tbody>

								<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
									<td><?php echo $formProduct->GetHTML('pid'); ?></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td><?php echo $formProduct->GetHTML('type'); ?></td>
									<td><input type="image" value="add" name="add" src="images/button-plus.gif" /></td>
								</tr>

								<?php
								foreach($product['Related'] as $related) {
									?>

									<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
										<td><?php echo $related['Product_ID']; ?></td>
										<td><?php echo $related['Product_Title']; ?></td>
										<td><?php echo $related['SKU']; ?></td>
										<td><?php echo $related['Type']; ?></td>
										<td><a href="javascript:confirmRequest('?action=removerelated&id=<?php echo $related['Product_Related_ID']; ?>&node=<?php echo $form->GetValue('node'); ?>', 'Are you sure you wish to remove this item?');"><img src="images/button-cross.gif" /></a></td>
									</tr>

									<?php
								}
								?>

							</tbody>
						</table>
						<br />

						<?php
						echo $formProduct->Close();
						?>

					</td>
				</tr>

				<?php
			}
			?>

		</tbody>
	</table>

	<?php
	$page->Display('footer');
}

function manageLinks() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductLink.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'managelinks', 'alpha', 11, 11);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Category ID', 'hidden', '', 'numeric_unsigned', 1, 11);

	$links = array();

	$data = new DataQuery(sprintf('SELECT pll.*, a.hash FROM product_link_library AS pll INNER JOIN asset AS a ON a.id=pll.assetId'));
	while($data->Row) {
		$links[] = $data->Row;

		$data->Next();
	}
	$data->Disconnect();

	foreach($links as $link) {
		$form->AddField('library_'.$link['id'], 'Library Link', 'checkbox', 'N', 'boolean', 1, 1, false);
	}

	$products = array();

	$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID WHERE pic.Category_ID=%d ORDER BY p.Product_ID ASC', mysql_real_escape_string($form->GetValue('node'))));
	while($data->Row) {
		$item = $data->Row;
		$item['Links'] = array();

		$data2 = new DataQuery(sprintf('SELECT pl.*, a.hash FROM product_link AS pl INNER JOIN asset AS a ON pl.assetId=a.id WHERE pl.productId=%d', mysql_real_escape_string($item['Product_ID'])));
		while($data2->Row) {
			$item['Links'][] = $data2->Row;

			$data2->Next();
		}
		$data2->Disconnect();

		$products[] = $item;

		$data->Next();
	}
	$data->Disconnect();

	foreach($products as $product) {
		$form->AddField('product_'.$product['Product_ID'], 'Product Link', 'checkbox', 'N', 'boolean', 1, 1, false);
	}

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			foreach($products as $product) {
				if($form->GetValue('product_'.$product['Product_ID']) == 'Y') {
					foreach($links as $link) {
						if($form->GetValue('library_'.$link['id']) == 'Y') {
							$linkObj = new ProductLink();
							$linkObj->product->ID = $product['Product_ID'];
							$linkObj->asset->id = $link['assetId'];
							$linkObj->name = $link['name'];
							$linkObj->url = $link['url'];
							$linkObj->add();
						}
					}
				}
			}

			redirectTo(sprintf('?action=managelinks&node=%d', $form->GetValue('node')));
		}
	}

	$page = new Page('Manage Product Links', 'Manage product links for this category.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	?>

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th width="1%">&nbsp;</th>
				<th>Name</th>
				<th>URL</th>
				<th width="1%">&nbsp;</th>
			</tr>
		</thead>
		<tbody>

			<?php
			foreach($links as $link) {
				?>
				
				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><img src="asset.php?hash=<?php echo $link['hash']; ?>" /></td>
					<td><?php echo $link['name']; ?></td>
					<td><?php echo $link['url']; ?></td>
					<td><?php echo $form->GetHTML('library_'.$link['id']); ?></td>
				</tr>
				
				<?php
			}
			?>

		</tbody>
	</table>
	<br />

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th width="10%">Product ID</th>
				<th>Name</th>
				<th>SKU</th>
				<th width="1%">&nbsp;</th>
			</tr>
		</thead>
		<tbody>

			<?php
			foreach($products as $product) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $product['Product_ID']; ?></td>
					<td><?php echo $product['Product_Title']; ?></td>
					<td><?php echo $product['SKU']; ?></td>
					<td><?php echo $form->GetHTML('product_'.$product['Product_ID']); ?></td>
				</tr>

				<?php
				if(!empty($product['Links'])) {
					?>

					<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
						<td style="background-color: #fff;">&nbsp;</td>
						<td style="background-color: #fff;" colspan="3">
							
							<?php
							foreach($product['Links'] as $link) {
								?>

								<div style="float: left; padding: 5px; text-align: center;">
									<a href="<?php echo $link['url']; ?>" target="_blank">
										<img src="asset.php?hash=<?php echo $link['hash']; ?>" /><br />
										<?php echo $link['name']; ?>
									</a>
								</div>

								<?php
							}
							?>

						</td>
					</tr>

					<?php
				}
			}
			?>

		</tbody>
	</table>
	<br />

	<input type="submit" class="btn" name="add" value="add" />

	<?php
	echo $form->Close();

	$page->Display('footer');
}

function manageQuality() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

	$category = new Category();

	if(!isset($_REQUEST['node']) || !$category->Get($_REQUEST['node'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'managequality', 'alpha', 13, 13);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Node', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Include Sub Categories?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			redirect(sprintf("Location: ?action=managequalityproducts&node=%d&sub=%s", $form->GetValue('node'), $form->GetValue('sub')));
		}
	}

	$page = new Page(sprintf("Product Quality Settings: %s", $category->Name), 'Please complete the form below.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow('Select Product Review');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');

	echo $window->Open();
	echo $window->AddHeader('Required fields are denoted by an asterisk (*)');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('sub'), $form->GetHTML('sub'). $form->GetIcon('sub'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_categories.php\';"> <input type="submit" name="submit" value="submit" class="btn" tabindex="%s">', $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();

	$page->Display('footer');

	require_once('lib/common/app_footer.php');
}

function manageQualityProducts() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$category = new Category();

	if(!isset($_REQUEST['node']) || !$category->Get($_REQUEST['node'])) {
		redirect(sprintf("Location: %s", $_SERVER['PHP_SELF']));
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'managequalityproducts', 'alpha', 21, 21);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('node', 'Category ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Include Sub Categories?', 'hidden', 'N', 'alpha', 1, 1);

	$categories = ($form->GetValue('sub') == 'Y') ? getSubCategories($category->ID) : array($category->ID);
	$products = array();

	$data = new DataQuery(sprintf('SELECT p.Product_ID, p.Product_Title, p.SKU, p.Quality FROM product AS p INNER JOIN product_in_categories AS pic ON pic.Product_ID=p.Product_ID WHERE pic.Category_ID IN (%s) ORDER BY p.Product_ID ASC', implode(', ', $categories)));
	while($data->Row) {
		$products[] = $data->Row;

		$form->AddField('quality_' . $data->Row['Product_ID'], 'Quality', 'select', $data->Row['Quality'], 'paragraph', 0, 30, false);
		$form->AddOption('quality_' . $data->Row['Product_ID'], '', '');
		$form->AddOption('quality_' . $data->Row['Product_ID'], 'Value', 'Value');
		$form->AddOption('quality_' . $data->Row['Product_ID'], 'Premium', 'Premium');

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			foreach($products as $product) {
				$productObj = new Product($product['Product_ID']);
				$productObj->Quality = $form->GetValue('quality_' . $product['Product_ID']);
				$productObj->Update();
			}

			redirectTo(sprintf('?action=managequalityproducts&node=%d&sub=%s', $form->GetValue('node'), $form->GetValue('sub')));
		}
	}

	$page = new Page('Manage Product Quality', 'Manage product quality of this category.');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('node');
	echo $form->GetHTML('sub');
	?>

	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th>Product ID</th>
				<th>Name</th>
				<th>SKU</th>
				<th>Quality</th>
			</tr>
		</thead>
		<tbody>

			<?php
			foreach($products as $product) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $product['Product_ID']; ?></td>
					<td><?php echo $product['Product_Title']; ?></td>
					<td><?php echo $product['SKU']; ?></td>
					<td><?php echo $form->GetHTML('quality_' . $product['Product_ID']); ?></td>
				</tr>

				<?php
			}
			?>

		</tbody>
	</table>
	<br />

	<input type="submit" class="btn" name="update" value="update" />

	<?php
	echo $form->Close();

	$page->Display('footer');
}