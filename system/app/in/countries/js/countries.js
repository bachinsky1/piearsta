
function updateModule() {
	moduleTable.updateModule();
}

function moduleDelete(id) {
	if (ruSure(langStrings.getMsg('rusure_delete','Are yor sure want to delete this content?'))) {
		ajaxRequest(moduleTable.getRequestUrl() + "delete/", "id=" + id, updateModule);
	}
}

function moduleEdit(id) {
	
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
		$('#edit_' + data["id"]).html(data['html']);
	}
}

var saveType;

function checkFields(type) {
	
	saveType = type;
	
	saveData();
}

function saveData() {
	
	var returnArray = {},
		domains = {},
		languages = {};
	
	$('#editForma :input').each(function(n, element) {
		if($(element).attr('type') !== 'button' && $(element).attr('type') !== 'hidden' && $(element).attr('type') !== 'file') {
			if ($(element).attr('id').indexOf("domain_") > -1) {
				var keys = $(element).attr('id').split('_').reverse();
				var id = keys[0];
				domains[id] = $(element).val();

				var defaultDomainId = $('input[name=default_domain]:checked').val();
				
				if(defaultDomainId != null && defaultDomainId != undefined){
					domains['default_domain'] = defaultDomainId;
				}
				
				
			}
			else if ($(element).attr('id').indexOf("langs_") > -1) {
				
				var lang_id = $(element).attr('id').replace("langs_", "");

				if ($('#langs_' + lang_id).is(':checked')) {

					languages[lang_id] = {};
					
					languages[lang_id]['enable'] = true;
					languages[lang_id]['main_id'] = $('#main_id_' + lang_id).val();
					languages[lang_id]['default'] = ($('#default_' + lang_id).is(':checked') ? true : false);
				}			
			}			
		}
		
	});
	
	returnArray["title"] = $('#title').val();
	returnArray["google_analytics"] = $('#google_analytics').val();
	returnArray["webmasters"] = $('#webmasters').val();

	if (saveType === "apply") {
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, "value=" + encodeURIComponent(JSON.stringify(returnArray)) + "&domains=" + encodeURIComponent(JSON.stringify(domains)) + "&languages=" + encodeURIComponent(JSON.stringify(languages)), function () {});		
	}
	if (saveType === "save") {
		
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		$('#edit_' + $('#id').val()).html('');
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, "value=" + encodeURIComponent(JSON.stringify(returnArray)) + "&domains=" + encodeURIComponent(JSON.stringify(domains)) + "&languages=" + encodeURIComponent(JSON.stringify(languages)), updateModule);
	}
	if (saveType === "next") {
		
		idUrl = $('#id').val() ? $('#id').val() + "/" : "";
		ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, "value=" + encodeURIComponent(JSON.stringify(returnArray)) + "&domains=" + encodeURIComponent(JSON.stringify(domains)) + "&languages=" + encodeURIComponent(JSON.stringify(languages)), nextEdit);
	} 
}

function nextEdit() {
	idUrl = $('#id').val() ? $('#id').val() + "/" : "";
	updateModule();
	ajaxRequest(moduleTable.getRequestUrl() + "nextedit/" + idUrl, moduleTable.getRequestParameters_(), getModuleEdit);
}

function moduleSort(id, value){
	ajaxRequest(moduleTable.getRequestUrl() + "sort/", "value=" + value + "&id=" + id, updateModule);
}
