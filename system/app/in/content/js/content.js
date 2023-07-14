
function updateModule() {
    moduleTable.updateContentModule(initTree);
	
}

function search() {
    moduleTable.additionalParms = "&search=" + $('#bla').val();
	
    updateModule();
}

function checkForSelectedId() {
    
    if (typeof(anchors['id']) != 'undefined') {
        $('#node' + anchors['id']).find('a').first().click();
        $('#node' + anchors['id']).find('a').first().dblclick();
    }
}

function initTree() {	
    $("#modulePath").jstree({
        "themes" : {
            "theme" : "default",
            "dots" : true,
            "icons" : false
        },
        "plugins" : [ "themes", "html_data", "ui", "cookies", "crrm", "dnd", "contextmenu" ],
        "contextmenu" : {
            "items" : contextMenu
			
        }
    }).bind("dblclick.jstree", function (event) {	
        var node = $(event.target).closest("li");
        var data = node.data("jstree");
        var id = $(node).attr("id").replace("node", "");
		
        moduleEdit(id, '');
        window.location.hash = 'id:' + id + '/';
		
        return false;
	   
    }).bind("prepare_move.jstree", function (event, data) {
        return false;
    }).bind("move_node.jstree", function (event, data) {
        var childrens = [];
        $(data.rslt.o).parent().children('li').each(function(index) {
            childrens.push($(this).attr('id').replace("node", ""));
        });
		var parentID = $(data.rslt.o).attr('id');
		parentID = parentID.replace("node", "");
        parentID = $(data.rslt.o).parent().parent().attr('id');
        if (parentID === 'modulePath') {
            parentID = 0;
        } else {
           parentID = parentID.replace("node", "");
        }
	if (parentID != 0) {	
          ajaxRequest(moduleTable.getRequestUrl() + "saveDND/", "value=" + parentID + "&main=" + parentID + "&childrens=" + encodeURIComponent(JSON.stringify(childrens)), updateModule);	
          return false;
        } else {
            //alert(langStrings.getMsg('no_more_root','No more root'));
             updateModule();
            
        }
    });
}

function contextMenu(node) {
    // The default set of all items
    var items = {
        create_subcategory : {
            label	: langStrings.getMsg('create_subcategory','Create subcategory'), 
            icon	: "", // you can set this to a classname or a path to an icon like ./myimage.gif 
            action	: function (NODE) { 				
                id = $(NODE).attr("id").replace("node", "");
                moduleEdit('', id);		
            },
            separator_before : true
        },
        move_up : {
            label	: langStrings.getMsg('sort_up','Move up'), 
            icon	: "", // you can set this to a classname or a path to an icon like ./myimage.gif 
            action	: function (NODE) { 			
                id = $(NODE).attr("id").replace("node", "");
                moduleSort(id, 'up');		
            },
            separator_before : true
        },
        move_down : {
            label	: langStrings.getMsg('sort_down','ove down'), 
            icon	: "", // you can set this to a classname or a path to an icon like ./myimage.gif 
            action	: function (NODE) { 				
                id = $(NODE).attr("id").replace("node", "");
                moduleSort(id, 'down');		
            },
            separator_before : true
        },
        visible : {
            label	: langStrings.getMsg('m_visible','Visible'), 
            icon	: "", // you can set this to a classname or a path to an icon like ./myimage.gif 
            action	: function (NODE) { 
                id = $(NODE).attr("id").replace("node", "");
                moduleEnable(id, 1, "enable");		
            },
            separator_before : true
        },
        unvisible : {
            label	: langStrings.getMsg('m_unvisible','Unvisible'), 
            icon	: "", // you can set this to a classname or a path to an icon like ./myimage.gif 	
            action	: function (NODE) { 
                id = $(NODE).attr("id").replace("node", "");
                moduleEnable(id, 0, "enable");		
            },
            separator_before : true
        },
        enable : {
            label	: langStrings.getMsg('m_enable','Enable'), 
            icon	: "", // you can set this to a classname or a path to an icon like ./myimage.gif 
            action	: function (NODE) { 				
                id = $(NODE).attr("id").replace("node", "");
                moduleEnable(id, 1, "active");		
            },
            separator_before : true
        },
        disable : {
            label	: langStrings.getMsg('m_disable','Disable'), 
            icon	: "", // you can set this to a classname or a path to an icon like ./myimage.gif 
            action	: function (NODE) { 
                id = $(NODE).attr("id").replace("node", "");
                moduleEnable(id, 0, "active");		
            },
            separator_before : true
        },
        copy : {
            label	: langStrings.getMsg('copy','Copy'), 
            icon	: "", // you can set this to a classname or a path to an icon like ./myimage.gif 
            action	: function (NODE) { 
                id = $(NODE).attr("id").replace("node", "");
                copyCat = id;
                openSiteMapDialog('', '', 'moduleCopy');		
            },
            separator_before : true
        },
        remove : {
            label	: langStrings.getMsg('m_delete','Delete'),
            icon	: "remove",
            visible	: function (NODE, TREE_OBJ) { 
                return true; 
            }, 
            action	: function (NODE) { 		
                if (ruSure(langStrings.getMsg('rusure_delete','Are yor sure want to delete this ?'))) {
                    id = $(NODE).attr("id").replace("node", "");
                    moduleDelete(id);
                }				
            },
            separator_before : true		
        }
    };

    if ($(node).find('a').hasClass("red")) {
        delete items.disable;
    } else {
        delete items.enable;
    }
    
    if ($(node).find('a').hasClass("gray")) {
        delete items.unvisible;
    } else {
        delete items.visible;
    }
    

    return items;
}

