<?php
	require_once('lib/common/appHeader.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en"><!-- InstanceBegin template="/templates/default.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>Light Bulb Colour Temperatures</title>
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
					<h1>Light Bulb Colour Temperatures</h1>
					<p>All <a href="<?php echo Category::GetCategory(16)->GetUrl(); ?>" title="View our <?php echo Category::GetCategory(16)->Name; ?>">fluorescent tubes</a>, <a href="<?php echo Category::GetCategory(14)->GetUrl(); ?>" title="View our <?php echo Category::GetCategory(14)->Name; ?>">compact fluorescent</a> and <a href="<?php echo Category::GetCategory(15)->GetUrl(); ?>" title="View our <?php echo Category::GetCategory(15)->Name; ?>">energy saving lamps</a> are available in different colour renditions, or  burning temperatures measured in Kelvin. </p>
					<p>Colour temperature is a standard method of describing colours for use in a range of situations and with different equipment. Colour temperatures are normally expressed in units called kelvins (K). Note that the term degrees kelvin is often used but is not technically correct.</p>
					<div class="tempView">
						<div class="img">
							<img src="images/colour-temperature.jpg" alt="Colour Temperatures in the Kelvin Scale" width="400" height="400" />
						</div>
						<div class="video">
							<iframe width="380" height="260" src="//www.youtube.com/embed/vqyox5dxhAA?wmode=transparent"  frameborder="0" allowfullscreen="allowfullscreen"></iframe>
					  </div>
						<div class="clear"></div>
					</div>


					<p>For example an energy saving lamp with a colour temperature of 3500K burns at 3500 Kelvin this colour is known as white.</p>
					<p>There is a high demand nowadays for  <a href="search.php?search=daylight" title="Search for Light Bulbs Containing Daylight">daylight lamps</a> which burn at a temperature of 6500K, these lamps are often used to combat SAD (seasonal affective disorder) </p>
					<p>Please find below a chart of colour temperatures, if you are unsure of the lamp you require please email us on <a href="mailto:sales@bltdirect.com?subject=Colour Temperature Enquiry&amp;body=Dear BLT Direct,">sales@bltdirect.com</a> or call us on <strong><?php echo Setting::GetValue('telephone_sales_hotline'); ?></strong>.</p>

					<table width="100%" border="0" cellpadding="0" cellspacing="0" class="catProducts">
			          <tr>
			            <th>Colour Reference</th>
			            <th>Colour</th>
			            <th>Colour Temperature</th>
			            <th>CR1 Ra</th>
			          </tr>

					<?php
					$data = new DataQuery(sprintf("SELECT lt.*, psv.Value FROM lamp_temperature AS lt LEFT JOIN product_specification_value AS psv ON psv.Value_ID=lt.Specification_Value_ID ORDER BY psv.Value ASC"));
					while($data->Row) {
						?>

						<tr>
							<td><?php echo $data->Row['Reference']; ?></td>
							<td><?php echo $data->Row['Colour']; ?></td>
							<td><a href="search.php?filter=<?php echo $data->Row['Specification_Value_ID']; ?>" title="Search for <?php echo $data->Row['Value']; ?> Colour Temperature Light Bulbs"><?php echo $data->Row['Value']; ?></a>&nbsp;</td>
							<td><?php echo $data->Row['CR1_Ra']; ?>&nbsp;</td>
						</tr>

						<?php
						$data->Next();
					}
					$data->Disconnect();
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