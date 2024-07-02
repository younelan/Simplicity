<?php
//now useless but eventually for plugin discovery
exit;

use Composer\Autoload\ClassLoader;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

// Autoload instance from Composer
/** @var ClassLoader $loader */
$loader = require(__DIR__ . '/autoload.php');

// Namespace of your plugins
$pluginNamespace = 'Opensitez\\Simplicity\\';

$plugins = [];

// Iterate through Composer's registered PSR-4 namespaces
foreach ($loader->getPrefixesPsr4() as $namespace => $paths) {
    //print_r($paths);print "<br/>$namespace - $pluginNamespace<hr>";

    // Check if the namespace matches your plugin namespace
    if ($namespace === $pluginNamespace) {
        // Iterate through the paths to find plugin classes
        foreach ($paths as $path) {
            //print "hello";
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path)
            );

            foreach ($iterator as $file) {
                //print "<p>$file";
                // Check if the file is a PHP class
                if ($file->isFile() && $file->getExtension() === 'php') {
                    // Get class name from file path
                    $class = $pluginNamespace . str_replace(
                        [$path, '/', '.php'],
                        ['', '\\', ''],
                        $file->getPathname()
                    );

                    // Check if the class exists and is instantiable
                    if (class_exists($class)) {
                        // Instantiate the plugin or add it to your plugins array
                        $plugin = new $class();

                        // Add the plugin to your collection
                        $plugins[] = $plugin;
                    }
                }
            }
        }
    }
}

// Now $plugins array contains instances of all discovered plugins
