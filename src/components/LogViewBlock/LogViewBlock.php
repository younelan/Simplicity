<?php

namespace Opensitez\Simplicity\Components;

use Opensitez\Simplicity\MSG;

class LogViewBlock extends \Opensitez\Simplicity\Component
{
    public $name = "Log View Block";
    public $description = "Renders log entries in a formatted table";
    var $params = array('log_entries' => '', 'columns' => [], 'filter_count' => 0, 'color_cycle' => []);

    function on_event($event)
    {
        if ($event['type'] === MSG::onComponentLoad) {
            $this->framework->register_type('blocktype', 'logview');
            $this->framework->register_type('contentprovider', 'logview');
        }
        parent::on_event($event);
    }

    function render($block_config, $options = [])
    {
        $log_entries = $block_config['log_entries'] ?? '';
        $columns = $block_config['columns'] ?? [];
        $filter_count = $block_config['filter_count'] ?? 0;
        $color_cycle = $block_config['color_cycle'] ?? ["darkblue","black","blue","green","darkgreen","darkred","red","orange"];

        if (empty($log_entries) && $filter_count === 0) {
            return "<div class='log-view-error'>No log data provided</div>";
        }

        return $this->renderLogView($log_entries, $columns, $filter_count, $color_cycle);
    }

    private function renderLogView($log_entries, $columns, $filter_count, $color_cycle)
    {
        $output = $this->renderColumnHeaders($columns, $color_cycle);
        
        if ($filter_count > 1) {
            $output .= "<p>" . $filter_count . " lines filtered</p>";
        }

        // Use template if available, otherwise just output the log entries directly
        if (class_exists('\Opensitez\Simplicity\SimpleTemplate')) {
            $template = new \Opensitez\Simplicity\SimpleTemplate();
            $templatePath = __DIR__ . '/../../templates/log.tpl';
            
            if (file_exists($templatePath)) {
                $template->setFile($templatePath);
                $variables = [
                    'LOG_ENTRIES' => $log_entries
                ];
                $template->setVars($variables);
                return $output . $template->render();
            }
        }
        
        // Fallback if template doesn't exist
        return $output . $log_entries;
    }

    private function renderColumnHeaders($columns, $color_cycle)
    {
        $coloredLine = "";
        $idx = 0;
        
        foreach($columns as $col_id => $col_name) {
            $idx++;
            $color = $color_cycle[$idx % count($color_cycle)];
            $display_name = is_array($col_name) ? $col_name[1] ?? $col_name[0] ?? $col_id : $col_name;
            $coloredLine .= "<font color=" . $color . ">" . htmlentities(trim($display_name)) . "</font>&nbsp;\t\n";
        }
        
        return $coloredLine . "<br/>";
    }

    public function on_render_page($app = [])
    {
        $this->app = $app;
        return $this->render($app);
    }
}
