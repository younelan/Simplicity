<?php
namespace Opensitez\Simplicity\Components;
use Opensitez\Simplicity\MSG;

class Layout extends \Opensitez\Simplicity\Component
{
    public $name = "Layout Provider";
    public $description = "Implements a layout provider for the framework.";
    public $app = [];
    function on_event($event)
    {
        switch ($event['type']) {
            case MSG::onComponentLoad:
                // Register this component as a route type handler for redirects
                $this->framework->register_type('layoutprovider', 'layout');
                break;
        }
        return parent::on_event($event);
    }
    function setLayout() {

    }
}