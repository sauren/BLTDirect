/*
	Script: evance_api.js
		Evance API creates the evance namespace and loads:
		evance.mootools.Core	- the MooTools library
								  <http://www.mootools.net>
		evance.core.Interface	- the Evance base Interface object,
								  which controls Event Listeners and Stage resizes
		Also, creates some handy loading tools.

	Copyright:
		copyright (c) 2007 Deveus, <http://www.deveus.com>
*/
var evance = {
	version: '1.0',
	path: 'js/',
	loaded: new Array(),
	pageLoaded: false,
	lang: 'en',
	_debugger: false,
	_autoId: 1,

	debug: function(object, title){
		if(this._debugger){
			evance.core.Debugger.add(object, title);
		}
	},

	inArray: function(value, array){
		for(var i=0; i < array.length; i++){
			if(array[i] === value) return true;
		}
		return false;
	},
/*
	Function: evance.using(namespaceURI)
		a handy little function which makes life a bit easier to load Javascript files
		based on the namespace of the Object.
		All files must reside within the evance.path and must be in the same file structure
		as the namespace uri of the object. The last part of the uri must be the name of a
		.js file which can be loaded.

	Arguments:
		uri		- the javascript namespace of the object

	Example:
		evance.using(evance.core.Interface);

		or

		using(evance.core.Interface);

		is converted to evance.path/core/Interface.js
*/
	using: function(api){
		api = api.split('.');
		//if(api[0].toLowerCase() == 'evance') api.splice(0,1);
		api = evance.path + api.join('/') + '.js';
		evance.load('script', api);
	},
/*
	Function: evance.isLoaded(src)
		Determines whether an attempt was made to load a file using the evance.load() function.

	Arguments:
		src		- the url of the file to load

	Retunrs:
		true	- an attempt has already been made to load the file
		false	- the file hasn't been loaded yet
*/
	isLoaded: function(src){
		return (evance.inArray(src, evance.loaded));
	},
/*
	Function: evance.load(type, src)
		Loads css or script files into the page.
		Before page load the scripts are written to the page
		After page load the scripts are called dynamically

	Arguments:
		type 	- type of file to load, either 'script' or 'css'
		src		- the url of the file to load

	Example:
		evance.load('script', './Client/core/UIObject.js');
*/
	load: function(type, src){
		var loadDynamically = evance.pageLoaded;
		if(!evance.isLoaded(src)){
			if(loadDynamically){
				var myScript = null;
				var head = document.getElementsByTagName('head')[0];
				if(type == 'css'){
					myScript=document.createElement("link");
					myScript.type="text/css";
					myScript.href=src;
					myScript.rel="stylesheet";
				} else if(type == 'script'){
					myScript = document.createElement('script');
					myScript.type = 'text/javascript';
					myScript.language = 'javascript';
					myScript.src = src;
				}
				if(myScript != null) head.appendChild(myScript);
			} else {
				if(type == 'css'){
					document.write('<link href="'+src+'" type="text/css" rel="stylesheet"></link>');
				} else if (type == 'script'){
					document.write('<script src="'+src+'" language="javascript" type="text/javascript"><\/script>');
				}
			}
			evance.loaded[evance.loaded.length] = src;
		}
	},

	extend: function() {
		var args = arguments;
		if (!args[1]) args = [this, args[0]];
		var subClass = args[0];
		var baseClass = args[1];

	   function inheritance() {};
	   inheritance.prototype = baseClass.prototype;
	   subClass.prototype = new inheritance();
	   subClass.prototype.constructor = subClass;
	   subClass.prototype.parent = baseClass;
	   subClass.superClass = baseClass.prototype;
	},

	// doesn't work on object functions
	// only works on classic function name()
	getFunctionName: function(f){
		if (/function (\w+)/.test(String(f))){
			var _name = RegExp.$1;
			return _name;
		} else {
			return "";
		}
	},

	instanceOf: function(instance){
		if(instance._className) return instance._className;
		return $type(instance);
	},

/*
 * Disable text selection for an element
 */
	disableSelect: function(el){
		el = $(el);
		if($defined(el.onselectstart)){
			el.onselectstart = function(){return false;};
		} else if ($defined(el.style.MozUserSelect)){
			el.style.MozUserSelect = 'none';
		}
		el.unselectable = "on";
		el.style.cursor = 'default';
	},
/*
 * Enable text selection for an element
 */
	enableSelect: function(el){
		el = $(el);
		if($defined(el.onselectstart)){
			el.onselectstart = function(){return true;};
		} else if ($defined(el.style.MozUserSelect)){
			el.style.MozUserSelect = '';
		}
		el.unselectable = "off";
		el.style.cursor = 'auto';
	}
};
/*
 * 	Event Stuff
 */
evance.event = {};
evance.event.fix = function(event){
	if (!event) event = window.event;
	if (event.target) {
		if (event.target.nodeType == 3) event.target = event.target.parentNode;
	} else if (event.srcElement) {
		event.target = event.srcElement;
	}
	return event;
}
evance.event.stopEventPropogation = function(e){
	if (e.stopPropagation) e.stopPropagation();
	else e.cancelBubble = true;
}
evance.event.stopDefaultAction = function(e){
	if (e.preventDefault) e.preventDefault();
	else e.returnValue = false;
}
/*
	EventListener Class
	---------------------------
*/
evance.event.EventListener = function(id, type, element, listenerObject, target){
	this.id = id;
	this.type = type;
	this.listener = listenerObject;
	this.element = element;
	this.target = target;
}

/*
 * Evance Error Handler
 */
 evance.error = {};
 evance.error.handler = function(message, url, line){
 	if(evance._debugger){

		var myObject = {Message: message, URL: url, Line: line};
		var notes = 0;

		if(!window.gecko){
			++notes;
			myObject['Note'+notes] = "For a more accurate error message try Firefox with the Firebug extension.";
		}
		if(line == 1){
			++notes;
			myObject['Note'+notes] = "This looks like an event error.";
		}

		evance.debug(myObject, 'Error');
 		return false;
 	} else {
 		return true;
 	}
 }
  window.onerror = evance.error.handler;
/*
	Setup standard functions
*/
using = evance.using;
evance.core = {};
evance.ui = {};