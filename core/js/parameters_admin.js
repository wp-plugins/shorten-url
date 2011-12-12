/* =====================================================================================
*
*  Toggle folder
*
*/

function activateDeactivate_Params(param, toChange) {
	
	isChecked = jQuery("#"+param).is(':checked');
	
	for (i=0; i<toChange.length; i++) {
		if (!isChecked) {
			jQuery("label[for='"+toChange[i]+"']").parents("tr").eq(0).hide() ; 
			jQuery("#"+toChange[i]).attr('disabled', 'disabled') ; 
		} else {
			jQuery("label[for='"+toChange[i]+"']").parents("tr").eq(0).show() ; 
			jQuery("#"+toChange[i]).removeAttr('disabled') ;
		}
	}
	return isChecked ; 
}

