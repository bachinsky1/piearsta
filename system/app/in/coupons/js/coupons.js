
function updateModule() {
	
	moduleTable.updateModule();	
}

function moduleEnable(id, value) {
	ajaxRequest(moduleTable.getRequestUrl() + "enable/", "value=" + value + "&id=" + id, updateModule);
}

function moduleEnableSelected(module, value) {

	var returnArray = [];
	
	var checkboxes = document.getElementsByTagName("input");
	for (var i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i].type === "checkbox" && checkboxes[i].name === module + "Box" && checkboxes[i].checked == true) {
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
	
	var returnArray = {},
		langValues = {},
		returnFiles = [],
		returnLinks = [];
	 
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
	
	var i = 0;
	$('.files-block').each(function(n, element) {
		returnFiles[i] = {};
		$(element).find('input, select').each(function() {
			if ($(this).attr('id')) {
				var $id = $(this).attr('id').toString().substr(0, $(this).attr('id').toString().indexOf('_'));
				if ($id != '') {
					returnFiles[i][$id] = $(this).val();
				}
			}
		
		});	
		
		i++;
	});	

	i = 0;
	$('.links-block').each(function(n, element) {
		returnLinks[i] = {};
		$(element).find('input, select').each(function() {
			if ($(this).attr('id')) {
				$id = $(this).attr('id').toString().substr(0, $(this).attr('id').toString().indexOf('_'));
				if ($id != '') {
					returnLinks[i][$id] = $(this).val();
				}
			}
			
		
		});	
		
		i++;
	});	
	
	var sendData = {};
	sendData['value'] =  returnArray;
	sendData['langValues'] =  langValues;
	sendData['files'] =  returnFiles;
	sendData['links'] =  returnLinks;
	
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
