<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Product.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/Image.php");
require_once($GLOBALS["DIR_WS_ADMIN"] . "lib/classes/UrlAlias.php");

class Category {
	public static $categories = array();
	public static $cacheName = 'template_categories';

	public static function GetCategory($id) {
		if(!isset(self::$categories[$id])) {
			self::$categories[$id] = new Category($id);
		}

		return self::$categories[$id];
	}
	
	public static function Cache() {
		$cache = '';

		$subCategory = new Category();

		$data = new DataQuery(sprintf("SELECT Category_ID, Category_Title, Meta_Title FROM product_categories WHERE Category_Parent_ID=1 AND Is_Active='Y' ORDER BY Sequence ASC"));
		while($data->Row){
			$subCategory->ID = $data->Row['Category_ID'];
			$subCategory->Name = $data->Row['Category_Title'];
			$subCategory->MetaTitle = $data->Row['Meta_Title'];

			$url = $subCategory->GetUrl();

			$cache .= sprintf("%s\n", $data->Row['Category_Title']);
			$cache .= sprintf("%s\n", $url);

			$data->Next();
		}
		$data->Disconnect();

		CacheFile::save(self::$cacheName, $cache);
	}

	var $ID;
	var $Sequence;
	var $Name;
	var $Parent;
	var $Description;
	var $DescriptionSecondary;
	var $MetaTitle;
	var $MetaDescription;
	var $MetaKeywords;
	var $SearchTerm;
	var $SearchTermTitle;
	var $Thumb;
	var $Large;
	var $Language;
	var $IsActive;
	var $IsRedirecting;
	var $IsFilterAvailable;
	var $IsProductListAvailable;
	var $RedirectUrl;
	var $UseUrlAlias;
	var $Order;
	var $CategoryOrder;
	var $ProductOffer;
	var $ShowImage;
	var $ShowImages;
	var $ShowDescriptions;
	var $ShowBestBuys;
	var $ColumnCountText;
	var $Layout;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;

	var $CategoryMode;
	var $ShowBuyButton;
	var $PriceColour;
	var $PriceSize;
	var $PriceWeight;

