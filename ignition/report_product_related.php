<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	
if($action == 'report') {
	$session->Secure(2);
	report();
	exit();
} else {
	$session->Secure(2);
	start();
	exit();
}

function start() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', 1, 1, false);

	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			redirect(sprintf('Location: ?action=report&parent=%d&subfolders=%s', $form->GetValue('parent'), $form->GetValue('subfolders')));
		}
	}

	$page = new Page('Product Related Report', 'Select report criteria.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Products Related.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Filter out products sold for particular orders.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Subfolders', 'hidden', 'N', 'boolean', 1, 1, false);
	$form->AddField('type', 'Type', 'select', '', 'paragraph', 0, 240, false, 'onchange="copyValue(this, \'type_\');"');
	$form->AddOption('type', '', '');
	$form->AddOption('type', 'Energy Saving Alternative', 'Energy Saving Alternative');
		
	$sqlSelect = sprintf('SELECT pr.Product_Related_ID, p.Product_ID, p.Product_Title, p2.Product_ID AS Related_Product_ID, p2.Product_Title AS Related_Product_Title, pr.Type ');
	$sqlFrom = sprintf('FROM product AS p INNER JOIN product_related AS pr ON pr.Related_To_Product_ID=p.Product_ID INNER JOIN product AS p2 ON p2.Product_ID=pr.Product_ID ');
	$sqlWhere = sprintf('WHERE p.Discontinued=\'N\' ');
	$sqlMisc = sprintf('ORDER BY p.Product_Title ASC');

	if($form->GetValue('parent') != 0) {
		$sqlFrom .= sprintf('INNER JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID ');

		if($form->GetValue('subfolders') == 'Y') {
			$sqlWhere .= sprintf('AND (c.Category_ID=%d %s) ', mysql_real_escape_string($form->GetValue('parent')), mysql_real_escape_string(getCategories($form->GetValue('parent'))));
		} else {
			$sqlWhere .= sprintf('AND c.Category_ID=%d ', mysql_real_escape_string($form->GetValue('parent')));
		}
	}
	
	$items = array();
	
	$data = new DataQuery(sprintf("%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlMisc));
	while($data->Row) {
		$items[] = $data->Row;
		
		$data->Next();
	}
	$data->Disconnect();
	
	foreach($items as $item) {
		$form->AddField('type_'.$item['Product_Related_ID'], 'Type', 'select', $item['Type'], 'paragraph', 0, 240, false);
		$form->AddOption('type_'.$item['Product_Related_ID'], '', '');
		$form->AddOption('type_'.$item['Product_Related_ID'], 'Energy Saving Alternative', 'Energy Saving Alternative');
	}
	
	if(isset($_REQUEST['confirm'])) {
		if($form->Validate()) {
			foreach($items as $item) {
				new DataQuery(sprintf("UPDATE product_related SET Type='%s' WHERE Product_Related_ID=%d", mysql_real_escape_string($form->GetValue('type_'.$item['Product_Related_ID'])), mysql_real_escape_string($item['Product_Related_ID'])));
			}
			
			redirectTo(sprintf('?action=report&parent=%d&subfolders=%s', $form->GetValue('parent'), $form->GetValue('subfolders')));
		}		
	}
	
	$script = sprintf('<script language="javascript" type="text/javascript">
		function copyValue(obj, match) {
			var e = document.getElementsByTagName(\'select\');
			
			for(var i=0; i<e.length; i++) {
				e[i].selectedIndex = obj.selectedIndex;
			}
		}
		</script>');
	
	$page = new Page('Product Related Report', '');
	$page->AddToHead($script);
	$page->Display('header');
	
	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');
	echo $form->GetHTML('subfolders'); 
	?>

	<br />
	<h3>Products</h3>
	<p>Listing products with relations.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Related Product ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Related Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Type</strong></td>
		</tr>
		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td><?php echo $form->GetHTML('type'); ?></td>
		</tr>
			
		<?php
		foreach($items as $item) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $item['Product_ID']; ?></td>
				<td><a href="product_profile.php?pid=<?php echo $item['Product_ID']; ?>"><?php echo $item['Product_Title']; ?></a></td>
				<td><?php echo $item['Related_Product_ID']; ?></td>
				<td><a href="product_profile.php?pid=<?php echo $item['Related_Product_ID']; ?>"><?php echo $item['Related_Product_Title']; ?></a></td>
				<td><?php echo $form->GetHTML('type_'.$item['Product_Related_ID']); ?></td>
			</tr>

			<?php
		}
		?>

	</table>
	<br />
	
	<input type="submit" class="btn" name="update" value="update" />	

	<?php
	echo $form->Close();
	
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function getCategories($categoryId) {
	$string = '';

	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row){
		$string .= sprintf("OR c.Category_ID=%d %s ", mysql_real_escape_string($data->Row['Category_ID']), mysql_real_escape_string(getCategories($data->Row['Category_ID'])));

		$data->Next();
	}
	$data->Disconnect();

	return $string;
}