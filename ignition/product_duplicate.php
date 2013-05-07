<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(3);

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action','action','hidden','duplicate','alpha',9,9);
$form->AddField('confirm','confirm','hidden','true','alpha',4,4);
$form->AddField('pid','pid','Hidden',$_REQUEST['pid'],'numeric_unsigned',1,11);
$oldProduct = new Product($form->GetValue('pid'));
$form->AddField('title','Title Of Product','text',$oldProduct->Name,'paragraph',3,255);
$form->AddField('sku','Product SKU','text',$oldProduct->SKU,'paragraph',1,30, false);
$form->AddField('related','The list of related Products','checkbox','N','alpha',1,1,false);

if(isset($_REQUEST['confirm']) && $_REQUEST['confirm'] == true && $_REQUEST['action'] == 'duplicate'){
	$form->Validate();
	if($form->Valid){
		$productID = $oldProduct->ID;

		$oldProduct->Name = $form->GetValue('title');
		$oldProduct->SKU = $form->GetValue('sku');
		$oldProduct->SpecCache = '';
		$oldProduct->SpecCachePrimary = '';
		$oldProduct->CacheBestCost = 0;
		$oldProduct->CacheBestSupplierID = 0;
		$oldProduct->CacheRecentCost = 0;
		$oldProduct->PositionQuantities = 0;
		$oldProduct->PositionQuantitiesRecent = 0;
		$oldProduct->PositionQuantities3Month = 0;
		$oldProduct->PositionQuantities12Month = 0;
		$oldProduct->PositionOrders = 0;
		$oldProduct->PositionOrdersRecent = 0;
		$oldProduct->PositionOrders3Month = 0;
		$oldProduct->PositionOrders12Month = 0;
		$oldProduct->TotalQuantities = 0;
		$oldProduct->TotalQuantities3Month = 0;
		$oldProduct->TotalQuantities12Month = 0;
		$oldProduct->TotalOrders = 0;
		$oldProduct->TotalOrders3Month = 0;
		$oldProduct->TotalOrders12Month = 0;
		$oldProduct->AverageDespatch = 0;
		$oldProduct->Add();

		$data = new DataQuery(sprintf("SELECT * FROM product_prices
                                          WHERE Price_Starts_On <= Now()
                                          AND Product_ID = %d
                                          ORDER BY Price_Starts_On DESC ",
		mysql_real_escape_string($productID)));
		$dataUpdate = new DataQuery(sprintf("INSERT INTO product_prices(Product_ID,
												Price_Base_Our,
												Price_Base_RRP,
												Is_Tax_Included,
												Price_Starts_On)
												values (%d, %f, %f, '%s', '%s')",
		mysql_real_escape_string($oldProduct->ID),
		mysql_real_escape_string($data->Row['Price_Base_Our']),
		mysql_real_escape_string($data->Row['Price_Base_RRP']),
		mysql_real_escape_string($data->Row['Is_Tax_Included']),
		mysql_real_escape_string($data->Row['Price_Starts_On'])));
		$data->Disconnect();
		$dataUpdate->Disconnect();

		$data = new DataQuery(sprintf("SELECT * FROM product_prices
                                          WHERE Product_ID = %d
                                          AND Price_Starts_On > Now()",
		mysql_real_escape_string($productID)));
		while($data->Row){
			$dataUpdate = new DataQuery(sprintf("INSERT INTO product_prices(Product_ID,
												Price_Base_Our,
												Price_Base_RRP,
												Is_Tax_Included,
												Price_Starts_On)
												values (%d, %f, %f, '%s', '%s')",
			mysql_real_escape_string($oldProduct->ID),
			mysql_real_escape_string($data->Row['Price_Base_Our']),
			mysql_real_escape_string($data->Row['Price_Base_RRP']),
			mysql_real_escape_string($data->Row['Is_Tax_Included']),
			mysql_real_escape_string($data->Row['Price_Starts_On'])));
			$dataUpdate->Disconnect();

			$data->Next();
		}
		$data->Disconnect();

       	$data = new DataQuery(sprintf("SELECT * FROM product_specification WHERE Product_ID=%d", mysql_real_escape_string($productID)));
		while($data->Row){
			$spec = new ProductSpec();
			$spec->Product->ID = $oldProduct->ID;
			$spec->Value->ID = $data->Row['Value_ID'];
			$spec->Add();

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT * FROM product_offers
                                          WHERE Product_ID = %d
                                          AND Offer_End_On >=Now()",
		mysql_real_escape_string($productID)));
		while($data->Row){
			$dataUpdate = new DataQuery(sprintf("insert into product_offers(Product_ID,
												Price_Offer,
												Is_Tax_Included,
												Offer_Start_On,
												Offer_End_On)
												values (%d, %f, '%s', '%s', '%s')",
			mysql_real_escape_string($oldProduct->ID),
			mysql_real_escape_string($data->Row['Price_Offer']),
			mysql_real_escape_string($data->Row['Is_Tax_Included']),
			mysql_real_escape_string($data->Row['Offer_Start_On']),
			mysql_real_escape_string($data->Row['Offer_End_On'])));
			$dataUpdate->Disconnect();
			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT * FROM product_images WHERE Product_ID = %d",$productID));
		while($data->Row){
			$dataUpdate = new DataQuery(sprintf("insert into product_images (
									Product_ID,
									Is_Active,
									Is_Primary,
									Image_Thumb,
									Image_Thumb_Width,
									Image_Thumb_Height,
									Image_Src,
									Image_Src_Width,
									Image_Src_Height,
									Image_Title,
									Image_Description,
									Created_On,
									Created_By,
									Modified_On,
									Modified_By
									) values (%d, '%s', '%s', '%s', %d, %d, '%s', %d, %d, '%s', '%s', Now(), %d, Now(), %d)",
			mysql_real_escape_string($oldProduct->ID),
			mysql_real_escape_string($data->Row['Is_Active']),
			mysql_real_escape_string($data->Row['Is_Primary']),
			mysql_real_escape_string($data->Row['Image_Thumb']),
			mysql_real_escape_string($data->Row['Image_Thumb_Width']),
			mysql_real_escape_string($data->Row['Image_Thumb_Height']),
			mysql_real_escape_string($data->Row['Image_Src']),
			mysql_real_escape_string($data->Row['Image_Src_Width']),
			mysql_real_escape_string($data->Row['Image_Src_Height']),
			mysql_real_escape_string($data->Row['Image_Title']),
			mysql_real_escape_string($data->Row['Image_Description']),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
			mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));
			$dataUpdate->Disconnect();
			$data->Next();
		}

		$sql = "SELECT * FROM product_in_categories WHERE Product_ID = $productID";
		$data = new DataQuery($sql);
		while($data->Row){
			$sql = sprintf("INSERT INTO
							product_in_categories
                                (Product_ID, Category_ID, Sequence_Number)
                                VALUES (%d, %d, %d)",
			mysql_real_escape_string($oldProduct->ID),
			mysql_real_escape_string($data->Row['Category_ID']),
			mysql_real_escape_string($oldProduct->ID));
			$dataUpdate = new DataQuery($sql);
			$data->Next();
		}
		$data->Disconnect();

		if($form->GetValue('related')=='Y'){
			$data = new DataQuery(sprintf("SELECT * FROM product_related WHERE Related_To_Product_ID = %d", mysql_real_escape_string($productID)));
			while($data->Row){
				$dataUpdate = new DataQuery(sprintf("insert into product_related (Product_ID, Related_To_Product_ID) values (%d, %d)",
				mysql_real_escape_string($data->Row['Product_ID']),
				mysql_real_escape_string($oldProduct->ID)));
				$dataUpdate->Disconnect();
				$data->Next();
			}
			$data->Disconnect();
		}
		redirect(sprintf("Location: product_profile.php?pid=%d",$oldProduct->ID));
	}

}

