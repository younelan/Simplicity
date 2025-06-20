<?php
    namespace Opensitez\Simplicity;

    class Config extends Base {
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

        /**
     * Get the web root path based on current script location
     * @return string The web root path (e.g., '/impress/public' or '')
     */
    public function getWebRoot(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        $baseDir = dirname($scriptName);
        
        if ($baseDir === '/') {
            $baseDir = '';
        }

        return $baseDir;
    }

    /**
     * Set the web root path in the paths configuration
     */
    public function setWebRoot(): void
    {
        $webRoot = $this->getWebRoot();
        $this->set('paths.webroot', $webRoot);
    }
    /**
     * Merge YAML file content with existing config value
     * @param string $configPath The config path (e.g., 'site.definition')
     * @param string $filename The YAML file path
     * @return bool True if successful, false on error
     */
    public function mergeYaml(string $configPath, string $filename): bool
    {
        if (!file_exists($filename)) {
            return false;
        }
        
        $yamlContent = file_get_contents($filename);
        if ($yamlContent === false) {
            return false;
        }
        
        try {
            $yamlData = \Opensitez\Simplicity\Spyc::YAMLLoadString($yamlContent);
            if (!is_array($yamlData)) {
                return false;
            }
            
            // Get existing value or empty array
            $existingValue = $this->get($configPath, []);
            
            // Merge existing with YAML data
            $mergedValue = array_merge($existingValue, $yamlData);
            
            // Set the merged value
            $this->set($configPath, $mergedValue);
            
            return true;
        } catch (Exception $e) {
            // Log error if needed
            return false;
        }
    }

    }