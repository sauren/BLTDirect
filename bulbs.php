<?php
require_once('lib/common/appHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProduct.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductGroup.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductGroupItem.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerProductLocation.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/CustomerLocation.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

$session->Secure();

// improved error checking on tab selection
$tabs = array('bulbs', 'groups');
$tab = param('tab', 'bulbs');
if(!in_array($tab, $tabs)) $tab = 'bulbs';

if(!isset($_SESSION['preferences']['bulbs']['group'])) {
	$_SESSION['preferences']['bulbs']['group'] = 0;
}

if($action == 'remove') {
	if(id_param('location')) {
		$location = new CustomerProductLocation();
		$location->Delete(id_param('location'));
	} elseif(id_param('product')) {
		$product = new CustomerProduct();
		$product->Delete(id_param('product'));
	} elseif(id_param('group')) {
		$group = new CustomerProductGroup();
		$group->delete(id_param('group'));
		if($_SESSION['preferences']['bulbs']['group'] == id_param('group')) {
			$_SESSION['preferences']['bulbs']['group'] = 0;
		}																			
		redirect(sprintf('Location: ?tab=groups#tab-groups'));
	} elseif(id_param('groupitem')) {
		$item = new CustomerProductGroupItem();
		$item->delete(id_param('groupitem'));
	}
	redirect(sprintf('Location: ?tab=bulbs#tab-bulbs'));
} elseif($action == 'session') {
	if(isset($_SESSION['preferences']['bulbs']['group'])){
		unset($_SESSION['preferences']['bulbs']['group']);
	}

	if(id_param('group')) {
		$_SESSION['preferences']['bulbs']['group'] = id_param('group');
	}

	redirect('Location: ' . $_SERVER['PHP_SELF']);
}

$contacts = array();

if($session->Customer->Contact->HasParent) {
	$data = new DataQuery(sprintf("SELECT Contact_ID FROM contact WHERE Parent_Contact_ID=%d", mysql_real_escape_string($session->Customer->Contact->Parent->ID)));
	while($data->Row) {
		$contacts[] = $data->Row['Contact_ID'];
		$data->Next();	
	}
	$data->Disconnect();
} else {
	$contacts[] = $session->Customer->Contact->ID;
}

// customer locations
$customerLocations = array();
$data = new DataQuery(sprintf("SELECT cl.* FROM customer_location AS cl INNER JOIN customer AS c ON c.Customer_ID=cl.CustomerID WHERE c.Contact_ID IN (%s)", implode(', ', $contacts)));
while($data->Row) {
	$customerLocations[preg_replace('/[^a-zA-Z0-9\s]/', '', $data->Row['Name'])] = $data->Row;
	$data->Next();
}
$data->Disconnect();

// customer products
$customerProducts = array();
$data = new DataQuery(sprintf("SELECT cp.* FROM customer_product AS cp INNER JOIN customer AS c ON c.Customer_ID=cp.Customer_ID LEFT JOIN customer_product_group_item AS cpgi ON cpgi.productId=cp.Product_ID WHERE c.Contact_ID IN (%s)%s GROUP BY cp.Customer_Product_ID", implode(', ', $contacts), ($_SESSION['preferences']['bulbs']['group'] > 0) ? sprintf(' AND cpgi.groupId=%d', $_SESSION['preferences']['bulbs']['group']) : ''));
while($data->Row) {
	$customerProducts[] = $data->Row;
	$data->Next();
}
$data->Disconnect();

// customer groups
$customerGroups = array();
$data = new DataQuery(sprintf("SELECT cpg.* FROM customer_product_group AS cpg INNER JOIN customer AS c ON c.Customer_ID=cpg.customerId WHERE c.Contact_ID IN (%s) ORDER BY cpg.name ASC", implode(', ', $contacts)));
while($data->Row) {
	$customerGroups[] = $data->Row;
	$data->Next();
}
$data->Disconnect();

$formGroup = new Form($_SERVER['PHP_SELF'] . '#tab-groups');
$formGroup->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$formGroup->AddField('form', 'Form', 'hidden', 'group', 'alpha', 5, 5);
$formGroup->SetValue('form', 'group');
$formGroup->AddField('tab', 'Tab', 'hidden', 'groups', 'alpha', 1, 20);
$formGroup->SetValue('tab', 'groups');
$formGroup->AddField('name', 'Name', 'text', '', 'paragraph', 1, 120, true);

$formGroupUpdate = new Form($_SERVER['PHP_SELF'] . '#tab-groups');
$formGroupUpdate->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$formGroupUpdate->AddField('form', 'Form', 'hidden', 'groupupdate', 'alpha', 11, 11);
$formGroupUpdate->SetValue('form', 'groupupdate');
$formGroupUpdate->AddField('tab', 'Tab', 'hidden', 'groups', 'alpha', 1, 20);
$formGroupUpdate->SetValue('tab', 'groups');

foreach($customerGroups as $customerGroup) {
	$formGroupUpdate->AddField('name_' . $customerGroup['id'], sprintf('Name for \'%s\'', $customerGroup['name']), 'text', $customerGroup['name'], 'paragraph', 1, 120, true, 'style="width: 100%;"');	
}

$locationForms = array();

foreach($customerProducts as $customerProduct) {
	$form = new Form($_SERVER['PHP_SELF'] . '#tab-bulbs');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('form', 'Form', 'hidden', 'location_' . $customerProduct['Customer_Product_ID'], 'anything', 8, 16);
	$form->SetValue('form', 'location_' . $customerProduct['Customer_Product_ID']);
	$form->AddField('location_' . $customerProduct['Customer_Product_ID'], 'Location', 'text', '', 'paragraph', 1, 120);
	$form->AddField('location_qty_' . $customerProduct['Customer_Product_ID'], 'Qty', 'text', '', 'numeric_unsigned', 1, 11, false, 'size="5"');
	
	$locationForms[$customerProduct['Customer_Product_ID']] = $form;
}

$groupForms = array();

foreach($customerProducts as $customerProduct) {
	$form = new Form($_SERVER['PHP_SELF'] . '#tab-bulbs');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('form', 'Form', 'hidden', 'group_' . $customerProduct['Customer_Product_ID'], 'anything', 8, 16);
	$form->SetValue('form', 'group_' . $customerProduct['Customer_Product_ID']);
	$form->AddField('group_' . $customerProduct['Customer_Product_ID'], 'Group', 'select', '', 'numeric_unsigned', 1, 11);
	$form->AddOption('group_' . $customerProduct['Customer_Product_ID'], '', '');
	
	foreach($customerGroups as $customerGroup) {
		$form->AddOption('group_' . $customerProduct['Customer_Product_ID'], $customerGroup['id'], $customerGroup['name']);	
	}
	
	$groupForms[$customerProduct['Customer_Product_ID']] = $form;
}

if(param('confirm')) {
	if(param('form')) {
		if(param('form') == 'group') {
			if($formGroup->Validate()) {
				$group = new CustomerProductGroup();
				$group->customer->ID = $session->Customer->ID;
				$group->name = $formGroup->GetValue('name');
				$group->add();
				redirect(sprintf('Location: ?tab=groups#tab-groups'));
			}
			
		} elseif(param('form') == 'groupupdate') {
			if($formGroupUpdate->Validate()) {
				foreach($customerGroups as $customerGroup) {
					$group = new CustomerProductGroup();
					$group->id = $customerGroup['id'];
					$group->customer->ID = $customerGroup['customerId'];
					$group->name = $formGroupUpdate->GetValue('name_' . $customerGroup['id']);
					$group->update();
				}
				
				redirect(sprintf('Location: ?tab=groups&groups=thanks#tab-groups'));
			}
		} else {
			foreach($locationForms as $customerProductId=>$form) {
				if(param('form') == $form->GetValue('form')) {
					if($form->Validate()) {
						$locationString = preg_replace('/[^a-zA-Z0-9\s]/', '', $form->GetValue(sprintf('location_' . $customerProductId)));
						
						if(!isset($customerLocations[$locationString])) {
							$location = new CustomerLocation();
							$location->Customer->ID = $session->Customer->ID;
							$location->Name = $form->GetValue($form->GetValue('form'));;
							$location->Add();
							
							$customerLocations[$locationString] = array('CustomerLocationID' => $location->ID, 'Name' => $location->Name);
						}
						
						$product = new CustomerProductLocation();
						$product->Location->ID = $customerLocations[$locationString]['CustomerLocationID'];
						$product->Product->ID = $customerProductId;
						$product->Group->ID = $_SESSION['preferences']['bulbs']['group'];
						$product->Quantity = $form->GetValue(sprintf('location_qty_' . $customerProductId));
						$product->Add();
						
						redirect(sprintf('Location: ?tab=bulbs#tab-bulbs'));
					}
				}
			}
			
			foreach($groupForms as $customerProductId=>$form) {
				if(param('form') == $form->GetValue('form')) {
					if($form->Validate()) {
						foreach($customerProducts as $customerProduct) {
							if($customerProduct['Customer_Product_ID'] == $customerProductId) {
								$item = new CustomerProductGroupItem();
								$item->group->id = $form->GetValue('group_' . $customerProductId);
								$item->product->ID = $customerProduct['Product_ID'];
								$item->add();
						
								break;
							}
						}
						
						redirect(sprintf('Location: ?tab=bulbs'));
					}
				}
			}
		}
	}
}

$groupsEquivalentWattage = array();
$groupsWattage = array();
$groupsLampLife = array();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%%equivalent%%' AND Reference LIKE '%%wattage%%'"));
while($data->Row) {
	$groupsEquivalentWattage[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE 'wattage'"));
while($data->Row) {
	$groupsWattage[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();

$data = new DataQuery(sprintf("SELECT Group_ID FROM product_specification_group WHERE Reference LIKE '%%lamp%%' AND Reference LIKE '%%life%%'"));
while($data->Row) {
	$groupsLampLife[] = $data->Row['Group_ID'];
	
	$data->Next();	
}
$data->Disconnect();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>My Bulbs</title>
	<!-- InstanceEndEditable -->

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs_print.css" media="print" />
	<link rel="stylesheet" type="text/css" href="css/Navigation.css" />
	<link rel="stylesheet" type="text/css" href="css/Menu.css" />
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		?>
		<link rel="stylesheet" type="text/css" href="css/Trade.css" />
        <?php
	}
	?>
	<link rel="shortcut icon" href="favicon.ico" />
<!--    <script type='text/javascript' src='http://api.handsetdetection.com/sites/js/43071.js'></script>-->
	<script language="javascript" type="text/javascript" src="js/generic.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance_api.js"></script>
	<script language="javascript" type="text/javascript" src="js/mootools.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance.js"></script>
	<script language="javascript" type="text/javascript" src="js/bltdirect.js"></script>
    <script language="javascript" type='text/javascript' src="js/api.js"></script>
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script language="javascript" type="text/javascript" src="js/bltdirect/template.js"></script>
        <?php
	}
	?>
    
	<script language="javascript" type="text/javascript">
	//<![CDATA[
		<?php
		for($i=0; $i<count($GLOBALS['Cache']['Categories']); $i=$i+2) {
			echo sprintf("menu1.add('navProducts%d', 'navProducts', '%s', '%s', null, 'subMenu');", $i, $GLOBALS['Cache']['Categories'][$i], $GLOBALS['Cache']['Categories'][$i+1]);
		}
		?>
	//]]>
	</script>	
	<!-- InstanceBeginEditable name="head" -->
	<script type="text/javascript" src="/js/tabs.js"></script>
	<script type="text/javascript">
		addContent('bulbs');
		addContent('groups');
	</script>
	<!-- InstanceEndEditable -->
</head>
<body>

    <div id="Wrapper">
        <div id="Header">
            <div id="HeaderInner">
                <?php require('lib/templates/header.php'); ?>
            </div>
        </div>
        <div id="PageWrapper">
            <div id="Page">
                <div id="PageContent">
                    <?php
                    if(strtolower(Setting::GetValue('site_message_active')) == 'true') {
                        ?>

                        <div id="SiteMessage">
                            <div id="SiteMessageLeft">
                                <div id="SiteMessageRight">
                                    <marquee scrollamount="4"><?php echo Setting::GetValue('site_message_value'); ?></marquee>
                                </div>
                            </div>
                        </div>

                        <?php
                    }
                    ?>
                    
                    <a name="top"></a>
                    
                    <!-- InstanceBeginEditable name="pageContent" -->
					<h1>My Bulbs</h1>
					
					<div id="orderConfirmation">
						<p class="breadCrumb"><a href="accountcenter.php">My Account</a> | <a href="introduce.php">Introduce A Friend</a> | <a href="bulbs.php">My Bulbs</a> | <a href="quotes.php">My Quotes</a> | <a href="orders.php">My Orders</a> | <a href="invoices.php">My Invoices</a> | <a href="enquiries.php">Enquiry Centre</a> | <a href="eNotes.php">Order Notes</a> | <a href="duplicate.php">Duplicate A Past Order</a> | <a href="returnorder.php">Returns</a> | <a href="profile.php">My Profile</a><?php if($session->Customer->Contact->HasParent){ ?> | <a href="businessProfile.php">My Business Profile</a><?php } ?> | <a href="changePassword.php">Change Password</a></p>
					</div>
					
					<?php include('lib/templates/bought.php'); ?>
					
					<div style="width:300px; float:left; margin-right:10px;">
						<p>Below is a list of your remembered products.<br />You may add any number of locations to your favourite products by typing the name into the location box and clicking the add locations button at the bottom of the page.</p>
						<p>Check out our video on how to use this system.</p>
					</div>
					<div style="width:600px; float:left;">
						<iframe width="350" height="197" src="//www.youtube.com/embed/I5mDScrZSy0?rel=0&amp;wmode=transparent"></iframe>
					</div>
					<div class="clear"></div>
					<br />

					<div class="tab-bar">
						<div class="tab-bar-item <?php echo ($tab == 'bulbs') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-bulbs" onclick="setContent('bulbs');">
							<a href="javascript: void(0);">Bulbs</a><br />
							<span class="tab-bar-item-sub">favourite products</span>
						</div>
						<div class="tab-bar-item <?php echo ($tab == 'groups') ? 'tab-bar-item-selected' : ''; ?>" id="tab-bar-item-groups" onclick="setContent('groups');">
							<a href="javascript: void(0);">Groups</a><br />
							<span class="tab-bar-item-sub">product arrangement</span>
					  </div>
						<div class="clear"></div>
					</div>
					
					<div class="tab-content">
						<div class="tab-content-item" id="tab-content-item-bulbs" <?php echo ($tab == 'bulbs') ? '' : 'style="display: none;"'; ?>>
						
							<div class="tab-content-title">
								<a name="tab-bulbs" id="tab-bulbs"></a>
								<h2>Bulbs</h2>
								for your account
							</div>
							
							<?php
							foreach($locationForms as $form) {
								if(!$form->Valid) {
									?>
			
									<div class="attention">
										<div class="attention-icon attention-icon-warning"></div>
										<div class="attention-info attention-info-warning">
											<span class="attention-info-title">Please Correct The Following</span><br />
											
											<ol>
											
												<?php
												for($i=0; $i<count($form->Errors); $i++) {
													echo sprintf('<li>%s</li>', $form->Errors[$i]);
												}
												?>
												
											</ol>
										</div>
									</div>
									
									<?php
								}
							}
							
							foreach($groupForms as $form) {
								if(!$form->Valid) {
									?>
			
									<div class="attention">
										<div class="attention-icon attention-icon-warning"></div>
										<div class="attention-info attention-info-warning">
											<span class="attention-info-title">Please Correct The Following</span><br />
											
											<ol>
											
												<?php
												for($i=0; $i<count($form->Errors); $i++) {
													echo sprintf('<li>%s</li>', $form->Errors[$i]);
												}
												?>
												
											</ol>
										</div>
									</div>
									
									<?php
								}
							}

							if(count($customerGroups) > 0) {
								?>
								
								<div class="options">
									<ul>

										<li class="options-primary"><a href="?action=session&amp;group=0">Show All</a></li>
											
										<?php						
										foreach($customerGroups as $customerGroup) {
											if(($_SESSION['preferences']['bulbs']['group'] == 0) || ($_SESSION['preferences']['bulbs']['group'] == $customerGroup['id'])) {
												?>
												
												<li><a href="?action=session&amp;group=<?php echo $customerGroup['id']; ?>"><?php echo $customerGroup['name']; ?></a></li>
												
												<?php
											}
										}
										?>
										
									</ul>
									
									<div class="clear"></div>
								</div>
								
								<?php
							}
											
							if(count($customerProducts) > 0) {
								?>
								
								<div class="bulb-products">
									<table class="list">
									
										<?php
										foreach($customerProducts as $customerProduct) {
											$subProduct = new Product();
											
											if($subProduct->Get($customerProduct['Product_ID'])) {
												$subProduct->GetRelatedByType('Energy Saving Alternative');
												
												$rowClass = 'list-none';
												$hideSpecifications = true;
												
												include('lib/templates/productLine.php');

												unset($rowClass);
												unset($hideSpecifications);
												?>
												
												<tr class="list-thin <?php echo !empty($subProduct->RelatedType['Energy Saving Alternative']) ? 'list-none' : ''; ?>">
													<td></td>
													<td colspan="2">
													
														<table class="list">
															<tr>
																<td width="50%" valign="top">
																	<?php
																	$form = $locationForms[$customerProduct['Customer_Product_ID']];
																	
																	echo $form->Open();
																	echo $form->GetHTML('confirm');
																	echo $form->GetHTML('form');
																	?>
																	
																	<div class="field">
																		<div class="field-label"><?php echo $form->GetLabel('location_' . $customerProduct['Customer_Product_ID']); ?></div>
																		<?php echo $form->GetHTML('location_' . $customerProduct['Customer_Product_ID']); ?>
																		
																		<input type="submit" name="add" value="Add" class="button" />
																		
																		<div class="field-label"><?php echo $form->GetLabel('location_qty_' . $customerProduct['Customer_Product_ID']); ?></div>
																		<?php echo $form->GetHTML('location_qty_' . $customerProduct['Customer_Product_ID']); ?>
																	</div>
																	
																	<?php
																	echo $form->Close();

																	$data = new DataQuery(sprintf("SELECT cpl.CustomerProductLocationID, cpl.Quantity, cl.Name, cpg.name AS `Group` FROM customer_product_location AS cpl INNER JOIN customer_location AS cl ON cl.CustomerLocationID=cpl.CustomerLocationID LEFT JOIN customer_product_group AS cpg ON cpg.id=cpl.CustomerProductGroupID WHERE cpl.CustomerProductID=%d%s", mysql_real_escape_string($customerProduct['Customer_Product_ID']), ($_SESSION['preferences']['bulbs']['group'] > 0) ? sprintf(' AND cpl.CustomerProductGroupID=%d', $_SESSION['preferences']['bulbs']['group']) : ''));
																	while($data->Row) {
																		echo sprintf('<a href="?action=remove&location=%d"><img class="bulb-products-delete" src="/images/button-cross.gif" alt="Remove" /></a>%s%s%s<br />', $data->Row['CustomerProductLocationID'], $data->Row['Name'], ($_SESSION['preferences']['bulbs']['group'] == 0) ? (!empty($data->Row['Group']) ? sprintf(' <strong>%s</strong>', $data->Row['Group']) : '') : '', !empty($data->Row['Quantity']) ? sprintf(' (%dx)', $data->Row['Quantity']) : '');
																		
																		$data->Next();
																	}
																	$data->Disconnect();
																	?>
																</td>
																<td width="50%" <?php echo ($_SESSION['preferences']['bulbs']['group'] == 0) ? 'valign="top"' : ''; ?>>
																	<?php
																	if(count($customerGroups) > 0) {
																		$form = $groupForms[$customerProduct['Customer_Product_ID']];
																		
																		if($_SESSION['preferences']['bulbs']['group'] == 0) {
																			echo $form->Open();
																			echo $form->GetHTML('confirm');
																			echo $form->GetHTML('form');
																			?>
																			
																			<div class="field">
																				<div class="field-label"><?php echo $form->GetLabel('group_' . $customerProduct['Customer_Product_ID']); ?></div>
																				<?php echo $form->GetHTML('group_' . $customerProduct['Customer_Product_ID']); ?>
																				<input type="submit" name="add" value="Add" class="button" />
																			</div>
																			
																			<?php
																			echo $form->Close();
																			
																			$data = new DataQuery(sprintf("SELECT cpgi.id, cpg.id AS groupId, cpg.name FROM customer_product_group_item AS cpgi INNER JOIN customer_product_group AS cpg ON cpg.id=cpgi.groupId INNER JOIN customer AS c ON c.Customer_ID=cpg.customerId WHERE cpgi.productId=%d AND c.Contact_ID IN (%s)", $customerProduct['Product_ID'], implode(', ', $contacts)));
																			while($data->Row) {
																				echo sprintf('<a href="?action=remove&groupitem=%d"><img class="bulb-products-delete" src="/images/button-cross.gif" alt="Remove" /></a>%s<br />', $data->Row['id'], $data->Row['name']);
																				
																				$data->Next();
																			}
																			$data->Disconnect();
																		}
																	}
																	?>
																</td>
															</tr>
														</table>
														
													</td>
													<td align="right">
													
														<?php
														$data = new DataQuery(sprintf("SELECT MAX(o.Created_On) AS Last_Ordered_On, SUM(ol.Quantity) AS Total_Ordered FROM orders AS o INNER JOIN customer AS c ON c.Customer_ID=o.Customer_ID INNER JOIN order_line AS ol ON ol.Order_ID=o.Order_ID AND ol.Product_ID=%d WHERE o.Status NOT IN ('Cancelled', 'Incomplete', 'Unauthenticated') AND c.Contact_ID IN (%s)", mysql_real_escape_string($subProduct->ID), implode(', ', $contacts)));

														if($data->Row['Total_Ordered'] > 0) {
															?>
															
															Last Ordered<br />
															<span class="colour-blue"><?php echo cDatetime($data->Row['Last_Ordered_On'], 'shortdate'); ?></span>
															<br /><br />
																														
															Total Ordered<br />
															<span class="colour-blue"><?php echo $data->Row['Total_Ordered']; ?></span>
															
															<?php
														}
														$data->Disconnect();
														?>
			
													</td>
													<td align="right">
														
														<?php
														if($_SESSION['preferences']['bulbs']['group'] > 0) {
															$data = new DataQuery(sprintf("SELECT cpgi.id FROM customer_product_group_item AS cpgi INNER JOIN customer_product_group AS cpg ON cpg.id=cpgi.groupId WHERE cpgi.productId=%d AND cpg.id=%d", mysql_real_escape_string($customerProduct['Product_ID']), mysql_real_escape_string($_SESSION['preferences']['bulbs']['group'])));
															if($data->TotalRows > 0) {
																?>
																					
																<input type="button" name="remove" value="Remove From Group" class="button button-grey" onclick="redirect('?action=remove&amp;groupitem=<?php echo $data->Row['id']; ?>');" /><br />
																
																<?php
															}
															$data->Disconnect();
														}	
														?>
														
														<input type="button" name="remove" value="Remove From Bulbs" class="button button-grey" onclick="redirect('?action=remove&amp;product=<?php echo $customerProduct['Customer_Product_ID']; ?>');" />
												  </td>
												</tr>
												
												<?php
												$countColumns = 4;

												include('lib/templates/productAlternatives.php');
											}
										}
										?>
										
									</table>
								</div>
								
								<?php
							}
							?>

							</table>
							
						</div>
						
						<div class="tab-content-item" id="tab-content-item-groups" <?php echo ($tab == 'groups') ? '' : 'style="display: none;"'; ?>>
						
							<div class="tab-content-side">
								<div class="tab-content-title">
									<a name="tab-groups" id="tab-groups"></a>
									<h2>New Group</h2>
									add new group
								</div>
								
								<?php
								if(!$formGroup->Valid) {
									?>
			
									<div class="attention">
										<div class="attention-icon attention-icon-warning"></div>
										<div class="attention-info attention-info-warning">
											<span class="attention-info-title">Please Correct The Following</span><br />
											
											<ol>
											
												<?php
												for($i=0; $i<count($formGroup->Errors); $i++) {
													echo sprintf('<li>%s</li>', $formGroup->Errors[$i]);
												}
												?>
												
											</ol>
										</div>
									</div>
									
									<?php
								}
								
								echo $formGroup->Open();
								echo $formGroup->GetHTML('confirm');
								echo $formGroup->GetHTML('form');
								echo $formGroup->GetHTML('tab');
								?>
								
								<div class="field">
									<div class="field-label"><?php echo $formGroup->GetLabel('name'); ?></div>
									<?php echo $formGroup->GetHTML('name'); ?>
								</div>
								
								<input type="submit" name="add" value="Add" class="button" />
								
								<?php
								echo $formGroup->Close();
								?>
							</div>
							
							<div class="tab-content-guttering">
								<div class="tab-content-title">
									<h2>Groups</h2>
									for arranging your favourite products
								</div>
								
								<?php
								if(param('groups') == 'thanks') {
									?>

									<div class="attention">
										<div class="attention-info attention-info-feedback">
											<span class="attention-info-title">Groups Updated</span><br />
											Your group titles have been updated and their product associations will not be affected.
										</div>
									</div>
										
									<?php
								} else {
									if(!$formGroupUpdate->Valid) {
										?>
				
										<div class="attention">
											<div class="attention-icon attention-icon-warning"></div>
											<div class="attention-info attention-info-warning">
												<span class="attention-info-title">Please Correct The Following</span><br />
												
												<ol>
												
													<?php
													for($i=0; $i<count($formGroupUpdate->Errors); $i++) {
														echo sprintf('<li>%s</li>', $formGroupUpdate->Errors[$i]);
													}
													?>
													
												</ol>
											</div>
										</div>
										
										<?php
									}
								}
														
								if(count($customerGroups) > 0) {
									echo $formGroupUpdate->Open();
									echo $formGroupUpdate->GetHTML('confirm');
									echo $formGroupUpdate->GetHTML('form');
									echo $formGroupUpdate->GetHTML('tab');
									?>
									
									<table class="list list-thin list-border-vertical">

										<?php						
										foreach($customerGroups as $customerGroup) {
											?>

											<tr>
												<td>
													<?php
													if(param('groups') == 'edit') {
														echo $formGroupUpdate->GetHTML('name_' . $customerGroup['id']);
													} else {
														echo $customerGroup['name'];
													}
													?>
												</td>
												<td align="right">
													<input type="button" name="filter" value="Show Bulbs" class="button button-grey" onclick="redirect('?action=session&amp;group=<?php echo $customerGroup['id']; ?>');" />
													<input type="button" name="remove" value="Remove" class="button button-grey" onclick="redirect('?action=remove&amp;group=<?php echo $customerGroup['id']; ?>');" />												
											  </td>
											</tr>

											<?php
										}
										?>

									</table>
									<br />
									
									<?php
									if(param('groups') == 'edit') {
										echo '<input type="submit" name="update" value="Update" class="button" />';
									} else {
										echo '<input type="button" name="edit" value="Edit" class="button button-grey" onclick="window.self.location.href = \'?tab=groups&groups=edit#tab-groups\'" />';
									}
									
									echo $formGroupUpdate->Close();
								}
								?>
								
							</div>
							<div class="clear"></div>
							
						</div>
						
					</div>
					
					<?php include('lib/templates/back.php'); ?>
					
					<!-- InstanceEndEditable -->
                </div>
            </div>
            <div id="PageFooter">
                <ul class="links">
                    <li><a href="./terms.php" title="BLT Direct Terms and Conditions of Use and Sale">Terms and Conditions</a></li>
                    <li><a href="./privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
                    <li><a href="./company.php" title="About BLT Direct">About Us</a></li>
                    <li><a href="./sitemap.php" title="Map of Site Contents">Site Map</a></li>
                    <li><a href="./support.php" title="Contact BLT Direct">Contact Us</a></li>
                    <li><a href="./index.php" title="Light Bulbs">Light Bulbs</a></li>
                    <li><a href="./products.php?cat=1251&amp;nm=Christmas+Lights" title="Christmas Lights">Christmas Lights</a></li> 
                    <li><a href="./Projector-Lamps.php" title="Projector Lamps">Projector Lamps</a></li>
                    <li><a href="./articles.php" title="Press Releases/Articles">Press Releases/Articles</a></li>
                </ul>
                
                <p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
            </div>
        </div>
        <div id="LeftNav">
            <?php require('lib/templates/left.php'); ?>
        </div>
        <div id="RightNav">
            <?php require('lib/templates/right.php'); ?>
        
            <div id="Azexis">
                <a href="http://www.azexis.com" target="_blank" title="Web Designers">Web Designers</a>
            </div>
        </div>
    </div>
	<script src="<?php print ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT']) ? 'http://www' : 'https://ssl'; ?>.google-analytics.com/urchin.js" type="text/javascript"></script>
	<script type="text/javascript">
	//<![CDATA[
		_uacct = "UA-1618935-2";
		urchinTracker();
	//]]>
	</script>

	<!-- InstanceBeginEditable name="Tracking Script" -->

<!--
<script>
var parm,data,rf,sr,htprot='http'; if(self.location.protocol=='https:')htprot='https';
rf=document.referrer;sr=document.location.search;
if(top.document.location==document.referrer||(document.referrer == '' && top.document.location != '')) {rf=top.document.referrer;sr=top.document.location.search;}
data='cid=256336&rf=' + escape(rf) + '&sr=' + escape(sr); parm=' border="0" hspace="0" vspace="0" width="1" height="1" '; document.write('<img '+parm+' src="'+htprot+'://stats1.saletrack.co.uk/scripts/stinit.asp?'+data+'">');
</script>
<noscript>
<img src="http://stats1.saletrack.co.uk/scripts/stinit.asp?cid=256336&rf=JavaScri
pt%20Disabled%20Browser" border="0" width="0" height="0" />
</noscript>
-->

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>
<?php include('lib/common/appFooter.php'); ?>