<?php
require_once('lib/common/app_header.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/DataQuery.php');

$page = new Page('Organisations No Country Report', '');
$page->Display('header');
?>

<br />
<h3>No Country</h3>
<p>Listing all organisations with no country in their standard address details.</p>

<table width="100%" border="0" >
	<tr>
		<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Organisation</strong></td>
		<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Address</strong></td>
		<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>City/Town</strong></td>
		<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Postcode</strong></td>
		<td align="left" style="border-bottom: 1px solid #aaaaaa;"><strong>Region</strong></td>
	</tr>
	
	<?php
	$data = new DataQuery(sprintf("SELECT o.Org_Name, c.Contact_ID, TRIM(BOTH ',' FROM TRIM(REPLACE(CONCAT_WS(', ', a.Address_Line_1, a.Address_Line_2, a.Address_Line_3), ', , ', ''))) AS Address, a.City, a.Zip, CONCAT_WS('', r.Region_Name, a.Region_Name) AS Region FROM organisation AS o INNER JOIN contact AS c ON o.Org_ID=c.Org_ID INNER JOIN address AS a ON a.Address_ID=o.Address_ID LEFT JOIN regions AS r ON r.Region_ID=a.Region_ID WHERE a.Country_ID=0 ORDER BY o.Org_Name ASC"));
	while($data->Row) {
		?>
		
		<tr class="dataRow">
			<td><a href="organisation_profile.php?cid=<?php echo $data->Row['Contact_ID']; ?>" target="_blank"><?php echo $data->Row['Org_Name']; ?></td>
			<td><?php echo $data->Row['Address']; ?></td>
			<td><?php echo $data->Row['City']; ?></td>
			<td><?php echo $data->Row['Zip']; ?></td>
			<td><?php echo $data->Row['Region']; ?></td>
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