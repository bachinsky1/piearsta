// Filemanager JS functions

var oldSelFolderId = '';

/**
  * initialization of drag and drop tree
  */
function initDragAndDropList() {
	if ($('filemanagerTree')) {
	
		var fTree = new Axent.DragDropTree('filemanagerTree', {
		    folderIcon : 'folder.gif',
		    showOnlyWithSubItems : true,
		    beforeDropNode: function(node, dropOnNode, point) { 

			    if ((dropOnNode.className != "folder" && dropOnNode.className != "folder active") || dropOnNode.id == "") {
					Dialog.alert(langStrings.getMsg('cant_move_file_to_file','Cant move File to File'), {width:300, height:100, id: "alertWindow", okLabel: langStrings.getMsg('close','Close'), ok:function(win) {return true;}}); 		
					return false;
				}
			    
			    var confirmed = confirm(langStrings.getMsg('move_selected_item','Move selected item'));
			    if (!confirmed) {
			        return false;
		        }
		    },
		    afterDropNode: function(node, dropOnNode, point) {
			    var src = node.identify().replace(/fm/g, "");
			    src = $('path' + src).innerHTML;
			    
			    var dst = (node.up('li') != undefined) ? node.up('li').identify().replace(/fm/g, "") : '';
			    dst = (dst != "") ? $('path' + dst).innerHTML : defaultFolder;

				if (dropOnNode.className == "folder" || dropOnNode.className == "folder active" && dst != "") {
					showPreloader();
					ajaxRequest(moduleTable.mainUrl, "ajax=y&action=move&module=" + moduleTable.moduleName + "&item=" + src + "&mFolder=" + dst, updateModule);
				}
				else {
					Dialog.alert(langStrings.getMsg('cant_move_file_to_file','Cant move File to File'), {width:300, height:100, id: "alertWindow", okLabel: langStrings.getMsg('close','Close'), ok:function(win) {return true;}}); 		
					updateModule();
					return false;
				}
		    }});
	
	}	
	
}

/**
 * updating filemanager module list
 */
function updateModule() {
	moduleTable.updateModule(initDragAndDropList);
}

function selectFileFromManager(fName) {
	if (dialogMode) {
		var fName = '/' + fName;
		parent.onDialogReturn.FileManager(fName, getQueryVariable('param'));
 		parent.Windows.close('FileManager');
		return;
	}
	
	return false;
}

/**
  * delete file or folder
  */
function moduleDelete(id) {
	if (ruSure(langStrings.getMsg('rusure_delete','Are yor sure want to delete this ?'))) {
		showPreloader();
		ajaxRequest(moduleTable.mainUrl, "ajax=y&action=delete&module=" + moduleTable.moduleName + "&item=" + id, updateModule);
	}
}

/**
  * edit file or folder
  */
function moduleEdit(id) {
	openWindow(moduleTable.mainUrl + "?module=" + moduleTable.moduleName + "&dialog=y&action=edit&item=" + id, "filemanagerEdit", 80, 500, langStrings.getMsg('fileorfolder_edit','File Folder Edit'), onModuleEdit, true);
}

/**
  * calling function to send file or folder information to php
  */
function onModuleEdit(item, values) {
	showPreloader();
	ajaxRequest(moduleTable.mainUrl, "ajax=y&action=save&module=" + moduleTable.moduleName + "&item=" + item + "&value=" + encodeURIComponent(JSON.stringify(values)), updateModule);	
}

/**
  * return file or folder information to calling function
  */
function returnItem() {
	
	var returnArray = {};

	var fItem = $F('fItem');
	returnArray["fName"] = $F('fName');	
	
	parent.onDialogReturn.filemanagerEdit(fItem, returnArray);	
	setTimeout("parent.Windows.close('filemanagerEdit')", 350);
}

/**
  * check data in fields and return false or run return data function
  */
function checkFields() {
	
	if ($F('fName') == '') {
		parent.Dialog.alert(langStrings.getMsg('enter_correct_name','Enter correct name'), {width:300, height:100, id: "alertWindow", okLabel: langStrings.getMsg('close','Close'), ok:function(win) {return true;}}); 
		return false;
	}

	parent.ajaxRequest(parent.moduleTable.mainUrl, "ajax=y&action=checkname&module=" + parent.moduleTable.moduleName + "&value=" + $F('fName') + "&item=" + $F('fItem'), checkFileOrFolderName);	
}

