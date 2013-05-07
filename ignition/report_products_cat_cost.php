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

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 6, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'N', 'boolean', NULL, NULL, false);
    $form->AddField('supplier', 'Supplier', 'select', '0', 'numeric_unsigned', 1, 11);
	$form->AddGroup('supplier', 'Y', 'Favourite Suppliers');
	$form->AddGroup('supplier', 'N', 'Standard Suppliers');
	$form->AddOption('supplier', '0', '');

	$data = new DataQuery(sprintf("SELECT s.Supplier_ID, CONCAT_WS(' ', p.Name_First, p.Name_Last, CONCAT('(', o.Org_Name, ')')) AS Supplier_Name, s.Is_Favourite FROM supplier AS s INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID INNER JOIN person AS p ON c.Person_ID=p.Person_ID LEFT JOIN contact AS c2 ON c2.Contact_ID=c.Parent_Contact_ID LEFT JOIN organisation AS o ON c2.Org_ID=o.Org_ID ORDER BY Supplier_Name ASC"));
	while($data->Row) {
		$form->AddOption('supplier', $data->Row['Supplier_ID'], $data->Row['Supplier_Name'], $data->Row['Is_Favourite']);

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		if($form->GetValue('parent') == 0) {
			$form->AddError('The Root category can not be selected for this report', 'parent');
		}

		if($form->Validate()) {
			report($form->GetValue('parent'),($form->GetValue('subfolders') =='Y')?true:false, $form->GetValue('supplier'));
			exit;
		}
	}

	$page = new Page('Product Category Costs Report', 'Please choose a category for your report');
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
	echo $webForm->AddRow($form->GetLabel('supplier'), $form->GetHtml('supplier').$form->GetIcon('supplier'));
	echo $webForm->AddRow('&nbsp','<input type="submit" name="submit" value="submit" class="btn" />');
	echo $form->Close();
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();
	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($cat, $sub, $supplierId) {
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Referrer.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Country.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Region.php');
	require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/User.php');

	$page = new Page('Product Category Costs Report');
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
	$catName = new DataQuery(sprintf("Select Category_Title From product_categories Where Category_ID = %d ", mysql_real_escape_string($cat)));
	?>
	<h3><br />
	Top Notch Products </h3>
	<p>Top 100  Products Sold in <?php echo ($cat == 0)?"the root ":"the ".$catName->Row['Category_Title']; echo ($sub)? "category and all of it's subfolders":"Category";?></p>

	<?php
	$catName->Disconnect();
	?>

	<table width="100%" border="0">
	  <tr>
		<td style="border-bottom:1px solid #aaaaaa"><strong>Product Name </strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Quickfind</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Preferred Supplier Name</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Preferred Supplier Cost</strong></td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>Our Price</strong> </td>
		<td style="border-bottom:1px solid #aaaaaa" align="right"><strong>% Markup</strong> </td>
	  </tr>
	  <?php
	  if($cat == 0){
	  	$top25 = new DataQuery(sprintf("select * From product WHERE Discontinued <> 'Y'"));
	  }else{
	  	$top25 = new DataQuery(sprintf("select p.* from product p INNER JOIN product_in_categories c ON p.Product_ID = c.Product_ID where Discontinued <> 'Y' %s", $clientString));
	  }
		$totMarkup = 0;
		$totProducts = 0;
	  while($top25->Row){
	  	if($cat == 0 && !$sub){
	  		$rootcheck = new DataQuery(sprintf("select * from product_in_categories WHERE Product_ID = %d",$top25->Row['Product_ID']));
	  		if($rootcheck->TotalRows == 0){
	  			//Get supplier details and price details;
	  			$supCheck = new DataQuery(sprintf('SELECT * FROM supplier_product AS sp INNER JOIN supplier AS s ON sp.Supplier_ID=s.Supplier_ID INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID WHERE sp.Product_ID=%1$d AND ((%2$d>0 AND sp.Supplier_ID=%2$d) OR (%2$d=0 AND sp.Preferred_Supplier=\'Y\'))', $top25->Row['Product_ID'], mysql_real_escape_string($supplierId)));
	  			if($supCheck->TotalRows == 0){
	  				$name = '-';
	  			}
	  			elseif($supCheck->Row['Parent_Contact_ID'] == 0){
	  				$nameFinder = new DataQuery(sprintf("SELECT * FROM person WHERE Person_ID = %d",$supCheck->Row['Person_ID']));
	  				$name = sprintf('%s %s %s %s',$nameFinder->Row['Name_Title'],$nameFinder->Row['Name_First'],$nameFinder->Row['Name_Initial'],$nameFinder->Row['Name_Last']);
					$nameFinder->Disconnect();
	  			}else{
	  				$nameFinder = new DataQuery(sprintf("SELECT * FROM organisation o INNER JOIN contact c ON c.Org_ID = o.Org_ID  WHERE c.Contact_ID =%d",$supCheck->Row['Parent_Contact_ID']));
	  			}
	  			$priceCheck = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc;",$top25->Row['Product_ID']));
	  			$markup = (!empty($supCheck->Row['Cost']))?round((($priceCheck->Row['Price_Base_Our']-$supCheck->Row['Cost'])/$supCheck->Row['Cost'])*100, 2):0;
	  			?>
				  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><a href="product_profile.php?pid=<?php echo $top25->Row['Product_ID']; ?>"><?php echo strip_tags($top25->Row['Product_Title']); ?></a></td>
					<td align="right"><?php echo $top25->Row['Product_ID']; ?></td>
					<td align="right"><?php echo $name ?></td>
					<td align="right"><?php echo (!empty($supCheck->Row['Cost']))?'&pound;'.number_format($supCheck->Row['Cost'], 2, '.', ','):'N/A'; ?></td>
					<td align="right">&pound;<?php echo number_format($priceCheck->Row['Price_Base_Our'], 2, '.', ','); ?></td>
					<td align="right"><?php echo (!empty($markup))?$markup:'N/A'; ?>%</td>
	  				</tr>
				<?php
				$totMarkup++;
				$totProducts++;
				$supCheck->Disconnect();
				$priceCheck->Disconnect();
	  		}
	  		$rootcheck->Disconnect();
	  	}else{
	//Get supplier details and price details;
	  			$supCheck = new DataQuery(sprintf('SELECT * FROM supplier_product AS sp INNER JOIN supplier AS s ON sp.Supplier_ID=s.Supplier_ID INNER JOIN contact AS c ON s.Contact_ID=c.Contact_ID WHERE sp.Product_ID=%1$d AND ((%2$d>0 AND sp.Supplier_ID=%2$d) OR (%2$d=0 AND sp.Preferred_Supplier=\'Y\'))', $top25->Row['Product_ID'], mysql_real_escape_string($supplierId)));
	  			if($supCheck->TotalRows == 0){
	  				$name = '-';
	  			}
	  			elseif($supCheck->Row['Parent_Contact_ID'] == 0){
	  				$nameFinder = new DataQuery(sprintf("SELECT * FROM person WHERE Person_ID = %d",$supCheck->Row['Person_ID']));
	  				$name = sprintf('%s %s %s %s',$nameFinder->Row['Name_Title'],$nameFinder->Row['Name_First'],$nameFinder->Row['Name_Initial'],$nameFinder->Row['Name_Last']);
					$nameFinder->Disconnect();
	  			}else{
	  				$nameFinder = new DataQuery(sprintf("SELECT * FROM organisation o INNER JOIN contact c ON c.Org_ID = o.Org_ID  WHERE c.Contact_ID =%d",$supCheck->Row['Parent_Contact_ID']));
	  				$name = $nameFinder->Row['Org_Name'];
	  				$nameFinder->Disconnect();
	  			}
	  			$priceCheck = new DataQuery(sprintf("SELECT * FROM product_prices WHERE Product_ID = %d AND Price_Starts_On <= now() Order By Price_Starts_On desc;",$top25->Row['Product_ID']));
	  			$markup = (!empty($supCheck->Row['Cost']) && ($supCheck->Row['Cost']  > 0))?round((($priceCheck->Row['Price_Base_Our']-$supCheck->Row['Cost'])/$supCheck->Row['Cost'])*100, 2):0;
	  			?>
				  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
					<td><a href="product_profile.php?pid=<?php echo $top25->Row['Product_ID']; ?>"><?php echo strip_tags($top25->Row['Product_Title']); ?></a></td>
					<td align="right"><?php echo $top25->Row['Product_ID']; ?></td>
					<td align="right"><?php echo $name ?></td>
					<td align="right"><?php echo (!empty($supCheck->Row['Cost']))?'&pound;'.number_format($supCheck->Row['Cost'], 2, '.', ','):'N/A'; ?></td>
					<td align="right">&pound;<?php echo number_format($priceCheck->Row['Price_Base_Our'], 2, '.', ','); ?></td>
					<td align="right"><?php echo (!empty($markup))?$markup.'%':'N/A'; ?></td>
	  				</tr>
				<?php
				if($markup != 0){
				$totMarkup += $markup;
				$totProducts++;
				}
				$supCheck->Disconnect();
				$priceCheck->Disconnect();
	  	}
	  	$top25->Next();
	  }
	  $top25->Disconnect();
	  ?>
	  </table><br><table width="100%"><tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
	  <td>Average Markup:</td><td align="right"><?php echo ($totProducts > 0) ? number_format($totMarkup/$totProducts,2,'.',',').'%' : 'N/A';?></td></tr></table>
	  <?php
	  $page->Display('footer');
}

function GetChildIDS($cat){
	//echo $cat;
	$string = "";
	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID = %d", mysql_real_escape_string($cat)));
	while($children->Row){
		$string .= "OR c.Category_ID = ".$children->Row['Category_ID']." ";
		$string .= GetChildIDS($children->Row['Category_ID']);
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}