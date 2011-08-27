
/* =====================================================================================
*
*  Add a new translation
*
*/

function translate_add(plug_param,dom_param) {
	var num = jQuery("#new_translation option:selected").val() ;
	jQuery("#wait_translation_add").show();
	
	var arguments = {
		action: 'translate_add', 
		idLink : num,
		plugin : plug_param, 
		domain : dom_param
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait_translation_add").fadeOut();
		jQuery("#zone_edit").html(response);
		window.location = String(window.location).replace(/\#.*$/, "") + "#edit_translation";
	});    
}

/* =====================================================================================
*
*  Save the new translation
*
*/

function translate_create (plug_param,dom_param,lang_param, nombre) {
	var num = jQuery("#new_translation option:selected").val() ;
	jQuery("#wait_translation_create").show();
	
	var result = new Array() ; 
	for (var i=0 ; i<nombre ; i++) {
		result[i] = jQuery("#trad"+i).val()  ;
	}
	
	var arguments = {
		action: 'translate_create', 
		idLink : result,
		name : jQuery("#nameAuthor").val(), 
		email : jQuery("#emailAuthor").val(), 
		lang : lang_param, 
		plugin : plug_param, 
		domain : dom_param
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait_translation_create").fadeOut();
		jQuery("#zone_edit").html("");
		jQuery("#summary_of_translations").html(response);
		window.location = String(window.location).replace(/\#.*$/, "") + "#top_translation";
	});    
}

/* =====================================================================================
*
*  Modify a translation
*
*/

function modify_trans(plug_param,dom_param,lang_param) {
	jQuery("#wait_translation_create").show();
	
	var arguments = {
		action: 'translate_modify', 
		lang : lang_param, 
		plugin : plug_param, 
		domain : dom_param
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait_translation_create").fadeOut();
		jQuery("#zone_edit").html(response);
		window.location = String(window.location).replace(/\#.*$/, "") + "#edit_translation";
	});    
}

/* =====================================================================================
*
*  Save the modification of the translation
*
*/

function translate_save_after_modification (plug_param,dom_param,lang_param, nombre) {

	var num = jQuery("#new_translation option:selected").val() ;
	jQuery("#wait_translation_modify").show();
	
	var result = new Array() ; 
	for (var i=0 ; i<nombre ; i++) {
		result[i] = jQuery("#trad"+i).val()  ;
	}
		
	var arguments = {
		action: 'translate_create', 
		idLink : result,
		name : jQuery("#nameAuthor").val(), 
		email : jQuery("#emailAuthor").val(), 
		lang : lang_param, 
		plugin : plug_param, 
		domain : dom_param
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait_translation_modify").fadeOut();
		jQuery("#zone_edit").html("");
		jQuery("#summary_of_translations").html(response);
		window.location = String(window.location).replace(/\#.*$/, "") + "#top_translation";
	});    
}

/* =====================================================================================
*
*  Send the modified translation
*
*/

function send_trans(plug_param,dom_param,lang_param) {

	var num = jQuery("#new_translation option:selected").val() ;
	jQuery("#wait_translation_modify").show();
		
	var arguments = {
		action: 'send_translation', 
		lang : lang_param, 
		plugin : plug_param, 
		domain : dom_param
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait_translation_modify").fadeOut();
		jQuery("#zone_edit").html(response);
		window.location = String(window.location).replace(/\#.*$/, "") + "#edit_translation";
	});    
}

/* =====================================================================================
*
*  Update the summary
*
*/

function update_summary(plug_param,dom_param) {

	jQuery("#wait_translation_modify").show();
		
	var arguments = {
		action: 'update_summary', 
		plugin : plug_param, 
		domain : dom_param
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait_translation_modify").fadeOut();
		jQuery("#zone_edit").html("");
		jQuery("#summary_of_translations").html(response);
		window.location = String(window.location).replace(/\#.*$/, "") + "#top_translation";
	});    
}