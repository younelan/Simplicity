<?php

namespace Opensitez\Simplicity;

class FormField extends \Opensitez\Simplicity\Plugin
{
	protected $colspan = 1;
	protected $field_def = [];
	public $name = "";
	protected $errors = [];
	protected $value = "";
	protected $listvals = [];
	protected $label;
	protected $fields;
	protected $lang;
	protected $config_fields;
	

	function set_fields($field_def)
	{
		$this->fields = $field_def;
		$this->init($field_def);
		$this->config_fields = $field_def;
	}
	function get_i18n_value($str, $lang = false, $debug = false)
	{
		$i18n = $this->framework->get_component("i18n");
		return $i18n->get_i18n_value($str, $lang, $debug);
	}
	function init($field_def, $defaultlang = false)
	{
		$i18n = $this->framework->get_component("i18n");
		if (!$defaultlang) {
			$this->lang = $field_def["lang"] ?? "en";
		} else {
			$this->lang = $defaultlang;
		}

		//print "{$this->lang}<br/>\n";
		//print_r($this->field_def);
		$this->field_def = $field_def;
		$this->name = $field_def["name"] ?? "";
		//print "hi $this->name";

		if (isset($field_def["colspan"]) && $this->colspan) {
			$this->colspan = "colspan=" . $field_def["colspan"];
		} else {
			$this->colspan = "";
		}
		if (isset($this->field_def["default"])) {
			$this->value = $field_def["default"];
		} else {
			$this->value = "";
		}
		if (isset($this->field_def["labels"])) {
			$this->label = $i18n->get_i18n_value($this->field_def["labels"], $lang = $this->lang);
		} else
			$this->label = $this->name;

		if (isset($field_def["values"])) {
			$this->listvals = $this->field_def["values"];
		}
		//print $this->label;exit;
		$this->errors = [];
	}

	function render_label()
	{
		return $this->label;
	}
	function render_field()
	{
		return $this->value;
	}
	function render_error()
	{
		if (isset($this->errors)) {
			if (is_array($this->errors)) {
				$theError = "";
				foreach ($this->errors as $error) {
					$theError = "<font color=brown>" . $error . "</font><br/>\n";
				}
			} else {
				$theError = "<font color=brown>" . $this->errors . "</font>";
			}
		} else
			$theError = "";
		return $theError;
	}

	
	function validate()
	{
	}
	function process()
	{
	}
	function selectbox($Values, $SelName, $Default = "", $OptVars = "")
	{
		if ($SelName <> "") $SelName = "name=$SelName";
		$selBox = "<select $SelName $OptVars>";
		if ($Values) {
			foreach ($Values as $key => $value) {
				if (is_array($value)) {
					$str_value = $value[$this->lang] ?? "";
				} else {
					$str_value = $value;
				}
				if ($Default == $key)
					$selBox .= "<option value='$key' selected>$str_value</option>\n";
				else
					$selBox .= "<option value='$key'>$str_value</option>\n";
			}
		}
		$selBox .= "</select>";
		return $selBox;
	}
	function get_list($lists, $listname)
	{
	}
}

class Checkbox
{
	function render()
	{
	}
	function validate()
	{
	}
	function process()
	{
	}
}
