<?php

/*

Neato 0.0.1-alpha
https://www.neato.pub

Copyright 2026 Neatnik LLC

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

// Handle errors gracefully
register_shutdown_function(function () {
  $error = error_get_last();
  if($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    neato_log("Your site was not generated. Correct the errors shown above and run Neato again.", ERROR);
  }
  echo "\n";
});

// Include any needed code
if(!function_exists('yaml_parse')) {
  include('includes/yaml.php');
}

// Define our log levels
$levels = ['INFO', 'LOADING', 'JOB', 'REPORT', 'ERROR'];
$max = max(array_map('strlen', $levels));
foreach($levels as $v) define($v, str_pad($v, $max, ' ', STR_PAD_LEFT));

// Log output to the terminal cleanly
function neato_log($entry, $type = INFO, $exit = false) {
  $prefix = date("H:i:s")." | $type | ";
  $pad = str_repeat(' ', strlen($prefix));
  #$width = 80 - strlen($prefix);
  #$wrapped = wordwrap($entry, $width, "\n", true);
  #$lines = explode("\n", $wrapped);
  #echo $prefix . array_shift($lines) . "\n";
  #foreach ($lines as $line) {
  #  echo $prefix . $line . "\n";
  #}
  
  echo $prefix . $entry . "\n";
  
  if ($exit) exit;
}

// Handle fractions of seconds a bit more gracefully than microtime() does
function pretty_time(float $seconds) {
  $fmt = fn($n, $d = 3) => rtrim(rtrim(number_format($n, $d, '.', ''), '0'), '.');
  if ($seconds >= 60) return sprintf("%dm %ss", floor($seconds / 60), $fmt($seconds % 60));
  if ($seconds >= 1) return $fmt($seconds). ' seconds';
  if ($seconds >= 0.001) return $fmt($seconds * 1000). ' milliseconds';
  return $fmt($seconds * 1_000_000, 0).' microseconds';
}

// Define a tmp file that we can reuse (for slightly efficiency gains)
$_parse_php_tmp_file = null;

// Parse PHP in content files and include the output in the generated file
function parse_php(string $s, array $vars = []) {
  global $_parse_php_tmp_file;
  
  // Make variables available to the included template/content
  extract($vars, EXTR_SKIP);
  
  // tmp file recycling
  if($_parse_php_tmp_file === null) {
    $_parse_php_tmp_file = tempnam(sys_get_temp_dir(), 'neato_');
    if($_parse_php_tmp_file === false) {
      throw new RuntimeException("Failed to create temporary file");
    }
  }
  
  $tmp = $_parse_php_tmp_file;
  
  // Ensure it's treated as plain text/HTML unless PHP tags appear inside
  file_put_contents($tmp, $s);
  
  // Suppress all PHP error display
  $old_display_errors = ini_get('display_errors');
  $old_log_errors = ini_get('log_errors');
  ini_set('display_errors', '0');
  ini_set('log_errors', '0');
  
  // Start capturing PHP output
  ob_start();
  try {
    include $tmp;
    $result = ob_get_clean();
    
    // Restore error settings
    ini_set('display_errors', $old_display_errors);
    ini_set('log_errors', $old_log_errors);
    
    return $result;
  } catch (Throwable $e) {
    ob_end_clean();
    ini_set('display_errors', $old_display_errors);
    ini_set('log_errors', $old_log_errors);
    neato_log("PHP error: ".$e->getMessage()." in $item on line ".$e->getLine(), ERROR);
    neato_log("Your site was not generated. Correct the errors shown above and run Neato again.", ERROR);
    exit;
  }
}

// Cleanup tmp file on shutdown
register_shutdown_function(function() {
  global $_parse_php_tmp_file;
  if($_parse_php_tmp_file !== null) {
    @unlink($_parse_php_tmp_file);
  }
});

// Define the available hooks for Power-Ups
$hooks = [
  'init' => [],
  'before_page' => [],
  'after_page' => [],
  'after_template' => [],
  'shutdown' => [],
];

function add_hook($hook, $fn, $priority = 10) {
  global $hooks;
  if(!isset($hooks[$hook])) {
    $hooks[$hook] = [];
  }
  $hooks[$hook][] = ['fn' => $fn, 'priority' => $priority];
}

function run_hook($hook, &...$args) {
  global $hooks;
  if(isset($hooks[$hook])) {
    usort($hooks[$hook], function($a, $b) {
      return $a['priority'] <=> $b['priority'];
    });
    foreach($hooks[$hook] as $entry) {
      $result = $entry['fn'](...$args);
      if($result !== null && count($args) > 0) {
        $args[0] = $result;
      }
    }
  }
}

// Begin

echo "\n";
neato_log('Welcome to Neato.', INFO);

// Load the configuration file
if(file_exists('neato.yaml')) {
  $config = yaml_parse(file_get_contents('neato.yaml'));
}
else {
  neato_log("You’re missing your neato.yaml configuration file. Grab a copy from https://source.tube/neatnik/neato, place it in the same directory where neato.php is, and run Neato again.", ERROR);
  exit;
}

// Normalize paths
foreach($config['paths'] as &$path) {
  if(substr($path, -1) !== '/') {
    $path .= '/';
  }
}

// Startup-checks

foreach($config['paths'] as $path => $val) {
  if(!is_dir($path)) {
    if(!mkdir($path, 0755, true)) {
      neato_log("Your `$path` directory could not be found, nor could it be created. Please create the directory and try again.", ERROR, true);
    }
    else {
      neato_log("Created missing `$path` directory.", INFO);
    }
  }
}


if(!file_exists($config['paths']['templates'].$config['templates']['page'])) {
  neato_log('Your page template could not be found. Please add one and try again.', ERROR, true);
}

// Load Power-Ups
if(isset($config['paths']['power-ups'])) {
  foreach(glob($config['paths']['power-ups'].'*') as $dir) {
    
    $segments = explode('/', $dir);
    
    if(substr(end($segments), 0, 1) == '_') {
      neato_log('Skipping Power-Up: '.$dir.' [disabled]', LOADING);
      continue;
    }
    
    neato_log('Loading Power-Up: '.$dir, LOADING);
    $power_up_script_file = substr($dir, strrpos($dir, '/')+1).'.php';
    if(file_exists($dir.'/'.$power_up_script_file)) {
      require_once($dir.'/'.$power_up_script_file);
    }
    else {
      neato_log('Missing '.$power_up_script_file, ERROR);
    }
  }
}

// Run init hooks
run_hook('init');

// Clear the published directory if configured
if(isset($config['publishing']['clear_published_directory']) && $config['publishing']['clear_published_directory']) {
  rrmdir($config['paths']['published']);
  mkdir($config['paths']['published']);
  neato_log('Deleted and recreated '.$config['paths']['published'].'.', INFO);
}


/* Functions */

