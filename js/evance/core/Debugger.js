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