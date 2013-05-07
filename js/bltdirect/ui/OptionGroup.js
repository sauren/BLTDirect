using('mootools.Class.Extras');
using('mootools.Function');
using('mootools.Fx.Base');
using('mootools.Fx.CSS');
using('mootools.Fx.Style');
using('mootools.Fx.Styles');
using('mootools.Fx.Transitions');

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