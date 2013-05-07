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