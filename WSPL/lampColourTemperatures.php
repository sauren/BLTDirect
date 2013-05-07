<?php
	require_once('../lib/common/appHeadermobile.php');
	include("ui/nav.php");
	include("ui/search.php");?>
    <div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Light Bulb Colour Temperatures</span></div>
<div class="maincontent">
<div class="maincontent1">
					<p>All <a href="<?php echo Category::GetCategory(16)->GetUrl(); ?>" title="View our <?php echo Category::GetCategory(16)->Name; ?>">fluorescent tubes</a>, <a href="<?php echo Category::GetCategory(14)->GetUrl(); ?>" title="View our <?php echo Category::GetCategory(14)->Name; ?>">compact fluorescent</a> and <a href="<?php echo Category::GetCategory(15)->GetUrl(); ?>" title="View our <?php echo Category::GetCategory(15)->Name; ?>">energy saving lamps</a> are available in different colour renditions, or  burning temperatures measured in Kelvin. </p>
					<p>Colour temperature is a standard method of describing colours for use in a range of situations and with different equipment. Colour temperatures are normally expressed in units called kelvins (K). Note that the term degrees kelvin is often used but is not technically correct.</p>
					<div class="tempView">
						<div class="img">
							<img src="images/colour-temperature.jpg" alt="Colour Temperatures in the Kelvin Scale" width="100%" height="280" />
						</div>
                        </br>
						<div class="video">
							<iframe width="100%" height="260" src="//www.youtube.com/embed/vqyox5dxhAA?wmode=transparent"  frameborder="0" allowfullscreen="allowfullscreen"></iframe>
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
                    </div>
                    </div>
  <?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>