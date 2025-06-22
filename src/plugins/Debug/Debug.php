<?php

namespace Opensitez\Simplicity\Plugins;

//Debug Class with utility function
//pass through Debug to SimpleDebug, making it a plugin
//a bit ugly but it works to share the same debug object
//between classes

//require_once(__DIR__ . "/SimpleDebug.php");
class Debug extends \Opensitez\Simplicity\Plugin
{
    private $debug_object;
    public function __construct()
    {
        parent::__construct();
        $this->debug_object = new \Opensitez\Simplicity\SimpleDebug();
    }
    public function printArray($array)
    {

        if (!$this->debug_object) {
            $this->debug_object = new \Opensitez\Simplicity\SimpleDebug();
        }
        return $this->debug_object->printArray($array);
    }

    public function __call($method, $args)
    {
        if (!$this->debug_object) {
            $this->debug_object = new \Opensitez\Simplicity\SimpleDebug();
        }
        $this->debug_object->$method($args[0]);
    }
}
