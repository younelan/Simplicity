<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;

class BarChartBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "Bar Chart Block";
    public $description = "Renders simple bar charts using HTML/CSS";
    var $params = array('data' => [], 'title' => 'Chart', 'limit' => 10);
    private $graphid = 0;

    function on_event($event)
    {
        if ($event['type'] === MSG::PluginLoad) {
            $this->framework->register_type('blocktype', 'barchart');
            $this->framework->register_type('contentprovider', 'barchart');
        }
        parent::on_event($event);
    }

    function render($block_config)
    {
        $data = $block_config['data'] ?? [];
        $title = $block_config['title'] ?? 'Chart';
        $limit = $block_config['limit'] ?? 10;
        $graphId = $block_config['graphId'] ?? 'chart' . $this->graphid++;

        if (empty($data)) {
            return "<div class='bar-chart-error'>No data provided for bar chart</div>";
        }

        return $this->renderBarChart($graphId, $data, $title, $limit);
    }

    private function renderBarChart($graphId, $data, $title, $limit = 10)
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
        $output .= $this->buildSimpleBarChart($graphId, $top_labels);
        $output .= "</div>";
        $output .= "</div>";

        return $output;
    }

    private function buildSimpleBarChart($name, $data)
    {
        $maxValue = max($data);
        $colors = ["#1f77b4", "#ff7f0e", "#2ca02c", "#d62728", "#9467bd", "#8c564b", "#e377c2", "#7f7f7f", "#bcbd22", "#17becf"];
        
        $output = "";

        $output .= "<div class='bar-chart-container' id='{$name}'>";
        
        $colorIndex = 0;
        foreach ($data as $label => $value) {
            $percentage = ($maxValue > 0) ? ($value / $maxValue * 100) : 0;
            $color = $colors[$colorIndex % count($colors)];
            
            $output .= "<div class='bar-item'>";
            $output .= "<div class='bar-label' title='" . htmlentities($label) . "'>" . htmlentities(substr($label, 0, 12)) . "</div>";
            $output .= "<div class='bar-visual'>";
            $output .= "<div class='bar-fill' style='width: {$percentage}%; background-color: {$color};'></div>";
            $output .= "<div class='bar-value'>{$value}</div>";
            $output .= "</div>";
            $output .= "</div>";
            
            $colorIndex++;
        }
        
        $output .= "</div>";
        
        return $output;
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
