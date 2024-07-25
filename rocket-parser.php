<?php
/**************************************************************************************************
* Rocket-Parser
*
* Rocket-Parser is a configuration generator for Rocket-Nginx to speedup your
* website with the cache plugin WP-Rocket (http://wp-rocket.me)
*
* Maintainer: SatelliteWP
* URL: https://github.com/satellitewp/rocket-nginx
*
* Original author: Maxime Jobin
* URL: https://www.maximejobin.com
*
* Version 3.1.0
*
**************************************************************************************************/

class RocketParser {
	
	public $configFile = 'rocket-nginx.ini';
	public $templateFile = 'rocket-nginx.tmpl';
	
	/**
	 * Parse the ini configuration file
	 */
	protected function parseIniFile() : array {
		$data = parse_ini_file($this->configFile, true);
		$config = array();

		foreach($data as $namespace => $properties) {
			$parts = explode(':', $namespace);
			$name = trim($parts[0]);
			$extends = isset($parts[1]) ? trim($parts[1]) : null;
			
			// create namespace if necessary
			if (!isset($config[$name])) {
				$config[$name] = array();
			}
			
			// inherit base namespace
			if (isset($data[$extends])) {
				foreach($data[$extends] as $prop => $val) {
					$config[$name][$prop] = $val;
				}
			}
			// overwrite / set current namespace values
			foreach($properties as $prop => $val) {
				$config[$name][$prop] = $val;
			}
		}

		return $config;
	}
	
	/**
	 * Generate all configuration files
	 * 
	 * @param array $config Configuration data
	 */
	protected function generateConfigurationFiles($config) : void {
		
		// Load template
		$template = $this->getTemplate();

		foreach($config as $name => $section) {
			$output = $template;

			// Debug
			$debug = false;
			if (isset($section['debug']) && $section['debug'] === '1') {
				$debug = true;
			}
			$output = str_replace('#!# DEBUG #!#', $debug ? '1' : '0', $output);

			// WP Content URI
			$wp_content_folder = 'wp-content';
			if (isset($section['wp_content_folder']) && !empty($section['wp_content_folder'])) {
				$wp_content_folder = $section['wp_content_folder'];
			}
			$output = str_replace('#!# WP_CONTENT_URI #!#', $wp_content_folder, $output);

			// Cache Control
			$html_cache_control = '';
			if (isset($section['html_cache_control']) && !empty($section['html_cache_control'])) {
				$html_cache_control = $section['html_cache_control'];
			}
			$output = str_replace('#!# HTML_CACHE_CONTROL #!#', $html_cache_control, $output);

			// Cookies
			$cookies = '';
			if (isset($section['cookie_invalidate']) && is_array($section['cookie_invalidate'])) {
				$cookies = implode('|', $section['cookie_invalidate']);
			}
			$output = str_replace('#!# COOKIE_INVALIDATE #!#', $cookies, $output);

			// Query strings to ignore
			$query_strings_ignore = '# None';
			if (isset($section['query_string_ignore']) && is_array($section['query_string_ignore'])) {
				$query_strings_ignore = $this->getGeneratedQueryStringsToIgnore($section['query_string_ignore']);
			}
			$output = str_replace('#!# QUERY_STRING_IGNORE #!#', $query_strings_ignore, $output);

			// Query strings to cache
			$query_strings_cache = '# None';
			if (isset($section['query_string_cache']) && is_array($section['query_string_cache'])) {
				$query_strings_cache = $this->getGeneratedQueryStringsToCache($section['query_string_cache']);
			}
			$output = str_replace('#!# QUERY_STRING_CACHE #!#', $query_strings_cache, $output);

			// CSS expiration delay
			$css_expiration = '30d';
			if (isset($section['css_expiration']) && !empty($section['css_expiration'])) {
				$css_expiration = $section['css_expiration'];
			}
			$output = str_replace('#!# CSS_EXPIRATION #!#', $css_expiration, $output);

			// JS expiration delay
			$js_expiration = '30d';
			if (isset($section['js_expiration']) && !empty($section['js_expiration'])) {
				$js_expiration = $section['js_expiration'];
			}
			$output = str_replace('#!# JS_EXPIRATION #!#', $js_expiration, $output);

			// Media expiration delay
			$media_expiration = '30d';
			if (isset($section['media_expiration']) && !empty($section['media_expiration'])) {
				$media_expiration = $section['media_expiration'];
			}
			$output = str_replace('#!# MEDIA_EXPIRATION #!#', $media_expiration, $output);

			// Start include
			$include_start = $this->getGeneratedInclude($name, 'start');
			$output = str_replace('#!# INCLUDE_START #!#', $include_start, $output);

			// Preprocess include
			
			$preprocess_start = $this->getGeneratedInclude($name, 'preprocess');
			$output = str_replace('#!# INCLUDE_PREPROCESS #!#', $preprocess_start, $output);

			// Global include
			$include_global = $this->getGeneratedInclude($name, 'global');
			$output = str_replace('#!# INCLUDE_GLOBAL #!#', $include_global, $output);

			// HTTP include
			$include_http = $this->getGeneratedInclude($name, 'http');
			$output = str_replace('#!# INCLUDE_HTTP #!#', $include_http, $output);

			// CSS headers
			$include_css = $this->getGeneratedInclude($name, 'css');
			$output = str_replace('#!# INCLUDE_CSS #!#', $include_css, $output);

			// JS headers
			$include_js = $this->getGeneratedInclude($name, 'js');
			$output = str_replace('#!# INCLUDE_JS #!#', $include_js, $output);

			// Media headers
			$include_media = $this->getGeneratedInclude($name, 'media');
			$output = str_replace('#!# INCLUDE_MEDIA #!#', $include_media, $output);

			// Media extensions
			$media_extensions = '';
			if (isset($section['media_extensions']) && !empty($section['media_extensions'])) {
				$media_extensions = $section['media_extensions'];
			}
			$output = str_replace('#!# MEDIA_EXTENSIONS #!#', $media_extensions, $output);

			// Mobile devices
			$mobile_ua_devices = 'not_v_a_l_i_d';
			if (isset($section['mobile_ua_devices']) && !empty($section['mobile_ua_devices'])) {
				$mobile_ua_devices = $section['mobile_ua_devices'];
			}
			$output = str_replace('#!# MOBILE_USER_AGENT #!#', $mobile_ua_devices, $output);
			

			// Create main configuration folder if it doesn't exist
			$main_confd = 'conf.d';
			if (!is_dir($main_confd)) {
				mkdir( $main_confd, 0770 );
			}

			// Create configuration folder 
			$confd = $main_confd . '/' . $name;
			if (!is_dir($confd)) {
				mkdir( $confd, 0770 );
			}

			// Output the file
			$filename = $main_confd . '/' . $name . ".conf";

			if (!$handle = fopen($filename, 'w')) {
				echo "Cannot open file: {$filename}.\n";
				continue;
			}

			if (fwrite($handle, $output) === FALSE) {
				echo "Cannot write to file {$filename}.\n";
				continue;
			}

			fclose($handle);
		}
	}

