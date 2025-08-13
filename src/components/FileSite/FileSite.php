<?php
namespace Opensitez\Simplicity\Components;
use Opensitez\Simplicity\MSG;

class FileSite extends \Opensitez\Simplicity\Component
{
    function on_event($event)
    {
        switch ($event["type"]) {
            case MSG::onComponentLoad:
                $this->framework->register_type("siteprovider", "filesite");
                break;
            case MSG::onParseSite:
                $domain = $event["domain"] ?? "";
                $this->checkSiteConfiguration($domain);
                break;
        }
        return parent::on_event($event);
    }

    /**
     * Check for default site fallback
     * @param string $domain The current domain
     */
    private function checkDefaultSite(string $domain): void
    {
        $defaults = $this->config_object->get("defaults", []);
        $defaultSite = $defaults["default-site"] ?? null;

        $this->debug("<strong>Debug:</strong> Checking for default site fallback<br/>");
        $this->debug("<strong>Debug:</strong> Default site setting: " .
            ($defaultSite ?? "not set") .
            "<br/>");

        if ($defaultSite) {
            $sites = $this->config_object->get("sites", []);

            if (isset($sites[$defaultSite])) {
                $this->debug("<strong>Debug:</strong> Default site '$defaultSite' found in sites configuration<br/>");
                $this->debug("<strong>Debug:</strong> Loading default site '$defaultSite' for domain '$domain'<br/>");
                $this->loadSiteConfiguration(
                    $defaultSite,
                    $sites[$defaultSite]
                );
            } else {
                $this->debug("<strong>Debug:</strong> Default site '$defaultSite' not found in sites configuration<br/>");
            }
        } else {
            $this->debug("<strong>Debug:</strong> No default site configured<br/>");
        }
    }

