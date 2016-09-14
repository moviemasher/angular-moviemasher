<?php

include_once(dirname(__FILE__) . '/loadutils.php');
load_utils('file');

if (! function_exists('log_file')) {
  function log_file($s, $config) {
    if ($s) {
      if (! $config) $config = config_get();
      if (! (config_error($config) || empty($config['log_file']))) {
        if (file_safe($config['log_file'], $config)) {
          $prelog = date('H:i:s') . ' ';
          $prelog .= basename($_SERVER['SCRIPT_NAME']);
          $existed = file_exists($config['log_file']);
          $fp = fopen($config['log_file'], 'a');
          if ($fp) {
            $s = $prelog . ' ' . $s . "\n";
            fwrite($fp, $s, strlen($s));
            fclose($fp);
          }
          if (! $existed) file_mode($config['log_file'], $config, TRUE);
        }
      }
    }
  }
}
