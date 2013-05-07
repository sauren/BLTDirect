using('evance.net.Request');
using('evance.ui.EventDispatcher');

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