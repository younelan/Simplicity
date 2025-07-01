<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class VBarChartBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "Vertical Bar Chart Block";
    public $description = "Renders vertical bar charts using the same template as pie charts";
    var $params = array('data' => [], 'title' => 'Chart', 'limit' => 10);
    private $graphid = 0;

    function on_event($event)
    {
        if ($event['type'] === MSG::PluginLoad) {
            $this->framework->register_type('blocktype', 'vbarchart');
            $this->framework->register_type('contentprovider', 'vbarchart');
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
            return "<div class='vbar-chart-error'>No data provided for bar chart</div>";
        }

        return $this->renderVBarChart($graphId, $data, $title, $limit);
    }

    private function renderVBarChart($graphId, $data, $title, $limit = 10)
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
        $output = '';
        $output .= "<div class='graph'>\n";
        $output .= "<div class='graph-content'>\n";
        $output .= "<h3 class='graph-title'>" . htmlentities($title) . "</h3>";
        $output .= $this->d3vbar($graphId, $top_labels);
        $output .= "\n</div>";
        $output .= "\n</div>\n";
        return $output;
    }

    private function d3vbar($name, $data)
    {
        $template = new \Opensitez\Simplicity\SimpleTemplate();
        $templatePath = __DIR__ . '/../../templates/vbarchart.tpl';
        $template->setFile($templatePath);
        
        // Build data list HTML with color indicators
        $colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'];
        $dataList = '';
        $colorIndex = 0;
        foreach($data as $key=>$value) {
            $color = $colors[$colorIndex % count($colors)];
            $dataList .= "<div class=\"graph-data-entry\"><span class=\"graph-data-color\" style=\"background-color: {$color};\"></span><span class=\"graph-data-label\">" . htmlentities($key) . "</span> : &nbsp; <span class=\"graph-data-value\">$value</span></div>\n";
            $colorIndex++;
        }
        
        // Build dataset for vertical bar chart
        $dataset = '';
        foreach($data as $key=>$value) {
            $dataset .= "{ label: '" . htmlentities($key) . "', value: " . intval($value) . "} , \n";
        }
        
        $variables = [
            'GRAPH_ID' => $name,
            'DATA_LIST' => $dataList,
            'DATASET' => $dataset
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
