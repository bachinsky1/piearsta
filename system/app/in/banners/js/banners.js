function updateModule() {
    moduleTable.updateModule();
}
function moduleEdit(id) {
    window.location.href = moduleTable.getRequestUrl() + 'edit/' + (id && parseInt(id) ? parseInt(id) : 0) + '/';
}

function moduleDelete(id) {
    if(ruSure(langStrings.getMsg('rusure_delete','Are yor sure want to delete this ?'))) {
        ajaxRequest(moduleTable.getRequestUrl() + 'delete/' + id + '/', updateModule);
    }
}
function moduleEnable(id, value) {
	ajaxRequest(moduleTable.getRequestUrl() + 'enable/', 'enabled=' + value + '&id=' + id, updateModule);
}

function changeFilter() {
	moduleTable.additionalParms = "&filterLang=" + $('#lang').val();
	moduleTable.updateModule();
}

function clearFilter() {
	$("#lang").val("0");
	moduleTable.additionalParms = "&clear=true";
	moduleTable.updateModule();
}

function scrollToTop() {
    $(this).scrollTop($('div.content').position().top);
}
function checkFields(type) {
    $('#errorBlock').hide();
    $('#okBlock').hide();
    
    $(".error").removeClass("error");
    
    var errorStr = "";
    
    var $errors = 0;
    var $int_regex = /^\d+$/;
    
    if(jQuery.trim($('#lang').val()) == 0) {
        errorStr = errorStr + "<div class='err_lang'>" + langStrings.getMsg('m_choose_lang','Please choose language') + "</div>";
        $('#lang').addClass("error");
        $errors++;
    }
    
    if(jQuery.trim($('#slot').val()) == 0) {
        errorStr = errorStr + "<div class='err_lang'>" + langStrings.getMsg('m_choose_slot','Please choose slot') + "</div>";
        $('#slot').addClass("error");
        $errors++;
    }
   
    if(!$('#image').size()) {
        errorStr = errorStr + "<div class='err_image'>" + langStrings.getMsg('m_choose_image','Please upload image') + "</div>";
        $("#imageDiv").parent().addClass("error");
        $errors++;
    }

    
    if($errors) {
        $('#errorBlock').html(errorStr);
        $('#errorBlock').show();
        scrollToTop();
        return false;
    } else {
        save(type);
        return true;
    }
}
function save(type) {
    var returnData = new Object();
    
    returnData['lang'] = jQuery.trim($('#lang').val());
    returnData['title'] = jQuery.trim($('#title').val());
    returnData['slot'] = jQuery.trim($('#slot').val());
    returnData['url'] = jQuery.trim($('#url').val());
    returnData['url_id'] = jQuery.trim($('#url_id').val());
    returnData['doc_id'] = jQuery.trim($('#doc_id').val());
    returnData['target'] = jQuery.trim($('#target').val());
    returnData['image'] = jQuery.trim($('#image').val());
    returnData['alt'] = jQuery.trim($('#alt').val());

    $.post(
        moduleTable.getRequestUrl() + 'save/' + $id + '/',
        'values=' + encodeURIComponent(JSON.stringify(returnData)),
        function(data) {
            var returndata = JSON.parse(data);
            if(returndata != null && returndata != undefined && returndata.id != null && returndata.id != undefined) {
                if(type == 'apply') {
                    $('#okBlock').show();
                    scrollToTop();
                } else {
                    window.location.href = moduleTable.getRequestUrl();
                }
                
                return true;
            }

            return false;
        }
    );
}

$(document).ready(function(){
    $("#lang").change(function(){
        if($(this).val()){
            $(this).removeClass("error");
            if($(".err_lang").length){
                $(".err_lang").remove();
                if($("#errorBlock div").length){
                    $("#").hide();
                }
            }
        }
    });
});