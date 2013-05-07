using('evance.ui.EventDispatcher');

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