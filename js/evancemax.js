if(evance._debugger){
	if(!$defined(evance.core)){evance.core = {}};

	evance.core.Debugger = new (function(){
		this._window = null;
		this._width = 250;
		this._path = evance.path + 'evance/core/Debugger.html';
		this._stack = new Array();
		this._isWindowLoaded = false;
		this._id = 1;
		this._recursionsPermitted = (window.ie) ? 2 : 4;
		this._className = 'Debugger';
	
		this.add = function(message, title){
			if(this._window == null || this._window.closed){
				this.openWindow();
			}
			if(this._isWindowLoaded){
				this.addToWindow(message, title);
			}
			this.addToStack(message, title);
		};
	
		this.getStack = function(){
			this._isWindowLoaded = true;
			for(var i=0; i < this._stack.length; i++){
				this.addToWindow(this._stack[i].message, this._stack[i].title);
			}
		};
	
		this.addToStack = function(message, title){
			this._stack[this._stack.length] = {message: message, title: title};
		};
	
		this.addToWindow = function(message, title){
			message = this.getMessage(message, title);
			var div = document.createElement('div');
			div.className = 'message';
			div.innerHTML = message;
			this._window.addToStack(div);
			//this._window.focus();
			window.focus();
		};
	
		this.getMessage = function(message, title){
			var recursions = 0;
	
			switch($type(message)){
				case 'element':
					var temp = 'Element &lt;'+message.nodeName+'&gt;';
					if(title) temp = title + ': ' + temp;
					message = this.buildObject(message, temp, recursions);
					break;
				case 'object':
					var temp = 'Object';
					if(title) temp = title + ': ' + temp;
					message = this.buildObject(message, temp, recursions);
					break;
				case 'array':
					var temp = 'Array('+message.length+')';
					if(title) temp = title + ': ' + temp;
					message = this.buildObject(message, temp, recursions);
					break;
				case 'class':
					var temp = 'Class';
					if(title) temp = title + ': ' + temp;
					message = this.buildObject(message, temp, recursions);
					break;
				case 'collection':
					var temp = 'Collection';
					if(title) temp = title + ': ' + temp;
					message = this.buildObject(message, temp, recursions);
					break;
				default:
					if(title) message = title + ': ' + message;
					message = this.buildString(message);
					break;
			}
			return message;
		};
	
		this.buildObject = function(element, title, recursions){
			++this._id;
			var str = '';
	
			str = '\n<table cellspacing="0">\n';
			str += '\t<tr onmouseover="this.className=\'over\';" onmouseout="this.className=\'\';" onclick="display('+this._id+')">\n';
			str += '\t\t<td class="plus" rowspan="2" id="'+this._id+'_plus" nowrap="nowrap">[+]</td>\n';
			str += '\t\t<td>'+title+'</td>\n';
			str += '\t</tr>\n\t<tr id="'+this._id+'_children" style="display:none">\n\t\t<td class="child">';
	
			if(recursions <= this._recursionsPermitted){
				if($type(element) == 'class'){
					str += this.buildObject(element.prototype, 'prototype', recursions);
				}
				str += this.buildChild(element, ++recursions);
			} else {
				str += this.buildString('NOTE: evance.core.Debugger Maximum Recursions reached ('+recursions+'). Recursions are limited for efficiency.');
			}
	
			str += '\t\t</td>\n\t</tr>\n</table>\n';
	
			return str;
		};
	
		this.buildChild = function(element, recursions){
			var str = '';
			var functions = {};
			var hasFunctions = false;
			var hasExceptions = false;
	
			for(var i in element){
				if($type(element) != 'class' || ($type(element) == 'class' && element[i] != element.prototype)){
					var type = false;
					if(window.ie){
						/*
						 * We need this for ie as it somehow loses members.
						 * And, exception handling will slow other browsers
						 * up when they really don't need it.
						 */
						try{
							type = $type(element[i]);
						} catch(e){
							type = 'exception';
							hasExceptions = true;
						}
					} else {
						type = $type(element[i]);
					}
	
	
					if(type == 'exception'){
						str += this.buildString(i + ': Exception Encountered!');
					} else if(type == 'function'){
						hasFunctions = true;
						functions[i] = 'function()';
					} else if (type == 'string' && (i == 'innerHTML' || i == 'outerHTML')){
							str += this.buildString(i + ': ' + type + '<textarea nowrap="nowrap" style="width:95%; height:300px;" readonly="readonly">'+element[i]+'</textarea>');
					} else if(type == 'element'){
							if(i == 'offsetParent' || i == 'parentNode' || i == 'parentElement' || i == 'ownerElement' || i == 'previousSibling' || i == 'nextSibling'){
								var temp = {};
								temp.id = element[i].id;
								temp.nodeName = element[i].nodeName;
								temp.nodeType = element[i].nodeType;
								temp.NOTE = 'Properties limited for efficiency!';
								str += this.buildObject(temp, i + ': Element &lt;' + element[i].nodeName + '&gt;', recursions);
							} else {
								try{
									str += this.buildObject(element[i], i + ': Element &lt;' + element[i].nodeName + '&gt;', recursions);
								} catch (e){
									str += this.buildString(i + ': Exception Encountered!');
								}
							}
					} else if (type == 'object'){
						if(i == 'document' || i == 'ownerDocument' || i == 'constructor'){
							str += this.buildString(i + ': Object not expanded for efficiency!');
						} else {
							try{
								str += this.buildObject(element[i], i + ': Object', recursions);
							} catch (e){
								str += this.buildString(i + ': Exception Encountered!');
							}
						}
					} else if (type == 'collection'){
						if(i == 'all'){
							str += this.buildString(i + ': Collection not expanded for efficiency!');
						} else if(i == 'children'){
							str += this.buildString(i + ': See childNodes!');
						} else {
							try{
								str += this.buildObject(element[i], i + ': Collection', recursions);
							} catch (e){
								str += this.buildString(i + ': Exception Encountered!');
							}
						}
					} else if (type == 'array'){
							str += this.buildObject(element[i], i + ': Array('+element[i].length+')', recursions);
					} else if (type == 'textnode'){
							str += this.buildString(i + ': Text Node');
					} else if (type == 'whitespace'){
							str += this.buildString(i + ': Whitespace');
					} else if (type == 'class'){
						try{
							str += this.buildObject(element[i], i + ': Class', recursions);
						} catch (e) {
							str += this.buildString(i + ': Exception Encountered!');
						}
					} else if(type) {
						str += this.buildString(i + ': ' + element[i]);
					}
				}
			}
			if(hasExceptions){
				if(element.target){
					str += this.buildString('One or More Exceptions Encountered! Using Internet Explorer to debug an Event object causes an Exception - sorry. ' +
							'Try debugging a specific Member. e.g. evance.debug(e.type); or evance.debug(e.target); will work. ' +
							'See documentation for more.');
				}
			}
	
			if(hasFunctions) str += this.buildObject(functions, 'Functions/Methods:', recursions);
	
			return str;
		};
	
		this.clearStack = function(){
			this._stack = new Array();
		}
	
		this.buildString = function(message){
			var str = '\n<table cellspacing="0">\n';
			str += '\t<tr onmouseover="this.className=\'over\';" onmouseout="this.className=\'\';">\n';
			str += '\t\t<td class="plus">&raquo;</td>\n';
			str += '\t\t<td>'+message+'</td>\n';
			str += '\t</tr>\n</table>\n';
			return str;
		};
	/*
	* 	Opens a new window
	*/
		this.openWindow = function(){
			var availWidth = screen.availWidth;
			var currentWidth = window.getWidth();
			var newWidth = currentWidth;
			var currentHeight = screen.availHeight;
	
			var posX = (window.screenX)?window.screenX:window.screenLeft;
	
			if(currentWidth+this._width > availWidth){
				newWidth = availWidth - this._width;
			}
	
			posX = posX+newWidth;
	
			this._window = window.open(this._path, 'Debugger', 'scrollbars=yes, location=no, resizable=yes, top=0, left='+posX+', height='+currentHeight+', width='+this._width);
	
			if(newWidth < currentWidth){
				var difference = currentWidth - newWidth;
				window.resizeBy(-difference, 0);
			}
		};
	});
}

