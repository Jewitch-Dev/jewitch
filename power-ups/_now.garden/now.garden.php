<?php

/*

now.garden Power-Up for Neato 0.0.1-alpha
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
  
  $now_garden_config = yaml_parse(file_get_contents(__DIR__.'/now.garden.yaml'));
  
  $now_last_updated = filemtime($config['paths']['content'].$now_garden_config['page']);
  
  // Has the /now page been updated?
  if(!file_exists(__DIR__.'/now.garden.cache')) {
    $now_cache = $now_last_updated;
    file_put_contents(__DIR__.'/now.garden.cache', $now_last_updated);
  }
  else {
    $now_cache = file_get_contents(__DIR__.'/now.garden.cache');
  }
  
  // If the /now page hasn’t been updated, we’ll just bail here
  
  if($now_cache == $now_last_updated) {
    neato_log('Skipping now.garden update for @'.$now_garden_config['address'].' because the /now page has not changed');
    return false;
  }
  
  // Otherwise, we’ll ping now.garden
  neato_log('Updating now.garden for @'.$now_garden_config['address']);
  
  $post = [];
  $post['content'] = '.'; // lol, this just can't be blank
  $post['listed'] = true; // and we need to explicitly set 'listed' or we won't be listed
  $post = json_encode($post);
  
  $ch = curl_init('https://api.omg.lol/address/'.$now_garden_config['address'].'/now');
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$now_garden_config['key']));
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  $result = curl_exec($ch);
  
  echo "\n\nResult = $result\n\n";
  
  file_put_contents(__DIR__.'/now.garden.cache', $now_last_updated);
  
}, 100); // earlier priority as the timing here isn't really important
