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
        if ($event['type'] === MSG::PluginLoad) {
            $this->plugins->register_type('blocktype', 'linechart');
            $this->plugins->register_type('contentprovider', 'linechart');
        }
        parent::on_event($event);
    }

    function render($block_config, $options = [])
    {
        $data = $block_config['data'] ?? [];
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
        
        // Build data list HTML with color indicators
        $colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'];
        $dataList = '';
        $colorIndex = 0;
        foreach($data as $key=>$value) {
            $color = $colors[$colorIndex % count($colors)];
            $dataList .= "<div class=\"graph-data-entry\"><span class=\"graph-data-color\" style=\"background-color: {$color};\"></span><span class=\"graph-data-label\">" . htmlentities($key) . "</span> : &nbsp; <span class=\"graph-data-value\">$value</span></div>\n";
            $colorIndex++;
        }
        
        // Build dataset for line chart
        $dataset = '';
        foreach($data as $key=>$value) {
            $dataset .= "{ x: '" . htmlentities($key) . "', y: " . intval($value) . "} , \n";
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