/*
	Script: Interface.js

	Copyright:
		copyright (c) 2007 Deveus, <http://www.deveus.com>

	Class: evance.core.Interface

*/

if(!$defined(evance.core)){evance.core = {}};

evance.core.Interface = new (function(){
/*
 * Public Members
 */
	this.blankGifImage = evance.path + 'evance/core/blank.gif';
	this.width = 0;
	this.height = 0;
	this.depth = 1;	// used as a central place to track z-index.
	this.focusOnLoad = null; // used to set focus to an element when the page loads
	this._className = 'Interface';
/*
 * Private Members
 */
 	this._eventListeners = new Array();
	this._listeners = new Array();
	this._interval = null;
	this._eventId = 1; // used to track event listeners efficiently
	this._iePngFixPath = evance.path + 'evance/core/iePngFix.htc'; // a fix for PNGs within ie6
	this._iePngFix = new Array('img', 'div');
	this._queueRemoveListeners = new Array();


	this.load = function(e){
		evance.core.Interface.onLoad(e);
	}

	this.onLoad = function(e){
		// get the dimensions
		this.getDimensions();
		// fix PNG images in ie5.5 and ie6
		this._fixPng();
		// execute load() method in each listener
		for(var i=0; i < this._listeners.length; i++){
			if(this._listeners[i].load){
				this._listeners[i].load();
			}
		}
		// set the Focus once the page has loaded
		this._setFocus();
		evance.pageLoaded = true;

		for(var i=0; i < this._queueRemoveListeners.length; i++){
			this.removeListener(this._queueRemoveListeners[i]);
		}
		this._queueRemoveListeners = new Array();
	}

	this._setFocus = function(){
		if(this.focusOnLoad != null){
			var element = $(this.focusOnLoad);
			if(element) element.focus();
		}
	}

	this.addListener = function(listener){
		this._listeners.push(listener);
	}

	this.removeListener = function(listener){
		if(evance.pageLoaded){
			this._listeners.remove(listener);
		} else {
			this._queueRemoveListeners.push(listener);
		}
	}

	this.addEventListener = function(element, type, listenerObject){
		// create the event listener
		if($type(element) == 'string') element = $(element);
		if(type != 'load'){
			var id = ++this._eventId;
			var onEvent = 'on'+type;
			if(element.addEventListener){
				element.addEventListener(type, function(e){evance.core.Interface._dispatchEvent(e, id)}, false);
			} else if(element.attachEvent){
				element.attachEvent(onEvent, function(e){evance.core.Interface._dispatchEvent(e, id)});
			} else {
				element[onEvent] = function(e){evance.core.Interface._dispatchEvent(e, id)};
			}
		}
		var num = this._eventListeners.length;
		this._eventListeners[num] = new evance.event.EventListener(id, type, element, listenerObject, this);
	}

	this.removeEventListener = function(type, listenerObject){
		// go through each listener
		for(var i=0; i < this._eventListeners.length; i++){
			if(this._eventListeners[i].type == type && this._eventListeners[i].listener == listenerObject){
				this._eventListeners.splice(i,1);
			}
		}
	}

	this._dispatchEvent = function(e, id){
		e = evance.event.fix(e);
		for(var i=0; i < evance.core.Interface._eventListeners.length; i++){
			var listener = evance.core.Interface._eventListeners[i];
			if(listener.id == id){
				evance.core.Interface._eventListeners[i].listener[e.type](e);
			}
		}
	}

	this.resize = function(){
		clearTimeout(this._interval);
		this._interval = setTimeout(function(){evance.core.Interface.onResize();}, 500);
	}

	this.onResize = function(){
		this.getDimensions();
		for(var i=0; i < this._listeners.length; i++){
			if(this._listeners[i].onResize){
				this._listeners[i].onResize();
			}
		}
	}

	this.getDimensions = function(){
		this.width = document.body.clientWidth;
		this.height = document.body.clientHeight;
		return {width:this.width, height:this.height};
	}

	this._fixPng = function(){
		// we only need this for IE so only worry about addRule
		// Make sure we only work on IE 5.5 or 6
		if(/MSIE (5\.5|6\.)/.test(navigator.userAgent)){
			var css = document.styleSheets[document.styleSheets.length-1];
			if(css && css.addRule){
				var b = 'behavior: url("' + this._iePngFixPath + '")';
				for(var i=0; i < this._iePngFix.length; i++){
					css.addRule(this._iePngFix[i], b);
				}
			}
		}
	}
	
	// todo: this needs to be done through element
	// find anything using this function and replace
	// then remove from interface
	this.disableSelect = function(target){
		if(typeof target == 'string'){
			target = document.getElementById(target);
		}
		target.onselectstart = function() {
			return false;
		};
		target.unselectable = "on";
		target.style.MozUserSelect = "none";
	}
	
	// todo: this needs to be done through element
	// find anything using this function and replace
	// then remove from interface
	this.getElementDimensions = function(Elem){
		if(typeof Elem == 'string'){
			if(document.getElementById) {
				var elem = document.getElementById(Elem);
			} else if (document.all){
				var elem = document.all[Elem];
			}
		} else if (typeof Elem == 'object') {
			var elem = Elem;
		} else {
			return false;
		}
		if ((navigator.userAgent.indexOf("Opera 5")!=-1)
			||(navigator.userAgent.indexOf("Opera/5")!=-1)) {
			h = elem.style.pixelHeight;
			w = elem.style.pixelWidth;
		} else {
			w = elem.offsetWidth;
			h = elem.offsetHeight;
		}
		elem = null;
		return {_width:w, _height:h};
	}

	// todo: this needs to be done through element
	// find anything using this function and replace
	// then remove from interface
	this.getElementPosition = function (obj){
		if(typeof obj == 'string') obj = document.getElementById(obj);
		var left = 0;
		var top  = 0;
		var e = obj;
		while (e.offsetParent){
			left += e.offsetLeft;
			top  += e.offsetTop;
			e     = e.offsetParent;
		}
		left += e.offsetLeft;
		top  += e.offsetTop;
		return {_x:left, _y:top};
	}
	
	this.getNextZIndex = function(){
		++this.depth;
		return this.depth;
	}
});

