<?php
$countSpaces = isset($countSpaces) ? $countSpaces : 1;
$countColumns = isset($countColumns) ? $countColumns : 1;
$hideSwitch = isset($hideSwitch) ? $hideSwitch : true;

if(!empty($subProduct->RelatedType['Energy Saving Alternative'])) {
	?>
	
	<tr>
		<td colspan="<?php echo $countSpaces; ?>"></td>
		<td colspan="<?php echo $countColumns; ?>">
		
			<div class="product-alternative">
				<p>
					<strong class="colour-green">Energy saving alternatives.</strong><br />
					<span class="product-detail-ident"><span class="colour-green">Switch to this energy saving alternative and you could save over the life of the bulb.</span></span>
				</p>
			
				<?php
				foreach($subProduct->RelatedType['Energy Saving Alternative'] as $related) {
					$specEquivalentWattage = null;
					$specWattage = null;
					$specLampLife = null;

					if(!empty($groupsEquivalentWattage)) {
						$data = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsEquivalentWattage), mysql_real_escape_string($related['Product_ID'])));
						if($data->TotalRows > 0) {
							$specEquivalentWattage = preg_replace('/[^0-9\.]/', '', $data->Row['Value']);
						}
						$data->Disconnect();
					}

					if(!empty($groupsWattage)) {
						$data = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsWattage), mysql_real_escape_string($related['Product_ID'])));
						if($data->TotalRows > 0) {
							$specWattage = preg_replace('/[^0-9\.]/', '', $data->Row['Value']);
						}
						$data->Disconnect();
					}

					if(!empty($groupsLampLife)) {
						$data = new DataQuery(sprintf("SELECT psv.Value FROM product_specification AS ps INNER JOIN product_specification_value AS psv ON ps.Value_ID=psv.Value_ID AND psv.Group_ID IN (%s) WHERE ps.Product_ID=%d", implode(', ', $groupsLampLife), mysql_real_escape_string($related['Product_ID'])));
						if($data->TotalRows > 0) {
							$specLampLife = preg_replace('/[^0-9\.]/', '', $data->Row['Value']);
						}
						$data->Disconnect();
					}
					
					$subProductRelated = new Product($related['Product_ID']);
					$subProductRelated->DefaultImage->Thumb->GetDimensions();
					$subProductRelated->DefaultImage->Thumb->Width /= 2;
					$subProductRelated->DefaultImage->Thumb->Height /= 2;
					
					$energySaving = 0;
					
					if(!empty($specEquivalentWattage) && !empty($specWattage) && !empty($specLampLife)) {
						$energySaving = ($specEquivalentWattage - $specWattage) * (12 / 100 / 1000) * $specLampLife;
					}
					?>
					
					<div class="product-alternative-item">
						<span class="product-alternative-item-image">
						
							<?php
							if(!empty($subProductRelated->DefaultImage->Thumb->FileName) && file_exists($GLOBALS['PRODUCT_IMAGES_DIR_FS'].$subProductRelated->DefaultImage->Thumb->FileName)) {
								?>
								
								<a href="./product.php?pid=<?php echo $subProductRelated->ID; ?><?php echo isset($subCategory) ? '&cat=' . $subProductRelated->ID : ''; ?>" title="<?php echo $subProductRelated->Name;?>"><img src="<?php echo $GLOBALS['PRODUCT_IMAGES_DIR_WS'] . $subProductRelated->DefaultImage->Thumb->FileName; ?>" alt="<?php echo $subProductRelated->Name; ?>" width="<?php echo $subProductRelated->DefaultImage->Thumb->Width; ?>" height="<?php echo $subProductRelated->DefaultImage->Thumb->Height; ?>" /></a><br />
								
								<?php
							}
							?>
							
							<?php
							if($energySaving > 0) {
								?>
								
								<span class="colour-green"><strong>&pound;<?php echo number_format($energySaving, 2, '.', ','); ?></strong></span>
								
								<?php
							}
							?>
						
						</span>

						<a href="./product.php?pid=<?php echo $subProductRelated->ID; ?><?php echo isset($subCategory) ? '&cat=' . $subProductRelated->ID : ''; ?>" title="<?php echo $subProductRelated->Name;?>"><?php echo $subProductRelated->Name; ?></a>
						
						<?php
						if(!$hideSwitch) {
							?>
							
							<br /><br />							
							<input type="button" name="switch" value="Switch" class="button" onclick="redirect('cart.php?action=switch&line=<?php echo $subCartLine->ID; ?>&product=<?php echo $subProductRelated->ID; ?>');" />
													
							<?php
						}
						?>
						
						<div class="clear"></div>
					</div>
					
					<?php
				}
				?>
				
			</div>

		</td>
	</tr>
	
	<?php
}