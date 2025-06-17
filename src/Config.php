<?php
    namespace Opensitez\Simplicity;

    class Config {
        private $settings = [];

        function __construct(&$config = [])
        {
            $this->settings = $config;
            //$this->on_init();
        }

        public function load(string $yamlFile): bool {
            if (!file_exists($yamlFile)) {
                return false;
            }
            
            try {
                $yamlData = Spyc::YAMLLoad($yamlFile);
                if (is_array($yamlData)) {
                    $this->settings = array_replace_recursive($this->settings, $yamlData);
                    return true;
                }
            } catch (Exception $e) {
                // Handle YAML parsing errors silently or log them
                return false;
            }
            
            return false;
        }

        public function get(string $key, $default = null) {
            $keys = explode('.', $key);
            $value = $this->settings;
            
            foreach ($keys as $k) {
                if (!is_array($value) || !isset($value[$k])) {
                    return $default;
                }
                $value = $value[$k];
            }
            
            return $value;
        }

        public function set(string $key, $value) {
            $keys = explode('.', $key);
            $current = &$this->settings;
            
            foreach ($keys as $k) {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
            
            $current = $value;
        }
        public function has(string $key): bool {
            $keys = explode('.', $key);
            $current = $this->settings;
            
            foreach ($keys as $k) {
                if (!is_array($current) || !isset($current[$k])) {
                    return false;
                }
                $current = $current[$k];
            }
            
            return true;
        }

    }