var Interface = evance.core.Interface;
window.onresize = evance.core.Interface.resize;
window.onload = evance.core.Interface.load;

if(!$defined(evance.ui)){evance.ui = {}};

evance.ui.EventDispatcher = new (function(){
	this.setup = function(obj){
		obj.addListener = function(listener){
			if(!this._listeners) {
				this._listeners = new Array();
			}

			this._listeners.push(listener);
		},

		obj.removeListener = function(listener){
			if(!$defined(this._isDispatching)) {
				this._isDispatching = false;
			}

			if($defined(this._listeners)){
				if(this._isDispatching){
					if(!$defined(this._removeListenerQueue)) {
						this._removeListenerQueue = new Array();
					}

					this._removeListenerQueue.push(listener);
				} else {
					this._listeners.remove(listener);
				}
			}
		},

		obj.dispatchEvent = function(type, eventObject){
			if(!$defined(this._isDispatching)){
				this._isDispatching = true;
			}

			if($defined(this._listeners)){
				for(var i=0; i<this._listeners.length; i++){
					if(this._listeners[i][type]) {
						this._listeners[i][type](eventObject);
					}
				}
			}

			this._isDispatching = false;

			if($defined(this._removeListenerQueue)){
				for(var i=0; i<this._removeListenerQueue.length; i++){
					this.removeListener(this._removeListenerQueue[i]);
				}

				delete this._removeListenerQueue;
			}
		}
	}
});

