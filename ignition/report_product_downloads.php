<?php
require_once('lib/common/app_header.php');
ini_set('display_errors', true);
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

function start(){
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

	$page = new Page('Product Downloads Report', 'Select report criteria.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Products Downloads.");
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
	
	$page = new Page('Product Downloads Report', '');
	$page->Display('header');

	$sqlSelect = sprintf('SELECT p.Product_ID, p.Product_Title, COUNT(DISTINCT pd.id) AS Download_Count, p.Position_Quantities_Recent ');
	$sqlFrom = sprintf('FROM product AS p LEFT JOIN product_download AS pd ON pd.productId=p.Product_ID ');
	$sqlWhere = sprintf('WHERE p.Discontinued=\'N\' AND p.Position_Quantities_Recent>0 ');
	$sqlMisc = sprintf('GROUP BY p.Product_ID ORDER BY p.Position_Quantities_Recent ASC');

	if($form->GetValue('parent') != 0) {
		$sqlFrom .= sprintf('INNER JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID ');

		if($form->GetValue('subfolders') == 'Y') {
			$sqlWhere .= sprintf('AND (c.Category_ID=%d %s) ', mysql_real_escape_string($form->GetValue('parent')), mysql_real_escape_string(getCategories($form->GetValue('parent'))));
		} else {
			$sqlWhere .= sprintf('AND c.Category_ID=%d ', mysql_real_escape_string($form->GetValue('parent')));
		}
	}
	?>

	<br />
	<h3>Products</h3>
	<p>Listing products with download counts.</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product ID</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Downloads</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Position</strong></td>
		</tr>

		<?php
		$totalDownloads = 0;

		$data = new DataQuery(sprintf("%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlMisc));
		while($data->Row) {
			$totalDownloads += $data->Row['Download_Count'];
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo $data->Row['Product_ID']; ?></td>
				<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo strip_tags($data->Row['Product_Title']); ?></a></td>
				<td align="right"><?php echo $data->Row['Download_Count']; ?></td>
				<td align="right"><?php echo $data->Row['Position_Quantities_Recent']; ?></td>
			</tr>

			<?php
			$data->Next();
		}
		$data->Disconnect();
		?>

		<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td align="right"><strong><?php echo $totalDownloads; ?></strong></td>
			<td>&nbsp;</td>
		</tr>
	</table>

	<?php
	$page->Display('footer');
}

function getCategories($categoryId) {
	$string = '';

	$data = new DataQuery(sprintf("SELECT Category_ID FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($categoryId)));
	while($data->Row){
		$string .= sprintf("OR c.Category_ID=%d %s ", $data->Row['Category_ID'], getCategories($data->Row['Category_ID']));

		$data->Next();
	}
	$data->Disconnect();

	return $string;
}