// Recursive directory deletion
function rrmdir($dir) {
  if(is_dir($dir)) {
    $objects = scandir($dir);
    foreach($objects as $object) {
      if($object != "." && $object != "..") {
        if(is_dir($dir.DIRECTORY_SEPARATOR.$object) && !is_link($dir."/".$object))
          rrmdir($dir.DIRECTORY_SEPARATOR.$object);
        else
          unlink($dir.DIRECTORY_SEPARATOR.$object);
      }
    }
    rmdir($dir);
  }
}

// Generate the finest quality UUIDs
function generate_uuid_v4() {
  $random_bytes = random_bytes(16);
  $random_bytes[6] = chr((ord($random_bytes[6]) & 0x0f) | 0x40);
  $random_bytes[8] = chr((ord($random_bytes[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($random_bytes), 4));
}

function array_keys_to_lowercase(array $array): array {
  $result = [];
  foreach($array as $key => $value) {
    $key = is_string($key) ? strtolower($key) : $key;
    if(is_array($value)) {
      $value = array_keys_to_lowercase($value);
    }
    $result[$key] = $value;
  }
  return $result;
}

// Parse function tags, e.g. {fa:solid/face-grin-beam}
function parse_function_tags($input) {
  preg_match_all('/\{([a-zA-Z0-9_-]+):([^}]+)\}/', $input, $matches, PREG_SET_ORDER);
  $results = [];
  foreach($matches as $match) {
    $results[] = [
      'match' => $match[0],     // the full matched string, e.g. "{fa:solid/face-grin-beam}"
      'function' => $match[1],  // the function name, e.g. "fa"
      'parameter' => $match[2], // the parameter, e.g. "solid/face-grin-beam"
    ];
  }
  return $results;
}

// Recursive glob
/*
function rglob($pattern, $flags = 0) {
  $files = glob($pattern, $flags); 
  foreach(glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
    $files = array_merge([], ...[$files, rglob($dir."/".basename($pattern), $flags)]);
  }
  return $files;
}
*/

function rglob($pattern, $flags = 0) {
  $files = glob($pattern, $flags) ?: [];
  $dir = dirname($pattern);
  $base = basename($pattern);
  foreach (scandir($dir) as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    $full = $dir . '/' . $entry;
    if (is_dir($full)) {
      $files = array_merge($files, rglob($full . '/' . $base, $flags));
    }
  }
  return $files;
}

// Cache our real_path lookups
$_template_real_path_cache = [];

// Recursively render output into page templates
function render_templated_output($file, $data, $context = [], &$stack = []) {
  global $config, $_template_real_path_cache;
  
  // Run registered hooks
  run_hook('before_page', $data);
  
  // I don't think this works any more, lol
  if(isset($data['type']) && $data['type'] == 'raw') {
    return $content;
  }
  
  // Override the template, if one is specified
  if(isset($data['template'])) $file = $data['template'];
  
  $template_path = $config['paths']['templates'].$file;
  
  // Use our real_path cache for efficiency
  if(!isset($_template_real_path_cache[$template_path])) {
    $real_path = realpath($template_path);
    if($real_path === false || !str_starts_with($real_path, realpath($config['paths']['templates']))) {
      throw new RuntimeException("Template '$file' not found or outside \$baseDir");
    }
    $_template_real_path_cache[$template_path] = $real_path;
  } else {
    $real_path = $_template_real_path_cache[$template_path];
  }
  
  // Uh oh
  if(isset($stack[$real_path])) {
    throw new RuntimeException("Circular include detected: $file");
  }
  $stack[$real_path] = true;
  $html = file_get_contents($real_path);

  $html = preg_replace_callback(
    '/\{templates\/([A-Za-z0-9_\-\/\.]+)\}/',
    #function ($m) use ($baseDir, $context, &$stack) {
    function ($m) use ($context, &$stack) { // I don't think we need $baseDir here?
      $target = $m[1].(pathinfo($m[1], PATHINFO_EXTENSION) ? '' : '.html');
      return render_templated_output($target, null, $context, $stack);
    },
    $html
  );
  
  $rendered = $html;
  
  // Make config values available as tokens; e.g. {site.domain}
  foreach($config as $section => $values) {
    if(is_array($values)) {
      foreach($values as $key => $val) {
        if(is_string($val)) {
          $data[$section.'.'.$key] = $val;
        }
      }
    }
  }
  
  // Build the token map from data
  $token_map = [];
  foreach($data as $key => $val) {
    if(is_string($val)) {
      $token_map['{'.$key.'}'] = $val;
    }
  }
  
  // Apply token replacements to content itself first
  if(isset($data['content'])) {
    $data['content'] = strtr($data['content'], $token_map);
  }
  
  // Handle core replacements
  $replacements = [
    '{content}' => $data['content'] ?? '',
    '{description}' => $data['description'] ?? '',
    '{microtime}' => microtime(1),
    '{title}' => $data[$config['metadata']['title_key']] ?? '',
    '{uuid}' => $data[$config['metadata']['id_key']] ?? '',
    '{unix-time}' => (string)time(),
    '{path}' => $data['path'],
  ];
  
  // Replace any YAML values
  foreach($data as $key => $val) {
    $replacements['{'.$key.'}'] = $val;
  }
  
  $rendered = strtr($rendered, $replacements);
  
  // Remove from the stack
  unset($stack[$real_path]);
  
  // Run post-template-prep hooks
  run_hook('after_template', $rendered);
  
  // Cleanup any escaped tags
  $rendered = str_replace('{\\', '{', $rendered);
  
  return $rendered;
}

// Iterate over the content directory to prepare and publish files

$source_content = rglob($config['paths']['content'].'*.{html,mphp,md,txt,neato}', GLOB_BRACE);
$running_pages = [];

$start = microtime(1);

// Define our YAML pattern
$yaml_pattern = '/\A(?:\xEF\xBB\xBF)?---[ \t]*\R(.*?)\R---[ \t]*\R?/s';

foreach($source_content as $item) {
  $metadata = [];
  
  neato_log('Processing '.$item, JOB);
  
  // Load the source content for this item
  $content = file_get_contents($item);
  
  // Identify any YAML frontmatter
  if(preg_match($yaml_pattern, $content, $m)) {
    $yaml = $m[1];
    
    // Slice the body from the original content and trim the first blank line if present
    $content = substr($content, strlen($m[0]));
    $content = ltrim($content, "\r\n");
  }
  else {
    $yaml = '';
  }
  
  $metadata = $yaml ? yaml_parse($yaml) : [];
  
  #print_r($metadata);
  #exit;
  
  // Normalize the metadata
  $metadata = array_keys_to_lowercase($metadata);
  
  // Skip content in a `draft` status
  if(isset($metadata['status'])) {
    if(strtolower($metadata['status']) === 'draft') {
      neato_log('∟ Skipping draft.', INFO);
      continue;
    }
  }
  
  // Track whether metadata needs updating
  $metadata_updated = false;
  $filemtime = filemtime($item);
  
  // Metadata: created
  if(!isset($metadata[$config['metadata']['created_key']])) {
    $metadata[$config['metadata']['created_key']] = date("Y-m-d H:i:s", $filemtime);
    $metadata_updated = true;
  }
  
  // Metadata: modified
  if(!isset($metadata[$config['metadata']['modified_key']])) {
    $metadata[$config['metadata']['modified_key']] = date("Y-m-d H:i:s", $filemtime);
    $metadata_updated = true;
  }
  
  // Metadata: UUID
  if(!isset($metadata[$config['metadata']['id_key']])) {
    $metadata[$config['metadata']['id_key']] = generate_uuid_v4();
    $metadata_updated = true;
  }
  
  // Only write the file if any metadata was updated
  if($metadata_updated) {
    $updated_yaml = yaml_emit($metadata);
    $updated_yaml = implode("\n", array_slice(explode("\n", rtrim($updated_yaml)), 1, -1));
    $updated_file = "---\n".$updated_yaml."\n---\n".trim($content);
    file_put_contents($item, $updated_file);
  }
  
  // At this point we have $metadata (an array of item metadata) as well as $content (the actual page content)
  // Aaaaannndd we're going to merge them into one simple $data variable
  $data = $metadata;
  $data['content'] = $content;
  
  // Evaluate any PHP in the content immediately, before doing any other processing
  if($config['publishing']['parse_php'] === true) {
    try {
      $data['content'] = parse_php($data['content'], [
        'data' => $data,
        'config' => $config,
        'item' => $item,
      ]);
    } catch (Throwable $e) {
      neato_log("Error processing file: $item", ERROR);
      throw $e;
    }
  }
  
  // Create the directory where this page will live
  
  // First, determine the directory name
  $item_path = substr($item, strlen($config['paths']['content']));
  $item_directory = explode('/', $item_path);
  $file_name = array_pop($item_directory);
  $file_path = implode('/', $item_directory);
  
  // Next, determine if this page is intended to be preserved
  if(!isset($data['preserve']) || $data['preserve'] !== true) {
    $file_bits = explode('.', $file_name);
    $item_directory[] = $file_bits[0];
    $file_path .= '/'.$file_bits[0];
    $file_name = 'index.html';
  }
  
  // Create the directory structure for the page, one directory at a time
  $structure = '';
  foreach($item_directory as $segment) {
    $structure .= '/'.$segment;
    $dir = $config['paths']['published'].$structure;
    // Make a directory, but only if we have to
    if(!is_dir($dir)) {
      if(!@mkdir($dir, 0755)) {
        $error = error_get_last();
        print_r($error);
      }
    }
  }
  
  $file_path = trim($file_path, '/').'/';
  
  // If we have location metadata specified, we're going to use that instead.
  if(isset($data['location'])) {
    $file_path = trim($data['location'], '/').'/';
  }
  
  $published_file = $config['paths']['published'].$file_path.$file_name;
  
  // Add the uri to metadata
  if(!isset($data['uri'])) {
    $data['uri'] = $file_path;
  }
  
  if(!isset($data['path'])) {
    $data['path'] = $file_path;
  }
  
  // Define the item by its path
  $data['item'] = $item;
  
  // Run the after_page hook, just prior to writing the file
  run_hook('after_page', $data);
  
  // Stash the filemtime
  $data['neato_filemtime'] = $filemtime;
  
  // Add the page data to a running list of pages
  $running_pages[] = $data;
  
  // Support switching templates
  $template = $data['template'] ?? $config['templates']['page'];
  
  // Write the file to disk
  if(!@file_put_contents($config['paths']['published'].$file_path.$file_name, render_templated_output($template, $data))) {
    $error = error_get_last();
    echo '<br>Error saving page: <pre>';
    print_r($error);
    echo '</pre>';
  }
}

// Copy non-content files (images, JS, CSS, etc.) to the published directory
$all_files = rglob($config['paths']['content'].'*');
$content_extensions = ['html', 'mphp', 'md', 'txt', 'neato'];

foreach($all_files as $file) {
  if(is_dir($file)) continue;
  
  $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
  if(in_array($ext, $content_extensions, true)) continue;
  
  // Determine destination path by replacing the content path prefix with the published path
  $relative = substr($file, strlen($config['paths']['content']));
  $dest = $config['paths']['published'].'/'.ltrim($relative, '/');
  
  // Ensure the destination directory exists
  $dest_dir = dirname($dest);
  if(!is_dir($dest_dir)) {
    mkdir($dest_dir, 0755, true);
  }
  
  if(!@copy($file, $dest)) {
    $error = error_get_last();
    neato_log("Failed to copy asset: $file → $dest", ERROR);
  } else {
    neato_log("Copied asset: $relative", INFO);
  }
}

// Now copy the index file for the landing page
@copy($config['paths']['published'].'/home/index.html', $config['paths']['published'].'/index.html');

// Create a cache of site pages and their sizes
$cache = [];

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator(
    $config['paths']['published'],
    FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
  ),
  RecursiveIteratorIterator::LEAVES_ONLY
);

foreach($iterator as $file) {
  if($file->isFile()) {
    $cache[$file->getPathname()] = $file->getSize();
  }
}

file_put_contents($config['paths']['cache'].'/neato_'.str_replace('.', '', microtime(1)).'.cache', json_encode($cache));

// Run shutdown hooks
run_hook('shutdown');

// And that's that
$end = microtime(1);

neato_log("Your site is ready!", REPORT);
neato_log("Neato processed ".count($source_content)." source item".(count($source_content) === 1 ? null : 's').".", REPORT);
neato_log("It took ".pretty_time($end - $start).".", REPORT);
neato_log("Thank you for using Neato.", REPORT);
