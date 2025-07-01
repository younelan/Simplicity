<?php

namespace Opensitez\Simplicity\Plugins;

//require_once dirname(__FILE__) . "/widgets/FormField.php";
require_once dirname(__FILE__) . "/FormController.php";
class Form extends \Opensitez\Simplicity\Plugin
{
	private $vars = [];
	private $values = [];
	private $errors = [];
	private $fields = [];
	private $showtr = true;
	private $action = "post";
	protected $available_fields = [];
	protected $lang;
	function get_menus($app = [])
	{


		$menu = [
			"content" => [
				"text" => "Content",
				"weight" => -2,
				"children" => [
					"select" => ["plugin" => "form", "page" => "default", "text" => "Forms", "category" => "all"],
				],
				"visible" => true,
			]
		];
		return $menu;
	}
	function setvars($vars, $app = false)
	{

		$this->vars = $vars;
		$this->lang = $app['lang'] ?? $vars['lang'] ?? false;
		$lang = $this->lang;
		$i18n = $this->framework->get_component("i18n");
		if (!$lang) {
			$langs = $i18n->accepted_langs();
			if ($langs) {
				$lang = array_keys($langs)[0];
			} else {
				$lang = "en";
			}
		}
		$this->fields = [];
		$field_classes = [
			"Select", "Birthday", "Checklist", "Currency", "DirSelect", "GFXFileRadio", "Inventory",
			"FileSelect", "Hidden", "Listbox", "Matrix", "OptionList", "Text", "TextArea",
		];
		$this->available_fields = [];
		foreach ($field_classes as $class_name) {
			$this->available_fields[strtolower($class_name)] = $class_name;
		}
		$this->framework->load_plugins(__DIR__ . "/widgets", 'Opensitez\\Simplicity\\Plugins', 'core');

		$replacements = [
			"number" => "Text", "integer" => "Text", "currency" => "Text",
			"list" => "Select", "enum" => "Select", "location" => "Text", "localisation" => "Text", "color" => "text", "amount" => "text"
		];
		foreach ($replacements as $fname => $fclass) {
			$this->available_fields[$fname] = $fclass;
		}
		foreach ($vars["fields"] ?? [] as $name => $field_def) {
			//print "$name<br/>\n";// print_r($field_def);
			$field_type = $field_def["type"] ?? "text";
			$field_def["name"] = $name;

			$field_def["lang"] = $lang ?? "en";
			$field_def['lang'] = $lang;
			// print $name;
			if (isset($field_type, $this->available_fields)) {
				if (isset($this->available_fields[$field_type]) && $this->available_fields[$field_type]) {
					if (isset($this->available_fields[$field_type])) {
						try {
							$current_class_name = "\\Opensitez\\Plugins\\" . $this->available_fields[$field_type];
							$new_field = new $current_class_name($this->config_object);
							$new_field->set_handler($this->framework);
							$new_field->set_fields($field_def);
							$this->fields[] = $new_field;
							//print "created $field_type<br/>\n";
							// $new_field->set_config($vars);
						} catch (Exception $e) {
							//print ("field type set but error creating class");
						}
					}
				} else {
					//print "error creating $field_type<br/>\n";
				}
			} else {
				//print "<div style=\"color:red;font-size:1.5em\">Unknown $field_type</div><br>\n";
			}
		}
	}
	function process($FormVars, $PostedValues)
	{
		$ErrorValues = "";
		$MyPostedValues = $PostedValues;
		foreach ($this->vars as $key => $value) {
			switch ($value["type"]) {
				case "checklist":
					break;
				default:
					if (isset($value["filter"])) {
						if (!isset($value["error"]))
							$value["error"] = "(error)";
						if (!ereg($value["filter"], $PostedValues[$key])) {
							$ErrorValues[$key] = $value["error"];
							$MyPostedValues[$key] = "";
						}
					}
			}
			if (!isset($PostedValues[$key])) $PostedValues[$key] = "";
			if (is_array($PostedValues[$key])) {
				$tmpValue = "";
				foreach ($PostedValues[$key] as $Postedkey => $Postedvalue) {
					$tmpValue .= "|" . $Postedvalue;
				}
				if ($tmpValue)
					$tmpValue = substr($tmpValue, 1);
				//print "<b>$key :</b> " . $tmpValue . "<br>";
			}
			//else
			// print "<b>$key :</b> " . $PostedValues[$key] . "<br>";
		}
		$this->values = $MyPostedValues;
		$this->render("", true, $ErrorValues);
	}
	function render($display = false)
	{
		$retval = "<table>\n";
		$retval .= "<form method=post action=$this->action>";
		$showtr = true;
		foreach ($this->fields ?? [] as $key => $field) {

			if ($this->showtr == true)
				$retval .= "<tr>";
			$app = [];
			//$retval=$field->render_label();
			$retval .= $field->render($app);
		}
		if ($this->fields ?? false) {
			$retval .= "<div class='form-submit'><input type=submit name=action></div>";
		}
		$retval .= "</table>";
		if ($display == true) print $retval;

		return $retval;
	}
	function on_render_admin_page($app)
	{

		$page = $app['page'] ?? "list";
		$app['form_id'] = $_POST['form_id'] ?? $_GET['form_id'] ?? false;
		$forms = new FormController($this->config_object);
		$forms->set_handler($this->framework);
		$forms->set_field_types($this->available_fields);
		$forms->connect();
		switch ($page) {
			case 'update_form':
				return $forms->editForm($app);
				break;
			case 'delete_form':
				return $forms->deleteForm($app);
				break;
			case 'add_form':
				return $forms->addForm($app);
				break;
			case 'default':
			case 'list':
			default:
				return $forms->listForms($app);
		}
	}

	function on_render_page($app)
	{
		$retval = "";
		$formname = $app['id'] ?? "";
		if (!$formname || isset($app['fields'])) {
			$formdef = $app;
		} else {
			$site = $this->config['site'];
			//print_r($site['data']);
			$formdef = $site['data']['forms'][$formname] ?? [];
		}
		// print("<pre>");
		// print_r($formdef);exit;
		foreach ($formdef['fields'] ?? [] as $field_name => $field_def) {
			if ($field_def['source'] ?? "" == 'list') {
				//print_r($field_def);
				$source_list = $field_def['id'] ?? "";
				//print $source_list;
				if ($source_list) {
					$values = $this->config['site']['data']['lists'][$source_list] ?? [];
					$formdef['fields'][$field_name]['values'] = $values;
				}
			}
		}
		$this->setvars($formdef, $app);
		$retval = $this->render();


		return $retval;
	}
	function generate_input($data)
	{
		$data_type = $data['type'] ?? "select";
		$label = $data['label'] ?? "";
		$retval = "";
		//$label=$label?"label='$label'":"";
		$name = $data['name'] ?? "";
		$default = $data['default'] ?? "";
		$input_name = $name ? "name='$name'" : "";
		$input_id = $name ? "id='$name'" : "";
		if ($name && $label) {
			$retval .= "<label for='$name'>$label:</label>\n";
		}


		switch ($data_type) {
			case "select":
				$retval .= "<select $input_id $input_name>\n";
				foreach ($data['values'] ?? [] as $key => $value) {

					$selected = $key == $default ? "selected" : "";
					$retval .= "<option value='$key' $selected>$value</option>\n";
				}
				$retval .= "</select>";
				break;
			case "text":
			default:
				$retval .= "<input type='text' $input_id $input_name>";
		}
		return $retval;
	}
}
