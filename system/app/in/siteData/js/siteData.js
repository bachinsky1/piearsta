$(document).ready(function(){
	
	var tabindex = 1;
	
	$('#modulePath :input,select').each(function() {
		if (this.type != "hidden" && this.id != "cmsLang") {
			var $input = $(this);
			$input.attr("tabindex", tabindex);
			tabindex++;
		}
	});
});

function checkFields(blockId) {
	var error = false,
		message = '';
	
	$('#' + blockId + ' .error').empty();
	
	$('#' + blockId + ' .required').each(function(n, element) {
		if($(this).val() == '') {
			$('#' + blockId + ' .error').show();
			$('#' + blockId + ' .error').append('<b>' + ($(this).attr('title') != "" ? $(this).attr('title') : $(this).attr('name')) + '</b> ' + langStrings.getMsg("empty_field",'Cannot be empty!') + '<br />');
			$(this).css('background', '#f7b5b5');
			$(this).focus();
			error = true;
		} else {
			$(this).css('background', '#ffffff');
		}
	});

	return error;
}

function saveValues(type) {
	
	if (type === 'apply') {
		var li = $( "#tabs ul > li" ).get($( "#tabs" ).tabs( "option", "selected" )),
			blockId = $(li).find('a').attr('rel');
	} else {
		blockId = 'modulePath';
	}
	
	var values = {};

	$('#' + blockId).find('input,select,textarea').each(function() {
		
		if ($(this).attr("id") != '' && $(this).attr("data") != 'block') {
			
			if ($(this).attr("rel")) {
				
				if ($(this).attr("data")) {
					
					if (typeof(values[$(this).attr("name")]) == 'undefined') {
						values[$(this).attr("name")] = {};
					}
					
					if (typeof(values[$(this).attr("name")][$(this).attr("rel")]) == 'undefined') {
						values[$(this).attr("name")][$(this).attr("rel")] = {};
					}

					if (this.type === "checkbox" || this.type === "radio") {
						if ($(this).attr("checked")) {
							values[$(this).attr("name")][$(this).attr("rel")][$(this).attr("data")] = $(this).val();
						}
						else {
							values[$(this).attr("name")][$(this).attr("rel")][$(this).attr("data")] = 0;
						}	
					}
					else {
						values[$(this).attr("name")][$(this).attr("rel")][$(this).attr("data")] = $(this).val();
					}
					
				} else {
					
					if (typeof(values[$(this).attr("name")]) == 'undefined') {
						values[$(this).attr("name")] = {};
					}

					if (this.type === "checkbox" || this.type === "radio") {
						if ($(this).attr("checked")) {
							values[$(this).attr("name")][$(this).attr("rel")] = $(this).val();
						}
						else {
							values[$(this).attr("name")][$(this).attr("rel")] = 0;
						}	
					}
					else {
						values[$(this).attr("name")][$(this).attr("rel")] = $(this).val();
					}
				}	
				
			} else {
				if (this.type == "checkbox" || this.type == "radio" ) {
					if ($(this).attr("checked")) {
						values[this.id] = $(this).val();
					}
					else {
						values[this.id] = 0;
					}	
				}
				else {
					values[this.id] = $(this).val();
				}
			}
	
		}		

	});
	
	var sendData = {};
	sendData['values'] = values;
	
	if(checkFields(blockId) == false) {
		ajaxRequest(moduleTable.getRequestUrl() + "save/", sendData, function () {});
		$('.error').empty();
		$('.error').css('display', 'none');
	}
}
