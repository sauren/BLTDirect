<?php
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS['DIR_WS_ADMIN'] . 'lib/classes/TaxCalculator.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/XmlParser.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/XmlBuilder.php');

class GoogleBaseRequest {
	private $authKey;
	private $authenticated;
	private $key;
	private $feedItem;
	private $feedBatch;
	private $xmlRoot;
	private	$xmlData;
	private $expectedRoot;

	public function __construct() {
		$this->key = $GLOBALS['GOOGLE_BASE_API_KEY'];
		$this->feedItem = 'http://www.google.com/base/feeds/items';
		$this->feedBatch = 'http://www.google.com/base/feeds/items/batch';

		$this->authenticated = false;
	}

	public function isAuthenticated() {
		return $this->authenticated;
	}

	public function login() {
		$curlSession = curl_init();

		curl_setopt($curlSession, CURLOPT_URL, 'https://www.google.com/accounts/ClientLogin');
		curl_setopt($curlSession, CURLOPT_HEADER, 0);
		curl_setopt($curlSession, CURLOPT_POST, 1);
		curl_setopt($curlSession, CURLOPT_POSTFIELDS, 'accountType=HOSTED_OR_GOOGLE&Email=advertising@bltdirect.com&Passwd=laptop5&service=gbase&source=EllwoodElectrical-Ignition-1.0');
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlSession, CURLOPT_TIMEOUT, 60);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);

		$errorNo = curl_errno($curlSession);

		if($errorNo > 0) {
			$error = curl_error($curlSession);
		} else {
			$response = curl_exec($curlSession);
		}

		curl_close($curlSession);

		if($errorNo == 0) {
			$queryStrings = explode("\n", $response);

			foreach($queryStrings as $queryString) {
				$items = explode('=', $queryString);

				if(count($items) == 2) {
					if(stristr('auth', $items[0])) {
						$this->authKey = $items[1];
						$this->authenticated = true;

						break;
					}
				}
			}

			return true;
		}

		return false;
	}

	private function request($url, $xml) {
		$data = '<?xml version="1.0" encoding="UTF-8"?>';
		$data .= "\n";
		$data .= $xml;

		$curlSession = curl_init();

		curl_setopt($curlSession, CURLOPT_URL, $url);
		curl_setopt($curlSession, CURLOPT_HEADER, 0);
		curl_setopt($curlSession, CURLOPT_HTTPHEADER, array('Authorization: GoogleLogin auth=' . $this->authKey, 'Content-Type: application/atom+xml', 'X-Google-Key: key=' . $this->key));
		curl_setopt($curlSession, CURLOPT_POST, 1);
		curl_setopt($curlSession, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlSession, CURLOPT_TIMEOUT, 1800);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);

		$errorNo = curl_errno($curlSession);

		if($errorNo > 0) {
			$error = curl_error($curlSession);
		} else {
			$response = curl_exec($curlSession);
		}

		curl_close($curlSession);

		if($errorNo == 0) {
			if($this->parseXml($response)) {
				if(stristr($this->xmlRoot, $this->expectedRoot)) {
					return true;
				}
			}
		}

		return false;
	}

	private function parseXml($xml=null) {
		$this->xmlRoot = null;
		$this->xmlData = null;

		if(!is_null($xml) && !empty($xml)) {
			$xmlParser = new XmlParser($xml);

	        $this->xmlRoot = $xmlParser->GetRoot();
	        $this->xmlData = $xmlParser->GetData();

	        return true;
		}

		return false;
	}

	private function getXmlEntry($name) {
		$attributes = array();
		$attributes['xmlns'] = 'http://www.w3.org/2005/Atom';
		$attributes['xmlns:g'] = 'http://base.google.com/ns/1.0';
		$attributes['xmlns:batch'] = 'http://schemas.google.com/gdata/batch';
		$attributes['xmlns:openSearch'] = 'http://a9.com/-/spec/opensearchrss/1.0/';

		return new XmlElement($name, null, $attributes);
	}

	private function appendXmlProduct(XmlElement $xml, $product) {
		$price = 0;

		$data = new DataQuery(sprintf("SELECT Price_Base_Our FROM product_prices WHERE Product_ID=%d AND Price_Starts_On<=NOW() ORDER BY Price_Starts_On DESC LIMIT 0, 1", mysql_real_escape_string($product['Product_ID'])));
		if($data->TotalRows > 0) {
			$price = $data->Row['Price_Base_Our'];
		}
		$data->Disconnect();

		$data = new DataQuery(sprintf("SELECT Price_Offer FROM product_offers WHERE Product_ID=%d AND ((Offer_Start_On<=NOW() AND Offer_End_On>NOW()) AND (Offer_Start_On='000-00-00 00:00:00' AND Offer_End_On='000-00-00 00:00:00') OR (Offer_Start_On='000-00-00 00:00:00' AND Offer_End_On<NOW()) OR (Offer_Start_On>=NOW() AND Offer_End_On='000-00-00 00:00:00')) ORDER BY Price_Offer ASC LIMIT 0, 1", mysql_real_escape_string($product['Product_ID'])));
		if($data->TotalRows > 0) {
			if(($price == 0) || ($data->Row['Price_Offer'] < $price)) {
				$price = $data->Row['Price_Offer'];
			}
		}
		$data->Disconnect();

		if($price > 0) {
			$taxCalculator = new TaxCalculator($price, $GLOBALS['DEFAULT_SHIPPING_COUNTRY'], $GLOBALS['DEFAULT_SHIPPING_REGION'], $GLOBALS['DEFAULT_TAX_ON_SHIPPING']);

			$price += round($taxCalculator->GetTax($price, $GLOBALS['DEFAULT_TAX_ON_SHIPPING']), 2);
		}

		if($price > 0) {
			$xml->AddChildElement(new XmlElement('category', null, array('scheme' => 'http://www.google.com/type', 'term' => 'googlebase.item')));
			$xml->AddChildElement(new XmlElement('title', null, array('type' => 'text'), html_entity_decode(trim(strip_tags(sprintf('%s %s', $product['Product_Title'], $product['Google_Base_Suffix']))))));
			$xml->AddChildElement(new XmlElement('content', null, array('type' => 'xhtml'), !empty($product['Product_Description']) ? html_entity_decode(trim(strip_tags($product['Product_Description']))) : html_entity_decode(trim(strip_tags($product['Product_Blurb'])))));
			$xml->AddChildElement(new XmlElement('link', null, array('rel' => 'alternate', 'type' => 'text/html', 'href' => sprintf('%sproduct.php?pid=%d', $GLOBALS['HTTP_SERVER'], $product['Product_ID']))));
			$xml->AddChildElement(new XmlElement('g:item_type', null, array('type' => 'text'), 'Products'));
			$xml->AddChildElement(new XmlElement('g:item_language', null, array('type' => 'text'), 'en'));
			$xml->AddChildElement(new XmlElement('g:target_country', null, array('type' => 'text'), 'gb'));
			$xml->AddChildElement(new XmlElement('g:id', null, array('type' => 'text'), $product['Product_ID']));
			$xml->AddChildElement(new XmlElement('g:product_type', null, array('type' => 'text'), 'light bulbs'));
			$xml->AddChildElement(new XmlElement('g:price', null, array('type' => 'floatunit'), sprintf('%s gbp', number_format($price, 2, '.', ''))));
			$xml->AddChildElement(new XmlElement('g:quantity', null, array('type' => 'int'), '1'));
			$xml->AddChildElement(new XmlElement('g:condition', null, array('type' => 'text'), 'new'));

			$data = new DataQuery(sprintf("SELECT Image_Src FROM product_images WHERE Product_ID=%d AND Is_Active='Y' ORDER BY Is_Primary ASC LIMIT 0, 1", mysql_real_escape_string($product['Product_ID'])));
			if($data->TotalRows > 0) {
				if(!empty($data->Row['Image_Src']) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'] . $data->Row['Image_Src'])) {
					$xml->AddChildElement(new XmlElement('g:image_link', null, array('type' => 'url'), sprintf('%s%s%s', substr($GLOBALS['HTTP_SERVER'], 0, -1), $GLOBALS['PRODUCT_IMAGES_DIR_WS'], $data->Row['Image_Src'])));
				}
			}
			$data->Disconnect();

			if(!empty($product['Brand'])) {
				$xml->AddChildElement(new XmlElement('g:brand', null, array('type' => 'text'), htmlentities($product['Brand'])));
			} elseif(!empty($product['Manufacturer_Name'])) {
				$xml->AddChildElement(new XmlElement('g:brand', null, array('type' => 'text'), htmlentities($product['Manufacturer_Name'])));
			}
			
			if(!empty($product['Barcode'])) {
				$xml->AddChildElement(new XmlElement('g:ean', null, array('type' => 'text'), $product['Barcode']));
			}

			if($product['Weight'] > 0) {
				$xml->AddChildElement(new XmlElement('g:weight', null, array('type' => 'numberunit'), sprintf('%d kg', $product['Weight'])));
			}

			$data = new DataQuery(sprintf("SELECT psg.Name, psg.Units, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID INNER JOIN product_specification_group AS psg ON psv.Group_ID=psg.Group_ID WHERE ps.Product_ID=%d", mysql_real_escape_string($product['Product_ID'])));
			while($data->Row) {
				$specTitle = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $data->Row['Name']))));
				$specValue = htmlentities(trim($data->Row['Value']));

				if((strlen($specTitle) > 0) && (strlen($specValue) > 0)) {
					if(is_numeric(substr($specTitle, 0, 1))) {
						$specTitle = sprintf('spec_%s', $specTitle);
					}

					$xml->AddChildElement(new XmlElement(sprintf('g:%s', $specTitle), null, array('type' => is_numeric($specValue) ? (!empty($data->Row['Units']) ? 'numberunit' : 'number') : 'text'), $specValue));
				}

				$data->Next();
			}
			$data->Disconnect();

			return true;
		}

		return false;
	}

	public function insertItem($productId) {
		$this->expectedRoot = 'entry';

		$product = new Product();

		if($product->Get($productId)) {
			$xmlAuthor = new XmlElement('author');
			$xmlAuthor->AddChildElement(new XmlElement('name', null, null, $GLOBALS['COMPANY']));
			$xmlAuthor->AddChildElement(new XmlElement('email', null, null, $GLOBALS['EMAIL_SALES']));

			$xml = $this->getXmlEntry('entry');
			$xml->AddChildElement($xmlAuthor);

			$row = array(	'Product_ID' => $product->ID,
							'Product_Title' => $product->Name,
							'Product_Blurb' => $product->Blurb,
							'Product_Description' => $product->Description,
							'Weight' => $product->Weight,
							'Google_Base_Suffix' => $product->GoogleBaseSuffix,
							'Manufacturer_Name' => $product->Manufacturer->Name,
							'Barcode' => '',
							'Brand' => '');
							
			$data = new DataQuery(sprintf("SELECT Barcode, Brand FROM product_barcode WHERE Quantity=1 AND ProductID=%d LIMIT 0, 1", mysql_real_escape_string($product->ID)));
			if($data->TotalRows > 0) {
				$row['Barcode'] = $data->Row['Barcode'];
				$row['Brand'] = $data->Row['Brand'];
			}
			$data->Disconnect();
			
			if($this->appendXmlProduct($xml, $row)) {
				return $this->request($this->feedItem, $xml->ToString());
			}
		}

		return false;
	}

	public function insertBatchItems($start = 0, $limit = 100) {
		$this->expectedRoot = 'atom';

		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.Product_Blurb, p.Product_Description, p.Weight, p.Google_Base_Suffix, m.Manufacturer_Name, pb.Barcode, pb.Brand FROM product AS p LEFT JOIN manufacturer AS m ON p.Manufacturer_ID=m.Manufacturer_ID LEFT JOIN product_barcode AS pb ON pb.ProductID=p.Product_ID AND pb.Quantity=1 WHERE p.Is_Active='Y' AND p.Is_Demo_Product='N' AND p.Discontinued='N' AND p.Is_Complementary='N' GROUP BY p.Product_ID ORDER BY p.Product_ID ASC LIMIT %d, %d", mysql_real_escape_string($start), mysql_real_escape_string($limit)));
		if($data->TotalRows > 0) {
			$xml = $this->getXmlEntry('feed');
			$xml->AddChildElement(new XmlElement('title', null, array('type' => 'text'), 'Batch Insert'));

			$xmlString = str_replace('</feed>', '', $xml->ToString());

			while($data->Row) {
				$xml = new XmlElement('entry');
				$xml->AddChildElement(new XmlElement('batch:id', null, null, $data->Row['Product_ID']));
				$xml->AddChildElement(new XmlElement('batch:operation', null, array('type' => 'insert')));

				if($this->appendXmlProduct($xml, $data->Row)) {
					$xmlString .= $xml->ToString();
				}

				$data->Next();
			}

			$xmlString .= '</feed>';

			return $this->request($this->feedBatch, $xmlString);
		}
		$data->Disconnect();

		return false;
	}
}