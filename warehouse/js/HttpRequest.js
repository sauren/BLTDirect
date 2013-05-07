/**
 * Class to wrap the functionality of the XMLHttpRequest class.
 * Provides functionality to instantiate and send XMLHttpRequests
 * for many modern browsers version including later versions of IE,
 * Firefox, Mozilla, and Safari.
 *
 * @version 1.7
 */
HttpRequest.RETRY_ON_NONE = 1;
HttpRequest.RETRY_ON_TIMEOUT = 2;
HttpRequest.RETRY_ON_ERROR = 3;
HttpRequest.RETRY_ON_BOTH = 4;

function HttpRequest() {
	this.req = null;
	this.url = null;
	this.method = null;
	this.handleResponse = null;
	this.handleError = null;
	this.callee = null;
	this.timeoutHandler = null;
	this.timeoutInterval = 30000;
	this.delayHandler = null;
	this.delayInterval = 0;
	this.format = 'text';
	this.mimeType = null;
	this.headers = new Array();
	this.async = true;
	this.data = null;
	this.cache = true;
	this.retry = HttpRequest.RETRY_ON_NONE;
	this.attempts = 2;
	this.retries = 0;
	this.username = null;
	this.password = null;

	/**
	 * Instantiates an XMLHttpRequest object for various browsers.
	 * Primary attempt to create object for Firefox, Safari, IE7,
	 * followed by later versions of IE, and finally for earlier
	 * versions of IE.
	 *
	 * @return True if the XMLHttpRequest object could be created.
	 */
	this.init = function() {
		if(!this.req) {
			try {
				// Firefox, Safari, IE7.
				this.req = new XMLHttpRequest();
				return true;
			}
			catch(exception) {}

			try {
				// later versions of IE.
				this.req = new ActiveXObject('MSXML2.XMLHTTP');
				return true;
			}
			catch(exception) {}

			try {
				// earlier versions of IE.
				this.req = new ActiveXObject('Microsoft.XMLHTTP');
				return true;
			}
			catch(exception) {}

			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * Makes a request using the XMLHttpRequest object and catches
	 * any response returned from the targetted URL.  This response is
	 * then forwarded onto the function specified to handle the response.
	 */
	this.onHttpRequest = function() {
		var self = this;
		var header = null;

		this.abort();

		if(!this.init()) {
			return false;
		}

		if(this.username && this.password) {
			this.req.open(this.method, this.url, this.async, this.username, this.password);
		}
		else {
			this.req.open(this.method, this.url, this.async);
		}

		// abort and call error handler when request times out.
		if(this.timeoutInterval > 0) {
			this.timeoutHandler = setTimeout(function() {
				self.abort();

				// HTTP specification (RFC 2616) timeout response code and status text.
				if((self.retry == HttpRequest.RETRY_ON_BOTH) || (self.retry == HttpRequest.RETRY_ON_TIMEOUT)) {
					self.onHttpRequestError(null, '408', 'Request Time-out');
				}
				else if(self.callee) {
					self.callee.onHttpRequestError(null, '408', 'Request Time-out');
				}
				else if(self.handleError) {
					self.handleError(null, '408', 'Request Time-out');
				}
			}, this.timeoutInterval);
		}

		// override mime type for Firefox, Safari and IE7.
		if((this.mimeType) && (navigator.userAgent.indexOf('MSIE') == -1)) {
			try {
				this.req.overrideMimeType(this.mimeType);
			}
			catch(exception) {}
		}

		// add custom headers to request.
	    if(this.headers.length) {
			for(var i = 0; i < this.headers.length; i++) {
				header = this.headers[i].split(': ');
				this.req.setRequestHeader(header[0], header[1]);
			}

			this.headers = new Array();
	    }

	    // set appropriate url encoding for post requests.
	    if(this.method == 'POST') {
			this.req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	    }

		if(this.async) {
			this.req.onreadystatechange = function() {
				if(self.req.readyState == 4) {
					self.onHttpRequestResponse();
				}
			}
			this.req.send(this.data);
		}
		else {
			this.req.send(this.data);

			// delay allows for timeout function to execute.
			setTimeout(function() {
				if(self.req) {
					self.onHttpRequestResponse();
				}
			}, 10);
		}

		return true;
	}

	/**
	 * Handles both synchronous and asynchronous responses when received
	 * from the server. The timeout handler will be cleared when it exists
	 * and a response is received. Whereas asynchronous requests will expire
	 * when the specified timeout interval has passed before receiving a
	 * response, synchronous requests will continue to execute until a response
	 * is received irrespective of the timeout interval.  When the execution
	 * time of a synchronous request is greater than the specified timeout
	 * interval, the error handler procedure will be executed if set.
	 */
	this.onHttpRequestResponse = function() {
		var resp = null;

		if(this.timeoutHandler) {
			clearTimeout(this.timeoutHandler);
		}

		switch(this.format) {
			case 'text':
				resp = this.req.responseText;
				break;
			case 'xml':
				resp = this.req.responseXML;
				break;
			case 'object':
				resp = req;
				break;
		}

		// HTTP specification (RFC 2616) response codes.
		if((this.req.status > 199) && (this.req.status < 300)) {
			if(this.callee) {
				this.callee.onHttpRequestResponse(resp, this.req.status, this.req.statusText);
			}
			else if(this.handleResponse) {
				this.handleResponse(resp, this.req.status, this.req.statusText);
			}
		}
		else {
			if((this.retry == HttpRequest.RETRY_ON_BOTH) || (this.retry == HttpRequest.RETRY_ON_ERROR)) {
				this.onHttpRequestError(resp, this.req.status, this.req.statusText);
			}
			else if(this.callee) {
				this.callee.onHttpRequestError(resp, this.req.status, this.req.statusText);
			}
			else if(this.handleError) {
				this.handleError(resp, this.req.status, this.req.statusText);
			}
		}
	}

	/**
	 * Handles retry attempts when either a server error or request timeout
	 * is encountered.  When the number of retries equals the attempt count
	 * setting the error handler is summoned if it exists.
	 *
	 * @param resp The response error obtained from the server.
	 * @param code The three digit result code to understand the request.
	 * @param status The reason-phrase to describe the status code.
	 */
	this.onHttpRequestError = function(resp, code, status) {
		if(this.retries < this.attempts) {
			this.retries++;
			this.onHttpRequest();
		}
		else if(this.callee) {
			this.callee.onHttpRequestError(resp, code, status);
		}
		else if(this.handleError) {
			this.handleError(resp, code, status);
		}
	}

	/**
	 * Handles delays for sending XmlHttpRequests to the server according to
	 * the time delay interval specified. If another request is made before
	 * the previous request's time delay is satisfied, the request is aborted
	 * and reinitialised with the current time delay.
	 */
	this.onHttpRequestDelay = function() {
		var self = this;

		if(this.delayHandler) {
			clearTimeout(this.delayHandler);
		}

		if(this.delayInterval > 0) {
			this.delayHandler = setTimeout(function() {
				return self.onHttpRequest();
			}, this.delayInterval);
		}
		else {
			return this.onHttpRequest();
		}
	}

	/**
	 * Initialises the request with a target URL and function to handle
	 * the response from the targetted server. If caching is disabled
	 * a unique time attribute will be appeneded to the data to avoid IE
	 * caching GET request responses.
	 *
	 * @param url The target url of the server being queried.
	 * @param data The information query string being sent to the server.
	 */
	this.get = function(url, data) {
		if(data) {
			if(url.indexOf('?') != -1) {
				if((url.indexOf('?') + 1) < url.length) {
					data = '&' + data;
				}
			}
			else {
				data = '?' + data;
			}

			url += data;
		}

		if(!this.cache) {
			if(url.indexOf('?') != -1) {
				if((url.indexOf('?') + 1) < url.length) {
					url += '&';
				}
			}
			else {
				url += '?';
			}

			url += 'nocache=' + new Date().getTime();
		}

		this.url = url;
		this.method = 'GET';
		this.retries = 0;

		return this.onHttpRequestDelay();
	}

	/**
	 * Initialises the request with a target URL and function to handle
	 * the response from the targetted server.
	 *
	 * @param url The target url of the server being queried.
	 * @param data The information query string being sent to the server.
	 */
	this.post = function(url, data) {
		this.url = url;
		this.data = data;
		this.method = 'POST';
		this.retries = 0;

		return this.onHttpRequestDelay();
	}

	/**
	 * Abort the request by destroying the XMLHttpRequest instance.
	 * Must change the onreadystate event handler to an empty function
	 * as many XMLHttpRequest implementations will execute the onreadystate
	 * event once abort is called.  Abort calls also clear any timeout
	 * functions which may be waiting to execute.
	 */
	this.abort = function() {
		if(this.timeoutHandler) {
			clearTimeout(this.timeoutHandler);
			this.timeoutHander = null;
		}

		if(this.req) {
			this.req.onreadystatechange = function() {};
			this.req.abort();
			this.req = null;
		}
	}

	/**
	 * Sets the timeout value for XmlHttpRequests.  When the specified
	 * time passes the XmlHttpRequest is aborted and destroyed.  A timeout
	 * value of zero will disabled the requests ability to timeout.
	 *
	 * @param timeout The time in milliseconds to pass before timing out.
	 */
	this.setTimeout = function(timeout) {
		if(timeout > 0) {
			this.timeoutInterval = timeout;
		}
		else {
			this.timeoutInterval = 0;
		}
	}

	/**
	 * Sets the cache status to avoid IE caching GET request responses by
	 * appending unique data attributes to the query string of a GET request.
	 *
	 * @param cache False if requests should send unique data to avoid caching.
	 */
	this.setCaching = function(cache) {
		this.cache = cache;
	}

	/**
	 * Sets the request to execute asynchronously or ssynchronously.
	 * Synchronous requests will freeze the browser window until a
	 * response is received, whether it be successful or not. Therefore
	 * synchronous requests are most appropriately utilised with local
	 * servers.
	 *
	 * @param async True if requests should execute asynchronously.
	 */
	this.setAsynchronous = function(async) {
		this.async = async;
	}

	/**
	 * Sets the mime type of the content being returned.
	 * Implementations of XMLHttpRequest in all major browsers require
	 * the HTTP response Content-Type to be set properly in order for
	 * the response to be handled as XML.
	 *
	 * @param mimeType The mime type of the content being returned.
	 */
	this.setMimeType = function(mimeType) {
		this.mimeType = mimeType;
	}

	/**
	 * Sets the format of the response being received.
	 * Available responses include text, xml, object
	 *
	 * @param format The format of the response being received.
	 */
	this.setResponseFormat = function(format) {
		this.format = format;
	}

	/**
	 * Sets header attributes for a single instance of the XmlHttpRequest.
	 * Once the request has been sent the values stored in the headers array
	 * will be cleared.
	 *
	 * @param headerName The name of the header attribute.
	 * @param headerValue The value of the header attribute.
	 */
	this.setRequestHeader = function(headerName, headerValue) {
		this.headers.push(headerName + ': ' + headerValue);
	}

	/**
	 * Sets the function to handle successful XmlHttpRequest responses
	 * from the server.
	 *
	 * @param handler The function to handle the response from the server.
	 */
	this.setHandlerResponse = function(handler) {
		this.handleResponse = handler;
	}

	/**
	 * Sets the function to handle unsuccessful XmlHttpRequest responses
	 * from the server.
	 *
	 * @param handler The function to handle the response from the server.
	 */
	this.setHandlerError = function(handler) {
		this.handleError = handler;
	}

	/**
	 * Sets the function to handle successful and unsuccessful
	 * XmlHttpRequest responses from the server.
	 *
	 * @param handler The function to handle the response from the server.
	 */
	this.setHandlerBoth = function(handler) {
		this.handleResponse = handler;
		this.handleError = handler;
	}

	/**
	 * Sets the callee object which has reference to this request stack.
	 * Callees handle successful and failed responses by their uniquely
	 * identifiable request handler functions.
	 *
	 * @param callee The object referencing this request.
	 */
	this.setCallee = function(callee) {
		this.callee = callee;
	}

	/**
	 * Sets the username for authentication purposes when the url requested
	 * required it. Used in conjunction with a password.
	 *
	 * @param username The username for authentication purposes if required.
	 */
	this.setUsername = function(username) {
		this.username = username;
	}

	/**
	 * Sets the password for authentication purposes when the url requested
	 * required it. Used in conjunction with a username.
	 *
	 * @param password The password for authentication purposes if required.
	 */
	this.setPassword = function(password) {
		this.password = password;
	}

	/**
	 * Sets the number of retry attempts to successfuly send and received
	 * an XmlHttpRequest to the server. The number of attempts is reliant
	 * upon the retry status of the object which may retry on timeouts,
	 * errors, both, or neither.
	 *
	 * @param attempts The number of attempts to successfully send and receive.
	 */
	this.setRetryAttempts = function(attempts) {
		this.attempts = attempts;
	}

	/**
	 * Sets the retry status which determines whether XmlHttpRequests should
	 * retry when either a timeout or error is encountered, or both.
	 *
	 * @param status The status to determine whether to retry or not.
	 */
	this.setRetryStatus = function(status) {
		this.retry = status;
	}

	/**
	 * Sets the delay for sending XmlHttpRequest to the server by the given
	 * value. If another request is made before the time delay is satisified
	 * then a new request will initialise according to the current time delay.
	 *
	 * @param delay The time in milliseconds to delay the XmlHttpRequest by.
	 */
	this.setDelay = function(delay) {
		if(delay > 0) {
			this.delayInterval = delay;
		}
		else {
			this.delayInterval = 0;
		}
	}
}