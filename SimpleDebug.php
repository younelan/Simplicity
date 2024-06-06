<?php

class SimpleDebug {
    private $template_file = '';
    private $default_template = 'templates/debug.html';
    function setTemplate($var) {
        $this->template = $var;
    }
    function printArray($array, $depth = 0) {
        $output = '';
    
        foreach ($array as $key => $value) {
            $has_children = is_array($value);
    
            $output .= '<div class="array-item" data-depth="' . $depth . '">';
            $output .= '<details><summary class="array-key">' . htmlentities($key);
            $output .= '<button class="toggle-children-recursive-btn toggle-children-btn" onclick="toggleChildrenRecursive(this)">▼</button>';
            $output .= '</summary>';
            $output .= '<div class="details-content">';
    
            // Display scalar values if they don't have children
            if (!$has_children) {
                $output .= '<div class=child-key>' . htmlentities($key) . '</div>:<div class=child-value> ' . htmlentities($value) . '</div></div>';
            }
    
            // Recurse for children
            if ($has_children) {
                // Display non-array key-value pairs on one line
                $non_array_values = [];
                foreach ($value as $child_key => $child_value) {
                    if (!is_array($child_value)) {
                        $non_array_values[] = "<div class=child-key>" . htmlentities($child_key) . '</div><div class=child-value>' . htmlentities($child_value) . "</div>";
                        unset($value[$child_key]); // Remove non-array key-value pair
                    }
                }
                if (!empty($non_array_values)) {
                    $output .= '<div class="array-value">' . implode(' ', $non_array_values) . '</div>';
                }
    
                // Recurse for child arrays
                $output .= $this->printArray($value, $depth + 1); // Recursive call with increased depth
            }
    
            $output .= '</div></details>';
            $output .= '</div>';
        }
        $template_file =$this->template_file;
        if(!$template_file) {
            $template_file = __DIR__ . "/" . $this->default_template;
        }
        if ($depth === 0) {
            // Base case: include the HTML template
            $template = file_get_contents($template_file);
            $template = str_replace('{{$content}}', $output, $template);
            return $template;
        }
    
        return $output;
    }


}