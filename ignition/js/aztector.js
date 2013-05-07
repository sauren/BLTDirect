	
	// aztector version 1.1
	// copyright (c) Azexis, 2003
	// Azexis is a registered trademark
	// --------------------------------
	// calling example: checkFor('../flash/index.html','Screen Colour','Screen Res','Flash','Director','SVG','Acrobat','Player','CPU','Browser')
	
	// Tset to true if you want to send results through to the url via GET
	var sendData = false;
	
	// Automatic Redirection
	// This will redirect the user to the required page
	// automatically if all the criteria are met
	// set to false if you wish  manual
	// Note: Manual redirection required if criteria not met
	var autoRedirect = true;
	
	// Screen Resolution : 640*480 | 800*600 | 1024*768 | 1280*1024
	var screenWidth = 1024;
	var screenHeight = 768;
	
	// Detect Flash: 3 | 4 | 5 | 6 etc
	var checkFlash = 6;
	
	// Detect CPU: 
	// Example: For a pentium II 400 processor set to 300
	// You should set an appropriate buffer
	var checkCPU = 300;

	// Detect Colour Depth
	// Remember not everyone will have high colour settings
	// many businesses still use 256 colours....shame!!!
	var checkColour = 16;
	
	// Check Browsers:
	// Will check for versions >= specified
	// set to false if you do not wish to check
	var checkNS = 7;	// netscape
	var checkIE = 5;	// Internet Explorer
	var checkOP = 7;	// Opera
	
	// Check Plugins: Audio/Video
	// set to false if you do not use a format on your site
	// which is compatible with the player
	var check_Windows_Media_Player = true;
	var check_QuickTime = true;
	var check_Real_Player = true;
	
	// Preferred Video/Audio Plugins: Windows Media Player | Real Player | QuickTime
	var preferred_player = "QuickTime";
	
	// The URLS of the various Plugins and Browsers at the time
	// of writing the script
	var url_IE = "http://www.microsoft.com/windows/ie/default.htm";
	var url_NS = "http://channels.netscape.com/ns/browsers/download.jsp";
	var url_OP = "http://www.opera.com";
	var url_QT = "http://www.apple.com/quicktime/products/qt/";
	var url_RP = "http://www.real.com/realoneplayer.html";
	
	var url_WMP = "http://www.microsoft.com/windows/windowsmedia/players.aspx";
	var url_SWF = "http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash";
	var url_DIR = "http://sdc.shockwave.com/shockwave/download/download.cgi?&P5_Language=English";
	var url_SVG = "http://www.adobe.com/svg/viewer/install/";
	var url_ADO = "http://www.adobe.com/products/acrobat/readstep2.html";
	

	//-----------------------------------------------------------------------------------
	// Convert userAgent string to Lowercase
	var agt=navigator.userAgent.toLowerCase();
	var is_major = parseInt(navigator.appVersion);
	var is_minor = parseFloat(navigator.appVersion);
	
	// Browser Agent
	var is_ns  = ((agt.indexOf('mozilla')!=-1) && (agt.indexOf('spoofer')==-1) && (agt.indexOf('compatible') == -1) && (agt.indexOf('opera')==-1) && (agt.indexOf('webtv')==-1) && (agt.indexOf('hotjava')==-1) || (agt.indexOf('netscape')!=-1));
	var is_ie   = (agt.indexOf("msie") != -1);
	var is_aol   = (agt.indexOf("aol") != -1);
	var is_opera = (agt.indexOf("opera") != -1);
	var is_webtv = (agt.indexOf("webtv") != -1);
	
	// Platform
	var is_win   = ( (agt.indexOf("win")!=-1) || (agt.indexOf("16bit")!=-1) );
	var is_win95 = ((agt.indexOf("win95")!=-1) || (agt.indexOf("windows 95")!=-1));
	var is_win16 = ((agt.indexOf("win16")!=-1) || (agt.indexOf("16bit")!=-1) || (agt.indexOf("windows 3.1")!=-1) || (agt.indexOf("windows 16-bit")!=-1) );
	var is_win31 = ((agt.indexOf("windows 3.1")!=-1) || (agt.indexOf("win16")!=-1) || (agt.indexOf("windows 16-bit")!=-1));
	var is_win98 = ((agt.indexOf("win98")!=-1) || (agt.indexOf("windows 98")!=-1));
	var is_win2000 = ((agt.indexOf("winnt")!=-1) || (agt.indexOf("windows nt 5")!=-1));
	var is_winnt = ((agt.indexOf("winnt")!=-1) || (agt.indexOf("windows nt")!=-1) && !is_win2000);
	var is_win32 = (is_win95 || is_winnt || is_win98 || ((is_major >= 4) && (navigator.platform == "Win32")) || (agt.indexOf("win32")!=-1) || (agt.indexOf("32bit")!=-1));
	var is_os2   = ((agt.indexOf("os/2")!=-1) || (navigator.appVersion.indexOf("OS/2")!=-1) || (agt.indexOf("ibm-webexplorer")!=-1));
	var is_mac = (agt.indexOf("mac")!=-1);
	var is_mac68k = (is_mac && ((agt.indexOf("68k")!=-1) || (agt.indexOf("68000")!=-1)));
	var is_macppc = (is_mac && ((agt.indexOf("ppc")!=-1) || (agt.indexOf("powerpc")!=-1)));

	// Misc Globals
	var detectableWithVB = false;
	var pluginFound = false;
	var isFlashOK = false;
	var icon_success = "./images/aztector_5.gif";
	var icon_download = "./images/aztector_4.gif";
	var icon_failed = "./images/aztector_6.gif";
	var isAllSuccessful;
	var urlReturnCode = "aztector=true";
	
	
	// Check Screen Resolution
	function screenRes(){
		var tempHTML = "";
		var checkScreen = screenWidth*screenHeight;
		var icon = "./images/aztector_pc_1.gif";
		var text = "Screen Resolution (min "+ screenWidth +" x "+ screenHeight +")";
		var screenOK = ((window.screen.width*window.screen.height) < checkScreen)? false : true;
		var url = (screenOK)? "success" : "failed";
		urlReturnCode += (url == "success")?"&resolution=1":"&resolution=0";
		tempHTML = buildRow(text,icon,url);
		return tempHTML;
	}
	
	// Check Flash Plugin
	function flashVer(){
		var tempHTML = "";
		var icon = "./images/aztector_flash_1.gif";
		var text = "Macromedia Flash Player "+ checkFlash +"+";
		if (navigator.plugins["Shockwave Flash"]) {
			var plugin_version = 0;
			var plugin_description = navigator.plugins["Shockwave Flash"].description.split(" ");

			for (var i = 0; i < plugin_description.length; ++i) {
				if (isNaN(parseInt(plugin_description[i]))) continue;
				plugin_version = plugin_description[i];
			}
		}
		if (plugin_version >= checkFlash) isFlashOK = true;
		if (is_ie && is_win32) {
			document.write('<SCRIPT LANGUAGE="VBScript"\>\n');
			document.write('on error resume next\n');
			document.write('isFlashOK = (IsObject(CreateObject("ShockwaveFlash.ShockwaveFlash.'+checkFlash+'")))\n');
			document.write('<'+'/SCRIPT> \n');
		}
		var url = (isFlashOK)? "success": url_SWF;
		urlReturnCode += (url == "success")?"&flash=1":"&flash=0";
		tempHTML = buildRow(text,icon,url);
		return tempHTML;
	}
	
	// Check CPU Rating
	function cpuRating(){
		var tempHTML = "";
		var icon = "./images/aztector_pc_1.gif";
		var text = "Checking For 400MHz Processor +";
		var url = "";
		var average,t1,t2,t,l,report=0,passes,offset=document.all?110:150;
		passes = 40;

		for (l=0;l<passes;l++){
			t1 = new Date().getTime();
			for (t=0;t<20000;t++){}
			t2 = new Date().getTime()
			report += t2-t1
		}
		average = report/passes
		mhz = parseInt((65/average)*offset)
		url = (mhz >= checkCPU)? "success" : "failed";
		urlReturnCode += (url == "success")?"&cpu=1":"&cpu=0";
		tempHTML = buildRow(text,icon,url);
		return tempHTML;
	}
	
	// Check Colour Depth
	function colourDepth(){
		var tempHTML = "";
		var icon = "./images/aztector_pc_1.gif";
		var text = "Recommended Colour Settings: "+ checkColour;
		var url = "";
		url = (window.screen.colorDepth >= checkColour)? "success" : "failed";
		urlReturnCode += (url == "success")?"&colour=1":"&colour=0";
		tempHTML = buildRow(text,icon,url);		
		return tempHTML;
	}
	
	// Check Browser Support
	function checkBrowser(){
		var temp;
		var tempHTML = "";
		var icon = "";
		var text = "";
		var url = "";
		
		if (is_ie) {
			var pos = agt.indexOf('msie');
			var bVer = agt.substring(pos + 5);
			var pos = bVer.indexOf(';');
			bVer = bVer.substring(0,pos);
		}
		if (is_opera)	{
			var pos = agt.indexOf('opera');
			var bVer = agt.substring(pos + 6);
			var pos = bVer.indexOf(' ');
			bVer = bVer.substring(0, pos);
		}
		if (is_ns) {
			var bVer = agt.substring(8);
			var pos = bVer.indexOf(' ');
			bVer = bVer.substring(0, pos);
		}
		if (is_ns && parseInt(navigator.appVersion) >= 5) {
			var pos = agt.lastIndexOf('/');
			var bVer = agt.substring(pos + 1);
		}
		
		temp = ((is_ie && bVer >= checkIE) || (is_opera && bVer >= checkOP) || (is_ns && bVer >= checkNS))? true : false;
		
		if(is_ie){
			icon = "./images/aztector_ie_1.gif";
			text = "Browser: Internet Explorer " + checkIE + "+";
			url = (temp)? "success": url_IE;
			tempHTML = buildRow(text,icon,url);
			urlReturnCode += '&browser=ie|'+bVer;
		}
		else if(is_opera){
			icon = "./images/aztector_opera_1.gif";
			text = "Browser: Opera " + checkOP + "+";
			url = (temp)? "success": url_OP;
			tempHTML = buildRow(text,icon,url);
			urlReturnCode += '&browser=opera|'+bVer
		}
		else if(is_ns){
			icon = "./images/aztector_ns_1.gif";
			text = "Browser: Netscape Navigator " + checkNS + "+";
			url = (temp)? "success": url_NS;
			tempHTML = buildRow(text,icon,url);
			urlReturnCode += '&browser=netscape|'+bVer
		}
		else if(!is_ns && !is_opera && !is_ie){
			icon = "./images/aztector_ie_1.gif";
			text = "Browser: Internet Explorer " + checkIE + "+";
			url = url_IE;
			tempHTML = buildRow(text,icon,url);
			icon = "./images/aztector_opera_1.gif";
			text = "Browser: Opera " + checkOP + "+";
			url = url_OP;
			tempHTML += buildRow(text,icon,url);
			icon = "./images/aztector_ns_1.gif";
			text = "Browser: Netscape Navigator " + checkNS + "+";
			url = url_NS;
			tempHTML += buildRow(text,icon,url);
		}
		return tempHTML;	
	}
	
	function canCheckPlugins(){
		return (detectableWithVB || (navigator.plugins && navigator.plugins.length > 0) )?true:false;
	}
	
	function checkDirector() { 
		var tempHTML = "";
		var icon = "./images/aztector_director_1.gif";
		var text = "Macromedia Shockwave Player";
		var url  = "";
    	pluginFound = detectPlugin('Shockwave','Director'); 
    	if(!pluginFound && detectableWithVB) pluginFound = detectActiveXControl('SWCtl.SWCtl.1');
    	url = (pluginFound)? "success": url_DIR;
		urlReturnCode += (url == "success")?"&director=1":"&director=0";
		tempHTML = buildRow(text,icon,url);
		return tempHTML;
	}
	
	function checkQT() {
    	pluginFound = detectPlugin('QuickTime');
    	if(!pluginFound && detectableWithVB) pluginFound = detectQuickTimeActiveXControl();
    	return pluginFound;
	}

	function checkRP() {
    	pluginFound = detectPlugin('RealPlayer');
    	if(!pluginFound && detectableWithVB) {
			pluginFound = (detectActiveXControl('rmocx.RealPlayer G2 Control') || 
				detectActiveXControl('RealPlayer.RealPlayer(tm) ActiveX Control (32-bit)') || 
				detectActiveXControl('RealVideo.RealVideo(tm) ActiveX Control (32-bit)'));
    	}	
    	return pluginFound;
	}

	function checkWMP() {
    	pluginFound = detectPlugin('Windows Media');
    	if(!pluginFound && detectableWithVB) pluginFound = detectActiveXControl('MediaPlayer.MediaPlayer.1');
    	return pluginFound;
	}
	
	
	// Check for Adobe Acrobat Plugin
	function checkAcrobat() {
		var tempHTML = "";
		var icon = "./images/aztector_adobe_2.gif";
		var text = "Adobe Acrobat Reader";
		var url = "";
    	pluginFound = detectPlugin('Adobe Acrobat');
    	if(!pluginFound && detectableWithVB) pluginFound = detectActiveXControl('PDF.PdfCtrl.5');
		url = (pluginFound)? "success": url_ADO;
		urlReturnCode += (url == "success")?"&acrobat=1":"&acrobat=0";
		tempHTML = buildRow(text,icon,url);
		return tempHTML;
	}
	
	// Check for Adobe SVG Viewer Plugin
	function checkSVG() {
		var tempHTML = "";
		var icon = "./images/aztector_adobe_1.gif";
		var text = "Adobe SVG Viewer";
		var url = "";
    	pluginFound = detectPlugin('SVG Viewer');
    	if(!pluginFound && detectableWithVB) pluginFound = detectActiveXControl('Adobe.SVGCtl');	
		url = (pluginFound)? "success": url_SVG;
		urlReturnCode += (url == "success")?"&svg=1":"&svg=0";
		tempHTML = buildRow(text,icon,url);
		return tempHTML;
	}
	
	function detectPlugin() {
    	var daPlugins = detectPlugin.arguments;
    	var pluginFound = false;
    	if (navigator.plugins && navigator.plugins.length > 0) {
			for (var i=0; i < navigator.plugins.length; i++ ) {
	    		var numFound = 0;
	    		for(var j=0; j < daPlugins.length; j++) {
					if( (navigator.plugins[i].name.indexOf(daPlugins[j]) >= 0) || (navigator.plugins[i].description.indexOf(daPlugins[j]) >= 0) ) {
		    			numFound++;
					}   
	    		}
	    		if(numFound == daPlugins.length) {
					pluginFound = true;
					break;
	    		}
			}
    	}
    	return pluginFound;
	}


	if ((navigator.userAgent.indexOf('MSIE') != -1) && (navigator.userAgent.indexOf('Win') != -1)) {
    	document.writeln('<script language="VBscript">');

    	document.writeln('detectableWithVB = False');
    	document.writeln('If ScriptEngineMajorVersion >= 2 then');
    	document.writeln('  detectableWithVB = True');
    	document.writeln('End If');

    	document.writeln('Function detectActiveXControl(activeXControlName)');
    	document.writeln('  on error resume next');
    	document.writeln('  detectActiveXControl = False');
    	document.writeln('  If detectableWithVB Then');
    	document.writeln('     detectActiveXControl = IsObject(CreateObject(activeXControlName))');
    	document.writeln('  End If');
    	document.writeln('End Function');

    	document.writeln('Function detectQuickTimeActiveXControl()');
    	document.writeln('  on error resume next');
    	document.writeln('  detectQuickTimeActiveXControl = False');
    	document.writeln('  If detectableWithVB Then');
    	document.writeln('    detectQuickTimeActiveXControl = False');
    	document.writeln('    hasQuickTimeChecker = false');
    	document.writeln('    Set hasQuickTimeChecker = CreateObject("QuickTimeCheckObject.QuickTimeCheck.1")');
    	document.writeln('    If IsObject(hasQuickTimeChecker) Then');
   	 	document.writeln('      If hasQuickTimeChecker.IsQuickTimeAvailable(0) Then ');
    	document.writeln('        detectQuickTimeActiveXControl = True');
    	document.writeln('      End If');
    	document.writeln('    End If');
    	document.writeln('  End If');
    	document.writeln('End Function');

    	document.writeln('</scr' + 'ipt>');
	}

	function checkFor(){
		var args = arguments;
		var aztectorHTML = "";
		var newlocation = "";
		var tempHTML = "";
		isAllSuccessful = true;
		
		for(var i=1; i < args.length; i++){
			switch(args[i].toLowerCase()){
				case "screen res": tempHTML+= screenRes(); break;
				case "screen colour": tempHTML+= colourDepth();  break;
				case "flash": tempHTML+=flashVer(); break;
				case "director": tempHTML+=checkDirector(); break;
				case "svg": tempHTML+=checkSVG(); break;
				case "acrobat": tempHTML+=checkAcrobat(); break;
				case "browser": tempHTML+=checkBrowser(); break;
				case "player": tempHTML+=audioVideo(); break;
				case "cpu": tempHTML+= cpuRating(); break;
			}
		}
		
		
		aztectorHTML += '<div id="aztectorHeader">';		
		if(isAllSuccessful){
			aztectorHTML += 'Your web browser and settings are compatible with Ignition.';
		}
		else{
			aztectorHTML += 'Some compatibility issues were found. Please check below!';
		}
		aztectorHTML += '</div>';
		newlocation = args[0];
		if(sendData) newlocation += "?"+urlReturnCode;
		
		aztectorHTML += '<div id="aztectoreResults"><table width="300">';
		aztectorHTML += tempHTML;
		aztectorHTML += "</table></div><div id=\"aztectorFooter\"><form><input type=\"button\" name=\"continue\" value=\"continue\" class=\"btn\" onclick=\"window.location.href='" + newlocation + "'\"></form></div>";
		
		if(autoRedirect && isAllSuccessful){
			window.location.href = newlocation;
		}
		return aztectorHTML;
	}
	
	
	function buildRow(text,icon,url){
		var tempHTML = "";
		tempHTML += '<tr><td><img src="'+ icon +'" border="0" hspace="0" vspace="0"></td>';
		tempHTML += '<td valign="top">'+ text +'</td>';
		if(url != "success"){
			isAllSuccessful = false;
			if(url == "failed"){
				tempHTML += '<td><img src="'+ icon_failed +'" border="0" hspace="0" vspace="0" alt="Check Failed"></td></tr>';
			}
			else{
				tempHTML += '<td><a href="'+ url +'" target="_blank"><img src="'+ icon_download +'" border="0" hspace="0" vspace="0" alt="Download / Update"></a></td></tr>';
			}
		}
		else{
			tempHTML += '<td><img src="'+ icon_success +'" border="0" hspace="0" vspace="0" alt="Check Passed"></td></tr>';
		}
		return tempHTML;
	}

	function audioVideo(){
		var tempHTML = "";
		var pluginsFound = false;
		var icon = "";
		var text = preferred_player;
		var url = "";
		
		// try and find the preferred player first
		switch(preferred_player.toLowerCase()){
			case "windows media player": 	icon = "./images/aztector_wmp_1.gif"; 
											url = (checkWMP())? "success" : url_WMP; 
											break;
			case "quicktime": 				icon = "./images/aztector_qt_1.gif"; 
											url = (checkQT())? "success" : url_QT; 
											break;
			case "real player":		 		icon = "./images/aztector_rp_1.gif"; 
											url = (checkRP())? "success" : url_RP; 
											break;
		}

		if(check_Windows_Media_Player){
			if(checkWMP()){
				if(url != "success" && preferred_player.toLowerCase() != "windows media player"){
					icon = "./images/aztector_wmp_1.gif"; 
					url = "success";
					text = "Windows Media Player";
				}
				urlReturnCode += '&wmp=1';
			}
			else{
				urlReturnCode += '&wmp=0';
			}
		}
		if(check_QuickTime){
			if(checkQT()){
				if(url != "success" && preferred_player.toLowerCase() != "quicktime"){
					icon = "./images/aztector_qt_1.gif"; 
					url = "success";
					text = "QuickTime";
				}
				urlReturnCode += '&qt=1';
			}
			else{
				urlReturnCode += '&qt=0';
			}
		}
		if(check_Real_Player){
			if(checkRP()){
				if(url != "success" && preferred_player.toLowerCase() != "realplayer"){
					icon = "./images/aztector_rp_1.gif"; 
					url = "success";
					text = "Real Player";
				}
				urlReturnCode += '&rp=1';
			}
			else{
				urlReturnCode += '&rp=0';
			}
		}

		
		tempHTML = buildRow(text,icon,url);
		return tempHTML;
	}

	
	