<?php

namespace Opensitez\Simplicity\Plugins;

require_once(__DIR__ . "/FormModel.php");
class FormController extends \Opensitez\Simplicity\Plugin
{
    private $formManager;
    private $field_types;
    public function set_field_types($field_types)
    {
        $this->field_types = $field_types;
    }
    public function connect()
    {
        //print_r($this->config);exit;
        $this->formManager = new FormModel($this->config_object);
        $this->formManager->set_handler($this->framework);
        $this->formManager->connect();
    }
    function addForm($app = [])
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $siteId = $app['site_id']?? false;
            if (!$siteId) {
                print "Invalid site ID";
            }
            $formLanguage = 'en';
            $formStatus = 1;
            $formTheme = "bootstrap";
            $formOwner = "youness";
            $formName = $_POST['form_name'] ?? "";
            $formDomain = $_POST['form_domain'] ?? "";
            $formDescription = $_POST['form_description'];
            $formDefinition = [];

            $form = [
                'form_name' => $formName,
                'site_id' => $siteId,
                'form_owner' => $formOwner,
                'form_description' => $formDescription,
                'form_status' => $formStatus,
                'definition' => $formDefinition ,
            ];
            $lastid = $this->formManager->addForm($form);
            //$lastid = $dbh->lastInsertId();
            if ($lastid) {
                return $this->editForm(['form_id' => $lastid]);
            }
        }
        $data = [
            'form_header' => 'Add Form',
            'button_header' => 'Add Form',
            'form_name' => '',
            'message_class' => '',
            'form_domain' => '',
            'form_action' => '?plugin=form&page=add_form',
            'form_description' => '',
            'form_editor' => '',
        ];
        $masterTemplate = file_get_contents(__DIR__ . '/views/form_add_form.html');
        return $this->substitute_vars($masterTemplate, $data);
    }
    function deleteForm($app = [])
    {
        $form_id = intval($app['form_id']);
        if (!$form_id ?? false) {
            return "Invalid form ID";
        }
        $this->formManager->deleteForm($form_id);
        return $this->listForms();
    }
    function collect_fields($fields, $prefix = 'field')
    {
        $original_idx = 0;
        $processedFields = [];
        $original_order = [];
        foreach ($fields as $key => $field) {
            // Check if the key follows the expected pattern
            if (preg_match('/^' . $prefix . '_(\d+)_(\w+)$/', $key, $matches)) {
                $idx = $matches[1];
                $name = $matches[2];
                if (!isset($original_order[$idx])) {
                    $processedFields[$idx]['idx'] = $idx;
                    $processedFields[$idx]['order'] = $original_idx;

                    $original_order[$idx] = $original_idx;
                    $original_idx++;
                }
                // Initialize the array for this index if it doesn't exist
                if (!isset($processedFields[$idx])) {
                    $processedFields[$idx] = [];
                }

                // Add the field to the processedFields array
                $processedFields[$idx][$name] = $field;
            }
        }

        $sorted_array = [];
        $idx = 0;
        //order in the edit form isn't the one we really want
        foreach ($processedFields as $idx => $processedField) {
            $sorted_array[] = $processedField;
        }

        return $sorted_array;
    }
    function process_fields($fields, $formDefinition)
    {
        $processedFields = [];
        foreach ($formDefinition as $idx => $field) {
            $processedFields[$idx] = $field;
        }
        return $processedFields;
    }
    function editForm($app = [])
    {
        $defaultform = [
            [
                'type' => 'option', 'name' => 'option1', 'label' => 'Option 1',
                'options' => [
                    ['label' => 'Option 1', 'value' => 'Value 1'],
                    ['label' => 'Option 2', 'value' => 'Value 2']
                ],
            ],
            ['type' => 'text', 'name' => 'text1', 'label' => 'Text 1', 'defaultValue' => '', 'enabled' => true],
            ['type' => 'checkbox', 'name' => 'checkbox1', 'label' => 'Checkbox 1', 'enabled' => false],
            ['type' => 'textarea', 'name' => 'textarea1', 'label' => 'Textarea 1', 'value' => '', 'enabled' => true]
        ];
        if (!$app['form_id'] ?? false) {
            return "Invalid FormID";
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fields = $this->collect_fields($_POST);

            //print_r($fields);exit;
            $formId = $app['form_id'];
            $formName = $_POST['form_name'];
            $formDomain = $_POST['form_domain'] ?? 1;
            $formStatus = $_POST['form_status'] ?? 'inactive';
            $formDefinition = json_encode([
                "fields" => $fields,
            ]);
            $formDescription = $_POST['form_description'] ?? "";
            $data = [
                "form_id" => $formId,
                "form_name" => $formName ?? "",
                "form_description" => $formDescription ?? "",
                "form_status" => $formStatus ?? "active",
                "form_definition" => $formDefinition,
            ];

            $this->formManager->updateForm($data);
        }
        //print_r(json_encode($defaultform));exit;
        $form = $this->formManager->getFormById($app['form_id']);
        $formDefinition = json_decode($form['form_definition'], true) ?? [];
        $formFields = $formDefinition['fields'] ?? [];
        //print_r($formDefinition);exit;
        //validate needed
        //$formFields=json_encode($formFields);
        $editorData = [
            'predefined_elements' => json_encode($formFields),
            'testdata' => 'hello',
        ];
        $formEditor = file_get_contents(__DIR__ . '/views/form_editor.html');
        $formEditor = $this->substitute_vars($formEditor, $editorData);
        $data = [
            'form_header' => 'Edit form',
            'form_action' => '?plugin=form&page=update_form&form_id=' . intval($app['form_id']),
            'button_header' => 'Update form',
            'form_name' => $form['form_name'],
            'message_class' => '',
            'form_editor' => $formEditor,
            'form_domain' => $form['form_domain'] ?? 1,
            'form_description' => $form['form_description']
        ];
        $masterTemplate = file_get_contents(__DIR__ . '/views/form_add_form.html');
        return $this->substitute_vars($masterTemplate, $data);
    }

    function listForms($app = [])
    {
        // Load master template from file
        $data = [
            'message' => $app['message'] ?? '',
            'rows' => '',
            'add_link' => '?plugin=form&page=add_form',
        ];
        $masterTemplate = file_get_contents(__DIR__ . '/views/form_list_master.html');
        // Load form template from file
        $formTemplate = file_get_contents(__DIR__ . '/views/form_list_row.html');
        $rows = "";
        // Get forms from the database
        $forms = $this->formManager->getForms();
        foreach ($forms as $form) {
            $rows .= $this->substitute_vars($formTemplate, $form);
        }
        $data['rows'] = $rows;


        return $this->substitute_vars($masterTemplate, $data);
    }
}
