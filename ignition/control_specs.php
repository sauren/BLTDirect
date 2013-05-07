<?php
ini_set('max_execution_time', '900');

require_once('lib/common/app_header.php');

if($action == 'report') {
	$session->Secure(3);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'Y', 'boolean', 1, 1, false);
	$form->AddField('specs', 'Specifications', 'selectmultiple', '', 'numeric_unsigned', 1, 11, true, 'size="15"');
	$form->AddGroup('specs', 'Y', 'Filterable');
	$form->AddGroup('specs', 'N', 'Non-Filterable');

	$data = new DataQuery(sprintf("SELECT Group_ID, Name, Is_Filterable FROM product_specification_group ORDER BY Name ASC"));
	while($data->Row) {
		$form->AddOption('specs', $data->Row['Group_ID'], $data->Row['Name'], $data->Row['Is_Filterable']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')){
		$specs = '';

		foreach($form->GetValue('specs') as $groupId) {
			$specs .= sprintf('&spec%d=Y', $groupId);
		}

		redirect(sprintf("Location: %s?action=report&cat=%d&sub=%s%s", $_SERVER['PHP_SELF'], $form->GetValue('parent'), $form->GetValue('subfolders'), $specs));
	}

	$page = new Page('Specification Control');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Specification control for products from a category.");
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow($form->GetLabel('subfolders'), $form->GetHtml('subfolders'));
	echo $webForm->AddRow($form->GetLabel('specs'), $form->GetHtml('specs'));
	echo $webForm->AddRow('','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $form->Close();
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataTable.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpec.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecValue.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/ProductSpecGroup.php');

	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cat', 'Category ID', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Subfolders', 'hidden', 'Y', 'boolean', 1, 1, false);

	$sqlFrom = '';
	$sqlWhere = '';

	if($form->GetValue('cat') != 0) {
		$sqlFrom = sprintf("INNER JOIN product_in_categories AS pic ON p.Product_ID=pic.Product_ID ");

		if($form->GetValue('sub') == 'Y') {
			$sqlWhere = sprintf("AND (pic.Category_ID=%d %s) ", mysql_real_escape_string($form->GetValue('cat')), mysql_real_escape_string(GetSubCategories($form->GetValue('cat'))));
		} else {
			$sqlWhere = sprintf("AND (pic.Category_ID=%d) ", mysql_real_escape_string($form->GetValue('cat')));
		}
	} else {
		if($form->GetValue('sub') == 'N') {
			$sqlFrom = sprintf("INNER JOIN product_in_categories AS pic ON p.Product_ID=pic.Product_ID ");
			$sqlWhere = sprintf("AND (pic.Category_ID=%d) ", mysql_real_escape_string($form->GetValue('cat')));
		}
	}

	$specs = array();

	foreach($_REQUEST as $key=>$value) {
		if(preg_match('/^spec([\d]*)$/', $key, $matches)) {
			if(count($matches) == 2) {
				if(is_numeric($matches[1])) {
					$specs[] = $matches[1];

					$form->AddField($key, 'Specification', 'hidden', $value, 'anything', 1, 255, false);
				}
			}
		}
	}

	$sqlWhere2 = (count($specs) > 0) ? sprintf(' AND (psv.Group_ID=%s)', mysql_real_escape_string(implode(' OR psv.Group_ID=', $specs))) : '';

	if(isset($_REQUEST['confirm']) && (strtolower($_REQUEST['confirm']) == 'true')) {
		if($form->Validate()) {
			$spec = new ProductSpec();
			$specValue = new ProductSpecValue();

			foreach($_REQUEST as $key=>$value) {
				if(preg_match('/^value_([\d]*)_([\d]*)$/', $key, $matches)) {
					if(count($matches) == 3) {
						if(is_numeric($matches[1]) && is_numeric($matches[2])) {
							if(strlen(trim($value)) > 0) {
								$data = new DataQuery(sprintf("SELECT Value_ID FROM product_specification_value AS psv WHERE psv.Value LIKE '%s' AND psv.Group_ID=%d", mysql_real_escape_string($value), mysql_real_escape_string($matches[2])));
								if($data->TotalRows > 0) {
									$spec->Value->ID = $data->Row['Value_ID'];
									$spec->Product->ID = $matches[1];
									$spec->Add();
								} else {
									$specValue->Group->ID = $matches[2];
									$specValue->Value = $value;
									$specValue->Add();

									$spec->Value->ID = $specValue->ID;
									$spec->Product->ID = $matches[1];
									$spec->Add();
								}
								$data->Disconnect();

								$data = new DataQuery(sprintf("SELECT ps.Specification_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID WHERE ps.Product_ID=%d AND psv.Group_ID=%d AND psv.Value_ID<>%d", mysql_real_escape_string($matches[1]), mysql_real_escape_string($matches[2]), mysql_real_escape_string($spec->Value->ID)));
								while($data->Row) {
									$data2 = new DataQuery(sprintf("DELETE FROM product_specification WHERE Specification_ID=%d", $data->Row['Specification_ID']));
									$data2->Disconnect();

									$data->Next();
								}
								$data->Disconnect();

							} else {
								$data = new DataQuery(sprintf("SELECT ps.Specification_ID FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID WHERE ps.Product_ID=%d AND psv.Group_ID=%d", mysql_real_escape_string($matches[1]), mysql_real_escape_string($matches[2])));
								if($data->TotalRows > 0) {
									$spec->Delete($data->Row['Specification_ID']);
								}
								$data->Disconnect();
							}
						}
					}
				}
			}

			$specStr = '';

			foreach($specs as $groupId) {
				$specStr .= sprintf('&spec%d=Y', $groupId);
			}

			redirect(sprintf("Location: %s?action=report&cat=%d&sub=%s%s", $_SERVER['PHP_SELF'], $form->GetValue('cat'), $form->GetValue('sub'), $specStr));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
		var copyDown = function(id, groupId) {
			var element = document.getElementById(id);
			var form = document.getElementById(\'form1\');
			var items = null;

			if(element && form) {
				for(var i=0; i<form.elements.length; i++) {
					items = form.elements[i].name.split(\'_\');

					if(items.length == 3) {
						if(items[2] == groupId) {
							form.elements[i].value = element.value;
						}
					}
				}
			}
		}
		</script>');

	$page = new Page('Specification Control');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo '<br />';
	}

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cat');
	echo $form->GetHTML('sub');

	for($i=0; $i<count($specs); $i++) {
		echo $form->GetHTML('spec'.$specs[$i]);
	}
	?>

	<br />
	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
		<thead>
			<tr>
				<th nowrap="nowrap"><strong>ID#</strong></th>
				<th nowrap="nowrap" class="dataHeadOrdered"><strong>Product Name</strong></th>

				<?php
				$group = new ProductSpecGroup();

				for($i=0; $i<count($specs); $i++) {
					if($group->Get($specs[$i])) {
						echo sprintf('<th nowrap="nowrap"><strong>%s</strong></th>', $group->Name);
					} else {
						unset($specs[$i]);
					}
				}
				?>

				<th width="1%">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td>&nbsp;</td>
				<td class="dataOrdered">&nbsp;</td>

				<?php
				for($i=0; $i<count($specs); $i++) {
					if(isset($specs[$i])) {
						echo sprintf('<td nowrap="nowrap"><input type="text" value="" name="copy_%s" id="copy_%s" size="10" maxlength="255" /> <a href="javascript:copyDown(\'copy_%s\', %s);"><img src="images/icon_pages_1.gif" border="0" height="15" width="14" alt="Copy down" /></a></td>', $specs[$i], $specs[$i], $specs[$i], $specs[$i]);
					}
				}
				?>

				<td>&nbsp;</td>
			</tr>

			<?php
			$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title FROM product AS p %s WHERE p.Discontinued<>'Y' AND p.Is_Active='Y' %s ORDER BY p.Product_Title ASC", mysql_real_escape_string($sqlFrom), mysql_real_escape_string($sqlWhere)));
			while($data->Row) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo $data->Row['Product_ID']; ?></td>
					<td class="dataOrdered"><?php echo strip_tags($data->Row['Product_Title']); ?></td>


					<?php
					$values = array();

					$data2 = new DataQuery(sprintf("SELECT psv.Group_ID, psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID WHERE ps.Product_ID=%d %s", $data->Row['Product_ID'], mysql_real_escape_string($sqlWhere2)));
					while($data2->Row) {
						$values[$data2->Row['Group_ID']] = $data2->Row['Value'];

						$data2->Next();
					}
					$data2->Disconnect();

					for($i=0; $i<count($specs); $i++) {
						if(isset($specs[$i])) {
							echo sprintf('<td><input type="text" value="%s" name="value_%s_%s" size="10" maxlength="255" /></td>', isset($values[$specs[$i]]) ? $values[$specs[$i]] : '', $data->Row['Product_ID'], $specs[$i]);
						}
					}
					?>

					<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>" target="_blank"><img width="16" height="16" src="images/icon_edit_1.gif" alt="View Product" border="0" /></td>
				</tr>

				<?php
				$data->Next();
			}
			$data->Disconnect();
			?>

		</tbody>
	</table><br />

	<?php
	echo '<input type="submit" class="btn" value="update" name="update" />';

	echo $form->Close();
}

function GetSubCategories($categoryId) {
	$sqlWhere = '';

	$data = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row) {
		$sqlWhere .= sprintf(' OR pic.Category_ID=%d', $data->Row['Category_ID']);
		$sqlWhere .= GetSubCategories($data->Row['Category_ID']);

		$data->Next();
	}
	$data->Disconnect();

	return $sqlWhere;
}
?>