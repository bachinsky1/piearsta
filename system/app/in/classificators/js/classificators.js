
function updateModule() {
	
	moduleTable.updateModule();	
}

function moduleEnable(id, value) {
	ajaxRequest(moduleTable.getRequestUrl() + "enable/", "value=" + value + "&id=" + id, updateModule);
}

function moduleEnableSelected(module, value) {

	var returnArray = new Array();
	
	var checkboxes = document.getElementsByTagName("input");
	for (var i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i].type == "checkbox" && checkboxes[i].name == module + "Box" && checkboxes[i].checked == true) {
			returnArray.push(checkboxes[i].id.substr(module.length + 1));
		}
	}

	if (returnArray.length > 0) {
		ajaxRequest(moduleTable.getRequestUrl() + "enable/", "value=" + value + "&id=" + encodeURIComponent(JSON.stringify(returnArray)), updateModule);
	}	
	
}

function moduleDelete(id) {
	if (ruSure(langStrings.getMsg('rusure_delete'))) {
		ajaxRequest(moduleTable.getRequestUrl() + "delete/", "id=" + id, updateModule);
	}
}

function moduleSort(id, sort) {
    ajaxRequest(moduleTable.getRequestUrl() + 'sort/' + id + '/', 'sort=' + sort, updateModule);
}

function moduleDeleteSelected(module) {
	
	returnArray = new Array();
	
	checkboxes = document.getElementsByTagName("input");
	for (i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i].type == "checkbox" && checkboxes[i].name == module + "Box" && checkboxes[i].checked == true) {
			returnArray.push(checkboxes[i].id.substr(module.length + 1));
		}
	}
	if (returnArray.length > 0) {
		if (ruSure(langStrings.getMsg('rusure_delete'))) {
			ajaxRequest(moduleTable.getRequestUrl() + "delete/", "id=" + encodeURIComponent(JSON.stringify(returnArray)), updateModule);
		}
	}	
	
}

function moduleEdit(id) {
	var idUrl = id ? id + "/" : "";
	window.location.href = moduleTable.getRequestUrl() + "edit/" + idUrl;
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
			$(this).css('background', '#f7b5b5');
		}
	});
	
	if (err == false) {
		saveData();
	}
}

function saveData() {
	
	var returnArray = {};
	var langValues = {};
	 
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
	
	if (saveType == "apply") {
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, "value=" + encodeURIComponent(JSON.stringify(returnArray)) + "&langValues=" + encodeURIComponent(JSON.stringify(langValues)), function () {});		
	}
	if (saveType == "save") {
		
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, "value=" + encodeURIComponent(JSON.stringify(returnArray)) + "&langValues=" + encodeURIComponent(JSON.stringify(langValues)), function () {
			
			window.location.href = moduleTable.getRequestUrl();
		});
	}
}

function changeFilter() {
	
	moduleTable.additionalParms = "&filterType=" + $('#filterType').val();
	moduleTable.from = 0;
	
	updateModule();
}

function clearFilter() {
	
	$('#filterType').val('');
	moduleTable.additionalParms = "";
	moduleTable.from = 0;
	
	updateModule();
}
