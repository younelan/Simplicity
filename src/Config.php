<?php
    namespace Opensitez\Simplicity;

    class Config extends Base {
        private $settings = [];
        private $langs = [];
        private $default_config_files = [
            "defaults" => "defaults.json",
            "palettes" => "palettes.json",
            "vars" => "vars.json",
            "auth" => "auth.json"
        ];

        function __construct($config = [])
        {
            //print_r($config);exit;
            parent::__construct();
            $this->settings = $config;
            $this->on_init();
        }
        public function on_init() {
            $this->load_primary_config();
            $this->setWebRoot();
            $this->set_default_language();
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
            $this->set_default_language();
            print_r($this->settings);
            return false;
        }
        function set_default_language($lang = false)
        {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                foreach (explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? []) as $lang_str) {
                    $lang_split = explode(";", $lang_str);
                    $lang = $lang_split[0];
                    $weight = $lang_split[1] ?? 0;
                    if (strstr($weight, "=")) {
                        $weight = explode("=", $weight)[1];
                    }
                    $weight = floatval($weight);
                    $lang = strtolower(substr(trim($lang), 0, 2));
                    if (ctype_alnum($lang)) {
                        $this->settings['langs'][$lang] = ["id" => $lang, "weight" => $weight];
                    }
                }
            }
            $current_lang = $this->getDefaultLang();
            $this->settings['site']['default-lang'] = $current_lang;
        }
        function getDefaultLang()
        {
            $default_lang = 'en';
            $current_score = 0;
            $lang_keys = array_keys($this->settings['langs'] ?? []);
            $default_lang = array_shift($lang_keys);
            foreach ($this->settings['langs'] as $lang => $details) {
                $lang_score = $details['weight'] ?? 0;
                if ($lang_score > $current_score) {
                    $default_lang = $lang;
                    $current_score = $lang_score;
                }
            }
            return $default_lang;
        }
        function load_primary_config()
        {
            $paths = $this->settings['paths'] ?? [];
            $simplicity_path = __DIR__;
            // $config_file = $this->config_file ?? $paths['base'] . "/local/config/config.yaml" ?? "";
            $settings = $this->settings;
            foreach ($this->default_config_files as $key => $default_file) {
                $fname = $simplicity_path . "/defaults/" . $default_file;
                $file_values = json_decode(file_get_contents($fname), true);
                $current = $this->get($key, []);
                if ($file_values) {
                    $merged = array_replace_recursive($current, $file_values);
                    $this->settings[$key] = $merged;
                }

            }
            //print_r($this->settings);exit;
            //$this->settings['defaults'] = $defaults;
        }
        /**
         * Get a configuration value by key
         * Supports nested keys using dot notation (e.g., 'site.name')
         * @param string $key The configuration key (e.g., 'site.name')
         * @param mixed $default The default value to return if the key does not exist
         * @return mixed The configuration value or the default value if not found
         */
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
        /**
         * Set a configuration key to a value
         * If the key does not exist, it will be created
         * @param string $key The configuration key (e.g., 'site.name')
         * @param mixed $value The value to set (can be an array or a single value)
         */
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
        /**
         * Merge a value into an existing configuration key
         * If the key does not exist, it will be created
         * @param string $key The configuration key (e.g., 'site.name')
         * @param mixed $value The value to merge (can be an array or a single value)
         */
        public function merge(string $key, $value): void {
            $currentValue = $this->get($key, []);
            if (!is_array($currentValue)) {
                $currentValue = [];
            }
            if (is_array($value)) {
                $mergedValue = array_replace_recursive($currentValue, $value);
            } else {
                $mergedValue = $currentValue;
                $mergedValue[] = $value; // Append non-array values
            }
            $this->set($key, $mergedValue);
        }
        /**
         * Check if a configuration key exists
         * @param string $key The configuration key (e.g., 'site.name')
         * @return bool True if the key exists, false otherwise
         */
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
     * This method sets the 'paths.webroot' configuration
     * @return void
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