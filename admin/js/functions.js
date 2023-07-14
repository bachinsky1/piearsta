
var anchors = {};

$(document).ready(function(){
	
	// Counts and limits text symbols in form fields
    $(".SymbCounter").each(function(){
        var max = $(this).children(".SymbMax").text();
        var inputId = $(this).prev().attr("id");
        $(this).children(".SymbCount").html($('[id='+inputId+']').val().length);
        $('[id='+inputId+']').keyup(function(){
            if($('[id='+inputId+']').val().length >= max){
                $('[id='+inputId+']').val($('[id='+inputId+']').val().substr(0, max));
            }
            $("."+inputId).children(".SymbCount").html($('[id='+inputId+']').val().length);
        });
    });
	
	getAnchors();
	
    $('select#limit_toTop, select#limit_toBottom').live('change', function() {
        if(moduleTable.itemsOnPage != parseInt($(this).val())) {
            $('select[name=limit_to]').val(parseInt($(this).val()));
            moduleTable.itemsOnPage = parseInt($(this).val());
            moduleTable.makePaginator();
            moduleTable.rewind(0);
            moduleTable.updateModule();
        }
    });   
	
});

function getDocumentsList(id, field, sel) {
	
	ajaxRequest("/admin/modules/news/getDocuments/", "id=" + $('#' + id).val(), function (data) {
		if (data) {
			var SELECT = $('#' + field);
			SELECT.find("option:not(:first-child)").remove();
	
			for (var i = 0; i < data.length; i++) {

				var selected = '';
				
				if (data[i].id == sel) {
					selected = ' selected="selected"';
				}
				
				SELECT.append(
	                    '<option value="' + data[i].id + '" ' + selected + '>' + data[i].title + '</option>'
	                ); 
			}
		}
	});
}

function getAnchors() {
	var hash = window.location.hash;
	if (hash != '' && hash != '#') {
		hash = hash.replace('#', '');
		
		hash = hash.split('/');
		for (var i = 0; i < hash.length; i++) {
			if (hash[i] != '') {
				var h = hash[i].split(':');
				anchors[h[0]] = h[1];
			}
		}
	}	
}

function addLinksBlock(id) {
	
	var $num = $('#' + id).find('.links-block').size() + 1;
	$('<p class="links-block" id="links-block' + $num + '"><input class="links" type="text" id="linkTitle_' + $num + '" value="' + langStrings.getMsg("link_title",'Link title') + '" /><input type="text" id="linkUrl_' + $num + '" value="' + langStrings.getMsg("link_url",'Link url') + '" /><input type="hidden" onchange="getDocumentsList(\'linkUrlId_' + $num + '\', \'linkDocId_' + $num + '\'); return false;" id="linkUrlId_' + $num + '" value="" /><a href="#" onclick="openSiteMapDialog(\'linkUrlId_' + $num + '\', \'linkUrl_' + $num + '\', \'\'); return false;" class="select-btn">' + langStrings.getMsg("select",'Select') + '</a><select id="linkDocId_' + $num + '" name="linkDocId_' + $num + '"><option value="0">Select</option></select><select id="linkTarget_' + $num + '"><option value="_blank">_blank</option><option value="_self">_self</option></select><b><a href="#" onclick="$(\'#links-block' + $num + '\').remove(); return false" title="' + langStrings.getMsg("remove",'Remove') + '">' + langStrings.getMsg("remove",'Remove') + '</a></b></p>').appendTo('#' + id);
	
	return false;
}

function addFilesBlock(id, folder) {
	
	var $num = $('#' + id).find('.files-block').size() + 1;
	$('<p class="files-block" id="files-block' + $num + '"><input class="files" type="text" id="fileTitle_' + $num + '" value="' + langStrings.getMsg("file_title",'File title') + '" /><input type="text" id="fileName_' + $num + '" /><a href="#" id="fileButton_' + $num + '" class="select-btn">' + langStrings.getMsg("upload",'Upload') + '</a><b><a href="#" onclick="$(\'#files-block' + $num + '\').remove(); return false" title="' + langStrings.getMsg("remove",'Remove') + '">' + langStrings.getMsg("remove",'Remove') + '</a></b></p>').appendTo('#' + id);
	
	loadUploadButton(folder, 'files-block' + $num, $num);
	
	return false;
}

