<?php
require_once('lib/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiry.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquiryLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquirySupplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/PriceEnquirySupplierLine.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProductPrice.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/SupplierProduct.php');

function getCsv($row, $fd=',', $quot='"') {
	$str ='';

	foreach($row as $cell) {
		$cell = str_replace($quot, $quot.$quot, $cell);

		if((strchr($cell, $fd) !== false) || (strchr($cell, $quot) !== false) || (strchr($cell, "\n") !== false)) {
			$str .= $quot.$cell.$quot.$fd;
		} else {
			$str .= $quot.$cell.$quot.$fd;
		}
	}

	return substr($str, 0, -1)."\n";
}

$session->Secure(3);

$supplierId = $session->Warehouse->Contact->ID;

$priceEnquiry = new PriceEnquiry($_REQUEST['id']);
$priceEnquiry->GetLines();
$priceEnquiry->GetSuppliers();
$priceEnquiry->GetQuantities();

$isEditable = (strtolower($priceEnquiry->Status) != 'complete') ? true : false;

$supplierPriceEnquiry = new PriceEnquirySupplier();

if(!$supplierPriceEnquiry->GetByEnquiryAndSupplierID($priceEnquiry->ID, $supplierId)) {
	redirect(sprintf("Location: price_enquiries_pending.php"));
}

$supplierPriceEnquiry->Supplier->Get();
$supplierPriceEnquiry->GetLines();
$supplierPriceEnquiry->GetCosts();

