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