<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');

if($action == 'report') {
	$session->Secure(2);
	report();
	exit;	
} else {
	$session->Secure(2);
	start();
	exit;
}

function start() {
	$form = new Form($_SERVER['PHP_SELF'], 'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 5);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm'])) {
		redirectTo(sprintf('?action=report&parent=%d&subfolders=%s', $form->GetValue('parent'), $form->GetValue('subfolders')));
	}

	$page = new Page('Products Related Stats Report', 'Select your report criteria.');
	$page->Display('header');

	if(!$form->Valid){
		echo $form->GetError();
		echo '<br />';
	}

	$window = new StandardWindow("Report on Products from a Category.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('parent') . '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>', '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
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
	$form->AddField('subfolders', 'Include Subfolders?', 'hidden', 'N', 'boolean', NULL, NULL, false);

	$page = new Page('Products Related Stats Report', '');
	$page->Display('header');

	$sqlSelect = sprintf("SELECT p.Product_ID, p.SKU, p.Product_Title, pr.Count AS Related, pr2.Count AS RelatedEnergy, pl.count AS SimilarLinks, pc.Category_Title ");
	$sqlFrom = sprintf("FROM product AS p LEFT JOIN (SELECT Related_To_Product_ID, COUNT(*) AS Count FROM product_related WHERE Type='' GROUP BY Related_To_Product_ID) AS pr ON pr.Related_To_Product_ID=p.Product_ID LEFT JOIN (SELECT Related_To_Product_ID, COUNT(*) AS Count FROM product_related WHERE Type='Energy Saving Alternative' GROUP BY Related_To_Product_ID) AS pr2 ON pr2.Related_To_Product_ID=p.Product_ID LEFT JOIN (SELECT productId, COUNT(*) AS count FROM product_link GROUP BY productId) AS pl ON pl.productId=p.Product_ID LEFT JOIN product_in_categories AS c2 ON c2.Product_ID=p.Product_ID LEFT JOIN product_categories AS pc ON pc.Category_ID=c2.Category_ID ");
	$sqlWhere = sprintf("WHERE TRUE ");
	$sqlGroup = sprintf("GROUP BY p.Product_ID ");
	$sqlOrder = sprintf("ORDER BY pc.Category_Title ASC, p.Product_Title ASC ");

	if($form->GetValue('parent') != 0) {
		$sqlFrom .= sprintf("INNER JOIN product_in_categories AS c ON c.Product_ID=p.Product_ID ");

		if($form->GetValue('subfolders') == 'Y') {
			$sqlWhere .= sprintf("AND (c.Category_ID=%d %s) ", mysql_real_escape_string($form->GetValue('parent')), mysql_real_escape_string(getCategories($form->GetValue('parent'))));
		} else {
			$sqlWhere .= sprintf("AND c.Category_ID=%d ", mysql_real_escape_string($form->GetValue('parent')));
		}
	}
	?>

	<br />
	<h3>Product Stats</h3>
	<p>Listing products matching the criteria specified</p>

	<table width="100%" border="0">
		<tr>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>SKU</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Quickfind</strong></td>
			<td style="border-bottom:1px solid #aaaaaa"><strong>Category</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Related</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Energy Saving Alternatives</strong></td>
			<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Similar Links</strong></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("%s%s%s%s%s", $sqlSelect, $sqlFrom, $sqlWhere, $sqlGroup, $sqlOrder));
		while($data->Row) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo strip_tags($data->Row['Product_Title']); ?></a></td>
				<td><?php echo $data->Row['SKU']; ?></td>
				<td><?php echo $data->Row['Product_ID']; ?></td>
				<td><?php echo $data->Row['Category_Title']; ?></td>
				<td align="right"><?php echo $data->Row['Related']; ?></td>
				<td align="right"><?php echo $data->Row['RelatedEnergy']; ?></td>
				<td align="right"><?php echo $data->Row['SimilarLinks']; ?></td>
			</tr>
			
			<?php
			$data->Next();
		}
		$data->Disconnect();
		?>

	</table>

	<?php
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
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