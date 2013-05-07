<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailTemplate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailBanner.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailDate.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');

class Email {
	var $ID;
	var $EmailTemplateID;
	var $CampaignID;
	var $CreatedOn;
	var $CreatedBy;
	var $ModifiedOn;
	var $ModifiedBy;
	var $Date;
	var $DatesFetched;

	function Email($id=NULL) {
		$this->Date = array();
		$this->DatesFetched = false;

		if(!is_null($id)) {
			$this->ID = $id;
			$this->Get();
		}
	}

	function Get($id=NULL) {
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM email WHERE EmailID=%d", mysql_real_escape_string($this->ID)));
		if($data->TotalRows > 0) {
			$this->EmailTemplateID = $data->Row['EmailTemplateID'];
			$this->CampaignID = $data->Row['CampaignID'];
			$this->CreatedOn = $data->Row['CreatedOn'];
			$this->CreatedBy = $data->Row['CreatedBy'];
			$this->ModifiedOn = $data->Row['ModifiedOn'];
			$this->ModifiedBy = $data->Row['ModifiedBy'];

			$data->Disconnect();
			return true;
		}

		$data->Disconnect();
		return false;
	}

	function GetDates() {
		$this->Date = array();
		$this->DatesFetched = true;
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT EmailDateID FROM email_date WHERE EmailID=%d ORDER BY Date ASC", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$this->Date[] = new EmailDate($data->Row['EmailDateID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function Add(){
		$data = new DataQuery(sprintf("INSERT INTO email (EmailTemplateID, CampaignID, CreatedOn, CreatedBy, ModifiedOn, ModifiedBy) VALUES (%d, %d, NOW(), %d, NOW(), %d)", mysql_real_escape_string($this->EmailTemplateID), mysql_real_escape_string($this->CampaignID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($GLOBALS['SESSION_USER_ID'])));

		$this->ID = $data->InsertID;
	}

	function Update(){
		if(!is_numeric($this->ID)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email SET EmailTemplateID=%d, CampaignID=%d, ModifiedOn=NOW(), ModifiedBy=%d WHERE EmailID=%d", mysql_real_escape_string($this->EmailTemplateID), mysql_real_escape_string($this->CampaignID), mysql_real_escape_string($GLOBALS['SESSION_USER_ID']), mysql_real_escape_string($this->ID)));
	}

	function Delete($id=NULL){
		if(!is_null($id)) {
			$this->ID = $id;
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM email WHERE EmailID=%d", mysql_real_escape_string($this->ID)));

		$date = new EmailDate();

		$data = new DataQuery(sprintf("SELECT EmailDateID FROM email_date WHERE EmailID=%d", mysql_real_escape_string($this->ID)));
		while($data->Row) {
			$date->Delete($data->Row['EmailDateID']);

			$data->Next();
		}
		$data->Disconnect();
	}

	function PrepareTemplate() {
		$templateHtml = '';

		if(!$this->DatesFetched) {
			$this->GetDates();
		}

		$nextDateId = 0;

		foreach($this->Date as $dateItem) {
			if(strtotime($dateItem->Date) > time()) {
				$dateItem->GetPanels();

				if(($dateItem->EmailBannerID > 0) && ($dateItem->EmailProductPoolID > 0) && (count($dateItem->Panel) == 3)) {
					$nextDateId = $dateItem->ID;
					break;
				}
			}
		}

		if($nextDateId > 0) {
			$templateHtml = $this->GetTemplate($nextDateId);
		}

		return $templateHtml;
	}

	function GetTemplate($dateId) {
		$templateHtml = '';

		$date = new EmailDate($dateId);
		$date->GetPanels();

		$banner = new EmailBanner($date->EmailBannerID);
		$template = new EmailTemplate();

		if($template->Get($this->EmailTemplateID)) {
			$columns = 4;
			$rows = $date->ProductLines;
			$pool = array();
			$products = '';
			$panels = '';

			$data = new DataQuery(sprintf("SELECT p.Product_ID FROM email_product_pool_product AS eppp INNER JOIN product AS p ON p.Product_ID=eppp.ProductID WHERE eppp.EmailProductPoolID=%d", mysql_real_escape_string($date->EmailProductPoolID)));
			while($data->Row) {
				$pool[$data->Row['Product_ID']] = $data->Row['Product_ID'];

				$data->Next();
			}
			$data->Disconnect();

			$data = new DataQuery(sprintf("SELECT pc.Category_ID FROM email_product_pool_category AS eppc INNER JOIN product_categories AS pc ON pc.Category_ID=eppc.CategoryID WHERE eppc.EmailProductPoolID=%d", mysql_real_escape_string($date->EmailProductPoolID)));
			while($data->Row) {
				$result = $this->GetSubCategoryProducts($data->Row['Category_ID']);

				foreach($result as $productId) {
					$pool[$productId] = $productId;
				}

				$data->Next();
			}
			$data->Disconnect();

			for($i=0; $i<count($date->Panel); $i++) {
				$panels .= sprintf('<td width="216" align="center"><a href="%s"><img src="http://www.bltdirect.com/images/email/panel/%s" border="0" /></a></td>', $date->Panel[$i]->Link, $date->Panel[$i]->FileName);
			}

			while(count($pool) < ($rows * $columns)) {
				$rows--;
			}
			
			foreach($pool as $productId=>$poolItem) {
				$pool[$productId] = sprintf('p.Product_ID=%d', $productId);
			}
			
			$productPool = array();
			
			if(count($pool) > 0) {
				$data = new DataQuery(sprintf("SELECT p.Product_ID, IF(ISNULL(edp.Sequence), CONVERT(-1, UNSIGNED INTEGER), edp.Sequence) AS Sequence FROM product AS p LEFT JOIN email_date_product AS edp ON edp.ProductID=p.Product_ID AND edp.EmailDateID=%d WHERE %s ORDER BY Sequence ASC", mysql_real_escape_string($date->ID), implode(' OR ', $pool)));
				while($data->Row) {
					$productPool[] = $data->Row['Product_ID'];
					
					$data->Next();	
				}
				$data->Disconnect();
				
				$nextIndex = 0;

				for($i=0; $i<$rows; $i++) {
					$productItem = '';

					for($j=0; $j<$columns; $j++) {
						$index = ($date->IsRandomised == 'Y') ? array_rand($productPool, 1) : $nextIndex;
						$product = new Product($productPool[$index]);

						unset($productPool[$index]);

						$productItem .= sprintf('<td valign="top" width="161" height="220" style="%sfont-family: arial, sans-serif; font-size: 11px; padding: 10px 0 10px 0;">', (($j + 1) < $columns) ? 'border-right: 1px solid #e5e5e5; ' : '');
						$productItem .= sprintf('<div style="text-align: center; height: 100px; margin: 5px 0 0 0;"><a href="http://www.bltdirect.com/product.php?entity=[ENTITY]&pid=%d"><img src="%s" border="0" /></a></div>', $product->ID, (!empty($product->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$product->DefaultImage->Thumb->FileName)) ? sprintf('%s%s%s', substr($GLOBALS['HTTP_SERVER'], 0, -1), $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $product->DefaultImage->Thumb->FileName) : sprintf('%simages/template/image_coming_soon_3.jpg', $GLOBALS['HTTP_SERVER']));
						$productItem .= sprintf('<div style="text-align: left; margin: 5px 5px 0 10px;">');
						$productItem .= sprintf('<p class="title" style="margin: 0; font-size: 14px; font-weight: bold; color: #000000; padding: 0 0 5px 0;"><a style="color: #000000; text-decoration: none" href="http://www.bltdirect.com/product.php?entity=[ENTITY]&pid=%d">%s</a></p>', $product->ID, $product->Name);
						$productItem .= sprintf('<p class="rrp" style="margin: 0; color: #333333; font-size: 12px;">RRP Price: <strong><s>&pound;%s</s></strong></p>', number_format($product->PriceRRP, 2, '.', ''));
						$productItem .= sprintf('<p class="price" style="margin: 0; color: #d62c2c; font-size: 18px;">Now: <strong>&pound;%s</strong></p>', number_format(($product->PriceCurrent < $product->PriceOurs) ? $product->PriceCurrent : $product->PriceOurs, 2, '.', ''));
						$productItem .= sprintf('<p class="tax" style="margin: 0; color: #6d757b; font-size: 10px; font-family: verdana, arial, sans-serif;">Excl. VAT</p>');
						$productItem .= sprintf('</div>');
						$productItem .= sprintf('</td>');
						
						$nextIndex++;
					}

					if($i > 0) {
						$products .= sprintf('<tr><td colspan="%d"><img src="http://www.bltdirect.com/images/email/section_divider_1.gif" border="0" /></td></tr>', $columns);
					}

					$products .= sprintf('<tr>%s</tr>', $productItem);
				}
			}

			$findReplace = new FindReplace();
			$findReplace->Add('/\[EMAIL_MANAGEMENT_BANNER\]/', sprintf('<img src="http://www.bltdirect.com%s%s" border="0" />', $GLOBALS['EMAIL_BANNER_IMAGES_DIR_WS'], $banner->Image->FileName));
			$findReplace->Add('/\[EMAIL_MANAGEMENT_PRODUCTS\]/', sprintf('<table width="100%%" border="0" cellpadding="0" cellspacing="0">%s</table>', $products));
			$findReplace->Add('/\[EMAIL_MANAGEMENT_PANELS\]/', sprintf('<table width="100%%" border="0" cellpadding="0" cellspacing="0"><tr>%s</tr></table>', $panels));

			$templateHtml .= $findReplace->Execute($template->Template);
		}

		return $templateHtml;
	}

	function GetSubCategoryProducts($categoryId, $products = array()) {
		$data = new DataQuery(sprintf("SELECT p.Product_ID FROM product_in_categories AS pic INNER JOIN product AS p ON p.Product_ID=pic.Product_ID WHERE pic.Category_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			$products[] = $data->Row['Product_ID'];

			$data->Next();
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
		while($data->Row) {
			$products = array_merge($this->GetSubCategoryProducts($data->Row['Category_ID']), $products);

			$data->Next();
		}
		$data->Disconnect();

		return $products;
	}

	static function EmailTemplate($id){
		if(!is_numeric($id)){
			return false;
		}
		new DataQuery(sprintf("UPDATE email SET EmailTemplateID=0 WHERE EmailTemplateID=%d", mysql_real_escape_string($id)));
	}
}