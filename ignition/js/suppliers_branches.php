<?php
require_once('../lib/classes/ApplicationHeader.php');

$data = new  DataQuery("SELECT Branch_Name,Branch_ID FROM branch ORDER BY Branch_Name");
$branches = array();
while($data->Row){
	$branches[]=sprintf("%d,\"%s\"\n",$data->Row['Branch_ID'],utf8_encode(addslashes($data->Row['Branch_Name'])));
	$data->Next();
}
$data->Disconnect();

$data = new DataQuery("SELECT s.Supplier_ID, p.Name_First, p.Name_Last, o.Org_Name FROM supplier s
						INNER JOIN contact c on s.Contact_ID = c.Contact_ID
						INNER JOIN person p on p.Person_ID = c.Person_ID
						LEFT JOIN contact c2 on c2.Contact_ID = c.Parent_Contact_ID
						LEFT JOIN organisation o on c2.Org_ID = o.Org_ID
						ORDER BY Org_Name,Name_First,Name_Last;");
$suppliers = array();
while($data->Row){
	if(empty($data->Row['Org_Name'])){
		$suppliers[] = sprintf("%d, \"%s %s\"\n",$data->Row['Supplier_ID'],$data->Row['Name_First'],$data->Row['Name_Last']);
	}
	else{
		$suppliers[] = sprintf("%d, \"%s\"\n",$data->Row['Supplier_ID'],$data->Row['Org_Name']);
	}
	$data->Next();
}
$data->Disconnect();

header("Content-Type: text/html; charset=UTF-8");
?>
var branches= new Array(<?php echo implode(",", $branches); ?>);
var suppliers = new Array(<?php echo implode(",",$suppliers);?>);

function propogateChoice(str, obj){
	var warehouseType = document.getElementById(str);
	var choice = obj;
	warehouseType.options.length = 1;
	var n = 1;
	if(choice.value == 'B'){
		for(var i=0; i < branches.length; i+=2){
			// new Option('new text','new value');
			warehouseType.options[n++] = new Option(branches[i+1], branches[i]);
	
		}
		}else{
	for(var i=0; i < suppliers.length; i+=2){
		// new Option('new text','new value');
		warehouseType.options[n++] = new Option(suppliers[i+1], suppliers[i]);
		}
	}
	warehouseType.selectedIndex = 0;
	
	if(warehouseType.options.length == 1){
		warehouseType.disabled = true;
	} else {
		warehouseType.disabled = false;
	}
}
<?php
$GLOBALS['DBCONNECTION']->Close();
?>