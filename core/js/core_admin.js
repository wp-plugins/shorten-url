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

function coreInfo(md5, url, plugin_name, current_core, current_finger, author, src_wait, msg_wait) {
	
	//POST the data and append the results to the results div
	rand = Math.floor(Math.random()*3000) ; 
	window.setTimeout(function() {
		var arguments = {
			action: 'coreInfo', 
			plugin_name : plugin_name, 
			current_finger : current_finger, 
			current_core : current_core, 
			author : author, 
			md5 : md5, 
			src_wait : src_wait, 
			msg_wait : msg_wait, 
			url : url
		} 
		
		waitImg = "<p>"+msg_wait+"<img id='corePluginWait_"+md5+"' src='"+src_wait+"'></p>" ;
		
		jQuery('#corePlugin_'+md5).html(waitImg);

		jQuery.post(ajaxurl, arguments, function(response) {
			if (response!="-1") {
				jQuery('#corePlugin_'+md5).html(response);
			} else {
				coreInfo(md5, url, plugin_name, current_core, current_finger, author); 
			}
		});
	}, rand) ; 
}

/* =====================================================================================
*
*  Update the core
*
*/

function coreUpdate(md5, url, plugin_name, current_core, current_finger, author, from, to, src_wait, msg_wait) {
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
	
	waitImg = "<p>"+msg_wait+"<img id='corePluginWait_"+md5+"' src='"+src_wait+"'></p>" ;
	jQuery('#corePlugin_'+md5).html(waitImg);
	
	jQuery.post(ajaxurl, arguments, function(response) {
		if (response!="-1") {
			jQuery('#corePlugin_'+md5).html(response);
		} else {
			coreUpdate(md5, url, plugin_name, current_core, current_finger, author, from, to) ; 
		}
	});
	return false ; 
}


/* =====================================================================================
*
*  Change the version of the plugin
*
*/

function changeVersionReadme(md5, plugin) {
	jQuery("#wait_changeVersionReadme_"+md5).show();
	var arguments = {
		action: 'changeVersionReadme', 
		plugin : plugin
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery('body').append(response);
		jQuery("#wait_changeVersionReadme_"+md5).hide();
	});
}


/* =====================================================================================
*
*  Save the version and the readme txt
*
*/

function saveVersionReadme(plugin) {
	jQuery("#wait_save").show();
	readmetext = jQuery("#ReadmeModify").val() ; 
	versiontext = jQuery("#versionNumberModify").val() ; 
	var arguments = {
		action: 'saveVersionReadme', 
		readme : readmetext, 
		plugin : plugin,
		version : versiontext
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery('#readmeVersion').html(response);
	});
}


