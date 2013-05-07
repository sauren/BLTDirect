<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Reserve.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ReserveItem.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Supplier.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

$session->Secure(3);

$reserve = new Reserve($_REQUEST['id']);
$reserve->getLines();
$reserve->supplier->Get();
$reserve->supplier->Contact->Get();

$isEditable = (strtolower($reserve->status) != 'completed') ? true : false;

if($action == "remove") {
	if($isEditable) {
		if(isset($_REQUEST['line'])) {
			$line = new ReserveItem();
			$line->delete($_REQUEST['line']);
		}
	}

	redirect(sprintf("Location: ?id=%d", $reserve->id));

} elseif($action == "cancel") {
	$reserve->cancel();

	redirect(sprintf("Location: ?id=%d", $reserve->id));

} elseif($action == "delete") {
	$reserve->delete();

	redirect(sprintf("Location: reserves_pending.php"));
}

if(isset($_REQUEST['removeselectedproducts'])) {
	if($isEditable) {
		$line = new ReserveItem();

		foreach($_REQUEST as $key=>$value) {
			if(preg_match('/^product_select_([\d]*)$/', $key, $matches)) {
				$line->delete($matches[1]);
			}
		}
	}

	redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $reserve->id));
}

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
$form->AddField('id', 'Reserve ID', 'hidden', $reserve->id, 'numeric_unsigned', 1, 11);

if($isEditable) {
	for($i=0; $i<count($reserve->line); $i++) {
		$form->AddField('product_select_'.$reserve->line[$i]->id, 'Selected Product', 'checkbox', 'N', 'boolean', 1, 1, false);
		$form->AddField('product_quantity_'.$reserve->line[$i]->id, sprintf('Quantity for \'%s\'', $reserve->line[$i]->product->Name), 'text', $reserve->line[$i]->quantity, 'numeric_unsigned', 1, 11, false, 'size="3"');
	}
}

if(isset($_REQUEST['confirm'])) {
	if($form->Validate()) {
		if(isset($_REQUEST['update']) || isset($_REQUEST['updateproducts'])) {
			if($isEditable) {
				for($i=0; $i<count($reserve->line); $i++) {
					$difference = $form->GetValue('product_quantity_'.$reserve->line[$i]->id) - $reserve->line[$i]->quantity;

					if($difference < 0) {
						if(($difference * -1) > $reserve->line[$i]->quantityRemaining) {
							$difference = $reserve->line[$i]->quantityRemaining * -1;
						}
					}

					$reserve->line[$i]->quantity += $difference;
					$reserve->line[$i]->quantityRemaining += $difference;
					$reserve->line[$i]->update();
				}
			}
		}

		if($form->Valid) {
			$reserve->update();

			redirect(sprintf("Location: %s?id=%d", $_SERVER['PHP_SELF'], $reserve->id));
		}
	}
}

$page = new Page(sprintf('[#%d] Reserve Details', $reserve->id), 'Manage this reserve here.');
$page->Display('header');

if(!$form->Valid){
	echo $form->GetError();
	echo '<br />';
}