function addImagesBlock(id, folder) {
	var $num = $('#' + id).find('.images-block').size() + 1;
	$('<div class="images-block" id="image' + $num + 'Div"></div><a href="#" id="image' + $num + 'Button" class="select-btn">' + langStrings.getMsg("upload",'Upload') + '</a>').appendTo('#' + id);

	loadUploadImgButtonForBlock(folder, 'image' + $num);
	
	return false;
}


$.ajaxSetup({
	beforeSend: function() {
		$('#ajax-loader').show();
		$('#loader').show();
	},
	complete: function(){
		$('#ajax-loader').hide();
		$('#loader').hide();
	}
});

function openSiteMapDialog(idField, titleField, func) {
	ajaxRequest("/admin/modules/sitemap/", "idField=" + idField + "&titleField=" + titleField + "&func=" + func, getSiteMapDialog);
}

function loadUploadImgButton(folder, element, config) {
	var button = $('#' + element + 'Button');

	$.ajax_upload(button, {
				action : '/admin/modules/filemanager/upload/?folder=' + folder + '&config=' + encodeURIComponent(JSON.stringify(config)),
				name : 'uploadFile',
				onSubmit : function(file, ext) {				
					$("<img id='imgLoading' src='/admin/images/design/loading.gif' />").appendTo("#" + element + "Div");
					this.disable();

				},
				onComplete : function(file, response) {

					this.enable();

				},
				
				onSuccess : function ( response ) {
					
					$('#imgLoading').remove();
					$('#' + element + 'Div').html('');
					
					$('<img width="100" src="' + response.info.real_path + '"><a href="#" onclick="emptyUploadFile(\'' + element + '\'); return false;\">' + langStrings.getMsg('m_delete','Delete') + '</a><input type="hidden" class="simple" name="' + element + '" id="' + element + '" value="' + response.info.file_name + '">').appendTo('#' + element + 'Div');
                    if($("#"+element+"Button").is(".img-required") && $("#"+element+"Div").parent().is(".error")){
                        $("#"+element+"Div").parent().removeClass("error");
                        var error_id="err_"+element;
                        if($("#errorBlock ."+error_id).length){
                            $("#errorBlock ."+error_id).remove();
                        }
                        if(!$("#errorBlock div").length){
                            $("#errorBlock").hide();
                        }
                    }
				},
				onError : function ( response ) {
					
					$('#imgLoading').remove();
					alert(response.errorMsg);
				}
			});
}

function loadUploadImgButtonForBlock(folder, element) {
	var button = $('#' + element + 'Button');

	$.ajax_upload(button, {
				action : '/admin/modules/filemanager/upload/?folder=' + folder,
				name : 'uploadFile',
				onSubmit : function(file, ext) {				
					$("<img id='imgLoading' src='/admin/images/design/loading.gif'>").appendTo("#" + element + "Div");
					this.disable();

				},
				onComplete : function(file, response) {

					this.enable();

				},
				
				onSuccess : function ( response ) {
					
					$('#imgLoading').remove();
					$('#' + element + 'Div').html('');
					
					$('<img width="100" src="' + response.info.real_path + '"><a href="#" onclick="emptyUploadFile(\'' + element + '\'); return false;\">' + langStrings.getMsg('m_delete','Delete') + '</a><input type="hidden" class="images" name="' + element + '" id="' + element + '" value="' + response.info.file_name + '">').appendTo('#' + element + 'Div');
				},
				onError : function ( response ) {
					
					$('#imgLoading').remove();
					alert(response.errorMsg);
				}
			});
}

