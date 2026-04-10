(function($){
	$.wpcfs_add_handler("input", "select_handler", function (main_ui, instance_config, type_config, save) {
		instance_config = $.extend({ 
				"source": "Auto",
				"allow_blank":true,
				"any_label": type_config.any_label,
			},instance_config);

		(function (select,options_ui) {
			select.append("<option value='Auto'>"+__("Auto")+"</option>");
			select.append("<option value='Manual'>"+__("Manual")+"</option>");
			select.val(instance_config.source);

			var show_options = function(){
				options_ui.html("");
				if(instance_config.source=="Auto"){
					var rand_id = Math.random();
					(function(auto_ui){

					 })($(
					"<div>"+
						"<input type='checkbox' id='"+rand_id+"'/>"+
						"<label for='"+rand_id+"'>"+__("Allow Blank")+"</label>"+
						"<input class='any-label'/>"+
					"</div>"
					).appendTo(options_ui));



				} else {

				}
			};
			select.change(function(){
				instance_config.source = $(this).val();
				save(instance_config);
				show_options();
			});
			show_options();
		})(
			$('<select/>').appendTo(main_ui),
			$('<div/>').appendTo(main_ui)
		);

	});
 })(jQuery);

 
jQuery(function ($) {
	var $editor = $('#wpcfs-presets-page');

	if (!$editor.length) {
		return;
	}

	$editor.wpcfs_editor({
		mode: 'presets',
		root_template: 'presets.html',

		form_config: window.wpcfsAdmin.presets,
		building_blocks: window.wpcfsAdmin.editor_config,
		settings_pages: window.wpcfsAdmin.settings_pages,

		root: window.wpcfsAdmin.root,

		save_callback: 'wpcfs_save_preset',
		delete_callback: 'wpcfs_delete_preset',
		export_callback: 'wpcfs_export_settings',

		save_nonce: window.wpcfsAdmin.save_nonce,
		delete_nonce: window.wpcfsAdmin.delete_nonce,
		export_nonce: window.wpcfsAdmin.export_nonce
	});
});