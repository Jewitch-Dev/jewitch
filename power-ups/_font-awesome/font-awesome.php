<?php

/*

Font Awesome Power-Up for Neato 0.0.1-alpha
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

if(!is_dir($config['paths']['assets'].'/font-awesome')) {
	neato_log('Can\'t find your font-awesome directory! You\'ll need to add it to use the Font Awesome Power-Up.', ERROR);
	exit;
}

$font_awesome_config = yaml_parse(file_get_contents(__DIR__.'/font-awesome.yaml'));

add_hook('after_page', function($data) {
	global $data, $config, $font_awesome_config;
	
	neato_log("∟ Converting Font Awesome icons on {$data['item']}", JOB);
	
	$blocks = parse_function_tags($data['content']);
	
	foreach($blocks as $block) {
		if($block['function'] === 'fa') {
			
			// Fixed-width detection
			if(substr($block['parameter'], -4) == '[fw]') {
				$block['parameter'] = substr($block['parameter'], 0, -4);
				$fw = true;
			}
			else {
				$fw = false;
			}
			
			$icon = file_get_contents($config['paths']['assets'].'/font-awesome/svgs/'.$block['parameter'].'.svg');
			$icon = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $icon);
			$icon = str_replace("\n", '', $icon);
			$icon = preg_replace('/\s*(width="\d+"\s*height="\d+"|height="\d+"\s*width="\d+")\s*/', ' ', $icon);
			$icon = str_replace('<svg ', '<svg class="icon" height="'.$font_awesome_config['height'].'" ', $icon);
			
			if($fw) {
				$icon = str_replace('<svg ', '<svg style="display: inline-block; width: '.$font_awesome_config['fw-width'].'; text-align: center; vertical-align: middle; margin-right: .4em;" ', $icon);
			}
			
			$icon = preg_replace('/<!--.*?-->/s', '', $icon);
			$data['content'] = str_replace($block['match'], $icon, $data['content']);
		}
	}
});

add_hook('after_template', function(&$rendered) {
	global $config, $font_awesome_config;
  
	neato_log("∟ Converting Font Awesome icons on completed page", JOB);
	
	$blocks = parse_function_tags($rendered);
	
	foreach($blocks as $block) {
		if($block['function'] === 'fa') {
			
			if(substr($block['parameter'], -4) == '[fw]') {
				$block['parameter'] = substr($block['parameter'], 0, -4);
				$fw = true;
			}
			else {
				$fw = false;
			}
			
			$icon = file_get_contents($config['paths']['assets'].'/font-awesome/svgs/'.$block['parameter'].'.svg');
			
			$icon = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $icon);
			$icon = str_replace("\n", '', $icon);
			$icon = preg_replace('/\s*(width="\d+"\s*height="\d+"|height="\d+"\s*width="\d+")\s*/', ' ', $icon);
			$icon = str_replace('<svg ', '<svg class="icon" height="'.$font_awesome_config['height'].'" ', $icon);
			if($fw) {
				$icon = str_replace('<svg ', '<svg style="display: inline-block; width: '.$font_awesome_config['fw-width'].'; text-align: center; vertical-align: middle; margin-right: .4em;" ', $icon);
			}
			$icon = preg_replace('/<!--.*?-->/s', '', $icon);
			$rendered = str_replace($block['match'], $icon, $rendered);
		}
	}
}, 100);