/**
  * check for correct entered file or folder name
  */
function checkFileOrFolderName(resp, MyArray) {

	MyArray = evalJson(resp.responseText);
	if (MyArray == "true") {
		returnItem();
	}
	else {
		parent.Dialog.alert(langStrings.getMsg('enter_correct_name','Enter correct name'), {width:300, height:100, id: "alertWindow", okLabel: langStrings.getMsg('close','Close'), ok:function(win) {return true;}}); 
		return false;
	}
}

/**
  * changing upload folder
  */
function changeUploadFolder(uFolder, id) {
	
	if (uploadFolder == uFolder) {
		$('fl' + id).removeClassName('selCat');
		var uploadFolder = defaultFolder;
	}else {
		if (uFolder && oldSelFolderId) {
			$('fl' + oldSelFolderId).removeClassName('selCat');
		}	
		
		$('fl' + id).addClassName('selCat');
		oldSelFolderId = id;
		uploadFolder = uFolder;
	}
	
}

/**
  * create folder to upload folder
  */
function createFolder() {
	uploadFolder = uploadFolder ? uploadFolder : defaultFolder;
	openWindow(moduleTable.mainUrl + "?module=" + moduleTable.moduleName + "&dialog=y&action=addFolder&item=" + uploadFolder, "filemanagerEdit", 80, 500, langStrings.getMsg('create_folder','Create folder'), onCreateFolder, true);
}

/**
  * calling function to send folder name and destination to php
  */
function onCreateFolder(item, value) {
	showPreloader();
	ajaxRequest(moduleTable.mainUrl, "ajax=y&action=saveFolder&module=" + moduleTable.moduleName + "&item=" + item + "&value=" + value, updateModule);
}

/**
  * checking for correct folder name
  */
function checkFolderName() {
	if ($F('fName') == '' || $F('fItem') == '') {
		parent.Dialog.alert(langStrings.getMsg('enter_correct_name','Enter correct name'), {width:300, height:100, id: "alertWindow", okLabel: langStrings.getMsg('close','Close'), ok:function(win) {return true;}}); 
		return false;
	}
	
	parent.ajaxRequest(parent.moduleTable.mainUrl, "ajax=y&action=checkname&module=" + parent.moduleTable.moduleName + "&value=" + $F('fName') + "&item=" + $F('fItem'), checkForExistFolderName);
}

/**
  * checking for exist folder name
  */
function checkForExistFolderName(resp, MyArray) {
	
	MyArray = evalJson(resp.responseText);
	if (MyArray == "true") {
		
		parent.onDialogReturn.filemanagerEdit($F('fItem'), $F('fName'));	
		setTimeout("parent.Windows.close('filemanagerEdit')", 350);
	}
	else {
		parent.Dialog.alert(langStrings.getMsg('enter_correct_name','Enter correct name'), {width:300, height:100, id: "alertWindow", okLabel: langStrings.getMsg('close','Close'), ok:function(win) {return true;}}); 
		return false;
	}		
}

/**
  * show file upload form
  */
function showUploadForm() {
	uploadFolder = uploadFolder ? uploadFolder : defaultFolder;
	openWindow(moduleTable.mainUrl + "?module=" + moduleTable.moduleName + "&dialog=y&action=uploadForm&folder=" + uploadFolder, "uploadFile", 300, 500, langStrings.getMsg('upload_files','Upload files'), uploadFiles, true);
}

/**
  * upload files
  */
function uploadFiles(fArray, eMsg) {
	if (eMsg != '') {
		alert(langStrings.getMsg('uploadFileError','Upload file Error') + eMsg);
	}
	
	Windows.close('uploadFile');
}

/**
  * checking for correct file name
  */
function fileExists(fName) {
	uploadFolder = parent.uploadFolder ? parent.uploadFolder : parent.defaultFolder;
	parent.ajaxRequest(parent.moduleTable.mainUrl, "ajax=y&action=checkname&module=" + parent.moduleTable.moduleName + "&value=" + fName + "&item=" + uploadFolder, checkForExistFileName);	
}

