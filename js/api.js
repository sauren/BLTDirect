// Handset Detection mobile redirection script 1.3
// 
var HandsetDetection = {
	ismobile : false,
	istablet : false,
	isconsole : false,
	xhtmllevel : 0,
	width :	0,
	height : 0,
	formfactor : "unknown",
	hdclass : "",
	vendor : "",
	model : "", 
	os : "",
	osversion : "",
	browser : "", 
	browserversion : "",
	urls : ["test.bltdirect.com"],
	country : "in",
	city : "bangalore",
	region : "karnataka",
	isp : "sify limited",
	company : "sify limited",
	sid : "514099fdbe5948a3410002c6",
	
	redirect: function() {
		// Smart Redirection - if referrer domain is in url list then do nothing - its internal.
		if ("1" == "1") {
			var refurl = "";
			if (document.referrer) {
				refurl = document.referrer.toString();
			} else if (window.opener && !window.opener.closed && window.opener.location) {
				try {
					refurl = window.opener.location.toString();
				} catch (err) {
					refurl = "";
				}
			}
			if (refurl.length > 0) {
				refurl = refurl.toLowerCase().replace(/http:\/\//i, "").replace(/https:\/\//i, "").split("/", 1)[0];
				urls = this.urls;
				for(var i=0,length=urls.length; i < length; i++) {
					if (urls[i].indexOf(refurl) > -1) {
						// Found match - quit.
						return 1;
					}
				}
			}
		}
		
		// Redirection rules.
// All Tablets
if (   (this.istablet == true ) ) { window.location = "http://test.bltdirect.com/wsplmobile"; return 1; }
// All Mobiles
if (   (this.ismobile == true ) ) { window.location = "http://test.bltdirect.com/wsplmobile"; return 1; }
		
	}
}

HandsetDetection.redirect();
