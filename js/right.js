function getRightFinderResults(obj) {
	var values = new Array();
	var combinations = new Array();
	var filterElement = null;
	var container = jQuery(obj).closest('.bulbFinderForm');
	for(var i=0; i<rightFinderGroups.length; i=i+2) {
		filterElement = jQuery('.' + rightFinderGroups[i] + ' select', container);
		if(filterElement) {
			if(filterElement.val() > 0) {
				if(!rightFinderGroups[i+1]) {
					values.push(filterElement.val());
				} else {
					combinations.push(filterElement.val());
				}
			}
		}
	}

	if((values.length > 0) || (combinations.length > 0)) {
		jQuery(".results", container).hide();
		jQuery(".loader", container).show();
		jQuery.get(
			'ignition/lib/util/loadBulbFinder.php?values=' + values.join(',') + '&combinations=' + combinations.join(','), function(results){
			updateRightFinderResults(results, container);
		});
	} else {
		updateRightFinderResults(0, container);
	}
}

function updateRightFinderResults(matches, container) {
	var results = jQuery('.right-results', container);

	jQuery(".loader", container).hide();
	jQuery(".results", container).show();

	if(results.length) {
		results.show();	
	}
	
	var resultsMatches = jQuery('.right-results-matches', container);
	
	if(resultsMatches.length) {
		if(matches.total && matches.total > 0) {
			resultsMatches.html(matches.total + ' matches');
		} else {
			resultsMatches.html('<?php echo $zeroMatch; ?>');
		}
	}
	var resultsShow = jQuery('.right-results-show', container);
	
	if(resultsShow.length) {
		if(matches.total && matches.total > 0){
			resultsShow.show();
		} else {
			resultsShow.hide();
		}
	}
	
	if(!matches.total || (matches.combine.length == 0 && matches.values.length==0)){
		jQuery('.bulbFinderForm select option', container).show();
	} else {
		// reset values
		jQuery('select', container).each(function(i){
			var select = jQuery(select);

			jQuery('option', jQuery(this)).each(function(j){
				var opt = jQuery(this);
				if(!opt.attr('data-label')){
					opt.attr('data-label', opt.text());
				}
				if(opt.text() != ''){
					//opt.text(opt.attr('data-label') + ' (0)');
					opt.hide();
				}
			});
		});

		// update select boxes
		for(var i in matches.values){
			// get the select using group id
			// go through each option set all numbers to 0
			var match = matches.values[i];
			var select = jQuery('.bulbFinderSelect_' + match['Group_ID'] + ' select', container);
			if(select.length){
				var opt = jQuery('option[value="'+match['Value_ID']+'"]', select);
				//opt.text(opt.attr('data-label') + ' ('+match['Total']+')');
				opt.show();
			}
		}
		for(var i in matches.combine){
			// get the select using group id
			// go through each option set all numbers to 0
			var match = matches.combine[i];
			var select = jQuery('.bulbFinderSelect_' + match['Group_ID'] + ' select', container);
			if(select.length){
				var opt = jQuery('option[value="'+match['Value_ID']+'"]', select);
				//opt.text(opt.attr('data-label') + ' ('+match['Total']+')');
				opt.show();
			}
		}
	}
}