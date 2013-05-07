<?php

if(!count($banners)){
	return;
}

///Next get a random banner for instant display
$selectedBanner = $banners[0];

function bannerLink($banner, $selectedId=null) {
	$attrs = array(
		"class" => array("slide")
	);

	if ($banner->ID == $selectedId) {
		$attrs["class"][] = "selected";
	}

	$attrs["data-image"] = $GLOBALS['ARTICLE_DOWNLOAD_DIR_WS'] . $banner->File->FileName;	

	if (isset($banner->Link) && $banner->Link) {
		$attrs["data-link"]	= htmlspecialchars($banner->Link);
	}

	if (isset($banner->Colour) && $banner->Colour) {
		$attrs["data-colour"] = $banner->Colour;
	}

	return element_tag_build("a", $attrs, "&nbsp;");
}

?>
<div class="dynamicSlideshow">
	<div class="leftCurve"></div>
	<div class="rightCurve"></div>
	<div>
		<div class="slideshowImage" title="<?php echo $selectedBanner->Title; ?>" style="background-color: #<?php echo $selectedBanner->Colour; ?>">
			<img src="<?php echo $GLOBALS['ARTICLE_DOWNLOAD_DIR_WS'] . $selectedBanner->File->FileName ?>" alt="<?php echo $selectedBanner->File->Name; ?>" />
			<?php if(isset($selectedBanner->Link)){ ?>
			<a class="slideshowLink" href="<?php echo $selectedBanner->Link ?>">&nbsp;</a>
			<?php } ?>
		</div>
	</div>

	<div class="slideshowButtons">
		<div><a class="previous">&nbsp;</a></div>

		<?php foreach ($banners as $num=>$banner) { ?>
		<div class="slideshowButton">
			<?php echo bannerLink($banner, $selectedBanner->ID) ?>
		</div>
		<?php } ?>
		
		<div><a class="next">&nbsp;</a></div>
	</div>
</div>
