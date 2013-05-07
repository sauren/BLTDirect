using('mootools.Class.Extras');
using('mootools.Function');
using('mootools.Fx.Base');
using('mootools.Fx.CSS');
using('mootools.Fx.Style');
using('mootools.Fx.Styles');
using('mootools.Fx.Transitions');
		
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