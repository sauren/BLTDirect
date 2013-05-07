<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

	$page = new Page('Weight Report', 'Please choose a start and end date for your report');
	$form = new Form($_SERVER['PHP_SELF'], 'get');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'Y', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->Validate()){
			report($form->GetValue('parent'), $form->GetValue('subfolders'));
			exit;
		}
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}

	$window = new StandardWindow("Report on Weights.");
	$webForm = new StandardForm;

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('parent');

	echo $window->Open();
	echo $window->AddHeader('Click on a the search icon to find a category to report on.');
	echo $window->OpenContent();
	echo $webForm->Open();
	$temp_1 = '<a href="javascript:popUrl(\'product_categories.php?action=getnode\', 600, 400);"><img src="images/icon_search_1.gif" width="16" height="16" align="absmiddle" border="0" alt="Search"></a>';
	echo $webForm->AddRow($form->GetLabel('parent') . $temp_1, '<span id="parentCaption">_root</span>');
	echo $webForm->AddRow('', $form->GetHtml('subfolders') . ' ' . $form->GetLabel('subfolders'));
	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	echo $form->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($cat = 0, $sub = true){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

	$clientString = "";

	if($cat != 0) {
		if($sub) {
			$clientString = sprintf("AND (c.Category_ID=%d %s)", $cat, GetChildIDS($cat));
		} else {
			$clientString = sprintf("AND (c.Category_ID=%d)", $cat);
		}
	} else {
		if(!$sub) {
			$clientString = sprintf("AND (c.Category_ID=%d)", $cat);
		}
	}

	$page = new Page('Weight Report', '');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	?>

	<br />
	<h3>Heaviest Products</h3>
	<p>The following are the heaviest products with weights above zero.</p>

	<table width="100%" border="0" >
		<tr>
			<th style="border-bottom:1px solid #aaaaaa" align="left"><strong>Product Name</strong></td>
			<th style="border-bottom:1px solid #aaaaaa" align="left"><strong>Part Number (SKU)</strong></td>
			<th style="border-bottom:1px solid #aaaaaa" align="center"><strong>Quickfind</strong></td>
			<th style="border-bottom:1px solid #aaaaaa" align="right"><strong>Weight</strong></td>
		</tr>

		<?php
		$data = new DataQuery(sprintf("SELECT p.Product_Title, p.Product_ID, p.SKU, p.Weight FROM product AS p LEFT JOIN product_in_categories AS c ON p.Product_ID=c.Product_ID WHERE p.Discontinued<>'Y' AND p.Weight>0 %s ORDER BY p.Weight DESC", mysql_real_escape_string($clientString)));
		while($data->Row) {
			?>

			<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
				<td><?php echo strip_tags($data->Row['Product_Title']); ?></td>
				<td><?php echo $data->Row['SKU']; ?></a></td>
				<td align="center"><a href="product_profile.php?pid=<?php echo $data->Row['Product_ID']; ?>"><?php echo $data->Row['Product_ID']; ?></a></td>
				<td align="right"><?php echo $data->Row['Weight']; ?></td>
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

function GetChildIDS($cat) {
	$string = "";
	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($cat)));
	while($children->Row) {
		$string .= "OR c.Category_ID=".$children->Row['Category_ID']." ";
		$string .= GetChildIDS($children->Row['Category_ID']);
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}
?>