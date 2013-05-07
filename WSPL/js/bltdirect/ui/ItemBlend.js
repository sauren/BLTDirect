using('evance.net.Request');
using('mootools.Class.Extras');
using('mootools.Function');
using('mootools.Fx.Base');
using('mootools.Fx.CSS');
using('mootools.Fx.Style');
using('mootools.Fx.Styles');
using('mootools.Fx.Transitions');

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.ItemBlend = new Class({
	_className: 'ItemBlend',
	_classOwner: 'bltdirect.ui.ItemBlend',
	_items: null,
	_request: null,
	_requestURL: '',
	_itemContainer: '',
	_imageContainer: '',
	_navContainer: '',
	_itemBlendInterval: null,
	_navBlendTimeout: null,
	_restartTimeout: null,
	_currentIndex: -1,
	_lastIndex: -1,
	_fadeInAnimation: null,
	_fadeInAnimation2: null,
	_fadeOutAnimation: null,
	_fadeOutAnimation2: null,
	_navImgOn: '',
	_navImgOff: '',
	_itemLoadTimeout: null,
	_stopped: true,
	_active: true,
	id: '',
	duration: 5000,
	restartWait: 5000,
	itemBlendTime: 1000,
	navBlendTime: 500,
	limit: 0,
	
	init: function() {
		this._items = new Array();
		
		this.id = (new Date().getTime()).toString(16);
		
		this.request = new evance.net.Request();
		this.request.addListener(this);
	},
	
	load: function() {	
		this.request.post(this._requestURL, 'limit=' + this.limit);
	},
	
	addItem: function(item) {
		this._items.push(item);
	},
	
	setRequestURL: function(url) {
		this._requestURL = url;
	},
	
	setItemContainer: function(container) {
		this._itemContainer = container;
	},
	
	setImageContainer: function(container) {
		this._imageContainer = container;
	},

	setNavContainer: function(container) {
		this._navContainer = container;
	},
	
	setNavImageOn: function(img) {
		this._navImgOn = img;
	},
	
	setNavImageOff: function(img) {
		this._navImgOff = img;
	},
	
	onHttpRequestResponse: function(data) {
		this._itemContainer = $(this._itemContainer);
		this._imageContainer = $(this._imageContainer);
		this._navContainer = $(this._navContainer); 
		
		if(this._itemContainer) {
			var items = data.response.split('{br}{br}');
			items.pop();
			
			for(var i=0; i<items.length; i++) {
				this.addItem(items[i].split('{br}'));
			}		
		}
	},

	onHttpRequestError: function(data) {
		evance.debug(data.status);
	},
	
	start: function() {
		var self = this;
		
		this._stopped = false;
		
		if(this._navContainer && (this._navImgOn.length > 0) && (this._navImgOff.length > 0)) {
			this.loadNextNav(0);
		} else {
			this.loadNextItem();
			
			if(this._itemBlendInterval) {
				clearInterval(this._itemBlendInterval);
			}
			
			this._itemBlendInterval = setInterval(function() {
				if($defined(self._items)) {			
					if(!self.loadNextItem()) {
						self._currentIndex = -1;
						
						if(!self.loadNextItem()) {
							clearInterval(self._itemBlendInterval);
						}
						
					} else if(self._currentIndex >= self._items.length) {
						self._currentIndex = -1;
					}
				} else {
					clearInterval(self._itemBlendInterval);
				}
			}, this.duration);
		}
	},
	
	restart: function(index) {
		var self = this;

		this._stopped = false;
		this._currentIndex = index;
		
		this._itemBlendInterval = setInterval(function() {
			if($defined(self._items)) {				
				if(!self.loadNextItem()) {
					self._currentIndex = -1;
					
					if(!self.loadNextItem()) {
						clearInterval(self._itemBlendInterval);
					}
					
				} else if(self._currentIndex >= self._items.length) {
					self._currentIndex = -1;
				}
			} else {
				clearInterval(self._itemBlendInterval);
			}
		}, this.duration);
	},
	
	stop: function(index) {
		var self = this;
		
		if(!this._active) {
			this._currentIndex = index - 1;
			
			if(!this._stopped) {
				if(this._itemBlendInterval) {
					clearInterval(this._itemBlendInterval);
					
					this._stopped = true;
				}
			}
			
			if(this._restartTimeout) {
				clearTimeout(this._restartTimeout);
			}

			this._restartTimeout = setTimeout(function() {
				self.restart(index);
			}, this.restartWait);
			
			if($defined(this._items)) {			
				if(!this.loadNextItem()) {
					this._currentIndex = -1;
					
					if(!this.loadNextItem()) {
						clearInterval(this._itemBlendInterval);
					}
					
				} else if(this._currentIndex >= this._items.length) {
					this._currentIndex = -1;
				}
			} else {
				clearInterval(this._itemBlendInterval);
			}
		}
	},
	
	loadNextNav: function(index) {
		var self = this;
		var element = null;
		var animation = null;
		
		if(this._navBlendTimeout) {
			clearTimeout(this._navBlendTimeout);
		}
		
		if(index <= this._items.length) {
			element = $(this.id + '-' + index + '-nav');
			
			if(element) {
				element.style.display = '';	
			
				animation = new Fx.Style(element.id, 'opacity', {duration: this.navBlendTime});
				animation.set(0);
				animation.start(1);
			
				this._navBlendTimeout = setTimeout(function() {
					self.loadNextNav(index + 1);
				}, this.navBlendTime / 4);
			} else {
				this.loadNextNav(index + 1);
			}
		} else {
			this.loadNextItem();
			
			if(this._itemBlendInterval) {
				clearInterval(this._itemBlendInterval);
			}
			
			this._itemBlendInterval = setInterval(function() {
				if($defined(self._items)) {			
					if(!self.loadNextItem()) {
						self._currentIndex = -1;
						
						if(!self.loadNextItem()) {
							clearInterval(self._itemBlendInterval);
						}
						
					} else if(self._currentIndex >= self._items.length) {
						self._currentIndex = -1;
					}
				} else {
					clearInterval(self._itemBlendInterval);
				}
			}, this.duration);
		}
	},
	
	loadNextItem: function() {
		var self = this;
		var item = null;
		var nav = null;
		var lastNav = null;
		
		for(var i=0; i<this._items.length; i++) {
			if(i > this._currentIndex) {
				item = $(this.id + '-' + i);
				
				if(item) {
					nav = $(this.id + '-' + i + '-nav');
				
					if(this.fadeOutItem(this._lastIndex, i)) {						
						if(nav) {
							this._itemLoadTimeout = setTimeout(function() {
								for(var j=0; j<self._items.length; j++) {
									lastNav = $(self.id + '-' + j + '-nav');
									
									if(lastNav) {
										lastNav.src = self._navImgOff;
									}
								}
								
								nav.src = self._navImgOn;
							}, this.itemBlendTime);
						}
					} else {
						this.fadeInItem(i)
						
						if(nav) {
							nav.src = self._navImgOn;
						}
					}
										
					this._currentIndex = i;
					this._lastIndex = i;
					
					return true;
				}
			}
		}
		
		return false;
	},
	
	fadeOutItem: function(index, nextIndex) {
		var self = this;
		var element = $(this.id + '-' + index);
		var image = null;

		if(element) {
			this._active = true;
			
			if($defined(this._fadeOutAnimation) && $defined(this._fadeOutAnimation.stop)) {
				this._fadeOutAnimation.stop();
			}
			
			this._fadeOutAnimation = new Fx.Style(element.id, 'opacity', {
				duration: this.itemBlendTime,
				onComplete: function() {
					element.style.display = 'none';
					
					self.fadeInItem(nextIndex);
				}});
			this._fadeOutAnimation.set(1);
			this._fadeOutAnimation.start(0);
			
			image = $(element.id + '-image');
			
			if(image) {
				if($defined(this._fadeOutAnimation2) && $defined(this._fadeOutAnimation2.stop)) {
					this._fadeOutAnimation2.stop();
				}
			
				this._fadeOutAnimation2 = new Fx.Style(image.id, 'opacity', {
					duration: this.itemBlendTime,
					onComplete: function() {
						image.style.display = 'none';
						
						self.fadeInItem(nextIndex);
					}});
				this._fadeOutAnimation2.set(1);
				this._fadeOutAnimation2.start(0);
			}
			
			return true;
		}
		
		return false;
	},
	
	fadeInItem: function(index) {
		var self = this;
		var element = $(this.id + '-' + index);
		var image = null;
		
		if(element) {		
			if($defined(this._fadeInAnimation) && $defined(this._fadeInAnimation.stop)) {
				this._fadeInAnimation.stop();
			}
			
			element.style.display = '';	
		
			this._fadeInAnimation = new Fx.Style(element.id, 'opacity', {
				duration: this.itemBlendTime,
				onComplete: function() {
					self._active = false;
				}});
			this._fadeInAnimation.set(0);
			this._fadeInAnimation.start(1);
			
			image = $(element.id + '-image');
			
			if(image) {
				if($defined(this._fadeInAnimation2) && $defined(this._fadeInAnimation2.stop)) {
					this._fadeInAnimation2.stop();
				}
				
				image.style.display = '';	
			
				this._fadeInAnimation2 = new Fx.Style(image.id, 'opacity', {
					duration: this.itemBlendTime,
					onComplete: function() {
						self._active = false;
					}});
				this._fadeInAnimation2.set(0);
				this._fadeInAnimation2.start(1);
			}
			
			return true;
		}
		
		return false;
	}
});