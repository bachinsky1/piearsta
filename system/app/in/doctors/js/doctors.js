var doctorFilters = ['filterName', 'filterSurname', 'filterPhone', 'filterEmail', 'filterClinics', 'filterType'];
var requiredFields;
var saveType;

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
    $(doctorFilters).each(function (index, filter) {
	if ($('#' + filter).val() !== '') {
	    moduleTable.additionalParms += '&' + filter + '=' + $('#' + filter).val();
	}
    });
    moduleTable.updateModule();
}

function clearFilter() {
    $(doctorFilters).each(function (index, filter) {
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
		clinicsArray = [],
		specialitiesArray = [],
		servicesArray = [];

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

    var clinicsValues = $(':input[name="clinics[]"]').map(function () {
    	return this.value;
    }).get();
    $(clinicsValues).each(function (i, value) {
		if (value && $.inArray(value, clinicsArray) === -1) {
		    clinicsArray.push(value);
		}
    });

    var specialitiesValues = $(':input[name="specialities[]"]').map(function () {
    	return this.value;
    }).get();
    $(specialitiesValues).each(function (i, value) {
		if (value && $.inArray(value, specialitiesArray) === -1) {
		    specialitiesArray.push(value);
		}
    });

    var servicesValues = $(':input[name="services[]"]').map(function () {
    	return this.value;
    }).get();
    $(servicesValues).each(function (i, value) {
		if (value && $.inArray(value, servicesArray) === -1) {
		    servicesArray.push(value);
		}
    });

    var sendData = {};
    sendData['value'] = returnArray;
    sendData['clinics'] = clinicsArray;
    sendData['specialities'] = specialitiesArray;
    sendData['services'] = servicesArray;
    sendData['langValues'] =  langValues;

    if (saveType === "apply") {
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, function () {
		});
    }
    if (saveType === "save") {
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
			var opt = $('<option>')
				.attr('value', option[keyName])
				.html(option[valueName]);
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