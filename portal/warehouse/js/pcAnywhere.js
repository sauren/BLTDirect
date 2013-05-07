var Address = new (function(){
	this.id = '';
	this.licence = '';
	this.account = '';
	this.language = 'english';
	this.style = 'simple';
	this.machineId = '';
	this.options = '';
	this.building = '';

	this.ties = new Array();

	this.find = function(obj){
		this.id = obj.id;
		this.postcode = obj.value;
		if(this.postcode != null && this.postcode != ''){
			pcaFastAddressBegin(this.postcode, '', this.language, this.style, this.account, this.licence, this.machineId, this.options);
		} else {
			alert('Please enter a UK Postcode');
		}
	}

	this.found = function(obj){
		var line1 = this.get('line1');
		if(line1){
			line1 = document.getElementById(line1);
			line1.value = obj.line1;
		}
		var line2 = this.get('line2');
		if(line2){
			line2 = document.getElementById(line2);
			line2.value = obj.line2;
		}
		var line3 = this.get('line3');
		if(line3){
			line3 = document.getElementById(line3);
			line3.value = obj.line3;
		}
		var city = this.get('city');
		if(city){
			city = document.getElementById(city);
			city.value = obj.city;
		}
		var county = this.get('county');
		if(county){
			county = document.getElementById(county);
			if(obj.county == '' && obj.city.match(/london/gi)){
				obj.county = 'london';
			} else if(obj.county != ''){
				for(var i=0; i < county.options.length; i++){
					var temp = county.options[i].text;
					var expression = '/' + obj.county + '/gi';
					if(temp.match(eval(expression))){
						county.options[i].selected = true;
					}
				}
			}
		}

	}

	this.add = function(postcode, itemId, fieldId){
		var obj = new Object();
		obj.postcode = postcode;
		obj.itemId = itemId;
		obj.fieldId = fieldId;
		this.ties[this.ties.length] = obj;
	}

	this.get = function(itemId){
		for(var i=0; i < this.ties.length; i++){
			if(this.ties[i].postcode == this.id && this.ties[i].itemId == itemId){
				return this.ties[i].fieldId;
			}
		}
		return false;
	}

});



function pcaFastAddressBegin(postcode, building, language, style, account_code, license_code, machine_id, options)
   {
      var scriptTag = document.getElementById("pcaScriptTag");
      var headTag = document.getElementsByTagName("head").item(0);
      var strUrl = "";

      //Build the url
      strUrl = "https://services.postcodeanywhere.co.uk/inline.aspx?";
      strUrl += "&action=fetch";
      strUrl += "&postcode=" + escape(postcode);
      strUrl += "&building=" + escape(building);
      strUrl += "&language=" + escape(language);
      strUrl += "&style=" + escape(style);
      strUrl += "&account_code=" + escape(account_code);
      strUrl += "&license_code=" + escape(license_code);
      strUrl += "&machine_id=" + escape(machine_id);
      strUrl += "&options=" + escape(options);
      strUrl += "&callback=pcaFastAddressEnd";

      //Make the request
      if (scriptTag)
         {
            headTag.removeChild(scriptTag);
         }
      scriptTag = document.createElement("script");
      scriptTag.src = strUrl
      scriptTag.type = "text/javascript";
      scriptTag.id = "pcaScript";
      headTag.appendChild(scriptTag);
   }

/*
	todo: need to customise
*/
function pcaFastAddressEnd()
   {
      //Test for an error
      if (pcaIsError)
         {
            //Show the error message
            alert(pcaErrorMessage);
         }
      else
         {
            //Check if there were any items found
            if (pcaRecordCount==0)
               {
                  alert("Sorry, no matching items found");
               }
            else
               {
                  //PUT YOUR CODE HERE
				  var ad = new Object();
				  ad.company = pca_organisation_name[0];
				  ad.line1 = pca_line1[0];
				  ad.line2 = pca_line2[0];
				  ad.line3 = pca_line3[0];
				  ad.city = pca_post_town[0];
				  ad.county = pca_county[0];
				  ad.postcode = pca_postcode[0];
				  Address.found(ad);
               }
         }
   }

