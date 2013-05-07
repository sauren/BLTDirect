<?php
require_once('../classes/ApplicationHeader.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Category.php');
if (!function_exists('json_encode'))
{
    function json_encode($a=false)
    {
        if (is_null($a)) return 'null';
        if ($a === false) return 'false';
        if ($a === true) return 'true';
        if (is_scalar($a))
        {
            if (is_float($a))
            {
                // Always use "." for floats.
                return floatval(str_replace(",", ".", strval($a)));
            }

            if (is_string($a))
            {
                static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
            }
            else
            return $a;
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a))
        {
            if (key($a) !== $i)
            {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList)
        {
            foreach ($a as $v) $result[] = json_encode($v);
            return '[' . join(',', $result) . ']';
        }
        else
        {
            foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
            return '{' . join(',', $result) . '}';
        }
    }
}
$values = isset($_REQUEST['values']) ? explode(',', $_REQUEST['values']) : array();
$combinations = isset($_REQUEST['combinations']) ? explode(',', $_REQUEST['combinations']) : array();
$matches = 0;

$json = new stdClass;

if(!empty($values)) {
	$sqlSelect = sprintf('SELECT COUNT(*) AS TotalRows ');
	$sqlFrom = sprintf('FROM product AS p ');
	$sqlWhere = sprintf('WHERE p.Is_Active=\'Y\' AND p.Is_Demo_Product=\'N\' AND p.Discontinued=\'N\' AND p.Integration_ID=0 AND ((NOW() BETWEEN p.Sales_Start AND p.Sales_End) OR (p.Sales_Start=\'0000-00-00 00:00:00\' AND p.Sales_End=\'0000-00-00 00:00:00\') OR (p.Sales_Start=\'0000-00-00 00:00:00\' AND p.Sales_End>NOW()) OR (p.Sales_Start<=NOW() AND p.Sales_End=\'0000-00-00 00:00:00\')) ');
	
	$index = 0;
	
	foreach($values as $value) {
		if($value > 0) {
			$index++;

			$sqlFrom .= sprintf("INNER JOIN product_specification AS ps%d ON ps%d.Product_ID=p.Product_ID AND ps%d.Value_ID=%d ", mysql_real_escape_string($index), mysql_real_escape_string($index), mysql_real_escape_string($index), mysql_real_escape_string($value));
		}
	}
	
	foreach($combinations as $combination) {
		if($combination > 0) {
			$index++;

			$sqlFrom .= sprintf('INNER JOIN product_specification AS ps%1$d ON ps%1$d.Product_ID=p.Product_ID INNER JOIN product_specification_combine_value AS pscv%1$d ON pscv%1$d.productSpecificationValueId=ps%1$d.Value_ID AND pscv%1$d.productSpecificationCombineId=%2$d ', mysql_real_escape_string($index), mysql_real_escape_string($combination));
		}
	}

	$data = new DataQuery($sqlSelect.$sqlFrom.$sqlWhere);
	$matches = $data->Row['TotalRows'];
	$data->Disconnect();
}

// Value query
$valueQuery = "select psv.Group_ID, psg.Name, psv.Value_ID, psv.Value, count(*) as Total 
from product_specification as ps
inner join product_specification_value as psv on psv.Value_ID=ps.Value_ID
inner join product_specification_group as psg on psg.Group_ID=psv.Group_ID and psv.Group_ID in (223,224)
where ps.Product_ID in (
SELECT p.Product_ID 
{$sqlFrom}
{$sqlWhere}
)
group by psv.Value_ID";
$data = new DataQuery($valueQuery);
$valueArray = array();
while($data->Row){
	$valueArray[] = $data->Row;
	$data->Next();
}
$data->Disconnect();
$json->values = $valueArray;

$combineQuery = "select psg.Group_ID, psg.Name, psc.id as Value_ID, psc.name as Value, count(*) as Total 
from product_specification as ps
inner join product_specification_combine_value as pscv on pscv.productSpecificationValueId=ps.Value_ID
inner join product_specification_combine as psc on psc.id=pscv.productSpecificationCombineId and psc.productSpecificationGroupId in (221,41)
inner join product_specification_group as psg on psc.productSpecificationGroupId=psg.Group_ID
where ps.Product_ID in (
SELECT p.Product_ID
{$sqlFrom}
{$sqlWhere}
)
group by pscv.id";
$data = new DataQuery($combineQuery);

$combineArray = array();
while($data->Row){
	$combineArray[] = $data->Row;
	$data->Next();
}
$data->Disconnect();
$json->combine = $combineArray;

$json->total = $matches;
header('Content-type: application/json');

echo json_encode($json);
$GLOBALS['DBCONNECTION']->Close();