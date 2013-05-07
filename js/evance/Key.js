using('evance.ui.EventDispatcher');

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