if($action == "complete") {
	$supplierPriceEnquiry->IsComplete = 'Y';
	$supplierPriceEnquiry->Update();

	redirect(sprintf("Location: %s?id=%d&supplier=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID, $supplierPriceEnquiry->Supplier->ID));
}

$quantities = array(1);

for($i=0; $i<count($priceEnquiry->Quantity); $i++) {
	$quantities[] = $priceEnquiry->Quantity[$i]->Quantity;
}

$lineCache = array();

for($i=0; $i<count($supplierPriceEnquiry->Line); $i++) {
	$lineData = array();
	$lineData['IsInStock'] = $supplierPriceEnquiry->Line[$i]->IsInStock;
	$lineData['StockBackorderDays'] = $supplierPriceEnquiry->Line[$i]->StockBackorderDays;

	$lineCache[$supplierPriceEnquiry->Line[$i]->PriceEnquiryLineID] = $lineData;
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Price Enquiry ID', 'hidden', $priceEnquiry->ID, 'numeric_unsigned', 1, 11);
$form->AddField('supplier', 'Supplier ID', 'hidden', '0', 'numeric_unsigned', 1, 11);

$supplierProducts = array();

if($isEditable) {
	for($k=0; $k<count($priceEnquiry->Line); $k++) {
		foreach($quantities as $quantity) {
			$cost = '';

			for($i=0; $i<count($supplierPriceEnquiry->Cost[$k]['Items']); $i++) {
				$item = $supplierPriceEnquiry->Cost[$k]['Items'][$i];

				if($item['Quantity'] == $quantity) {
					if($item['Cost'] > 0) {
						$cost = $item['Cost'];
					}

					break;
				}
			}

			$form->AddField(sprintf('cost_%d_%d', $priceEnquiry->Line[$k]->ID, $quantity), sprintf('Cost for \'%dx\' of \'%s\'', $quantity, $priceEnquiry->Line[$k]->Product->Name), 'text', $cost, 'float', 1, 11, false, 'size="5"');
		}

		$supplierProducts[$priceEnquiry->Line[$k]->ID] = array('SupplierProductID' => 0, 'SupplierProductNumber' => 0, 'SupplierSKU' => '');

	    $data = new DataQuery(sprintf("SELECT Supplier_Product_ID, Supplier_Product_Number, Supplier_SKU FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $supplierPriceEnquiry->Supplier->ID, $priceEnquiry->Line[$k]->Product->ID));
		if($data->TotalRows > 0) {
			$supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierProductID'] = $data->Row['Supplier_Product_ID'];
			$supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierProductNumber'] = $data->Row['Supplier_Product_Number'];
			$supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierSKU'] = $data->Row['Supplier_SKU'];
		}
		$data->Disconnect();

		$form->AddField(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID), sprintf('Is In Stock for \'%s\'', $priceEnquiry->Line[$k]->Product->Name), 'radio', isset($lineCache[$priceEnquiry->Line[$k]->ID]['IsInStock']) ? $lineCache[$priceEnquiry->Line[$k]->ID]['IsInStock'] : 'U', 'alpha', 1, 1);
		$form->AddOption(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID), 'N', 'N');
		$form->AddOption(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID), 'Y', 'Y');
		$form->AddField(sprintf('stockbackorder_%d', $priceEnquiry->Line[$k]->ID), sprintf('Backorder Stock (Days) for \'%s\'', $priceEnquiry->Line[$k]->Product->Name), 'text', isset($lineCache[$priceEnquiry->Line[$k]->ID]['StockBackorderDays']) ? $lineCache[$priceEnquiry->Line[$k]->ID]['StockBackorderDays'] : 0, 'numeric_unsigned', 1, 11, true, 'size="3"');
		$form->AddField(sprintf('productnumber_%d', $priceEnquiry->Line[$k]->ID), sprintf('Supplier Product Number for \'%s\'', $priceEnquiry->Line[$k]->Product->Name), ($supplierPriceEnquiry->Supplier->ShowProduct == 'N') ? 'hidden' : 'text', $supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierProductNumber'], 'numeric_unsigned', 1, 11, true, 'size="10"');
		$form->AddField(sprintf('sku_%d', $priceEnquiry->Line[$k]->ID), sprintf('Supplier SKU for \'%s\'', $priceEnquiry->Line[$k]->Product->Name), 'text', $supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierSKU'], 'anything', 1, 30, false, 'size="10"');
	}
}

if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
    if(isset($_REQUEST['exportproducts'])) {
        $fileDate = getDatetime();
		$fileDate = substr($fileDate, 0, strpos($fileDate, ' '));

		$fileName = sprintf('blt_price_enquiry_%d_%s.csv', $priceEnquiry->ID, $fileDate);

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename=" . basename($fileName) . ";");
		header("Content-Transfer-Encoding: binary");

		$line = array();
		$line[] = 'Product ID';
		$line[] = 'Product Name';

		echo getCsv($line);

		for($i=0; $i<count($priceEnquiry->Line); $i++) {
		$line = array();
			$line[] = $priceEnquiry->Line[$i]->Product->ID;
			$line[] = $priceEnquiry->Line[$i]->Product->Name;

			echo getCsv($line);
		}

		exit;

	} elseif(isset($_REQUEST['updateproducts'])) {
		if($form->Validate()) {
			for($k=0; $k<count($priceEnquiry->Line); $k++) {
				foreach($quantities as $quantity) {
					$cost = '';
					$quantityId = 0;

					for($i=0; $i<count($supplierPriceEnquiry->Cost[$k]['Items']); $i++) {
						$item = $supplierPriceEnquiry->Cost[$k]['Items'][$i];

						if($item['Quantity'] == $quantity) {
							if($item['Cost'] > 0) {
								$cost = $item['Cost'];
							}

							$quantityId = $item['Supplier_Product_Price_ID'];

							break;
						}
					}

					$newCost = $form->GetValue(sprintf('cost_%d_%d', $priceEnquiry->Line[$k]->ID, $quantity));

					if($cost != $newCost) {
						if($quantity > 1) {
							if($quantityId > 0) {
								$price = new SupplierProductPrice($quantityId);

								if($newCost <> $price->Cost) {
									$price->Cost = $newCost;
									$price->Add();
								}
							} else {
								$price = new SupplierProductPrice();
								$price->Supplier->ID = $supplierPriceEnquiry->Supplier->ID;
								$price->Product->ID = $priceEnquiry->Line[$k]->Product->ID;
								$price->Quantity = $quantity;
								$price->Cost = $newCost;
								$price->Add();
							}
						} else {
							$data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $supplierPriceEnquiry->Supplier->ID, $priceEnquiry->Line[$k]->Product->ID));
							if($data->TotalRows > 0) {
								$product = new SupplierProduct($data->Row['Supplier_Product_ID']);
								$product->Cost = $newCost;
								$product->Update();
							} else {
								if($newCost > 0) {
									$product = new SupplierProduct();
									$product->Supplier->ID = $supplierPriceEnquiry->Supplier->ID;
									$product->Product->ID = $priceEnquiry->Line[$k]->Product->ID;
									$product->Cost = $newCost;
									$product->Add();
								}
							}
							$data->Disconnect();
						}
					}
				}

				$found = false;

                $data = new DataQuery(sprintf("SELECT Supplier_Product_ID FROM supplier_product WHERE Supplier_ID=%d AND Product_ID=%d", $supplierPriceEnquiry->Supplier->ID, $priceEnquiry->Line[$k]->Product->ID));
				if($data->TotalRows > 0) {
					$product = new SupplierProduct($data->Row['Supplier_Product_ID']);
					$product->SupplierProductNumber = $form->GetValue(sprintf('productnumber_%d', $priceEnquiry->Line[$k]->ID));
					$product->SKU = $form->GetValue(sprintf('sku_%d', $priceEnquiry->Line[$k]->ID));
					$product->Update();
				} else {
					$product = new SupplierProduct();
					$product->Supplier->ID = $supplierPriceEnquiry->Supplier->ID;
					$product->Product->ID = $priceEnquiry->Line[$k]->Product->ID;
                    $product->SupplierProductNumber = $form->GetValue(sprintf('productnumber_%d', $priceEnquiry->Line[$k]->ID));
					$product->SKU = $form->GetValue(sprintf('sku_%d', $priceEnquiry->Line[$k]->ID));
					$product->Add();
				}
				$data->Disconnect();

				for($i=0; $i<count($supplierPriceEnquiry->Line); $i++) {
					if($priceEnquiry->Line[$k]->ID == $supplierPriceEnquiry->Line[$i]->PriceEnquiryLineID) {
						$line = new PriceEnquirySupplierLine($supplierPriceEnquiry->Line[$i]->ID);
						$line->IsInStock = $form->GetValue(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID));
						$line->StockBackorderDays = $form->GetValue(sprintf('stockbackorder_%d', $priceEnquiry->Line[$k]->ID));
						$line->Update();

						$found = true;
						break;
					}
				}

				if(!$found) {
					$line = new PriceEnquirySupplierLine();
					$line->PriceEnquirySupplierID = $supplierPriceEnquiry->ID;
					$line->PriceEnquiryLineID = $priceEnquiry->Line[$k]->ID;
					$line->IsInStock = $form->GetValue(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID));
					$line->StockBackorderDays = $form->GetValue(sprintf('stockbackorder_%d', $priceEnquiry->Line[$k]->ID));
					$line->Add();
				}
			}

			redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $priceEnquiry->ID));
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><!-- InstanceBegin template="/templates/portal-warehouse.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Warehouse Portal</title>
	<!-- InstanceEndEditable -->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="/ignition/css/i_content.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="/warehouse/css/lightbulbs.css" rel="stylesheet" type="text/css" media="screen" />
	<link href="/warehouse/css/lightbulbs_print.css" rel="stylesheet" type="text/css" media="print" />
	<link href="/warehouse/css/default.css" rel="stylesheet" type="text/css" media="screen" />
	<script language="javascript" src="/warehouse/js/generic_1.js" type="text/javascript"></script>
    <script language="javascript" type="text/javascript">
	var toggleGroup = function(group) {
		var e = document.getElementById(group);
		if(e) {
			if(e.style.display == 'none') {
				e.style.display = 'block';
			} else {
				e.style.display = 'none';
			}
		}
	}
	</script>
    <!-- InstanceBeginEditable name="head" -->
	<!-- InstanceEndEditable -->
