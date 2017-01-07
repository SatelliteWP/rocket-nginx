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
* Version 2.0
*
**************************************************************************************************/

class RocketParser {
  
  public $configFile = "rocket-nginx.ini";
  
  function parseIniFile() {
    $p_ini = parse_ini_file($this->configFile, true);
    $config = array();
    foreach($p_ini as $namespace => $properties) {
      list($name, $extends) = explode(':', $namespace);
      $name = trim($name);
      $extends = trim($extends);
      
      // create namespace if necessary
      if(!isset($config[$name])) $config[$name] = array();
      
      // inherit base namespace
      if(isset($p_ini[$extends])) {
        foreach($p_ini[$extends] as $prop => $val)
          $config[$name][$prop] = $val;
      }
      // overwrite / set current namespace values
      foreach($properties as $prop => $val)
        $config[$name][$prop] = $val;
    }
    return $config;
  }
  
  public function start() {
    
    if (file_exists($this->configFile) {
    }
  }
}