    /**
     * Check if the current domain matches a configured site
     * @param string $domain The current domain
     */
    public function checkSiteConfiguration(string $domain): void
    {
        // $this->debug("<strong>Debug:</strong> Checking site configuration for domain: '$domain'<br/>");

        $defaults = $this->config_object->get("defaults", []);
        $siteOverride = $defaults["site-override"] ?? false;

        // Priority 1: Check for site-override
        if ($siteOverride === true) {
            // $this->debug("<strong>Debug:</strong> Site-override is enabled, forcing default site<br/>");
            $this->checkDefaultSite($domain);
            return;
        } else {
            // $this->debug("<strong>Debug:</strong> Site-override is not enabled, not forcing default site<br/>");
        }

        // $this->debug("<strong>Debug:</strong> Site-override is disabled, checking normal site resolution<br/>");

        // Priority 2: Check direct match or aliases in sites array
        $sites = $this->config_object->get("sites", []);
        // $this->debug("<strong>Debug:</strong> Found " . count($sites) . " configured sites<br/>");

        foreach ($sites as $siteName => $siteConfig) {
            // $this->debug("<strong>Debug:</strong> Checking site '$siteName'<br/>");

            // Check direct site name match
            if ($siteName === $domain) {
                // $this->debug("<strong>Debug:</strong> Direct match found for '$siteName'<br/>");
                $this->loadSiteConfiguration($siteName, $siteConfig);
                return;
            }

            // Check aliases
            $aliases = $siteConfig["aliases"] ?? [];
            if (in_array($domain, $aliases)) {
                // $this->debug("<strong>Debug:</strong> Alias match found in '$siteName' aliases<br/>");
                $this->loadSiteConfiguration($siteName, $siteConfig);
                return;
            }
        }

        // $this->debug("<strong>Debug:</strong> No configured site found for '$domain'<br/>");

        // Priority 3: Check autofolder if explicitly enabled
        $autofolder = $defaults["autofolder"] ?? null;
        // $this->debug("<strong>Debug:</strong> Autofolder setting: " . ($autofolder === true ? 'true' : ($autofolder === false ? 'false' : 'not set')) . "<br/>");

        if ($autofolder === true) {
            $this->debug("<strong>Debug:</strong> Autofolder is explicitly enabled, checking for folder<br/>");
            $this->checkAutoFolder($domain);

            // If autofolder didn't find anything, check default site
            if (!$this->config_object->get("site")) {
                $this->debug("<strong>Debug:</strong> Autofolder didn't find a folder, checking default site fallback<br/>");
                $this->checkDefaultSite($domain);
            }
        } else {
            $this->debug("<strong>Debug:</strong> Autofolder is not enabled, checking default site fallback<br/>");
            $this->checkDefaultSite($domain);
        }
    }
    /**
     * Check if a folder exists for the domain (autofolder feature)
     * @param string $domain The domain name
     */
    private function checkAutoFolder(string $domain): void
    {
        $this->debug("<strong>Debug:</strong> Checking autofolder for domain: '$domain'<br/>");

        // Validate domain name to prevent directory traversal
        if (!$this->isValidFolderName($domain)) {
            $this->debug("<strong>Debug:</strong> Domain '$domain' is not a valid folder name<br/>");
            return;
        }

        $sitesPath = $this->config_object->get("paths.sites");
        if (!$sitesPath) {
            $this->debug("<strong>Debug:</strong> No sites path configured<br/>");
            return;
        }

        $this->debug("<strong>Debug:</strong> Sites path: '$sitesPath'<br/>");
        $domainFolder = $sitesPath . "/" . $domain;
        $this->debug("<strong>Debug:</strong> Checking folder: '$domainFolder'<br/>");

        if (is_dir($domainFolder)) {
            //$this->config_object->set('site.sitepath', $domainFolder);
            $this->debug("<strong>Debug:</strong> Autofolder found: '$domainFolder'<br/>");
            // Create a minimal site configuration for the auto-discovered folder
            $autoSiteConfig = [
                "name" => $domain,
                "folder" => $domain,
                "db" => $this->config_object->get("defaults.db", "mysql"),
            ];

            $this->loadSiteConfiguration($domain, $autoSiteConfig);
        } else {
            $this->debug("<strong>Debug:</strong> Autofolder not found: '$domainFolder'<br/>");
        }
    }
    /**
     * Load site configuration and definition file
     * @param string $siteName The site name
     * @param array $siteConfig The site configuration
     */
    private function loadSiteConfiguration(
        string $siteName,
        array $siteConfig
    ): void {
        $normalizedConfig = $siteConfig;
        $system = $this->config_object->get("system", []);

        // Set domain to the current domain
        //$normalizedConfig['domain'] = $this->getDomain();

        // Set path to the current path
        $normalizedConfig["path"] = $this->config_object->getWebPath();
        $localBase = $this->config_object->get("paths.base", "local/sites");

        // Set name - use the name from config or fall back to site name
        $normalizedConfig["name"] = $siteConfig["name"] ?? $siteName;

        // Set folder - use folder from config or fall back to site name
        $normalizedConfig["folder"] = $siteConfig["folder"] ?? $siteName;
        $datafolder = "$localBase/local/sites/" . $normalizedConfig["folder"];
        $this->config_object->set("paths.datafolder", $datafolder);

        // Set db - use db from site config or fall back to default
        $normalizedConfig["db"] =
            $siteConfig["db"] ??
            $this->config_object->get("defaults.db", "mysql");

        // Set file - use file from site config or fall back to 'definition.yaml'
        $normalizedConfig["file"] =
            $siteConfig["file"] ?? "imports/global.yaml";

        // Start with defaults.definition as base, then merge with file definition
        $defaultDefinition = $this->config_object->get(
            "defaults.definition",
            []
        );
        $normalizedConfig["definition"] = $defaultDefinition;
        $contentFolder =
            "$localBase/local/sites/" .
            $normalizedConfig["folder"] .
            "/content";
        $this->config_object->set("paths.site-content", $contentFolder);

        // Load definition file first, then set the complete normalized config
        $folder = $normalizedConfig["folder"];
        $this->config_object->merge("site", $normalizedConfig);

        $sitesPath = $this->config_object->get("paths.sites");
        $acceptedLangs = array_keys(
            $this->config_object->get("site.accepted-langs", [])
        );
        // $this->debug("<strong>Debug:</strong> Accepted languages: " . implode(', ', $acceptedLangs) . "<br/>");
        if ($sitesPath) {
            $filename = $normalizedConfig["file"];
            $definitionFile = $sitesPath . "/{$folder}/{$filename}";
            // $this->debug("<strong>Debug:</strong> Loading definition file: '$definitionFile'<br/>");

            $this->config_object->mergeYaml("site.definition", $definitionFile);
            // $this->debug(print_r($this->config_object->get('site.definition', [])['routes'], true));
            $load_i18n_files = $this->config_object->get(
                "site.definition.check-language-files",
                false
            );
            if ($load_i18n_files) {
                $this->merge_i18n_translations($definitionFile);
            }
            foreach (
                $this->config_object->get("site.definition.imports", [])
                as $importFile
            ) {
                $importPath = $sitesPath . "/{$folder}/imports/$importFile";
                if (file_exists($importPath)) {
                    $this->config_object->mergeYaml(
                        "site.definition",
                        $importPath
                    );
                    if ($load_i18n_files) {
                        $this->merge_i18n_translations($importPath);
                    }
                } else {
                    // $this->debug("<strong>Debug:</strong> Import file '$importPath' not found, skipping<br/>");
                }
            }
        }
    }
    public function merge_i18n_translations(string $definitionFile): void
    {
        $acceptedLangs = array_keys(
            $this->config_object->get("site.accepted-langs", [])
        );
        $load_i18n_files = $this->config_object->get(
            "site.definition.check-language-files",
            false
        );
        foreach ($acceptedLangs as $lang) {
            $langFile = $definitionFile . ".$lang";

            if ($load_i18n_files && file_exists($langFile)) {
                $this->config_object->mergeYaml("site.definition", $langFile);
            } else {
                // $this->debug("<strong>Debug:</strong> Language file '$langFile' not found, skipping<br/>");
            }
        }
    }
    /**
     * Validate if a domain name can be safely used as a folder name
     * @param string $domain The domain name to validate
     * @return bool True if valid, false otherwise
     */
    private function isValidFolderName(string $domain): bool
    {
        // Check for directory traversal attempts
        if (strpos($domain, '..') !== false || strpos($domain, '/') !== false || strpos($domain, '\\') !== false) {
            return false;
        }
        
        // Check for invalid characters for folder names
        if (preg_match('/[<>:"|?*]/', $domain)) {
            return false;
        }
        
        // Check if domain is empty or only whitespace
        if (trim($domain) === '') {
            return false;
        }
        
        return true;
    }
}
