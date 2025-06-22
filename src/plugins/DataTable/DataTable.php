<?php

namespace Opensitez\Simplicity\Plugins;

class DataTable extends \Opensitez\Simplicity\Plugin
{
    public $name = "Link Table";
    public $description = "Implements a table of links";
    var $params = array('block' => "index.txt", 'path' => '.');
    private $debug_style = '<style>
        .datagrid {
            display: grid;
            gap: 0px;
            width: 100%;
        }
        .header, .row {
            display: contents;
        }
        .header { font-weight: bold;}
        .cell {
            padding: 8px;
            text-align: left;
        }
        .row:hover .cell {
            background-color: #f2f2f2;
        }
        .row:active .cell {
            background-color: #dcdcdc;
        }  
        </style>
        ';
    function on_render_page($app)
    {
        $results = $app['values'] ?? [];
        $columns = $app['columns'] ?? [];
        $output = "";
        if (!empty($results)) {

            if (!$columns) {
                $columns = array_keys($results[array_key_first($results)]);
            }
            $output .= $this->debug_style;
            $num_cols = count($columns);
            $output .= "<style> .datagrid { grid-template-columns: repeat($num_cols, 1fr); } </style>";
            $output .= "<div class='datagrid'>\n";

            $output .= "<div class='header'>\n";
            //$headers = array_keys(array_key_first($results));
            foreach ($columns as $header) {
                $output .= '<div class="cell">' . htmlspecialchars($header) . "</div>\n";
            }
            $output .= '</div>';
            // Print rows
            foreach ($results as $idx => $row) {
                $output .= "<div class='row'>";
                $idx = 0;
                foreach ($columns as $key) {
                    $class = $key === 'id' ? 'id-cell' : '';
                    if ($idx == 0) {
                        $class = 'firstcol';
                        $idx++;
                    }
                    $output .= "<div class='" . $class . "'>" . htmlspecialchars($row[$key] ?? "") . "</div>\n";
                }
                $output .= "</div>\n";
            }
            $output .= "</div>\n";
        } else {
            $output .= 'No results found.';
        }
        //print $output;exit;

        return $output;
    }

    // public function on_render_page($params){
    //     $this->params = $params;
    //     $output="";
    //     $title=$this->params['title'];
    //     $site_path=$this->params['path'];
    //     $rows = params['values']??[];
    //     if($rows) {
    //         $column_headers = array_keys($results[0]);
    //         $columns = array_combine($column_headers, $column_headers);
    //         if(isset($columns['icon']))
    //             unset($columns['icon']);
    //         if(isset($columns['url']))
    //             unset($columns['url']);
    //         // $output .= $this->debug_style;
    //         $output .= '<table>';
    //         $output .= '<tr>';
    //         foreach ($column_headers as $header) {
    //             $output .= '<th>' . htmlspecialchars($header) . '</th>';
    //         }
    //         $output .= '</tr>';

    //         foreach($rows as $row) {
    //             $output .= '<tr>';
    //             $icon = $row['icon'] ?? '';
    //             $url = $row['url'] ?? '';
    //             $output .= '<td class=tdicon align=center><img src=$icon></td>';

    //             foreach($row as $key=>$value) {
    //                 $output .= '<td>' . htmlspecialchars($value) . '</td>';
    //             }
    //                     $filecount++;
    //                     ($filecount%2)?$style="class='tdark'":$style="class='tlight'";

    //                     $output .= "\n<tr $style><td class=tdicon align=center><img src=$icon></td>
    //                         <td><a href='$url'>$name</a></td>
    //                         <td>$description</td></tr>";
    //                 }
    //             }
    //         }

    //     }
    //     $page_source=file_get_contents("$site_path/" . $this->params['block']);
    //     $page_source=trim($page_source);
    //     $lines=explode("\n",$page_source);
    //     $filecount=0;
    //     $icon="/images/world.png";
    //     $output .= "<table>";
    //     $output .= "</table>";
    //     return $output;
    // }
}
