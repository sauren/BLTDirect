<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Order.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Despatch.php');

$session->Secure(3);

$order = new Order($_REQUEST['orderid']);
$order->GetLines();

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('orderid', 'Order ID', 'hidden', '', 'numeric_unsigned', 1, 11);

for($i=0; $i<count($order->Line); $i++) {
	if($order->Line[$i]->DespatchID > 0) {
		$form->AddField(sprintf('quantity_%d', $order->Line[$i]->ID), sprintf('Quantity for \'%s\'', $order->Line[$i]->Product->Name), 'text', $order->Line[$i]->Quantity, 'numeric_unsigned', 1, 11, true, 'size="3"');
	}
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		for($i=0; $i<count($order->Line); $i++) {
			if($order->Line[$i]->DespatchID > 0) {
				if(($form->GetValue(sprintf('quantity_%d', $order->Line[$i]->ID)) < 0) || ($form->GetValue(sprintf('quantity_%d', $order->Line[$i]->ID)) > $order->Line[$i]->Quantity)) {
					$form->AddError(sprintf('Quantity for \'%s\' must be between 0 and %d.', $order->Line[$i]->Product->Name, $order->Line[$i]->Quantity), sprintf('quantity_%d', $order->Line[$i]->ID));
				}
			}
		}

		if($form->Valid) {
			$purchases = array('Deleted' => array(), 'Amended' => array());
			
			for($i=0; $i<count($order->Line); $i++) {
				if($order->Line[$i]->DespatchID > 0) {
					$data = new DataQuery(sprintf("SELECT Despatch_Line_ID FROM despatch_line WHERE Despatch_ID=%d AND Product_ID=%d AND Quantity=%d", mysql_real_escape_string($order->Line[$i]->DespatchID), mysql_real_escape_string($order->Line[$i]->Product->ID), mysql_real_escape_string($order->Line[$i]->Quantity)));
					if($data->TotalRows > 0) {
						$quantityDespatched = $order->Line[$i]->Quantity;
						$quantityUndespatch = $form->GetValue(sprintf('quantity_%d', $order->Line[$i]->ID));

						if($quantityUndespatch > 0) {
							$despatch = new Despatch($order->Line[$i]->DespatchID);
							$despatch->Purchase->Get();
							
							if($quantityUndespatch < $quantityDespatched) {
								new DataQuery(sprintf("UPDATE despatch_line SET Quantity=%d WHERE Despatch_Line_ID=%d", mysql_real_escape_string($quantityDespatched - $quantityUndespatch), $data->Row['Despatch_Line_ID']));

								$lineDiscount = $order->Line[$i]->Discount;
								$lineTax = $order->Line[$i]->Tax;
								$lineTotal = $order->Line[$i]->Total;

								$order->Line[$i]->Quantity = $quantityDespatched - $quantityUndespatch;
								$order->Line[$i]->Discount = ($lineDiscount / $quantityDespatched) * $order->Line[$i]->Quantity;
								$order->Line[$i]->Tax = ($lineTax / $quantityDespatched) * $order->Line[$i]->Quantity;
								$order->Line[$i]->Total = ($lineTotal / $quantityDespatched) * $order->Line[$i]->Quantity;
								$order->Line[$i]->Update();

								$order->Line[$i]->Quantity = $quantityUndespatch;
								$order->Line[$i]->DespatchID = 0;
								$order->Line[$i]->Discount = ($lineDiscount / $quantityDespatched) * $order->Line[$i]->Quantity;
								$order->Line[$i]->Tax = ($lineTax / $quantityDespatched) * $order->Line[$i]->Quantity;
								$order->Line[$i]->Total = ($lineTotal / $quantityDespatched) * $order->Line[$i]->Quantity;
								$order->Line[$i]->Add();
							} else {
								new DataQuery(sprintf("DELETE FROM despatch_line WHERE Despatch_Line_ID=%d", $data->Row['Despatch_Line_ID']));

								$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM despatch_line WHERE Despatch_ID=%d", mysql_real_escape_string($order->Line[$i]->DespatchID)));
								if($data2->Row['Count'] == 0) {
									if($despatch->Purchase->ID > 0) {
										$despatch->Purchase->Delete();
										
										$purchases['Deleted'][$despatch->Purchase->ID] = $despatch->Purchase;
									}

									new DataQuery(sprintf("DELETE FROM despatch WHERE Despatch_ID=%d", mysql_real_escape_string($order->Line[$i]->DespatchID)));
								}
								$data2->Disconnect();

								$order->Line[$i]->DespatchID = 0;
								$order->Line[$i]->Update();
							}

							if($despatch->Purchase->ID > 0) {
								if($despatch->Purchase->Get()) {
									$despatch->Purchase->GetLines();
									
									$remaining = $quantityUndespatch;
									
									for($j=count($despatch->Purchase->Line)-1; $j>=0; $j--) {
										if($remaining > 0) {
											if($despatch->Purchase->Line[$j]->Product->ID == $order->Line[$i]->Product->ID) {
												$currentQty = $despatch->Purchase->Line[$j]->Quantity;
																								
												if($remaining < $despatch->Purchase->Line[$j]->Quantity) {
													$despatch->Purchase->Line[$j]->Quantity -= $remaining;
													$despatch->Purchase->Line[$j]->Update();
													
													$purchases['Amended'][$despatch->Purchase->ID] = $despatch->Purchase;
												} else {
													$despatch->Purchase->Line[$j]->Delete();
													
													$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS Count FROM purchase_line WHERE Purchase_ID=%d", mysql_real_escape_string($despatch->Purchase->ID)));
													if($data2->Row['Count'] == 0) {
														$despatch->Purchase->Delete();
														
														$purchases['Deleted'][$despatch->Purchase->ID] = $despatch->Purchase;
														
														break;
													}
													$data2->Disconnect();
												}
												
												$remaining -= $currentQty;
											}
										}
									}
								}								
							}

							$order->Line[$i]->DespatchedFrom->ChangeQuantity($order->Line[$i]->Product->ID, $quantityUndespatch * -1);
						}
					}
					$data->Disconnect();
				}
			}

			$order->GetLines();
			$order->Status = 'Packing';

			for($i=0; $i<count($order->Line); $i++) {
				if($order->Line[$i]->DespatchID > 0) {
					$order->Status = 'Partially Despatched';
					break;
				}
			}

			$order->Update();
			
			foreach($purchases['Deleted'] as $purchase) {
				$purchase->EmailSupplierDeleted();
				
				if(isset($purchases['Amended'][$purchase->ID])) {
					unset($purchases['Amended'][$purchase->ID]);
				}
			}
			
			foreach($purchases['Amended'] as $purchase) {
				$purchase->EmailSupplierAmended();
			}

			echo '<script language="javascript" type="text/javascript">window.opener.location.reload(true);window.self.close();</script>';
			exit;
		}
	}
}

