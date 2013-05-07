/*
	Load the banners
*/

function consoleLog(obj){
	if(console && console.log){
		console.log(obj);
	}
}

jQuery(function($) {

	$(".dynamicSlideshow").each(function() {

		var slideShow = $(".slideshowImage", this);
		var image = $("img", this);

		function sizeBanner() {
			var faderImage = $('.faderImage');
			// Height
			image.css('height', 'auto');
			if(image.height() > 300){
				image.height(300);
			}
			slideShow.height(image.height());
			faderImage.width('');
			if(faderImage.width() > slideShow.width()){
				faderImage.width('100%');
			}
		}
		var objImage = new Image();
		objImage.src = image.attr('src');
		objImage.onload = function(){
			image.css('max-width', objImage.width).width('100%');
			sizeBanner();
		}
		/*image.bind('load', function(e){
			image.css('max-width', image.width()).width('100%');
			sizeBanner();
			consoleLog('foo');
		});*/
		
		
		
		var slides = $(".slideshowButtons .slide", this);

		var bannerImages = slides.map(function() {
			return $(this).attr("data-image");
		});

		var bannerLinks = slides.map(function() {
			return $(this).attr("data-link");
		});

		var bannerColours = slides.map(function() {
			return $(this).attr("data-colour");
		})

		var slide = Azexis.slideshow($(".slideshowImage", this), bannerImages, bannerColours);

		//Show the buttons
		$(".slideshowButtons", this).removeClass('hideme');

		$(".previous", this).click(function() {
			slide.moveToPrevious();
		});
		$(".next", this).click(function() {
			slide.moveToNext();
		});
			
		var link = $(".slideshowLink", $(".slideshowImage", this));
		
		slides.each(function (i) {
			$(this).click(function() {
				slide.moveTo(i);
			});
		});
		
		slide.bind("onImageChange", function(e) {
			slides.removeClass("selected");
			$(slides[e.imageId]).addClass("selected");
			link.attr("href", bannerLinks[e.imageId]);
		});
		$(window).bind("resize", function() {
			sizeBanner()
		});
		$(window).bind("load", function() {
			image.css('max-width', objImage.width).width('100%');
			sizeBanner();
		});
	});
});
