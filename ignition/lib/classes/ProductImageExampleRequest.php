<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Asset.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Customer.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/EmailQueue.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/FindReplace.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductImageExample.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Template.php');

class ProductImageExampleRequest {
	var $id;
	var $product;
	var $customer;
	var $asset;
	var $createdOn;

	public function __construct($id=NULL) {
		$this->product = new Product();
		$this->customer = new Customer();
		$this->asset = new Asset();
	
		if(isset($id)){
			$this->id = $id;
			$this->get();
		}
	}

	public function get($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		if(!is_numeric($this->ID)){
			return false;
		}

		$data = new DataQuery(sprintf("SELECT * FROM product_image_example_request WHERE id=%d", mysql_real_escape_string($this->id)));
		if($data->Row) {
			$this->product->ID = $data->Row['productId'];
			$this->customer->ID = $data->Row['customerId'];
			$this->asset->id = $data->Row['assetId'];
			$this->createdOn = $data->Row['createdOn'];

			$data->Disconnect();
			return true;
		}
		$data->Disconnect();
		return false;
	}
	
	public function add() {
		$data = new DataQuery(sprintf("INSERT INTO product_image_example_request (productId, customerId, assetId, createdOn) VALUES (%d, %d, %d, NOW())", mysql_real_escape_string($this->product->ID), mysql_real_escape_string($this->customer->ID), mysql_real_escape_string($this->asset->id)));

		$this->id = $data->InsertID;
	}

	public function delete($id = null) {
		if(!is_null($id)) {
			$this->id = $id;
		}
		
		if(empty($this->asset->id)) {
			$this->get();
		}

		if(!is_numeric($this->ID)){
			return false;
		}

		new DataQuery(sprintf("DELETE FROM product_image_example_request WHERE id=%d", mysql_real_escape_string($this->id)));
		
		$this->asset->clean();
	}
	
	public function approve() {
		$this->product->Get();
		$this->asset->Get();
		
		$example = new ProductImageExample();
		$example->ParentID = $this->product->ID;
		$example->Customer->ID = $this->customer->ID;
		$example->Name = $this->product->Name;
		
		$example->Large->CreateUniqueName($this->asset->name);
		$example->Large->Create($this->asset->data);
		
		if(!$example->Large->CheckDimensions()) {
			$example->Large->Resize();
		}
		
		$tempFileName = $example->Large->Name . '_thumb.' . $example->Large->Extension;
		
		$example->Large->Copy($example->Thumb->Directory, $tempFileName);
		
		$example->Thumb->SetName($tempFileName);
		$example->Thumb->Width = $example->Large->Width;
		$example->Thumb->Height = $example->Large->Height;
		
		if(!$example->Thumb->CheckDimensions()){
			$example->Thumb->Resize();
		}
		
		$example->Add();
		
		$this->delete();
		
		$coupon = new Coupon();
		
		if($coupon->Get(91029)) {
			$this->customer->Get();
			$this->customer->Contact->Get();
			
			$findReplace = new FindReplace();
			$findReplace->Add('/\[COUPON_REFERENCE\]/', $coupon->Reference);
			$findReplace->Add('/\[COUPON_DISCOUNT\]/', $coupon->Discount);

			$html = $findReplace->Execute(Template::GetContent('email_discount_example'));

			$findReplace = new FindReplace();
			$findReplace->Add('/\[NAME\]/', $this->customer->Contact->Person->GetFullName());
			$findReplace->Add('/\[BODY\]/', $html);

			$html = $findReplace->Execute(Template::GetContent('email_template_standard'));
			
			$queue = new EmailQueue();
			$queue->GetModuleID('discounts');
			$queue->Priority = 'H';
			$queue->ToAddress = $this->customer->GetEmail();
			$queue->Subject = sprintf('%s - Example Approved Discount (%s)', $GLOBALS['COMPANY'], $coupon->Reference);
			$queue->Body = $html;
			$queue->Add();
		}
	}
}