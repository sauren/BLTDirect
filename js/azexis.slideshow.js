var Azexis = Azexis || {};


(function($) {
	Azexis.slideshow = function(banner, images, colours, options) {
	/*
		Variables
		---------
	*/
		var banner = $(banner);
		var bannerImage = $("img", banner);
		var self = evance.events();
		var springDuration = 500; // Spring a partially completed fade to completion.
		var images = images;
		var colours = colours;
		var cachedImages = [true];
		var failedInARow = 0;
		// Actually mid-fade, which is never stopped.
		var animating = false;
		// Don't start any new fades automatically, but user interaction can still
		// force a single fade.
		var paused = false;
		var forceSingle = false;
		var lastTime = new Date;
		
		Azexis.slideshow.defaults = {
			autoPlay: true,
			imagesDir: "",
			duration: 1500,
			userDuration: 1000,
			wait: 5000,
			width: banner.innerWidth(),
			height: banner.innerHeight(),
			backgroundPosition: "left top"
		};
		
		var options = $.extend({}, Azexis.slideshow.defaults, options);
		
		// play automatically?
		paused = !options.autoPlay;
		
		var fader = $("<div>").css({
			position: "absolute",
			'z-index':1,
			top: 0,
			left: 0,
			width: '100%',
			height: options.height,
			backgroundRepeat: "no-repeat",
			backgroundPosition: options.backgroundPosition
		});

		var faderImage = $("<img class='faderImage'>", fader).css({
			position: "relative",
			"max-height": 300
		});
		
		// Current image is the index of the image that is fully loaded and moved to
		// the background div.
		var nxt = 1;
		
	/*
		Methods
		---------
	*/
		function mod(a,b) { var n = a%b; return n < 0 ? n+b : n; }
		function currentImage() { if(images) return mod(nxt-1,images.length); }
		function nextImage() { if(images) return mod(nxt,images.length); }
		function advanceImage() { nxt += 1; }
	
		// User manually changes to another slide.
		function changeNext(n) {
			nxt = n;
			
			// Don't interrupt in-progress animations, but switch immediately if we can.
			if (!animating) {
				resetFader();
			} else {
				// I don't know what these did but they look like there were left over from mootools
				// but, everytime i remove them something stops working even though they throw and error
				//fx.stop();
				//fx.setOptions({duration: springDuration});
				//fx.start(1);
				//resetFader();
			}
			
			// User interation always force a single move even if the animation is paused.
			forceSingle = true;
			eventHandler();
		}

		function imageToData(n) {
			return options.imagesDir + images[n];
		}
		
		function imageToCSS(n) {
			return "url(" + options.imagesDir + images[n] + ")";
		}

		function colourToCSS(n) {
			return "#" + colours[n];
		}
		
		function loadNextImage() {
			var img = new Image;
			
			// Store the current imageCache and number in a closure. If they change
			// during the time it takes to load, the next cache won't get the incorrect
			// values written to it.
			img.onload = (function(cache, n) {
				return function() {
					failedInARow = 0;
					fader.height(img.height);
					fader.css("background-color", colourToCSS(n));
					fader.attr("data-image", imageToData(n));
					fader.attr("data-image-width", img.width);
					faderImage.attr("src", imageToData(n));
					faderImage.css("max-width", img.width).width('100%');
					cache[n] = true;
					eventHandler();
				}
			})(cachedImages, nextImage());
			
			img.onerror = function() {
				// Can't load the image, just try the next.
				if (failedInARow > 10) {
					// Don't keep looking forever.
					return;
				}
				failedInARow++;
				advanceImage();
				resetFader();
			}
			
			// Start load process.
			if(images) {
				img.src = options.imagesDir + images[nextImage()];
			}
		}
		
		function updateCurrentImage() {
			$(bannerImage).css({
				'max-width': '',
				'max-height': ''
			}).width('').height('');
			$(bannerImage).attr("src", fader.attr("data-image"));
			$(bannerImage).css({
				'max-width': parseInt(fader.attr("data-image-width"),10),
				'max-height': 300
			}).width('100%');

			$(banner).height($(bannerImage).height());

			$(banner).css("background-color", fader.css("background-color"));
		}
		
		function resetFader() {
			fader.css("opacity", 0);
			loadNextImage();
		}
		
		function eventHandler() {
			
			if (animating) {
				return;
			}
			
			if (!forceSingle && ((new Date) - lastTime) < options.wait) {
				// Come back when the time is right.
				setTimeout(eventHandler, options.wait - ((new Date) - lastTime));
				return;
			}
			
			if (!cachedImages[nextImage()]) {
				// Not cached yet, the event handler will be called when the cache is
				// complete.
				return;
			}
			
			if (paused && !forceSingle) { return; }
			
			self.trigger('onBeforeImageChange', { 
				type: 'onBeforeImageChange',
				imageId: currentImage(),
				src: images[currentImage()]
			});
			
			// check again to check if a listener paused 
			// the slideshow before the image was shown
			if (paused && !forceSingle) { return; }
			
			
			var fadeDuration = options.duration;
			if (forceSingle) {
				forceSingle = false;
				fadeDuration = options.userDuration;
			} 
			self.trigger('onImageChange', { 
				type: 'onImageChange',
				imageId: nextImage(),
				src: images[nextImage()]
			});
			
			fader.animate({opacity: 1}, fadeDuration, "linear", function() {
				lastTime = new Date;
				updateCurrentImage();
				
				// Delay added to prevent flicker during image swap.
				setTimeout(function() {
					animating = false;
					resetFader();
					eventHandler();
					self.trigger('onAfterImageChange', { 
						type: 'onAfterImageChange',
						imageId: currentImage(),
						src: images[currentImage()]
					});
				}, 100);
			});
			animating = true;
			advanceImage();
		}
		
	/*
		Public Methods
		---------------
	*/
		self = $.extend({
			loadImageSequence: function(seq) {
				images = seq;
				cachedImages = [];
				changeNext(0);
			},
			
			pause: function() {
				paused = true;
			},
			
			resume: function() {
				paused = false;
				eventHandler();
			},
			
			play: function(){
				paused = false;
				eventHandler();
			},
			
			moveTo: function(n) {
				changeNext(n);
			},
			
			moveToNext: function() {
				changeNext(currentImage()+1);
			},
			
			moveToPrevious: function() {
				changeNext(currentImage()-1);
			}
		}, self);
		
	/*
		Logic
		------
	*/
		banner.append(fader);
		fader.append(faderImage);
		resetFader();
		eventHandler();
		return self;
	};
})(jQuery);
