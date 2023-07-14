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
		if (checkboxes[i].type == "checkbox" && checkboxes[i].name == module + "Box" && checkboxes[i].checked == true) {
			returnArray.push(checkboxes[i].id.substr(module.length + 1));
		}
	}

	if (returnArray.length > 0) {
		ajaxRequest(moduleTable.getRequestUrl() + "enable/", "value=" + value + "&id=" + encodeURIComponent(JSON.stringify(returnArray)), updateModule);
	}	
	
}

function moduleDelete(id) {
	if (ruSure(langStrings.getMsg('rusure_delete','Are yor sure want to delete this ?'))) {
		ajaxRequest(moduleTable.getRequestUrl() + "delete/", "id=" + id, updateModule);
	}
}

function moduleDeleteSelected(module) {
	
	var returnArray = [];
	
	var checkboxes = document.getElementsByTagName("input");
	for (var i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i].type == "checkbox" && checkboxes[i].name == module + "Box" && checkboxes[i].checked == true) {
			returnArray.push(checkboxes[i].id.substr(module.length + 1));
		}
	}
	if (returnArray.length > 0) {
		if (ruSure(langStrings.getMsg('rusure_delete','Are yor sure want to delete this ?'))) {
			ajaxRequest(moduleTable.getRequestUrl() + "delete/", "id=" + encodeURIComponent(JSON.stringify(returnArray)), updateModule);
		}
	}	
	
}

function moduleEdit(id) {
	var idUrl = id ? id + "/" : "";
	window.location.href = moduleTable.getRequestUrl() + "edit/" + idUrl;	
	return false;
}

var saveType;

function checkFields(type) {

	var result = true;
	
	$('.required').each(function(n, element) {
		if ($(this).val() == '') {
			$(this).css('background', '#f7b5b5');
			$(this).focus();
			result = false;
		} else {
			$(this).css('background', '#ffffff');
		}
	});

	if (result) {
		saveType = type;
		
		saveData();
	}	
}

function saveData() {
	
	var returnArray = {},
		returnMsg = {};

	$('btn *').removeAttr('onclick'); 
	
	$('.simple').each(function(n, element) {
		
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
		

		
	});
	
	$('.message').each(function(n, element) {
		
		if ($(element).val()) {
			returnMsg[$(element).attr('id')] = $(element).val();
		}
		
	});
	
	var sendData = {};
	sendData['value'] = returnArray;
	sendData['message'] = returnMsg;

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

function addMessage() {

	var returnMsg = {};

	$('.message').each(function(n, element) {
		
		if ($(element).val()) {
			returnMsg[$(element).attr('id')] = $(element).val();
		}
		
	});
	
	var sendData = {};
	sendData['message'] = returnMsg;

	var idUrl = $('#id').val() ? $('#id').val() + "/" : "";
	ajaxRequest(moduleTable.getRequestUrl() + "saveMessage/" + idUrl, sendData, function (response) {
		$('#message_block').html(response.html);
	});
}

function changeFilter() {
	$('.filter').each(function(n, element) {
		moduleTable.additionalParms += "&" + $(element).attr('id') + "=" + $(element).val();
		
	});
	
	moduleTable.from = 0;
	
	updateModule();
}

function clearFilter() {
	$('.filter').each(function(n, element) {
		$(element).val('');
	});
	
	moduleTable.additionalParms = "&filter=clear";
	moduleTable.from = 0;
	
	updateModule();
}