evance.Key = new (function(){
	this._keysDown = new Array();
	this._className = 'Key';
	this._classOwner = 'evance.Key';
	this.BACKSPACE = 8;
	this.TAB = 9;
	this.ENTER = 13;
	this.SHIFT = 16;
	this.CONTROL = 17;
	this.ALT = 18;
	this.SPACE = 32;
	this.LEFT = 37;
	this.UP = 38;
	this.RIGHT = 39;
	this.DOWN = 40;

	Interface.addListener(this);

	this.load = function(){
		Interface.removeListener(this);
		evance.ui.EventDispatcher.setup(this);
	}

	this.keyDown = function(e){
		evance.Key.onKeyDown(e);
	};

	this.keyUp = function(e){
		evance.Key.onKeyUp(e);
	};

	this.onKeyDown = function(e){
		e = window.event?event:e;
		var unicode = e.charCode? e.charCode:e.keyCode;
		if(!evance.Key._keysDown.contains(unicode)){
			evance.Key._keysDown.push(unicode);
		}
		evance.Key.dispatchEvent('onKeyDown', e);
	};

	this.onKeyUp = function(e){
		e = window.event?event:e;
		var unicode = e.charCode? e.charCode:e.keyCode;
		evance.Key._keysDown.remove(unicode);
		evance.Key.dispatchEvent('onKeyUp', e);
	};

	this.isDown = function(key){
		if(this._keysDown.contains(key)) {
			return true;
		} else {
			return false;
		}
	};
});

document.onkeydown = evance.Key.keyDown;
document.onkeyup = evance.Key.keyUp;

