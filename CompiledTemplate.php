<?php
class PHPTemplate {
    private $template;
    private $data = [];

    public function __construct($template) {
        $this->template = $template;
    }

    public function assign($variable, $value) {
        $this->data[$variable] = $value;
    }

    public function render() {
        $output = $this->renderTemplate($this->template, $this->data);
        eval(' ?>' . $output . '<?php ');
    }

    private function renderTemplate($template, $data) {
        $output = preg_replace_callback('/{{(.*?)}}(.*?){{\/\1}}/s', function ($matches) use ($data) {
            $blockName = trim($matches[1]);
            $blockContent = $matches[2];
            print_r(array_keys($data));
            if (isset($data[$blockName])) {
                if (is_array($data[$blockName])) {
                    $nestedContent = '';
                    foreach ($data[$blockName] as $nestedData) {
                        $nestedContent .= $this->renderTemplate($blockContent, $nestedData);
                    }
                    return $nestedContent;
                } else {
                    return $this->renderTemplate($blockContent, $data[$blockName]);
                }
            } else {
                return '';
            }
        }, $template);

        $output = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($data) {
            $variable = trim($matches[1]);

            // Split variable into parts for nested data access
            $variableParts = explode('.', $variable);
            $value = $data;

            foreach ($variableParts as $part) {
                if (isset($value[$part])) {
                    $value = $value[$part];
                } else {
                    $value = '';
                    break;
                }
            }

            return $value;
        }, $output);

        return $output;
    }
}

$template = new PHPTemplate(file_get_contents('restaurant.html'));

// Assign variables
$template->assign('pagetitle', 'Restaurant Page');
$template->assign('restaurant', [
    'restaurant.name' => 'Luigi\'s Pizzeria',
    'address' => [
        [
            'city' => 'Los Angeles',
            'state' => 'CA',
            'street' => '123 Main St',
        ],
        [
            'city' => 'New York',
            'state' => 'NY',
            'street' => '456 Elm St',
        ],
    ],
]);

// Render the template
$template->render();
