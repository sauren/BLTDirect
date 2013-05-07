using('evance.ui.EventDispatcher');

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