var copyCat;

function moduleCopy(NODE) {
	
    id = $(NODE).attr("id").replace("node", "");
	
    ajaxRequest(moduleTable.getRequestUrl() + "copy/" + id + "/", "copy=" + copyCat, function() {});
}

function moduleEdit(id, parentId) {

    var idUrl, addData;
    
    if (id) {
        idUrl = id + "/";
        addData = "";
    } else {
        idUrl = "";
        addData = "country=" + $('#countryF').val() + "&language=" + $('#language').val() + "&parentId=" + parentId;
    }	
	
    ajaxRequest(moduleTable.getRequestUrl() + "edit/" + idUrl, addData, getModuleEdit);
}

function getModuleEdit(data) {
    
    if (data) {
        $('#contentData').html(data["html"]);
        inheritChecker();
    }	
}

function moduleEnable(id, value, type) {
    ajaxRequest(moduleTable.getRequestUrl() + type + "/", "value=" + value + "&id=" + id, updateModule);
}

function moduleDelete(id) {
    ajaxRequest(moduleTable.getRequestUrl() + "delete/", "id=" + id, updateModule);
}

function moduleSort(id, value){
    
    ajaxRequest(moduleTable.getRequestUrl() + "sort/", "value=" + value + "&id=" + id, updateModule);
}

function changeCountry() {
    moduleTable.additionalParms = "&filterCountry=" + $('#countryF').val();
    updateModule();
}

function changeLang() {
    moduleTable.additionalParms = "&filterLang=" + $('#language').val() + "&filterCountry=" + $('#countryF').val();
    updateModule();
}

function checkFields() {
	
    if ($('#url').val() == '') {
        $('#errorBlock').html(langStrings.getMsg('select_correct_url','Please enter unique url for the chosen language'));
        $('#errorBlock').show(); 
        return false;
    }
	
    var idUrl = $('#id').val() ? $('#id').val() + "/" : "";
    
    
    var values = {};
    values["url"] = $('#url').val();
    values["lang"] = $('#lang').val();
    values["country"] = $('#country').val();
    values["parent_id"] = $('#parent_id').val() ? $('#parent_id').val() : 0;
    
    ajaxRequest(moduleTable.getRequestUrl() + "checkname/" + idUrl, "value=" + encodeURIComponent(JSON.stringify(values)), checkName);
   
}

function checkName(data) {
    if(data){
        saveData();
    }
    else {
        $('#errorBlock').html(langStrings.getMsg('select_correct_url','Please enter unique url for the chosen language'));
        $('#errorBlock').show(); 
        return false;
    }
}

