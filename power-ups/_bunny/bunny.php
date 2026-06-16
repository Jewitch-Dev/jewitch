<?php

/*

Bunny Power-Up for Neato 0.0.1-alpha
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

// Need a Bunny account?
// Support Neato by signing up with this URL!
// https://bunny.net?ref=78iezsfq3y

add_hook('shutdown', function() {
	global $config;
	
	$bunny_config = yaml_parse(file_get_contents(__DIR__.'/bunny.yaml'));
	
	neato_log("Uploading to Bunny...");
	
	// Need to add more config here
	$remote_base_path = '';
	$remote_base_path = trim($remote_base_path, "/"); // Bunny paths are URL paths
	
	function join_path(...$parts) {
		$out = [];
		foreach($parts as $p) {
			$p = trim($p, "/");
			if($p !== "") $out[] = $p;
		}
		return implode("/", $out);
	}
	
	function guess_content_type($filePath) {
		if(function_exists('mime_content_type')) {
			$mt = @mime_content_type($filePath);
			if(is_string($mt) && $mt !== '') return $mt;
		}
		return 'application/octet-stream';
	}
	
	function bunny_storage_upload($storage_host, $zone_name, $access_key, $remote_path, $local_file, bool $dry_run = false) {
		$remote_path = ltrim($remote_path, "/");
		$url = "https://{$storage_host}/" . rawurlencode($zone_name) . "/" . str_replace('%2F', '/', rawurlencode($remote_path));
		
		$size = filesize($local_file);
		if($size === false) {
			throw new RuntimeException("Unable to stat file: {$local_file}");
		}
		
		$contentType = guess_content_type($local_file);
		
		neato_log("PUT $url", JOB);
		
		if($dry_run) return;
		
		$fh = fopen($local_file, "rb");
		if(!$fh) throw new RuntimeException("Unable to open file: {$local_file}");
		
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_CUSTOMREQUEST  => 'PUT',
			CURLOPT_HTTPHEADER     => [
				"AccessKey: {$access_key}",
				"Content-Type: {$contentType}",
				"Content-Length: {$size}",
			],
			CURLOPT_UPLOAD         => true,
			CURLOPT_INFILE         => $fh,
			CURLOPT_INFILESIZE     => $size,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FAILONERROR    => false,
		]);
		
		$body = curl_exec($ch);
		$err  = curl_error($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		
		fclose($fh);
		
		if($body === false) {
			throw new RuntimeException("cURL error uploading {$local_file}: {$err}");
		}
		if($code < 200 || $code >= 300) {
			throw new RuntimeException("Upload failed ({$code}) for {$remote_path}. Response: " . trim((string)$body));
		}
	}
	
	function bunnyPurgePullZone(int $pull_zone_id, $account_api_key, bool $dry_run = false) {
		$url = "https://api.bunny.net/pullzone/{$pull_zone_id}/purgeCache";
		neato_log("POST $url", JOB);
		
		if($dry_run) return;
		
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_POST           => true,
			CURLOPT_HTTPHEADER     => [
					"AccessKey: {$account_api_key}",
					"Content-Type: application/json",
			],
			CURLOPT_POSTFIELDS     => json_encode(new stdClass(), JSON_UNESCAPED_SLASHES),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FAILONERROR    => false,
		]);

		$body = curl_exec($ch);
		$err  = curl_error($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		
		if($body === false) {
			throw new RuntimeException("cURL error purging pull zone: {$err}");
		}
		if($code < 200 || $code >= 300) {
			throw new RuntimeException("Purge failed ({$code}). Response: " . trim((string)$body));
		}
		
		neato_log("Bunny cache purged.");
	}
	
	function shouldSkip($path, bool $skipHidden): bool {
		if(!$skipHidden) return false;
		$base = basename($path);
		return str_starts_with($base, '.');
	}
	
	$rii = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($config['paths']['published'], FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::LEAVES_ONLY
	);
	
	$uploaded = 0;
	
	// Load the Neato cache so we can determine if files need to be uploaded or not
	$cache_contents = glob($config['paths']['cache'].'*');
	
	if(count($cache_contents) <= 2) {
		$second_newest = null;
	}
	else {
		rsort($cache_contents, SORT_STRING);
		$second_newest = $cache_contents[1];
		
		$cache = json_decode(file_get_contents($second_newest), 1);
		
		$files_to_delete = array_slice($cache_contents, 2);
		
		foreach ($files_to_delete as $file) {
			unlink($file);
		}
		
	}
	
	foreach($rii as $file_info) {
		
		if(filesize($file_info->getPathname()) === @$cache[$file_info->getPathname()]) {
			neato_log('Skipping '.$file_info->getPathname().'; unchanged');
			continue;
		}
		
		if(!$file_info->isFile()) continue;
		
		$fullPath = $file_info->getPathname();
		if(shouldSkip($fullPath, (bool)$bunny_config['skip_hidden_files'])) continue;
		
		$rel = substr($fullPath, strlen($config['paths']['published']));
		$rel = ltrim(str_replace('\\', '/', $rel), '/');
		
		$remote_path = join_path($remote_base_path, $rel);
		
		try {
			bunny_storage_upload(
				$bunny_config['storage_host'],
				$bunny_config['storage_zone_name'],
				$bunny_config['storage_zone_password'],
				$remote_path,
				$fullPath,
				(bool)$bunny_config['dry_run']
			);
			$uploaded++;
		} catch (Throwable $e) {
			fwrite(STDERR, "ERROR: {$e->getMessage()}\n");
			exit(1);
		}
	}
	
	$file_word = $uploaded == 1 ? 'file' : 'files';
	neato_log("Uploaded {$uploaded} $file_word to Bunny.");
	
	if(!empty($bunny_config['purge_cdn'])) {
		try {
			bunnyPurgePullZone((int)$bunny_config['pull_zone_id'], $bunny_config['account_api_key'], (bool)$bunny_config['dry_run']);
		} catch (Throwable $e) {
			fwrite(STDERR, "ERROR: {$e->getMessage()}\n");
			exit(1);
		}
	}
});
