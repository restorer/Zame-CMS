<?php

ob_start('ob_gzhandler');
require_once('s/s.php');

function process()
{
	$cache_dir = conf('cache.path') . 'proxy';

	if (!file_exists($cache_dir)) {
		mkdir($cache_dir);
		chmod($cache_dir, 0777);
	}

	$files = array_filter(array_map(
		create_function('$v', 'return preg_replace("/[^a-z0-9\-_]/i","",$v);'),
		explode('.', ltrim(_SERVER('PATH_INFO'), '/'))
	));

	if (count($files) < 2) {
		return;
	}

	$type = array_splice($files, -1, 1);
	$type = $type[0];

	if (!in_array($type, array('js', 'css'))) {
		return;
	}

	$base_path = BASE . "{$type}/";
	$cache_path = "{$cache_dir}/" . rawurlencode(join('.', $files)) . ".{$type}";

	if (is_readable($cache_path)) {
		$cache_mtime = filemtime($cache_path);
	} else {
		$cache_mtime = 0;
	}

	if ($cache_mtime) {
		$last_mtime = 0;

		foreach ($files as $name) {
			$last_mtime = max($last_mtime, filemtime("{$base_path}{$name}.{$type}"));
		}

		if ($last_mtime > $cache_mtime) {
			$cache_mtime = 0;
		}
	}

	if (!$cache_mtime) {
		$cached = array();

		foreach ($files as $name) {
			$body = file_get_contents("{$base_path}{$name}.{$type}");

			if ($type == 'css') {
				$body = preg_replace('@/\*.*?\*/@s', '', $body);
				$body = str_replace('{ROOT}', ROOT, $body);
			}

			// bug with stripping comments in js: { foo: "foo/*", bar: "bar*/" }

			$body = join("\n", array_filter(array_map(
				create_function('$v', 'return trim($v);'),
				explode("\n", str_replace("\r", "\n", $body))
			)));

			$cached[] = $body;
		}

		$cached = join("\n\n", $cached);

		file_put_contents($cache_path, $cached);
		chmod($cache_path, 0666);

		$cache_mtime = filemtime($cache_path);
	} else {
		$cached = file_get_contents($cache_path);
	}

	session_cache_limiter('public');
	header('Last-Modified: ' . gmdate('r', $cache_mtime));
	header('Pragma: public');

	if ($type == 'js') {
		header('Content-Type: application/x-javascript');
	} else {	// css
		header('Content-Type: text/css');
	}

	echo $cached;
}

process();
