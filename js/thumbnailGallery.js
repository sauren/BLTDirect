/*
	jQuery Thumbnail Gallery.
   
	A simple sliding gallery of thumbnail images. Scroll left and right to view
	more image in the gallery, then click a thumbnail to trigger an event.
   
	Usage:
		Call the thumbnailGallery function on the element containing the entire
		gallery. This can optionally contain left and right scroll buttons.
	   
		$(".gallery").thumbnailGallery();
	   
		The following example markup should get you started, but the exact
		structure here is not required.
		
		<div class="gallery">
			<div class="leftBtn"></div>
			
			<div class="mask">
				<div class="slider">
					<div class="image"><img src="" /></div>
					<div class="image"><img src="" /></div>
				</div>
			</div>
			
			<div class="rightBtn"></div>
		</div>
		
	Options:
		duration
			Duration of slide between pages.
		
		easing
			Easing type for slide animation.
			
		leftBtnSelector
			Selector to find optional "scroll left" button within the gallery.
		
		rightBtnSelector
			Selector to find optional "scroll right" button within the gallery.
		
		maskSelector
			Selector to find the fixed width mask element which contains the
			slider.

		sliderSelector
			Selector to find slider element which contains all the thumbnails
			and moves during animation.

		imageSelector: ".image"
			Selector to find each individual image within the sliding area. This
			does not need to be an "img" element itself.
*/

(function($) {
   
	$.fn.thumbnailGallery = function(options) {
		var opts = $.extend({}, $.fn.thumbnailGallery.defaults, options);
		
		this.each(function() {
			var gallery = $(this);
			
			var leftBtn = gallery.find(opts.leftBtnSelector);
			var rightBtn = gallery.find(opts.rightBtnSelector);
			
			var mask = gallery.find(opts.maskSelector);
			var slider = gallery.find(opts.sliderSelector);
			
			var thumbs = slider.find(opts.imageSelector);
			
			mask.css({
				overflow: "hidden",
				position: "relative"
			});
			
			
			// Set slider width.
			var totalWidth = 0;
			var itemWidth = 0;
			thumbs.each(function() {
				totalWidth += $(this).outerWidth(true);
				itemWidth = $(this).outerWidth(true);
			});
			
			slider.width(totalWidth);
			slider.css({position: "relative", top: 0, left: 0});
			
			function maxScrollDist() {
				var div = Math.floor(mask.width() / itemWidth);
				return itemWidth * div;
			}
			
			function checkArrows() {
				var pos = slider.position().left;
				
				rightBtn.toggleClass("active", maxScrollDist()-pos < totalWidth);
				leftBtn.toggleClass("active", pos < 0);
			}
			
			function slide(dist) {
				var currentPos = slider.position().left;
				var pos = currentPos + dist;

				if (pos >= 0) {
					pos = 0;
				}
				else if (pos-maxScrollDist() < -totalWidth) {
					pos = maxScrollDist() - totalWidth;
				}
				
				slider.animate({left: pos}, opts.duration, opts.easing, checkArrows);
			}
			
			leftBtn.click(function(e) {
				e.preventDefault();
				slide(maxScrollDist());
			});
			rightBtn.click(function(e) {
				e.preventDefault();
				slide(-maxScrollDist());
			});
			
			thumbs.click(function(e) {
				thumbs.removeClass("active");
				$(this).addClass("active");
				gallery.trigger("thumbClick", [e, this]);
			});
			
			checkArrows();
		});
	   
		return this;
	};
   
	// Allow access to defaults.
	$.fn.thumbnailGallery.defaults = {
		duration: 500,
		easing: "swing",
		leftBtnSelector: ".leftBtn",
		rightBtnSelector: ".rightBtn",
		maskSelector: ".mask",
		sliderSelector: ".slider",
		imageSelector: ".image"
	};
   
})(jQuery);
