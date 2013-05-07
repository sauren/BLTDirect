<?php
require_once('../lib/common/appHeadermobile.php');
require_once($GLOBALS["DIR_WS_ADMIN"] . 'lib/classes/Form.php');

function compareWattage($a, $b) {
	return strnatcmp($a['EquivalentWattageID']['EquivalentWattage'], $b['EquivalentWattageID']['EquivalentWattage']);
}

$defaultHours = 4.65;
$defaultCost = 0.15;

$form = new Form($_SERVER['PHP_SELF']);
$form->AddField('hours', 'Daily Bulb Usage', 'text', $defaultHours, 'float', 1, 11, true);
$form->AddField('cost', 'Electricity Cost (kWh)', 'text', $defaultCost, 'float', 1, 11, true);

$value = array();
$sort = array();
$line = array();

$data = new DataQuery(sprintf("SELECT psv.Value_ID AS EquivalentWattageID, psv.Value AS EquivalentWattage, psv2.Value_ID AS WattageID, psv2.Value AS Wattage FROM product_specification_value AS psv INNER JOIN product_specification AS ps ON ps.Value_ID=psv.Value_ID INNER JOIN product_specification AS ps2 ON ps2.Product_ID=ps.Product_ID INNER JOIN product_specification_value AS psv2 ON psv2.Value_ID=ps2.Value_ID AND psv2.Group_ID=211 WHERE psv.Group_ID=73 GROUP BY psv.Value, psv2.Value"));
while($data->Row) {
	if(!isset($value[$data->Row['EquivalentWattageID']])) {
		$value[$data->Row['EquivalentWattageID']] = array(	'EquivalentWattageID' => $data->Row['EquivalentWattageID'],
															'EquivalentWattage' => $data->Row['EquivalentWattage'],
															'Wattages' => array());
	}

	$value[$data->Row['EquivalentWattageID']]['Wattages'][$data->Row['WattageID']] = array(	'WattageID' => $data->Row['WattageID'],
																							'Wattage' => $data->Row['Wattage']);
	$data->Next();
}
$data->Disconnect();

foreach($value as $valueId=>$valueItem) {
	if($pos = stripos($valueItem['EquivalentWattage'], 'W')) {
		$wattage = trim(substr($valueItem['EquivalentWattage'], 0, $pos));

		if(is_numeric($wattage)) {
			$sort[$wattage] = $valueId;
		}
	}
}

ksort($sort);

foreach($sort as $sortValue=>$sortValueId) {
	foreach($value as $valueId=>$valueItem) {
		if($valueId == $sortValueId) {
			foreach($valueItem['Wattages'] as $wattageId=>$wattageItem) {
				$wattageEquivalent = $sortValue;
				$wattageStandard = null;

				if($pos = stripos($wattageItem['Wattage'], 'W')) {
					$wattage = trim(substr($wattageItem['Wattage'], 0, $pos));

					if(is_numeric($wattage)) {
						$wattageStandard = $wattage;
					}
				}

				$line[] = array('WattageID' => $wattageId,
								'WattageValue' => $wattageItem['Wattage'],
								'EquivalentWattageID' => $valueId,
								'EquivalentWattageValue' => $valueItem['EquivalentWattage'],
								'NumberWattageStandard' => $wattageStandard,
								'NumberWattageEquivalent' => $wattageEquivalent);
			}

			break;
		}
	}
}
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Energy Saving Light Bulbs</span></div>
<div class="maincontent">
<div class="maincontent1">
					<p>Although energy saving light bulbs are more expensive than their energy-wasting counterparts, energy saving light bulbs make use of modern technology to reduce your overall electricity costs and consumption over time. The chart below highlights  cost savings per light bulb you could be making in your home or work place. With government initiatives to reduce carbon emissions are you doing your bit to save the environment?</p>
					<p><a href=".<?php echo Category::GetCategory(15)->GetUrl(); ?>">View <?php echo Category::GetCategory(15)->Name ?> Section</a></p>

					<table border="0" cellpadding="10" cellspacing="0" class="bluebox">
						<tr>
						  <td>
								<h3 class="blue">Price Comparison Evaluation</h3>
								<p>Enter your own figures to calculate how much you could be saving!</p>

								<?php
								echo $form->Open();
								?>

								<table cellpadding="0" cellspacing="0" border="0" width="100%">
									<tr>
										<td width="50%">
											<strong><?php echo $form->GetLabel('hours'); ?></strong><br />
											<?php echo $form->GetHTML('hours'); ?> (Hours)
										</td></tr>
                                        <tr>
										<td width="50%">
											<strong><?php echo $form->GetLabel('cost'); ?></strong><br />
											<?php echo $form->GetHTML('cost'); ?> (&pound;)
										</td>
									</tr>
								</table>
								<input type="submit" class="greySubmit" name="update" value="Update Savings" />

								<?php
								echo $form->Close();
								?>
							</td>
						</tr>
					</table><br />

					<p class="alert">Click on a wattage type below to view matching energy saving light bulbs.</p><br />

					<table width="100%"  cellspacing="0" class="energySavingTable" border="1">
						<tr>
							<th style="text-align: center;">Energy Saving Wattage</th>
							<th style="text-align: center;">Equivalent Normal Wattage</th>
							<th style="text-align: center;">Savings Over One Year</th>
							<th style="text-align: center;">Savings Over 5000 Hours</th>
							<th style="text-align: center;">Savings Over 8000 Hours</th>
						</tr>

						<?php
						for($i=0; $i<count($line); $i++) {
							$lineItem = $line[$i];
							?>

							<tr>
								<td style="text-align: center;"><a href="./search.php?filter=<?php echo $lineItem['WattageID']; ?>&amp;cat=15,241" title="<?php echo $lineItem['WattageValue']; ?> Energy Saving Light Bulbs"><?php echo $lineItem['WattageValue']; ?></a></td>
								<td style="text-align: center;"><a href="./search.php?filter=<?php echo $lineItem['EquivalentWattageID']; ?>" title="<?php echo $lineItem['EquivalentWattageValue']; ?> Energy Saving Light Bulbs"><?php echo $lineItem['EquivalentWattageValue']; ?></a></td>
								<td style="text-align: center;" id="saving_year_<?php echo $i; ?>"><?php echo !is_null($lineItem['NumberWattageStandard']) ? sprintf('&pound;%s', number_format(((($lineItem['NumberWattageEquivalent'] - $lineItem['NumberWattageStandard']) * $form->GetValue('hours') * 365) / 1000) * $form->GetValue('cost'), 2, '.', '')) : '-'; ?></td>
								<td style="text-align: center;" id="saving_5000_<?php echo $i; ?>"><?php echo !is_null($lineItem['NumberWattageStandard']) ? sprintf('&pound;%s', number_format(((($lineItem['NumberWattageEquivalent'] - $lineItem['NumberWattageStandard']) * 5000) / 1000) * $form->GetValue('cost'), 2, '.', '')) : '-'; ?></td>
								<td style="text-align: center;" id="saving_8000_<?php echo $i; ?>"><?php echo !is_null($lineItem['NumberWattageStandard']) ? sprintf('&pound;%s', number_format(((($lineItem['NumberWattageEquivalent'] - $lineItem['NumberWattageStandard']) * 8000) / 1000) * $form->GetValue('cost'), 2, '.', '')) : '-'; ?></td>
							</tr>

							<?php
						}
						?>

					</table>

</div>
</div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>