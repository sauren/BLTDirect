<?php
ini_set('max_execution_time', '900');

require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardWindow.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/StandardForm.php');

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
	$page = new Page('Product Weight Control');

	$form = new Form($_SERVER['PHP_SELF'],'GET');
	$form->AddField('action', 'Action', 'hidden', 'start', 'alpha', 5, 6);
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('parent', 'Category', 'hidden', '0', 'numeric_unsigned', 1, 11);
	$form->AddField('subfolders', 'Include Subfolders?', 'checkbox', 'Y', 'boolean', NULL, NULL, false);

	if(isset($_REQUEST['confirm']) && strtolower($_REQUEST['confirm']) == "true"){
		report($form->GetValue('parent'), $form->GetValue('subfolders'));
		exit;
	}

	$page->Display('header');
	if(!$form->Valid){
		echo $form->GetError();
		echo "<br>";
	}
	$window = new StandardWindow("Weight control for Products from a Category.");
	$webForm = new StandardForm();

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
	echo $form->Close();
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	$page->Display('footer');
	require_once('lib/common/app_footer.php');
}

function report($cat = 0, $sub = 'Y') {
	$form = new Form($_SERVER['PHP_SELF']);
	$form->AddField('action', 'Action', 'hidden', 'report', 'alpha', 1, 12);
	$form->SetValue('action', 'report');
	$form->AddField('confirm', 'Confirm', 'hidden', 'true', 'alpha', 4, 4);
	$form->AddField('cat', 'Category ID', 'hidden', $cat, 'numeric_unsigned', 1, 11);
	$form->AddField('sub', 'Include Sub Categories', 'hidden', $sub, 'boolean', 1, 1, false);
	$form->AddField('weight', 'Weight', 'text', '', 'float', 0, 11, false);

	if(isset($_REQUEST['sub'])) {
		$form->SetValue('sub', ($_REQUEST['sub']) ? 'Y' : 'N');
	}

	$sub = ($form->GetValue('sub') == 'Y') ? true : false;
	$cat = $form->GetValue('cat');

	$clientString = "";

	if($cat != 0) {
		if($sub) {
			$clientString = sprintf("AND (c.Category_ID=%d %s)", mysql_real_escape_string($cat), mysql_real_escape_string(GetChildIDS($cat)));
		} else {
			$clientString = sprintf("AND (c.Category_ID=%d)", mysql_real_escape_string($cat));
		}
	} else {
		if(!$sub) {
			$clientString = sprintf("AND (c.Category_ID=%d)", mysql_real_escape_string($cat));
		}
	}

	$stock = array();

	$data = new DataQuery(sprintf("SELECT p.Product_Title, p.Product_ID, p.SKU, p.Weight FROM product AS p LEFT JOIN product_in_categories AS c ON p.Product_ID=c.Product_ID WHERE p.Discontinued<>'Y' AND p.Weight=0 %s ORDER BY p.Product_ID ASC", mysql_real_escape_string($clientString)));
	while($data->Row) {
		$stockItem = array();
		$stockItem['id'] = $data->Row['Product_ID'];
		$stockItem['name'] = strip_tags($data->Row['Product_Title']);
		$stockItem['sku'] = $data->Row['SKU'];
		$stockItem['weight'] = $data->Row['Weight'];

		if(!is_null($stockItem['weight'])) {
			$form->AddField('weight_'.$stockItem['id'], 'Product Weight', 'text', $stockItem['weight'], 'float', 0, 11, true, 'size="5"');
		}

		$stock[] = $stockItem;

		$data->Next();
	}
	$data->Disconnect();

	if(isset($_REQUEST['action']) && ($_REQUEST['action'] == "report") && (isset($_REQUEST['confirm']))) {
		if($form->Validate()) {
			for($i = 0; $i < count($stock); $i++) {
				if($stock[$i]['weight'] != $form->GetValue('weight_'.$stock[$i]['id'])) {
					$data = new DataQuery(sprintf("UPDATE product SET Weight=%f WHERE Product_ID=%d", mysql_real_escape_string($form->GetValue('weight_'.$stock[$i]['id'])), mysql_real_escape_string($stock[$i]['id'])));
					$data->Disconnect();
				}
			}

			redirect(sprintf("Location: %s?action=report&cat=%s&sub=%s", $_SERVER['PHP_SELF'], $cat, $sub));
		}
	}

	$script = sprintf('<script language="javascript" type="text/javascript">
	function copyDown() {
		var weight = document.getElementById(\'weight\');
		var form = document.getElementById(\'form1\');
		var formElement = null;

		if(weight && form) {
			for(var i=0; i<form.elements.length; i++) {
				formElement = form.elements[i];

				switch(formElement.type) {
					case \'text\':
						formElement.value = weight.value;
						break;
				}
			}
		}
	}
	</script>');

	$page = new Page('Product Weight Control');
	$page->AddToHead($script);
	$page->Display('header');

	if(!$form->Valid) {
		echo $form->GetError();
		echo "<br />";
	}

	$window = new StandardWindow('Sort products');
	$webForm = new StandardForm();

	echo $form->Open();
	echo $form->GetHTML('action');
	echo $form->GetHTML('confirm');
	echo $form->GetHTML('cat');
	echo $form->GetHTML('sub');

	echo $window->Open();
	echo $window->OpenContent();
	echo $webForm->Open();
	echo $webForm->AddRow($form->GetLabel('weight'), $form->GetHTML('weight'));
	echo $webForm->AddRow('','<input type="button" name="copydown" value="copy down" class="btn" onclick="copyDown();" />');
	echo $webForm->Close();
	echo $window->CloseContent();
	echo $window->Close();

	echo "<br />";
	?>
	<br />
	<table align="center" cellpadding="4" cellspacing="0" class="DataTable">
	   <thead>
		  <tr>
			<th nowrap><strong>Product Name</strong></td>
			<th nowrap><strong>Part Number (SKU)</strong></td>
			<th class="dataHeadOrdered" nowrap align="center"><strong>Quickfind</strong></td>
			<th nowrap align="right"><strong>Weight</strong></td>
		 </tr>
	   </thead>
	   <tbody>
		  <?php
		  for($i = 0; $i < count($stock); $i++) {
		  	?>
		  	<tr class="dataRow" onMouseOver="setClassName(this, 'dataRowOver');" onMouseOut="setClassName(this, 'dataRow');">
		  	<td><a href="product_profile.php?pid=<?php echo $stock[$i]['id']; ?>" target="_blank"><?php echo $stock[$i]['name']; ?></a></td>
		  	<td><?php echo $stock[$i]['sku']; ?></td>
		  	<td class="dataOrdered" align="center"><?php echo $stock[$i]['id']; ?></td>
		  	<td align="right"><?php echo $form->GetHTML('weight_'.$stock[$i]['id']); ?></td>
		  	</tr>
			 <?php
		  }
		  ?>
		  </tbody>
	</table>

	<br />

	<input type="submit" class="btn" value="update" name="report" />

	<?php
	echo $form->Close();
}

function GetChildIDS($cat) {
	$string = "";
	$children = new DataQuery(sprintf("SELECT * FROM product_categories WHERE Category_Parent_ID=%d", mysql_real_escape_string($cat)));
	while($children->Row) {
		$string .= "OR c.Category_ID=".mysql_real_escape_string($children->Row['Category_ID'])." ";
		$string .= mysql_real_escape_string(GetChildIDS($children->Row['Category_ID']));
		$children->Next();
	}
	$children->Disconnect();
	return $string;
}
?>