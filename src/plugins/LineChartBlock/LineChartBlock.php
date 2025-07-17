<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class LineChartBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "Line Chart Block";
    public $description = "Renders line charts using the same template structure as other charts";
    var $params = array('data' => [], 'title' => 'Chart', 'limit' => 10, 'xlabel' => '', 'ylabel' => '');
    private $graphid = 0;

    function on_event($event)
    {
        if ($event['type'] === MSG::onComponentLoad) {
            $this->framework->register_type('blocktype', 'linechart');
            $this->framework->register_type('contentprovider', 'linechart');
        }
        parent::on_event($event);
    }

    function render($block_config)
    {
        $data = $block_config['data'] ?? [];
        // print_r($block_config); // Debugging line to check data structure
        // exit;
        $title = $block_config['title'] ?? 'Chart';
        $limit = $block_config['limit'] ?? 10;
        $xlabel = $block_config['xlabel'] ?? '';
        $ylabel = $block_config['ylabel'] ?? '';
        $graphId = $block_config['graphId'] ?? 'chart' . $this->graphid++;

        if (empty($data)) {
            return "<div class='line-chart-error'>No data provided for line chart</div>";
        }

        return $this->renderLineChart($graphId, $data, $title, $limit, $xlabel, $ylabel);
    }

    private function renderLineChart($graphId, $data, $title, $limit = 10, $xlabel = '', $ylabel = '')
    {
        // For line charts, don't sort by value - keep original order for proper line progression
        if (count($data) > $limit) {
            $top_labels = array_slice($data, 0, $limit, true);
        } else {
            $top_labels = $data;
        }

        $output = "<div class='graph'>";
        $output .= "<div class='graph-content'>";
        $output .= "<h3 class='graph-title'>" . htmlentities($title) . "</h3>";
        $output .= $this->d3line($graphId, $top_labels, $xlabel, $ylabel);
        $output .= "</div>";
        $output .= "</div>";

        return $output;
    }

    private function d3line($name, $data, $xlabel = '', $ylabel = '')
    {
        $template = new \Opensitez\Simplicity\SimpleTemplate();
        $templatePath = __DIR__ . '/../../templates/linechart.tpl';
        $template->setFile($templatePath);
        //print_r($data); // Debugging line to check data structure
        
        // Build data list HTML with color indicators
        $colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'];
        $dataList = '';
        $colorIndex = 0;
        
        // Check if this is 2D array data or simple x,count data
        $is2DArray = false;
        foreach($data as $value) {
            if (is_array($value)) {
                $is2DArray = true;
                break;
            }
        }
        
        if ($is2DArray) {
            // Handle 2D array data (x => [y => count])
            $chartData = [];
            $yValues = []; // Track unique Y values for positioning
            
            foreach($data as $x_val => $y_data) {
                foreach($y_data as $y_val => $count) {
                    // Track unique Y values for sequential positioning
                    if (!in_array($y_val, $yValues)) {
                        $yValues[] = $y_val;
                    }
                    
                    $color = $colors[$colorIndex % count($colors)];
                    $dataList .= "<div class=\"graph-data-entry\"><span class=\"graph-data-color\" style=\"background-color: {$color};\"></span><span class=\"graph-data-label\">" . htmlentities($x_val . ' → ' . $y_val) . "</span> : &nbsp; <span class=\"graph-data-value\">$count</span></div>\n";
                    $colorIndex++;
                }
            }
            
            // Convert to chart data - group by X values and sum counts
            $synonyms = $data['synonyms'] ?? [];
            print_r($synonyms); // Debugging line to check synonyms
            exit;
            foreach($data as $x_val => $y_data) {
                $total_for_x = array_sum($y_data);
                $chartData[] = ['x' => $x_val, 'y' => $total_for_x];
            }
            
        } else {
            // Handle simple x,count data
            $chartData = [];
            
            // Sort by x value
            ksort($data);
            
            foreach($data as $x_val => $count) {
                $chartData[] = ['x' => $x_val, 'y' => $count];
                
                $color = $colors[$colorIndex % count($colors)];
                $dataList .= "<div class=\"graph-data-entry\"><span class=\"graph-data-color\" style=\"background-color: {$color};\"></span><span class=\"graph-data-label\">" . htmlentities($x_val) . "</span> : &nbsp; <span class=\"graph-data-value\">$count</span></div>\n";
                $colorIndex++;
            }
        }
        
        // Sort chart data by x value
        usort($chartData, function($a, $b) {
            return strcmp($a['x'], $b['x']);
        });
        
        // Build dataset for line chart
        $dataset = '';
        foreach($chartData as $point) {
            $dataset .= "{ x: '" . htmlentities($point['x']) . "', y: " . intval($point['y']) . "} , \n";
        }
        
        $variables = [
            'GRAPH_ID' => $name,
            'DATA_LIST' => $dataList,
            'DATASET' => $dataset,
            'X_LABEL' => htmlentities($xlabel),
            'Y_LABEL' => htmlentities($ylabel)
        ];
        
        $template->setVars($variables);
        return $template->render();
    }

    public function isAvailable()
    {
        return true;
    }

    public function on_render_page($app = [])
    {
        $this->app = $app;
        return $this->render($app);
    }
}
