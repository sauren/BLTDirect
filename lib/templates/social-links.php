<div class="nav-social-links">
	<div class="google-plusone">
		<!-- Place this tag in your head or just before your close body tag. -->
		<script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>

		<!-- Place this tag where you want the +1 button to render. -->
		<div class="g-plusone" data-size="medium" data-href="http://www.bltdirect.com"></div>
	</div>

    <div class="twitter-tweet">
		<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://www.bltdirect.com" data-text="BLT Direct - Your One Stop Shop for Light Bulbs and Other Lighting Products" data-count="none">Tweet</a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
	</div>

    <div class="facebook-like">
    	<div id="fb-root"></div>
		<script>(function(d, s, id) {
		  var js, fjs = d.getElementsByTagName(s)[0];
		  if (d.getElementById(id)) return;
		  js = d.createElement(s); js.id = id;
		  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1";
		  fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));</script>
		<div class="fb-like" data-href="http://www.bltdirect.com" data-send="false" data-layout="standard" data-width="450" data-show-faces="false" data-font="arial"></div>
    </div>

    <div class="blt-rating">
    	<a href="/feedback.php" class="overall-average-product-rating">
    		<p><strong>Customer reviews <span style="color:#e29d0b;">(<?php echo number_format($average['total_reviews']); ?>)</span></strong></p>
    		<span class="small-star-rating" data-stars="<?php echo $average['rating']; ?>"></span>
	        <span class="hover-tooltip"><?php echo number_format($average['total_reviews']); ?> customer reviews with an average rating of <?php printf('%s out of %d', $average['rating'], $GLOBALS['PRODUCT_REVIEW_RATINGS']); ?>.</span>
	    </a>
	</div>
	
	<div class="clear"></div>
</div>
<script>
	jQuery(document).ready(function() {
		smallStars(jQuery('span.small-star-rating'));
	});
</script>
