<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

if($action == 'remove'){
	$session->Secure(3);
	remove();
	exit;
} elseif($action == 'add'){
	$session->Secure(3);
	add();
	exit;
} elseif($action == 'update'){
	$session->Secure(3);
	update();
	exit;
} elseif($action == 'products'){
	$session->Secure(3);
	products();
	exit;
} elseif($action == 'matrix'){
	$session->Secure(3);
	matrix();
	exit;
} else {
	$session->Secure(2);
	view();
	exit;
}

function remove(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

	if(isset($_REQUEST['value']) && is_numeric($_REQUEST['value'])){
		$value = new ProductSpecValue($_REQUEST['value']);
		$value->Delete();

		redirect(sprintf("Location: %s?group=%d", $_SERVER['PHP_SELF'], $value->Group->ID));
	}

	redirect(sprintf("Location: product_specs_groups.php"));
}

function add(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

	$group = new ProductSpecGroup($_REQUEST['group']);
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'add', 'alpha', 3, 3);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('value', 'Value', 'text', '', 'anything', 1, 60);
	$form->AddField('group', 'Group', 'hidden', $_REQUEST['group'], 'numeric_unsigned', 1, 11);
	$form->AddField('hide', 'Hide', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$value = new ProductSpecValue();
			$value->Value = $form->GetValue('value');
			$value->Group->ID = $form->GetValue('group');
			$value->Hide = $form->GetValue('hide');
			$value->Add();
			
			redirect(sprintf('Location: ?group=%d', $value->Group->ID));
		}
	}

	$page = new Page('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; <a href="product_specs_groups_values.php?group='.$form->GetValue('group').'">Product Specification Group Values</a> &gt; Add Specification','Manage global specification groups for your products');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Add a Product Specification Group Value.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('group');
	echo $window->Open();
	echo $window->AddHeader('Add specification group value');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $group->Units);
	echo $webForm->AddRow($form->GetLabel('hide'), $form->GetHTML('hide'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_specs_groups_values.php?group=%d\';"> <input type="submit" name="add" value="add" class="btn" tabindex="%s">', $_REQUEST['group'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function update(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');

	$value = new ProductSpecValue($_REQUEST['valueid']);
	$value->Group->Get();

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'update', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('valueid', 'Value ID', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('value', 'Value', 'text', $value->Value, 'anything', 1, 60);
	$form->AddField('hide', 'Hide', 'checkbox', $value->Hide, 'boolean', 1, 1, false);
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$value->Value = $form->GetValue('value');
			$value->Hide = $form->GetValue('hide');
			$value->Update();
			
			redirect(sprintf('Location: ?group=%d', $value->Group->ID));
		}
	}

	$page = new Page('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; <a href="product_specs_groups_values.php?group='.$value->Group->ID.'">Product Specification Group Values</a> &gt; Update Specification','Manage global specification groups for your products');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow("Update a Product Specification Group Value.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('action');
	echo $form->GetHTML('valueid');
	echo $window->Open();
	echo $window->AddHeader('Update specification group value');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('value'), $form->GetHTML('value') . $value->Group->Units);
	echo $webForm->AddRow($form->GetLabel('hide'), $form->GetHTML('hide'));
	echo $webForm->AddRow('', sprintf('<input type="button" name="back" value="back" class="btn" onClick="window.self.location=\'product_specs_groups_values.php?group=%d\';"> <input type="submit" name="update" value="update" class="btn" tabindex="%s">', $_REQUEST['group'], $form->GetTabIndex()));
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function products(){
	if(!isset($_REQUEST['group']) || ($_REQUEST['group'] == 0)) {
		redirect("Location: product_specs_groups.php");
	}

	if(!isset($_REQUEST['value']) || ($_REQUEST['value'] == 0)) {
		redirect("Location: product_specs_groups.php");
	}

	$page = new Page(sprintf('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; <a href="%s?group=%d">Product Specification Group Values</a> &gt; Products', $_SERVER['PHP_SELF'], $_REQUEST['group']), 'Products associated with this specirfication group.');
	$page->Display('header');

	$table = new DataTable("products");
	$table->SetExtractVars();
	$table->SetSQL(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID WHERE ps.Value_ID=%d", mysql_real_escape_string($_REQUEST['value'])));
	$table->AddField('ID#', 'Product_ID', 'right');
	$table->AddField('Product', 'Product_Title', 'left');
	$table->AddLink("product_profile.php?pid=%s", "<img src=\"./images/folderopen.gif\" alt=\"View Product\" border=\"0\">", "Product_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy("Product_Title");
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" name="manage" value="manage specification values" class="btn" onclick="window.location.href=\'%s?action=matrix&group=%d&value=%d\'" /> ', $_SERVER['PHP_SELF'], $_REQUEST['group'], $_REQUEST['value']);

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function matrix(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');

	if(!isset($_REQUEST['group']) || ($_REQUEST['group'] == 0)) {
		redirect("Location: product_specs_groups.php");
	}

	if(!isset($_REQUEST['value']) || ($_REQUEST['value'] == 0)) {
		redirect("Location: product_specs_groups.php");
	}

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'matrix', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('group', 'Group', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('value', 'Value', 'hidden', '', 'numeric_unsigned', 1, 11);

	$specification = array();
	$product = array();

	$data = new DataQuery(sprintf("SELECT psg.Group_ID, psg.Name, psv.Value_ID, psv.Value, CONCAT_WS(' ', psv.Value, psg.Units) AS UnitValue FROM product_specification_group AS psg INNER JOIN product_specification_value AS psv ON psg.Group_ID=psv.Group_ID WHERE psg.Is_Filterable='Y' ORDER BY psg.Sequence_Number, psv.Value ASC"));
	while($data->Row) {
		if(!isset($specification[$data->Row['Group_ID']])) {
			$specification[$data->Row['Group_ID']] = array('GroupID' => $data->Row['Group_ID'], 'Name' => $data->Row['Name'], 'Values' => array());
		}

		$specification[$data->Row['Group_ID']]['Values'][$data->Row['Value_ID']] = array('ValueID' => $data->Row['Value_ID'], 'Value' => $data->Row['Value'], 'UnitValue' => $data->Row['UnitValue']);

		$data->Next();
	}
	$data->Disconnect();

	$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, psg.Group_ID, psv.Value_ID FROM product AS p INNER JOIN product_specification AS ps ON ps.Product_ID=p.Product_ID AND ps.Value_ID=%d LEFT JOIN product_specification AS ps2 ON ps2.Product_ID=p.Product_ID LEFT JOIN product_specification_value AS psv ON ps2.Value_ID=psv.Value_ID LEFT JOIN product_specification_group AS psg ON psv.Group_ID=psg.Group_ID AND psg.Is_Filterable='Y' ORDER BY p.Product_Title ASC", mysql_real_escape_string($_REQUEST['value'])));
	while($data->Row) {
		if(!isset($product[$data->Row['Product_ID']])) {
			$product[$data->Row['Product_ID']] = array('ProductID' => $data->Row['Product_ID'], 'Name' => strip_tags($data->Row['Product_Title']), 'Specifications' => array());
		}

		if(!empty($data->Row['Group_ID'])) {
			$product[$data->Row['Product_ID']]['Specifications'][$data->Row['Group_ID']] = array('GroupID' => $data->Row['Group_ID'], 'ValueID' => $data->Row['Value_ID']);
		}

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			$spec = new ProductSpec();

			foreach($_REQUEST as $key=>$reqValueId) {
				if(preg_match('/spec_group_([0-9]*)_([0-9]*)/', $key, $matches)) {
					$reqProductId = $matches[1];
					$reqGroupId = $matches[2];

					foreach($product as $productId=>$productItem) {
						if($productId == $reqProductId) {

							foreach($specification as $groupId=>$specificationItem) {
								if($groupId == $reqGroupId) {

									if(!isset($productItem['Specifications'][$groupId]['ValueID'])) {
										if($reqValueId > 0) {
											$spec->Product->ID = $reqProductId;
											$spec->Value->ID = $reqValueId;
											$spec->Add();
										}
									} elseif($productItem['Specifications'][$groupId]['ValueID'] != $reqValueId) {
										if($reqValueId > 0) {
											new DataQuery(sprintf("UPDATE product_specification SET Value_ID=%d WHERE Product_ID=%d AND Value_ID=%d", mysql_real_escape_string($reqValueId), mysql_real_escape_string($reqProductId), mysql_real_escape_string($productItem['Specifications'][$groupId]['ValueID'])));
										} else {
											new DataQuery(sprintf("DELETE FROM product_specification WHERE Product_ID=%d AND Value_ID=%d", mysql_real_escape_string($reqProductId), mysql_real_escape_string($productItem['Specifications'][$groupId]['ValueID'])));
										}
										
										$product = new Product();
										$product->Get($reqProductId);
										$product->UpdateSpecCache();
									}
								}
							}

							break;
						}
					}
				}
			}

			redirect(sprintf("Location: %s?action=matrix&group=%d&value=%d", $_SERVER['PHP_SELF'], $form->GetValue('group'), $form->GetValue('value')));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var dropDown = function(obj, groupId) {
			var form = document.getElementById(\'form1\');
			var formElement = null;
			var parts = null;

			for(var i=0; i<form.elements.length; i++) {
				formElement = form.elements[i];

				switch(formElement.type) {
					case \'select-one\':
						if(formElement.name.length >= 10) {
							if(formElement.name.substring(0, 10) == \'spec_group\') {
								parts = formElement.name.split(\'_\');

								if(parts.length == 4) {
									if(parts[3] == groupId) {
										formElement.value = obj.value;
									}
								}
							}
						}

		        		break;
				}
			}
		}
		</script>');

	$page = new Page(sprintf('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; <a href="%s?group=%d">Product Specification Group Values</a> &gt; <a href="%s?action=products&group=%d&value=%d">Products</a> &gt; Manage Specifications', $_SERVER['PHP_SELF'], $_REQUEST['group'], $_SERVER['PHP_SELF'], $_REQUEST['group'], $_REQUEST['value']), 'Products associated with this specirfication group.');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('group');
	echo $form->GetHTML('value');
	?>

	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="DataTable">
		<thead>
			<tr>
				<th>ID#</th>
				<th>Product</th>

				<?php
				foreach($specification as $groupId=>$specificationItem) {
					echo sprintf('<th nowrap="nowrap"><a href="product_specs_groups_values.php?group=%d">%s</a></th>', $groupId, $specificationItem['Name']);
				}
				?>

			</tr>
		</thead>
		<tbody>

			<?php
			echo '<tr>';
			echo '<td>&nbsp;</td>';
			echo '<td>&nbsp;</td>';

			foreach($specification as $groupId=>$specificationItem) {
				echo '<td>';
				echo sprintf('<select name="spec_group_%d" onchange="dropDown(this, %d);">', $groupId, $groupId);
				echo '<option value="0"></option>';

				foreach($specificationItem['Values'] as $valueId=>$valueItem) {
					echo sprintf('<option value="%s">%s</option>', $valueId, $valueItem['UnitValue']);
				}

				echo '</select>';
				echo '</td>';
			}

			echo '</tr>';

			foreach($product as $productId=>$productItem) {
				echo '<tr>';
				echo sprintf('<td>%s</td>', $productId);
				echo sprintf('<td nowrap="nowrap"><a href="product_profile.php?pid=%d" target="_blank">%s</a></td>', $productId, $productItem['Name']);

				foreach($specification as $groupId=>$specificationItem) {
					echo '<td>';
					echo sprintf('<select name="spec_group_%d_%d">', $productId, $groupId);
					echo '<option value="0"></option>';

					foreach($specificationItem['Values'] as $valueId=>$valueItem) {
						echo sprintf('<option value="%s"%s>%s</option>', $valueId, ($valueId == $productItem['Specifications'][$groupId]['ValueID']) ? ' selected="selected"' : '', $valueItem['UnitValue']);
					}

					echo '</select>';
					echo '</td>';
				}

				echo '</tr>';
			}
			?>

		</tbody>
	</table><br />

	<input type="submit" name="update" value="update" class="btn" />

	<?php
	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function view() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Product.php');
	
	if(!isset($_REQUEST['group']) || ($_REQUEST['group'] == 0)) {
		redirect("Location: product_specs_groups.php");
	}
	
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'view', 'alpha', 4, 4);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('group', 'Group', 'hidden', '', 'numeric_unsigned', 1, 11);

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			if(isset($_REQUEST['primary'])) {
				$primaryValue = $_REQUEST['primary'];

				if(is_numeric($primaryValue)) {
					foreach($_REQUEST as $key=>$value) {
						if(preg_match('/merge_([0-9]+)/', $key, $matches)) {
							if(is_numeric($matches[1])) {
								$mergeValue = $matches[1];

								if($mergeValue != $primaryValue) {
									$productUpdates = array();
									
									$data = new DataQuery(sprintf("SELECT Product_ID FROM product_specification WHERE Value_ID=%d", mysql_real_escape_string($mergeValue)));
									while($data->Row) {
										$productUpdates[] = $data->Row['Product_ID'];
										
										$data->Next();
									}
									$data->Disconnect();
									
									new DataQuery(sprintf("UPDATE product_specification SET Value_ID=%d WHERE Value_ID=%d", mysql_real_escape_string($primaryValue), mysql_real_escape_string($mergeValue)));
									new DataQuery(sprintf("DELETE FROM product_specification_value WHERE Value_ID=%d", mysql_real_escape_string($mergeValue)));
									
									foreach($productUpdates as $productId) {
										$product = new Product();
										$product->Get($productId);
										$product->UpdateSpecCache();
									}
								}
							}
						}
					}
				}
			}
			
			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/hide_([0-9]+)/', $key, $matches)) {
					if(is_numeric($matches[1])) {
						$spec = new ProductSpecValue();
						
						if($spec->Get($matches[1])) {
							$spec->Hide = $value;
							$spec->Update();
						}
					}
				}
			}

			redirect(sprintf("Location: %s?group=%d", $_SERVER['PHP_SELF'], $form->GetValue('group')));
		}
	}

	$page = new Page('<a href="product_specs_groups.php">Product Specification Groups</a> &gt; Product Specification Group Values', 'Manage global specification group values for your products');
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('group');

	$table = new DataTable('values');
	$table->SetSQL(sprintf("SELECT Value_ID, Value, Hide FROM product_specification_value WHERE Group_ID=%d", mysql_real_escape_string($_REQUEST['group'])));
	$table->AddField('ID#', 'Value_ID', 'right');
	$table->AddField('Value', 'Value', 'left');
	$table->AddInput('Hide', 'Y', 'Hide', 'hide', 'Value_ID', 'select');
	$table->AddInputOption('Hide', 'Y', 'Yes');
	$table->AddInputOption('Hide', 'N', 'No');
	$table->AddInput('Primary', 'Y', 'Value_ID', 'primary', '', 'radio');
	$table->AddInput('Merge', 'N', 'N', 'merge', 'Value_ID', 'checkbox');
	$table->AddLink("product_specs_groups_value_rewrites.php?valueid=%s", "<img src=\"images/i_document.gif\" alt=\"Rewrites\" border=\"0\">", "Value_ID");
	$table->AddLink("product_image_specs.php?valueid=%s", "<img src=\"images/i_picture.gif\" alt=\"Product Images\" border=\"0\">", "Value_ID");
	$table->AddLink("product_specs_groups_value_images.php?valueid=%s", "<img src=\"images/i_picture.gif\" alt=\"Landing Images\" border=\"0\">", "Value_ID");
	$table->AddLink("product_specs_groups_values.php?action=update&valueid=%s&group=".$_REQUEST['group'], "<img src=\"images/icon_edit_1.gif\" alt=\"Update Value\" border=\"0\">", "Value_ID");
	$table->AddLink("product_specs_groups_values.php?action=products&value=%s&group=".$_REQUEST['group'], "<img src=\"images/folderopen.gif\" alt=\"View Products\" border=\"0\">", "Value_ID");
	$table->AddLink("javascript:confirmRequest('product_specs_groups_values.php?action=remove&confirm=true&value=%s&group=".$_REQUEST['group']."','Are you sure you want to remove this product specification title/value pair?');", "<img src=\"images/aztector_6.gif\" alt=\"Remove\" border=\"0\">", "Value_ID");
	$table->SetMaxRows(25);
	$table->SetOrderBy('Value');
	$table->Finalise();
	$table->DisplayTable();
	echo '<br />';
	$table->DisplayNavigation();

	echo '<br />';
	echo sprintf('<input type="button" name="add" value="add specification value" class="btn" onclick="window.location.href=\'product_specs_groups_values.php?action=add&group=%d\'" /> ', $_REQUEST['group']);
	echo sprintf('<input type="submit" name="update" value="update" class="btn" /> ');

	echo $form->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}