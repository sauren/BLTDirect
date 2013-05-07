<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$session->Secure(2);
start();
exit();


function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$page = new Page('Unpreferred Suppliers Report', 'Please choose a category for your report');
	$year = cDatetime(getDatetime(), 'y');
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'Y', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()) {
			report($form->GetValue('parent'),($form->GetValue('subfolders') =='Y')?true:false);
			exit;
		}
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
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
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 300, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $form->Close();
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($cat,$sub){
	$page = new Page("Unpreferred Suppliers Report","Here you can see products with no preferred suppliers");
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');

	if($cat != 0) {
		if($sub) {
			$clientString = sprintf("AND (c.Category_ID=%d %s) ", mysql_real_escape_string($cat), mysql_real_escape_string(GetChildIDS($cat)));
		} else {
			$clientString = sprintf("AND c.Category_ID=%d ", mysql_real_escape_string($cat));
		}
	} else {
		if(!$sub) {
			$clientString = sprintf("AND (c.Category_ID IS NULL OR c.Category_ID=%d) ", mysql_real_escape_string($cat));
		}
	}

	$catName = new DataQuery(sprintf("Select Category_Title From product_categories Where Category_ID = %d ",mysql_real_escape_string($cat)));
	?>

	<h3><br />Products Without A Preferred Supplier</h3>
	<p>All products with no preferred suppliers in <?php echo ($cat == 0)?"the root ":"the ".$catName->Row['Category_Title']; echo ($sub)? "category and all of it's subfolders":"Category";?></p>

	<?php
	$catName->Disconnect();
	?>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Title</strong></td>
		<td style="border-bottom:1px solid #aaaaaa"><strong>SKU</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="center"><strong>Edit Suppliers</strong></td>
	  </tr>

	  <?php
		$data = new DataQuery(sprintf("SELECT p.Product_ID, p.Product_Title, p.SKU FROM product AS p INNER JOIN product_in_categories c ON p.Product_ID = c.Product_ID INNER JOIN supplier_product AS s ON p.Product_ID=s.Product_ID WHERE s.Preferred_Supplier='N' %s GROUP BY Product_ID", mysql_real_escape_string($clientString)));

		while($data->Row) {
			$data2 = new DataQuery(sprintf("SELECT COUNT(*) AS count FROM supplier_product WHERE Preferred_Supplier='Y' AND Product_ID=%d", $data->Row['Product_ID']));
			if($data2->Row['count'] == 0) {
				?>

				<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><?php echo strip_tags($data->Row['Product_Title']); ?></td>
					<td><?php echo $data->Row['SKU']; ?></td>
					<td align="right"><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']?>" target="_blank"><?php echo $data->Row['Product_ID']; ?></a></td>
					<td align="center"><a href="supplier_product.php?pid=<?php echo $data->Row['Product_ID']; ?>" target="_blank"><img src="./images/icon_edit_1.gif" alt="Edit the suppliers for this product" border="0"></a></td>
		  		</tr>

		  		<?php
			}
			$data2->Disconnect();
			$data->Next();
		}
	  ?>

	  </table>

	<?php
	$page->Display('footer');
}

function GetChildIDS($cat){
	$string = "";
	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID = %d",mysql_real_escape_string($cat)));
	while($children->Row){
		$string .= "OR c.Category_ID = ".$children->Row['Category_ID']." ";
		$string .= GetChildIDS($children->Row['Category_ID']);
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}
?>