$page = new Page(sprintf('Undespatch Order %s%s', $order->Prefix, $order->ID), 'If you are undespatching this order in part please edit the relevant quantities.');
$page->Display('header');

if(!$form->Valid) {
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('orderid');
?>

<table border="0" cellpadding="4" cellspacing="0" class="orderDetails">
	<tr>
		<th>Quantity</th>
		<th>Description</th>
		<th>Warehouse</th>
		<th>Quickfind</th>
	</tr>

	<?php
	for($i=0; $i<count($order->Line); $i++) {
		if($order->Line[$i]->DespatchID > 0) {
			$order->Line[$i]->DespatchedFrom->Contact->Get();
			?>

			<tr>
				<td><?php echo $form->GetHTML(sprintf('quantity_%d', $order->Line[$i]->ID)); ?></td>
				<td><?php echo $order->Line[$i]->Product->Name; ?></td>
				<td>
					<?php
					if($order->Line[$i]->DespatchedFrom->Type == 'B') {
						echo $order->Line[$i]->DespatchedFrom->Contact->Name;

					} elseif($order->Line[$i]->DespatchedFrom->Type == 'S') {
						$order->Line[$i]->DespatchedFrom->Contact->Contact->Get();

						echo $order->Line[$i]->DespatchedFrom->Contact->Contact->Parent->Organisation->Name;
					}
					?>
				</td>
				<td><?php echo $order->Line[$i]->Product->ID; ?></td>
			</tr>

			<?php
		}
	}
	?>

</table>
<br />

<input type="submit" name="undespatch" value="undespatch" class="btn">

<?php
echo $form->Close();

$page->Display('footer');
require_once('lib/common/app_footer.php');