CKEDITOR.editorConfig = function( config )
{
	config.language = 'en';
	// config.uiColor = '#99BBE8';

	config.toolbar_full = [
		['Undo','Redo','PasteText','PasteFromWord','Find','Replace'],
		['Image','Flash','Table','HorizontalRule','SpecialChar','PageBreak','Iframe'],
		['TextColor','BGColor'],
		['RemoveFormat','ShowBlocks'],
		'/',
		['Bold','Italic','Underline','Strike','Subscript','Superscript','NumberedList','BulletedList','Outdent','Indent','Blockquote','CreateDiv'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['Maximize','About'],
		'/',
		['Link','Unlink','Anchor'],
		['Format','Font','FontSize','Source']
	];

	config.toolbar_light = [
		['Undo','Redo','PasteText','PasteFromWord','Find','Replace'],
		['Image','Flash','Table','HorizontalRule','SpecialChar','PageBreak','Iframe'],
		['TextColor','BGColor'],
		['RemoveFormat','ShowBlocks'],
		'/',
		['Bold','Italic','Underline','Strike','Subscript','Superscript','NumberedList','BulletedList','Outdent','Indent','Blockquote','CreateDiv'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['Maximize','About'],
		'/',
		['Link','Unlink','Anchor'],
		['Format','Font','FontSize','Source']
	];
};
