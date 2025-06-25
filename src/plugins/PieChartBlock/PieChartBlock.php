<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class PieChartBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "Pie Chart Block";
    public $description = "Renders pie charts using D3.js";
    var $params = array('data' => [], 'title' => 'Chart', 'limit' => 10);
    private $graphid = 0;

    function on_event($event)
    {
        if ($event['type'] === MSG::PluginLoad) {
            $this->plugins->register_type('blocktype', 'piechart');
            $this->plugins->register_type('contentprovider', 'piechart');
        }
        parent::on_event($event);
    }

    function render($block_config, $options = [])
    {
        $data = $block_config['data'] ?? [];
        $title = $block_config['title'] ?? 'Chart';
        $limit = $block_config['limit'] ?? 10;
        $graphId = $block_config['graphId'] ?? 'chart' . $this->graphid++;

        if (empty($data)) {
            return "<div class='pie-chart-error'>No data provided for pie chart</div>";
        }

        return $this->renderPieChart($graphId, $data, $title, $limit);
    }

    private function renderPieChart($graphId, $data, $title, $limit = 10)
    {
        // Limit to top N labels and aggregate others
        arsort($data);
        if (count($data) > $limit) {
            $top_labels = array_slice($data, 0, $limit, true);
            $others = array_slice($data, $limit, null, true);
            $others_total = array_sum($others);
            if ($others_total > 0) {
                $top_labels["Others"] = $others_total;
            }
        } else {
            $top_labels = $data;
        }

        $output = "<div class='graph'>";
        $output .= "<div class='graph-content'>";
        $output .= "<h3 class='graph-title'>" . htmlentities($title) . "</h3>";
        $output .= $this->d3pie($graphId, $top_labels);
        $output .= "</div>";
        $output .= "</div>";

        return $output;
    }

    private function d3pie($name, $data)
    {
        $template = new \Opensitez\Simplicity\SimpleTemplate();
        $templatePath = __DIR__ . '/../../templates/graph.tpl';
        $template->setFile($templatePath);
        
        // Calculate total for percentages
        $total = array_sum($data);
        
        // Build data list HTML
        $dataList = '';
        foreach($data as $key=>$value) {
            $dataList .= "<div style=\"display:block\"><span style='font-weight:bold;color:#e1b698;text-shadow: 2px 1px black;display:inline-block;'>" . htmlentities($key) . "</span> : &nbsp; <span style='color:#aaa;'>$value</span></div>\n";
        }
        
        // Build dataset for D3
        $dataset = '';
        foreach($data as $key=>$value) {
            $dataset .= "{ label: '" . htmlentities($key) . "', count: " . intval($value*360/$total) . "} , \n";
        }
        
        $variables = [
            'GRAPH_ID' => $name,
            'DATA_LIST' => $dataList,
            'DATASET' => $dataset
        ];
        
        $template->setVars($variables);
        return $template->render();
    }

    public function on_render_page($app = [])
    {
        $this->app = $app;
        return $this->render($app);
    }
}