function loadUploadButton(folder, element, num) {
	var button = $('#fileButton_' + num);

	$.ajax_upload(button, {
				action : '/admin/modules/filemanager/upload/?folder=' + folder,
				name : 'uploadFile',
				onSubmit : function(file, ext) {				
					$("<img id='imgLoading' src='/admin/images/design/loading.gif'>").appendTo("#" + element);
					this.disable();

				},
				onComplete : function(file, response) {

					this.enable();

				},
				
				onSuccess : function ( response ) {
					
					$('#imgLoading').remove();
					$('#fileName_' + num).val(response.info.file_name);
				},
				onError : function ( response ) {
					
					$('#imgLoading').remove();
					alert(response.errorMsg);
				}
			});
}

function emptyUploadFile(el) {
	$('#' + el + 'Div').html('');
}

function getSiteMapDialog(data) {
	
	if (data) {
		$('<div id="dialog"></div>').appendTo('body');
		$("#dialog").attr("title", langStrings.getMsg('select_page','Select page'))
					.html(data)
					.dialog({
						resizable: false,
						modal: true,
						close: function(event, ui) {
							$('#dialog').remove();
						}
					});
	}		
}

function openCkEditor(idField, mode) {
	ajaxRequest("/admin/modules/ckeditor/", "idField=" + idField + "&mode=" + mode, getCkEditor);
}

function getCkEditor(data) {
	$('<div id="ckeditor"></div>').appendTo('body');
	if (data) {
		$("#ckeditor").attr("title", langStrings.getMsg('wysiwyg_title','Wysiwyg editor'))
					.html(data)
					.dialog({
							resizable: true,
							modal: true,
							width: 850,
							height: 580,
							close: function(event, ui) {
										$('#ckeditor').remove();
									}
		});
	}
}

function openFilesDialog(idField, func) {
	ajaxRequest("/admin/modules/filemanager/", "idField=" + idField + "&func=" + func, getFilesDialog);
}

function getFilesDialog(data) {
	$('<div id="dialog"></div>').appendTo('body');
	if (data) {
		$("#dialog").attr("title", langStrings.getMsg('select_file','Select file'))
					.html(data)
					.dialog({
							resizable: false,
							modal: true,
							close: function(event, ui) {
								$('#dialog').remove();
							}
		});
	}		
}

function filterAction(type) {
	
	if ($('#multiaction' + type).val() == "delete") {
        moduleDeleteSelected(moduleTable.moduleName);
		$('#selAllTop').attr('checked', false);
                $('#selAllBottom').attr('checked', false);
		$('#disAllBottom').attr('checked', false);
                $('#disAllTop').attr('checked', false);
                $('#multiactionTop').val("");
                $('#multiactionBottom').val("");
		return;
	}
	if ($('#multiaction' + type).val() == "enable") {
		moduleEnableSelected(moduleTable.moduleName, "1");
		$('#selAllTop').attr('checked', false);
                $('#selAllBottom').attr('checked', false);
		$('#disAllBottom').attr('checked', false);
                $('#disAllTop').attr('checked', false);
                $('#multiactionTop').val("");
                $('#multiactionBottom').val("");
		return;
	}
	if ($('#multiaction' + type).val() == "disable") {
		moduleEnableSelected(moduleTable.moduleName, "0");
		$('#selAllTop').attr('checked', false);
                $('#selAllBottom').attr('checked', false);
		$('#disAllBottom').attr('checked', false);
                $('#disAllTop').attr('checked', false);
                $('#multiactionTop').val("");
                $('#multiactionBottom').val("");
		return;
	}
	if ($('#multiaction' + type).val() == "copy") {
		moduleCopySelected(moduleTable.moduleName);
		$('#selAllTop').attr('checked', false);
                $('#selAllBottom').attr('checked', false);
		$('#disAllBottom').attr('checked', false);
                $('#disAllTop').attr('checked', false);
                $('#multiactionTop').val("");
                $('#multiactionBottom').val("");
		return;
	}
}