/**
  * checking for exist file name
  */
function checkForExistFileName(resp, MyArray) {
	
	MyArray = evalJson(resp.responseText);
	if (MyArray == "true") {
		return true;
	}
	else {	
		return false;
	}
}

/**
  * check upload files
  */
function checkUploadFiles() {
		
	var fileFrom = $('fileform');
	var uploadFiles = $A(fileFrom.getElementsByTagName("input")).findAll(function (input) {
		return (input.type=='file') && (input.value != "");
	});
	
	uploadFiles.each(function(uploadFile) {
		var fnameMatches = uploadFile.value.match(/([^\/\\]+)$/);
		var fname = fnameMatches[1];
		fname = fname.replace(/[^a-zA-Z0-9.-_\-]/g, "_");
		fname = fname.toLowerCase();
		/*if (fileExists(fname)) {

			if (!confirm(langStrings.getMsg("WarnOverwrite") + " " + fname + "?")) {
				
				// remove input and list
				uploadFile.parentNode.removeChild(uploadFile);
				var listNode = $(uploadFile.id + "list");
				listNode.parentNode.removeChild(listNode);
			}
		}*/
	});

	// check what left
	uploadFiles = $A(fileFrom.getElementsByTagName("input")).findAll(function (input) {
		return (input.type=='file') && (input.value != "");
	});

	return uploadFiles.length != 0 ? true : false;
}

/**
  * multi selector class
  */
function MultiSelector(list_target, max) {
	this.list_target = list_target;
	this.count = 0;
	this.id = 0;
	this.max = max || -1;
	
	this.addElement = function( element ){
			var id = 'userfile_' + this.id++;
			element.name = id;
			element.id = id;
			element.multi_selector = this;

			element.onchange = function() {
				if (!this.multi_selector.isValidFileName(element.value)) {
					element.value = "";
					return;
				}
				var new_element = document.createElement('input');
				new_element.type = 'file';
				new_element.size = "30";
				new_element.className = "uploadfile";
				this.parentNode.insertBefore(new_element, this);
				this.multi_selector.addElement(new_element);
				this.multi_selector.addListRow(this);
				this.style.position = 'absolute';
				this.style.left = '-1000px';
			};
			if( this.max != -1 && this.count >= this.max ){
				element.disabled = true;
			}
			this.count++;
			this.current_element = element;
	};

	this.addListRow = function(element) {
		var new_row = document.createElement( 'li' );
		new_row.id = element.id + "list";
		var new_row_button = document.createElement('img');
		new_row_button.src = 'images/ico_delete.png';
		new_row_button.title = langStrings.getMsg("removeFileFromUploadList",'Remove file from upload list');
		new_row_button.alt = langStrings.getMsg("removeFileFormUploadList",'Remove file from upload list');
		new_row.element = element;
		new_row_button.onclick= function(){
			this.parentNode.element.parentNode.removeChild(this.parentNode.element);
			this.parentNode.parentNode.removeChild(this.parentNode);
			this.parentNode.element.multi_selector.count--;
			this.parentNode.element.multi_selector.current_element.disabled = false;
			return false;
		};
		new_row.innerHTML = element.value;
		new_row.appendChild( new_row_button );
		this.list_target.appendChild( new_row );
	};
	
	this.isValidFileName = function (filePath){
		var fnameMatches = filePath.match(/([^\/\\]+)$/)
		var fname = fnameMatches[1];
		var FnameTmp = fname.replace(/ /g, "_");
		//if (FnameTmp.match(/[^a-zA-Z0-9.-_\-]/)) {
			//alert (CmsLangStrings.getMsg("invalidFileName"));
			//return false;
		//}
		
		// if file will be renamed, warn user
		// FnameTmp = FnameTmp.replace(/[^a-zA-Z0-9.-_\-]/g, "_");
		// FnameTmp = FnameTmp.toLowerCase();
		// if ( FnameTmp != fname){
		//	alert(CmsLangStrings.getMsg("FileWillBeRenamed") + " " + FnameTmp);
		// }
		
		//check bad extensions
		if (fname.match(/\.js$|\.php$/)) {
			alert (langStrings.getMsg("notAllowedFileExtension",'Not allowed file extension.'));
			return false;
		}
		return true;
	}
};
