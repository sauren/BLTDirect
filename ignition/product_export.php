<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
view();

function view(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			export($form->GetValue('parent'), (strtolower($form->GetValue('subfolders')) == 'y')?true:false);
			exit();
		}
	}

	$page = new Page('Export Products to CSV', '');

	$page->Display('header');
	// Show Error Report if Form Object validation fails
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Export Products from a Category.");
	$webForm = new StandardForm;
	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('parent');
	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category to export from.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption"></span>&nbsp; &nbsp;<input type="submit" name="export" value="export" class="btn" />');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	echo "<br>";
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function export($cat, $subfolders=false){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	$lines = array();
	/*
	Export Fields:
	1.  Product ID
	2.  Product Category
	3.  Product Title
	4. 	Short Description
	5.  Long Description
	6.  SKU
	7.  Manufacturer
	8.  Model
	9.  Variant
	10. Meta Title
	11. Meta Keywords
	12. Meta Description
	13. Weight
	14. Shelf Width
	15. Shelf Height
	16. Shelf Depth
	17. Stocked
	18. Monitor Stock
	19. Stock Alert
	20. Stock Suspend
	21. Our Price
	22. RRP Price
	23. Thumbnail
	24. Large Image
	25. Image Title
	26. Image Description
	27. Shipping Class
	28. Tax Class
	29. Stock Imported
	30. Guarantee
	31. Despatch Min
	32. Despatch Max
	33. Min Order
	34. Max Order
	35. Discount Limit
	*/
	$exportTemplate = file('./lib/templates/productCsvExportTemplate.csv');
	$lines[] = split(',',trim($exportTemplate[0]));

	$fileDate = getDatetime();
	$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

	$filename = "ignition_product_export_" . $fileDate.".csv";

	// Set File Headers
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

	if((is_integer(strpos($userAgent, "msie"))) && (is_integer(strpos($userAgent, "win")))){
		header("Content-Disposition: filename=" . basename($filename) . ";");
	} else {
		header("Content-Disposition: attachment; filename=" . basename($filename) . ";");
	}
	header("Content-Transfer-Encoding: binary");

	outputColumns();

	// output folder contents
	getFolderContent($cat, $subfolders);
}

