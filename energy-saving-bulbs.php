<?php
require_once('lib/common/appHeader.php');
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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Energy Saving Light Bulbs</title>
	<!-- InstanceEndEditable -->

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Language" content="en" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="css/lightbulbs_print.css" media="print" />
	<link rel="stylesheet" type="text/css" href="css/Navigation.css" />
	<link rel="stylesheet" type="text/css" href="css/Menu.css" />
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'Y') {
		?>
		<link rel="stylesheet" type="text/css" href="css/Trade.css" />
        <?php
	}
	?>
	<link rel="shortcut icon" href="favicon.ico" />
<!--    <script type='text/javascript' src='http://api.handsetdetection.com/sites/js/43071.js'></script>-->
	<script language="javascript" type="text/javascript" src="js/generic.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance_api.js"></script>
	<script language="javascript" type="text/javascript" src="js/mootools.js"></script>
	<script language="javascript" type="text/javascript" src="js/evance.js"></script>
	<script language="javascript" type="text/javascript" src="js/bltdirect.js"></script>
    <script language="javascript" type='text/javascript' src="js/api.js"></script>
    
    <?php
	if($session->Customer->Contact->IsTradeAccount == 'N') {
		?>
		<script language="javascript" type="text/javascript" src="js/bltdirect/template.js"></script>
        <?php
	}
	?>
    
	<script language="javascript" type="text/javascript">
	//<![CDATA[
		<?php
		for($i=0; $i<count($GLOBALS['Cache']['Categories']); $i=$i+2) {
			echo sprintf("menu1.add('navProducts%d', 'navProducts', '%s', '%s', null, 'subMenu');", $i, $GLOBALS['Cache']['Categories'][$i], $GLOBALS['Cache']['Categories'][$i+1]);
		}
		?>
	//]]>
	</script>	
	<!-- InstanceBeginEditable name="head" -->
	<meta name="Keywords" content="light bulbs, light bulb, lightbulbs, lightbulb, lamps, fluorescent, tubes, osram, energy saving, sylvania, philips, ge, halogen, low energy, metal halide, candle, dichroic, gu10, projector, blt direct" />
	<meta name="Description" content="We specialise in supplying lamps, light bulbs and fluorescent tubes, Our stocks include Osram,GE, Sylvania, Omicron, Pro lite, Crompton, Ushio and Philips light bulbs, " />
	<!-- InstanceEndEditable -->
</head>
<body>

    <div id="Wrapper">
        <div id="Header">
            <div id="HeaderInner">
                <?php require('lib/templates/header.php'); ?>
            </div>
        </div>
        <div id="PageWrapper">
            <div id="Page">
                <div id="PageContent">
                    <?php
                    if(strtolower(Setting::GetValue('site_message_active')) == 'true') {
                        ?>

                        <div id="SiteMessage">
                            <div id="SiteMessageLeft">
                                <div id="SiteMessageRight">
                                    <marquee scrollamount="4"><?php echo Setting::GetValue('site_message_value'); ?></marquee>
                                </div>
                            </div>
                        </div>

                        <?php
                    }
                    ?>
                    
                    <a name="top"></a>
                    
                    <!-- InstanceBeginEditable name="pageContent" -->
					<h1>Energy Saving Light Bulbs</h1>
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
										</td>
										<td width="50%">
											<strong><?php echo $form->GetLabel('cost'); ?></strong><br />
											<?php echo $form->GetHTML('cost'); ?> (&pound;)
										</td>
									</tr>
								</table><br />

								<input type="submit" class="greySubmit" name="update" value="Update Savings" />

								<?php
								echo $form->Close();
								?>
							</td>
						</tr>
					</table><br />

					<p class="alert">Click on a wattage type below to view matching energy saving light bulbs.</p><br />

					<table width="100%" border="0" cellspacing="0" cellpadding="4" class="energySavingTable">
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

					<!-- InstanceEndEditable -->
                </div>
            </div>
            <div id="PageFooter">
                <ul class="links">
                    <li><a href="./terms.php" title="BLT Direct Terms and Conditions of Use and Sale">Terms and Conditions</a></li>
                    <li><a href="./privacy.php" title="BLT Direct Privacy Policy">Privacy Policy</a></li>
                    <li><a href="./company.php" title="About BLT Direct">About Us</a></li>
                    <li><a href="./sitemap.php" title="Map of Site Contents">Site Map</a></li>
                    <li><a href="./support.php" title="Contact BLT Direct">Contact Us</a></li>
                    <li><a href="./index.php" title="Light Bulbs">Light Bulbs</a></li>
                    <li><a href="./products.php?cat=1251&amp;nm=Christmas+Lights" title="Christmas Lights">Christmas Lights</a></li> 
                    <li><a href="./Projector-Lamps.php" title="Projector Lamps">Projector Lamps</a></li>
                    <li><a href="./articles.php" title="Press Releases/Articles">Press Releases/Articles</a></li>
                </ul>
                
                <p class="copyright">Copyright &copy; BLT Direct, 2005. All Right Reserved.</p>
            </div>
        </div>
        <div id="LeftNav">
            <?php require('lib/templates/left.php'); ?>
        </div>
        <div id="RightNav">
            <?php require('lib/templates/right.php'); ?>
        
            <div id="Azexis">
                <a href="http://www.azexis.com" target="_blank" title="Web Designers">Web Designers</a>
            </div>
        </div>
    </div>
	<script src="<?php print ($_SERVER['SERVER_PORT'] != $GLOBALS['SSL_PORT']) ? 'http://www' : 'https://ssl'; ?>.google-analytics.com/urchin.js" type="text/javascript"></script>
	<script type="text/javascript">
	//<![CDATA[
		_uacct = "UA-1618935-2";
		urchinTracker();
	//]]>
	</script>

	<!-- InstanceBeginEditable name="Tracking Script" -->

<!--
<script>
var parm,data,rf,sr,htprot='http'; if(self.location.protocol=='https:')htprot='https';
rf=document.referrer;sr=document.location.search;
if(top.document.location==document.referrer||(document.referrer == '' && top.document.location != '')) {rf=top.document.referrer;sr=top.document.location.search;}
data='cid=256336&rf=' + escape(rf) + '&sr=' + escape(sr); parm=' border="0" hspace="0" vspace="0" width="1" height="1" '; document.write('<img '+parm+' src="'+htprot+'://stats1.saletrack.co.uk/scripts/stinit.asp?'+data+'">');
</script>
<noscript>
<img src="http://stats1.saletrack.co.uk/scripts/stinit.asp?cid=256336&rf=JavaScri
pt%20Disabled%20Browser" border="0" width="0" height="0" />
</noscript>
-->

<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>
<?php include('lib/common/appFooter.php'); ?>