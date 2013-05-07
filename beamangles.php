<?php
	require_once('lib/common/appHeader.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>BLT Direct - Beam Angles</title>
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
	<script type="text/javascript" src="./js/swfobject.js"></script>
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
					<h1>Beam Angles</h1>
					<p>Some halogen lamps come with a built in reflector and are available in different beam angles ranging from 4&deg; to 120&deg;, please use the arrows on the interactive picture below to see the various effects from different beam angles.  Please remember that when installing a number of fittings or lamps in one room that the spread effect from the lamp will increase coverage.</p>

					<div id="beamAngles" style="text-align: center;"></div>
					<br />

					<script type="text/javascript">
						var so = new SWFObject("./media/beamangles.swf", "BLT Direct", "592", "432", "8", null, true);
						so.addVariable("lang", "en");
						so.addVariable("safari", (window.webkit)?'true':'false');
						so.addParam("swLiveConnect", "true");
						so.addParam("allowScriptAccess", "sameDomain");
						// Media was taken out bellow due to google webmaster tools to replace use '/media'
						so.addParam("base", "/");
						so.write("beamAngles");
					</script>

					<?php
					$links = array();
					$linkColumns = array();
					$linkColumn = 0;
					$count = 0;
					$columns = 3;

					$data = new DataQuery(sprintf("SELECT psv.* FROM product_specification_value AS psv WHERE psv.Group_ID=20 ORDER BY psv.Value ASC"));
					while($data->Row) {
						$links[] = sprintf('<a href="./search.php?filter=%d" title="Beam Angle: %s">%s</a>', $data->Row['Value_ID'], $data->Row['Value'], $data->Row['Value']);

						$data->Next();
					}
					$data->Disconnect();

					for($i=0; $i<count($links); $i++) {
						if($count >= (count($links) / $columns)) {
							$linkColumn++;
							$count = 0;
						}

						$linkColumns[$linkColumn][] = $links[$i];
						$count++;
					}

					if(count($linkColumns) > 0) {
						?>

						<table width="100%" border="0" cellspacing="0" cellpadding="4" class="energySavingTable">
							<tr>
								<th style="text-align: left;" colspan="<?php echo $columns; ?>">Beam Angle (&deg;)</th>
							</tr>

							<?php
							for($i=0;$i < count($linkColumns[0]); $i++) {
								echo '<tr>';

								for($j=0; $j<$columns; $j++) {
									if(isset($linkColumns[$j][$i])) {
										$link = $linkColumns[$j][$i];
									} else {
										$link = '&nbsp;';
									}

									echo sprintf('<td style="text-align: left; width: %s%%;">%s</td>', 100/$columns, $link);
								}

								echo '</tr>';
							}
							?>

						</table>

						<?php
					}
					?>

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