function getFolderContent($node, $getSubfolders){
	$sql = sprintf("select p.Product_ID, pc.Category_Title, pc.Category_ID, sp.Cost
				FROM product_in_categories as pic
				inner join product as p on p.Product_ID=pic.Product_ID
				left join product_categories as pc on pc.Category_ID=pic.Category_ID
				LEFT JOIN supplier_product AS sp ON sp.Product_ID=p.Product_ID AND sp.Preferred_Supplier='Y'
				where pic.Category_ID=%d", mysql_real_escape_string($node));

	$products = new DataQuery($sql);
	while($products->Row){
		$line = array();
		$prod = new Product($products->Row['Product_ID']);
		$line[] = $prod->ID;
		$line[] = $products->Row['Category_Title'];
		$line[] = $GLOBALS['HTTP_SERVER'] . 'product.php?pid=' . $prod->ID . '&cat=' . $products->Row['Category_ID'];
		$line[] = $prod->Name;
		$line[] = $prod->Blurb;
		$line[] = $prod->Description;
		$line[] = $prod->SKU;
		$line[] = $prod->Manufacturer->Name;
		$line[] = $prod->Model;
		$line[] = $prod->Variant;
		$line[] = $prod->MetaTitle;
		$line[] = $prod->MetaKeywords;
		$line[] = $prod->MetaDescription;
		$line[] = $prod->Weight;
		$line[] = $prod->Width;
		$line[] = $prod->Height;
		$line[] = $prod->Depth;
		$line[] = $prod->Stocked;
		$line[] = $prod->StockMonitor;
		$line[] = $prod->StockAlert;
		$line[] = $prod->StockSuspend;
		$line[] = $prod->PriceOurs;
		$line[] = $prod->PriceRRP;
		$line[] = $products->Row['Cost'];
		$line[] = $prod->DefaultImage->Thumb->FileName;
		$line[] = substr($GLOBALS['HTTP_SERVER'], 0, -1) . $GLOBALS['PRODUCT_IMAGES_DIR_WS'] . $prod->DefaultImage->Thumb->FileName;
		$line[] = $prod->DefaultImage->Large->FileName;
		$line[] = substr($GLOBALS['HTTP_SERVER'], 0, -1) . $GLOBALS['PRODUCT_IMAGES_DIR_WS'] . $prod->DefaultImage->Large->FileName;
		$line[] = $prod->DefaultImage->Name;
		$line[] = $prod->DefaultImage->Description;
		$line[] = $prod->ShippingClass->Name;
		$line[] = $prod->TaxClass->Name;
		$line[] = $prod->StockImported;
		$line[] = $prod->Guarantee;
		$line[] = $prod->DespatchDaysMin;
		$line[] = $prod->DespatchDaysMax;
		$line[] = $prod->OrderMin;
		$line[] = $prod->OrderMax;
		$line[] = $prod->DiscountLimit;

		print(getCsv($line));
		unset($line);

		$products->Next();
	}
	$products->Disconnect();

	// Do we continue getting data?
	if($getSubfolders){
		$sql = "select pc.Category_ID, pc.Category_Title from product_categories as pc where pc.Category_Parent_ID=" . mysql_real_escape_string($node);
		$data = new DataQuery($sql);
		while($data->Row){
			getFolderContent($data->Row['Category_ID'], $getSubfolders);
			$data->Next();
		}
		$data->Disconnect();
	}
}

/*
$products = new DataQuery("select pic.Product_ID, pc.Category_Title from product_in_categories as pic inner join product_categories as pc on pc.Category_ID=pic.Category_ID where pic.Category_ID=" . $cat);

while($products->Row){
$line = array();
$prod = new Product($products->Row['Product_ID']);
$line[] = $prod->ID;
$line[] = $products->Row['Category_Title'];
$line[] = $prod->Name;
$line[] = $prod->Blurb;
$line[] = $prod->Description;
$line[] = $prod->SKU;
$line[] = $prod->Manufacturer->Name;
$line[] = $prod->Model;
$line[] = $prod->Variant;
$line[] = $prod->MetaTitle;
$line[] = $prod->MetaKeywords;
$line[] = $prod->MetaDescription;
$line[] = $prod->Weight;
$line[] = $prod->Width;
$line[] = $prod->Height;
$line[] = $prod->Depth;
$line[] = $prod->Stocked;
$line[] = $prod->StockMonitor;
$line[] = $prod->StockAlert;
$line[] = $prod->StockSuspend;
$line[] = $prod->PriceOurs;
$line[] = $prod->PriceRRP;
$line[] = $prod->DefaultImage->Thumb->FileName;
$line[] = $prod->DefaultImage->Large->FileName;
$line[] = $prod->DefaultImage->Name;
$line[] = $prod->DefaultImage->Description;
$line[] = $prod->ShippingClass->Name;
$line[] = $prod->TaxClass->Name;
$line[] = $prod->StockImported;
$line[] = $prod->Guarantee;
$line[] = $prod->DespatchDaysMin;
$line[] = $prod->DespatchDaysMax;
$line[] = $prod->OrderMin;
$line[] = $prod->OrderMax;
$lines[] = $line;
unset($line);
$products->Next();
}
$products->Disconnect();

// wow now we've done all that lets export to a csvFile;
$fp = fopen('./temp/productExport.csv', 'w');
foreach ($lines as $line) {
fputcsv($fp, $line);
}
fclose($fp);
redirect("Location: ./temp/productExport.csv");
}


function fputcsv($handle, $row, $fd=',', $quot='"'){
$str='';
foreach ($row as $cell)
{
$cell = str_replace($quot, $quot.$quot, $cell);

if (strchr($cell, $fd) !== FALSE || strchr($cell, $quot) !== FALSE || strchr($cell, "\n") !== FALSE)
{
$str .= $quot.$cell.$quot.$fd;
}
else
{
$str .= $cell.$fd;
}
}

fputs($handle, substr($str, 0, -1)."\n");

return strlen($str);

}*/

function getCsv($row, $fd=',', $quot='"'){
	$str ='';
	foreach($row as $cell){
		$cell = str_replace($quot, $quot.$quot, $cell);

		if (strchr($cell, $fd) !== FALSE || strchr($cell, $quot) !== FALSE || strchr($cell, "\n") !== FALSE) {
			$str .= $quot.$cell.$quot.$fd;
		}
		else {
			$str .= $quot.$cell.$quot.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}

function outputColumns(){
	$line = array();
	$line[] = 'Product ID';
	$line[] = 'Product Category';
	$line[] = 'Product URL';
	$line[] = 'Product Title';
	$line[] = 'Short Description';
	$line[] = 'Long Description';
	$line[] = 'SKU';
	$line[] = 'Manufacturer';
	$line[] = 'Model';
	$line[] = 'Variant';
	$line[] = 'Meta Title';
	$line[] = 'Meta Keywords';
	$line[] = 'Meta Description';
	$line[] = 'Weight';
	$line[] = 'Shelf Width';
	$line[] = 'Shelf Height';
	$line[] = 'Shelf Depth';
	$line[] = 'Stocked';
	$line[] = 'Monitor Stock';
	$line[] = 'Stock Alert';
	$line[] = 'Stock Suspend';
	$line[] = 'Our Price';
	$line[] = 'RRP Price';
	$line[] = 'Cost Price';
	$line[] = 'Thumbnail';
	$line[] = 'Thumbnail URL';
	$line[] = 'Large Image';
	$line[] = 'Large Image URL';
	$line[] = 'Image Title';
	$line[] = 'Image Description';
	$line[] = 'Shipping Class';
	$line[] = 'Tax Class';
	$line[] = 'Stock Imported';
	$line[] = 'Guarantee';
	$line[] = 'Despatch Min';
	$line[] = 'Despatch Max';
	$line[] = 'Min Order';
	$line[] = 'Max Order';
	$line[] = 'Discount Limit';

	print(getCsv($line));
}
?>