evance.Mouse = new (function(){
	this._className = 'Mouse';
	this._classOwner = 'evance.Mouse';
	this.x = 0;
	this.y = 0;
	this.lastEvent = null;

	Interface.addListener(this);

	this.load = function(){
		Interface.removeListener(this);
		evance.ui.EventDispatcher.setup(this);
	};

	this.move = function(e){
		evance.Mouse.onMouseMove(e);
	};

	this.up = function(e){
		evance.Mouse.onMouseUp(e);
	};

	this.down = function (e){
		evance.Mouse.onMouseDown(e);
	};

	this.onMouseMove = function(e){
		e = evance.event.fix(e);
		this.lastEvent = e;
		this.getCoords(e);
		if(evance.pageLoaded) this.dispatchEvent('onMouseMove', e);
	};

	this.onMouseUp = function (e){
		e = evance.event.fix(e);
		this.lastEvent = e;
		this.getCoords(e);
		if(evance.pageLoaded) this.dispatchEvent('onMouseUp', e);
	};

	this.onMouseDown = function (e){
		e = evance.event.fix(e);
		this.lastEvent = e;
		this.getCoords(e);
		if(evance.pageLoaded) this.dispatchEvent('onMouseDown', e);
	};

	this.getCoords = function(e){
		if(evance.pageLoaded){
			if(e.pageX || e.pageY){
				this.x = e.pageX;
				this.y = e.pageY;
			} else {
				this.x = e.clientX + document.body.scrollLeft - document.body.clientLeft;
				this.y = e.clientY + document.body.scrollTop  - document.body.clientTop;
			}
		}
	};

	this.isOver = function(el){
		if(this.lastEvent) {
			el = $(el);
			var e = this.lastEvent;
			/*
			var position = el.getPosition();
			if(this.x > position.x && this.x < (position.x+el.offsetWidth) && this.y > position.y && this.y < (position.y+el.offsetHeight)){
				evance.debug(e);
				return true;
			} else {
				return false;
			}*/
			
			/*
				The old way didn't really work
				because of scroll positions
				but this new way might work by 
				tracking the last mouse event.
			*/

			var currentEl = $(e.target);
			var elements = new Array();
			while(currentEl){
				elements.push(currentEl.nodeName);
				if(el == currentEl){
					return true;
				}
				currentEl = $(currentEl.parentNode);
			}
		}
		return false;
	}
});

document.onmousemove = evance.Mouse.move;
document.onmouseup = evance.Mouse.up;
document.onmousedown = evance.Mouse.down;

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

if(!$defined(evance.net)){evance.net = {}};

