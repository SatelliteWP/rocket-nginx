<?php
/**************************************************************************************************
* Rocket-Parser
*
* Rocket-Parser is a configuration generator for Rocket-Nginx to speedup your
* website with the cache plugin WP-Rocket (http://wp-rocket.me)
*
* Author: Maxime Jobin
* URL: https://github.com/maximejobin/rocket-nginx
*
* Version 2.2
*
**************************************************************************************************/

class RocketParser {
  
  public $configFile = 'rocket-nginx.ini';
  public $templateFile = 'rocket-nginx.tmpl';
  
  /**
   * Parse the ini configuration file
   */
  protected function parseIniFile() {
    $data = parse_ini_file($this->configFile, true);
    $config = array();

    foreach($data as $namespace => $properties) {
      $parts = explode(':', $namespace);
      $name = trim($parts[0]);
      $extends = isset($parts[1]) ? trim($parts[1]) : null;
      
      // create namespace if necessary
      if(!isset($config[$name])) {
        $config[$name] = array();
      }
      
      // inherit base namespace
      if(isset($data[$extends])) {
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
   */
  protected function generateConfigurationFiles($config) {
    
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

      // HSTS
      $header_hsts = '';
      if (isset($section['header_hsts']) && !empty($section['header_hsts'])) {
        $header_hsts = $section['header_hsts'];
      }
      $output = str_replace('#!# HEADER_HSTS #!#', $header_hsts, $output);

      // Cookies
      $cookies = '';
      if (isset($section['cookie_invalidate']) && is_array($section['cookie_invalidate'])) {
        $cookies = implode('|', $section['cookie_invalidate']);
      }
      $output = str_replace('#!# COOKIE_INVALIDATE #!#', $cookies, $output);

      // Query strings to ignore
      $query_strings_ignore = '';
      if (isset($section['query_string_ignore']) && is_array($section['query_string_ignore'])) {
        $query_strings_ignore = $this->getGeneratedQueryStringsToIgnore($section['query_string_ignore']);
      }
      $output = str_replace('#!# QUERY_STRING_IGNORE #!#', $query_strings_ignore, $output);

      // Query strings to cache
      $query_strings_cache = '';
      if (isset($section['query_string_cache']) && is_array($section['query_string_cache'])) {
        $query_strings_cache = $this->getGeneratedQueryStringsToCache($section['query_string_cache']);
      }
      $output = str_replace('#!# QUERY_STRING_CACHE #!#', $query_strings_cache, $output);

      // HTTP headers
      $header_http = '';
      if (isset($section['http_header']) && is_array($section['http_header'])) {
        $header_http = $this->getGeneratedHeaders($section['http_header']);
      }
      $output = str_replace('#!# HEADER_HTTP #!#', $header_http, $output);
      
      // GZIP headers
      $gzip_header = '';
      if (isset($section['gzip_header']) && is_array($section['gzip_header'])) {
        $gzip_header = $this->getGeneratedHeaders($section['gzip_header']);
      }
      $output = str_replace('#!# HEADER_GZIP #!#', $gzip_header, $output);

      // Non-GZIP headers
      $nongzip_header = '';
      if (isset($section['nongzip_header']) && is_array($section['nongzip_header'])) {
        $nongzip_header = $this->getGeneratedHeaders($section['nongzip_header']);
      }
      $output = str_replace('#!# HEADER_NON_GZIP #!#', $nongzip_header, $output);

      // CSS headers
      $css_header = '';
      if (isset($section['css_header']) && is_array($section['css_header'])) {
        $css_header = $this->getGeneratedHeaders($section['css_header']);
      }
      $output = str_replace('#!# HEADER_CSS #!#', $css_header, $output);

      // JS headers
      $js_header = '';
      if (isset($section['js_header']) && is_array($section['js_header'])) {
        $js_header = $this->getGeneratedHeaders($section['js_header']);
      }
      $output = str_replace('#!# HEADER_JS #!#', $js_header, $output);

      // Media headers
      $medias_header = '';
      if (isset($section['medias_header']) && is_array($section['medias_header'])) {
        $medias_header = $this->getGeneratedHeaders($section['medias_header']);
      }
      $output = str_replace('#!# HEADER_MEDIAS #!#', $medias_header, $output);

      // Media extensions
      $media_extensions = '';
      if (isset($section['media_extensions']) && !empty($section['media_extensions'])) {
        $media_extensions = $section['media_extensions'];
      }
      $output = str_replace('#!# EXTENSION_MEDIAS #!#', $media_extensions, $output);
      

      // Output the file
      $filename = $name . ".conf";

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
   * Returns generated Nginx headers
   * @param $headers array Headers
   *
   * @return string Nginx headers
   */
  protected function getGeneratedHeaders($headers) {
    $result = '';

    if (isset($headers) && is_array($headers)) {
      $iteration = 1;

      foreach ($headers as $name => $value) {

        if ($iteration > 1) {
          $result .= "\t";
        }
        $result .= "add_header {$name} \"{$value}\";\n";

        $iteration++;
      }
    }

    return $result;
  }

  /**
   * Returns generated query strings statement to ignore
   * @param $queryStrings array Query strings to ignore
   * 
   * @return string Nginx "if" statements
   */
  protected function getGeneratedQueryStringsToIgnore($queryStrings) {
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
      $result .= 'if ($rocket_args ~ ^&(.*)) { set $rocket_args $1;  }' . "\n\n";
      $result .= 'set $rocket_args $is_args$rocket_args;' . "\n";
      $result .= "\n";
      $result .= '# Do not count arguments if part of caching arguments' . "\n";
      $result .= 'if ($rocket_args ~ ^\?$) {' . "\n";
      $result .= "\t" . 'set $rocket_args "";' . "\n";
      $result .= "\t" . 'set $rocket_is_args "";' . "\n";
      $result .= '}' . "\n";
    }
    else {
      $result = "# None.\n";
    }

    return $result;
  }

    /**
   * Returns generated query strings statement to ignore
   * @param $queryStrings array Query strings to ignore
   * 
   * @return string Nginx "if" statements
   */
  protected function getGeneratedQueryStringsToCache($queryStrings) {
    $result = '';

    if (isset($queryStrings) && is_array($queryStrings)) {
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
      $result .= "\t" . 'set $rocket_args "";' . "\n";
      $result .= "\t" . 'set $rocket_is_args "";' . "\n";
      $result .= '}' . "\n";
    }
    else {
      $result = "# None.\n";
    }

    return $result;
  }

  /**
   * Get the template file if it exists
   */
  protected function getTemplate() {

    if (file_exists($this->templateFile) === false) {
      die("Error: the file 'rocket-nginx.ini' could not be found to generate the configuration. " .
        "You must rename the orginal 'rocket-nginx.ini.disabled' file to 'rocket-nginx.ini' and run this script again.");
    }

    return file_get_contents('rocket-nginx.tmpl');
  }

  /**
   * Check if configuration file exists
   */
  protected function checkConfigurationFile() {
    if (file_exists($this->configFile) === false) {
      die("Error: the file 'rocket-nginx.ini' could not be found to generate the configuration. " .
        "You must rename the orginal 'rocket-nginx.ini.disabled' file to 'rocket-nginx.ini' and run this script again.");
    }    
  }

  /**
   * Generate configuration files
   */
  public function go() {
    $this->checkConfigurationFile();

    $data = $this->parseIniFile();
    $this->generateConfigurationFiles($data);
  }
}

// If file is included, we assume it will call the class automatically.
// Otherwise, let's generate the configuration files.
$includedFiles = count(get_included_files());

if ($includedFiles === 1) {
  error_reporting(-1);

  $rp = new RocketParser();
  $rp->go();
}