	function Category($id=NULL, $connection = null) {
		$this->IsActive = 'Y';
		$this->IsRedirecting = 'N';
		$this->IsFilterAvailable = 'Y';
		$this->IsProductListAvailable = 'N';
		$this->ShowImage = 'Y';
		$this->ShowImages = 'Y';
		$this->ShowDescriptions = 'Y';
		$this->ShowBestBuys = 'N';
		$this->ColumnCountText = 2;
		$this->ProductOffer = new Product();
		$this->UseUrlAlias = 'N';
		$this->Layout = 'Table';

		$this->Thumb = new Image();
		$this->Thumb->OnConflict = "makeunique";
		$this->Thumb->SetMinDimensions($GLOBALS['CATEGORY_THUMB_MIN_WIDTH'], $GLOBALS['CATEGORY_THUMB_MIN_HEIGHT']);
		$this->Thumb->SetMaxDimensions($GLOBALS['CATEGORY_THUMB_MAX_WIDTH'], $GLOBALS['CATEGORY_THUMB_MAX_HEIGHT']);
		$this->Thumb->SetDirectory($GLOBALS['CATEGORY_IMAGES_DIR_FS']);

		$this->Large = new Image();
		$this->Large->OnConflict = "makeunique";
		$this->Large->SetMinDimensions($GLOBALS['CATEGORY_IMG_MIN_WIDTH'], $GLOBALS['CATEGORY_IMG_MIN_HEIGHT']);
		$this->Large->SetMaxDimensions($GLOBALS['CATEGORY_IMG_MAX_WIDTH'], $GLOBALS['CATEGORY_IMG_MAX_HEIGHT']);
		$this->Large->SetDirectory($GLOBALS['CATEGORY_IMAGES_DIR_FS']);

		if(!is_null($id)){
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL, $connection = null){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if($this->ID > 0){
			$data = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_ID=%d", $this->ID), $connection);
			if($data->TotalRows > 0) {
				$this->Parent = new Category();
				$this->Parent->ID = $data->Row['Category_Parent_ID'];
				$this->ProductOffer->ID = $data->Row['Product_Offer_ID'];
				$this->Sequence = $data->Row['Sequence'];
				$this->Name = $data->Row['Category_Title'];
				$this->Description = $data->Row['Category_Description'];
				$this->DescriptionSecondary = $data->Row['Category_Description_Secondary'];
				$this->MetaTitle = $data->Row['Meta_Title'];
				$this->MetaDescription = $data->Row['Meta_Description'];
				$this->MetaKeywords = $data->Row['Meta_Keywords'];
				$this->SearchTerm = $data->Row['Search_Term'];
				$this->SearchTermTitle = $data->Row['Search_Term_Title'];
				$this->IsActive = $data->Row['Is_Active'];
				$this->IsRedirecting = $data->Row['Is_Redirecting'];
				$this->IsFilterAvailable = $data->Row['Is_Filter_Available'];
				$this->IsProductListAvailable = $data->Row['Is_Product_List_Available'];
				$this->RedirectUrl = $data->Row['Redirect_Url'];
				$this->UseUrlAlias = $data->Row['Use_Url_Alias'];
				$this->Order = $data->Row['Order'];
				$this->CategoryOrder = $data->Row['Category_Order'];
				$this->ShowImage = $data->Row['Show_Image'];
				$this->ShowImages = $data->Row['Show_Images'];
				$this->ShowDescriptions = $data->Row['Show_Descriptions'];
				$this->ShowBestBuys = $data->Row['Show_Best_Buys'];
				$this->ColumnCountText = $data->Row['Column_Count_Text'];
				$this->Thumb->SetName($data->Row['Category_Thumb']);
				$this->Thumb->Width = $data->Row['Category_Thumb_Width'];
				$this->Thumb->Height = $data->Row['Category_Thumb_Height'];
				$this->Large->SetName($data->Row['Category_Image']);
				$this->Large->Width = $data->Row['Category_Image_Width'];
				$this->Large->Height = $data->Row['Category_Image_Height'];
				$this->Layout = $data->Row['Layout'];
				$this->CreatedOn = $data->Row['Created_On'];
				$this->CreatedBy = $data->Row['Created_By'];
				$this->ModifiedOn = $data->Row['Modified_On'];
				$this->ModifiedBy = $data->Row['Modified_By'];

				$this->CategoryMode = $data->Row['Category_Mode'];
				$this->ShowBuyButton = $data->Row['Show_Buy_Button'];
				$this->PriceColour = $data->Row['Price_Colour'];
				$this->PriceSize = $data->Row['Price_Size'];
				$this->PriceWeight = $data->Row['Price_Weight'];

				$data->Disconnect();
				return true;
			}

			$data->Disconnect();
			return false;
		} else {
			$this->Name = "_root";
		}

		return true;
	}

	function GetParentInfo(){
		if(is_object($this->Parent) && isset($this->Parent->ID)){
			$this->Parent->Get();
		}
	}

	function Add($thumbField=NULL, $largeField=NULL){
		if(!is_null($largeField) && isset($_FILES[$largeField]) && !empty($_FILES[$largeField]['name'])){
			if(!$this->Large->Upload($largeField)){
				return false;
			} else {
				if(!$this->Large->CheckDimensions()){
					$this->Large->Resize();
				}
			}
		}

		if(!is_null($largeField) && $largeField == $thumbField  && !empty($_FILES[$largeField]['name'])){
			$tempFileName = $this->Large->Name . "_thumb." . $this->Large->Extension;
			$this->Large->Copy($this->Thumb->Directory, $tempFileName);
			$this->Thumb->SetName($tempFileName);
			$this->Thumb->Width = $this->Large->Width;
			$this->Thumb->Height = $this->Large->Height;

			if(!$this->Thumb->CheckDimensions()){
				$this->Thumb->Resize();
			}
		} else {
			if(!is_null($thumbField) && isset($_FILES[$thumbField]) && !empty($_FILES[$thumbField]['name'])){
				if(!$this->Thumb->Upload($thumbField)){
					return false;
				} else {
					if(!$this->Thumb->CheckDimensions()){
						$this->Thumb->Resize();
					}
				}
			}
		}

		$data = new DataQuery(sprintf("INSERT INTO product_categories
												(Category_Parent_ID,
												Product_Offer_ID,
												Show_Image,
												Show_Images,
												Show_Descriptions,
												Show_Best_Buys,
												Column_Count_Text,
												Category_Title,
												Category_Description,
												Category_Description_Secondary,
												Meta_Title,
												Meta_Description,
												Meta_Keywords,
												Search_Term,
												Search_Term_Title,
												Category_Thumb,
												Category_Thumb_Width,
												Category_Thumb_Height,
												Category_Image,
												Category_Image_Width,
												Category_Image_Height,
												Is_Active,
												Is_Redirecting,
												Is_Filter_Available,
												Is_Product_List_Available,
												Redirect_Url,
												Use_Url_Alias,
												`Order`,
                                                Category_Order,
                                                Layout,
												Created_On,
												Created_By,
												Modified_On,
												Modified_By,
												Category_Mode,
												Show_Buy_Button,
												Price_Colour,
												Price_Size,
												Price_Weight)
												VALUES (%d, %d, '%s','%s','%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', Now(), %d, Now(), %d, '%s', '%s', '%s', %d, '%s')",
		mysql_real_escape_string($this->Parent->ID),
		mysql_real_escape_string($this->ProductOffer->ID),
		mysql_real_escape_string($this->ShowImage),
		mysql_real_escape_string($this->ShowImages),
		mysql_real_escape_string($this->ShowDescriptions),
		mysql_real_escape_string($this->ShowBestBuys),
		mysql_real_escape_string($this->ColumnCountText),
		mysql_real_escape_string(stripslashes($this->Name)),
		mysql_real_escape_string(stripslashes($this->Description)),
		mysql_real_escape_string(stripslashes($this->DescriptionSecondary)),
		mysql_real_escape_string(stripslashes($this->MetaTitle)),
		mysql_real_escape_string(stripslashes($this->MetaDescription)),
		mysql_real_escape_string(stripslashes($this->MetaKeywords)),
		mysql_real_escape_string(stripslashes($this->SearchTerm)),
		mysql_real_escape_string(stripslashes($this->SearchTermTitle)),
		mysql_real_escape_string($this->Thumb->FileName),
		mysql_real_escape_string($this->Thumb->Width),
		mysql_real_escape_string($this->Thumb->Height),
		mysql_real_escape_string($this->Large->FileName),
		mysql_real_escape_string($this->Large->Width),
		mysql_real_escape_string($this->Large->Height),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsRedirecting),
		mysql_real_escape_string($this->IsFilterAvailable),
		mysql_real_escape_string($this->IsProductListAvailable),
		mysql_real_escape_string($this->RedirectUrl),
		mysql_real_escape_string($this->UseUrlAlias),
		mysql_real_escape_string($this->Order),
		mysql_real_escape_string($this->CategoryOrder),
		mysql_real_escape_string($this->Layout),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->CategoryMode),
		mysql_real_escape_string($this->ShowBuyButton),
		mysql_real_escape_string($this->PriceColour),
		mysql_real_escape_string($this->PriceSize),
		mysql_real_escape_string($this->PriceWeight)));

