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

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.LeftPane = evance.core.UIObject.extend({
	_className: 'LeftPane',
	_classOwner: 'bltdirect.ui.LeftPane',
	_animation: null,
	_collapseHeight: 0,
	_locked: false,
	_mask: null,
	_container: null,
	_cap: null,
	_capImgOpen: '',
	_capImgClosed: '',
	_list: null,
	_mouseTimeout: null,
	_minItems: 0,
	_maxItems: 0,
	_minimiseArea: '',
	duration: 500,
	isCollapsed: false,
	isMouseOver: false,

	init: function(){
		this.parent(arguments[0]);
	},

	onLoad: function(e){
		evance.Mouse.addListener(this);
	},

	onMouseOver: function(e) {
		this.parent(e);
		this.isMouseOver = true;
		this.expand();

		evance.event.stopEventPropogation(e);
	},

	onMouseMove: function(e){
		if(this.isMouseOver){
			if(evance.Mouse.isOver(this._minimiseArea)) {
				if(this._mouseTimeout) {
					clearTimeout(this._mouseTimeout);

					this._mouseTimeout = null;
				}
			} else {
				if(!this._mouseTimeout) {
					this.isMouseOver = false;

					var self = this;

					this._mouseTimeout = setTimeout(function() {
						self.collapse();
					}, 1000);
				}
			}
		}
	},

	setMask: function(id) {
		this._mask = $(id);

		if(this._mask) {
			this._mask.style.overflow = 'hidden';
		}
	},

	setContainer: function(id) {
		this._container = $(id);
	},

	setList: function(id) {
		var items = new Array();
		var lastIndex = 0;

		this._list = $(id);

		if(this._list) {
			while(this._list.firstChild) {
				if(this._list.firstChild.nodeType == 1) {
					items.push(this._list.firstChild);
				}

				this._list.removeChild(this._list.firstChild);
			}

			for(var i=0; i<items.length; i++) {
				if(i<this._minItems) {
					this._list.appendChild(items[i]);

					lastIndex = i;
				}
			}

			this._collapseHeight = this._mask.offsetHeight;

			for(var i=lastIndex; i<items.length; i++) {
				this._list.appendChild(items[i]);
			}

			if(!evance.Mouse.isOver(this.element)) {
				this.collapse({animate: true});
			}
		}
	},

	setCap: function(id, imgOpen, imgClosed) {
		this._cap = $(id);
		this._capImgOpen = imgOpen;
		this._capImgClosed = imgClosed;

		if(this._cap && (this._capImgOpen.length > 0)) {
			this._cap.style.backgroundImage = 'url(' + this._capImgOpen + ')';
		}
	},

	setMinimumItems: function(min) {
		this._minItems = min;

	},

	setMinimiseArea: function(area) {
		this._minimiseArea = area;
	},

	expand: function(data) {
		if(this.isCollapsed) {
			this.onExpand(data);
		}
	},

	collapse: function(data) {
		if(!this.isCollapsed) {
			this.onCollapse(data);
		}
	},

	onExpand: function(e) {
		if(!this._locked) {
			if($defined(this._mask) && $defined(this._container)) {
				if(!$defined(e) || ($defined(e) && e.animate)) {
					var self = this;

					this._locked = true;

					if($defined(this._animation) && $defined(this._animation.stop)) {
						this._animation.stop();
					}

					this._mask.style.height = this._collapseHeight + 'px';

					this._animation = new Fx.Style(this._mask.id, 'height', {
						duration: this.duration,
						onComplete: function(){
							self.isCollapsed = !self.isCollapsed;

							self._locked = false;
							self._mask.style.height = 'auto';

							if(self._cap && (self._capImgClosed.length > 0)) {
								self._cap.style.backgroundImage = 'url(' + self._capImgClosed + ')';
							}
						}});

					this._animation.set(this._mask.style.height);
					this._animation.start(this._container.offsetHeight);
				} else {
					this._mask.style.height = 'auto';
					this.isCollapsed = false;

					if(this._cap && (this._capImgClosed.length > 0)) {
						this._cap.style.backgroundImage = 'url(' + this._capImgClosed + ')';
					}
				}
			}
		}
	},

	onCollapse: function(e) {
		if(!this._locked) {
			if($defined(this._mask) && $defined(this._container)) {
				if(!$defined(e) || ($defined(e) && e.animate)) {
					var self = this;

					this._locked = true;

					if($defined(this._animation) && $defined(this._animation.stop)) {
						this._animation.stop();
					}

					this._mask.style.height = this._mask.offsetHeight + 'px';

					this._animation = new Fx.Style(this._mask.id, 'height', {
						duration: this.duration,
						onComplete: function(){
							self.isCollapsed = !self.isCollapsed;

							self._locked = false;

							if(self._cap && (self._capImgOpen.length > 0)) {
								self._cap.style.backgroundImage = 'url(' + self._capImgOpen + ')';
							}
						}});

					this._animation.start(this._collapseHeight);
				} else {
					this._mask.style.height = this._collapseHeight + 'px';
					this.isCollapsed = true;

					if(this._cap && (this._capImgOpen.length > 0)) {
						this._cap.style.backgroundImage = 'url(' + this._capImgOpen + ')';
					}
				}
			}
		}
	}
});

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.RightPane = evance.core.UIObject.extend({
	_className: 'RightPane',
	_classOwner: 'bltdirect.ui.RightPane',
	_animation: null,
	_collapseHeight: 0,
	_locked: false,
	_mask: null,
	_container: null,
	_cap: null,
	_capImgOpen: '',
	_capImgClosed: '',
	_mouseTimeout: null,
	_minimiseArea: '',
	duration: 500,
	isCollapsed: true,
	isMouseOver: false,

	init: function(){
		this.parent(arguments[0]);
	},

	onLoad: function(e){
		evance.Mouse.addListener(this);
	},

	onMouseOver: function(e) {
		this.parent(e);
		this.isMouseOver = true;
		this.expand();

		evance.event.stopEventPropogation(e);
	},

	onMouseMove: function(e){
		if(this.isMouseOver){
			if(evance.Mouse.isOver(this._minimiseArea)) {
				if(this._mouseTimeout) {
					clearTimeout(this._mouseTimeout);

					this._mouseTimeout = null;
				}
			} else {
				if(!this._mouseTimeout) {
					this.isMouseOver = false;

					var self = this;

					this._mouseTimeout = setTimeout(function() {
						self.collapse();
					}, 1000);
				}
			}
		}
	},

	setMask: function(id) {
		this._mask = $(id);

		if(this._mask) {
			this._mask.style.overflow = 'hidden';
			this._mask.style.height = this._collapseHeight + 'px';
		}
	},

	setContainer: function(id) {
		this._container = $(id);
	},

	setCap: function(id, imgOpen, imgClosed) {
		this._cap = $(id);
		this._capImgOpen = imgOpen;
		this._capImgClosed = imgClosed;

		if(this._cap && (this._capImgOpen.length > 0)) {
			this._cap.style.backgroundImage = 'url(' + this._capImgOpen + ')';
		}
	},

	setMinimiseArea: function(area) {
		this._minimiseArea = area;
	},

	expand: function(data) {
		if(this.isCollapsed) {
			this.onExpand(data);
		}
	},

	collapse: function(data) {
		if(!this.isCollapsed) {
			this.onCollapse(data);
		}
	},

	onExpand: function(e) {
		if(!this._locked) {
			if($defined(this._mask) && $defined(this._container)) {
				if(!$defined(e) || ($defined(e) && e.animate)) {
					var self = this;

					this._locked = true;

					if($defined(this._animation) && $defined(this._animation.stop)) {
						this._animation.stop();
					}

					this._mask.style.height = this._collapseHeight + 'px';

					this._animation = new Fx.Style(this._mask.id, 'height', {
						duration: this.duration,
						onComplete: function(){
							self.isCollapsed = !self.isCollapsed;

							self._locked = false;
							self._mask.style.height = 'auto';

							if(self._cap && (self._capImgClosed.length > 0)) {
								self._cap.style.backgroundImage = 'url(' + self._capImgClosed + ')';
							}
						}});

					this._animation.set(this._mask.style.height);
					this._animation.start(this._container.offsetHeight);
				} else {
					this._mask.style.height = 'auto';
					this.isCollapsed = false;

					if(this._cap && (this._capImgClosed.length > 0)) {
						this._cap.style.backgroundImage = 'url(' + this._capImgClosed + ')';
					}
				}
			}
		}
	},

	onCollapse: function(e) {
		if(!this._locked) {
			if($defined(this._mask) && $defined(this._container)) {
				if(!$defined(e) || ($defined(e) && e.animate)) {
					var self = this;

					this._locked = true;

					if($defined(this._animation) && $defined(this._animation.stop)) {
						this._animation.stop();
					}

					this._mask.style.height = this._mask.offsetHeight + 'px';

					this._animation = new Fx.Style(this._mask.id, 'height', {
						duration: this.duration,
						onComplete: function(){
							self.isCollapsed = !self.isCollapsed;

							self._locked = false;

							if(self._cap && (self._capImgOpen.length > 0)) {
								self._cap.style.backgroundImage = 'url(' + self._capImgOpen + ')';
							}
						}});

					this._animation.start(this._collapseHeight);
				} else {
					this._mask.style.height = this._collapseHeight + 'px';
					this.isCollapsed = true;

					if(this._cap && (this._capImgOpen.length > 0)) {
						this._cap.style.backgroundImage = 'url(' + this._capImgOpen + ')';
					}
				}
			}
		}
	}
});

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.Menu = new Class({
	_className: 'Menu',
	_classOwner: 'bltdirect.ui.Menu',
	classes: null,
	instance: null,
	items: null,
	id: 0,
	interval: null,
	timeout: 500,
	isOpen: false,
	operation: 'onmouseover',

	init: function() {
		this.instance = arguments[0];

		this.classes = new Array();
		this.items = new Array();
	},

	load: function() {
		this.build();
	},

	addClass: function(id, name, direction){
		this.classes[this.classes.length] = new bltdirect.ui.MenuClass(id, name, direction);
	},

	getClass: function(id){
		for(var i=0; i < this.classes.length; i++){
			if(id == this.classes[i].id) return this.classes[i];
		}
	},

	add: function(id, parent, label, url, icon, classId){
		if(parent == null){
			this.items[this.items.length] = new bltdirect.ui.MenuItem(id, this, label, url, icon, classId, this.instance);
			return true;
		} else {
			for(var i=0; i < this.items.length; i++){
				var node = this.items[i];
				if(node.add(id, parent, label, url, icon, classId, this.instance)){
					node.hasChildren = true;
					return true;
				}
			}
		}
		return false;
	},

	onRelease: function(id){
		this.isOpen = !this.isOpen;
		if(this.isOpen){
			this.open(id);
		} else {
			this.close();
		}
	},

	onRightClick: function(id){
		this.isOpen = true;
		this.open(id);
	},

	onRollOver: function(id){
		clearTimeout(this.interval);
		if(this.isOpen || (this.operation == 'onmouseover' && !this.isOpen)) this.open(id);
	},

	onRollOut: function(id){
		var ref = this;
		this.interval = setTimeout(function(){ref.close();}, this.timeout);
	},

	open: function(id){
		for(var i=0; i < this.items.length; i++){
			if(this.items[i].open(id)) return;
		}
	},

	close: function(){
		for(var i=0; i < this.items.length; i++){
			this.items[i].close();
		}
		this.isOpen = false;
	},

	closeChildrenExcept: function(id){
		for(var i=0; i < this.items.length; i++){
			if(this.items[i].id != id) this.items[i].close();
		}
	},

	build: function(){
		for(var i=0; i < this.items.length; i++){
			this.items[i].build();
		}
	}
});

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.MenuClass = new Class({
	_className: 'MenuClass',
	_classOwner: 'bltdirect.ui.MenuClass',
	id: 0,
	name: null,
	direction: null,

	init: function() {
		this.id = arguments[0];
		this.name = arguments[1];
		this.direction = arguments[2];
	}
});

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.MenuItem = new Class({
	_className: 'MenuItem',
	_classOwner: 'bltdirect.ui.MenuItem',
	id: 0,
	parent:  null,
	instance:  null,
	menuClass:  null,
	icon:  '',
	label:  '',
	url:  '',
	container:  '',
	isOpen:  false,
	items:  null,
	hasChildren:  false,
	interval:  null,
	isDisabled:  false,

	init: function() {
		this.id = arguments[0];
		this.parent = arguments[1];
		this.label = arguments[2];
		this.url = arguments[3];
		this.icon = arguments[4];
		this.instance = arguments[6];
		this.menuClass = this.getClass(arguments[5]);

		this.container = this.instance + '_' + this.id + '_Container';
		this.items = new Array();
	},

	getClass: function(classId){
		var menu = eval(this.instance);
		var classObject = menu.getClass(classId);
		return classObject;
	},

	add: function(id, parent, label, url, icon, classId, instance){
		if(parent == this.id){
			this.items[this.items.length] = new bltdirect.ui.MenuItem(id, this, label, url, icon, classId, instance);
			return true;
		} else {
			for(var i=0; i < this.items.length; i++){
				var node = this.items[i];
				if(node.add(id, parent, label, url, icon, classId, instance)){
					node.hasChildren = true;
					return true;
				}
			}
			return false
		}
		return false;
	},

	build: function(){
		if(this.hasChildren){
			var div = null;

			if(!document.getElementById(this.container)){
				div = document.createElement('div');
				div.id = this.container;
				div.className = this.menuClass.name;

				document.body.appendChild(div);
			} else {
				div = document.getElementById(this.container);
			}

			var html = '<ul>';
			for(var i=0; i < this.items.length; i++){
				html += this.items[i].getHtml((i == (this.items.length - 1)) ? true : false);
				this.items[i].build();
			}
			html += '</ul>';
			div.innerHTML = html;
		}
	},

	closeChildrenExcept: function(id){
		for(var i=0; i < this.items.length; i++){
			if(this.items[i].id != id) this.items[i].close();
		}
	},

	open: function(id){
		if(id == this.id){
			this.rollOver();
			this.display(id);
			return true;
		} else {
			for(var i = 0; i < this.items.length; i++){
				if(this.items[i].open(id)) return true;
			}
			return false;
		}
	},

	rollOver: function(){
		var object = document.getElementById(this.id);
		object.className = 'over';
		if(this.parent.rollOver) this.parent.rollOver();
		object = null;
	},

	display: function(){
		// close all submenus of the parent.
		this.parent.closeChildrenExcept(this.id);

		if(this.hasChildren){
			var object = document.getElementById(this.id);

			if(object) {
				//object.className = 'open';
				var position = this.getPosition(object);

				var container = document.getElementById(this.container);

				if(container) {
					container.style.display = 'block';
					container.style.position = 'absolute';

					if(this.menuClass.direction == 'down'){
						container.style.left = position.x + 'px';
						container.style.top = position.y + object.offsetHeight + 'px';
					} else if(this.menuClass.direction == 'left'){
						container.style.left = position.x + object.offsetWidth + 'px';
						container.style.top = position.y + 'px';
					} else if(this.menuClass.direction == 'context'){
						container.style.left = Mouse._x -3 + 'px';
						container.style.top = Mouse._y -3 + 'px';
					}

					container = null;
				}

				object = null;
			}
		}
	},

	getPosition: function(element){
		var top = 0;
		var left = 0;
		var e = element;

		while(e.offsetParent){
			top += e.offsetTop;
			left += e.offsetLeft;
			e = e.offsetParent;
		}
		top += e.offsetTop;
		left += e.offsetLeft;
		return {x:left, y:top};
	},

	close: function(){
		var object = document.getElementById(this.id);

		if(object) {
			object.className = 'out';
			object = null;
		}

		if(this.hasChildren){
			var container = document.getElementById(this.container);

			if(container) {
				container.style.display = 'none';
				container = null;
			}
		}

		// close its children
		if(this.hasChildren){
			for(var i=0; i < this.items.length; i++){
				this.items[i].close();
			}
		}
	},

	getHtml: function(isLast){
		var html = '<li ' + ((isLast) ? 'class="last"' : '') + '>';
		html += '<a href="'+this.url+'" onmouseover="'+this.instance+'.onRollOver(\''+this.id+'\');" onmouseout="'+this.instance+'.onRollOut(\''+this.id+'\');" id="'+this.id+'">';
		html += '<span class="capLeft"></span>';
		html += '<span class="label">'+this.label+'</span>';
		if(this.hasChildren) html += '<span class="subMenuIcon"></span>';
		html += '<span class="capRight"></span>';
		html += '</a>';
		html += '</li>';
		return html;
	}
});

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.OptionGroup = new Class({
	_className: 'OptionGroup',
	_classOwner: 'bltdirect.ui.OptionGroup',
	_options: null,
	_expandAnimation: null,
	_collapseAnimation: null,
	_lockedExpand: false,
	_lockedCollapse: false,
	duration: 500,

	load: function() {
		this._options = new Array();
	},

	addOption: function(id){
		var element = $(id + 'Mask');

		if(element) {
			element.style.overflow = 'hidden';
		}

		this._options.push(id);
	},

	expand: function(data) {
		if(!this._lockedExpand) {
			if(this._options) {
				for(var i=0; i<this._options.length; i++) {
					if(data.element.id == this._options[i]) {
						var mask = $(this._options[i] + 'Mask');

						if(mask) {
							if(mask.style.height == '0px') {
								if(!$defined(data) || !$defined(data.animate) || data.animate) {
									var self = this;
									var container = $(this._options[i] + 'Container');

									this._lockedExpand = true;

									if($defined(this._expandAnimation) && $defined(this._expandAnimation.stop)) {
										this._expandAnimation.stop();
									}

									mask.style.height = '0px';

									this._expandAnimation = new Fx.Style(mask.id, 'height', {
										duration: this.duration,
										onComplete: function(){
											self._lockedExpand = false;

											mask.style.height = 'auto';
										}});

									this._expandAnimation.set(mask.style.height);
									this._expandAnimation.start(container.offsetHeight);

									this.collapse();
								} else {
									this.collapse({animate: false});

									mask.style.height = 'auto';
								}

								break;
							}
						}
					}
				}
			}
		}
	},

	collapse: function(data) {
		if(!this._lockedCollapse) {
			var mask;

			if(this._options) {
				for(var i=0; i<this._options.length; i++) {
					mask = $(this._options[i] + 'Mask');

					if(mask) {
						if(mask.style.height != '0px') {
							if(!$defined(data) || !$defined(data.animate) || data.animate) {
								var self = this;

								this._lockedCollapse = true;

								if($defined(this._collapseAnimation) && $defined(this._collapseAnimation.stop)) {
									this._collapseAnimation.stop();
								}

								mask.style.height = mask.offsetHeight + 'px';

								this._collapseAnimation = new Fx.Style(mask.id, 'height', {
									duration: this.duration,
									onComplete: function(){
										self._lockedCollapse = false;
									}});

								this._collapseAnimation.start(0);
							} else {
								mask.style.height = '0px';
							}
						}
					}
				}
			}
		}
	}
});

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.SpecialOfferButton = evance.core.UIObject.extend({
	_className: 'SpecialOfferButton',
	_classOwner: 'bltdirect.ui.SpecialOfferButton',
	_owner: null,
	_index: 0,

	init: function() {
		this.parent(arguments[0]);
	},

	draw: function(index) {
		this._index = index;

		if(this._owner) {
			this.parentElement = $(this._owner._navContainer.id);

			if(this.parentElement) {
				this.element = document.createElement('img');
				this.element.id = this.id;
				this.element.src = this._owner._navImgOff;
				this.element.height = 10;
				this.element.width = 10;
				this.element.style.visibility = 'hidden';
				this.element.alt = this._owner._items[index][1];

				this.parentElement.appendChild(this.element);
				this.parentElement.appendChild(document.createTextNode('\u00a0'));

				this.load();

				return true;
			}
		}

		return false;
	},

	setOwner: function(owner) {
		this._owner = owner;
	},

	onClick: function(e) {
		this._owner.stop(this._index);
	}
});

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.SpecialOffers = bltdirect.ui.ItemBlend.extend({
	_className: 'SpecialOffers',
	_classOwner: 'bltdirect.ui.SpecialOffers',

	init: function() {
		this.parent(arguments);

		this.limit = 6;
	},

	onHttpRequestResponse: function(data) {
		this.parent(data);

		var div = null;

		if(this._itemContainer) {
			this._itemContainer.innerHTML = '';

			for(var i=0; i<this._items.length; i++) {
				if(this._items[i].length == 5) {
					div = document.createElement('div');
					div.id = this.id + '-' + i;
					div.style.display = 'none';
					div.className = '';
					div.innerHTML += '<p class="offerWhite"><span class="offerPercentage">' + this._items[i][3] + '%</span><span class="offerOff">ff</span></p>';
					div.innerHTML += '<p><a href="./product.php?pid=' + this._items[i][0] + '">' + this._items[i][1] + '</a></p>';
					div.innerHTML += '<p class="offerPrice">Only <span class="offerWhite">&pound;' + this._items[i][2] + '</span></p>';

					this._itemContainer.appendChild(div);
				}
			}

			if(this._imageContainer) {
				this._imageContainer.innerHTML = '';

				for(var i=0; i<this._items.length; i++) {
					if(this._items[i].length == 5) {
						div = document.createElement('div');
						div.id = this.id + '-' + i + '-image';
						div.style.display = 'none';
						div.className = '';

						if(this._items[i][4].length > 1) {
							div.innerHTML += '<a href="/product.php?pid=' + this._items[i][0] + '" title="' + this._items[i][1] + '"><img src="' + this._items[i][4] + '" alt="' + this._items[i][1] + '" /></a>';
						}

						this._imageContainer.appendChild(div);
					}
				}
			}

			if(this._navContainer && (this._navImgOn.length > 0) && (this._navImgOff.length > 0)) {
				this._navContainer.innerHTML = '';

				for(var i=0; i<this._items.length; i++) {
					if(this._items[i].length == 5) {
						img = new bltdirect.ui.SpecialOfferButton(this.id + '-' + i + '-nav');
						img.setOwner(this);
						img.draw(i);
					}
				}
			}

			this.start();
		}
	}
});

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.Tab = new Class({
	_className: 'Tab',
	_classOwner: 'bltdirect.ui.Tab',
	_tabs: null,
	_tabItems: null,

	init: function() {
		this._tabs = new Array();
		this._tabItems = new Array();
	},

	addTab: function(tab) {
		this._tabs.push(tab);
		this._tabItems.push(new bltdirect.ui.TabItem);
	},

	addTabItem: function(tab, item) {
		for(var i=0; i<this._tabs.length; i++) {
			if(this._tabs[i] == tab) {
				this._tabItems[i].addItem(item);

				return true;
			}
		}

		return false;
	},

	showTab: function(tab) {
		for(var i=0; i<this._tabs.length; i++) {
			if(this._tabs[i] == tab) {
				this._tabItems[i].showItems();
			} else {
				this._tabItems[i].hideItems();
			}
		}
	}
});

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.TabItem = new Class({
	_className: 'Tabs',
	_classOwner: 'bltdirect.ui.TabItem',
	_items: null,

	init: function() {
		this._items = new Array();
	},

	addItem: function(item) {
		this._items.push(item);
	},

	showItems: function() {
		var element = null;

		for(var i=0; i<this._items.length; i++) {
			element = $(this._items[i]);

			if(element) {
				element.style.display = '';
			}
		}
	},

	hideItems: function() {
		var element = null;

		for(var i=0; i<this._items.length; i++) {
			element = $(this._items[i]);

			if(element) {
				element.style.display = 'none';
			}
		}
	}
});

