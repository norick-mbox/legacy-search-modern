<?php
	class WPCustomFieldsSearch_TextBoxInput extends WPCustomFieldsSearch_Input {
		public string $template = 'text';
		function get_editor_options(){
			$options = parent::get_editor_options();
			$options['extra_config_form'] = plugin_dir_url(__FILE__).'ng/partials/inputs/textbox.html';
			$options['split_words'] = '';
			return $options;
		}
		function get_submitted_value($options,$data){
			$html_name="f".$options['index'];
			$value = isset($data[$html_name]) ? $data[$html_name] : '';

            if(array_key_exists('split_words',$options) && $options['split_words']){
                return array_values(array_filter(explode(' ', $value), 'strlen'));
            } else {
                return array($value);
            }
		}
        function get_name(){ return __("Text Input","legacy-search-modern"); }
	}



    class WPCustomFieldsSearchClassException extends Exception{}
    function wpcfs_instantiate_class($class){
        if(!is_string($class)) throw new WPCustomFieldsSearchClassException("Class name must be a string");
        if(!class_exists($class)) throw new WPCustomFieldsSearchClassException("Class name must refer to an existing class");
        return new $class();
    }

	class WPCustomFieldsSearch_SelectInput extends WPCustomFieldsSearch_Input {
        function get_name(){ return __("Drop Down","legacy-search-modern"); }
		var $template = "select";
		function get_editor_options(){
			$options = parent::get_editor_options();
			$options['extra_config_form'] = plugin_dir_url(__FILE__).'ng/partials/inputs/select.html';

            $options['defaults'] = array( 
                "any_message"=>__("Any","legacy-search-modern"), 
                "source"=>"Auto",
	            "options"=>array(array("value"=>1,"label"=>__("One","legacy-search-modern")),array("value"=>2,"label"=>__("Two","legacy-search-modern")))
            );

			return $options;
		}

		function render($config,$query){
			if (isset($config['source']) && $config['source'] === 'Auto') {
                try {
                    $datatype = wpcfs_instantiate_class($config['datatype']);
                    $config['options'] = array_merge(array(array("value"=>"","label"=>$config['any_message'])),$datatype->get_suggested_values($config));
                } catch(WPCustomFieldsSearchClassException $e){
                    $config['options'] = array();
                }
			}
			return parent::render($config,$query);
		}
	}
	class WPCustomFieldsSearch_RadioButtons extends WPCustomFieldsSearch_SelectInput {
        function get_name(){ return __("Radio Buttons","legacy-search-modern"); }
		var $template = "radio-buttons";
    }
	class WPCustomFieldsSearch_CheckboxInput extends WPCustomFieldsSearch_Input {
        function get_name(){ return __("Checkboxes","legacy-search-modern"); }
		var $template = "checkbox";
		function get_editor_options(){
			$options = parent::get_editor_options();
			$options['extra_config_form'] = plugin_dir_url(__FILE__).'ng/partials/inputs/checkbox.html';
            $options['defaults'] = array( 
                "any_message"=>__("Any","legacy-search-modern"), 
                "source"=>"Auto",
	            "options"=>array(array("value"=>1,"label"=>__("One","legacy-search-modern")),array("value"=>2,"label"=>__("Two","legacy-search-modern")))
            );
			return $options;
		}

		function render($config,$query){
			if (isset($config['source']) && $config['source'] === 'Auto') {
                try {
                    $datatype = wpcfs_instantiate_class($config['datatype']);
                    $config['options'] = $datatype->get_suggested_values($config);
                } catch(WPCustomFieldsSearchClassException $e){
                    $config['options'] = array();
                }
			}
			return parent::render($config,$query);
		}
	}
	class WPCustomFieldsSearch_HiddenInput extends WPCustomFieldsSearch_Input {
        public bool $show_in_form = false;

		function get_editor_options(){
			$options = parent::get_editor_options();
			$options['extra_config_form'] = plugin_dir_url(__FILE__).'ng/partials/inputs/hidden.html';
            $options['constant_value'] = '';
			return $options;
		}
		function get_submitted_value($options,$data){
            return $options['constant_value'];
		}

        function get_name(){ return __("Hidden Constant","legacy-search-modern"); }
		function is_submitted($options,$data){
            return true;
        }
	}
