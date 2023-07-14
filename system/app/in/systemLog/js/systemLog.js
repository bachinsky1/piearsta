
function updateModule() {
	moduleTable.updateModule();
}

function moduleEmpty() {
	if (ruSure(langStrings.getMsg('sys_log_rusure_empty','Are you sure want empty system log table?'))) {
		ajaxRequest(moduleTable.getRequestUrl() + "empty/", "", updateModule);
	}
}