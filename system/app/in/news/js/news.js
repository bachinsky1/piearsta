
function updateModule() {
	
	if ($('#content_id').val() != "") {
		$('.table-nav').show();
		moduleTable.additionalParms = "&content_id=" + $('#content_id').val();
		moduleTable.updateModule();
	}	
}

function siteMapReturn(NODE) {
	
	var id = $(NODE).attr("id").replace("node", "");
	
	$('#' + idField).val(id);
	$('#' + titleField).val($(NODE).attr("title"));
	moduleTable.from = 0;
	updateModule();
}

function moduleSort(id, value){
	ajaxRequest(moduleTable.getRequestUrl() + "sort/", "value=" + value + "&id=" + id, updateModule);
}

function moduleEnable(id, value) {
	ajaxRequest(moduleTable.getRequestUrl() + "enable/", "value=" + value + "&id=" + id, updateModule);
}

function moduleEnableSelected(module, value) {

	var returnArray = [];
	
	var checkboxes = document.getElementsByTagName("input");
	for (var i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i].type === "checkbox" && checkboxes[i].name === module + "Box" && checkboxes[i].checked === true) {
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
	if ($('#content_id').val() != "") {
		var idUrl = id ? id + "/" : "?content_id=" + $('#content_id').val();
		window.location.href = moduleTable.getRequestUrl() + "edit/" + idUrl;	
	}	
	return false;
}

var saveType;

function checkFields(type) {
	var result = true;
	
	if ($('#title').val() == '') {
		$('#title').css('background', '#f7b5b5');
		$('#title').focus();
		result = false;
	} else {
		$('#title').css('background', '#ffffff');
	}
	

	$('.files-block input[type=text]').each(function(n, element) {
		if($(this).val() == '') {
			$(this).css('background', '#f7b5b5');
			$(this).focus();
			result = false;
		} else {
			$(this).css('background', '#ffffff');
		}
	});

	$('.links-block input[type=text]').each(function(n, element) {
		if(($(this).val() == '') || ($(this).val() == 'Link url')) {
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
		returnFiles = [],
		returnLinks = [];

	$('btn *').removeAttr('onclick'); 
	
	$('.simple').each(function(n, element) {

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
	sendData['value'] = returnArray;
	sendData['files'] = returnFiles;
	sendData['links'] = returnLinks;

	if (saveType === "apply") {
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, function () {});		
	}
	if (saveType === "save") {
		
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, function () {
			
			window.location.href = moduleTable.getRequestUrl() + '#content_id:' + $("#content_id").val() + '/';
		});
	}
}
