<?php

namespace Opensitez\Simplicity\Components;

use Opensitez\Simplicity\MSG;
// use \SimpleQRCode;

class QRCodeBlock extends \Opensitez\Simplicity\Component
{
    public $name = "QR Code Generator";
    public $description = "Implements an image gallery";
    var $params = array('block' => "index.txt", 'path' => '.');

    function on_event($event)
    {
        if ($event['type'] === MSG::onComponentLoad) {
            $this->framework->register_type('blocktype', 'qrcode');
        }
        parent::on_event($event);
    }

    function render($block_config, $options = [])
    {

        //print_r($block_config);exit;
        $output = "";
        //print_r($this->config);exit;
        $domain = $this->config_object->get('host');
        $routestr = $this->config['app']['route'] ?? "";
        if ($routestr == "default")
            $routestr = "";
        $defaultqr = "http://" . $domain . "/" . $routestr;

        $qrurl = $this->config['current']['content'] ?? $defaultqr;
        // $d=url;
        $options = ["s" => "qr", "w" => 200, "h" => 200, "p" => 5];
        $generator = new \Opensitez\Simplicity\SimpleQRCode($qrurl, $options);
        $image = $generator->render_image();
        ob_start();
        imagepng($image);
        $outputimage = ob_get_clean();

        $output .= "<img src='data:image/png;base64," .
            base64_encode($outputimage) . "'>";

        imagedestroy($image);

        return $output;
    }
    public function on_render_page($app = [])
    {
        $this->app = $app;
        return $this->render($app);
    }

}