</head>
<body>
<div id="Wrapper">
	<div id="Header">
		<a href="/warehouse" title="Back to Home Page"><img src="/images/template/logo_blt_1.jpg" width="185" height="70" border="0" class="logo" alt="BLT Direct Logo" /></a>
		<div id="NavBar" class="warehouse">Warehouse Portal</div>
		<div id="CapTop" class="warehouse">
			<div class="curveLeft"></div>
		</div>
		<ul id="NavTop" class="nav warehouse">
			<?php if($session->IsLoggedIn){
				echo sprintf('<li class="login"><a href="%s?action=logout" title="Logout">Logout</a></li>', $_SERVER['PHP_SELF']);
			} else {
				echo '<li class="login"><a href="/index.php" title="Login as a BLT Direct supplier or warehouse">Login</a></li>';
			}?>
			<li class="account"><a href="/warehouse/account_settings.php" title="Your BLT Direct Account">My Account</a></li>
			<li class="contact"><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
			<li class="help"><a href="/support.php" title="Light Bulb, Lamp and Tube Help">Help</a></li>
		</ul>
	</div>

<div id="PageWrapper">
	<div id="Page">
		<div id="PageContent"><!-- InstanceBeginEditable name="pageContent" -->
			<h1>Price Enquiry Details</h1>
			<br />

			<?php
			if(!$form->Valid){
				echo $form->GetError();
				echo '<br />';
			}

			echo $form->Open();
			echo $form->GetHTML('confirm');
			echo $form->GetHTML('id');
			echo $form->GetHTML('supplier');

			for($k=0; $k<count($priceEnquiry->Line); $k++) {
				if($supplierPriceEnquiry->Supplier->ShowProduct == 'N') {
					echo $form->GetHTML(sprintf('productnumber_%d', $priceEnquiry->Line[$k]->ID));
				}
			}
			?>

			<table width="100%" border="0" cellspacing="0" cellpadding="0">
			  <tr>
			    <td align="left" valign="top"></td>
			    <td align="right" valign="top">

			    <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
			      <tr>
			        <th>Price Enquiry:</th>
			        <td>#<?php echo $priceEnquiry->ID; ?></td>
			      </tr>
			      <tr>
			        <th>Status:</th>
			        <td><?php echo $priceEnquiry->Status; ?></td>
			      </tr>
			      <tr>
			        <th>&nbsp;</th>
			        <td>&nbsp;</td>
			      </tr>
			      <tr>
			        <th>Supplier:</th>
			        <td>
			        	<?php
						$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name FROM supplier AS s INNER JOIN contact AS c ON c.Contact_ID=s.Contact_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON o.Org_ID=c2.Org_ID INNER JOIN person AS p ON p.Person_ID=c.Person_ID WHERE s.Supplier_ID=%d", $supplierId));
						echo ($data->TotalRows > 0) ? $data->Row['Supplier_Name'] : '&nbsp;';
						$data->Disconnect();
			        	?>
			        </td>
			      </tr>
			      <tr>
			        <th>Position:</th>
			        <td><?php echo ($supplierPriceEnquiry->Position > 0) ? sprintf('#%d', $supplierPriceEnquiry->Position) : '<em>Undisclosed</em>'; ?></td>
			      </tr>
			      <tr>
			        <th>Prices Complete:</th>
			        <td><?php echo ($supplierPriceEnquiry->IsComplete == 'N') ? 'No' : 'Yes'; ?></td>
			      </tr>
			      <tr>
			        <th>&nbsp;</th>
			        <td>&nbsp;</td>
			      </tr>
			      <tr>
			        <th>Created On:</th>
			        <td><?php echo cDatetime($priceEnquiry->CreatedOn, 'shortdate'); ?></td>
			      </tr>
			    </table><br />

			   </td>
			  </tr>
			  <tr>
			    <td colspan="2">
			    	<br />

					<?php
					if($isEditable) {
						if($supplierPriceEnquiry->IsComplete == 'N') {
							?>

							<div style="background-color: #eee; padding: 10px 0 10px 0;">
					 			<p><span class="pageSubTitle">Finished?</span><br /><span class="pageDescription">Mark pricing for your price enquiry complete by clicking the below button.</span></p>

			    				<input name="complete" type="button" value="complete" class="submit" onclick="confirmRequest('price_enquiry_details.php?id=<?php echo $priceEnquiry->ID; ?>&supplier=<?php echo $supplierPriceEnquiry->Supplier->ID; ?>&action=complete', 'Please confirm you wish to mark these prices as complete?');" />
			    			</div><br />

							<?php
						}
					}
					?>

					<div style="background-color: #eee; padding: 10px 0 10px 0;">
					 	<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing product discounts and special nets for the quantities requested.</span></p>

                        <?php
		 				$columns = 6;
		 				?>

					 	<table cellspacing="0" class="orderDetails">
							<tr>
								<th nowrap="nowrap" style="padding-right: 5px;">Quantity<br />&nbsp;</th>
								<th nowrap="nowrap" style="padding-right: 5px;">Quickfind<br />&nbsp;</th>
					      		<th nowrap="nowrap" style="padding-right: 5px;">Name<br />&nbsp;</th>

					      		<?php
					      		foreach($quantities as $quantity) {
					      			$columns++;

					      			echo sprintf('<th nowrap="nowrap" style="padding-right: 5px; text-align: right;">%dx<br />&nbsp;</th>', $quantity);
					      		}
					      		?>

					      		<th nowrap="nowrap" style="padding-right: 5px; padding-left: 5px; text-align: center;">Is In Stock<br />Y / N</th>
						      	<th nowrap="nowrap" style="padding-right:5px;">Backorder Stock<br />(Days)</th>

                                <?php
			      				if($supplierPriceEnquiry->Supplier->ShowProduct == 'Y') {
			      					$columns++;

			      					echo '<th nowrap="nowrap" style="padding-right:5px;">Product Number<br />&nbsp;</th>';
								}
								?>

								<th nowrap="nowrap" style="padding-right:5px;">Supplier<br />SKU</th>
							</tr>

							<?php
							if(count($priceEnquiry->Line) > 0) {
								for($k=0; $k<count($priceEnquiry->Line); $k++) {
									switch(strtoupper(isset($lineCache[$priceEnquiry->Line[$k]->ID]['IsInStock']) ? $lineCache[$priceEnquiry->Line[$k]->ID]['IsInStock'] : 'U')) {
										case 'Y':
											$isInStock = 'Yes';
											break;
										case 'N':
											$isInStock = 'No';
											break;
										default:
											$isInStock = 'Unknown';
											break;
									}
									?>

									<tr>
							      		<td nowrap="nowrap"><?php echo $priceEnquiry->Line[$k]->Quantity; ?></td>
							      		<td nowrap="nowrap"><?php echo $priceEnquiry->Line[$k]->Product->ID; ?></td>
							      		<td nowrap="nowrap"><?php echo $priceEnquiry->Line[$k]->Product->Name; ?></td>

										<?php
							      		foreach($quantities as $quantity) {
							      			$cost = '';

											for($i=0; $i<count($supplierPriceEnquiry->Cost[$k]['Items']); $i++) {
												$item = $supplierPriceEnquiry->Cost[$k]['Items'][$i];

												if($item['Quantity'] == $quantity) {
													if($item['Cost'] > 0) {
														$cost = $item['Cost'];
													}

													break;
												}
											}

							      			echo sprintf('<td nowrap="nowrap" align="right">%s</td>', ($isEditable) ? sprintf('&pound;%s', $form->GetHTML(sprintf('cost_%d_%d', $priceEnquiry->Line[$k]->ID, $quantity))) : (!empty($cost) ? sprintf('&pound;%s', $cost) : '-'));
							      		}
							      		?>

							      		<td nowrap="nowrap" align="center"><?php echo ($isEditable) ? sprintf('%s %s', $form->GetHTML(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID), 2), $form->GetHTML(sprintf('isstocked_%d', $priceEnquiry->Line[$k]->ID), 1)) : $isInStock; ?></td>
							      		<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('stockbackorder_%d', $priceEnquiry->Line[$k]->ID)) : (isset($lineCache[$priceEnquiry->Line[$k]->ID]['StockBackorderDays']) ? $lineCache[$priceEnquiry->Line[$k]->ID]['StockBackorderDays'] : 0); ?></td>

                                        <?php
			      						if($supplierPriceEnquiry->Supplier->ShowProduct == 'Y') {
			      							?>

				      						<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('productnumber_%d', $priceEnquiry->Line[$k]->ID)) : (($supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierProductNumber'] > 0) ? $supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierProductNumber'] : '-'); ?></td>

				      						<?php
										}
										?>

										<td nowrap="nowrap"><?php echo ($isEditable) ? $form->GetHTML(sprintf('sku_%d', $priceEnquiry->Line[$k]->ID)) : $supplierProducts[$priceEnquiry->Line[$k]->ID]['SupplierSKU']; ?>&nbsp;</td>
									</tr>

									<?php
								}
							} else {
						      	?>

						      	<tr>
									<td colspan="<?php echo $columns; ?>" align="center">No products available for viewing.</td>
						      	</tr>

						      	<?php
							}
							?>
					    </table><br />

						<?php
						if($isEditable) {
							?>

							<table cellspacing="0" cellpadding="0" border="0" width="100%">
								<tr>
									<td align="left">
										<input type="submit" name="updateproducts" value="update" class="greySubmit" />
									</td>
									<td align="right">
                                        <input type="submit" name="exportproducts" value="export" class="greySubmit" />
									</td>
								</tr>
							</table>

							<?php
						}
						?>

					</div>

			    </td>
			  </tr>
			</table>

			<?php
			echo $form->Close();
			?>

			<!-- InstanceEndEditable -->
		</div>
  	</div>

	<div id="PageFooter">
		<ul class="links">
			<li><a href="/privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
			<li><a href="/support.php" title="Contact BLT Direct">Contact Us</a></li>
		</ul>
		<p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
	</div>
