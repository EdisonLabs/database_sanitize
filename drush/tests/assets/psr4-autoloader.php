<?php

/**
 * @file
 * PSR4 autoloader.
 */

/**
 * Work around to load EdisonLabs/MergeYaml library on test site.
 *
 * @param string $class The fully-qualified class name.
 *   The class name.
 *
 * @return void
 */
spl_autoload_register(function ($class) {

  // Project-specific namespace prefix.
  $prefix = 'EdisonLabs\\MergeYaml\\';

  // Base directory for the namespace prefix.
  $base_dir = dirname(__DIR__, 2) . '/modules/database_sanitize/vendor/edisonlabs/merge-yaml/src/';

  // Does the class use the namespace prefix?
  $len = strlen($prefix);
  if (strncmp($prefix, $class, $len) !== 0) {
    // No, move to the next registered autoloader.
    return;
  }

  // Get the relative class name.
  $relative_class = substr($class, $len);

  // Replace the namespace prefix with the base directory, replace namespace
  // separators with directory separators in the relative class name, append
  // with .php.
  $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

  // If the file exists, require it.
  if (file_exists($file)) {
    require $file;
  }
});
