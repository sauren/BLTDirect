<?php
require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");
?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Delivery Rates</span></div>
<div class="maincontent">
<div class="maincontent1">
<td width="100%" valign="top">
			  <p style="text-align:justify;">Our shopping cart defaults to United Kingdom, England &amp; Wales. Simply start adding product to your shopping cart to find out the shipping costs. If you are not within England or Wales you should follow the instructions below for international visitors. </p>

			  <h3 style="text-align:justify;">Deliveries Within the United Kingdom</h3>
			  <p style="text-align:justify;">The table below highlights the delivery costs of our Standard Light Bulbs.</p>
              </td>

				<table cellpadding="0" cellspacing="0" width="100%">
				  	<tr>
				  		<td width="100%" valign="top">

				  			<table cellpadding="0" cellspacing="0" class="homeProducts">
								<tr>
									<th>Delivery Rates for <span style="color: #0a0;">Standard Light Bulbs</span></th>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td align="right"><strong>Under 10Kg</strong></td>
									<td align="right"><strong>Over 10Kg</strong></td>
								</tr>
								<tr>
									<td><strong>Standard 2-6 Days</strong></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td>Under &pound;45.00 (ex. VAT)</td>
									<td align="right">&pound;4.45</td>
									<td align="right">&pound;4.45</td>
								</tr>
								<tr>
									<td>Over &pound;45.00 (ex. VAT)</td>
									<td align="right"><span style="color: #0a0;">FREE</span></td>
									<td align="right"><span style="color: #0a0;">FREE</span></td>
								</tr>
								<tr>
									<td><strong>Next Day Delivery </strong></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td>All Orders</td>
									<td align="right">&pound;13.95</td>
									<td align="right">+&pound;0.73 per additional kilo</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
                                </table>
                                <table width="100%">
								<tr>
									<th>All Other Product Types </th>
								</tr>
								<tr>
									<td>
										<p style="text-align:justify; width:100%;">Please use the shopping cart to find out your exact shipping costs for the following items which start at &pound;4.45</p>
										<ul style="margin-top: 0;">
											<li>Bathroom Light Fittings </li>
											<li>Control Gear</li>
											<li>Fluorescent Tubes 450mm - 900mm</li>
											<li>Heater Lamps</li>
											<li>Light Fittings</li>
										</ul>

										<p style="text-align:justify;">Courier service for the following items from &pound;7.00</p>
										<ul style="margin-top: 0;">
											<li>Projector Lamp</li>
											<li>Fluorescent Tubes 1200mm - 1800mm</li>
											<li>Fluorescent Tubes 1800mm - 2400mm</li>
											<li>Sunbed Tubes</li>
										</ul>
									</td>
								</tr>
							</table>

				  		</td></tr>
                        <tr>
				  		<td width="100%" valign="top">

					  			<h1 style="text-align:left;">Free Deliveries</h1>
					  			<p style="text-align:justify;">Light bulb orders qualify for free delivery in the following areas where the order value is over &pound;45.00 (ex. VAT). Light fittings, fluorescent tubes, and control gear do not qualify for free shipping.</p>
					  			<br />

					  			<div style="text-align: center;"><img src="images/delivery_map.gif" width="100%" height="304" alt="Regions of free delivery." /></div>
					  			<br />

					  			<p style="background-color: #54A854; margin: 0; padding: 5px; color: #fff; border: 1px solid #439B25;">BLT Direct offer free delivery on orders over &pound;45.00 (ex. VAT) for England, Wales, and Southern Scotland.</p><br />
					  			<p style="background-color: #FBEE5B; margin: 0; padding: 5px; color: #000; border: 1px solid #E7D830;">Northern Ireland, Scottish Highlands and Isles, Isle of Man, and the Channel Islands only qualify for free shipping where the consignment weight is under 2kgs and the order value is over &pound;45.00 (ex. VAT).</p><br />

				  		</td>
				  	</tr>
				  </table><br />
                  <td width="100%" valign="top">

			  <p>&nbsp;</p>
			  <p style="text-align:justify;"><strong>Please Note:</strong> if you have not selected a postage option, or if no shipping prices are available, the Mini Cart on the right of this page will show a shipping cost of &pound;0.00. If this happens please click the &quot;View&quot; button on the Mini Cart to read more.</p>

			  <h3 style="text-align:justify;"><a name="International" id="International">International Deliveries</a></h3>
			  <div style="text-align: center;"><img src="images/delivery_world_map.gif" width="100%" height="250" alt="International Delivery Rates" />

			  <p style="text-align:justify;">We try our hardest to provide our online customers with the most competitive shipping and handling no matter your location. Unfortunately this makes our shipping and handling calculations very complicated. If you would like to find out the shipping costs for your location please use our shipping and handling calculator built into our shopping cart. You can change your location by clicking on the &quot;Change Location&quot; link beneath the Shipping location within the shopping cart (pictured below).</p>
              <img src="images/cart_changeLocation_1.gif" width="100%" height="141" alt="Change Location" />
			  <p style="text-align:justify;">You will then be redirected to another page where you will be able to choose the Country and Region you would like your purchase to be shipped to (pictured below). Click submit and you will be redirected back to the shopping cart where the new Shipping Charges will be displayed. </p>
			  <img src="images/cart_changeLocation_2.gif" width="198" height="236" alt="Shipping Charges" />
			<p style="text-align:justify;">If we do not have any prices for your location you will be prompted. If this happens please call us on <strong><?php echo Setting::GetValue('telephone_sales_hotline'); ?></strong> and we will be happy to provide you with a quotation for Shipping your purchase to your chosen destination. </p>
			<p style="text-align:justify;">If you have any further queries with regard to our Shipping costs please <a href="support.php">contact us</a>.</p>
            </div>
            </td>
            </div>
            </div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>            