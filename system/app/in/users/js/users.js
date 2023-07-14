
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
	
	if ($('#password').val() != '' && $('#password').val() != $('#password2').val()) {
		$('#errorBlock').html(langStrings.getMsg('user_password_error','Retype correct password!'));
		$('#errorBlock').show(); 
		return false;
	}
	
	if ($('#username').val() == '') {
		$('#errorBlock').html(langStrings.getMsg('select_correct_name','Please enter unique name!'));
		$('#errorBlock').show(); 
		return false;
	}
	
	var idUrl = $('#id').val() ? $('#id').val() + "/" : "";
	ajaxRequest(moduleTable.getRequestUrl() + "checkname/" + idUrl, "value=" + $('#username').val(), checkName);	
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
		rolesArray = {};
	
	$('#editForma :input').each(function(n, element) {
		if($(element).attr('type') !== 'button' && $(element).attr('type') !== 'hidden') {
			
			if ($(element).hasClass('roles')) {

				if ($(element).is(':checked')) {
					rolesArray[$(element).attr('id')] = $(element).val();
				}
				else {
					rolesArray[$(element).attr('id')] = 0;
				}
			} else {
				if($(element).attr('type') === 'checkbox') {
					if ($(element).is(':checked')) {
						returnArray[$(element).attr('id')] = $(element).val();
					}
					else {
						returnArray[$(element).attr('id')] = 0;
					}	
				}
				else {
					returnArray[$(element).attr('id')] = $(element).val();	
				}
			}
	
		}
		
	});
	
	if (saveType === "save") {
		
		var currentId = $('#id').val();
		
		var idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		$('#edit_' + $('#id').val()).html('');
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, "value=" + encodeURIComponent(JSON.stringify(returnArray)) + "&roles=" + encodeURIComponent(JSON.stringify(rolesArray)), function (data) {
			updateModule();
			setTimeout('$(\'#tr_' + data.id + '\').addClass(\'open-row\')', 500);
		});
	}
}

function checkForAdmin() {
	if ($('#admin').is(':checked')) {
		$('#userModules').hide();
		$('#user_group').hide();
	} else {
		$('#userModules').show();
		$('#user_group').show();
	}	
}

function getRolesGroup(){
	var group_id = $('#group_id').val();
	$('#userModules input:checkbox').attr('checked',false);
	if(group_id != ''){
		ajaxRequest(moduleTable.getRequestUrl() + "getGroup/", "group_id=" + group_id, function (data) {
			$.each(data, function() {
				$('#'+this).attr("checked", "checked");
			});
		});
	}	
}