evance.net.Request = new Class({
	_className: 'Request',
	_classOwner: 'evance.net.Request',
	_req: null,
	_url: null,
	_method: null,
	_timeoutHandler: null,
	_timeoutInterval: 30000,
	_delayHandler: null,
	_delayInterval: 0,
	_format: 'text',
	_mimeType: null,
	_headers: new Array(),
	_async: true,
	_data: null,
	_cache: true,
	_retry: 0,
	_attempts: 2,
	_retries: 0,
	_username: null,
	_password: null,
	id: 0,
	isActive: false,

	init: function() {
		evance.ui.EventDispatcher.setup(this);

		this.id = (new Date().getTime()).toString(16) + '-0';
	},

	_createRequest: function() {
		if(!this._req) {
			try {
				// Firefox, Safari, IE7.
				this._req = new XMLHttpRequest();
				return true;
			} catch(e) {}

			try {
				// later versions of IE.
				this._req = new ActiveXObject('MSXML2.XMLHTTP');
				return true;
			} catch(e) {}

			try {
				// earlier versions of IE.
				this._req = new ActiveXObject('Microsoft.XMLHTTP');
				return true;
			} catch(e) {}

			evance.debug(this._className + ' could not instantiate an XML HTTP Request object for AJAX requests due to incompatabilities with this browser.', 'Error');

			return false;
		}

		return true;
	},

	_request: function() {
		var self = this;
		var header = null;

		this.abort();

		if(!this._createRequest()) {
			return false;
		}

		this.isActive = true;

		if(this._username && this._password) {
			this._req.open(this._method, this._url, this._async, this._username, this._password);
		} else {
			this._req.open(this._method, this._url, this._async);
		}

		// abort and call error handler when request times out.
		if(this._timeoutInterval > 0) {
			this._timeoutHandler = setTimeout(function() {
				self.abort();

				// HTTP specification (RFC 2616) timeout response code and status text.
				if((self._retry == evance.net.Request.RETRY_ON_ALL) || (self._retry == evance.net.Request.RETRY_ON_TIMEOUT)) {
					self._requestError(null, '408', 'Request Time-out');
				} else {
					self.isActive = false;
					self.dispatchEvent('onHttpRequestError', {key: self.id, response: null, statusCode: '408', status: 'Request Time-out'});
				}
			}, this._timeoutInterval);
		}

		// override mime type for Firefox, Safari and IE7.
		if((this._mimeType) && (navigator.userAgent.indexOf('MSIE') == -1)) {
			try {
				this._req.overrideMimeType(this._mimeType);
			} catch(e) {}
		}

		// add custom headers to request.
		if(this._headers.length) {
			for(var i = 0; i < this._headers.length; i++) {
				header = this._headers[i].split(': ');
				this._req.setRequestHeader(header[0], header[1]);
			}

			this._headers = new Array();
		}

		// set appropriate url encoding for post requests.
		if(this._method == 'POST') {
			this._req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		}

		if(this._async) {
			this._req.onreadystatechange = function() {
				if(self._req.readyState == 4) {
					self._requestResponse();
				}
			}
			this._req.send(this._data);
		}
		else {
			this._req.send(this._data);

			// delay allows for timeout function to execute for synchronous requests.
			setTimeout(function() {
				if(self._req) {
					self._requestResponse();
				}
			}, 10);
		}

		return true;
	},

	_requestResponse: function() {
		var resp = null;
		var status = null;
		var statusText = null;

		if(this._timeoutHandler) {
			clearTimeout(this._timeoutHandler);
		}

		// handles exception whilst accessing request object, primarily due to refreshing
		try {
			switch(this._format) {
				case 'text':
					resp = this._req.responseText;
					break;
				case 'xml':
					resp = this._req.responseXML;
					break;
				case 'object':
					resp = this._req;
					break;
			}

			status = this._req.status;
			statusText = this._req.statusText;
		} catch(e) {}

		if(status && statusText) {

			// HTTP specification (RFC 2616) response codes.
			if((status > 199) && (status < 300)) {
				this.isActive = false;
				this.dispatchEvent('onHttpRequestResponse', {key: this.id, response: resp, statusCode: status, status: statusText});
			} else {
				if((this._retry == evance.net.Request.RETRY_ON_ALL) || (this._retry == evance.net.Request.RETRY_ON_ERROR)) {
					this._requestError(resp, status, statusText);
				} else {
					this.isActive = false;
					this.dispatchEvent('onHttpRequestError', {key: this.id, response: resp, statusCode: status, status: statusText});
				}
			}
		}
	},

	_requestError: function(resp, code, status) {
		if(this._retries < this._attempts) {
			this._retries++;
			this._request();
		} else {
			this.isActive = false;
			this.dispatchEvent('onHttpRequestError', {key: this.id, response: resp, statusCode: code, status: status});
		}
	},

	_requestDelay: function() {
		var self = this;

		if(this._delayHandler) {
			clearTimeout(this._delayHandler);
		}

		this.isActive = true;

		if(this._delayInterval > 0) {
			this._delayHandler = setTimeout(function() {
				return self._request();
			}, this._delayInterval);
		} else {
			return this._request();
		}
	},

	get: function(url, data) {
		if(data) {
			if(url.indexOf('?') != -1) {
				if((url.indexOf('?') + 1) < url.length) {
					data = '&' + data;
				}
			} else {
				data = '?' + data;
			}

			url += data;
		}

		if(!this._cache) {
			if(url.indexOf('?') != -1) {
				if((url.indexOf('?') + 1) < url.length) {
					url += '&';
				}
			} else {
				url += '?';
			}

			url += 'nocache=' + new Date().getTime();
		}

		this._url = url;
		this._method = 'GET';
		this._retries = 0;

		return this._requestDelay();
	},

	post: function(url, data) {
		this._url = url;
		this._data = data;
		this._method = 'POST';
		this._retries = 0;

		return this._requestDelay();
	},

	abort: function() {
		this.isActive = false;

		if(this._timeoutHandler) {
			clearTimeout(this._timeoutHandler);
			this.timeoutHander = null;
		}

		if(this._req) {
			this._req.onreadystatechange = function() {};
			this._req.abort();
			this._req = null;
		}
	},

	setTimeout: function(timeout) {
		if(timeout > 0) {
			this._timeoutInterval = timeout;
		} else {
			this._timeoutInterval = 0;
		}
	},

	setCaching: function(cache) {
		this._cache = cache;
	},

	setAsynchronous: function(async) {
		this._async = async;
	},

	setMimeType: function(mimeType) {
		this._mimeType = mimeType;
	},

	setResponseFormat: function(format) {
		this._format = format;
	},

	setRequestHeader: function(headerName, headerValue) {
		this._headers.push(headerName + ': ' + headerValue);
	},

	setUsername: function(username) {
		this._username = username;
	},

	setPassword: function(password) {
		this._password = password;
	},

	setRetryAttempts: function(attempts) {
		this._attempts = attempts;
	},

	setRetryStatus: function(status) {
		this._retry = status;
	},

	setDelay: function(delay) {
		if(delay > 0) {
			this._delayInterval = delay;
		} else {
			this._delayInterval = 0;
		}
	}
});

