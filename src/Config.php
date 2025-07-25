<?php
    namespace Opensitez\Simplicity;

    class Config extends Base {
        private $settings = [];
        private $langs = [];
        private $default_config_files = [
            "defaults" => "defaults.json",
            "system.palettes" => "palettes.json",
            "vars" => "vars.json",
            "translations" => "translations.json"
        ];

        function __construct($config = [])
        {
            //print_r($config);exit;
            //parent::__construct($config);
            $this->settings = $config;
            $this->setDefaultLanguage();

            $this->on_init();
        }
        public function on_init() {
            $this->loadPrimaryConfig();
            $this->setTranslations();
            
            $this->setSiteVars();
            $this->setWebRoot();
            $this->setDefaultLanguage();
            
            $this->setSimplicityPaths();
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
        function setDefaultLanguage($lang = false)
        {

            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                foreach (explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? []) as $lang_str) {
                    $lang_split = explode(";", $lang_str);
                    $lang = $lang_split[0];
                    $weight = $lang_split[1] ?? 1;
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
            $this->settings['site']['accepted-langs'] = $this->settings['langs'] ?? [];
        }
        function getDefaultLang()
        {
            $default_lang = 'en';
            $current_score = 0;
            // if( isset($this->settings['langs'])) {
            //     $this->setDefaultLanguage();
            // }
            $lang_keys = array_keys($this->settings['langs'] ?? []);
            $default_lang = array_shift($lang_keys);
            foreach ($this->settings['langs'] ?? [] as $lang => $details) {
                $lang_score = $details['weight'] ?? 0;
                if ($lang_score > $current_score) {
                    $default_lang = $lang;
                    $current_score = $lang_score;
                }
            }
            return $default_lang;
        }
        function setTranslations()
        {
            $translations = $this->settings['translations'] ?? [];
            if (!isset($translations['en'])) {
                $keys = array_keys($translations['fr'] ?? []);
                $translations['en'] = array_combine($keys, $keys);
            }
            $this->settings['translations'] = $translations;
        }
        /**
         * Get the current domain
         * @return string The domain (e.g., "localhost", "example.com")
         */
        // public function getDomain(): string
        // {
        //     $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        //     $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            
        //     return $host;
        // }

        function loadPrimaryConfig()
        {
            $paths = $this->settings['paths'] ?? [];
            $simplicity_path = __DIR__;
            $settings = $this->settings;
            foreach ($this->default_config_files as $key => $default_file) {
                $fname = $simplicity_path . "/defaults/" . $default_file;
                $file_values = json_decode(file_get_contents($fname), true);
                $current = $this->get($key, []);
                if ($file_values) {
                    $merged = array_replace_recursive($current, $file_values);
                    $this->merge($key, $merged);
                }

            }

        }
        /**
         * Set site variables from the configuration
         * This method sets the 'site.vars' configuration
         * @return void
         */
        public function setSiteVars(): void
        {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

            $this->settings['site']['protocol'] = $protocol;
            $this->settings['site']['host'] = $host;
        }
        public function setSimplicityPaths(): void 
        {
            $basedir = __DIR__;
            $this->settings['system']['paths'] = [
                'base' => $basedir,
                'components' => $basedir . '/components',
                'templates' => $basedir . '/templates',
                'palettes' => $basedir . '/templates/css',
                'themes' => $basedir . '/themes',
                        'assets' => $basedir . '/assets',
            ];
            if(!isset($this->settings['system']['debug'])) {
                $this->settings['system']['debug'] = false;
            }
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
     * Get the path after the script name
     * For URL like http://localhost/index.php/path/2/3/4/ returns "path/2/3/4"
     * @return string The path segments after the script
     */
    public function getWebPath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Remove query string if present
        $requestUri = strtok($requestUri, '?');
        
        // If script name is in the URI, extract path after it
        if (!empty($scriptName) && strpos($requestUri, $scriptName) === 0) {
            $path = substr($requestUri, strlen($scriptName));
        } else {
            // Fallback: use PATH_INFO if available
            $path = $_SERVER['PATH_INFO'] ?? '';
            
            // If no PATH_INFO, try to extract from REQUEST_URI
            if (empty($path)) {
                $path = $requestUri;
                
                // Remove the script directory from the path
                $scriptDir = dirname($scriptName);
                if ($scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
                    $path = substr($path, strlen($scriptDir));
                }
            }
        }
        
        // Clean up the path: remove leading/trailing slashes
        $path = trim($path, '/');
        
        return $path;
    }
    function getUser()
    {
        $user = $_SESSION['user_data'];
        $user['username'] = $_SESSION['user'];

        return $user; 
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
            $mergedValue = array_replace_recursive($existingValue, $yamlData);
            
            // Set the merged value
            $this->set($configPath, $mergedValue);
            
            return true;
        } catch (Exception $e) {
            // Log error if needed
            return false;
        }
    }

    }

