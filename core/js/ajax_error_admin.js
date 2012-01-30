jQuery().ready(function(){
	jQuery.ajaxSetup({
		error:function(x,e){
			if(x.status==0){
				//alert('You are offline!!\n Please Check Your Network.');
			}else if(x.status==404){
				alert('Requested URL not found.');
			}else if(x.status==500){
				alert('Internel Server Error.');
			}else if(e=='parsererror'){
				alert('Error.\nParsing JSON Request failed.');
			}else if(e=='timeout'){
				alert('Request Time out.');
			}else {
				alert('Unknow Error.\n'+x.responseText);
			}
		}
	});
});