if(!$defined(bltdirect)) var bltdirect = {};
if(!$defined(bltdirect.ui)){bltdirect.ui = {}};

bltdirect.ui.SearchBox = evance.core.UIObject.extend({
	_className: 'SearchBox',
	_classOwner: 'bltdirect.ui.SearchBox',
	_requestURL: '',
	_hideTimeout: null,
	request: null,
	requestData: null,
	results: null,
	items: null,
	itemsReplacements: null,
	itemsOverrides: null,

	init: function() {
		this.parent(arguments[0]);

		this.request = new evance.net.Request();
		this.request.setDelay(250);
		this.request.setCaching(true);
		this.request.addListener(this);

		this.requestData = new evance.net.RequestData();
		this.requestData.addItem('limit', '15');

		this.items = new Array();
		this.itemsReplacements = new Array();
		this.itemsOverrides = new Array();

		this.results = new evance.core.UIObject();
		this.results.addListener(this);
	},

	load: function() {
		this.parent();

		var element = document.createElement('div');
		element.id = 'SearchResults';
		element.style.top = (this.element.getPosition().y + this.element.offsetHeight) + 'px';
		element.style.left = this.element.getPosition().x + 'px';
		element.style.display = 'none';
		element.style.width = this.element.offsetWidth - 12;
		element.className = 'SearchResults';

		this.results.id = element.id;
		this.results.load();

		document.body.appendChild(element);
	},

	setRequestURL: function(url) {
		this._requestURL = url;
	},

	setTextField: function(textField) {
		this.id = textField;
	},

	closeWindow: function() {
		this.hideItems();
	},

	addItem: function(item) {
		this.items.push(item);
	},

	addItemReplacement: function(replacement) {
		this.itemsReplacements.push(replacement);
	},

	addItemOverride: function(override) {
		this.itemsOverrides.push(override);
	},

	prepareMatch: function(val){
		val = val.replace(/\\/g, '\\\\');
		val = val.replace(/\//g, '\\\/');

		return val;
	},

	drawItems: function() {
		var self = this;
		var items = null;
		var pos = 0;
		var contents = '';
		var values = this.element.value.replace(/-/g, '').replace(/\./g, '').replace(/\//g, '').replace(/\(/g, '').replace(/\)/g, '').split(/\s/g);
		var anchor = null;
		var div = null;
		var productTitle = '';
		var productSku = '';
		var parsedInteger = 0;
		var replacement = '';
		var parts = null;

		if(this.itemsReplacements.length > 0) {
			contents += '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
			contents += '<tr><td class="ResultReplacements">';
			contents += '<strong>Did you mean?</strong> ';

			for(var i=0; i<this.itemsReplacements.length; i++) {
				contents += '<a href="javascript:searchReplace(\'' + this.itemsReplacements[i] + '\');">' + this.itemsReplacements[i] + '</a>';

				if((i+1) < this.itemsReplacements.length) {
					contents += ', ';
				}
			}
			
			contents += '</td></tr>';
			contents += '</table>';
		}

		if(this.itemsOverrides.length > 0) {
			contents += '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
			contents += '<tr><td class="ResultOverrideHeader">';
			contents += '<strong>Have you considered?</strong> ';
			contents += '</td></tr>';

			for(var i=0; i<this.itemsOverrides.length; i++) {
				parts = this.itemsOverrides[i].split('{br}');
				
				if(parts[0] == 'product') {
					contents += '<tr><td class="ResultOverride ResultOverrideProduct">Product: <a href="product.php?pid=' + parts[1] + '">' + parts[2] + '</a></td></tr>';

				} else if(parts[0] == 'category') {
					contents += '<tr><td class="ResultOverride ResultOverrideCategory">Category: <a href="products.php?cat=' + parts[1] + '">' + parts[2] + '</a></td></tr>';
				}
			}
			
			contents += '</table>';
		}
		
		if(this.items.length > 0) {
			contents += '<table width="100%" border="0" cellspacing="0" cellpadding="0">';

			parsedInteger = parseInt(this.requestData.getItem('criteria'));

			if(!isNaN(parsedInteger)) {
				for(var i=0; i<this.items.length; i++) {
					if(parsedInteger == this.items[i][1]) {
						items = this.items[i][0].toLowerCase().split(/\s/g);

						for(var j=0; j<values.length; j++) {
							for(var k=0; k<items.length; k++) {
								if(items[k].replace(' ', '').length > 0) {
									items[k] = items[k].replace(eval('/^' + this.prepareMatch(values[j]) + '/gi'), ' <span class="Match">' + values[j] + '</span>');
								}
							}
						}

						productTitle = items.join(' ');

						if(this.items[i][2]) {
							items = this.items[i][2].toLowerCase().split(/\s/g);

							for(var j=0; j<values.length; j++) {
								for(var k=0; k<items.length; k++) {
									if(items[k].replace(' ', '').length > 0) {
										items[k] = items[k].replace(eval('/' + this.prepareMatch(values[j]) + '/gi'), '<span class="Match">' + values[j] + '</span>');
									}
								}
							}

							productSku = items.join(' ');
						} else {
							productSku = '';
						}

						contents += '<tr class="ResultDirectMatch"><td class="ResultLeft"><a href="product.php?pid=' + this.items[i][1] + '">' + productTitle + '<br /><span class="ResultLeftSub">' + productSku + '</span></a></td><td class="ResultRight"><a href="product.php?pid=' + this.items[i][1] + '">' + this.items[i][1] + '</a></td></tr>';

						this.items.splice(i, 1);
						
						break;
					}
				}
			}

			for(var i=0; i<this.items.length; i++) {
				items = this.items[i][0].toLowerCase().split(/\s/g);

				for(var j=0; j<values.length; j++) {
					for(var k=0; k<items.length; k++) {
						if(items[k].replace(' ', '').length > 0) {
							items[k] = items[k].replace(eval('/^' + this.prepareMatch(values[j]) + '/gi'), ' <span class="Match">' + values[j] + '</span>');
						}
					}
				}

				productTitle = items.join(' ');

				if(this.items[i][2]) {
					items = this.items[i][2].toLowerCase().split(/\s/g);

					for(var j=0; j<values.length; j++) {
						for(var k=0; k<items.length; k++) {
							if(items[k].replace(' ', '').length > 0) {
								items[k] = items[k].replace(eval('/' + this.prepareMatch(values[j]) + '/gi'), '<span class="Match">' + values[j] + '</span>');
							}
						}
					}

					productSku = items.join(' ');
				} else {
					productSku = '';
				}

				contents += '<tr><td class="ResultLeft"><a href="product.php?pid=' + this.items[i][1] + '">' + productTitle + '<br /><span class="ResultLeftSub">' + productSku + '</span></a></td><td class="ResultRight"><a href="product.php?pid=' + this.items[i][1] + '">' + this.items[i][1] + '</a></td></tr>';
			}

			contents += '</table>';
		}

		if((this.items.length > 0) || (this.itemsReplacements.length > 0) || (this.itemsOverrides.length > 0)) {
			anchor = document.createElement('a');
			anchor.appendChild(document.createTextNode('close'));
			anchor.href = '#';
			anchor.onclick = function() {
				self.closeWindow();
				return false;
			}

			div = document.createElement('div');
			div.className = 'CloseWindow';
			div.appendChild(anchor);

			this.results.element.innerHTML = contents;
			this.results.element.appendChild(div);
			this.results.element.style.display = '';
		}
	},

	hideItems: function() {
		this.results.element.style.display = 'none';
	},

	showItems: function() {
		if(this.items.length > 0) {
			this.results.element.style.display = '';
			this.results.element.style.top = (this.element.getPosition().y + this.element.offsetHeight) + 'px';
			this.results.element.style.left = this.element.getPosition().x + 'px';
		}
	},

	clearItems: function() {
		this.results.element.innerHTML = '';
		this.results.element.style.display = 'none';

		this.items = new Array();
		this.itemsReplacements = new Array();
		this.itemsOverrides = new Array();
	},

	processSearch: function(e) {
		if(this.element.value.length > 0) {
			this.requestData.addItem('criteria', this.element.value);

			this.request.post(this._requestURL, this.requestData.serialise());
		} else {
			this.clearItems();
		}
	},

	onHttpRequestResponse: function(data) {
		var parts = data.response.split('{br}{br}{br}');
		var items = null;
		
		this.clearItems();
		
		if(parts[0]) {
			items = parts[0].split('{br}{br}');
			
			for(var i=0; i<items.length; i++) {
				if(items[i].length > 0) {
					this.addItem(items[i].split('{br}'));
				}
			}
		}
		
		if(parts[1]) {
			replacement = parts[1].split('{br}{br}');
			
			for(var i=0; i<replacement.length; i++) {
				if(replacement[i].length > 0) {
					this.addItemReplacement(replacement[i]);
				}
			}
		}

		if(parts[2]) {
			override = parts[2].split('{br}{br}');

			for(var i=0; i<override.length; i++) {
				if(override[i].length > 0) {
					this.addItemOverride(override[i]);
				}
			}
		}
			
		this.drawItems();
	},

	onHttpRequestError: function(data) {
		evance.debug(data.status);
	},

	onKeyUp: function(e) {
		this.processSearch();
	},

	onMouseOut: function(e) {
		var self = this;

		if(this._hideTimeout) {
			clearTimeout(this._hideTimeout);
		}

		this._hideTimeout = setTimeout(function() {
			self.hideItems();
		}, 500);
	},

	onMouseOver: function(e) {
		if(this._hideTimeout) {
			clearTimeout(this._hideTimeout);
		}

		this.showItems();
	}
});