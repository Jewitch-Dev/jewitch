<?php

/*

Pagefind Power-Up for Neato 0.0.1-alpha
https://www.neato.pub

Copyright 2026 Neatnik LLC

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

add_hook('shutdown', function() {
	global $config;
	
	neato_log("Running Pagefind...");
	
  // We need the Pagefind binary in the Power-Up directory in order to proceed
  if(!file_exists(__DIR__.'/pagefind')) {
    neato_log('The Pagefind binary needs to be placed into the Pagefind Power-Up directory. Please download the binary for your system by visiting https://github.com/pagefind/pagefind/releases.', ERROR);
    exit;
  }
  
  $command = str_replace(' ', '\ ', __DIR__).'/pagefind --site "'.__DIR__.'/../../published" --output-path "'.__DIR__.'/../../content/pagefind"';
  
  neato_log("… Pagefind: $command", JOB);
  
  $handle = popen($command.' 2>&1', 'r');
  while(!feof($handle)) {
    $line = fgets($handle);
    $line = str_replace(array("\r", "\n"), '', $line);
    neato_log("… Pagefind: $line", JOB);
    flush();
  }
  pclose($handle);
  
}, 900); // priority set to 900 to run second-to-last, before rsync (which is 1000 and last)