echo $form->Open();
echo $form->GetHTML('confirm');
echo $form->GetHTML('id');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td align="left" valign="top"></td>
    <td align="right" valign="top">

	    <table border="0" cellpadding="0" cellspacing="0" class="invoicePaymentDetails">
	      <tr>
	        <th>Reserve:</th>
	        <td>#<?php echo $reserve->id; ?></td>
	      </tr>
	      <tr>
	        <th>Status:</th>
	        <td><?php echo $reserve->status; ?></td>
	      </tr>
	      <tr>
	        <th>Supplier:</th>
	        <td>
	        	<?php
	        	echo $reserve->supplier->Contact->Person->GetFullName();
	        	?>
	        </td>
	      </tr>
	      <tr>
	        <th>&nbsp;</th>
	        <td>&nbsp;</td>
	      </tr>
	      <tr>
	        <th>Created On:</th>
	        <td><?php echo cDatetime($reserve->createdOn, 'shortdate'); ?></td>
	      </tr>
	      <tr>
	        <th>Created By:</th>
	        <td>
	        	<?php
	        	$user = new User();
	        	$user->ID = $reserve->createdBy;

	        	if($user->Get()) {
	        		echo trim(sprintf('%s %s', $user->Person->Name, $user->Person->LastName));
	        	}
	        	?>
	        	&nbsp;
	        </td>
	      </tr>
	    </table><br />

   </td>
  </tr>
  <tr>
  	<td valign="top">

		<?php
		if($isEditable) {
			if($reserve->status != 'Cancelled') {
				echo sprintf('<input name="cancel" type="button" value="cancel" class="btn" onclick="confirmRequest(\'?id=%d&action=cancel\', \'Please confirm you wish to cancel this item?\');" /> ', $reserve->id);
			}

			echo sprintf('<input name="delete" type="button" value="delete" class="btn" onclick="confirmRequest(\'?id=%d&action=delete\', \'Are you sure you would like to delete this item?\');" /> ', $reserve->id);
		}
		?>

		<input name="print" type="button" value="print" class="btn" onclick="popUrl('reserve_print.php?id=<?php echo $reserve->id; ?>', 800, 600);" />

		<br />

  	</td>
  	<td align="right" valign="top">
	  	<?php
		if($isEditable) {
			?>

			<input name="update" type="submit" value="update" class="btn" />

			<?php
		}
		?>
	</td>
  </tr>
  <tr>
    <td colspan="2">
		<br />

		<div style="background-color: #eee; padding: 10px 0 10px 0;">
		 	<p><span class="pageSubTitle">Products</span><br /><span class="pageDescription">Listing products requesting reserving.</span></p>

		 	<table cellspacing="0" class="orderDetails">
				<tr>
			        <?php
					if($isEditable) {
						echo '<th nowrap="nowrap">&nbsp;</th>';
						echo '<th nowrap="nowrap">&nbsp;</th>';
					}
					?>

					<th nowrap="nowrap" style="padding-right: 5px;">Quantity</th>
					<th nowrap="nowrap" style="padding-right: 5px;">Quickfind</th>
			        <th nowrap="nowrap" style="padding-right: 5px;">Name</th>
			        <th nowrap="nowrap" style="padding-right: 5px;">SKU</th>
			        <th nowrap="nowrap" style="padding-right: 5px;">Expires</th>

			        <?php
					if($isEditable) {
						echo '<th nowrap="nowrap" style="padding-right: 5px;">Remaining</th>';
					}
					?>
				</tr>

				<?php
				if(count($reserve->line) > 0) {
					for($i=0; $i<count($reserve->line); $i++) {
						?>

						<tr>
							<?php
							if($isEditable) {
								echo sprintf('<td nowrap="nowrap" width="1%%">%s</td>', $form->GetHTML('product_select_'.$reserve->line[$i]->id));
								echo sprintf('<td nowrap="nowrap" width="1%%"><a href="javascript:confirmRequest(\'?id=%d&action=remove&line=%d\', \'Are you sure you wish to remove this item?\');"><img align="absmiddle" src="images/icon_trash_1.gif" alt="Remove" border="0" /></a></td>', $reserve->id, $reserve->line[$i]->id);
							}

							if($isEditable) {
								echo sprintf('<td nowrap="nowrap">%s</td>', $form->GetHTML('product_quantity_'.$reserve->line[$i]->id));
							} else {
								echo sprintf('<td nowrap="nowrap">%s</td>', number_format(round($reserve->line[$i]->quantity, 2), 2, '.', ''));
							}
							?>

							<td nowrap="nowrap"><?php echo $reserve->line[$i]->product->ID; ?></td>
							<td nowrap="nowrap"><a href="product_profile.php?pid=<?php echo $reserve->line[$i]->product->ID; ?>"><?php echo $reserve->line[$i]->product->Name; ?></a></td>
							<td nowrap="nowrap"><?php echo $reserve->line[$i]->product->SKU; ?></td>
							<td nowrap="nowrap">
								<?php
								$data = new DataQuery(sprintf("SELECT DropSupplierExpiresOn FROM product WHERE Product_ID=%d AND DropSupplierID=%d", mysql_real_escape_string($reserve->line[$i]->product->ID), mysql_real_escape_string($reserve->supplier->ID)));
								echo ($data->TotalRows > 0) ? cDatetime($data->Row['DropSupplierExpiresOn'], 'shortdate') : '';
								$data->Disconnect();
								?>
							</td>

							<?php
							if($isEditable) {
								echo sprintf('<td nowrap="nowrap">%s</td>', $reserve->line[$i]->quantityRemaining);
							}
							?>
						</tr>

						<?php
					}
				} else {
			      	?>

			      	<tr>
			      		<td colspan="<?php echo ($isEditable) ? 6 : 4; ?>" align="center">No products available for viewing.</td>
			      	</tr>

			      	<?php
				}
			  	?>
		    </table>
		    <br />

			<?php
			if($isEditable) {
				?>

				<table cellspacing="0" cellpadding="0" border="0" width="100%">
					<tr>
						<td align="left">
							<input type="submit" name="removeselectedproducts" value="remove selected" class="btn" />
							<input type="submit" name="updateproducts" value="update" class="btn" />
						</td>
						<td align="right"></td>
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

$page->Display('footer');
require_once('lib/common/app_footer.php');