
function updateModule() {
	moduleTable.updateModule();
}

function moduleEnable(id, value) {
	ajaxRequest(moduleTable.getRequestUrl() + "enable/", "value=" + value + "&mId=" + id, updateModule);
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
		ajaxRequest(moduleTable.getRequestUrl() + "enable/", "value=" + value + "&mId=" + encodeURIComponent(JSON.stringify(returnArray)), updateModule);
	}	
	
}

function moduleDelete(id) {
	if (ruSure(langStrings.getMsg('rusure_delete','Are yor sure want to delete this ?'))) {
		ajaxRequest(moduleTable.getRequestUrl() + "delete/", "mId=" + id, updateModule);
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
		if (ruSure(langStrings.getMsg('rusure_delete','Are yor sure want to delete this ?'))) {
			ajaxRequest(moduleTable.getRequestUrl() + "delete/", "mId=" + encodeURIComponent(JSON.stringify(returnArray)), updateModule);
		}
	}	
	
}

function changeFilter() {
	
	if ($('#notTranslated').val() != "") {
		$('#filterSearch').val('');
	}	
	
	moduleTable.additionalParms = "&filterModule=" + $('#filterModule').val() + "&filterSearch=" + $('#filterSearch').val() + "&notTranslated=" + $('#notTranslated').val();
	moduleTable.from = 0;
	
	updateModule();
}

function clearFilter() {
	
	$('#filterModule').val('');
	$('#filterSearch').val('');
	$('#notTranslated').val('');
	moduleTable.additionalParms = "";
	moduleTable.from = 0;
	
	updateModule();
}

function moduleEdit(id) {

	$('table tr').removeClass('open-row');
	
	if ($('#editForma').lenght != 0) {
		$('#edit_' + $('#id').val()).html('');
	}	
	
	if (id) {
		$('#tr_' + id).addClass('open-row');
		$('#firstTd_' + id).addClass('first-td');
		$('#lastTd_' + id).addClass('last-td');
	}	
	
	var idUrl = id ? id + "/" : "";
	ajaxRequest(moduleTable.getRequestUrl() + "edit/" + idUrl, "", getModuleEdit);
}

function getModuleEdit(data) {
	if (data) {
		$('#tr_' + data["id"]).addClass('open-row');
		$('#firstTd_' + data["id"]).addClass('first-td');
		$('#lastTd_' + data["id"]).addClass('last-td');
		
		$('#edit_' + data["id"]).html(data["html"]);
	}	
}

var saveType;

function checkFields(type) {
	
	saveType = type;
	
	if ($('#name').val() == '') {
		$('#errorBlock').html(langStrings.getMsg('select_correct_name','Please enter unique name!'));
		$('#errorBlock').show(); 
		return false;
	}
	
	idUrl = $('#id').val() ? $('#id').val() + "/" : "";
	ajaxRequest(moduleTable.getRequestUrl() + "checkname/" + idUrl, "value=" + $('#name').val(), checkName);	
}

function checkName(data) {

	if (data === true) {
		saveData();
	}
	else {
		$('#errorBlock').html(langStrings.getMsg('select_correct_name','Please enter unique name!'));
		$('#errorBlock').show(); 
		return false;
	}
}

function saveData() {
	
	var returnArray = {},
		langValues = {};
	
	$('#editForma :input').each(function(n, element) {
		if($(element).attr('type') !== 'button' && $(element).attr('type') !== 'hidden') {
			if($(element).attr('type') === 'checkbox') {
				if ($(element).is(':checked')) {
					returnArray[$(element).attr('id')] = $(element).val();
				}
				else {
					returnArray[$(element).attr('id')] = 0;
				}	
			}
			else {
				if (String($(element).attr('id')).search("value_")) {
					returnArray[$(element).attr('id')] = $(element).val();
				}
				else {
					langValues[$(element).attr('id')] = $(element).val();
				}	
			}	
			
		}
		
	});
	
	var sendData = {};
	sendData['value'] = returnArray;
	sendData['langValues'] = langValues;

	var idUrl;
	
	if (saveType === "apply") {
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, function () {});		
	}
	if (saveType === "save") {
		
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		$('#edit_' + $('#id').val()).html('');
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, function (data) {
			updateModule();
			setTimeout('$(\'#tr_' + data.id + '\').addClass(\'open-row\')', 500);
		});
	}
	if (saveType === "next") {
		
		var currentId = $('#id').val();
		
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, function (data) {
			if (data.id != '') {
				var nextId = $('#edit_' + data.id).next().attr('id').split("_");
				updateModule();
				if (nextId[1] != '') {
					setTimeout('moduleEdit(' + nextId[1] + ')', 200);
				}
			} else {
				updateModule();
			}
		});
	}
}

function showHideCountryBlock() {

		
	if ($('#type').val() == "l") {
		$('#typeC').hide();
		$('#typeL').show();
	}
	if ($('#type').val() == "c") {
		$('#typeL').hide();
		$('#typeC').show();
	}
}
