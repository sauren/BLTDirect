<?php require_once('../lib/common/appHeadermobile.php');
include("ui/nav.php");
include("ui/search.php");?>
<div class="cartmiddle"><span style="font-size:20px;color:#333; margin-top:10px;">Beam Angles</span></div>
<div class="maincontent">
<div class="maincontent1">
					<p style="text-align:justify">Some halogen lamps come with a built in reflector and are available in different beam angles ranging from 4&deg; to 120&deg;, please use the arrows on the interactive picture below to see the various effects from different beam angles.  Please remember that when installing a number of fittings or lamps in one room that the spread effect from the lamp will increase coverage.</p>

					<div id="beamAngles" style="text-align: center;"></div>
					<br />

					<script type="text/javascript">
						var so = new SWFObject("media/beamangles.swf", "BLT Direct", "592", "432", "8", null, true);
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
						$links[] = sprintf('<a href="search.php?filter=%d" title="Beam Angle: %s">%s</a>', $data->Row['Value_ID'], $data->Row['Value'], $data->Row['Value']);

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
</div>
</div>
<?php include("ui/footer.php")?>
<?php include('../lib/common/appFooter.php'); ?>