$page = new Page(sprintf('<a href="product_profile.php?pid=%s">Product Profile</a> &gt; Duplicate', $_REQUEST['pid']),'The more information you supply the better your system will become.');

$page->Display('header');
// Show Error Report if Form Object validation fails
if(!$form->Valid){
	echo $form->GetError();
	echo "<br>";
}
$window = new StandardWindow("Duplicate the product");
$webForm = new StandardForm;
echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('action');
echo $form->GetHTML('pid');
echo $window->Open();
echo $window->AddHeader('The Details of the product including the current price, technical specs and current and future offers are duplicated automatically. However there are some additional options');
echo $window->OpenContent();
echo $webForm->Open();
echo $webForm->AddRow($form->GetLabel('title'),$form->GetHTML('title').$form->GetIcon('title'));
echo $webForm->AddRow($form->GetLabel('sku'),$form->GetHTML('sku').$form->GetIcon('sku'));
echo $webForm->AddRow($form->GetLabel('related'),$form->GetHTML('related').$form->GetIcon('related'));
echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_profile.php?pid=%s\';"> <input type="submit" name="duplicate" value="duplicate" class="btn" tabindex="%s">', $_REQUEST['pid'], $form->GetTabIndex()));
echo $webForm->Close();
echo $window->CloseContent();
echo $window->Close();
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');