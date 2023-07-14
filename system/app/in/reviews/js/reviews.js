
function updateModule() {
	
	moduleTable.updateModule();	
}

function moduleDelete(id) {
	if (ruSure(langStrings.getMsg('rusure_delete'))) {
		ajaxRequest(moduleTable.getRequestUrl() + "delete/", "id=" + id, updateModule);
	}
}

function moduleDeleteSelected(module) {
	
	var returnArray = new Array();
	
	var checkboxes = document.getElementsByTagName("input");
	for (var i = 0; i < checkboxes.length; i++) {
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
	
	var returnArray = {},
		langValues = {};
	 
	$('.simple').each(function(n, element) {
		
		if ($(this).attr("id") != '') {
			
			if ($(this).attr("rel")) {
				
				if (typeof(langValues[$(this).attr("name")]) == 'undefined') {
					langValues[$(this).attr("name")] = {};
				}
				
				if (this.type === "checkbox") {
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
				if($(element).attr('type') === 'checkbox') {
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
	
	var sendData = {};
	sendData['value'] =  returnArray;
	sendData['langValues'] =  langValues;

	var idUrl;
	
	if (saveType === "apply") {
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, function () {});		
	}
	if (saveType === "save") {
		
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, function () {
			
			window.location.href = moduleTable.getRequestUrl();
		});
	}
}
