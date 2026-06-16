<?php

/*

rsync Power-Up for Neato 0.0.1-alpha
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
  
  $rsync_config = yaml_parse(file_get_contents(__DIR__.'/rsync.yaml'));
  
  neato_log("Running rsync...");
  
  // rsync -avz -e "ssh -p 22" ./public/ user@server:/var/www/site/
  $command = 'rsync -avz -e \'ssh -p '.$rsync_config['rsync_port'].'\' '.$config['paths']['published'].' '.$rsync_config['rsync_remote'];
  
  neato_log("… rsync: $command", JOB);
  
  #passthru($command);
  $handle = popen($command.' 2>&1', 'r');
  while(!feof($handle)) {
    $line = fgets($handle);
    $line = str_replace(array("\r", "\n"), '', $line);
    neato_log("… rsync: $line", JOB);
    flush();
  }
  pclose($handle);
  
}, 1000); // priority set to 1000 to run last
