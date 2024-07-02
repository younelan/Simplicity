<?php

namespace Opensitez\Simplicity;

class SimpleDebug
{
    private $template_file = '';
    private $debug_style = '<style>
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px;}
        th, td { padding: 8px 12px; border: 1px solid #cdbcbc; text-align: left; }
        th { background-color: #4f4837; color: white; }
        tr:nth-child(odd) { background-color: #e5e0d3; }
        .firstcol { color: #c96666; font-weight: bold; }
        tr:nth-child(even) { background-color: #fffdf3; }
        .id-cell { font-weight: bold; color: #ff0000; }
        .tabledetails  {text-align: center; background-color:  #4f4837;color: white}
      </style>';
    private $default_template = 'templates/debug.html';
    function setTemplate($var)
    {
        $this->template = $var;
    }
    function printArray($array, $depth = 0)
    {
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
        $template_file = $this->template_file;
        if (!$template_file) {
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
    /* debug a query by printing results as a table */
    function printQueryResults($results)
    {
        if (!empty($results)) {
            echo $this->debug_style;
            echo '<table>';

            // Print headers
            echo '<tr>';
            $headers = array_keys($results[0]);
            foreach ($headers as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';

            // Print rows
            foreach ($results as $row) {
                echo '<tr>';
                $idx = 0;
                foreach ($headers as $key) {
                    $class = $key === 'id' ? 'id-cell' : '';
                    if ($idx == 0) {
                        $class = 'firstcol';
                        $idx++;
                    }
                    echo '<td class="' . $class . '">' . htmlspecialchars($row[$key]) . '</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo 'No results found.';
        }
    }

    /* simple function to highlight sql code to make it easier to read*/
    function highlightSql($query)
    {
        // Define CSS classes
        $keywordClass = 'sql-keyword';
        $stringClass = 'sql-string';
        $backtickClass = 'sql-backtick';
        $parenthesisClass = 'sql-parenthesis';
        $operatorClass = 'sql-operator';

        // Define SQL keywords
        $keywords = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'FROM', 'WHERE', 'AND', 'OR', 'NOT', 'NULL',
            'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'ON', 'AS', 'IN', 'IS', 'BY', 'GROUP', 'ORDER',
            'HAVING', 'LIMIT', 'OFFSET', 'UNION', 'DISTINCT', 'COUNT', 'AVG', 'MIN', 'MAX', 'SUM'
        ];
        $keywordsPattern = implode('|', array_map('preg_quote', $keywords));

        // Tokenize and highlight the SQL query
        $pattern = "/('(?:''|[^'])*'|\"(?:\"\"|[^\"])*\"|`[^`]*`|\b($keywordsPattern)\b|[()=])/i";
        $highlightedQuery = preg_replace_callback($pattern, function ($matches) use ($keywordClass, $stringClass, $backtickClass, $parenthesisClass, $operatorClass) {
            if (isset($matches[2])) {
                return '<span class="' . $keywordClass . '">' . htmlspecialchars($matches[2]) . '</span>';
            } elseif (preg_match("/^'.*'$/s", $matches[0]) || preg_match('/^".*"$/s', $matches[0])) {
                return '<span class="' . $stringClass . '">' . htmlspecialchars($matches[0], ENT_QUOTES) . '</span>';
            } elseif (preg_match("/^`.*`$/", $matches[0])) {
                return '<span class="' . $backtickClass . '">' . htmlspecialchars($matches[0], ENT_QUOTES) . '</span>';
            } elseif ($matches[0] === '(' || $matches[0] === ')') {
                return '<span class="' . $parenthesisClass . '">' . htmlspecialchars($matches[0]) . '</span>';
            } elseif ($matches[0] === '=') {
                return '<span class="' . $operatorClass . '">' . htmlspecialchars($matches[0]) . '</span>';
            } else {
                return htmlspecialchars($matches[0]);
            }
        }, $query);
        $highlightedQuery .= "\n<style>
            .sql-keyword { color: #0b0bc7; font-weight: bold; }
            .sql-string { color: #2eb92e; font-weight: bold; padding: 2px;padding-left:5px; padding-right:5px;background-color: #e9e9e9 }
            .sql-backtick { color: brown; }
            .sql-parenthesis { color: #ff1bff; font-weight: bold;}
            .sql-operator { color: #c73232  ; }
            </style>";
        return '<pre>' . $highlightedQuery . '</pre>';
    }
}