function moduleCopySelected(module) {
	
	var  returnArray = [];
	
    $('input[name=' + module + 'Box]').each(function() {
        if($(this).is(':checked')) {
            returnArray.push($(this).attr('id').substr(module.length + 1));
        }        
    });
    
	if(returnArray.length > 0 && ruSure(langStrings.getMsg('rusure_copy','Are yor sure want to copy this?'))) {
        ajaxRequest(
            moduleTable.getRequestUrl() + 'copy/',
            'id=' + encodeURIComponent(JSON.stringify(returnArray)),
            updateModule
        );
    }
}

function setAll(value, module) {
	
	if (value) {
		$('#selAllTop').attr('checked', true);
		$('#selAllBottom').attr('checked', true);
		$('#disAllTop').attr('checked', false);
		$('#disAllBottom').attr('checked', false);
	}
	else {
		$('#selAllTop').attr('checked', false);
		$('#selAllBottom').attr('checked', false);
		$('#disAllTop').attr('checked', true);
		$('#disAllBottom').attr('checked', true);
	}	
		
	var checkboxes = document.getElementsByTagName("input");
	for (var i = 0; i < checkboxes.length; i++) {
		if (checkboxes[i].type == "checkbox" && checkboxes[i].name == module + "Box") {
			checkboxes[i].checked = value;
		}
	}
}

var langStrings = {
		msg : new Array(),
		lang: "",
		
		addString : function(ar) {
			for (var key in ar) {
				this.msg[key] = ar[key];
			}
		},
		
		getMsg : function (key, defaultMsg) {
			var msgValue = defaultMsg;
			if(this.msg[key] == undefined || this.msg[key] == null){
				if(key != null ){

					ajaxRequest(
			            '/admin/js/langMsg.php?addNew=msg' + '&key=' + encodeURIComponent(JSON.stringify(key)) + '&defValue=' + encodeURIComponent(JSON.stringify(msgValue)),
			            function(data){
			            	if (typeof(data[0]) != 'undefined') {
			            		msgValue = data[0];
			            	}
			            	
			            }
			        );
					if(msgValue == ''){
						msgValue = key;
					}
					return msgValue;
				}
			}
			else{
				return this.msg[key] ;	
			}
			
			return key;		
		}
};

function ruSure(msg) {
	return confirm(msg);
}

function getQueryVariable(variable) {
	var query = window.location.search.substring(1);
	var vars = query.split("&");
	for (var i=0;i<vars.length;i++) {
		var pair = vars[i].split("=");
		if (pair[0] == variable) {
			return pair[1];
		}
	}
	return "";
}

function loadCmsTabs() {
	$('ul.lang-tabs a').live("click", function() {
		var curChildIndex = $(this).parent().prevAll().length + 1;
		$(this).parent().parent().children('.active').removeClass('active');
		$(this).parent().addClass('active');
		$(this).parent().parent().next('.areaBlock').children('.open').fadeOut('fast',function() {
			$(this).removeClass('open');
			$(this).parent().children('div:nth-child('+curChildIndex+')').fadeIn('normal',function() {
				$(this).addClass('open');
			});
		});
		return false;
	});
	$('div.block-holder h4').click(function() {
		$(this).parent().parent().children('.open').removeClass('open');
		$(this).parent().addClass('open');
		
		return false;
	});
}

function setInputError(e, error) {
    if(error === true) {
        $('#' + e).addClass('error');
    } else {
        $('#' + e).removeClass('error');
    }
}

function removeInputError() {
    $('input, textarea').each(function() {
        if($(this).hasClass('error')) {
            $(this).removeClass('error');
        }
    });
}

function cancelClose(id) {
    id = parseInt(id);
    
    $('tr#edit_' + (id ? id : '')).html('');
    if(id) {
        $('tr#tr_' + id).removeClass('open-row');
    }    
}

function scrollToTop() {
    $(this).scrollTop($('div.content').position().top);
}