evance.net.Request.RETRY_ON_NONE = 0;
evance.net.Request.RETRY_ON_TIMEOUT = 1;
evance.net.Request.RETRY_ON_ERROR = 2;
evance.net.Request.RETRY_ON_ALL = 3;

if(!$defined(evance.net)){evance.net = {}};

evance.net.RequestData = new Class({
	_className: 'RequestData',
	_classOwner: 'evance.net.RequestData',
	_data: new Array(),
	_autoClear: false,

	addItem: function(variable, value) {
		var pair = null;

		for(var i = 0; i < this._data.length; i++) {
			pair = this._data[i].split('=');

			if(pair[0] == variable) {
				this._data[i] = variable + '=' + encodeURI(value);
				return true;
			}
		}

		this._data.push(variable + '=' + value);
		return false;
	},

	getItem: function(variable) {
		var pair = null;

		for(var i = 0; i < this._data.length; i++) {
			pair = this._data[i].split('=');

			if(pair[0] == variable) {
				return pair[1];
			}
		}

		return false;
	},

	removeItem: function(variable) {
		var pair = null;

		for(var i = 0; i < this._data.length; i++) {
			pair = this._data[i].split('=');

			if(pair[0] == variable) {
				this._data.splice(i, 1)
				return true;
			}
		}

		return false;
	},

	removeAll: function() {
		this._data = new Array();
	},

	setAutoClear: function(autoClear) {
		this.autoClear = autoClear;
	},

	serialise: function() {
		var dataString = '';

		for(var i = 0; i < this._data.length; i++) {
			dataString += '&' + this._data[i];
		}

		if(this.autoClear) {
			this._data = new Array();
		}

		return dataString.substr(1, dataString.length);
	},

	loadFromForm: function(formReference) {
		var form = $(formReference);
		var formElement = null;
		var formGroups = new Array();
		var groupArray = null;
		var groupFound = false;
		var listOption = null;
		var listString = '';

		if($defined(form)) {
			for(var i = 0; i < form.elements.length; i++) {
				formElement = form.elements[i];

				switch(formElement.type) {
					case 'text':
					case 'hidden':
					case 'password':
					case 'textarea':
					case 'select-one':
						this.addItem(formElement.name, encodeURI(formElement.value));
		        		break;
					case 'select-multiple':
						for(var j = 0; j < formElement.options.length; j++) {
						   	var listOption = formElement.options[j];

							if(listOption.selected) {
								listString += encodeURI(listOption.value) + ',';
							}
						}

						this.addItem(formElement.name, listString.substr(0, listString.length - 1));
						break;
					case 'checkbox':
						if(formElement.checked) {
							groupFound = false;

							for(j = 0; j < formGroups.length; j++) {
								if(formGroups[j][0] == formElement.name) {
									formGroups[j][1] += encodeURI(formElement.value) + ',';

									groupFound = true;
									break;
								}
							}

							if(!groupFound) {
								groupArray = new Array(2);
								groupArray[0] = formElement.name;
								groupArray[1] = encodeURI(formElement.value) + ',';

								formGroups[formGroups.length] = groupArray;
							}
		 				}
						break;
					case 'radio':
				        if(formElement.checked) {
							this.addItem(formElement.name, encodeURI(formElement.value));
				        }
				        break;
				}
			}

			for(var i = 0; i < formGroups.length; i++) {
				this.addItem(formGroups[i][0], formGroups[i][1].substr(0, formGroups[i][1].length - 1));
			}
		}
	}
});

if(!$defined(evance.net)){evance.net = {}};