</div>

	<div id="LeftNav">
		<div id="CatalogueNav" class="greyNavLeft">
			<div id="NavLeftItems" class="warehouse">
			<p class="title"><strong>Warehouse Options </strong> </p>

			<ul class="rootCat">
				<?php
				if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N'))) {
					?>
					<li><a href="/warehouse/orders_pending.php">Pending Orders</a></li>
                    
                    <?php
					if($session->Warehouse->Type == 'B') {
						echo '<li><a href="/warehouse/orders_pending_tax_free.php">Pending Orders<br />(Tax Free)</a></li>';
					}
					?>
                    
					<li><a href="/warehouse/orders_collections.php">Collection Orders</a></li>
					<li><a href="/warehouse/orders_backordered.php">Backordered Orders</a></li>
                    
                    <?php
					if(($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsBidder == 'Y')) {
						echo '<li><a href="/warehouse/orders_bidding.php">Bidding Orders</a></li>';
					}
					
					if($session->Warehouse->Type == 'S') {
						echo '<li><a href="/warehouse/orders_warehouse_declined.php">Warehouse Declined Orders</a></li>';
					}
					?>
                    
					<li><a href="/warehouse/orders_despatched.php">Despatched Orders</a></li>
					<li><a href="/warehouse/orders_search.php">Search Orders</a></li>
					<?php
				}

				if($session->Warehouse->Type == 'B') {
					?>
					<li><a href="/warehouse/orders_auto_despatch.php">Auto Despatch Orders</a></li>
					<?php
				}

				if(($session->Warehouse->Type == 'B') || (($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N'))) {
					?>

					<li><a href="/warehouse/despatches_track.php">Track Consignments</a></li>
					<li><a href="/warehouse/products_stocked.php">Stocked Products</a></li>

					<?php
				}
				?>
                
                <li><a href="/warehouse/products_backordered.php">Products Backordered</a></li>
                
                <?php
				if(($session->Warehouse->Type == 'S') && ($session->Warehouse->Contact->IsStockerOnly == 'N')) {
					?>

					<li><a href="/warehouse/products_held.php">Products Held</a></li>
					<li><a href="/warehouse/products_supplied.php">Products Supplied</a></li>

					<?php
					$supplier = new Supplier($session->Warehouse->Contact->ID);

					if($supplier->IsComparable == 'Y') {
						?>

						<li><a href="/warehouse/products_unsupplied.php">Unsupplied Products</a></li>

						<?php
					}
				}

				if($session->Warehouse->Type == 'B') {
					?>

					<li><a href="/warehouse/timesheets.php">Timesheets</a></li>

					<li><a href="javascript:toggleGroup('navLabels');" target="_self">Labels</a></li>
                    <ul id="navLabels" class="subCat" style="display: none;">
                        <li><a href="/warehouse/downloads/2nd-class-stamp.pdf" target="_blank">2<sup>nd</sup> Class Stamps</a></li>
                    </ul>
                    
					<?php
				}

				if($session->Warehouse->Type == 'S') {
					?>
					
					<li><a href="javascript:toggleGroup('navReserves');" target="_self">Reserves</a></li>
					<ul id="navReserves" class="subCat" style="display: none;">
						<li><a href="/warehouse/reserves_pending.php">Pending</a></li>
						<li><a href="/warehouse/reserves_completed.php">Completed</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navPriceEnquiries');" target="_self">Price Enquiries</a></li>
					<ul id="navPriceEnquiries" class="subCat" style="display: none;">
						<li><a href="/warehouse/price_enquiries_pending.php">Pending</a></li>
						<li><a href="/warehouse/price_enquiries_completed.php">Completed</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navPurchaseRequests');" target="_self">Purchase Requests</a></li>
					<ul id="navPurchaseRequests" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/purchase_requests_pending.php">Pending</a></li>
                    	<li><a href="/warehouse/purchase_requests_confirmed.php">Confirmed</a></li>
                    	<li><a href="/warehouse/purchase_requests_completed.php">Completed</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navPurchaseOrders');" target="_self">Purchase Orders</a></li>
					<ul id="navPurchaseOrders" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/purchase_orders_unfulfilled.php">Unfulfilled</a></li>
                    	<li><a href="/warehouse/purchase_orders_fulfilled.php">Fulfilled</a></li>
					</ul>

					<li><a href="javascript:toggleGroup('navReturnRequests');" target="_self">Return Requests</a></li>
					<ul id="navReturnRequests" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/supplier_return_requests_pending.php">Pending</a></li>
                    	<li><a href="/warehouse/supplier_return_requests_confirmed.php">Confirmed</a></li>
                    	<li><a href="/warehouse/supplier_return_requests_completed.php">Completed</a></li>
					</ul>
                    
					<li><a href="/warehouse/supplier_return_requests_pending_purchase.php">Damages</a></li>
                    
                    <li><a href="javascript:toggleGroup('navInvoiceQueries');" target="_self">Invoice Queries</a></li>
                    <ul id="navInvoiceQueries" class="subCat" style="display: none;">
                        <li><a href="/warehouse/supplier_invoice_queries_pending.php">Pending</a></li>
                        <li><a href="/warehouse/supplier_invoice_queries_resolved.php">Resolved</a></li>
                    </ul>
                        
                    <li><a href="javascript:toggleGroup('navDebits');" target="_self">Debits</a></li>
					<ul id="navDebits" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/debits_pending.php">Pending</a></li>
                    	<li><a href="/warehouse/debits_completed.php">Completed</a></li>
					</ul>
                    
                    <li><a href="javascript:toggleGroup('navReports');" target="_self">Reports</a></li>
					<ul id="navReports" class="subCat" style="display: none;">
                    	<li><a href="/warehouse/report_orders_despatched.php">Orders Despatched</a></li>
						<li><a href="/warehouse/report_reserved_stock.php">Reserved Stock</a></li>
						<li><a href="/warehouse/report_stock_dropped.php">Stock Dropped</a></li>
					</ul>

					<?php
				}
				?>
				
				<li><a href="/warehouse/account_settings.php">Account Settings</a></li>
			</ul>
			</div>
			<div class="cap"></div>
			<div class="shadow"></div>
		</div>
	</div>
</div>
</body>
<!-- InstanceEnd --></html>