using('evance.ui.EventDispatcher');

if(!$defined(evance.core)){evance.core = {}};

evance.core.UIObject = new Class({
	_id: 0,
	_className: 'UIObject',
	_classOwner: 'evance.core.UIObject',
	_isMouseOver: false,
	_minHeight: null,
	_maxHeight: null,
	_minWidth: null,
	_maxWidth: null,
	_width: null,
	_height: null,
	_events: new Array('click', 'mousemove', 'mouseup', 'mousedown', 'mouseover', 'mouseout', 'keyup', 'keydown'),
	id: null,
	element: null,
	parentId: null,
	parentElement: null,
	isLoaded: false,
	isDrawn: false,
	isPageLoaded: false,
	isLocked: false,
	enabled: true,

	init: function(){
		this._classOwner = eval(this._classOwner);
		this._id = evance._autoId++;
		this.id = (arguments[0]) ? arguments[0] : this._className + '_' + this._id;

		evance.ui.EventDispatcher.setup(this);

		this.isLoaded = false;

		if(!evance.pageLoaded) {
			evance.core.Interface.addListener(this);
		}
	},

	load: function(e){
		this.isPageLoaded = true;

		if($(this.id) && !this.isLoaded){
			this.element = $(this.id);
			this._loadEvents();
			this.isLoaded = true;

			if(this.onLoad) {
				this.onLoad(e);
			}

			this.dispatchEvent('onLoad', e);
		}
	},

	unload: function(){
		this._unloadEvents();

		if(this.element && this.parentElement) {
			this.parentElement.removeChild(this.element);
		}

		this.isDrawn = false;
		this.isLoaded = false;

		if(this.onUnload) this.onUnload();
		this.dispatchEvent('onUnload');
	},

	lock: function() {
		this.isLocked = true;
		this.dispatchEvent('onLock');
	},

	unlock: function() {
		this.isLocked = false;
		this.dispatchEvent('onUnlock');
	},

	_addEvents: function(){
		var args = arguments;
		if(args.length > 0){
			for(var i=0; i < args.length; i++){
				this._events.push(args[i]);
			}
			return true;
		}
		return false;
	},

	_loadEvents: function(){
		for(var i=0; i<this._events.length; i++){
			evance.core.Interface.addEventListener(this.element, this._events[i], this);
		}
	},

	_unloadEvents: function(){
		for(var i=0; i<this._events.length; i++){
			evance.core.Interface.removeEventListener(this._events[i], this);
		}
	},

	click: function(e){
		if(this.onClick) {
			this.onClick(e);
		}

		this.dispatchEvent('onClick', e);
	},

	mousemove: function(e){
		if(this.onMouseMove) {
			this.onMouseMove(e);
		}

		this.dispatchEvent('onMouseMove', e);
	},

	mouseup: function(e){
		if(this.onMouseUp) {
			this.onMouseUp(e);
		}

		this.dispatchEvent('onMouseUp', e);
	},

	mousedown: function(e){
		if(this.onMouseDown) {
			this.onMouseDown(e);
		}

		this.dispatchEvent('onMouseDown', e);
	},

	mouseover: function(e){
		this._isMouseOver = true;

		if(this.onMouseOver) {
			this.onMouseOver(e);
		}

		this.dispatchEvent('onMouseOver', e);
	},

	mouseout: function(e){
		this._isMouseOver = false;

		if(this.onMouseOut) {
			this.onMouseOut(e);
		}

		this.dispatchEvent('onMouseOut', e);
	},

	keydown: function(e){
		if(this.onKeyDown) {
			this.onKeyDown(e);
		}

		this.dispatchEvent('onKeyDown', e);
	},

	keyup: function(e){
		if(this.onKeyUp) {
			this.onKeyUp(e);
		}

		this.dispatchEvent('onKeyDown', e);
	},

	redraw: function(){
		this.draw();

		if(this.onDraw) {
			this.onDraw(e);
		}

		this.dispatchEvent('onDraw', {type: 'draw'});
	},

	draw: function(){
		if(this.isPageLoaded) {
			if(this.isDrawn){
			   this.unload();
			}

			if(this.parentId) {
				var div = document.createElement('div');
				div.id = this.id;

				if(this._height) {
					this._height = this._height.toString();
					if(this._height.search(/^\d+$/) >= 0){
						div.style.height = this._height + 'px';
					} else {
						div.style.height = this._height;
					}
				}

				if(this._width) {
					this._width = this._width.toString();
					if(this._width.search(/^\d+$/) >= 0){
						div.style.width = this._width + 'px';
					} else {
						div.style.width = this._width;
					}
				}

				this.parentElement = $(this.parentId);
				if(this.parentElement){
					if(this._className == 'Tabs'){
						this.parentElement.insertBefore(div,this.parentElement.firstChild);
					} else {
						this.parentElement.appendChild(div);
					}
				}
				this.isDrawn = true;
				this.load();

				return true;
			}
		}

		return false;
	},

	setFocus: function(){
		if(!this.element) this.element = $(this.id);
		evance.core.Interface.addEventListener(this.element, 'keydown', this);
	},

	setBlur: function(){
		evance.core.Interface.removeEventListener('keydown', this);
	},

	size: function(){
		if(!this.element) this.element = $(this.id);
		if(arguments.length == 2){
			if(this.isDrawn){
				// set the size
				var newWidth = arguments[0];
				var newHeight = arguments[1];
				this.width(newWidth);
				this.height(newHeight);
				// dispatch event with new dimensions
				this.dispatchEvent('onSize', {type: 'size', newWidth: newWidth, newHeight: newHeight});
			} else {
				this._width = arguments[0];
				this._height = arguments[1];
			}
			return true;
		} else if(arguments.length != 2 && arguments.length > 0){
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; size was specified with an incorrect argument count. Allowed argument counts are 0 and 2.');
		} else {
			return this.element.getSize();
		}
	},

	moveTo: function(x, y){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			if((this.element.style.position != 'absolute') && (this.element.style.position != 'relative')){
				this.element.style.position = 'relative';
			}
			this.element.style.left = x+'px';
			this.element.style.top = y+'px';
			// TODO need to report the new position
			this.dispatchEvent('onMove', {type: 'move'});
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; position cannot be moved to because it is not yet drawn.');
		}
	},

	moveBy: function (x, y){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			var point = this.element.getPosition();
			this.moveTo(point.x+x, point.y+y);
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; position cannot be moved by because it is not yet drawn.');
		}
	},

	x: function(){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			if(arguments.length == 1 && arguments[0] != undefined){
				this.element.style.right = '';
				this.element.style.left = arguments[0] + 'px';
				this.dispatchEvent('onMove', {type: 'move'});
				return true;
			} else {
				var point = this.element.getPosition();
				return point.x;
			}
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; coordinate x cannot be handled because it is not yet drawn.');
		}
	},

	y: function(){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			if(arguments.length == 1 && arguments[0] != undefined){
				this.element.style.bottom = '';
				this.element.style.top = arguments[0] + 'px';
				// TODO need to report the new position
				this.dispatchEvent('onMove', {type: 'move'});
				return true;
			} else {
				var point = this.element.getPosition();
				return point.y;
			}
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; coordinate y cannot be handled because it is not yet drawn.');
		}
	},

	// gets or sets the left position relative to its parent
	left: function(){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			if(arguments.length == 1){
				// set the left position relative to the parent left edge.
				var el = this.element.style;
				this._fixPositioning();
				el.right = '';
				el.left = arguments[0] + 'px';
				this.dispatchEvent('onMove', {type: 'move'});
			} else {
				var point = this.element.getPosition();
				var parentPoint = $(this.element.offsetParent).getPosition();
				return (point.x - parentPoint.x);
			}
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; relative left offset cannot be handled because it is not yet drawn.');
		}
	},

	// gets or sets the top position relative to its parent
	top: function(){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			if(arguments.length == 1){
				// set the left position relative to the parent left edge.
				var el = this.element.style;
				this._fixPositioning();
				el.bottom = '';
				el.top = arguments[0] + 'px';
				this.dispatchEvent('onMove', {type: 'move'});
			} else {
				var point = this.element.getPosition();
				var parentPoint = $(this.element.offsetParent).getPosition();
				return (point.y - parentPoint.y);
			}
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; relative top offset cannot be handled because it is not yet drawn.');
		}
	},

	// gets or sets the right position relative to the right side of the parent
	right: function(){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			if(arguments.length == 1){
				var el = this.element.style;
				this._fixPositioning();
				el.left = '';
				el.right = arguments[0] + 'px';
				this.dispatchEvent('onMove', {type: 'move'});
			} else {
				var pel = this.element.offsetParent;
				var right = pel.offsetWidth - (this.element.offsetWidth + this.left());
				return right;
			}
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; relative right offset cannot be handled because it is not yet drawn.');
		}
	},

	// gets or sets the distance between the bottom edge of the UIObject and its parent
	bottom: function(){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			if(arguments.length == 1){
				var el = this.element.style;
				this._fixPositioning();
				el.top = '';
				el.bottom = arguments[0] + 'px';
				this.dispatchEvent('onMove', {type: 'move'});
			} else {
				var pel = this.element.offsetParent;
				var bottom = pel.offsetHeight - (this.element.offsetHeight + this.top());
				return bottom;
			}
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; relative bottom offset cannot be handled because it is not yet drawn.');
		}
	},

	width: function(){	// If no unit is given, px is presumed
		if(!this.element) this.element = $(this.id);
		if(arguments.length == 1){
			if(this.isDrawn){
				if((this._maxWidth && (arguments[0] > this._maxWidth)) || (this._minWidth && (arguments[0] < this._minWidth)) || (arguments[0] < 0)) {
					evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; width cannot be set to ' + arguments[0] + ' because it is not within the minimum or maximum bounds. (Max Width: ' + this._maxWidth + ', Min Width:' + this._minWidth + ')');
					return false;
				} else {
					w = arguments[0].toString();

					if(w.search(/^\d+$/) >= 0){
						this.element.style.width = w + 'px';
					} else {
						this.element.style.width = w;
					}

					//this.dispatchEvent('onSize', {type: 'size', newWidth: arguments[0]});
					return true;
				}
			} else {
				this._width = arguments[0];
			}
		} else {
			if(this.isDrawn){
				return this.element.offsetWidth;
			} else {
				evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; width cannot be returned because it is not yet drawn.');
				return false;
			}
		}
	},

	height: function(){	// If no unit is given, px is presumed
		if(!this.element) this.element = $(this.id);
		if(arguments.length == 1){
			if(this.isDrawn){
				if((this._maxHeight && (arguments[0] > this._maxHeight)) || (this._minHeight && (arguments[0] < this._minHeight)) || (arguments[0] < 0)){
					evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; height cannot be set to ' + arguments[0] + ' because it is not within the minimum or maximum bounds.(Max Height: ' + this._maxHeight + ', Min Height:' + this._minHeight + ')');
					return false;
				} else {
					h = arguments[0].toString();

					if(h.search(/^\d+$/) >= 0){
						this.element.style.height = h + 'px';
					} else {
						this.element.style.height = h;
					}
					//this.dispatchEvent('onSize', {type: 'size', newHeight: arguments[0]});
					return true;
				}
			} else {
				this._height = arguments[0];
			}
		} else {
			if(this.isDrawn){
				return this.element.offsetHeight;
			} else {
				evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; height cannot be returned because it is not yet drawn.');
				return false;
			}
		}
	},

	minWidth: function(){
		if(arguments.length == 1){
			this._minWidth = arguments[0];
		} else {
			return this._minWidth;
		}
	},

	minHeight: function(){
		if(arguments.length == 1){
			this._minHeight = arguments[0];
		} else {
			return this._minHeight;
		}
	},

	maxWidth: function(){
		if(arguments.length == 1){
			this._maxWidth = arguments[0];
		} else {
			return this._maxWidth;
		}
	},

	maxHeight: function(){
		if(arguments.length == 1){
			this._maxHeight = arguments[0];
		} else {
			return this._maxHeight;
		}
	},

	_fixPositioning: function(){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			if((this.element.offsetParent.style.position != 'absolute') && (this.element.offsetParent.style.position != 'relative')) {
				this.element.offsetParent.style.position = 'relative';
			}
			if(this.element.style.position != 'absolute') {
				this._fixDimensions();
				this.element.style.position = 'absolute';
			}
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; positioning cannot be fixed because it is not yet drawn.');
		}
	},

	_fixDimensions: function(){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			this.element.style.width = this.element.offsetWidth + 'px';
			this.element.style.height = this.element.offsetHeight + 'px';
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; dimensions cannot be fixed because it is not yet drawn.');
		}
	},

	visible: function(){
		if(this.isDrawn){
			if(!this.element) this.element = $(this.id);
			if(arguments.length == 1){
				this.element.style.visibility = (arguments[0] === true) ? 'visible' : 'hidden';
				this.dispatchEvent('onVisible', {type: 'visible', visible: arguments[0]});
			} else {
				return ((this.element.style.visibility == '') || (this.element.style.visibility == 'visible')) ? true : false;
			}
		} else {
			evance.warn(this._className + ' with ID &quot;' + this.id + '&quot; visible cannot be handled because it is not yet drawn.');
		}
	},

	setEnabled: function(enabled) {
		this.enabled = enabled;
	}
});