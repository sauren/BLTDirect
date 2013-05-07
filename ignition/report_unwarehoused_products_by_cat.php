<?php
require_once('lib/common/app_header.php');

$session->Secure(2);
start();
exit();

function start(){
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	$page = new Page('Unwarehoused Products by Category Report', 'Please choose a category for your report');
	$year = cDatetime(getDatetime(), 'y');
	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->GetValue('parent') == 0){
			$form->AddError('The Root category can not be selected for this report', 'parent');
		}
		if($form->Validate()){
			// Hurrah! Create a new entry.
			report($form->GetValue('parent'),($form->GetValue('subfolders') =='Y')?true:false);
			exit;
		}
	}

	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br />";
	}
	$window = new StandardWindow("Report on Unwarehoused Products by Category.");
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
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$page = new Page('Unwarehoused Products by Category Report');
	$page->AddToHead('<script language="javascript" src="js/scw.js" type="text/javascript"></script>');
$page->Display('header');
	//$orders = new DataQuery(sprintf("select count(Order_ID) as OrderCount, Order_Prefix, sum(SubTotal) as SubTotal, sum(TotalShipping) as TotalShipping, sum(TotalTax) as TotalTax, sum(Total) as Total from orders where Created_On between '%s' and '%s' and Status != 'Cancelled' group by Order_Prefix", $start, $end));
	if($cat !=0) {
		if($sub) {
			$clientString = sprintf(" (Category_ID = %d %s) ", mysql_real_escape_string($cat), mysql_real_escape_string(GetChildIDS($cat)));
		} else {
			$clientString = sprintf(" Category_ID = %d ", mysql_real_escape_string($cat));
		}
	}
	$catName = new DataQuery(sprintf("Select * From product_categories Where Category_ID = %d ",mysql_real_escape_string($cat)));
	?>
	<h3><br />
	Products Without A Warehouse</h3>
	<p>Products that are not stored in a warehouse that belong to <?php echo ($cat == 0)?"the root ":"the ".$catName->Row['Category_Title'];$catName->Disconnect();echo ($sub)? " category and all of it's subfolders":" category";?>
	</p>
	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name </strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quickfind</strong></td>
	  </tr>
	  <?php
	  // Sales by Product - top 25 sold
	  //echo sprintf("select sum(ol.Quantity) as OrderCount, ol.Product_ID, ol.Product_Title, sum(ol.Line_Total) as Total from order_line as ol inner join orders as o on ol.Order_ID=o.Order_ID where o.Created_On between '%s' and '%s' and Line_Status != 'Cancelled' group by ol.Product_ID order by OrderCount desc limit 500", $start, $end);
	  if($cat == 0){
	  	$top25 = new DataQuery(sprintf("select * From product WHERE Discontinued <> 'Y'"));
	  }else{
	  	$top25 = new DataQuery(sprintf("select p.* from product p INNER JOIN product_in_categories c ON p.Product_ID = c.Product_ID where Discontinued <> 'Y' AND %s", $clientString));
	  }
	  while($top25->Row){
	  	$supCheck = new DataQuery(sprintf("SELECT * FROM warehouse_stock ws INNER JOIN warehouse w ON ws.Warehouse_ID = w.Warehouse_ID WHERE Product_ID = %d",$top25->Row['Product_ID']));
	  	if($supCheck->TotalRows == 0){
	  			?>
				  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><a href="product_profile.php?pid=<?php echo $top25->Row['Product_ID']?>"><?php echo strip_tags($top25->Row['Product_Title']); ?></a></td>
					<td align="right"><?php echo $top25->Row['Product_ID']; ?></td>
	  				</tr>
				<?php
	  	}
		$supCheck->Disconnect();
		$top25->Next();
	  }
	  $top25->Disconnect();
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