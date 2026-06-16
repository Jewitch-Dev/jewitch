<?php

/*

Atom Power-Up for Neato 0.0.1-alpha
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

$parts = explode('/', __FILE__);
$this_power_up_base_path = implode('/', array_slice($parts, 0, -1));

function html_to_xhtml_div(string $html): string {
  $fragmentDoc = new DOMDocument();
  @$fragmentDoc->loadHTML(
    '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $html . '</body></html>',
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
  );

  $xhtmlDoc = new DOMDocument('1.0', 'UTF-8');
  $div = $xhtmlDoc->createElementNS('http://www.w3.org/1999/xhtml', 'div');

  $body = $fragmentDoc->getElementsByTagName('body')->item(0);
  foreach ($body->childNodes as $node) {
    $imported = $xhtmlDoc->importNode($node, true);
    $div->appendChild($imported);
  }

  $xhtmlDoc->appendChild($div);
  return $xhtmlDoc->saveXML($div);
}

add_hook('shutdown', function() {
  global $running_pages, $config, $this_power_up_base_path;
  
  $atom_config = yaml_parse(file_get_contents(__DIR__.'/atom.yaml'));
  
  neato_log('Generating an Atom feed...', JOB);
  
  $replacements = $atom_config['meta'];
  $replacements['feed_updated'] = date("c", strtotime($running_pages[0]['modified']));
  
  $atom_feed = file_get_contents($this_power_up_base_path.'/atom_feed.xml');
  
  foreach($replacements as $key => $val) {
    $atom_feed = str_replace('{'.$key.'}', $val, $atom_feed);
  }
  
  $entry_template = file_get_contents($this_power_up_base_path.'/atom_entry.xml');
  
  $entries = '';
  
  // Sort newest to oldest
  usort($running_pages, function ($a, $b) {
    return strtotime($b['modified']) <=> strtotime($a['modified']);
  });
  
  foreach($running_pages as $page) {
    
    // Process exclusions
    if(isset($page['properties'][$atom_config['properties']['exclude']])) continue;
    
    // Process inclusions
    if(isset($page['properties'][$atom_config['properties']['include']])) {
      goto add_to_feed;
    }
    
    // Exclude anything not in the defined path
    $path = $atom_config['path'];
    if(substr($path, 0, 1) == '/') $path = substr($path, 1);
    if(substr($path, -1) !== '/') $path = $path.'/';
    if(!str_starts_with($page['uri'], $path)) continue;
    
    add_to_feed:
    
    // Copy the template for this page
    $page_xml = $entry_template;
    
    $entry_replacements = [];
    
    // Craft an RFC 4151-compliant tag URI
    $id = 'tag:'.$config['site']['domain'].','.$config['site']['date_registered'].':'.$page[$config['metadata']['id_key']];
    
    if(!isset($page['title'])) {
      $page['title'] = '';
    }
    
    $entry_replacements['title'] = $page['title'];
    $entry_replacements['id'] = $id;
    $entry_replacements['link'] = 'https://'.$config['site']['domain'].'/'.$page['uri'];
    $entry_replacements['published'] = date("c", strtotime($page['created']));
    $entry_replacements['updated'] = date("c", strtotime($page['modified']));
    $entry_replacements['content'] = html_to_xhtml_div($page['content']);
    
    foreach($entry_replacements as $key => $val) {
      $page_xml = str_replace('{'.$key.'}', $val, $page_xml);
    }
    
    $entries .= $page_xml;
  }
  
  $atom_feed = str_replace('{entries}', $entries, $atom_feed);
  file_put_contents($config['paths']['published'].'feed.xml', $atom_feed);
  
  // Copy the XSL file over
  copy($this_power_up_base_path.'/feed.xsl', $config['paths']['published'].'/feed.xsl');
  
  neato_log('The Atom feed has been generated.', INFO);
});