		$this->ID = $data->InsertID;
		
		$this->Sequence = $this->ID;
		$this->Update();

		return true;
	}

	function Update($thumbField=NULL, $largeField=NULL){
		$oldLarge = new Image($this->Large->FileName, $this->Large->Directory);
		$oldThumb = new Image($this->Thumb->FileName, $this->Thumb->Directory);

		if(!is_null($largeField) && isset($_FILES[$largeField]) && !empty($_FILES[$largeField]['name'])){
			if(!$this->Large->Upload($largeField)){
				return false;
			} else {
				if(!$this->Large->CheckDimensions()){
					$this->Large->Resize();
				}
				if(!empty($oldLarge->FileName)) $oldLarge->Delete();
			}
		}

		if(!is_null($thumbField) && isset($_FILES[$thumbField]) && !empty($_FILES[$thumbField]['name'])){
			if(!$this->Thumb->Upload($thumbField)){
				return false;
			} else {
				if(!$this->Thumb->CheckDimensions()){
					$this->Thumb->Resize();
				}
				if(!empty($oldThumb->FileName)) $oldThumb->Delete();
			}
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("UPDATE product_categories SET
												Category_Parent_ID=%d,
												Product_Offer_ID=%d,
												Show_Image='%s',
												Show_Images='%s',
												Show_Descriptions='%s',
												Show_Best_Buys='%s',
												Column_Count_Text=%d,
                                                Sequence=%d,
												Category_Title='%s',
												Category_Description='%s',
												Category_Description_Secondary='%s',
												Meta_Title='%s',
												Meta_Description='%s',
												Meta_Keywords='%s',
												Search_Term='%s',
												Search_Term_Title='%s',
												Category_Thumb='%s',
												Category_Thumb_Width=%d,
												Category_Thumb_Height=%d,
												Category_Image='%s',
												Category_Image_Width=%d,
												Category_Image_Height=%d,
												Is_Active='%s',
												Is_Redirecting='%s',
												Is_Filter_Available='%s',
												Is_Product_List_Available='%s',
												Redirect_Url='%s',
												Use_Url_Alias='%s',
												`Order`='%s',
												`Category_Order`='%s',
												Layout='%s',
												Modified_On=NOW(),
												Modified_By=%d,
												Category_Mode='%s',
												Show_Buy_Button='%s',
												Price_Colour='%s',
												Price_Size=%d,
												Price_Weight='%s'
												WHERE Category_ID=%d",
		mysql_real_escape_string($this->Parent->ID),
		mysql_real_escape_string($this->ProductOffer->ID),
		mysql_real_escape_string($this->ShowImage),
		mysql_real_escape_string($this->ShowImages),
		mysql_real_escape_string($this->ShowDescriptions),
		mysql_real_escape_string($this->ShowBestBuys),
		mysql_real_escape_string($this->ColumnCountText),
		mysql_real_escape_string($this->Sequence),
		mysql_real_escape_string(stripslashes($this->Name)),
		mysql_real_escape_string(stripslashes($this->Description)),
		mysql_real_escape_string(stripslashes($this->DescriptionSecondary)),
		mysql_real_escape_string(stripslashes($this->MetaTitle)),
		mysql_real_escape_string(stripslashes($this->MetaDescription)),
		mysql_real_escape_string(stripslashes($this->MetaKeywords)),
		mysql_real_escape_string(stripslashes($this->SearchTerm)),
		mysql_real_escape_string(stripslashes($this->SearchTermTitle)),
		mysql_real_escape_string($this->Thumb->FileName),
		mysql_real_escape_string($this->Thumb->Width),
		mysql_real_escape_string($this->Thumb->Height),
		mysql_real_escape_string($this->Large->FileName),
		mysql_real_escape_string($this->Large->Width),
		mysql_real_escape_string($this->Large->Height),
		mysql_real_escape_string($this->IsActive),
		mysql_real_escape_string($this->IsRedirecting),
		mysql_real_escape_string($this->IsFilterAvailable),
		mysql_real_escape_string($this->IsProductListAvailable),
		mysql_real_escape_string($this->RedirectUrl),
		mysql_real_escape_string($this->UseUrlAlias),
		mysql_real_escape_string($this->Order),
		mysql_real_escape_string($this->CategoryOrder),
		mysql_real_escape_string($this->Layout),
		mysql_real_escape_string($GLOBALS['SESSION_USER_ID']),
		mysql_real_escape_string($this->CategoryMode),
		mysql_real_escape_string($this->ShowBuyButton),
		mysql_real_escape_string($this->PriceColour),
		mysql_real_escape_string($this->PriceSize),
		mysql_real_escape_string($this->PriceWeight),
		mysql_real_escape_string($this->ID)));

		$this->UpdateFilterAvailable($this->ID);
		
		if($this->Parent->ID == 1) {
			Category::Cache();
		}
	}

	function UpdateFilterAvailable($categoryId) {
		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", $categoryId));
		while($data->Row) {
			new DataQuery(sprintf("UPDATE product_categories SET Is_Filter_Available='%s' WHERE Category_ID=%d", $this->IsFilterAvailable, $data->Row['Category_ID']));

			$this->UpdateFilterAvailable($data->Row['Category_ID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function GetAffectedProducts($catId) {
		$products = array();

		$data = new DataQuery(sprintf("SELECT Product_ID FROM product_in_categories WHERE Category_ID=%d", $catId));
		while($data->Row) {
			$products[$data->Row['Product_ID']] = $data->Row['Product_ID'];

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", $catId));
		while($data->Row) {
			$temp = $this->GetAffectedProducts($data->Row['Category_ID']);

			foreach($temp as $key) {
				$products[$key] = $key;
			}

			$data->Next();
		}
		$data->Disconnect();

		return $products;
	}

	function GetAffectedCategories($catId) {
		$categories = array();
		$categories[$catId] = $catId;

		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", $catId));
		while($data->Row) {
			$temp = $this->GetAffectedCategories($data->Row['Category_ID']);

			foreach($temp as $key) {
				$categories[$key] = $key;
			}

			$data->Next();
		}
		$data->Disconnect();

		return $categories;
	}

	function Remove($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		$products = $this->GetAffectedProducts($this->ID);
		$categories = $this->GetAffectedCategories($this->ID);

		if(empty($this->Large->FileName)  && empty($this->Thumb->FileName)){
			$this->Get();
		}

		if(!isset($this->Parent)) {
			$this->Get();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$category = new Category();
			$category->Remove($data->Row['Category_ID']);

			$data->Next();
		}
		$data->Disconnect();

		new DataQuery(sprintf("DELETE FROM product_categories WHERE Category_ID=%d", mysql_real_escape_string($this->ID)));
		new DataQuery(sprintf("DELETE FROM product_category_link WHERE Category_ID=%d AND Linked_Category_ID=%d", mysql_real_escape_string($this->ID), mysql_real_escape_string($this->ID)));
		$data = new UrlAlias();
		$data->ReferenceID = $this->ID;
		$data->Delete();

		$product = new Product();
		$product->DeleteFromCategory($this->ID);

		if(!empty($this->Thumb->FileName) && $this->Thumb->Exists()){
			$this->Thumb->Delete();
		}
		if(!empty($this->Large->FileName) && $this->Large->Exists()){
			$this->Large->Delete();
		}

		if($this->Parent->ID == 1) {
			self::Cache();
		}
	}

	function RemoveProduct($id=NULL){
		if(!is_null($id)){
			$product = new Product();
			$product->DeleteFromCategory($this->ID, $id);
		}

		return true;
	}

	function Exists($name=NULL){
		if(!is_null($name)) $this->Name = $name;
		$chkCat = new DataQuery(sprintf("select Category_ID from product_categories where Category_Title='%s'", mysql_real_escape_string($this->Name)));
		if($chkCat->TotalRows > 0){
			$this->ID = $chkCat->Row['Category_ID'];
			$returnVal = true;
		} else {
			$returnVal = false;
		}
		$chkCat->Disconnect();

		return $returnVal;
	}

	function GetUrl() {
		$alias = UrlAlias::getUrl('category', $this->ID);
		
		return !is_null($alias) ? $alias : sprintf('/products.php?cat=%s&amp;nm=%s', $this->ID, !empty($this->MetaTitle) ? urlencode($this->MetaTitle) : urlencode($this->Name));
	}

	function GetPriceStyling() {
		$style = array();

		if(isset($this->PriceColour) && !empty($this->PriceColour)){
			$style[] = sprintf('color:#%s', $this->PriceColour);
		}
		if(isset($this->PriceSize) && !empty($this->PriceSize)){
			$style[] = sprintf('font-size:%dpx', $this->PriceSize);
		}
		if(isset($this->PriceWeight) && !empty($this->PriceWeight)){
			if(strpos(strtolower($this->PriceWeight), 'bold') === false){
				$style[] = 'font-weight:normal';
			} else {
				$style[] = 'font-weight:bold;';
			}

			if(strpos(strtolower($this->PriceWeight), 'italic') !== false){
				$style[] = 'font-style:italic';
			}
		}

		$styling = join('; ', $style);
		return $styling;
	}
}