evance.net.RequestStack = new Class({
	_className: 'RequestStack',
	_classOwner: 'evance.net.RequestStack',
	_stack: new Array(),
	_requests: new Array(),
	_queuing: false,
	_nextKey: 0,
	_intervalHandler: null,
	id: 0,
	isActive: false,
	isQueued: false,
	autoStart: true,

	init: function() {
		evance.ui.EventDispatcher.setup(this);

		this.id = (new Date().getTime()).toString(16);
	},

	restart: function() {
		this.stop();
		this.start();
	},

	start: function() {
		if(!this.isActive && (this._stack.length > 0)) {
			this.isActive = true;

			if(this.isQueued) {
				var self = this;

				this._queuing = true;
				this._intervalHandler = setInterval(function() {
					var processQueue = true;

					for(var i=0; i<self._requests.length; i++) {
						if(self._requests[i].isActive) {
							processQueue = false;
							break;
						}
					}

					// ensures only one request is active at a time.
					if(processQueue) {
						if(self._intervalHandler) {
							clearInterval(self._intervalHandler);
						}

						self._processStack();
					}
				}, 500);
			} else {
				this._queuing = false;
				this._processStack();
			}
		}

		this.dispatchEvent('onHttpRequestStackStart');
	},

	stop: function() {
		this.isActive = false;

		this.dispatchEvent('onHttpRequestStackStop');
	},

	clearRequests: function() {
		this.stop();
		this._stack = new Array();
	},

	_processStack: function() {
		var index = -1;
		var object = null;
		var request = null;
		var key = 0;

		if(this.isActive) {
			if($defined(this._stack[0]) && $defined(this._stack[0].request)) {
				key = this._stack[0].key;

				request = this._stack[0].request;
				request.data = $defined(request.data) ? request.data : '';

				this._stack.splice(0, 1);

				if($defined(request.url) && $defined(request.method)) {
					for(var i=0; i<this._requests.length; i++) {
						if(!this._requests[i].isActive) {
							index = i;
							break;
						}
					}

					if(index == -1) {
						this._requests.push(new evance.net.Request());
						this._requests[this._requests.length - 1].addListener(this);

						index = this._requests.length - 1;
					}

					this._requests[index].id = key;

					if($defined(request.format)) {
						this._requests[index].setResponseFormat(request.format);
					}

					if($defined(request.cache)) {
						this._requests[index].setCaching(request.cache);
					}

					if($defined(request.username)) {
						this._requests[index].setUsername(request.username);
					}

					if($defined(request.password)) {
						this._requests[index].setPassword(request.password);
					}

					if($defined(request.delay)) {
						this._requests[index].setDelay(request.delay);
					}

					if($defined(request.mimeType)) {
						this._requests[index].setMimeType(request.mimeType);
					}

					if($defined(request.timeout)) {
						this._requests[index].setTimeout(request.timeout);
					}

					if($defined(request.retryAttempts)) {
						this._requests[index].setRetryAttempts(request.retryAttempts);
					}

					if($defined(request.retryStatus)) {
						this._requests[index].setRetryStatus(request.retryStatus);
					}

					if($defined(request.async)) {
						this._requests[index].setAsynchronous(request.async);
					}

					if($defined(request.headers)) {
						for(var i=0; i<request.headers.length; i++) {
							if(request.headers[i].length == 2) {
								this._requests[index].setRequestHeader(request.headers[i][0], request.headers[i][1]);
							}
						}
					}

					if(request.method.toUpperCase() == 'POST') {
						this._requests[index].post(request.url, request.data);
					} else if(request.method.toUpperCase() == 'GET') {
						this._requests[index].get(request.url, request.data);
					}

					if(!this._queuing) {
						this._processStack();
					}
				} else {
					evance.debug({Message: this._className + ' with ID &quot;' + this.id + '&quot; dropped the following request from the stack due to lack of a target URL or request method.', Request: request}, 'Warning');

					this._processStack();
				}
			} else {
				this.stop();
			}
		}
	},

	onHttpRequestResponse: function(e) {
		e.stack = this.id;

 		this.dispatchEvent('onHttpRequestStackResponse', e);

		if(this._queuing) {
			this._processStack();
		}
	},

	onHttpRequestError: function(e) {
		e.stack = this.id;

		this.dispatchEvent('onHttpRequestStackError', e);

		if(this._queuing) {
			this._processStack();
		}
	},

	addHttpRequest: function(request) {
		var key = this.id + '-' + this._nextKey;

		this._stack[this._stack.length] = {key: key, request: request};
		this._nextKey++;

		if(!this.isActive && this.autoStart) {
			var self = this;

			// allow return of key variable before processing.
			setTimeout(function() {
				self.start();
			}, 10);
		}
		
		return key;
	}
});