<?php
require_once('../../../ignition/lib/classes/ApplicationHeader.php');

$regions = array();

$data = new DataQuery("SELECT c.Country_ID, r.Region_ID, r.Region_Name FROM countries AS c INNER JOIN regions AS r ON c.Country_ID=r.Country_ID WHERE Allow_Sales='Y' ORDER BY r.Region_Name ASC");
while($data->Row){
	$regions[] = sprintf("%d, %d, '%s'", $data->Row['Country_ID'], $data->Row['Region_ID'], utf8_encode(addslashes($data->Row['Region_Name'])));
	$data->Next();
}
$data->Disconnect();

header("Content-Type: text/html; charset=UTF-8");
?>
var regions = new Array(<?php echo implode(', ', $regions); ?>);

function propogateRegions(target, country){
	var region = document.getElementById(target);
	var n = 1;

	region.options.length = 1;

	for(var i=0; i < regions.length; i+=3){
		if(country.options[country.selectedIndex].value == regions[i]){
			region.options[n++] = new Option(regions[i+2], regions[i+1]);
		}
	}

	region.selectedIndex = 0;

	if(region.options.length == 1) {
		region.disabled = true;
	} else {
		region.disabled = false;
	}
}
<?php
$GLOBALS['DBCONNECTION']->Close();
?>