/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	config.resize_enabled = true;
	config.extraPlugins = 'mediaembed';
	
	
	/*config.allowedContent = true;
	config.oembed_maxWidth = '560';
	config.oembed_maxHeight = '315';
	config.oembed_WrapperClass = 'embededContent';*/
	
	config.toolbar_AdwebAdvanced =
	    [
	        ['Source','NewPage','Preview'],
	        ['Cut','Copy','Paste','PasteText','PasteFromWord'],
	        ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	        ['Link','Unlink','Anchor'],
	        ['Image','oEmbed','MediaEmbed','Flash','Table','HorizontalRule','Smiley','SpecialChar'],
	        '/',
	        ['Format','Font','FontSize'],
	        ['Bold','Italic','Underline','Strike','Subscript','Superscript'],
	        ['NumberedList','BulletedList','-','Outdent','Indent'],
	        ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	        ['TextColor','BGColor'],
	        ['Maximize']
	    ];
	
	config.toolbar_AdwebSimple =
	    [
	        ['Bold', 'Italic','Underline', '-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink','-']
	    ];
};
