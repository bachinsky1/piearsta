function updateModule() {
    moduleTable.updateModule();
}

function moduleDelete(id) {
    if (ruSure(langStrings.getMsg('rusure_delete'))) {
        ajaxRequest(moduleTable.getRequestUrl() + "delete/", "id=" + id, updateModule);
    }
}

function moduleEnable(id, value) {
	ajaxRequest(moduleTable.getRequestUrl() + "enable/", "value=" + value + "&id=" + id, updateModule);
}

function changeFilter() {
    moduleTable.additionalParms = "&filter=search&filterLanguage=" + $('#filterLanguage').val() + "&filterEmail=" + $('#filterEmail').val();
    moduleTable.from = 0;
    updateModule();
}
function clearFilter() {
    $('#filterLanguage').val('');
    $('#filterEmail').val('');

    $('#errorBlock').hide();

    moduleTable.from = 0;
    moduleTable.additionalParms = "&filter=clear";

    updateModule();
}