	/**
	 * Returns generated query strings statement to ignore
	 * 
	 * @param array $queryStrings Query strings to ignore
	 * 
	 * @return string Nginx "if" statements
	 */
	protected function getGeneratedQueryStringsToIgnore(array $queryStrings) : string {
		$result = '';

		if (isset($queryStrings) && is_array($queryStrings)) {
			$iteration = 1;

			$result .= 'set $rocket_args $args;' . "\n";
			foreach ($queryStrings as $name => $value) {
				$result .= 'if ($rocket_args ~ (.*)(?:&|^)' . $value . '=[^&]*(.*)) { ';
				$result .= 'set $rocket_args $1$2; ';
				$result .= "}\n";

				$iteration++;
			}

			$result .= "\n";
			$result .= '# Remove & at the beginning (if needed)' . "\n";
			$result .= 'if ($rocket_args ~ ^&(.*)) { set $rocket_args $1;  }' . "\n";
			$result .= "\n";
			$result .= '# Do not count arguments if part of caching arguments' . "\n";
			$result .= 'if ($rocket_args ~ ^\?$) {' . "\n";
			$result .= "\t" . 'set $rocket_is_args "";' . "\n";
			$result .= '}';
		}

		return $result;
	}

	/**
	 * Returns generated query strings statement to cache
	 * 
	 * @param array $queryStrings Query strings to cache
	 * 
	 * @return string Nginx "if" statements
	 */
	protected function getGeneratedQueryStringsToCache(array $queryStrings) : string {
		$result = '';
		$iteration = 1;

		$result .= 'set $rocket_args_tmp $rocket_args;' . "\n";
		foreach ($queryStrings as $name => $value) {
			$result .= 'if ($rocket_args_tmp ~ (.*)(?:&|^)' . $value . '=[^&]*(.*)) { ';
			$result .= 'set $rocket_has_query_cache 1; ';
			$result .= "}\n";

			$iteration++;
		}

		$result .= "\n";
		$result .= 'if ($rocket_has_query_cache = 1) {' . "\n";
		$result .= "\t" . 'set $rocket_args "#$rocket_args";' . "\n";
		$result .= "\t" . 'set $rocket_is_args "";' . "\n";
		$result .= '}';

		return $result;
	}

	/**
	 * Returns generated include for a section headers
	 *
	 * @param string $config Configuration name 
	 * @param string $section Section name
	 *
	 * @return string Include statement
	 */
	protected function getGeneratedInclude(string $config, string $section) : string {
		$dir = dirname(__FILE__);
		$result = "include {$dir}/conf.d/{$config}/{$section}.*.conf;";

		return $result;
	}

	/**
	 * Get the template file if it exists
	 */
	protected function getTemplate() : string {

		if (file_exists($this->templateFile) === false) {
			die("Error: the file 'rocket-nginx.ini' could not be found to generate the configuration. " .
			"You must rename the orginal 'rocket-nginx.ini.disabled' file to 'rocket-nginx.ini' and run this script again.");
		}

		return file_get_contents('rocket-nginx.tmpl');
	}

	/**
	 * Check if configuration file exists
	 */
	protected function checkConfigurationFile() : void {
		if (file_exists($this->configFile) === false) {
			die("Error: the file 'rocket-nginx.ini' could not be found to generate the configuration. " .
			"You must rename the orginal 'rocket-nginx.ini.disabled' file to 'rocket-nginx.ini' and run this script again.");
		}
	}

	/**
	 * Generate configuration files
	 */
	public function go() : void {
		$this->checkConfigurationFile();

		$data = $this->parseIniFile();
		$this->generateConfigurationFiles($data);
	}
}

// If this file is included (using 'include' or 'require'), we assume it will call 
// the class automatically. Otherwise, let's generate the configuration files.
$includedFiles = count(get_included_files());

if ($includedFiles === 1) {
	error_reporting(-1);

	$rp = new RocketParser();
	$rp->go();
}