function saveData() {
    var returnArray = {},
        modulesValues = {};

    console.log('here');
    console.log($('#editForma :input'));

    $('#editForma :input').each(function(n, element) {
        if($(element).attr('type') !== 'button' && $(element).attr('type') !== 'file') {

            console.log('getting element');
            console.log(element);

            if ($(element).hasClass('notInsert')) {
                console.log('NOT INSERT !!!!!!!!!!!!!!!!!');
                //continue;
            }
            else {

                if (String($(element).attr('id')).search('module_')) {
                    if ($(element).attr('type') === 'checkbox') {
                        if ($(element).is(':checked')) {
                            returnArray[$(element).attr('id')] = $(element).val();
                        } else {
                            returnArray[$(element).attr('id')] = '0';
                        }	
                    } else {
                        if ($(element).attr('type') === 'radio') {
                            if ($(element).is(':checked')) {
                                returnArray[$(element).attr('name')] = $(element).val();
                            }
                        } else {
                            returnArray[$(element).attr('id')] = $(element).val();
                        }
                    }
					
                } else {
                    if ($(element).attr('type') === 'checkbox') {
                        if ($(element).is(':checked')) {
                            modulesValues[$(element).attr('id')] = $(element).val();
                        } 
                    }
                }
            }		
        }
		
    });
	
    var sendData = {};
    sendData['value'] = returnArray;
    sendData['modules'] = modulesValues;

    console.log(sendData);
    
    var idUrl = $('#id').val() ? $('#id').val() + "/" : "";
    ajaxRequest(moduleTable.getRequestUrl() + "save/" + idUrl, sendData, onSaveData);
    return false;
}

function inheritChecker() {
    
    if ($('#inherit').is(':checked')) {
        $('.inherit').each(function(n, element) {
            $(element).attr('disabled', 'disabled');
        });
    } else {
        $('.inherit').each(function(n, element) {
            $(element).removeAttr('disabled');
        });
    }	
}

function onSaveData(data) {

	error = '';
    if (data) {
		data = JSON.stringify(data); 
		switch(data)
		{
		case '"root_already_exists"':
		  error = langStrings.getMsg('root_already_exists','Root directory already set');
		  break;
		case '"parent_child_or_self"':
		  error = langStrings.getMsg('parent_child_or_self', 'Parent page cant be its child or itself');
		  break;
		}
		if (error){
			$('#errorBlock').html(error);
			$('#errorBlock').show();
		}
		
        updateModule();
        window.location.hash = 'id:' + data + '/';
        moduleEdit(data);

    } else {
        $('#contentData').html('');
        updateModule();
    }	
}

function createUrl() { 
    if ($('#url').val() == '') {
        
        var value = $('#title').val();
        
        ajaxRequest(
            moduleTable.getRequestUrl() + "createTitleUrl/", 
            "value=" + encodeURIComponent(value),
            function(response) {
                var values = {};
                values["url"] = response;
                values["idUrl"] = $('#id').val() ? $('#id').val() + "/" : "";
                values["lang"] = $('#lang').val();
                values["country"] = $('#country').val();
                values["parent_id"] = $('#parent_id').val() ? $('#parent_id').val() : 0;

                // console.log('=================================');
                // console.log('values');
                // console.log(values);

                var idUrl = values['url'];

                var isUnique = checkUniqueUrl(values['url'], values);
                var addUrlVal = 2;

                while (!isUnique){
                    values["url"] = response + '-' + addUrlVal;
                    isUnique = checkUniqueUrl(values['url'], values);
                    addUrlVal++;
                 }  
                 
         });
    }

function checkUniqueUrl(idUrl, values){
    var result;
    $.ajaxSetup({
        async: false
    });
    ajaxRequest(moduleTable.getRequestUrl() + "checkname/" + idUrl, "value=" + encodeURIComponent(JSON.stringify(values)), function(checkResult){
        result = checkResult;
    });
    $.ajaxSetup({
        async: true
    });
    return result;
}


}