
function updateModule() {
	moduleTable.updateModule();
}

function moduleEnable(id, value) {
	ajaxRequest(moduleTable.getRequestUrl() + "enable/", "value=" + value + "&id=" + id, updateModule);
}

function moduleDelete(id) {
	if (ruSure(langStrings.getMsg('rusure_delete','Are yor sure want to delete this ?'))) {
		ajaxRequest(moduleTable.getRequestUrl() + "delete/", "id=" + id, updateModule);
	}
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
	
	var idUrl = $('#id').val() ? $('#id').val() + "/" : "";
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
					var info = $(element).attr('id').split("_");
					langValues[info[1]] = $(element).val();
				}
			}	
			
		}
		
	});
	
	if (saveType === "save") {
		
		var currentId = $('#id').val();
		
		var idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		$('#edit_' + $('#id').val()).html('');
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, "value=" + encodeURIComponent(JSON.stringify(returnArray)) + "&langValues=" + encodeURIComponent(JSON.stringify(langValues)), function (data) {
			updateModule();
			setTimeout('$(\'#tr_' + data.id + '\').addClass(\'open-row\')', 500);
		});
	}
}
