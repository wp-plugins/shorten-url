/* =====================================================================================
*
*  Get the plugin Info
*
*/

function pluginInfo(id_div, url, plugin_name) {
	
	//POST the data and append the results to the results div
	rand = Math.floor(Math.random()*3000) ; 
	window.setTimeout(function() {
		var arguments = {
			action: 'pluginInfo', 
			plugin_name : plugin_name, 
			url : url
		} 
		
		jQuery.post(ajaxurl, arguments, function(response) {
			if (response!="-1") {
				jQuery('#'+id_div).html(response);
			} else {
				pluginInfo(id_div, url, plugin_name) ; 
			}
		});
	}, rand) ; 
}

/* =====================================================================================
*
*  Get the core Info
*
*/

function coreInfo(id_div, url, plugin_name, current_core, current_finger, author) {
	
	//POST the data and append the results to the results div
	rand = Math.floor(Math.random()*3000) ; 
	window.setTimeout(function() {
		var arguments = {
			action: 'coreInfo', 
			plugin_name : plugin_name, 
			current_finger : current_finger, 
			current_core : current_core, 
			author : author, 
			url : url
		} 
		
		jQuery.post(ajaxurl, arguments, function(response) {
			if (response!="-1") {
				jQuery('#'+id_div).html(response);
			} else {
				coreInfo(id_div, url, plugin_name, current_core, current_finger, author); 
			}
		});
	}, rand) ; 
}

/* =====================================================================================
*
*  Update the core
*
*/

function coreUpdate(id_div, url, plugin_name, current_core, current_finger, author, from, to) {
	var arguments = {
		action: 'coreUpdate', 
		plugin_name : plugin_name, 
		current_finger : current_finger, 
		current_core : current_core, 
		author : author, 
		url : url,
		from : from, 
		to : to
	} 
	jQuery('#wait_'+id_div).show();
	jQuery.post(ajaxurl, arguments, function(response) {
		if (response!="-1") {
			jQuery('#'+id_div).html(response);
		} else {
			coreUpdate(id_div, url, plugin_name, current_core, current_finger, author, from, to) ; 
		}
	});
	return false ; 
}
