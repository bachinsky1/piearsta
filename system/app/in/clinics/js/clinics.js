var clinicsFilters = ['filterName', 'filterRegNr', 'filterPhone', 'filterEmail', 'filterType'];

function updateModule() {
    moduleTable.updateModule();
}

function moduleView(id) {
    window.location.href = moduleTable.getRequestUrl() + 'view/' + (id && parseInt(id) ? parseInt(id) : 0) + '/';
}

function moduleDelete(id) {
    if (ruSure(langStrings.getMsg('rusure_delete'))) {
	ajaxRequest(moduleTable.getRequestUrl() + "delete/", "id=" + id, updateModule);
    }
}

function changeFilter() {
    moduleTable.additionalParms = '';
    $(clinicsFilters).each(function (index, filter) {
	if ($('#' + filter).val() !== '') {
	    moduleTable.additionalParms += '&' + filter + '=' + $('#' + filter).val();
	}
    });
    moduleTable.updateModule();
}

function clearFilter() {
    $(clinicsFilters).each(function (index, filter) {
	$('#' + filter).val('');
    });

    moduleTable.additionalParms = '&clear=true';
    moduleTable.updateModule();
}

function moduleEdit(id) {
    var idUrl = id ? id + "/" : "";
    window.location.href = moduleTable.getRequestUrl() + "edit/" + idUrl;
    return false;
}

var saveType;

function checkFields(type) {
	var err = false;
	saveType = type;
	$('.error-msg').hide();
	$('.required').css('background', '#ffffff');
	
	$('.required').each(function(){
		if ($(this).val() == "") {
            err = true;
			$(this).closest('td').find('.error-msg').show();
			$(this).css('background', '#f7b5b5').focus();
		}
	});
	
	if (err == false) {
		saveData();
	}
}

function saveData() {

    var returnArray = {},
		langValues = {},
		contactsData = [],
		doctorsArray = [];
    
    var i = 0;
	$('.contacts').each(function(n, element) {
		contactsData[i] = {};
		$(element).find('input').each(function() {
			if ($(this).attr("rel")) {
				
				if (typeof(contactsData[i][$(this).attr("name")]) == 'undefined') {
					contactsData[i][$(this).attr("name")] = {};
				}
				
				contactsData[i][$(this).attr("name")][$(this).attr("rel")] = $(this).val();
				
			} else {
				contactsData[i][$(this).attr('name')] = $(this).val();
			}
		
		});	
		
		i++;
	});	

    $('.simple').each(function(n, element) {
		
		if ($(this).attr("id") != '') {
			
			if ($(this).attr("rel")) {
				
				if (typeof(langValues[$(this).attr("name")]) == 'undefined') {
					langValues[$(this).attr("name")] = {};
				}
				
				if (this.type == "checkbox") {
					if ($(this).attr("checked")) {
						langValues[$(this).attr("name")][$(this).attr("rel")] = $(this).val();
					}
					else {
						langValues[$(this).attr("name")][$(this).attr("rel")] = 0;
					}	
				}
				else {
					langValues[$(this).attr("name")][$(this).attr("rel")] = $(this).val();
				}
				
			} else {
				if($(element).attr('type') == 'checkbox') {
					if ($(element).is(':checked')) {
						returnArray[$(element).attr('id')] = $(element).val();
					}
					else {
						returnArray[$(element).attr('id')] = 0;
					}	
				} else {
					returnArray[$(element).attr('id')] = $(element).val();
				}
			}	
		}
	
	});

    var doctorsValues = $(':input[name="doctors[]"]').map(function () {
    	return this.value;
    }).get();
    $(doctorsValues).each(function (i, value) {
		if (value && $.inArray(value, doctorsArray) === -1) {
		    doctorsArray.push(value);
		}
    });

    var sendData = {};
    sendData['value'] = returnArray;
    sendData['doctors'] = doctorsArray;
    sendData['langValues'] =  langValues;
    sendData['contacts'] =  contactsData;
    
    if (saveType == "apply") {
    	idUrl = $('#id').val() ? $('#id').val() + "/" : "";
    	ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, function () {
    	});
    }
    
    if (saveType == "save") {
    	idUrl = $('#id').val() ? $('#id').val() + "/" : "";
    	ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, function () {
    		window.location.href = moduleTable.getRequestUrl();
    	});
    }
}

function buildSelect(target, name, object, keyName, valueName, selected) {
    selected = selected || false;
    keyName = keyName || 'c_id';
    valueName = valueName || 'title';
    object = object || [];

    if (name && target && target.length && object.length) {
	var wrapper = $('<div>')
		.addClass('custom_select');

	var select = $('<select>')
		.attr('name', name)
		.appendTo(wrapper);

	$('<option>')
		.attr('value', '')
		.html('Select')
		.appendTo(select);
	$(object).each(function (i, option) {
	    var htmlContent = '';
	    if (typeof(valueName) === 'string') {
		htmlContent = option[valueName];
	    } else {
		$.each(valueName, function (i, name) {
		    if (htmlContent !== '') htmlContent += ' ';
		    htmlContent += option[name];
		});
	    }
	    var opt = $('<option>')
		    .attr('value', option[keyName])
		    .html(htmlContent);
	    if (selected && option[keyName] == selected) {
		opt.attr('selected', 'selected');
	    }
	    opt.appendTo(select);
	});

	var remove = $('<button>')
		.addClass('rm-select')
		.html('-')
		.appendTo(wrapper);

	wrapper.appendTo(target);
    }
}

function removeSelect(target) {
    if (target && target.length) {
	$(target).parents('.custom_select').remove();
    }
}