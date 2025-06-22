<?php

namespace Opensitez\Simplicity\Plugins;

use Opensitez\Simplicity\MSG;
use \QRCode;

class QRCodeBlock extends \Opensitez\Simplicity\Plugin
{
    public $name = "QRCodeBlock";
    public $description = "Renders QR codes";

    function on_event($event)
    {
        if ($event['type'] === MSG::PluginLoad) {
            $this->plugins->register_type('blocktype', 'qrcode');
        }
        parent::on_event($event);
    }

    function render($text, $options = [])
    {
        $qr = new QRCode(null);
        return $qr->render_page();
    }
}